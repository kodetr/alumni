<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
    stats: {
        type: Object,
        default: () => ({
            totalAlumni: 0,
            alumniTahunIni: 0,
        }),
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard Alumni
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div v-if="isAdmin" class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <p class="text-sm text-gray-500">Total Alumni</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ stats.totalAlumni }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <p class="text-sm text-gray-500">Lulusan Tahun Ini</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ stats.alumniTahunIni }}
                        </p>
                    </div>
                </div>

                <div v-if="isAdmin" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Kelola Data Alumni
                    </h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Tambahkan dan perbarui informasi alumni secara terpusat.
                    </p>
                    <Link
                        :href="route('alumni.index')"
                        class="mt-4 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500"
                    >
                        Buka Data Alumni
                    </Link>
                </div>

                <div v-if="isAdmin" class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Kelola Berita Alumni</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Tambah informasi terbaru untuk ditampilkan di landing page.
                        </p>
                        <Link
                            :href="route('berita.index')"
                            class="mt-4 inline-flex rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-50"
                        >
                            Buka Berita
                        </Link>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Kelola Agenda Alumni</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Atur jadwal kegiatan agar alumni melihat agenda terbaru.
                        </p>
                        <Link
                            :href="route('agenda.index')"
                            class="mt-4 inline-flex rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-50"
                        >
                            Buka Agenda
                        </Link>
                    </div>
                </div>

                <div v-else class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Akun Alumni Aktif
                    </h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Kamu sudah login sebagai alumni. Hubungi admin jika membutuhkan pembaruan data master alumni.
                    </p>
                    <Link
                        :href="route('profile.edit')"
                        class="mt-4 inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Edit Profil Saya
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
