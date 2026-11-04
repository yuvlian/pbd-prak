use sqlx::error::ErrorKind;
use sqlx::{MySql, MySqlPool, Transaction};

use super::models::*;
use crate::api::ConversationDetail;
use crate::error::AppError;

fn fk_or_other(e: sqlx::Error, msg: &str) -> AppError {
    match e {
        sqlx::Error::Database(d) if d.kind() == ErrorKind::ForeignKeyViolation => {
            AppError::BadRequest(msg.into())
        }
        e => e.into(),
    }
}

pub async fn assert_member(
    pool: &MySqlPool,
    conversation_id: u32,
    user_id: u32,
) -> Result<(), AppError> {
    let exists: bool = sqlx::query_scalar!(
        r#"
        select exists(
            select 1 from conversation_members
            where conversation_id = ? and user_id = ?
        ) as "exists!: bool"
        "#,
        conversation_id,
        user_id
    )
    .fetch_one(pool)
    .await?;

    if exists {
        Ok(())
    } else {
        Err(AppError::Forbidden)
    }
}

pub async fn create_group(
    pool: &MySqlPool,
    creator: u32,
    name: &str,
    member_ids: &[u32],
) -> Result<u32, AppError> {
    let mut tx: Transaction<'_, MySql> = pool.begin().await?;

    let res = sqlx::query!(
        "insert into `groups` (group_name, created_by) values (?, ?)",
        name,
        creator
    )
    .execute(&mut *tx)
    .await?;

    let gid = res.last_insert_id() as u32;

    let res = sqlx::query!("insert into conversations (group_id) values (?)", gid)
        .execute(&mut *tx)
        .await?;

    let cid = res.last_insert_id() as u32;

    // creator is 'owner'
    sqlx::query!(
        "insert into conversation_members (conversation_id, user_id, group_role_id) values (?, ?, ?)",
        cid,
        creator,
        GroupRole::Owner.id()
    )
    .execute(&mut *tx)
    .await?;

    // everyone else 'member'
    for &uid in member_ids {
        if uid == creator {
            continue;
        }

        sqlx::query!(
            "insert into conversation_members (conversation_id, user_id, group_role_id) values (?, ?, ?)",
            cid,
            uid,
            GroupRole::Member.id()
        )
        .execute(&mut *tx)
        .await
        .map_err(|e| fk_or_other(e, "unknown member id"))?;
    }

    tx.commit().await?;
    Ok(cid)
}

// title = group name
pub async fn list_for_user(pool: &MySqlPool, me: u32) -> Result<Vec<ConversationListApi>, AppError> {
    let rows: Vec<ConversationList> = sqlx::query_as!(
        ConversationList,
        "select
            c.conversation_id as `conversation_id!`,
            g.group_name as `title!`,
            cm.role_name as `my_role!`
         from v_conversations c
         join v_conversation_members cm on cm.conversation_id = c.conversation_id and cm.user_id = ?
         join v_groups g on g.group_id = c.group_id
         order by c.conversation_id",
        me
    )
    .fetch_all(pool)
    .await?;
    rows.into_iter().map(ConversationList::api).collect()
}

pub async fn detail(
    pool: &MySqlPool,
    conversation_id: u32,
) -> Result<ConversationDetail, AppError> {
    let conv = sqlx::query!(
        "select conversation_id, group_id
         from v_conversations where conversation_id = ?",
        conversation_id
    )
    .fetch_one(pool)
    .await?;

    let title: Option<String> = sqlx::query_scalar!(
        "select group_name from v_groups where group_id = ?",
        conv.group_id
    )
    .fetch_optional(pool)
    .await?;

    let members: Vec<MemberOut> = sqlx::query_as!(
        MemberOut,
        "select user_id, username, status_name as status, role_name as `role`
         from v_conversation_members
         where conversation_id = ?
         order by username",
        conversation_id
    )
    .fetch_all(pool)
    .await?;
    let members: Result<Vec<MemberOutApi>, AppError> =
        members.into_iter().map(MemberOut::api).collect();

    Ok(ConversationDetail {
        conversation_id: conv.conversation_id,
        group_id: conv.group_id,
        title: title.unwrap_or_default(),
        members: members?,
    })
}

