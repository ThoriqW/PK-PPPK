import client from './client';

export const fetchOpds            = () => client.get('/admin/reference/opds').then((r) => r.data);
export const fetchJabatanCategories = () => client.get('/admin/reference/jabatan-categories').then((r) => r.data);
export const fetchAppointmentYears = () => client.get('/admin/reference/appointment-years').then((r) => r.data);
export const fetchActiveTemplates  = () => client.get('/admin/reference/templates').then((r) => r.data);
export const fetchActiveNumberings = () => client.get('/admin/reference/numberings').then((r) => r.data);
export const fetchDashboard        = () => client.get('/admin/dashboard').then((r) => r.data);
export const fetchAuditLogs        = (params) => client.get('/admin/audit-logs', { params }).then((r) => r.data);
export const fetchAuditActions     = () => client.get('/admin/audit-logs-actions').then((r) => r.data.actions);
