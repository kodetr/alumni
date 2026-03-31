<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    alumni: {
        type: Object,
        required: true,
    },
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
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
