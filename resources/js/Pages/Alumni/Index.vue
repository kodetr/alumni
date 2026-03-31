<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    alumni: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            angkatan: '',
            jurusan: '',
            per_page: 20,
        }),
    },
    perPageOptions: {
        type: Array,
        default: () => [20, 30, 50, 100],
    },
    angkatanOptions: {
        type: Array,
        default: () => [],
    },
    jurusanOptions: {
        type: Array,
        default: () => [],
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
    angkatan: props.filters.angkatan ?? '',
    jurusan: props.filters.jurusan ?? '',
    per_page: String(props.filters.per_page ?? 20),
});

const submitFilters = () => {
    router.get(route('alumni.index'), filterForm, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.angkatan = '';
    filterForm.jurusan = '';
    filterForm.per_page = '20';
    submitFilters();
};

const destroy = (id, nama) => {
    if (confirm(`Hapus data alumni ${nama}?`)) {
        router.delete(route('alumni.destroy', id));
    }
};
</script>

<template>
    <Head title="Data Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Data Alumni
                </h2>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <form
                        class="grid gap-4 md:grid-cols-5"
                        @submit.prevent="submitFilters"
                    >
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Cari
                            </label>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Nama, NIM, jurusan, email"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Angkatan
                            </label>
                            <select
                                v-model="filterForm.angkatan"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Semua</option>
                                <option
                                    v-for="year in angkatanOptions"
                                    :key="year"
                                    :value="String(year)"
                                >
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Jurusan
                            </label>
                            <select
                                v-model="filterForm.jurusan"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Semua</option>
                                <option
                                    v-for="major in jurusanOptions"
                                    :key="major"
                                    :value="major"
                                >
                                    {{ major }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Tampilkan
                            </label>
                            <select
                                v-model="filterForm.per_page"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="submitFilters"
                            >
                                <option
                                    v-for="size in perPageOptions"
                                    :key="size"
                                    :value="String(size)"
                                >
                                    {{ size }} data
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-5 flex flex-wrap gap-2">
                            <button
                                type="submit"
                                class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                            >
                                Terapkan
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="resetFilters"
                            >
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">NIM</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Nama</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Jurusan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Angkatan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="item in alumni.data"
                                    :key="item.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 text-gray-700">{{ item.nim }}</td>
                                    <td class="px-4 py-3 text-gray-900">{{ item.nama }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ item.jurusan }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ item.angkatan }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ item.email || '-' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <Link
                                                :href="route('alumni.show', item.id)"
                                                class="rounded-md border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                            >
                                                Detail
                                            </Link>
                                            <Link
                                                :href="route('alumni.edit', item.id)"
                                                class="rounded-md border border-indigo-200 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                class="rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                                                @click="destroy(item.id, item.nama)"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="alumni.data.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        Belum ada data alumni.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="alumni.links.length > 3"
                        class="flex flex-wrap items-center gap-2 border-t border-gray-100 p-4"
                    >
                        <Link
                            v-for="(link, index) in alumni.links"
                            :key="index"
                            :href="link.url || ''"
                            class="rounded-md px-3 py-1.5 text-sm"
                            :class="{
                                'bg-indigo-600 text-white': link.active,
                                'text-gray-500': !link.url,
                                'border border-gray-300 text-gray-700 hover:bg-gray-50': link.url && !link.active,
                            }"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
