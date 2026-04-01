<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Chat Antar Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Jejaring Sosial - Chat Antar Alumni
            </h2>
        </template>

        <div class="py-10">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-emerald-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-emerald-700">Sub Menu 2</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Percakapan langsung antar alumni</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Halaman ini disiapkan untuk fitur chat pribadi agar alumni bisa saling terhubung lebih cepat.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
