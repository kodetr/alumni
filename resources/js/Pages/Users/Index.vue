<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { reactive } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
});
const page = usePage();

const submitFilters = () => {
    router.get(route('admin.users.index'), filterForm, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    submitFilters();
};

const formatRole = (role) => {
    return ['superadmin', 'admin'].includes(role) ? 'Super Admin' : role;
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

const destroy = async (id, name) => {
    if (id === page.props.auth.user.id) {
        await Swal.fire({
            icon: 'info',
            title: 'Tidak dapat dihapus',
            text: 'Akun yang sedang aktif tidak bisa dihapus.',
            confirmButtonColor: '#4f46e5',
        });

        return;
    }

    const result = await Swal.fire({
        icon: 'warning',
        title: 'Hapus user super admin?',
        text: `User ${name} akan dihapus permanen.`,
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(route('admin.users.destroy', id), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: `User ${name} berhasil dihapus.`,
                confirmButtonColor: '#4f46e5',
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal menghapus',
                text: `User ${name} gagal dihapus.`,
                confirmButtonColor: '#4f46e5',
            });
        },
    });
};
</script>

<template>
    <Head title="User Super Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    User Super Admin
                </h2>

                <Link
                    :href="route('admin.users.create')"
                    class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500"
                >
                    Tambah User
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <form class="grid gap-4 md:grid-cols-4" @submit.prevent="submitFilters">
                        <div class="md:col-span-3">
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Cari User
                            </label>

                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Nama atau email"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
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
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Nama</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Role</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Dibuat</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="item in users.data"
                                    :key="item.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <span>{{ item.name }}</span>
                                            <span
                                                v-if="item.id === $page.props.auth.user.id"
                                                class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700"
                                            >
                                                Anda
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ item.email }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ formatRole(item.role) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ formatDateTime(item.created_at) }}</td>
                                    <td class="px-4 py-3">
                                        <button
                                            type="button"
                                            class="rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="item.id === $page.props.auth.user.id"
                                            @click="destroy(item.id, item.name)"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Belum ada user super admin.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="users.links.length > 3"
                        class="flex flex-wrap items-center gap-2 border-t border-gray-100 p-4"
                    >
                        <Link
                            v-for="(link, index) in users.links"
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
