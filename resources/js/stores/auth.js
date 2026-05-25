import { defineStore } from 'pinia';
import * as authApi from '@/api/auth';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loaded: false,
    }),
    getters: {
        isAuthenticated: (s) => !!s.user,
    },
    actions: {
        async loadMe() {
            try {
                this.user = await authApi.me();
            } catch {
                this.user = null;
            } finally {
                this.loaded = true;
            }
        },
        async login(email, password) {
            this.user = await authApi.login(email, password);
            return this.user;
        },
        async logout() {
            await authApi.logout();
            this.user = null;
        },
    },
});
