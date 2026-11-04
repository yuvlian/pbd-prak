pub mod auth;
pub mod conversations;
pub mod debug;
pub mod messages;
pub mod pages;
pub mod users;
pub mod validate;
pub mod ws;

use actix_session::Session;
use actix_web::{Scope, web};
use sqlx::MySqlPool;

use crate::db;
use crate::error::AppError;

pub async fn require_user(session: &Session) -> Result<u32, AppError> {
    session
        .get::<u32>("user_id")
        .map_err(|_| AppError::Unauthorized)?
        .ok_or(AppError::Unauthorized)
}

// cookies :drool:
pub async fn require_live_user(pool: &MySqlPool, session: &Session) -> Result<u32, AppError> {
    let user_id = require_user(session).await?;
    if db::users::find_by_id(pool, user_id).await?.is_none() {
        session.purge();
        return Err(AppError::Unauthorized);
    }
    Ok(user_id)
}

pub fn api_scope() -> Scope {
    web::scope("/api").service((
        auth::scope(),
        users::scope(),
        conversations::scope(),
        ws::scope(),
    ))
}

// this only on dev build
pub fn debug_scope() -> Scope {
    debug::debug_scope()
}
