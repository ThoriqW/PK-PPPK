<template>
    <div class="space-y-4">
        <div class="card p-4 grid md:grid-cols-3 gap-3">
            <select v-model="filters.opd_id" class="input">
                <option value="">Semua OPD</option>
                <option v-for="o in opds" :key="o.id" :value="o.id">{{ o.name }}</option>
            </select>
            <select v-model="filters.appointment_year" class="input">
                <option value="">Semua Tahun</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <div class="card overflow-hidden">
            <table class="table">
                <thead><tr><th>Nomor</th><th>Pegawai</th><th>OPD</th><th>Tahun</th><th>Periode</th><th>Diarsipkan</th></tr></thead>
                <tbody>
                    <tr v-for="a in items" :key="a.id" class="cursor-pointer hover:bg-slate-50" @click="$router.push(`/admin/agreements/${a.id}`)">
                        <td class="font-mono text-xs">{{ a.agreement_number }}</td>
                        <td>{{ a.employee?.full_name }}</td>
                        <td>{{ a.employee?.opd }}</td>
                        <td>{{ a.employee?.appointment_year }}</td>
                        <td>{{ a.period_start }} — {{ a.period_end }}</td>
                        <td>{{ formatDate(a.updated_at) }}</td>
                    </tr>
                    <tr v-if="!items.length"><td colspan="6" class="text-center py-6 text-slate-500">Belum ada arsip.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { listArchive } from '@/api/agreements';
import { fetchOpds, fetchAppointmentYears } from '@/api/reference';

const filters = reactive({ opd_id: '', appointment_year: '' });
const items = ref([]);
const opds = ref([]);
const years = ref([]);

function formatDate(s) { return s ? new Date(s).toLocaleString('id-ID') : '-'; }
async function refresh() { const d = await listArchive(filters); items.value = d.data ?? []; }
onMounted(async () => { [opds.value, years.value] = await Promise.all([fetchOpds(), fetchAppointmentYears()]); refresh(); });
watch(filters, refresh, { deep: true });
</script>
