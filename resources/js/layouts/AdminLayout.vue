<template>
    <div class="min-h-screen flex bg-slate-50">
        <aside class="w-64 bg-brand-700 text-white flex-shrink-0">
            <div class="px-5 py-6 border-b border-brand-900/30">
                <div class="text-xs uppercase tracking-wider opacity-80">BKPSDMD Kota Palu</div>
                <div class="text-base font-semibold">Perjanjian Kerja</div>
            </div>
            <nav class="py-3 text-sm">
                <RouterLink v-for="item in nav" :key="item.to" :to="item.to" class="flex items-center px-5 py-2.5 hover:bg-brand-900/40 transition" active-class="bg-brand-900/60">
                    <span>{{ item.label }}</span>
                </RouterLink>
            </nav>
        </aside>
        <main class="flex-1 flex flex-col min-w-0">
            <header class="h-14 px-6 flex items-center justify-between border-b bg-white">
                <h1 class="text-base font-semibold text-slate-700">{{ title }}</h1>
                <div class="flex items-center gap-3 text-sm text-slate-600">
                    <span>{{ auth.user?.name }}</span>
                    <button class="btn-secondary" @click="onLogout">Keluar</button>
                </div>
            </header>
            <section class="p-6 overflow-y-auto flex-1">
                <router-view />
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const nav = [
    { to: '/admin',                  label: 'Dasbor' },
    { to: '/admin/employees',        label: 'Pegawai' },
    { to: '/admin/employees/import', label: 'Impor Excel' },
    { to: '/admin/agreements',       label: 'Perjanjian' },
    { to: '/admin/agreements/new',   label: 'Buat Perjanjian' },
    { to: '/admin/agreements/batch-extend', label: 'Perpanjangan Massal' },
    { to: '/admin/archive',          label: 'Arsip' },
    { to: '/admin/templates',        label: 'Template' },
    { to: '/admin/numbering',        label: 'Penomoran' },
    { to: '/admin/audit',            label: 'Riwayat (Audit)' },
];

const title = computed(() => nav.find((n) => route.path === n.to)?.label ?? 'Sistem Perjanjian Kerja');

async function onLogout() {
    await auth.logout();
    router.push('/admin/login');
}
</script>
