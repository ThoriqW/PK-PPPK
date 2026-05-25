<template>
    <div class="space-y-4">
        <div class="card p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
            <input v-model="filters.search" placeholder="Cari nama / NIP / NIK" class="input md:col-span-2" />
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
                <option value="AKTIF">AKTIF</option>
                <option value="PENSIUN">PENSIUN</option>
                <option value="NONAKTIF">NONAKTIF</option>
            </select>
        </div>

        <div class="card overflow-hidden">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Jabatan</th>
                        <th>OPD</th>
                        <th>Tahun</th>
                        <th>Pensiun</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in items" :key="row.id" class="hover:bg-slate-50 cursor-pointer" @click="$router.push(`/admin/employees/${row.id}`)">
                        <td>{{ row.full_name }}</td>
                        <td class="font-mono text-xs">{{ row.nip || '-' }}</td>
                        <td>{{ row.jabatan }}</td>
                        <td>{{ row.opd?.name }}</td>
                        <td>{{ row.appointment_year }}</td>
                        <td>{{ row.retirement_date || '-' }}</td>
                        <td><span :class="`badge badge-${row.status === 'AKTIF' ? 'AKTIF' : row.status === 'PENSIUN' ? 'ARSIP' : 'DIBATALKAN'}`">{{ row.status }}</span></td>
                    </tr>
                    <tr v-if="!items.length">
                        <td colspan="7" class="text-center text-slate-500 py-6">Tidak ada data.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { listEmployees } from '@/api/employees';
import { fetchOpds, fetchAppointmentYears } from '@/api/reference';

const filters = reactive({ search: '', opd_id: '', appointment_year: '', status: '' });
const items = ref([]);
const opds = ref([]);
const years = ref([]);

let t;
async function refresh() {
    const data = await listEmployees(filters);
    items.value = data.data ?? [];
}

onMounted(async () => {
    [opds.value, years.value] = await Promise.all([fetchOpds(), fetchAppointmentYears()]);
    refresh();
});
watch(filters, () => { clearTimeout(t); t = setTimeout(refresh, 250); }, { deep: true });
</script>
