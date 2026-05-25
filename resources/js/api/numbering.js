import client from './client';

export const listNumbering    = () => client.get('/admin/numbering').then((r) => r.data.data ?? r.data);
export const getNumbering     = (id) => client.get(`/admin/numbering/${id}`).then((r) => r.data.data);
export const createNumbering  = (body) => client.post('/admin/numbering', body).then((r) => r.data.data);
export const updateNumbering  = (id, body) => client.put(`/admin/numbering/${id}`, body).then((r) => r.data.data);
export const previewNumbering = (body) => client.post('/admin/numbering/preview', body).then((r) => r.data.rendered);
