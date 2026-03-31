<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    event: {
        type: Object,
        required: true,
    },
});

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'full',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Detail Agenda Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Detail Agenda Alumni
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900">{{ event.title }}</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ formatDate(event.event_date) }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="route('agenda.edit', event.id)"
                                class="rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                            >
                                Edit
                            </Link>
                            <Link
                                :href="route('agenda.index')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Kembali
                            </Link>
                        </div>
                    </div>

                    <div v-if="event.poster_image_url" class="mb-6">
                        <img
                            :src="event.poster_image_url"
                            alt="Poster agenda alumni"
                            class="h-64 w-full rounded-lg border border-gray-200 object-cover"
                        />
                    </div>

                    <dl class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-gray-500">Slug</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ event.slug }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="event.is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ event.is_published ? 'Published' : 'Draft' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Tanggal Agenda</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ formatDate(event.event_date) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Lokasi</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ event.location || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Link Pendaftaran</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                <a
                                    v-if="event.registration_url"
                                    :href="event.registration_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-indigo-600 hover:text-indigo-500"
                                >
                                    {{ event.registration_url }}
                                </a>
                                <span v-else>-</span>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-gray-500">Deskripsi</dt>
                            <dd class="mt-1 text-sm leading-relaxed text-gray-900">
                                <div class="rich-content" v-html="event.description || '-'"></div>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
