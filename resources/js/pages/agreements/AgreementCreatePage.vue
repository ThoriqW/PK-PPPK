<template>
    <div class="space-y-4">
        <div class="card p-5 grid md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="label">Pegawai</label>
                <select v-model.number="form.employee_id" class="input">
                    <option value="">— pilih pegawai —</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name }} ({{ e.nip || '-' }}) — {{ e.opd?.name }}</option>
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
                    <option v-for="n in numberings" :key="n.id" :value="n.id">{{ n.name }} — {{ n.format }}</option>
                </select>
            </div>
            <div>
                <label class="label">Tanggal Mulai</label>
                <input type="date" v-model="form.period_start" class="input" />
            </div>
            <div>
                <label class="label">Tanggal Selesai</label>
                <input type="date" v-model="form.period_end" class="input" />
            </div>
            <div>
                <label class="label">Tanggal Tanda Tangan</label>
                <input type="date" v-model="form.signed_at" class="input" />
            </div>
            <div>
                <label class="label">Nama Penanda Tangan</label>
                <input v-model="form.signed_by_name" class="input" />
            </div>
            <div class="md:col-span-2">
                <label class="label">Jabatan Penanda Tangan</label>
                <input v-model="form.signed_by_position" class="input" />
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <button class="btn-secondary" @click="$router.back()">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="onSubmit">{{ saving ? 'Memproses...' : 'Buat Perjanjian' }}</button>
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { listEmployees } from '@/api/employees';
import { fetchActiveTemplates, fetchActiveNumberings } from '@/api/reference';
import { createAgreement } from '@/api/agreements';

const route = useRoute();
const router = useRouter();
const form = reactive({
    employee_id: route.query.employee_id ? Number(route.query.employee_id) : '',
    template_id: '', numbering_config_id: '',
    period_start: '', period_end: '',
    signed_at: '', signed_by_name: '', signed_by_position: '',
});
const employees = ref([]);
const templates = ref([]);
const numberings = ref([]);
const saving = ref(false);
const error = ref('');

onMounted(async () => {
    const [emp, tpl, num] = await Promise.all([listEmployees({ per_page: 200, status: 'AKTIF' }), fetchActiveTemplates(), fetchActiveNumberings()]);
    employees.value = emp.data ?? [];
    templates.value = (tpl ?? []).filter((t) => t.is_active);
    numberings.value = (num ?? []).filter((n) => n.is_active);
    if (templates.value[0])  form.template_id = templates.value[0].id;
    if (numberings.value[0]) form.numbering_config_id = numberings.value[0].id;
});

async function onSubmit() {
    saving.value = true; error.value = '';
    try {
        const a = await createAgreement(form);
        router.push(`/admin/agreements/${a.id}`);
    } catch (e) {
        error.value = e.response?.data?.error?.message || e.response?.data?.message || 'Gagal membuat perjanjian.';
    } finally {
        saving.value = false;
    }
}
</script>
