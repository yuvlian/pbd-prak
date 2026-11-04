use actix_session::Session;
use actix_web::{HttpResponse, Scope, web};
use sqlx::MySqlPool;

use crate::api::*;
use crate::db;
use crate::db::models::*;
use crate::error::AppError;
use crate::routes::{require_live_user, validate};
use crate::ws::hub::ChatHub;

pub fn scope() -> Scope {
    web::scope("/conversations")
        .route("", web::get().to(list))
        .route("", web::post().to(create))
        .route("/{id}", web::get().to(detail))
        .route("/{id}", web::delete().to(delete_group))
        .route("/{id}/name", web::put().to(rename))
        .route("/{id}/members", web::post().to(add_member))
        .route("/{id}/members/{user_id}", web::delete().to(remove_member))
        .route("/{id}/leave", web::delete().to(leave))
        .service(
            web::scope("/{id}/messages")
                .route("", web::get().to(super::messages::list))
                .route("", web::post().to(super::messages::send)),
        )
}

fn group_event(conversation_id: u32, kind: GroupEventKind, user_ids: &[u32]) -> GroupEvent<'_> {
    GroupEvent {
        op: EventOp::GroupEvent,
        kind,
        conversation_id,
        user_ids,
    }
}

// GET /api/conversations
async fn list(
    pool: web::Data<MySqlPool>,
    session: Session,
) -> Result<web::Json<Vec<ConversationListApi>>, AppError> {
    let me = require_live_user(&pool, &session).await?;
    Ok(web::Json(
        db::conversations::list_for_user(&pool, me).await?,
    ))
}

// POST /api/conversations
async fn create(
    pool: web::Data<MySqlPool>,
    session: Session,
    body: web::Json<CreateConvReq>,
) -> Result<HttpResponse, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let b = body.into_inner();

    let name = validate::group_name(&b.name)?;

    let mut member_ids = Vec::with_capacity(b.member_names.len());
    for raw in &b.member_names {
        let username = raw.trim();
        if username.is_empty() {
            continue;
        }

        let id = db::users::find_id_by_username(&pool, username)
            .await?
            .ok_or_else(|| AppError::BadRequest(format!("unknown user: {username}")))?;
        member_ids.push(id);
    }

    let cid = db::conversations::create_group(&pool, me, &name, &member_ids).await?;
    Ok(HttpResponse::Created().json(CreatedConversation {
        conversation_id: cid,
    }))
}

// GET /api/conversations/{id}
async fn detail(
    pool: web::Data<MySqlPool>,
    session: Session,
    path: web::Path<u32>,
) -> Result<web::Json<ConversationDetail>, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let cid = path.into_inner();
    db::conversations::assert_member(&pool, cid, me).await?;
    Ok(web::Json(db::conversations::detail(&pool, cid).await?))
}

// PUT /api/conversations/{id}/name
async fn rename(
    pool: web::Data<MySqlPool>,
    session: Session,
    path: web::Path<u32>,
    body: web::Json<RenameGroupReq>,
    hub: web::Data<ChatHub>,
) -> Result<HttpResponse, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let cid = path.into_inner();
    db::conversations::assert_owner(&pool, cid, me).await?;

    let name = validate::group_name(&body.into_inner().name)?;

    let detail = db::conversations::detail(&pool, cid).await?;
    let old_title = detail.title.clone();
    let changed = db::conversations::rename_group(&pool, cid, &name).await?;

    if !changed {
        return Err(AppError::BadRequest("group name unchanged".into()));
    }

    let actor = db::users::find_by_id(&pool, me)
        .await?
        .map(|u| u.username)
        .unwrap_or_else(|| "someone".into());

    let notice = db::messages::insert_system(
        &pool,
        cid,
        &format!("{actor} renamed the group from {old_title} to {name}"),
    )
    .await?;

    hub.broadcast(cid, wire(&message_new(&notice)).as_str(), None);
    hub.broadcast(
        cid,
        wire(&GroupRenamedEvent {
            op: EventOp::GroupRenamed,
            conversation_id: cid,
            name: name.clone(),
        })
        .as_str(),
        None,
    );

    Ok(HttpResponse::NoContent().finish())
}

