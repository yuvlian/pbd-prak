use actix_session::Session;
use actix_web::{HttpResponse, Scope, web};
use sqlx::MySqlPool;

use crate::api::{LoginReq, RegisterReq, SessionUser};
use crate::db;
use crate::db::models::MeOutApi;
use crate::error::AppError;
use crate::routes::{require_live_user, validate};

pub fn scope() -> Scope {
    web::scope("/auth")
        .route("/register", web::post().to(register))
        .route("/login", web::post().to(login))
        .route("/logout", web::post().to(logout))
        .route("/me", web::get().to(me))
}

// POST /api/auth/register
async fn register(
    pool: web::Data<MySqlPool>,
    body: web::Json<RegisterReq>,
) -> Result<HttpResponse, AppError> {
    let b = body.into_inner();
    let username = validate::username(&b.username)?;
    validate::email(&b.email)?;
    validate::password(&b.password)?;

    let hash = bcrypt::hash(&b.password, bcrypt::DEFAULT_COST)?;
    let user_id = db::users::create(&pool, &username, &b.email, &hash).await?;

    Ok(HttpResponse::Created().json(SessionUser { user_id, username }))
}

// POST /api/auth/login
async fn login(
    pool: web::Data<MySqlPool>,
    session: Session,
    body: web::Json<LoginReq>,
) -> Result<HttpResponse, AppError> {
    let b = body.into_inner();

    let user = db::users::find_by_username(&pool, b.username.trim())
        .await?
        .ok_or(AppError::Unauthorized)?;

    if !bcrypt::verify(&b.password, &user.password_hash)? {
        return Err(AppError::Unauthorized);
    }

    session
        .insert("user_id", user.user_id)
        .map_err(|_| AppError::Unauthorized)?;

    Ok(HttpResponse::Ok().json(SessionUser {
        user_id: user.user_id,
        username: user.username,
    }))
}

// POST /api/auth/logout
async fn logout(session: Session) -> Result<HttpResponse, AppError> {
    session.purge();
    Ok(HttpResponse::NoContent().finish())
}

// GET /api/auth/me
async fn me(pool: web::Data<MySqlPool>, session: Session) -> Result<web::Json<MeOutApi>, AppError> {
    let user_id = require_live_user(&pool, &session).await?;
    Ok(web::Json(db::users::me(&pool, user_id).await?))
}
