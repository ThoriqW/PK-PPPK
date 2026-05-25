import client, { ensureCsrfCookie } from './client';

export async function login(email, password) {
    await ensureCsrfCookie();
    const { data } = await client.post('/admin/login', { email, password });
    return data.user;
}

export async function logout() {
    await client.post('/admin/logout');
}

export async function me() {
    const { data } = await client.get('/admin/me');
    return data.user;
}
