use serde::{Deserialize, Serialize};

use crate::db::models::{MemberOutApi, MessageOut};

pub fn wire<T: serde::Serialize>(ev: &T) -> String {
    serde_json::to_string(ev).expect("event types always serialize")
}

// ---- auth ----

#[derive(Debug, Deserialize)]
pub struct RegisterReq {
    pub username: String,
    pub email: String,
    pub password: String,
}

#[derive(Debug, Deserialize)]
pub struct LoginReq {
    pub username: String,
    pub password: String,
}

#[derive(Debug, Serialize)]
pub struct SessionUser {
    pub user_id: u32,
    pub username: String,
}

// ---- users ----

#[derive(Debug, Deserialize)]
pub struct RenameUserReq {
    pub username: String,
}

// ---- conversations ----

#[derive(Debug, Deserialize)]
pub struct CreateConvReq {
    pub name: String,
    pub member_names: Vec<String>,
}

#[derive(Debug, Deserialize)]
pub struct AddMemberReq {
    pub username: String,
}

#[derive(Debug, Deserialize)]
pub struct RenameGroupReq {
    pub name: String,
}

#[derive(Debug, Serialize)]
pub struct ConversationDetail {
    pub conversation_id: u32,
    pub group_id: u32,
    pub title: String,
    pub members: Vec<MemberOutApi>,
}

// ---- messages ----

#[derive(Debug, Deserialize)]
pub struct SendMsgReq {
    pub content: String,
}

#[derive(Debug, Deserialize)]
pub struct HistoryQuery {
    pub before_id: Option<u32>,
}

// ---- misc responses ----

#[derive(Debug, Serialize)]
pub struct ErrorResponse {
    pub error: String,
}

#[derive(Debug, Serialize)]
pub struct CreatedConversation {
    pub conversation_id: u32,
}

/// GET /debug/view/{name} dump.
#[derive(Debug, Serialize)]
pub struct DebugTable {
    pub name: String,
    pub columns: Vec<String>,
    pub rows: Vec<Vec<String>>,
}

// ---- live-room (websocket) events ----

#[derive(Debug, Serialize)]
#[serde(rename_all = "snake_case")]
pub enum EventOp {
    MessageNew,
    Presence,
    UserRenamed,
    GroupRenamed,
    GroupEvent,
}

#[derive(Debug, Serialize)]
#[serde(rename_all = "snake_case")]
pub enum GroupEventKind {
    MemberAdded,
    MemberRemoved,
    MemberLeft,
    GroupDeleted,
}

pub fn message_new(message: &MessageOut) -> MessageNewEvent<'_> {
    MessageNewEvent {
        op: EventOp::MessageNew,
        message,
    }
}

#[derive(Debug, Serialize)]
pub struct MessageNewEvent<'a> {
    pub op: EventOp,
    pub message: &'a MessageOut,
}

#[derive(Debug, Serialize)]
pub struct PresenceEvent {
    pub op: EventOp,
    pub user_id: u32,
    pub status: PresenceStatus,
}

#[derive(Debug, Serialize)]
#[serde(rename_all = "snake_case")]
pub enum PresenceStatus {
    Online,
    Offline,
}

#[derive(Debug, Serialize)]
pub struct UserRenamedEvent {
    pub op: EventOp,
    pub user_id: u32,
    pub username: String,
}

#[derive(Debug, Serialize)]
pub struct GroupRenamedEvent {
    pub op: EventOp,
    pub conversation_id: u32,
    pub name: String,
}

#[derive(Debug, Serialize)]
pub struct GroupEvent<'a> {
    pub op: EventOp,
    pub kind: GroupEventKind,
    pub conversation_id: u32,
    pub user_ids: &'a [u32],
}

// ---- client -> server ops ----

#[derive(Debug, Deserialize)]
#[serde(tag = "op", rename_all = "snake_case")]
pub enum ClientOp {
    #[serde(alias = "delivered")]
    Delivered,
    #[serde(alias = "read")]
    Read,
}
