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

                        <div v-if="alumni.tempat_lahir || alumni.tanggal_lahir" class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Pribadi ( dari API)</h4>
                        </div>
                        <div v-if="alumni.tempat_lahir">
                            <dt class="text-sm text-gray-500">Tempat Lahir</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.tempat_lahir }}</dd>
                        </div>
                        <div v-if="alumni.tanggal_lahir">
                            <dt class="text-sm text-gray-500">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.tanggal_lahir }}</dd>
                        </div>
                        <div v-if="alumni.agama">
                            <dt class="text-sm text-gray-500">Agama</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.agama }}</dd>
                        </div>
                        <div v-if="alumni.jenis_kelamin">
                            <dt class="text-sm text-gray-500">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                        </div>
                        <div v-if="alumni.no_ktp">
                            <dt class="text-sm text-gray-500">No. KTP</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.no_ktp }}</dd>
                        </div>

                        <div v-if="alumni.ipk || alumni.predikat || alumni.judul_skripsi" class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Akademik ( dari API)</h4>
                        </div>
                        <div v-if="alumni.ipk">
                            <dt class="text-sm text-gray-500">IPK</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.ipk }}</dd>
                        </div>
                        <div v-if="alumni.predikat">
                            <dt class="text-sm text-gray-500">Predikat</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.predikat }}</dd>
                        </div>
                        <div v-if="alumni.judul_skripsi" class="sm:col-span-2">
                            <dt class="text-sm text-gray-500">Judul Skripsi/Tesis</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.judul_skripsi }}</dd>
                        </div>
                        <div v-if="alumni.pembimbing_1">
                            <dt class="text-sm text-gray-500">Pembimbing 1</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.pembimbing_1 }}</dd>
                        </div>
                        <div v-if="alumni.pembimbing_2">
                            <dt class="text-sm text-gray-500">Pembimbing 2</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.pembimbing_2 }}</dd>
                        </div>

                        <div v-if="alumni.ukuran_toga || alumni.status_bekerja !== null" class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Wisuda ( dari API)</h4>
                        </div>
                        <div v-if="alumni.ukuran_toga">
                            <dt class="text-sm text-gray-500">Ukuran Toga</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.ukuran_toga }}</dd>
                        </div>
                        <div v-if="alumni.status_bekerja !== null">
                            <dt class="text-sm text-gray-500">Status Bekerja</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.status_bekerja ? 'Ya' : 'Tidak' }}</dd>
                        </div>

                        <div v-if="alumni.nama_ayah || alumni.nama_ibu || alumni.no_telepon_orang_tua" class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Orang Tua ( dari API)</h4>
                        </div>
                        <div v-if="alumni.nama_ayah">
                            <dt class="text-sm text-gray-500">Nama Ayah</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.nama_ayah }}</dd>
                        </div>
                        <div v-if="alumni.nama_ibu">
                            <dt class="text-sm text-gray-500">Nama Ibu</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.nama_ibu }}</dd>
                        </div>
                        <div v-if="alumni.no_telepon_orang_tua">
                            <dt class="text-sm text-gray-500">No. Telepon Orang Tua</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ alumni.no_telepon_orang_tua }}</dd>
                        </div>

                        <div v-if="alumni.link_dokumen_tambahan" class="sm:col-span-2 border-t pt-4 mt-2">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Link Dokumen</h4>
                            <a
                                :href="alumni.link_dokumen_tambahan"
                                target="_blank"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                {{ alumni.link_dokumen_tambahan }}
                            </a>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
