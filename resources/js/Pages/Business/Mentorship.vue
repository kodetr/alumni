<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Mentorship" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Bisnis - Mentorship
            </h2>
        </template>

        <div class="py-10">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-orange-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-orange-700">Sub Menu 3</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Mentorship bisnis alumni</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Halaman ini disiapkan untuk mentoring usaha, konsultasi bisnis, dan berbagi pengalaman profesional.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
