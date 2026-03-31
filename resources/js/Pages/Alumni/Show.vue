<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    alumni: {
        type: Object,
        required: true,
    },
});

const photoUrl = computed(() => {
    const directPhoto = props.alumni.photo_url;

    if (typeof directPhoto === 'string' && directPhoto.trim() !== '') {
        return directPhoto;
    }

    const payload = props.alumni.integration_payload;

    if (!payload || typeof payload !== 'object') {
        return null;
    }

    const payloadPhoto = payload.photo_path || payload.photo_3x4_path || null;

    return typeof payloadPhoto === 'string' && payloadPhoto.trim() !== '' ? payloadPhoto : null;
});

const hasPayload = computed(() => props.alumni.integration_payload && typeof props.alumni.integration_payload === 'object');

const ignoredPayloadKeys = new Set([
    'nim',
    'full_name',
    'study_program_name',
    'faculty_name',
    'personal_email',
    'campus_email',
    'phone_number',
    'full_address',
    'intake_year',
    'photo_path',
    'photo_3x4_path',
]);

const payloadLabelMap = {
    source_graduate_id: 'ID Sumber Lulusan',
    source_student_id: 'ID Sumber Mahasiswa',
    student_user_id: 'ID User Mahasiswa',
    faculty_id: 'ID Fakultas',
    faculty_name: 'Fakultas',
    study_program_id: 'ID Program Studi',
    study_program_name: 'Program Studi',
    campus_email: 'Email Kampus',
    ipk: 'IPK',
    predicate: 'Predikat Kelulusan',
    thesis_title: 'Judul Skripsi/Tesis',
    supervisor_1: 'Pembimbing 1',
    supervisor_2: 'Pembimbing 2',
    intake_year: 'Tahun Angkatan',
    birth_place: 'Tempat Lahir',
    birth_date: 'Tanggal Lahir',
    religion: 'Agama',
    gender: 'Jenis Kelamin',
    phone_number: 'Nomor Telepon',
    personal_email: 'Email Pribadi',
    ktp_number: 'Nomor KTP',
    full_address: 'Alamat Lengkap',
    gown_size: 'Ukuran Toga',
    is_employed: 'Status Bekerja',
    father_name: 'Nama Ayah',
    mother_name: 'Nama Ibu',
    parent_phone: 'Nomor Telepon Orang Tua',
    photo_3x4_path: 'Foto 3x4',
    ktp_photo_path: 'Foto KTP',
    diploma_document_path: 'Dokumen Ijazah',
    payment_proof_path: 'Bukti Pembayaran',
    extra_document_link: 'Link Dokumen Tambahan',
    profile_status: 'Status Profil',
    submitted_at: 'Tanggal Submit',
    verified_at: 'Tanggal Verifikasi',
    verified_by_user_id: 'Diverifikasi Oleh (ID User)',
    rejection_note: 'Catatan Penolakan',
    photo_path: 'Path Foto',
    call_order: 'Urutan Panggil',
    source_session_id: 'ID Sesi Sumber',
    source_session_name: 'Nama Sesi Sumber',
    source_ceremony_name: 'Nama Acara Wisuda',
    attendance_status: 'Status Kehadiran',
    archived_at: 'Tanggal Arsip',
    archived_by_user_id: 'Diarsipkan Oleh (ID User)',
    created_at: 'Dibuat Pada',
    updated_at: 'Diperbarui Pada',
    'source_session.id': 'ID Sesi',
    'source_session.session_name': 'Nama Sesi',
    'source_session.ceremony_id': 'ID Acara Wisuda',
    'source_session.ceremony.id': 'ID Acara',
    'source_session.ceremony.name': 'Nama Acara',
    'faculty.id': 'ID Fakultas',
    'faculty.name': 'Nama Fakultas',
    'study_program.id': 'ID Program Studi',
    'study_program.name': 'Nama Program Studi',
    'archived_by.id': 'ID User Pengarsip',
    'archived_by.name': 'Nama Pengarsip',
    'archived_by.email': 'Email Pengarsip',
    'verified_by.id': 'ID User Verifikator',
    'verified_by.name': 'Nama Verifikator',
    'verified_by.email': 'Email Verifikator',
};

