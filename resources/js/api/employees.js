import client from './client';

export const listEmployees   = (params) => client.get('/admin/employees', { params }).then((r) => r.data);
export const getEmployee     = (id)     => client.get(`/admin/employees/${id}`).then((r) => r.data.data);
export const createEmployee  = (body)   => client.post('/admin/employees', body).then((r) => r.data.data);
export const updateEmployee  = (id, body) => client.put(`/admin/employees/${id}`, body).then((r) => r.data.data);
export const deleteEmployee  = (id)     => client.delete(`/admin/employees/${id}`).then((r) => r.data);

export const listImportBatches = (params) => client.get('/admin/imports', { params }).then((r) => r.data);
export const getImportBatch    = (id) => client.get(`/admin/imports/${id}`).then((r) => r.data);
export const uploadImport      = (file) => {
    const fd = new FormData();
    fd.append('file', file);
    return client.post('/admin/imports', fd, { headers: { 'Content-Type': 'multipart/form-data' } }).then((r) => r.data);
};
