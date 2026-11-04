use actix_session::Session;
use actix_web::{HttpResponse, web};
use sqlx::MySqlPool;

use crate::api::*;
use crate::db;
use crate::db::models::MessageOut;
use crate::error::AppError;
use crate::routes::{require_live_user, validate};
use crate::ws::hub::ChatHub;

// GET /api/conversations/{id}/messages
pub async fn list(
    pool: web::Data<MySqlPool>,
    session: Session,
    path: web::Path<u32>,
    query: web::Query<HistoryQuery>,
) -> Result<web::Json<Vec<MessageOut>>, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let cid = path.into_inner();
    db::conversations::assert_member(&pool, cid, me).await?;
    Ok(web::Json(
        db::messages::history(&pool, cid, query.before_id).await?,
    ))
}

// POST /api/conversations/{id}/messages
pub async fn send(
    pool: web::Data<MySqlPool>,
    session: Session,
    path: web::Path<u32>,
    body: web::Json<SendMsgReq>,
    hub: web::Data<ChatHub>,
) -> Result<HttpResponse, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let cid = path.into_inner();

    let content = validate::message(&body.into_inner().content)?;

    db::conversations::assert_member(&pool, cid, me).await?;
    let msg = db::messages::insert(&pool, cid, me, &content).await?;

    hub.broadcast(cid, wire(&message_new(&msg)).as_str(), None);
    Ok(HttpResponse::Created().json(msg))
}
