<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Forum Diskusi / Komunitas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Jejaring Sosial - Forum Diskusi / Komunitas
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-indigo-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-indigo-700">Sub Menu 1</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Forum diskusi dan komunitas alumni</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Ruang ini disiapkan untuk diskusi topik kampus, karier, dan kolaborasi antar alumni.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
