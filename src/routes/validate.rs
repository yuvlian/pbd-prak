use crate::error::AppError;

pub fn username(raw: &str) -> Result<String, AppError> {
    let u = raw.trim();
    if !(3..=32).contains(&u.chars().count())
        || !u.chars().all(|c| c.is_ascii_alphanumeric() || c == '_')
    {
        return Err(AppError::BadRequest(
            "username must be 3-32 chars of A-Za-z0-9_".into(),
        ));
    }
    Ok(u.to_owned())
}

pub fn email(raw: &str) -> Result<String, AppError> {
    if !raw.contains('@') {
        return Err(AppError::BadRequest("invalid email".into()));
    }
    Ok(raw.to_owned())
}

pub fn password(raw: &str) -> Result<(), AppError> {
    if !(8..=72).contains(&raw.len()) {
        return Err(AppError::BadRequest("password must be 8-72 chars".into()));
    }
    Ok(())
}

pub fn group_name(raw: &str) -> Result<String, AppError> {
    let n = raw.trim();
    if n.is_empty() || n.chars().count() > 64 {
        return Err(AppError::BadRequest("group name must be 1-64 chars".into()));
    }
    Ok(n.to_owned())
}

pub fn message(raw: &str) -> Result<String, AppError> {
    let m = raw.trim();
    if m.is_empty() || m.chars().count() > 4000 {
        return Err(AppError::BadRequest("message must be 1-4000 chars".into()));
    }
    Ok(m.to_owned())
}
