<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { reactive } from 'vue';

const props = defineProps({
    newsPosts: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: '',
        }),
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

const submitFilters = () => {
    router.get(route('berita.index'), filterForm, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = '';
    submitFilters();
};

const destroy = async (id, title) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Hapus berita?',
        text: `Berita "${title}" akan dihapus permanen.`,
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(route('berita.destroy', id));
};

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Kelola Berita Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Kelola Berita Alumni
                </h2>
                <Link
                    :href="route('berita.create')"
                    class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500"
                >
                    Tambah Berita
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <form class="grid gap-4 md:grid-cols-4" @submit.prevent="submitFilters">
                        <div class="md:col-span-3">
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Cari Berita
                            </label>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Judul atau ringkasan berita"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Status
                            </label>
                            <select
                                v-model="filterForm.status"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Semua</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>

                        <div class="md:col-span-4 flex flex-wrap gap-2">
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
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Judul</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Tanggal Publikasi</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="item in newsPosts.data"
                                    :key="item.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <img
                                                v-if="item.cover_image_url"
                                                :src="item.cover_image_url"
                                                alt="Cover berita"
                                                class="h-11 w-16 rounded-md border border-gray-200 object-cover"
                                            />
                                            <div>
                                                <p class="font-medium text-gray-900">{{ item.title }}</p>
                                                <p class="mt-1 line-clamp-1 text-xs text-gray-500">
                                                    {{ item.excerpt || '-' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ formatDateTime(item.published_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="item.is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                        >
                                            {{ item.is_published ? 'Published' : 'Draft' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <Link
                                                :href="route('berita.show', item.id)"
                                                class="rounded-md border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                            >
                                                Detail
                                            </Link>
                                            <Link
                                                :href="route('berita.edit', item.id)"
                                                class="rounded-md border border-indigo-200 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                class="rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                                                @click="destroy(item.id, item.title)"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="newsPosts.data.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                        Belum ada data berita alumni.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="newsPosts.links.length > 3"
                        class="flex flex-wrap items-center gap-2 border-t border-gray-100 p-4"
                    >
                        <Link
                            v-for="(link, index) in newsPosts.links"
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
