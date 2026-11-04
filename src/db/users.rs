use sqlx::MySqlPool;
use sqlx::error::ErrorKind;

use super::models::{MeOut, MeOutApi, Status, User};
use crate::error::AppError;

pub async fn create(
    pool: &MySqlPool,
    username: &str,
    email: &str,
    password_hash: &str,
) -> Result<u32, AppError> {
    match sqlx::query!(
        "insert into users (username, email, password_hash, role_id, status_id) values (?, ?, ?, 1, 1)",
        username,
        email,
        password_hash
    )
    .execute(pool)
    .await
    {
        Ok(res) => Ok(res.last_insert_id() as u32),
        Err(sqlx::Error::Database(d)) if d.kind() == ErrorKind::UniqueViolation => {
            Err(AppError::Conflict("username or email already taken".into()))
        }
        Err(e) => Err(e.into()),
    }
}

pub async fn find_by_username(pool: &MySqlPool, username: &str) -> Result<Option<User>, AppError> {
    Ok(sqlx::query_as!(
        User,
        "select user_id, username, email, password_hash, role_id, status_id, created_at
         from v_users where username = ?",
        username
    )
    .fetch_optional(pool)
    .await?)
}

pub async fn find_by_id(pool: &MySqlPool, user_id: u32) -> Result<Option<User>, AppError> {
    Ok(sqlx::query_as!(
        User,
        "select user_id, username, email, password_hash, role_id, status_id, created_at
         from v_users where user_id = ?",
        user_id
    )
    .fetch_optional(pool)
    .await?)
}

pub async fn find_id_by_username(
    pool: &MySqlPool,
    username: &str,
) -> Result<Option<u32>, AppError> {
    Ok(
        sqlx::query_scalar!("select user_id from v_users where username = ?", username)
            .fetch_optional(pool)
            .await?,
    )
}

// false if username taken
pub async fn rename(pool: &MySqlPool, user_id: u32, username: &str) -> Result<bool, AppError> {
    match sqlx::query!(
        "update users set username = ? where user_id = ?",
        username,
        user_id
    )
    .execute(pool)
    .await
    {
        Ok(_) => Ok(true),
        Err(sqlx::Error::Database(d)) if d.kind() == ErrorKind::UniqueViolation => Ok(false),
        Err(e) => Err(e.into()),
    }
}

pub async fn set_status(pool: &MySqlPool, user_id: u32, status: Status) -> Result<(), AppError> {
    sqlx::query!("update users set status_id = ? where user_id = ?", status.id(), user_id)
        .execute(pool)
        .await?;
    Ok(())
}

pub async fn me(pool: &MySqlPool, user_id: u32) -> Result<MeOutApi, AppError> {
    let row = sqlx::query_as!(
        MeOut,
        "select user_id, username, email, role_name as `role`, status_name as `status`
         from v_users where user_id = ?",
        user_id
    )
    .fetch_one(pool)
    .await?;
    row.api()
}
