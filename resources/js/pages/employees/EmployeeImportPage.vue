<template>
    <div class="space-y-4">
        <div class="card p-5">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div>
                    <h2 class="font-semibold text-slate-800">Impor Pegawai dari Excel</h2>
                    <p class="text-sm text-slate-600 mt-1">
                        Format kolom (baris pertama sebagai header):
                        <code class="text-xs bg-slate-100 px-1 rounded">nip, nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, pendidikan, jabatan, kategori_jabatan_kode, golongan, opd_kode, unit_kerja, tahun_pengangkatan, telepon, email</code>.
                        Baris dengan NIP/NIK yang sudah terdaftar akan ditolak sebagai duplikat.
                    </p>
                </div>
                <a href="/api/admin/imports/template" class="btn-secondary text-xs whitespace-nowrap">Unduh Template Excel</a>
            </div>
            <div class="flex items-center gap-3">
                <input type="file" accept=".xlsx,.xls" @change="onFile" class="text-sm" />
                <button class="btn-primary" :disabled="!file || uploading" @click="onUpload">{{ uploading ? 'Memproses...' : 'Unggah' }}</button>
            </div>
            <p v-if="error" class="mt-2 text-sm text-red-600 whitespace-pre-wrap">{{ error }}</p>
            <div v-if="lastBatch" class="mt-4 text-sm text-slate-700 border rounded p-3 bg-slate-50">
                <div>Status: <strong>{{ lastBatch.status }}</strong></div>
                <div>Total baris: {{ lastBatch.total_rows }}, Berhasil: {{ lastBatch.inserted_rows }}, Duplikat: {{ lastBatch.skipped_rows }}, Gagal: {{ lastBatch.failed_rows }}</div>
                <div v-if="lastBatch.error_summary" class="mt-2 text-red-700 break-words">{{ lastBatch.error_summary }}</div>
                <button v-if="lastBatch.id" class="btn-secondary text-xs mt-2" @click="loadDetails(lastBatch.id)">Lihat Detail Per Baris</button>
            </div>

            <div v-if="rowDetails.length" class="mt-4 border rounded overflow-hidden">
                <div class="px-3 py-2 bg-slate-50 font-semibold text-slate-700 text-sm">Detail Per Baris</div>
                <table class="table">
                    <thead><tr><th>Baris</th><th>Hasil</th><th>Pesan</th></tr></thead>
                    <tbody>
                        <tr v-for="r in rowDetails" :key="r.id">
                            <td>{{ r.row_number }}</td>
                            <td>
                                <span :class="`badge ${r.outcome === 'INSERTED' ? 'badge-AKTIF' : r.outcome === 'DUPLICATE' ? 'badge-ARSIP' : 'badge-DIBATALKAN'}`">{{ r.outcome }}</span>
                            </td>
                            <td>{{ r.error_message || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b bg-slate-50 font-semibold text-slate-700">Riwayat Impor</div>
            <table class="table">
                <thead><tr><th>Tanggal</th><th>Nama File</th><th>Status</th><th>Total</th><th>Disimpan</th><th>Duplikat</th><th>Gagal</th></tr></thead>
                <tbody>
                    <tr v-for="b in batches" :key="b.id" class="hover:bg-slate-50 cursor-pointer" @click="loadDetails(b.id, b)">
                        <td>{{ formatDate(b.created_at) }}</td>
                        <td>{{ b.filename }}</td>
                        <td>{{ b.status }}</td>
                        <td>{{ b.total_rows }}</td>
                        <td>{{ b.inserted_rows }}</td>
                        <td>{{ b.skipped_rows }}</td>
                        <td>{{ b.failed_rows }}</td>
                    </tr>
                    <tr v-if="!batches.length"><td colspan="7" class="text-center text-slate-500 py-6">Belum ada impor.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { listImportBatches, uploadImport, getImportBatch } from '@/api/employees';

const file = ref(null);
const uploading = ref(false);
const error = ref('');
const lastBatch = ref(null);
const rowDetails = ref([]);
const batches = ref([]);

function onFile(e) {
    file.value = e.target.files?.[0] || null;
    error.value = '';
}

function extractError(e) {
    const data = e.response?.data;
    if (!data) return 'Tidak dapat menghubungi server.';
    if (data.error?.message) return `[${data.error.code || 'ERROR'}] ${data.error.message}`;
    if (data.message)        return data.message;
    if (data.errors)         return Object.values(data.errors).flat().join('\n');
    return 'Gagal mengunggah.';
}

async function onUpload() {
    if (!file.value) return;
    uploading.value = true;
    error.value = '';
    rowDetails.value = [];
    try {
        const res = await uploadImport(file.value);
        lastBatch.value = res.batch;
        if (lastBatch.value?.id) await loadDetails(lastBatch.value.id);
        await refresh();
    } catch (e) {
        error.value = extractError(e);
    } finally {
        uploading.value = false;
    }
}

async function loadDetails(id, asLast = null) {
    try {
        const res = await getImportBatch(id);
        rowDetails.value = res.rows || [];
        if (asLast) lastBatch.value = res.batch;
    } catch {
        rowDetails.value = [];
    }
}

async function refresh() {
    const res = await listImportBatches();
    batches.value = res.data ?? [];
}
function formatDate(s) { return s ? new Date(s).toLocaleString('id-ID') : '-'; }

onMounted(refresh);
</script>
