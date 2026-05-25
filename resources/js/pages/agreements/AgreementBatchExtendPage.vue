<template>
    <div class="space-y-4">
        <div class="card p-5 grid md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="label">Tahun Pengangkatan</label>
                <select v-model.number="form.appointment_year" class="input">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <div>
                <label class="label">Template</label>
                <select v-model.number="form.template_id" class="input">
                    <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }} (v{{ t.version }})</option>
                </select>
            </div>
            <div>
                <label class="label">Penomoran</label>
                <select v-model.number="form.numbering_config_id" class="input">
                    <option v-for="n in numberings" :key="n.id" :value="n.id">{{ n.name }}</option>
                </select>
            </div>
            <div>
                <label class="label">Durasi (tahun)</label>
                <input type="number" v-model.number="form.years" min="1" max="10" class="input" />
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button class="btn-primary" :disabled="saving" @click="onSubmit">{{ saving ? 'Memproses...' : 'Jalankan Perpanjangan Massal' }}</button>
            </div>
        </div>

        <div v-if="result" class="card p-5">
            <h3 class="font-semibold mb-2">Hasil</h3>
            <p class="text-sm text-slate-600 mb-3">Total: {{ result.total }} • Berhasil: {{ result.extended }} • Gagal: {{ result.failures }}</p>
            <table class="table">
                <thead><tr><th>Pegawai</th><th>Perjanjian Lama</th><th>Status</th><th>Pesan</th></tr></thead>
                <tbody>
                    <tr v-for="(o, i) in result.outcomes" :key="i">
                        <td>{{ o.employee_id }}</td>
                        <td>{{ o.agreement_id }}</td>
                        <td><span :class="`badge ${o.status === 'EXTENDED' ? 'badge-AKTIF' : 'badge-DIBATALKAN'}`">{{ o.status }}</span></td>
                        <td>{{ o.message || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { fetchActiveTemplates, fetchActiveNumberings, fetchAppointmentYears } from '@/api/reference';
import { batchExtend } from '@/api/agreements';

const form = reactive({ appointment_year: '', template_id: '', numbering_config_id: '', years: 5 });
const templates = ref([]);
const numberings = ref([]);
const years = ref([]);
const saving = ref(false);
const result = ref(null);

onMounted(async () => {
    [templates.value, numberings.value, years.value] = await Promise.all([fetchActiveTemplates(), fetchActiveNumberings(), fetchAppointmentYears()]);
    if (templates.value[0])  form.template_id = templates.value[0].id;
    if (numberings.value[0]) form.numbering_config_id = numberings.value[0].id;
    if (years.value[0]) form.appointment_year = years.value[0];
});

async function onSubmit() {
    saving.value = true;
    try {
        result.value = await batchExtend(form);
    } finally {
        saving.value = false;
    }
}
</script>
