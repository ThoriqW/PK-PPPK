<template>
    <div v-if="employee" class="space-y-4">
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold">{{ employee.full_name }}</h2>
                    <div class="text-sm text-slate-600">NIP: {{ employee.nip || '-' }} | NIK: {{ employee.nik || '-' }}</div>
                </div>
                <span :class="`badge badge-${employee.status === 'AKTIF' ? 'AKTIF' : 'ARSIP'}`">{{ employee.status }}</span>
            </div>
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-y-3 gap-x-6 mt-4 text-sm">
                <div><dt class="text-slate-500">Jabatan</dt><dd>{{ employee.jabatan }}</dd></div>
                <div><dt class="text-slate-500">Kategori</dt><dd>{{ employee.jabatan_category?.name }}</dd></div>
                <div><dt class="text-slate-500">Golongan</dt><dd>{{ employee.golongan || '-' }}</dd></div>
                <div><dt class="text-slate-500">OPD</dt><dd>{{ employee.opd?.name }}</dd></div>
                <div><dt class="text-slate-500">Unit Kerja</dt><dd>{{ employee.unit_kerja || '-' }}</dd></div>
                <div><dt class="text-slate-500">Tahun Pengangkatan</dt><dd>{{ employee.appointment_year }}</dd></div>
                <div><dt class="text-slate-500">Tempat / Tanggal Lahir</dt><dd>{{ employee.place_of_birth }} / {{ employee.date_of_birth }}</dd></div>
                <div><dt class="text-slate-500">Tanggal Pensiun</dt><dd>{{ employee.retirement_date || '-' }}</dd></div>
                <div><dt class="text-slate-500">Pendidikan</dt><dd>{{ employee.education || '-' }}</dd></div>
            </dl>
        </div>

        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b bg-slate-50 flex items-center justify-between">
                <h3 class="font-semibold text-slate-700">Riwayat Perjanjian</h3>
                <RouterLink :to="`/admin/agreements/new?employee_id=${employee.id}`" class="btn-primary text-xs">Buat Perjanjian</RouterLink>
            </div>
            <table class="table">
                <thead>
                    <tr><th>Nomor</th><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <tr v-for="a in agreements" :key="a.id" @click="$router.push(`/admin/agreements/${a.id}`)" class="cursor-pointer hover:bg-slate-50">
                        <td class="font-mono text-xs">{{ a.agreement_number }}</td>
                        <td>{{ a.kind }}</td>
                        <td>{{ a.period_start }}</td>
                        <td>{{ a.period_end }}</td>
                        <td><span :class="`badge badge-${a.status}`">{{ a.status }}</span></td>
                    </tr>
                    <tr v-if="!agreements.length"><td colspan="5" class="text-center text-slate-500 py-6">Belum ada perjanjian.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { getEmployee } from '@/api/employees';
import { listAgreements } from '@/api/agreements';

const route = useRoute();
const employee = ref(null);
const agreements = ref([]);

onMounted(async () => {
    const id = route.params.id;
    employee.value = await getEmployee(id);
    const list = await listAgreements({ search: employee.value.nip || employee.value.full_name });
    agreements.value = (list.data ?? []).filter((a) => a.employee?.id === Number(id));
});
</script>
