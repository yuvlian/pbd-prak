use actix_session::Session;
use actix_web::{HttpResponse, Scope, web};
use actix_ws::Message;
use futures_util::StreamExt;
use sqlx::MySqlPool;

use crate::api::*;
use crate::db;
use crate::db::models::Status;
use crate::error::AppError;
use crate::routes::require_live_user;
use crate::ws::hub::ChatHub;

#[derive(Debug, serde::Deserialize)]
pub struct WsQuery {
    pub conversation_id: Option<u32>,
}

pub fn scope() -> Scope {
    web::scope("/ws").route("", web::get().to(ws))
}

// `?conversation_id=N` join convo
async fn ws(
    req: actix_web::HttpRequest,
    payload: web::Payload,
    pool: web::Data<MySqlPool>,
    hub: web::Data<ChatHub>,
    session: Session,
    query: web::Query<WsQuery>,
) -> Result<HttpResponse, AppError> {
    let me = require_live_user(&pool, &session).await?;

    if let Some(cid) = query.conversation_id {
        db::conversations::assert_member(&pool, cid, me).await?;
    }

    let hub_key = query.conversation_id.unwrap_or(0);

    let (response, mut ws_session, mut msg_stream) = actix_ws::handle(&req, payload)
        .map_err(|_| AppError::BadRequest("ws handshake failed".into()))?;

    let (tx, mut rx) = tokio::sync::mpsc::unbounded_channel::<String>();
    let came_online = hub.join(hub_key, me, tx);

    if came_online {
        let _ = db::users::set_status(pool.get_ref(), me, Status::Online).await;
        hub.broadcast_all(
            wire(&PresenceEvent {
                op: EventOp::Presence,
                user_id: me,
                status: PresenceStatus::Online,
            })
            .as_str(),
        );
    }

    let pool = pool.get_ref().clone();
    let hub = hub.clone();

    actix_web::rt::spawn(async move {
        let mut writer = ws_session.clone();
        actix_web::rt::spawn(async move {
            while let Some(text) = rx.recv().await {
                if writer.text(text).await.is_err() {
                    break;
                }
            }
        });

        // reader side
        let mut close_reason = None;
        while let Some(Ok(msg)) = msg_stream.next().await {
            match msg {
                Message::Text(bytes) => {
                    let Ok(_op) = serde_json::from_slice::<ClientOp>(bytes.as_bytes()) else {
                        continue;
                    };
                }
                Message::Ping(bytes) => {
                    if ws_session.pong(&bytes).await.is_err() {
                        break;
                    }
                }
                Message::Close(reason) => {
                    close_reason = reason;
                    break;
                }
                _ => {}
            }
        }

        let _ = ws_session.close(close_reason).await;
        let went_offline = hub.leave(hub_key, me);
        if went_offline {
            let _ = db::users::set_status(&pool, me, Status::Offline).await;
            hub.broadcast_all(
                wire(&PresenceEvent {
                    op: EventOp::Presence,
                    user_id: me,
                    status: PresenceStatus::Offline,
                })
                .as_str(),
            );
        }
    });

    Ok(response)
}
