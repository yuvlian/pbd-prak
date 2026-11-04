use chrono::NaiveDateTime;
use serde::{Deserialize, Serialize};
use sqlx::FromRow;

use crate::error::AppError;

#[derive(Debug, Clone, Copy, PartialEq, Eq, Serialize, Deserialize)]
#[serde(rename_all = "snake_case")]
pub enum Status {
    Offline,
    Online,
}

impl Status {
    pub fn id(self) -> u32 {
        match self {
            Self::Offline => 1,
            Self::Online => 2,
        }
    }

    pub fn from_name(name: &str) -> Option<Self> {
        match name {
            "offline" => Some(Self::Offline),
            "online" => Some(Self::Online),
            _ => None,
        }
    }
}

#[derive(Debug, Clone, Copy, PartialEq, Eq, Serialize, Deserialize)]
#[serde(rename_all = "snake_case")]
pub enum GroupRole {
    Owner,
    Member,
}

impl GroupRole {
    pub fn id(self) -> u32 {
        match self {
            Self::Owner => 1,
            Self::Member => 2,
        }
    }

    pub fn name(self) -> &'static str {
        match self {
            Self::Owner => "owner",
            Self::Member => "member",
        }
    }

    pub fn from_name(name: &str) -> Option<Self> {
        match name {
            "owner" => Some(Self::Owner),
            "member" => Some(Self::Member),
            _ => None,
        }
    }
}

// v_users pretty much
#[allow(dead_code)]
#[derive(Debug, FromRow)]
pub struct User {
    pub user_id: u32,
    pub username: String,
    pub email: String,
    pub password_hash: String,
    pub role_id: u32,
    pub status_id: u32,
    pub created_at: NaiveDateTime,
}

// rsp too for:
// GET /api/auth/me
// PATCH /api/users/me
#[derive(Debug, FromRow)]
pub struct MeOut {
    pub user_id: u32,
    pub username: String,
    pub email: String,
    pub role: String,
    pub status: String,
}

impl MeOut {
    pub fn api(self) -> Result<MeOutApi, AppError> {
        Ok(MeOutApi {
            user_id: self.user_id,
            username: self.username,
            email: self.email,
            role: GroupRole::from_name(&self.role).ok_or(AppError::Internal)?,
            status: Status::from_name(&self.status).ok_or(AppError::Internal)?,
        })
    }
}

#[derive(Debug, Serialize)]
pub struct MeOutApi {
    pub user_id: u32,
    pub username: String,
    pub email: String,
    pub role: GroupRole,
    pub status: Status,
}

#[derive(Debug, FromRow)]
pub struct ConversationList {
    pub conversation_id: u32,
    pub title: String,
    pub my_role: String,
}

impl ConversationList {
    pub fn api(self) -> Result<ConversationListApi, AppError> {
        Ok(ConversationListApi {
            conversation_id: self.conversation_id,
            title: self.title,
            my_role: GroupRole::from_name(&self.my_role).ok_or(AppError::Internal)?,
        })
    }
}

#[derive(Debug, Serialize)]
pub struct ConversationListApi {
    pub conversation_id: u32,
    pub title: String,
    pub my_role: GroupRole,
}

#[derive(Debug, FromRow)]
pub struct MemberOut {
    pub user_id: u32,
    pub username: String,
    pub status: String,
    pub role: String,
}

impl MemberOut {
    pub fn api(self) -> Result<MemberOutApi, AppError> {
        Ok(MemberOutApi {
            user_id: self.user_id,
            username: self.username,
            status: Status::from_name(&self.status).ok_or(AppError::Internal)?,
            role: GroupRole::from_name(&self.role).ok_or(AppError::Internal)?,
        })
    }
}

#[derive(Debug, Serialize)]
pub struct MemberOutApi {
    pub user_id: u32,
    pub username: String,
    pub status: Status,
    pub role: GroupRole,
}

// sender stuff none -> system
#[derive(Debug, FromRow, Serialize)]
pub struct MessageOut {
    pub message_id: u32,
    pub conversation_id: u32,
    pub sender_id: Option<u32>,
    pub sender_name: Option<String>,
    pub content: String,
    pub sent_at: NaiveDateTime,
}
