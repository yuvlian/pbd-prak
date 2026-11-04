export class ChatSocket {
  constructor(conversationId, onEvent) {
    this.conversationId = conversationId;
    this.onEvent = onEvent;
    const proto = location.protocol === 'https:' ? 'wss' : 'ws';
    const qs = conversationId == null ? '' : `?conversation_id=${conversationId}`;
    this.ws = new WebSocket(`${proto}://${location.host}/api/ws${qs}`);
    this.ws.onmessage = (e) => {
      try { this.onEvent(JSON.parse(e.data)); } catch { /* ignore bad frames */ }
    };
  }

  send(obj) {
    if (this.ws.readyState === WebSocket.OPEN) this.ws.send(JSON.stringify(obj));
  }

  close() {
    this.onEvent = () => { };
    this.ws.close();
  }
}
