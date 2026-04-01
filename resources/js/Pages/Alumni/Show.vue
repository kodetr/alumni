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

const studyProgram = computed(() => {
    const organisasi = typeof props.alumni.organisasi === 'string' ? props.alumni.organisasi.trim() : '';

    if (organisasi !== '') {
        return organisasi;
    }

    const jurusan = typeof props.alumni.jurusan === 'string' ? props.alumni.jurusan.trim() : '';

    return jurusan !== '' ? jurusan : null;
});

const displayValue = (value) => {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'string') {
        const cleaned = value.trim();

        return cleaned !== '' ? cleaned : '-';
    }

    return String(value);
};

const displayBirthDate = computed(() => {
    const value = props.alumni.tanggal_lahir;

    if (!value) {
        return '-';
    }

    if (typeof value === 'string') {
        return value.split('T')[0];
    }

    return String(value);
});

const displayGender = computed(() => {
    if (props.alumni.jenis_kelamin === 'L') {
        return 'Laki-laki';
    }

    if (props.alumni.jenis_kelamin === 'P') {
        return 'Perempuan';
    }

    return '-';
});

const displayEmploymentStatus = computed(() => {
    if (props.alumni.status_bekerja === true) {
        return 'Ya';
    }

    if (props.alumni.status_bekerja === false) {
        return 'Tidak';
    }

    return '-';
});

const documentLink = computed(() => {
    const value = props.alumni.link_dokumen_tambahan;

    if (typeof value !== 'string') {
        return null;
    }

    const cleaned = value.trim();

    return cleaned !== '' ? cleaned : null;
});
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
                        <h3 class="text-2xl font-semibold text-gray-900">Informasi Alumni</h3>

                        <div class="flex gap-2">
                            <Link
                                :href="route('alumni.index')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Kembali
                            </Link>
                        </div>
                    </div>

                    <div class="mb-6 flex flex-col gap-4 border-b pb-6 sm:flex-row sm:items-start">
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

                        <div class="flex-1">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-500">NIM</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ displayValue(alumni.nim) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Nama</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ displayValue(alumni.nama) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Tahun Lulus</p>
                                    <p class="font-semibold text-slate-700">{{ displayValue(alumni.tahun_lulus) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Email Kampus</p>
                                    <p class="font-semibold text-slate-700">{{ displayValue(alumni.email_kampus) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500">Program Studi</p>
                            <p class="font-medium text-gray-900">{{ displayValue(studyProgram) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Fakultas</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.fakultas) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Email Pribadi</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.email_pribadi) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">No. Telepon</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.no_telepon) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Pekerjaan</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.pekerjaan) }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs text-gray-500">Alamat</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.alamat) }}</p>
                        </div>

                        <div class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Pribadi</h4>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Tempat Lahir</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.tempat_lahir) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Tanggal Lahir</p>
                            <p class="font-medium text-gray-900">{{ displayBirthDate }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Agama</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.agama) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Jenis Kelamin</p>
                            <p class="font-medium text-gray-900">{{ displayGender }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">No. KTP</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.no_ktp) }}</p>
                        </div>

                        <div class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Akademik</h4>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">IPK</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.ipk) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Predikat</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.predikat) }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs text-gray-500">Judul Skripsi/Tesis</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.judul_skripsi) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Pembimbing 1</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.pembimbing_1) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Pembimbing 2</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.pembimbing_2) }}</p>
                        </div>

                        <div class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Wisuda</h4>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Ukuran Toga</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.ukuran_toga) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Status Bekerja</p>
                            <p class="font-medium text-gray-900">{{ displayEmploymentStatus }}</p>
                        </div>

                        <div class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Orang Tua</h4>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Nama Ayah</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.nama_ayah) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Nama Ibu</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.nama_ibu) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">No. Telepon Orang Tua</p>
                            <p class="font-medium text-gray-900">{{ displayValue(alumni.no_telepon_orang_tua) }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs text-gray-500">Link Dokumen Tambahan</p>
                            <a
                                v-if="documentLink"
                                :href="documentLink"
                                target="_blank"
                                class="font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                {{ documentLink }}
                            </a>
                            <p v-else class="font-medium text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
