<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div v-for="stat in stats" :key="stat.label" class="card p-4">
                <div class="text-xs text-slate-500 uppercase tracking-wider">{{ stat.label }}</div>
                <div class="text-2xl font-semibold mt-1 text-slate-800">{{ stat.value ?? '—' }}</div>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="font-semibold text-slate-800 mb-2">Selamat datang.</h2>
            <p class="text-sm text-slate-600">
                Gunakan menu di sebelah kiri untuk mengelola data pegawai, perjanjian kerja, template, dan riwayat aktivitas.
            </p>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { fetchDashboard } from '@/api/reference';

const data = ref({});
onMounted(async () => { data.value = await fetchDashboard(); });

const stats = computed(() => [
    { label: 'Total Pegawai',       value: data.value.employees_total },
    { label: 'Pegawai Aktif',       value: data.value.employees_active },
    { label: 'Perjanjian Aktif',    value: data.value.agreements_active },
    { label: 'Arsip',               value: data.value.agreements_archive },
    { label: 'Berakhir 90 Hari',    value: data.value.expiring_90d },
]);
</script>
