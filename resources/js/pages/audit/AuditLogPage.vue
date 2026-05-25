<template>
    <div class="space-y-4">
        <div class="card p-4 grid md:grid-cols-3 gap-3">
            <select v-model="filters.action" class="input">
                <option value="">Semua Aksi</option>
                <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
            </select>
            <input type="date" v-model="filters.from" class="input" />
            <input type="date" v-model="filters.to" class="input" />
        </div>
        <div class="card overflow-hidden">
            <table class="table">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Subjek</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr v-for="r in items" :key="r.id">
                        <td class="text-xs">{{ formatDate(r.created_at) }}</td>
                        <td>{{ r.user?.name || '—' }}</td>
                        <td><span class="badge badge-DRAFT">{{ r.action }}</span></td>
                        <td class="text-xs font-mono">{{ r.subject_type ? r.subject_type.split('\\').pop() + '#' + r.subject_id : '—' }}</td>
                        <td>{{ r.description }}</td>
                    </tr>
                    <tr v-if="!items.length"><td colspan="5" class="text-center text-slate-500 py-6">Tidak ada log.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { fetchAuditLogs, fetchAuditActions } from '@/api/reference';

const filters = reactive({ action: '', from: '', to: '' });
const items = ref([]);
const actions = ref([]);

function formatDate(s) { return s ? new Date(s).toLocaleString('id-ID') : '-'; }
async function refresh() { const d = await fetchAuditLogs(filters); items.value = d.data ?? d ?? []; }
onMounted(async () => { actions.value = await fetchAuditActions(); refresh(); });
watch(filters, refresh, { deep: true });
</script>
