<script setup>
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
    stats: {
        type: Object,
        default: () => ({
            totalAlumni: 0,
            alumniTahunIni: 0,
            tracking: {
                bekerja: { count: 0, percentage: 0 },
                kuliah_lanjut: { count: 0, percentage: 0 },
                wirausaha: { count: 0, percentage: 0 },
                lainnya: { count: 0, percentage: 0 },
                classified_count: 0,
            },
            trackingScopeTotal: 0,
            trackingScopeLabel: 'Semua tahun',
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            year: null,
            availableYears: [],
        }),
    },
});

const selectedYear = ref(props.filters?.year ? String(props.filters.year) : 'all');

watch(() => props.filters?.year, (year) => {
    selectedYear.value = year ? String(year) : 'all';
});

const applyYearFilter = () => {
    const params = selectedYear.value === 'all' ? {} : { year: selectedYear.value };

    router.get(route('dashboard'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const percentLabel = (value) => `${Number(value ?? 0).toFixed(1)}%`;

const trackingItems = computed(() => ([
    {
        key: 'bekerja',
        label: 'Bekerja',
        color: 'bg-emerald-500',
        count: props.stats.tracking?.bekerja?.count ?? 0,
        percentage: props.stats.tracking?.bekerja?.percentage ?? 0,
    },
    {
        key: 'kuliah_lanjut',
        label: 'Kuliah lanjut',
        color: 'bg-sky-500',
        count: props.stats.tracking?.kuliah_lanjut?.count ?? 0,
        percentage: props.stats.tracking?.kuliah_lanjut?.percentage ?? 0,
    },
    {
        key: 'wirausaha',
        label: 'Wirausaha',
        color: 'bg-amber-500',
        count: props.stats.tracking?.wirausaha?.count ?? 0,
        percentage: props.stats.tracking?.wirausaha?.percentage ?? 0,
    },
]));

const classifiedSummary = computed(() => {
    const classified = props.stats.tracking?.classified_count ?? 0;
    const total = props.stats.trackingScopeTotal ?? 0;

    return `${classified} / ${total}`;
});

const donutSegments = computed(() => {
    const base = trackingItems.value.map((item) => ({
        key: item.key,
        label: item.label,
        color: item.color.replace('bg-', 'text-'),
        percentage: Number(item.percentage ?? 0),
    }));
    const knownTotal = base.reduce((sum, item) => sum + item.percentage, 0);
    const otherPercentage = Math.max(0, Number((100 - knownTotal).toFixed(1)));
    const all = otherPercentage > 0
        ? [...base, { key: 'lainnya', label: 'Lainnya', color: 'text-gray-400', percentage: otherPercentage }]
        : base;

    const circumference = 2 * Math.PI * 44;
    let offset = 0;

    return all.map((item) => {
        const stroke = (Math.max(0, Math.min(100, item.percentage)) / 100) * circumference;
        const segment = {
            ...item,
            dasharray: `${stroke} ${circumference - stroke}`,
            dashoffset: -offset,
        };

        offset += stroke;

        return segment;
    });
});

const donutCenterLabel = computed(() => `${props.stats.trackingScopeTotal ?? 0} data`);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard Alumni
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="isAdmin" class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <p class="text-sm text-gray-500">Total Alumni</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ stats.totalAlumni }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <p class="text-sm text-gray-500">Lulusan Tahun Ini</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ stats.alumniTahunIni }}
                        </p>
                    </div>
                </div>

                <div v-if="isAdmin" class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Statistik &amp; Tracking Alumni</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Persentase status alumni: bekerja, kuliah lanjut, dan wirausaha.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <label for="tracking-year" class="text-xs font-medium text-gray-600">Tahun lulus</label>
                            <select
                                id="tracking-year"
                                v-model="selectedYear"
                                class="rounded-md border border-gray-300 py-1.5 pl-2 pr-8 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                @change="applyYearFilter"
                            >
                                <option value="all">Semua tahun</option>
                                <option v-for="year in (filters.availableYears || [])" :key="year" :value="String(year)">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                        <span class="rounded-md bg-gray-100 px-2.5 py-1 font-medium text-gray-700">
                            Cakupan: {{ stats.trackingScopeLabel }}
                        </span>
                        <span class="rounded-md bg-gray-100 px-2.5 py-1 font-medium text-gray-700">
                            Data terklasifikasi: {{ classifiedSummary }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div v-for="item in trackingItems" :key="item.key" class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p class="text-sm font-medium text-gray-600">{{ item.label }}</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ percentLabel(item.percentage) }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ item.count }} alumni</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
                        <div class="flex flex-col items-center justify-center rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p class="text-sm font-semibold text-gray-800">Visualisasi data</p>
                            <div class="relative mt-3 h-48 w-48">
                                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                                    <circle cx="60" cy="60" r="44" fill="none" stroke="#e5e7eb" stroke-width="14" />
                                    <circle
                                        v-for="segment in donutSegments"
                                        :key="`donut-${segment.key}`"
                                        cx="60"
                                        cy="60"
                                        r="44"
                                        fill="none"
                                        :class="segment.color"
                                        stroke="currentColor"
                                        stroke-width="14"
                                        stroke-linecap="round"
                                        :stroke-dasharray="segment.dasharray"
                                        :stroke-dashoffset="segment.dashoffset"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <p class="text-xs font-medium text-gray-500">Total data</p>
                                    <p class="text-sm font-semibold text-gray-800">{{ donutCenterLabel }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div v-for="item in trackingItems" :key="`${item.key}-bar`" class="space-y-1">
                                <div class="flex items-center justify-between text-xs text-gray-600">
                                    <span>{{ item.label }}</span>
                                    <span>{{ percentLabel(item.percentage) }}</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="item.color"
                                        :style="{ width: `${Math.min(100, Math.max(0, item.percentage))}%` }"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs text-gray-600">
                                    <span>Lainnya</span>
                                    <span>{{ percentLabel(stats.tracking?.lainnya?.percentage ?? 0) }}</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        class="h-full rounded-full bg-gray-400 transition-all duration-300"
                                        :style="{ width: `${Math.min(100, Math.max(0, stats.tracking?.lainnya?.percentage ?? 0))}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="isAdmin" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Kelola Data Alumni
                    </h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Tambahkan dan perbarui informasi alumni secara terpusat.
                    </p>
                    <Link
                        :href="route('alumni.index')"
                        class="mt-4 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500"
                    >
                        Buka Data Alumni
                    </Link>
                </div>

                <div v-if="isAdmin" class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Kelola Berita Alumni</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Tambah informasi terbaru untuk ditampilkan di landing page.
                        </p>
                        <Link
                            :href="route('berita.index')"
                            class="mt-4 inline-flex rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-50"
                        >
                            Buka Berita
                        </Link>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Kelola Agenda Alumni</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Atur jadwal kegiatan agar alumni melihat agenda terbaru.
                        </p>
                        <Link
                            :href="route('agenda.index')"
                            class="mt-4 inline-flex rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-50"
                        >
                            Buka Agenda
                        </Link>
                    </div>
                </div>

                <div v-else class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Akun Alumni Aktif
                    </h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Kamu sudah login sebagai alumni. Hubungi admin jika membutuhkan pembaruan data master alumni.
                    </p>
                    <Link
                        :href="route('profile.edit')"
                        class="mt-4 inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Edit Profil Saya
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
