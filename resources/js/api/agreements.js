import client from './client';

export const listAgreements   = (params) => client.get('/admin/agreements', { params }).then((r) => r.data);
export const getAgreement     = (id) => client.get(`/admin/agreements/${id}`).then((r) => r.data.data);
export const createAgreement  = (body) => client.post('/admin/agreements', body).then((r) => r.data.data);
export const extendAgreement  = (id, body) => client.post(`/admin/agreements/${id}/extend`, body).then((r) => r.data.data);
export const cancelAgreement  = (id, reason) => client.post(`/admin/agreements/${id}/cancel`, { reason }).then((r) => r.data.data);
export const batchExtend      = (body) => client.post('/admin/agreements/batch-extend', body).then((r) => r.data);
export const downloadUrl      = (id) => `/api/admin/agreements/${id}/download`;
export const listArchive      = (params) => client.get('/admin/archive', { params }).then((r) => r.data);
