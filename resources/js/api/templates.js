import client from './client';

export const listTemplates    = (params) => client.get('/admin/templates', { params }).then((r) => r.data);
export const getTemplate      = (id) => client.get(`/admin/templates/${id}`).then((r) => r.data.data);
export const createTemplate   = (body) => client.post('/admin/templates', body).then((r) => r.data.data);
export const updateTemplate   = (id, body) => client.put(`/admin/templates/${id}`, body).then((r) => r.data.data);
export const deleteTemplate   = (id) => client.delete(`/admin/templates/${id}`).then((r) => r.data);
export const placeholders     = () => client.get('/admin/templates/placeholders').then((r) => r.data.placeholders);
