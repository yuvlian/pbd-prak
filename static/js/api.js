export const api = {
  async get(url) {
    return fetch(url);
  },
  async post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: body === undefined ? null : JSON.stringify(body),
    });
  },
  async put(url, body) {
    return fetch(url, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
  },
  async patch(url, body) {
    return fetch(url, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
  },
  async del(url) {
    return fetch(url, { method: 'DELETE' });
  },
  async json(res) {
    if (res.status === 204) return null;
    return res.json();
  },
};