pub async fn add_member(
    pool: &MySqlPool,
    conversation_id: u32,
    user_id: u32,
) -> Result<(), AppError> {
    match sqlx::query!(
        "insert into conversation_members (conversation_id, user_id, group_role_id) values (?, ?, ?)",
        conversation_id,
        user_id,
        GroupRole::Member.id()
    )
    .execute(pool)
    .await
    {
        Ok(_) => Ok(()),
        Err(sqlx::Error::Database(d)) if d.kind() == ErrorKind::UniqueViolation => {
            Err(AppError::Conflict("already a member".into()))
        }
        Err(e) => Err(fk_or_other(e, "unknown user_id")),
    }
}

pub async fn assert_owner(
    pool: &MySqlPool,
    conversation_id: u32,
    user_id: u32,
) -> Result<(), AppError> {
    let role: Option<String> = sqlx::query_scalar!(
        "select role_name from v_conversation_members
         where conversation_id = ? and user_id = ?",
        conversation_id,
        user_id
    )
    .fetch_optional(pool)
    .await?;

    match role.as_deref().and_then(GroupRole::from_name) {
        Some(GroupRole::Owner) => Ok(()),
        Some(_) => Err(AppError::Forbidden),
        None => Err(AppError::NotFound),
    }
}

pub async fn remove_member(
    pool: &MySqlPool,
    conversation_id: u32,
    user_id: u32,
) -> Result<(), AppError> {
    let res = sqlx::query!(
        "delete from conversation_members
         where conversation_id = ? and user_id = ? and group_role_id <> ?",
        conversation_id,
        user_id,
        GroupRole::Owner.id()
    )
    .execute(pool)
    .await?;

    if res.rows_affected() == 0 {
        let is_owner: i64 = sqlx::query_scalar!(
            "select count(*) from conversation_members
             where conversation_id = ? and user_id = ? and group_role_id = ?",
            conversation_id,
            user_id,
            GroupRole::Owner.id()
        )
        .fetch_one(pool)
        .await?;

        if is_owner > 0 {
            return Err(AppError::BadRequest("cannot remove the group owner".into()));
        }

        return Err(AppError::NotFound);
    }
    Ok(())
}

// false if no change
pub async fn rename_group(
    pool: &MySqlPool,
    conversation_id: u32,
    name: &str,
) -> Result<bool, AppError> {
    let res = sqlx::query!(
        "update `groups` g
         join conversations c on c.group_id = g.group_id
         set g.group_name = ?
         where c.conversation_id = ?",
        name,
        conversation_id
    )
    .execute(pool)
    .await?;
    Ok(res.rows_affected() > 0)
}

pub async fn delete_group(pool: &MySqlPool, conversation_id: u32) -> Result<(), AppError> {
    let mut tx: Transaction<'_, MySql> = pool.begin().await?;

    let gid: u32 = sqlx::query_scalar!(
        "select group_id from conversations where conversation_id = ?",
        conversation_id
    )
    .fetch_optional(&mut *tx)
    .await?
    .ok_or(AppError::NotFound)?;

    sqlx::query!(
        "delete from messages where conversation_id = ?",
        conversation_id
    )
    .execute(&mut *tx)
    .await?;

    sqlx::query!(
        "delete from conversation_members where conversation_id = ?",
        conversation_id
    )
    .execute(&mut *tx)
    .await?;

    sqlx::query!(
        "delete from conversations where conversation_id = ?",
        conversation_id
    )
    .execute(&mut *tx)
    .await?;

    sqlx::query!("delete from `groups` where group_id = ?", gid)
        .execute(&mut *tx)
        .await?;

    tx.commit().await?;
    Ok(())
}
