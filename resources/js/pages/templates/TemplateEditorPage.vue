<template>
    <div class="space-y-4">
        <div class="card p-5 grid md:grid-cols-2 gap-4">
            <div>
                <label class="label">Nama Template</label>
                <input v-model="form.name" class="input" />
            </div>
            <div>
                <label class="label">Status</label>
                <select v-model="form.is_active" class="input">
                    <option :value="true">Aktif</option>
                    <option :value="false">Nonaktif</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="label">Deskripsi</label>
                <input v-model="form.description" class="input" />
            </div>
        </div>

        <div class="card p-5">
            <label class="label">Isi Template (HTML)</label>
            <HtmlEditor v-model="form.body_html" :placeholders="placeholders" />
            <p class="text-xs text-slate-500 mt-2">
                Gunakan placeholder seperti <code>{{ ex('nama') }}</code>, <code>{{ ex('nomor_perjanjian') }}</code>, dst. Sisipkan QR Code dengan <code>{{ ex('qr_code') }}</code>.
            </p>
        </div>

        <div class="flex items-center justify-between">
            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            <div class="flex gap-2">
                <button class="btn-secondary" @click="$router.push('/admin/templates')">Batal</button>
                <button class="btn-primary" :disabled="saving" @click="onSave">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getTemplate, createTemplate, updateTemplate, placeholders as fetchPlaceholders } from '@/api/templates';
import HtmlEditor from '@/components/HtmlEditor.vue';

const route = useRoute();
const router = useRouter();

const form = reactive({ name: '', description: '', body_html: '<p>...</p>', is_active: true });
const placeholders = ref([]);
const saving = ref(false);
const error = ref('');

// Build a literal "{{key}}" string in JS so Vue's template compiler does not
// see nested mustaches in the template source.
function ex(key) {
    return '{{' + key + '}}';
}

onMounted(async () => {
    placeholders.value = await fetchPlaceholders();
    if (route.params.id) {
        const t = await getTemplate(route.params.id);
        Object.assign(form, t);
    }
});

async function onSave() {
    saving.value = true;
    error.value = '';
    try {
        if (route.params.id) {
            await updateTemplate(route.params.id, form);
        } else {
            const created = await createTemplate(form);
            router.replace(`/admin/templates/${created.id}`);
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'Gagal menyimpan template.';
    } finally {
        saving.value = false;
    }
}
</script>
