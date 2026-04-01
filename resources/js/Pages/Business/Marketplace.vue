<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Marketplace" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Bisnis - Marketplace
            </h2>
        </template>

        <div class="py-10">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-lime-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-lime-700">Sub Menu 1</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Marketplace alumni</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Halaman ini disiapkan untuk promosi produk dan jasa milik alumni dalam satu ekosistem.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
