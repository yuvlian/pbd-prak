import { api } from './api.js?v=7';
import { ChatSocket } from './ws.js?v=7';

const $ = (id) => document.getElementById(id);

const Op = Object.freeze({
  MESSAGE_NEW: 'message_new',
  PRESENCE: 'presence',
  USER_RENAMED: 'user_renamed',
  GROUP_RENAMED: 'group_renamed',
  GROUP_EVENT: 'group_event',
});

const GroupKind = Object.freeze({
  MEMBER_ADDED: 'member_added',
  MEMBER_REMOVED: 'member_removed',
  MEMBER_LEFT: 'member_left',
  GROUP_DELETED: 'group_deleted',
});

const Presence = Object.freeze({
  ONLINE: 'online',
  OFFLINE: 'offline',
});

let me = null;            // my profile (user_id, username, status)
let conversations = [];   // sidebar rows
let currentConv = null;   // ConversationList row of the open chat
let socket = null;        // ChatSocket for currentConv

// ---------- bootstrap ----------

const meRes = await api.get('/api/auth/me');
if (!meRes.ok) location.href = '/';
me = await meRes.json();
renderMe();

await refreshConversations();
setInterval(refreshConversations, 3000);

new ChatSocket(null, onWsEvent);

// ---------- identity ----------

function renderMe() {
  $('me-name').textContent = me.username;
}

$('rename-me-btn').addEventListener('click', async () => {
  const input = prompt('new username:', me.username);
  if (!input?.trim() || input.trim() === me.username) return;
  const res = await api.patch('/api/users/me', { username: input.trim() });
  if (!res.ok) {
    alert((await res.json()).error);
    return;
  }
  me = await res.json();
  renderMe();
  await refreshConversations();
  if (currentConv) await openConversation(conversations.find((c) => c.conversation_id === currentConv.conversation_id) ?? currentConv);
});

// ---------- sidebar ----------

async function refreshConversations() {
  conversations = await (await api.get('/api/conversations')).json();
  const ul = $('conv-list');
  ul.innerHTML = '';
  for (const c of conversations) {
    const li = document.createElement('li');
    li.dataset.convId = c.conversation_id;
    if (currentConv && c.conversation_id === currentConv.conversation_id) li.classList.add('active');
    const selected = currentConv && c.conversation_id === currentConv.conversation_id;
    const actions = !selected ? '' : c.my_role === 'owner'
      ? `<span class="conv-actions"><button class="conv-rename-btn" title="rename">✎</button><button class="conv-delete-btn" title="delete group">×</button></span>`
      : `<span class="conv-actions"><button class="conv-leave-btn" title="leave group">×</button></span>`;
    li.innerHTML = `<span class="conv-title">${escapeHtml(c.title)}</span>${actions}`;
    li.addEventListener('click', () => openConversation(c));
    ul.appendChild(li);
  }

  const ren = document.querySelector('.conv-rename-btn');
  if (ren) ren.addEventListener('click', (e) => { e.stopPropagation(); renameGroupPrompt(); });
  const del = document.querySelector('.conv-delete-btn');
  if (del) del.addEventListener('click', (e) => { e.stopPropagation(); deleteGroupPrompt(); });
  const lv = document.querySelector('.conv-leave-btn');
  if (lv) lv.addEventListener('click', (e) => { e.stopPropagation(); leaveGroup(); });
}

async function renameGroupPrompt() {
  if (!currentConv) return;
  const name = prompt('new group name:', currentConv.title);
  if (!name?.trim() || name.trim() === currentConv.title) return;
  const res = await api.put(`/api/conversations/${currentConv.conversation_id}/name`, { name: name.trim() });
  if (!res.ok) alert((await res.json()).error);
  // sidebar + title update via the group_renamed event
}

async function deleteGroupPrompt() {
  if (!currentConv) return;
  if (!confirm(`delete group "${currentConv.title}" for everyone?`)) return;
  const res = await api.del(`/api/conversations/${currentConv.conversation_id}`);
  if (!res.ok) alert((await res.json()).error);
  closeCurrentConv();
  await refreshConversations();
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  })[ch]);
}

// ---------- chat pane ----------

async function openConversation(c) {
  currentConv = c;
  $('messages').innerHTML = ''; // shitfix

  api.get(`/api/conversations/${c.conversation_id}`).then(async (res) => {
    if (!res.ok || currentConv?.conversation_id !== c.conversation_id) return;
    const detail = await res.json();
    const mine = detail.members.find((m) => m.user_id === me.user_id);
    const isOwner = mine && mine.role === 'owner';

    renderMembersPanel(detail.members, isOwner);
  });

  await refreshConversations();

  // one socket per conv
  if (socket) socket.close();
  socket = new ChatSocket(c.conversation_id, onWsEvent);

  const history = await (await api.get(`/api/conversations/${c.conversation_id}/messages`)).json();
  for (const msg of history.slice().reverse()) appendMessage(msg);
  $('messages').scrollTop = $('messages').scrollHeight;
}