const formatPayloadValue = (value) => {
    if (value === null || value === undefined || String(value).trim() === '') {
        return '-';
    }

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    if (typeof value === 'string') {
        const trimmed = value.trim();

        if (/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z)?)?$/.test(trimmed)) {
            const date = new Date(trimmed);

            if (!Number.isNaN(date.getTime())) {
                return date.toLocaleString('id-ID');
            }
        }

        return trimmed;
    }

    return String(value);
};

const toLabel = (key) =>
    payloadLabelMap[key] ||
    key
        .replaceAll('.', ' ')
        .replaceAll('_', ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase());

const flattenPayload = (value, prefix = '') => {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return [];
    }

    const entries = [];

    Object.entries(value).forEach(([key, currentValue]) => {
        const path = prefix ? `${prefix}.${key}` : key;

        if (currentValue !== null && typeof currentValue === 'object' && !Array.isArray(currentValue)) {
            entries.push(...flattenPayload(currentValue, path));

            return;
        }

        if (ignoredPayloadKeys.has(path) || ignoredPayloadKeys.has(key)) {
            return;
        }

        let formattedValue = '-';

        if (Array.isArray(currentValue)) {
            formattedValue = currentValue.length ? currentValue.map((item) => formatPayloadValue(item)).join(', ') : '-';
        } else {
            formattedValue = formatPayloadValue(currentValue);
        }

        entries.push({
            key: path,
            label: toLabel(path),
            value: formattedValue,
        });
    });

    return entries;
};

const integrationEntries = computed(() => flattenPayload(props.alumni.integration_payload));
</script>

<template>
    <Head title="Detail Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Detail Alumni
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900">{{ alumni.nama }}</h3>
                            <p class="text-sm text-gray-500">NIM: {{ alumni.nim }}</p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="route('alumni.edit', alumni.id)"
                                class="rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                            >
                                Edit
                            </Link>
                            <Link
                                :href="route('alumni.index')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Kembali
                            </Link>
                        </div>
                    </div>

                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start">
                        <img
                            v-if="photoUrl"
                            :src="photoUrl"
                            alt="Foto alumni"
                            class="h-[180px] w-[120px] rounded-md border border-gray-200 object-cover"
                        />
                        <div
                            v-else
                            class="flex h-[180px] w-[120px] items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 text-xs font-semibold text-gray-500"
                        >
                            Tidak ada foto
                        </div>
                        <p class="text-sm text-gray-600">
                            Foto alumni ditampilkan dari data hasil integrasi API jika tersedia.
                        </p>
                    </div>

                    <dl class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500">Jurusan</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.jurusan }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Angkatan</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.angkatan }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Tahun Lulus</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.tahun_lulus || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">No. Telepon</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.no_telepon || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.email || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Pekerjaan</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.pekerjaan || '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-gray-500">Instansi / Perusahaan</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.instansi || '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-gray-500">Alamat</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm font-medium text-gray-900">
                                {{ alumni.alamat || '-' }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-8 rounded-md border border-gray-200 bg-gray-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-800">Data Integrasi Lengkap (API)</h4>
                        <p class="mt-1 text-xs text-gray-600">
                            Seluruh data tambahan dari API ditampilkan dalam format field, bukan JSON mentah.
                        </p>

                        <dl v-if="hasPayload && integrationEntries.length" class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="entry in integrationEntries"
                                :key="entry.key"
                                class="rounded-md border border-gray-200 bg-white p-3"
                            >
                                <dt class="text-xs font-medium text-gray-500">{{ entry.label }}</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{{ entry.value }}</dd>
                            </div>
                        </dl>

                        <p v-else class="mt-3 text-sm text-gray-500">
                            Data integrasi API belum tersedia untuk alumni ini.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
