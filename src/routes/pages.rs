use actix_files::NamedFile;
use actix_web::{Resource, web};

fn page(path: &'static str, file: &'static str) -> Resource {
    web::resource(path).route(web::get().to(move || async move {
        NamedFile::open(format!("./static/{file}"))
    }))
}

pub fn page_root() -> Resource {
    page("/", "index.html")
}

pub fn page_app() -> Resource {
    page("/app", "app.html")
}

pub fn page_debug() -> Resource {
    page("/debug", "debug.html")
}
