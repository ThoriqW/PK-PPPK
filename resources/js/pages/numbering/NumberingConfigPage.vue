<template>
    <div class="space-y-4">
        <div class="card p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">Konfigurasi Penomoran</h2>
            <div v-for="cfg in items" :key="cfg.id" class="border rounded-md p-4 grid md:grid-cols-6 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="label">Nama</label>
                    <input v-model="cfg.name" class="input" />
                </div>
                <div class="md:col-span-2">
                    <label class="label">Format</label>
                    <input v-model="cfg.format" class="input font-mono" />
                </div>
                <div>
                    <label class="label">No. Awal</label>
                    <input v-model.number="cfg.current_number" type="number" min="0" class="input" />
                </div>
                <div>
                    <label class="label">Padding</label>
                    <input v-model.number="cfg.padding" type="number" min="1" max="6" class="input" />
                </div>
                <div>
                    <label class="label">Reset</label>
                    <select v-model="cfg.reset_policy" class="input">
                        <option>NEVER</option><option>YEARLY</option><option>MONTHLY</option>
                    </select>
                </div>
                <div>
                    <label class="label">Aktif</label>
                    <select v-model="cfg.is_active" class="input">
                        <option :value="true">Ya</option><option :value="false">Tidak</option>
                    </select>
                </div>
                <div class="md:col-span-3 text-xs text-slate-500">
                    Pratinjau: <span class="font-mono">{{ previews[cfg.id] || '—' }}</span>
                </div>
                <div class="md:col-span-3 flex justify-end gap-2">
                    <button class="btn-secondary" @click="onPreview(cfg)">Pratinjau</button>
                    <button class="btn-primary" @click="onSave(cfg)">Simpan</button>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <h3 class="font-semibold text-slate-700 mb-3">Buat Konfigurasi Baru</h3>
            <div class="grid md:grid-cols-6 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="label">Nama</label>
                    <input v-model="draft.name" class="input" />
                </div>
                <div class="md:col-span-2">
                    <label class="label">Format</label>
                    <input v-model="draft.format" class="input font-mono" />
                </div>
                <div>
                    <label class="label">No. Awal</label>
                    <input v-model.number="draft.current_number" type="number" class="input" />
                </div>
                <div>
                    <label class="label">Padding</label>
                    <input v-model.number="draft.padding" type="number" min="1" max="6" class="input" />
                </div>
                <div>
                    <label class="label">Reset</label>
                    <select v-model="draft.reset_policy" class="input">
                        <option>NEVER</option><option>YEARLY</option><option>MONTHLY</option>
                    </select>
                </div>
                <div>
                    <label class="label">Aktif</label>
                    <select v-model="draft.is_active" class="input">
                        <option :value="true">Ya</option><option :value="false">Tidak</option>
                    </select>
                </div>
                <div class="md:col-span-6 flex justify-end">
                    <button class="btn-primary" @click="onCreate">Tambah</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { listNumbering, createNumbering, updateNumbering, previewNumbering } from '@/api/numbering';

const items = ref([]);
const previews = ref({});
const draft = reactive({ name: '', format: '{seq}/PPPK/BKPSDMD/{roman_month}/{year}', current_number: 0, padding: 3, reset_policy: 'YEARLY', is_active: false });

async function refresh() { items.value = await listNumbering(); }
async function onPreview(cfg) {
    previews.value[cfg.id] = await previewNumbering({ format: cfg.format, seq: (cfg.current_number || 0) + 1, padding: cfg.padding, opd: 'BKPSDMD' });
}
async function onSave(cfg) { await updateNumbering(cfg.id, cfg); await refresh(); }
async function onCreate() { await createNumbering(draft); await refresh(); }

onMounted(refresh);
</script>
