mod api;
mod db;
mod error;
mod routes;
mod ws;

use std::env;
use std::io;

use actix_files::Files;
use actix_session::{SessionMiddleware, storage::CookieSessionStore};
use actix_web::{App, HttpServer, cookie::Key, web};
use sqlx::mysql::MySqlPoolOptions;

use crate::ws::hub::ChatHub;

#[tokio::main]
async fn main() -> io::Result<()> {
    dotenvy::dotenv_override().ok();

    tracing_subscriber::fmt()
        .with_env_filter(
            tracing_subscriber::EnvFilter::try_from_default_env().unwrap_or_else(|_| "info".into()),
        )
        .init();

    let database_url = env::var("DATABASE_URL").expect("DATABASE_URL env var not set");
    let session_secret = env::var("SESSION_SECRET").expect("SESSION_SECRET env var not set");

    let pool = MySqlPoolOptions::new()
        .max_connections(5)
        .connect(&database_url)
        .await
        .expect("mysql pool connect failed");

    sqlx::migrate!()
        .run(&pool)
        .await
        .map_err(io::Error::other)?;

    tracing::info!("migrations applied");

    let hub = ChatHub::default();
    let key = Key::from(session_secret.as_bytes());

    let host = env::var("HOST").unwrap_or_else(|_| "127.0.0.1".into());
    let port: u16 = env::var("PORT")
        .unwrap_or_else(|_| "8080".into())
        .parse()
        .expect("PORT must be a number");

    tracing::info!("listening on {host}:{port}");

    HttpServer::new(move || {
        let app = App::new()
            .app_data(web::Data::new(pool.clone()))
            .app_data(web::Data::new(hub.clone()))
            .wrap(
                SessionMiddleware::builder(CookieSessionStore::default(), key.clone())
                    .cookie_secure(false)
                    .build(),
            )
            .service(routes::api_scope())
            .service(routes::pages::page_root())
            .service(routes::pages::page_app())
            .service(routes::pages::page_debug());

        #[cfg(debug_assertions)]
        let app = app.service(routes::debug_scope());

        app.wrap(
            actix_web::middleware::DefaultHeaders::new()
                // THIS CACHE PISSES ME OFFFFFFFFFFFFFFFFFFFFFFF
                .add((actix_web::http::header::CACHE_CONTROL, "no-cache")),
        )
        .service(Files::new("/", "./static").index_file("index.html"))
    })
    .bind((host.as_str(), port))?
    .run()
    .await
}