function renderMembersPanel(members, isOwner) {
  const add = isOwner
    ? `<li class="add-member"><button id="panel-add-member-btn">+ add member</button></li>` : '';
  $('members-panel').innerHTML = `<div class="panel-title">member list</div><ul>${members.map((m) => {
    const online = m.status === Presence.ONLINE;
    const kick = isOwner && m.user_id !== me.user_id
      ? `<button class="kick-btn" data-user="${m.user_id}">×</button>` : '';
    return `<li class="member-chip" data-user="${m.user_id}"><span class="presence-dot${online ? ' online' : ''}" data-user="${m.user_id}"></span><span class="member-name">${escapeHtml(m.username)}</span><small>${escapeHtml(m.role)}</small>${kick}</li>`;
  }).join('')}${add}</ul>`;

  document.querySelectorAll('.kick-btn').forEach((b) => {
    b.addEventListener('click', async () => {
      const res = await api.del(`/api/conversations/${currentConv.conversation_id}/members/${b.dataset.user}`);
      if (!res.ok) alert((await res.json()).error);
      await refreshConversations();
      openConversation(currentConv);
    });
  });

  const addBtn = document.getElementById('panel-add-member-btn');
  if (addBtn) addBtn.addEventListener('click', addMemberPrompt);
}

async function addMemberPrompt() {
  if (!currentConv) return;
  const username = prompt('username to add:');
  if (!username?.trim()) return;
  const res = await api.post(`/api/conversations/${currentConv.conversation_id}/members`, {
    username: username.trim(),
  });
  if (!res.ok) {
    alert((await res.json()).error);
    await refreshConversations();
    openConversation(currentConv);
  }
}

function appendMessage(msg) {
  // system msg
  if (msg.sender_id == null) {
    const div = document.createElement('div');
    div.className = 'sysmsg';
    div.textContent = msg.content;
    $('messages').appendChild(div);
    $('messages').scrollTop = $('messages').scrollHeight;
    return;
  }
  const div = document.createElement('div');
  div.className = 'msg' + (msg.sender_id === me.user_id ? ' mine' : '');
  div.dataset.messageId = msg.message_id;
  div.innerHTML = `${escapeHtml(msg.content)}<div class="meta">${msg.sender_name ? escapeHtml(msg.sender_name) : ''} · ${msg.sent_at}</div>`;

  $('messages').appendChild(div);
  $('messages').scrollTop = $('messages').scrollHeight;
}

// ---------- composer ----------

$('send-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!currentConv) return;
  const input = $('send-input');
  const content = input.value.trim();
  if (!content) return;
  input.value = '';
  const res = await api.post(`/api/conversations/${currentConv.conversation_id}/messages`, { content });
  if (!res.ok) {
    const err = (await res.json()).error;
    input.value = content;
    alert(err);
  }
});

// ---------- members ----------

async function leaveGroup() {
  if (!currentConv) return;
  if (!confirm(`leave "${currentConv.title}"?`)) return;
  const res = await api.del(`/api/conversations/${currentConv.conversation_id}/leave`);
  if (!res.ok) {
    alert((await res.json()).error);
    return;
  }
  closeCurrentConv();
  await refreshConversations();
}

function closeCurrentConv() {
  if (socket) { socket.close(); socket = null; }
  currentConv = null;
  $('messages').innerHTML = '';
  $('members-panel').innerHTML = '';
  $('members-panel').hidden = true;
}

// ---------- websocket events ----------

function onWsEvent(ev) {
  switch (ev.op) {
    case Op.MESSAGE_NEW:
      if (ev.message.conversation_id === currentConv?.conversation_id) {
        appendMessage(ev.message);
      }
      refreshConversations(); // new groups from others show up here too
      break;
    case Op.PRESENCE: {
      const dot = document.querySelector(`.presence-dot[data-user="${ev.user_id}"]`);
      if (dot) dot.classList.toggle('online', ev.status === Presence.ONLINE);
      break;
    }
    case Op.USER_RENAMED:
      if (ev.user_id === me.user_id) {
        me.username = ev.username;
        renderMe();
      }
      document.querySelectorAll('.member-chip').forEach((chip) => {
        const label = chip.querySelector('.member-name');
        if (label && label.textContent === ev.old_username) {
          label.textContent = ev.username;
        }
      });
      break;
    case Op.GROUP_RENAMED:
      if (currentConv?.conversation_id === ev.conversation_id) {
        currentConv.title = ev.name;
      }
      refreshConversations();
      break;
    case Op.GROUP_EVENT: {
      if (ev.kind !== GroupKind.MEMBER_ADDED
        && (ev.user_ids.includes(me.user_id) || ev.kind === GroupKind.GROUP_DELETED)) {
        if (currentConv?.conversation_id === ev.conversation_id) {
          closeCurrentConv();
        }
        alert(ev.kind === GroupKind.GROUP_DELETED
          ? 'a group you were in was deleted'
          : ev.kind === GroupKind.MEMBER_LEFT
            ? 'you left the group'
            : 'you were removed from a group');
      }
      refreshConversations();
      break;
    }
  }
}

// ---------- new group ----------

$('new-group-btn').addEventListener('click', async () => {
  const name = prompt('group name:');
  if (!name?.trim()) return;
  const members = prompt('member usernames, comma separated (e.g. cass,rain,alice):');
  if (members == null) return;
  const member_names = members.split(',').map((s) => s.trim()).filter(Boolean);
  const res = await api.post('/api/conversations', { name: name.trim(), member_names });
  if (!res.ok) {
    alert((await res.json()).error);
    return;
  }
  await refreshConversations();
});

// ---------- logout ----------

$('logout-btn').addEventListener('click', async () => {
  await api.post('/api/auth/logout');
  location.href = '/';
});
