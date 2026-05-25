<template>
    <div class="min-h-screen grid place-items-center bg-gradient-to-br from-brand-700 to-brand-900 p-4">
        <div class="card w-full max-w-md p-8">
            <div class="text-center mb-6">
                <div class="text-xs tracking-widest text-brand uppercase">BKPSDMD Kota Palu</div>
                <h1 class="text-xl font-semibold text-slate-800 mt-1">Sistem Perjanjian Kerja</h1>
            </div>
            <form @submit.prevent="onSubmit" class="space-y-4">
                <div>
                    <label class="label">Email</label>
                    <input v-model="form.email" type="email" autocomplete="email" required class="input" />
                </div>
                <div>
                    <label class="label">Kata Sandi</label>
                    <input v-model="form.password" type="password" autocomplete="current-password" required class="input" />
                </div>
                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
                <button class="btn-primary w-full" :disabled="loading">{{ loading ? 'Memproses...' : 'Masuk' }}</button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const form = reactive({ email: '', password: '' });
const loading = ref(false);
const error = ref('');

async function onSubmit() {
    loading.value = true;
    error.value = '';
    try {
        await auth.login(form.email, form.password);
        const target = route.query.redirect || '/admin';
        router.push(target);
    } catch (e) {
        error.value = e.response?.data?.message || e.response?.data?.error?.message || 'Gagal masuk.';
    } finally {
        loading.value = false;
    }
}
</script>
