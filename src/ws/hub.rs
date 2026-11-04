use std::collections::HashMap;
use std::sync::{Arc, RwLock};

use tokio::sync::mpsc::UnboundedSender;

pub type Sink = UnboundedSender<String>;

#[derive(Clone, Default)]
pub struct ChatHub {
    // hub_key (conversation_id, or 0) -> user_id -> sink
    convs: Arc<RwLock<HashMap<u32, HashMap<u32, Sink>>>>,
    // user_id -> live socket count
    online: Arc<RwLock<HashMap<u32, u32>>>,
}

impl ChatHub {
    pub fn join(&self, hub_key: u32, user_id: u32, sink: Sink) -> bool {
        self.convs
            .write()
            .unwrap()
            .entry(hub_key)
            .or_default()
            .insert(user_id, sink);

        let mut online = self.online.write().unwrap();
        let count = online.entry(user_id).or_insert(0);
        *count += 1;
        *count == 1
    }

    pub fn leave(&self, hub_key: u32, user_id: u32) -> bool {
        if let Some(members) = self.convs.write().unwrap().get_mut(&hub_key) {
            members.remove(&user_id);
        }

        let mut online = self.online.write().unwrap();
        if let Some(count) = online.get_mut(&user_id) {
            *count = count.saturating_sub(1);
            if *count == 0 {
                online.remove(&user_id);
                return true;
            }
        }
        false
    }

    pub fn evict(&self, conversation_id: u32, user_id: u32) {
        if let Some(members) = self.convs.write().unwrap().get_mut(&conversation_id) {
            members.remove(&user_id);
        }
    }

    pub fn drop_conversation(&self, conversation_id: u32) {
        self.convs.write().unwrap().remove(&conversation_id);
    }

    pub fn broadcast(&self, hub_key: u32, msg: &str, except: Option<u32>) {
        let sinks: Vec<Sink> = {
            let members = self.convs.read().unwrap();
            members
                .get(&hub_key)
                .map(|m| {
                    m.iter()
                        .filter(|(uid, _)| except != Some(**uid))
                        .map(|(_, sink)| sink.clone())
                        .collect()
                })
                .unwrap_or_default()
        };
        for sink in sinks {
            let _ = sink.send(msg.to_owned());
        }
    }

    pub fn broadcast_all(&self, msg: &str) {
        let sinks: Vec<Sink> = self
            .convs
            .read()
            .unwrap()
            .values()
            .flat_map(|m| m.values().cloned())
            .collect();
        for sink in sinks {
            let _ = sink.send(msg.to_owned());
        }
    }
}
