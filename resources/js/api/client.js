import axios from 'axios';

const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

client.interceptors.response.use(
    (r) => r,
    (err) => {
        if (err.response?.status === 419) {
            // CSRF token expired; force a fresh login.
            window.location.href = '/admin/login';
        }
        return Promise.reject(err);
    },
);

export async function ensureCsrfCookie() {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

export default client;
