use actix_session::Session;
use actix_web::{Scope, web};
use sqlx::MySqlPool;

use crate::api::*;
use crate::db;
use crate::db::models::*;
use crate::error::AppError;
use crate::routes::{require_live_user, validate};
use crate::ws::hub::ChatHub;

pub fn scope() -> Scope {
    web::scope("/users").route("/me", web::patch().to(rename_me))
}

// PATCH /api/users/me
async fn rename_me(
    pool: web::Data<MySqlPool>,
    session: Session,
    hub: web::Data<ChatHub>,
    body: web::Json<RenameUserReq>,
) -> Result<web::Json<MeOutApi>, AppError> {
    let me = require_live_user(&pool, &session).await?;
    let username = validate::username(&body.into_inner().username)?;

    let changed = db::users::rename(&pool, me, &username).await?;
    if !changed {
        return Err(AppError::BadRequest("username already taken".into()));
    }

    hub.broadcast_all(
        wire(&UserRenamedEvent {
            op: EventOp::UserRenamed,
            user_id: me,
            username,
        })
        .as_str(),
    );
    Ok(web::Json(db::users::me(&pool, me).await?))
}
