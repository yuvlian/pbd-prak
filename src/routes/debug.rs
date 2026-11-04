use actix_web::{HttpRequest, HttpResponse, Scope, web};
use sqlx::{MySqlPool, Row};

use crate::api::DebugTable;
use crate::error::AppError;
use sqlx::Column;

// all of this sucks A.F. but who gaf

pub fn debug_scope() -> Scope {
    web::scope("/debug")
        .route(
            "",
            web::get().to(|| async {
                HttpResponse::Found()
                    .append_header(("Location", "/debug.html"))
                    .finish()
            }),
        )
        .route("/view/{name}", web::get().to(view))
}

pub const VIEWS: &[(&str, &str)] = &[
    (
        "users",
        "select cast(user_id as char) as user_id, username, email, role_name, status_name, cast(created_at as char) as created_at from v_users order by user_id",
    ),
    (
        "groups",
        "select cast(group_id as char) as group_id, group_name, creator_name, cast(created_at as char) as created_at from v_groups order by group_id",
    ),
    (
        "conversations",
        "select cast(conversation_id as char) as conversation_id, group_name, cast(group_creator_id as char) as group_creator_id, cast(created_at as char) as created_at from v_conversations order by conversation_id",
    ),
    (
        "conversation_members",
        "select cast(conversation_id as char) as conversation_id, cast(user_id as char) as user_id, username, status_name, role_name, cast(joined_at as char) as joined_at from v_conversation_members order by conversation_id, user_id",
    ),
    (
        "messages",
        "select cast(message_id as char) as message_id, cast(conversation_id as char) as conversation_id, cast(sender_id as char) as sender_id, sender_name, type_name, content, cast(sent_at as char) as sent_at from v_messages order by message_id",
    ),
];

// GET /debug/view/{name}
// /debug.html?view=name
async fn view(
    pool: web::Data<MySqlPool>,
    req: HttpRequest,
    path: web::Path<String>,
) -> Result<HttpResponse, AppError> {
    let name = path.into_inner();

    let wants_html = req
        .headers()
        .get("accept")
        .and_then(|a| a.to_str().ok())
        .is_some_and(|a| a.starts_with("text/html"));

    if wants_html {
        return Ok(HttpResponse::Found()
            .append_header(("Location", format!("/debug.html?view={name}")))
            .finish());
    }

    let Some((_, sql)) = VIEWS.iter().find(|(v, _)| *v == name) else {
        return Err(AppError::NotFound);
    };

    let query = sqlx::query(sqlx::AssertSqlSafe(sql.to_string()));
    let rows = query.fetch_all(pool.get_ref()).await?;
    let columns: Vec<String> = rows
        .first()
        .map(|r| r.columns().iter().map(|c| c.name().to_string()).collect())
        .unwrap_or_default();

    let data: Vec<Vec<String>> = rows
        .iter()
        .map(|row| {
            (0..columns.len())
                .map(|i| {
                    row.try_get_unchecked::<Option<String>, _>(i)
                        .ok()
                        .flatten()
                        .unwrap_or_else(|| "NULL".into())
                })
                .collect()
        })
        .collect();

    Ok(HttpResponse::Ok().json(DebugTable {
        name,
        columns,
        rows: data,
    }))
}