// POST /api/conversations/{id}/members
async fn add_member(
    pool: web::Data<MySqlPool>,
    session: Session,
    path: web::Path<u32>,
    body: web::Json<AddMemberReq>,
    hub: web::Data<ChatHub>,
) -> Result<HttpResponse, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let cid = path.into_inner();
    db::conversations::assert_member(&pool, cid, me).await?;

    let username = body.into_inner().username;
    let user_id = db::users::find_id_by_username(&pool, username.trim())
        .await?
        .ok_or_else(|| AppError::BadRequest(format!("unknown user: {username}")))?;

    db::conversations::add_member(&pool, cid, user_id).await?;

    let detail = db::conversations::detail(&pool, cid).await?;
    let notice =
        db::messages::insert_system(&pool, cid, &format!("{username} joined {}", detail.title))
            .await?;

    hub.broadcast(cid, wire(&message_new(&notice)).as_str(), None);
    hub.broadcast_all(&wire(&group_event(cid, GroupEventKind::MemberAdded, &[user_id])));
    Ok(HttpResponse::NoContent().finish())
}

// DELETE /api/conversations/{id}/members/{user_id}
async fn remove_member(
    pool: web::Data<MySqlPool>,
    session: Session,
    path: web::Path<(u32, u32)>,
    hub: web::Data<ChatHub>,
) -> Result<HttpResponse, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let (cid, target) = path.into_inner();
    db::conversations::assert_owner(&pool, cid, me).await?;

    if target == me {
        db::conversations::delete_group(&pool, cid).await?;
        hub.drop_conversation(cid);
        hub.broadcast_all(&wire(&group_event(cid, GroupEventKind::GroupDeleted, &[])));
        return Ok(HttpResponse::NoContent().finish());
    }

    db::conversations::remove_member(&pool, cid, target).await?;
    let target_name = db::users::find_by_id(&pool, target)
        .await?
        .map(|u| u.username)
        .unwrap_or_else(|| "unknown".into());

    let detail = db::conversations::detail(&pool, cid).await?;
    let notice = db::messages::insert_system(
        &pool,
        cid,
        &format!("{target_name} was removed from {}", detail.title),
    )
    .await?;

    hub.broadcast(cid, wire(&message_new(&notice)).as_str(), None);
    hub.broadcast_all(&wire(&group_event(cid, GroupEventKind::MemberRemoved, &[target])));
    hub.evict(cid, target);

    Ok(HttpResponse::NoContent().finish())
}

// DELETE /api/conversations/{id}/leave
// owner leave = deletes the group
async fn leave(
    pool: web::Data<MySqlPool>,
    session: Session,
    path: web::Path<u32>,
    hub: web::Data<ChatHub>,
) -> Result<HttpResponse, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let cid = path.into_inner();
    db::conversations::assert_member(&pool, cid, me).await?;

    let detail = db::conversations::detail(&pool, cid).await?;
    let mine = detail.members.iter().find(|m| m.user_id == me);

    if mine
        .and_then(|m| GroupRole::from_name(m.role.name()))
        .is_some_and(|r| r == GroupRole::Owner)
    {
        let member_ids: Vec<u32> = detail.members.iter().map(|m| m.user_id).collect();
        db::conversations::delete_group(&pool, cid).await?;

        hub.broadcast_all(&wire(&group_event(cid, GroupEventKind::GroupDeleted, &member_ids)));
        hub.drop_conversation(cid);
        return Ok(HttpResponse::NoContent().finish());
    }

    db::conversations::remove_member(&pool, cid, me).await?;
    let notice = db::messages::insert_system(
        &pool,
        cid,
        &format!(
            "{} left {}",
            mine.map(|m| m.username.as_str()).unwrap_or("someone"),
            detail.title
        ),
    )
    .await?;

    hub.broadcast(cid, wire(&message_new(&notice)).as_str(), None);
    hub.broadcast_all(&wire(&group_event(cid, GroupEventKind::MemberLeft, &[me])));
    hub.evict(cid, me);
    Ok(HttpResponse::NoContent().finish())
}

// DELETE /api/conversations/{id}
async fn delete_group(
    pool: web::Data<MySqlPool>,
    session: Session,
    path: web::Path<u32>,
    hub: web::Data<ChatHub>,
) -> Result<HttpResponse, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let cid = path.into_inner();
    db::conversations::assert_owner(&pool, cid, me).await?;

    let detail = db::conversations::detail(&pool, cid).await?;
    let member_ids: Vec<u32> = detail.members.iter().map(|m| m.user_id).collect();
    db::conversations::delete_group(&pool, cid).await?;

    hub.broadcast_all(&wire(&group_event(cid, GroupEventKind::GroupDeleted, &member_ids)));
    hub.drop_conversation(cid);
    Ok(HttpResponse::NoContent().finish())
}
