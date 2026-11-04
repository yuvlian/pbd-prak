use sqlx::MySqlPool;

use super::models::MessageOut;
use crate::error::AppError;

pub async fn insert(
    pool: &MySqlPool,
    conversation_id: u32,
    sender_id: u32,
    content: &str,
) -> Result<MessageOut, AppError> {
    insert_typed(pool, conversation_id, Some(sender_id), content, 1).await
}

pub async fn insert_system(
    pool: &MySqlPool,
    conversation_id: u32,
    content: &str,
) -> Result<MessageOut, AppError> {
    insert_typed(pool, conversation_id, None, content, 2).await
}

async fn insert_typed(
    pool: &MySqlPool,
    conversation_id: u32,
    sender_id: Option<u32>,
    content: &str,
    type_id: u32,
) -> Result<MessageOut, AppError> {
    let res = sqlx::query!(
        "insert into messages (conversation_id, sender_id, type_id, content) values (?, ?, ?, ?)",
        conversation_id,
        sender_id,
        type_id,
        content
    )
    .execute(pool)
    .await?;

    let message_id = res.last_insert_id() as u32;
    get_one(pool, message_id).await?.ok_or(AppError::NotFound)
}

pub async fn get_one(pool: &MySqlPool, message_id: u32) -> Result<Option<MessageOut>, AppError> {
    Ok(sqlx::query_as!(
        MessageOut,
        "select message_id, conversation_id, sender_id as `sender_id?`, sender_name as `sender_name?`, content, sent_at
         from v_messages
         where message_id = ?",
        message_id
    )
    .fetch_optional(pool)
    .await?)
}

// currently limited by 50
pub async fn history(
    pool: &MySqlPool,
    conversation_id: u32,
    before_id: Option<u32>,
) -> Result<Vec<MessageOut>, AppError> {
    match before_id {
        Some(before) => Ok(sqlx::query_as!(
            MessageOut,
            "select message_id, conversation_id, sender_id as `sender_id?`, sender_name as `sender_name?`, content, sent_at
             from v_messages
             where conversation_id = ? and message_id < ?
             order by message_id desc limit 50",
            conversation_id,
            before
        )
        .fetch_all(pool)
        .await?),

        None => Ok(sqlx::query_as!(
            MessageOut,
            "select message_id, conversation_id, sender_id as `sender_id?`, sender_name as `sender_name?`, content, sent_at
             from v_messages
             where conversation_id = ?
             order by message_id desc limit 50",
            conversation_id
        )
        .fetch_all(pool)
        .await?),
    }
}
