<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Webinar / Seminar" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Event - Webinar / Seminar
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-blue-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-blue-700">Sub Menu 2</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Webinar dan seminar alumni</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Halaman ini disiapkan untuk publikasi webinar, seminar, dan kelas pengembangan karier alumni.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
