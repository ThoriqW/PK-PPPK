<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold text-slate-800">Template Perjanjian</h2>
            <RouterLink to="/admin/templates/new" class="btn-primary">Buat Template</RouterLink>
        </div>
        <div class="card overflow-hidden">
            <table class="table">
                <thead><tr><th>Nama</th><th>Versi</th><th>Status</th><th>Diperbarui</th></tr></thead>
                <tbody>
                    <tr v-for="t in items" :key="t.id" class="cursor-pointer hover:bg-slate-50" @click="$router.push(`/admin/templates/${t.id}`)">
                        <td>{{ t.name }}</td>
                        <td>v{{ t.version }}</td>
                        <td><span :class="`badge ${t.is_active ? 'badge-AKTIF' : 'badge-DRAFT'}`">{{ t.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>{{ formatDate(t.updated_at) }}</td>
                    </tr>
                    <tr v-if="!items.length"><td colspan="4" class="text-center text-slate-500 py-6">Belum ada template.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { listTemplates } from '@/api/templates';

const items = ref([]);
function formatDate(s) { return s ? new Date(s).toLocaleString('id-ID') : '-'; }
onMounted(async () => { const d = await listTemplates(); items.value = d.data ?? []; });
</script>
