<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { reactive, ref } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    alumniAccountsCount: {
        type: Number,
        default: 0,
    },
    globalPermissions: {
        type: Object,
        default: () => ({}),
    },
    permissionCatalog: {
        type: Array,
        default: () => [],
    },
    permissionsFeatureReady: {
        type: Boolean,
        default: true,
    },
    permissionsMissingCount: {
        type: Number,
        default: 0,
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
const showPermissionManager = ref(false);
const clonePermissions = (value) => JSON.parse(JSON.stringify(value ?? {}));

const globalPermissionForm = reactive(clonePermissions(props.globalPermissions));

const getPermissionValue = (group, key) => {
    return Boolean(globalPermissionForm?.[group]?.[key]);
};

const setPermissionValue = (group, key, checked) => {
    if (!globalPermissionForm[group]) {
        globalPermissionForm[group] = {};
    }

    globalPermissionForm[group][key] = checked;
};

const setAllPermissions = (enabled) => {
    props.permissionCatalog.forEach((section) => {
        section.items.forEach((permissionItem) => {
            setPermissionValue(section.group, permissionItem.key, enabled);
        });
    });
};

const applyPreset = async (preset) => {
    const result = await Swal.fire({
        icon: 'question',
        title: 'Terapkan preset akses?',
        text: 'Checklist saat ini akan disesuaikan otomatis berdasarkan preset yang dipilih.',
        showCancelButton: true,
        confirmButtonText: 'Terapkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
    });

    if (!result.isConfirmed) {
        return;
    }

    if (preset === 'all') {
        setAllPermissions(true);

        return;
    }

    setAllPermissions(false);

    if (preset === 'readonly') {
        [
            ['features', 'social_forum'],
            ['features', 'event_reunion'],
            ['features', 'event_webinar'],
            ['features', 'mapping_locations'],
            ['features', 'mapping_global'],
            ['features', 'donation_online'],
            ['features', 'business_marketplace'],
        ].forEach(([group, key]) => setPermissionValue(group, key, true));

        return;
    }

    if (preset === 'basic') {
        [
            ['actions', 'edit'],
            ['features', 'profile_edit'],
            ['features', 'social_forum'],
            ['features', 'social_chat'],
            ['features', 'career_center'],
            ['features', 'event_reunion'],
            ['features', 'event_webinar'],
            ['features', 'event_rsvp'],
            ['features', 'mapping_locations'],
            ['features', 'donation_online'],
            ['features', 'business_marketplace'],
        ].forEach(([group, key]) => setPermissionValue(group, key, true));
    }
};

const resetPermissionForm = () => {
    const resetValues = clonePermissions(props.globalPermissions);
    Object.keys(globalPermissionForm).forEach((key) => {
        delete globalPermissionForm[key];
    });
    Object.assign(globalPermissionForm, resetValues);
};

const savePermissions = () => {
    router.patch(route('admin.users.permissions.update-global'), globalPermissionForm, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: `Hak akses global alumni berhasil diperbarui untuk semua akun alumni.`,
                confirmButtonColor: '#4f46e5',
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal menyimpan',
                text: 'Hak akses global alumni gagal diperbarui.',
                confirmButtonColor: '#4f46e5',
            });
        },
    });
};

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
            <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="permissionsFeatureReady" class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                            <h3 class="text-sm font-semibold text-gray-800">Manajemen Role &amp; Hak Akses Alumni</h3>
                            <p class="mt-1 text-xs text-gray-500">
                                Berlaku untuk semua akun alumni. Cukup checklist fitur yang dibuka.
                            </p>
                            <p class="mt-1 text-xs text-indigo-700">
                                Total akun alumni terdampak: {{ alumniAccountsCount }} akun
                            </p>
                            <p v-if="permissionsMissingCount > 0" class="mt-1 text-xs text-amber-700">
                                Terdapat {{ permissionsMissingCount }} akun lama yang belum punya data permission.
                            </p>
                            </div>

                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md border border-indigo-200 px-3 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-50"
                                @click="showPermissionManager = !showPermissionManager"
                            >
                                {{ showPermissionManager ? 'Tutup Akses Alumni' : 'Buka Akses Alumni' }}
                                <svg
                                    class="h-4 w-4 transition-transform duration-200"
                                    :class="showPermissionManager ? 'rotate-180' : 'rotate-0'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-[2200px] opacity-100"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="max-h-[2200px] opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                    >
                    <div v-if="showPermissionManager" class="overflow-hidden p-4">
                        <div class="mb-4 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-md border border-indigo-300 px-3 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-50"
                                @click="applyPreset('all')"
                            >
                                Preset: Semua Fitur
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-sky-300 px-3 py-1.5 text-xs font-medium text-sky-700 transition hover:bg-sky-50"
                                @click="applyPreset('basic')"
                            >
                                Preset: Basic Alumni
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50"
                                @click="applyPreset('readonly')"
                            >
                                Preset: Read-only
                            </button>

                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="setAllPermissions(true)"
                            >
                                Semua ON
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="setAllPermissions(false)"
                            >
                                Semua OFF
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50"
                                @click="resetPermissionForm"
                            >
                                Reset
                            </button>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div v-for="section in permissionCatalog" :key="section.group" class="rounded-md border border-gray-200 p-3">
                                <p class="mb-2 text-sm font-semibold text-gray-800">{{ section.title }}</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <label
                                        v-for="permissionItem in section.items"
                                        :key="`${section.group}-${permissionItem.key}`"
                                        class="inline-flex items-center gap-2 rounded-md border border-gray-100 px-2.5 py-2 text-sm text-gray-700"
                                    >
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            :checked="getPermissionValue(section.group, permissionItem.key)"
                                            @change="setPermissionValue(section.group, permissionItem.key, $event.target.checked)"
                                        />
                                        <span>{{ permissionItem.label }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button
                                type="button"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500"
                                @click="savePermissions"
                            >
                                Simpan Checklist Fitur Alumni
                            </button>
                        </div>
                    </div>
                    </Transition>
                </div>

                <div v-else class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Fitur manajemen hak akses belum aktif karena kolom <code>access_permissions</code> belum tersedia. Jalankan migration lalu refresh halaman.
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <form class="space-y-2" @submit.prevent="submitFilters">
                        <label class="block text-sm font-medium text-gray-700">
                            Cari User
                        </label>

                        <div class="flex flex-col gap-2 md:flex-row md:items-center">
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Nama, email, atau NIM"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:flex-1"
                            />

                            <div class="flex flex-wrap gap-2">
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
