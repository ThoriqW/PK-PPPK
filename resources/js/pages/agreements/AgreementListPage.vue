<template>
    <div class="space-y-4">
        <div class="card p-4 grid md:grid-cols-5 gap-3">
            <input v-model="filters.search" class="input md:col-span-2" placeholder="Cari nomor / nama / NIP" />
            <select v-model="filters.opd_id" class="input">
                <option value="">Semua OPD</option>
                <option v-for="o in opds" :key="o.id" :value="o.id">{{ o.name }}</option>
            </select>
            <select v-model="filters.appointment_year" class="input">
                <option value="">Semua Tahun</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
            <select v-model="filters.status" class="input">
                <option value="">Semua Status</option>
                <option>AKTIF</option><option>ARSIP</option><option>DIBATALKAN</option><option>DRAFT</option>
            </select>
        </div>

        <div class="card overflow-hidden">
            <table class="table">
                <thead><tr><th>Nomor</th><th>Pegawai</th><th>OPD</th><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="a in items" :key="a.id" class="hover:bg-slate-50">
                        <td class="font-mono text-xs">{{ a.agreement_number }}</td>
                        <td>{{ a.employee?.full_name }} <span class="text-xs text-slate-500">({{ a.employee?.nip || '-' }})</span></td>
                        <td>{{ a.employee?.opd }}</td>
                        <td>{{ a.kind }}</td>
                        <td>{{ a.period_start }}</td>
                        <td>{{ a.period_end }}</td>
                        <td><span :class="`badge badge-${a.status}`">{{ a.status }}</span></td>
                        <td class="space-x-1">
                            <RouterLink :to="`/admin/agreements/${a.id}`" class="btn-secondary text-xs">Detail</RouterLink>
                            <a v-if="a.has_pdf" :href="downloadUrl(a.id)" target="_blank" class="btn-secondary text-xs">PDF</a>
                        </td>
                    </tr>
                    <tr v-if="!items.length"><td colspan="8" class="text-center text-slate-500 py-6">Tidak ada perjanjian.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { listAgreements, downloadUrl } from '@/api/agreements';
import { fetchOpds, fetchAppointmentYears } from '@/api/reference';

const filters = reactive({ search: '', opd_id: '', appointment_year: '', status: '' });
const items = ref([]);
const opds = ref([]);
const years = ref([]);

let t;
async function refresh() {
    const data = await listAgreements(filters);
    items.value = data.data ?? [];
}
onMounted(async () => {
    [opds.value, years.value] = await Promise.all([fetchOpds(), fetchAppointmentYears()]);
    refresh();
});
watch(filters, () => { clearTimeout(t); t = setTimeout(refresh, 250); }, { deep: true });
</script>
