<template>
    <div v-if="a" class="space-y-4">
        <div class="card p-5 flex items-start justify-between">
            <div>
                <div class="text-xs uppercase text-slate-500">Nomor Perjanjian</div>
                <div class="text-lg font-semibold font-mono">{{ a.agreement_number }}</div>
                <div class="mt-1 text-sm text-slate-600">{{ a.kind }} • {{ a.period_start }} s/d {{ a.period_end }}</div>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span :class="`badge badge-${a.status}`">{{ a.status }}</span>
                <a v-if="a.has_pdf" :href="downloadUrl(a.id)" target="_blank" class="btn-primary text-xs">Unduh PDF</a>
            </div>
        </div>

        <div class="card p-5 grid md:grid-cols-3 gap-4 text-sm">
            <div><div class="text-slate-500">Pegawai</div><div class="font-semibold">{{ a.employee?.full_name }}</div></div>
            <div><div class="text-slate-500">NIP</div><div>{{ a.employee?.nip || '-' }}</div></div>
            <div><div class="text-slate-500">OPD</div><div>{{ a.employee?.opd }}</div></div>
            <div><div class="text-slate-500">Jabatan</div><div>{{ a.employee?.jabatan }}</div></div>
            <div><div class="text-slate-500">Tahun Pengangkatan</div><div>{{ a.employee?.appointment_year }}</div></div>
            <div><div class="text-slate-500">URL Verifikasi Publik</div><div class="break-all"><a :href="a.qr_url" target="_blank" class="text-brand">{{ a.qr_url }}</a></div></div>
        </div>

        <div class="card p-5" v-if="a.status === 'AKTIF'">
            <h3 class="font-semibold text-slate-800 mb-3">Perpanjang Perjanjian</h3>
            <div class="grid md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="label">Template</label>
                    <select v-model.number="ext.template_id" class="input">
                        <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }} (v{{ t.version }})</option>
                    </select>
                </div>
                <div>
                    <label class="label">Penomoran</label>
                    <select v-model.number="ext.numbering_config_id" class="input">
                        <option v-for="n in numberings" :key="n.id" :value="n.id">{{ n.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="label">Durasi (tahun)</label>
                    <input type="number" v-model.number="ext.years" min="1" max="10" class="input" />
                </div>
                <div>
                    <button class="btn-primary w-full" :disabled="saving" @click="onExtend">{{ saving ? 'Memproses...' : 'Perpanjang' }}</button>
                </div>
            </div>
            <p v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</p>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getAgreement, extendAgreement, downloadUrl } from '@/api/agreements';
import { fetchActiveTemplates, fetchActiveNumberings } from '@/api/reference';

const route = useRoute();
const router = useRouter();
const a = ref(null);
const templates = ref([]);
const numberings = ref([]);
const saving = ref(false);
const error = ref('');
const ext = reactive({ template_id: '', numbering_config_id: '', years: 5 });

onMounted(async () => {
    [a.value, templates.value, numberings.value] = await Promise.all([
        getAgreement(route.params.id),
        fetchActiveTemplates(),
        fetchActiveNumberings(),
    ]);
    if (templates.value[0])  ext.template_id = templates.value[0].id;
    if (numberings.value[0]) ext.numbering_config_id = numberings.value[0].id;
});

async function onExtend() {
    saving.value = true; error.value = '';
    try {
        const next = await extendAgreement(a.value.id, ext);
        router.push(`/admin/agreements/${next.id}`);
    } catch (e) {
        error.value = e.response?.data?.error?.message || 'Gagal memperpanjang.';
    } finally {
        saving.value = false;
    }
}
</script>
