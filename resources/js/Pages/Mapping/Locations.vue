<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

const props = defineProps({
    alumni: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            from: null,
            to: null,
            total: 0,
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            totalAlumni: 0,
            withLocation: 0,
            withCoordinates: 0,
            withoutLocation: 0,
            uniqueCities: 0,
            coveragePercentage: 0,
        }),
    },
    topCities: {
        type: Array,
        default: () => [],
    },
    markers: {
        type: Array,
        default: () => [],
    },
    cityOptions: {
        type: Array,
        default: () => [],
    },
    yearOptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            year: '',
            city: '',
            per_page: 25,
        }),
    },
    perPageOptions: {
        type: Array,
        default: () => [25, 50, 100],
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
    year: props.filters.year ?? '',
    city: props.filters.city ?? '',
    per_page: String(props.filters.per_page ?? 25),
});

const submitFilters = () => {
    router.get(
        route('mapping.locations'),
        {
            search: filterForm.search,
            year: filterForm.year,
            city: filterForm.city,
            per_page: filterForm.per_page,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.year = '';
    filterForm.city = '';
    filterForm.per_page = String(props.perPageOptions[0] ?? 25);
    submitFilters();
};

const numberFormatter = new Intl.NumberFormat('id-ID');
const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const formatNumber = (value) => numberFormatter.format(Number(value ?? 0));

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return '-';
    }

    return dateFormatter.format(parsed);
};

const activeFilterCount = computed(() => [
    filterForm.search,
    filterForm.year,
    filterForm.city,
].filter((value) => value !== '').length);

const markerPoints = computed(() => {
    const list = Array.isArray(props.markers) ? props.markers : [];

    return list
        .map((item) => {
            const lat = Number(item.lat);
            const lng = Number(item.lng);

            return {
                ...item,
                lat,
                lng,
                count: Number(item.count ?? 0),
                percentage: Number(item.percentage ?? 0),
            };
        })
        .filter((item) => Number.isFinite(item.lat) && Number.isFinite(item.lng));
});

const mapContainerRef = ref(null);
const mapShellRef = ref(null);
const isMapFullscreen = ref(false);

let mapInstance = null;
let markerLayer = null;

const defaultMapCenter = [-2.5, 118];
const defaultMapZoom = 5;

const escapeText = (text) => String(text ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

const fitMapToMarkers = (bounds) => {
    if (!mapInstance) {
        return;
    }

    if (bounds.length === 0) {
        mapInstance.setView(defaultMapCenter, defaultMapZoom);

        return;
    }

    if (bounds.length === 1) {
        mapInstance.setView(bounds[0], 7);

        return;
    }

    mapInstance.fitBounds(bounds, {
        padding: [24, 24],
        maxZoom: 8,
    });
};

const getDensityMeta = (count, maxCount) => {
    const ratio = Math.max(0, Math.min(1, count / maxCount));

    if (ratio >= 0.67) {
        return { className: 'is-high', label: 'Tinggi' };
    }

    if (ratio >= 0.34) {
        return { className: 'is-medium', label: 'Sedang' };
    }

    return { className: 'is-low', label: 'Rendah' };
};

const buildGoogleLikeIcon = (count, maxCount, index, densityClass) => {
    const ratio = Math.max(0, Math.min(1, count / maxCount));
    const scale = 0.95 + (ratio * 0.35);
    const delay = Math.min(index * 45, 420);
    const displayCount = count > 99 ? '99+' : String(count);

    return L.divIcon({
        className: 'gmap-pin-wrapper',
        html: `
            <div class="gmap-pin-shell ${densityClass}" style="--pin-scale:${scale.toFixed(2)};--pin-delay:${delay}ms;">
                <div class="gmap-pin"></div>
                <div class="gmap-pin-shadow"></div>
                <span class="gmap-pin-badge">${displayCount}</span>
            </div>
        `,
        iconSize: [32, 42],
        iconAnchor: [16, 41],
        popupAnchor: [0, -36],
    });
};

const buildClusterIcon = (cluster) => {
    const childCount = cluster.getChildCount();
    let sizeClass = 'is-small';

    if (childCount >= 25) {
        sizeClass = 'is-large';
    } else if (childCount >= 10) {
        sizeClass = 'is-medium';
    }

    return L.divIcon({
        className: 'gmap-cluster-wrapper',
        html: `
            <div class="gmap-cluster ${sizeClass}">
                <span>${formatNumber(childCount)}</span>
            </div>
        `,
        iconSize: [44, 44],
        iconAnchor: [22, 22],
    });
};

const drawMarkers = () => {
    if (!mapInstance || !markerLayer) {
        return;
    }

    markerLayer.clearLayers();

    if (markerPoints.value.length === 0) {
        fitMapToMarkers([]);

        return;
    }

    const maxCount = Math.max(...markerPoints.value.map((point) => point.count), 1);
    const bounds = [];

    markerPoints.value.forEach((point, index) => {
        const densityMeta = getDensityMeta(point.count, maxCount);
        const popupText = `
            <div style="min-width: 170px;">
                <strong>${escapeText(point.label)}</strong><br>
                ${formatNumber(point.count)} alumni<br>
                Kepadatan: ${densityMeta.label}<br>
                ${point.percentage.toFixed(1)}% dari hasil filter
            </div>
        `;

        const marker = L.marker([point.lat, point.lng], {
            icon: buildGoogleLikeIcon(point.count, maxCount, index, densityMeta.className),
            title: point.label,
            riseOnHover: true,
        });

        marker.bindPopup(popupText);
        marker.addTo(markerLayer);
        bounds.push([point.lat, point.lng]);
    });

    fitMapToMarkers(bounds);
};

const initMap = () => {
    if (typeof window === 'undefined' || mapInstance || !mapContainerRef.value) {
        return;
    }

    mapInstance = L.map(mapContainerRef.value, {
        zoomControl: true,
        minZoom: 4,
        maxZoom: 18,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapInstance);

    markerLayer = L.markerClusterGroup({
        maxClusterRadius: 55,
        showCoverageOnHover: false,
        spiderfyOnMaxZoom: true,
        removeOutsideVisibleBounds: true,
        chunkedLoading: true,
        iconCreateFunction: buildClusterIcon,
    });

    mapInstance.addLayer(markerLayer);
    drawMarkers();

    setTimeout(() => {
        mapInstance.invalidateSize();
    }, 80);
};

const getFullscreenElement = () => document.fullscreenElement || document.webkitFullscreenElement || null;

const requestFullscreen = async () => {
    if (!mapShellRef.value) {
        return;
    }

    const element = mapShellRef.value;

    if (typeof element.requestFullscreen === 'function') {
        await element.requestFullscreen();

        return;
    }

    if (typeof element.webkitRequestFullscreen === 'function') {
        element.webkitRequestFullscreen();
    }
};

const exitFullscreen = async () => {
    if (typeof document.exitFullscreen === 'function') {
        await document.exitFullscreen();

        return;
    }

    if (typeof document.webkitExitFullscreen === 'function') {
        document.webkitExitFullscreen();
    }
};

const toggleMapFullscreen = async () => {
    if (!mapShellRef.value || typeof document === 'undefined') {
        return;
    }

    try {
        if (getFullscreenElement() === mapShellRef.value) {
            await exitFullscreen();
        } else {
            await requestFullscreen();
        }
    } catch {
        // Ignore fullscreen errors caused by browser policy.
    }
};

const handleFullscreenChange = () => {
    isMapFullscreen.value = getFullscreenElement() === mapShellRef.value;

    setTimeout(() => {
        mapInstance?.invalidateSize();
    }, 120);
};

watch(markerPoints, () => {
    drawMarkers();
});

onMounted(() => {
    initMap();

    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
});

onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);

    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
        markerLayer = null;
    }
});
</script>

<template>
    <Head title="Lokasi Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Mapping - Lokasi Alumni
                </h2>
                <span class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">
                    Cakupan koordinat: {{ stats.coveragePercentage }}%
                </span>
            </div>
        </template>

        <div class="py-10">
            <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-xl border border-cyan-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-cyan-700">Pemetaan data domisili alumni</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Pantau persebaran kota alumni secara cepat</h3>
                    <p class="mt-2 max-w-3xl text-sm text-gray-600">
                        Halaman ini menampilkan ringkasan kota terbanyak, titik koordinat utama, dan tabel detail alumni berdasarkan hasil pencocokan alamat serta tempat lahir.
                    </p>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total alumni</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ formatNumber(stats.totalAlumni) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Lokasi teridentifikasi</p>
                        <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ formatNumber(stats.withLocation) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Punya koordinat</p>
                        <p class="mt-2 text-2xl font-semibold text-cyan-700">{{ formatNumber(stats.withCoordinates) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Belum terpetakan</p>
                        <p class="mt-2 text-2xl font-semibold text-amber-600">{{ formatNumber(stats.withoutLocation) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Kota unik</p>
                        <p class="mt-2 text-2xl font-semibold text-indigo-700">{{ formatNumber(stats.uniqueCities) }}</p>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <form class="grid gap-4 md:grid-cols-6" @submit.prevent="submitFilters">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Cari alumni</label>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Nama, NIM, jurusan, alamat"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tahun lulus</label>
                            <select
                                v-model="filterForm.year"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="">Semua tahun</option>
                                <option v-for="year in yearOptions" :key="year" :value="String(year)">
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kota</label>
                            <select
                                v-model="filterForm.city"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="">Semua kota</option>
                                <option
                                    v-for="cityOption in cityOptions"
                                    :key="cityOption.key"
                                    :value="cityOption.key"
                                >
                                    {{ cityOption.label }} ({{ cityOption.count }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Per halaman</label>
                            <select
                                v-model="filterForm.per_page"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
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

                        <div class="flex flex-wrap items-end gap-2 md:col-span-6">
                            <button
                                type="submit"
                                class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-cyan-600"
                            >
                                Terapkan filter
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="resetFilters"
                            >
                                Reset
                            </button>
                            <span class="text-xs text-gray-500">
                                Filter aktif: {{ activeFilterCount }}
                            </span>
                        </div>
                    </form>
                </section>

                <section class="grid gap-6 xl:grid-cols-[2fr_1fr]">
                    <div class="overflow-hidden rounded-xl border border-cyan-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-cyan-100 px-5 py-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Peta titik kota alumni</h3>
                                <p class="mt-1 text-xs text-gray-500">Visualisasi koordinat kota dari data yang berhasil dipetakan.</p>
                            </div>
                            <button
                                type="button"
                                class="rounded-md border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100"
                                @click="toggleMapFullscreen"
                            >
                                {{ isMapFullscreen ? 'Keluar Full Screen' : 'Full Screen' }}
                            </button>
                        </div>

                        <div class="p-5">
                            <div ref="mapShellRef" class="leaflet-host relative overflow-hidden rounded-2xl border border-cyan-200">
                                <div ref="mapContainerRef" class="h-80 w-full md:h-[28rem]" />

                                <div class="pointer-events-none absolute left-3 top-3 rounded-md bg-white/95 px-3 py-2 text-[11px] font-medium text-gray-700 shadow">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500">Petunjuk pin</p>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="map-legend-pin map-legend-pin-high" />
                                            Tinggi
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <span class="map-legend-pin map-legend-pin-medium" />
                                            Sedang
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <span class="map-legend-pin map-legend-pin-low" />
                                            Rendah
                                        </span>
                                    </div>
                                    <p class="mt-1 text-[10px] text-gray-500">Titik berdekatan akan dikelompokkan otomatis.</p>
                                </div>

                                <div
                                    v-if="markerPoints.length === 0"
                                    class="pointer-events-none absolute bottom-4 left-1/2 -translate-x-1/2 rounded-md bg-white/95 px-3 py-2 text-xs text-gray-600 shadow"
                                >
                                    Belum ada titik koordinat untuk filter ini.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Kota terbanyak</h3>
                        <p class="mt-1 text-xs text-gray-500">Top 10 kota berdasarkan jumlah alumni pada hasil filter.</p>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="(cityItem, index) in topCities"
                                :key="cityItem.key"
                                class="rounded-lg border border-gray-100 p-3"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-800">{{ index + 1 }}. {{ cityItem.label }}</p>
                                    <span class="text-xs font-medium text-gray-500">{{ cityItem.count }} alumni</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                                    <div
                                        class="h-full rounded-full bg-cyan-500"
                                        :style="{ width: `${Math.min(100, cityItem.percentage || 0)}%` }"
                                    />
                                </div>
                            </div>

                            <div v-if="topCities.length === 0" class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                                Belum ada data kota untuk ditampilkan.
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Detail data alumni per lokasi</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Menampilkan {{ formatNumber(alumni.from || 0) }} - {{ formatNumber(alumni.to || 0) }} dari {{ formatNumber(alumni.total || 0) }} data.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Alumni</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Lokasi</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Sumber</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Koordinat</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Update</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="item in alumni.data"
                                    :key="item.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-semibold text-gray-900">{{ item.nama || '-' }}</p>
                                        <p class="text-xs text-gray-500">NIM {{ item.nim || '-' }} • {{ item.jurusan || '-' }}</p>
                                        <p class="text-xs text-gray-500">Lulus {{ item.tahun_lulus || '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-medium text-gray-800">{{ item.location_label || 'Belum diketahui' }}</p>
                                        <p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ item.alamat || '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-sm text-gray-700">
                                        {{ item.location_source || '-' }}
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <span
                                            v-if="item.has_coordinates"
                                            class="inline-flex rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-700"
                                        >
                                            {{ Number(item.lat).toFixed(4) }}, {{ Number(item.lng).toFixed(4) }}
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
                                        >
                                            Koordinat belum tersedia
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top text-xs text-gray-500">
                                        {{ formatDateTime(item.updated_at) }}
                                    </td>
                                </tr>

                                <tr v-if="(alumni.data || []).length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Tidak ada data alumni yang cocok dengan filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="(alumni.links || []).length > 3"
                        class="flex flex-wrap items-center gap-2 border-t border-gray-100 p-4"
                    >
                        <Link
                            v-for="(link, index) in alumni.links"
                            :key="index"
                            :href="link.url || ''"
                            class="rounded-md px-3 py-1.5 text-sm"
                            :class="{
                                'bg-cyan-600 text-white': link.active,
                                'text-gray-400': !link.url,
                                'border border-gray-300 text-gray-700 hover:bg-gray-50': link.url && !link.active,
                            }"
                            v-html="link.label"
                        />
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:deep(.leaflet-container) {
    font: inherit;
}

:deep(.leaflet-popup-content) {
    margin: 10px 12px;
    font-size: 12px;
    line-height: 1.4;
}

:deep(.gmap-pin-wrapper) {
    background: transparent;
    border: 0;
}

:deep(.gmap-pin-shell) {
    position: relative;
    width: 28px;
    height: 42px;
    animation: gmap-pin-drop 360ms cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: var(--pin-delay, 0ms);
}

:deep(.gmap-pin) {
    position: relative;
    width: 28px;
    height: 28px;
    transform: rotate(-45deg) scale(var(--pin-scale, 1));
    transform-origin: 50% 100%;
    border-radius: 50% 50% 50% 0;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: 2px solid #ffffff;
    box-shadow: 0 8px 14px rgba(15, 23, 42, 0.25);
}

:deep(.gmap-pin-shell.is-high .gmap-pin) {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

:deep(.gmap-pin-shell.is-medium .gmap-pin) {
    background: linear-gradient(135deg, #f97316, #ea580c);
}

:deep(.gmap-pin-shell.is-low .gmap-pin) {
    background: linear-gradient(135deg, #06b6d4, #0e7490);
}

:deep(.gmap-pin-shadow) {
    position: absolute;
    left: 5px;
    bottom: 1px;
    width: 18px;
    height: 6px;
    border-radius: 9999px;
    background: rgba(2, 6, 23, 0.22);
    filter: blur(1px);
    transform: translateY(3px) scale(0.9);
}

:deep(.gmap-pin::after) {
    content: '';
    position: absolute;
    top: 7px;
    left: 7px;
    width: 10px;
    height: 10px;
    border-radius: 9999px;
    background: #ffffff;
}

:deep(.gmap-pin-badge) {
    position: absolute;
    top: -6px;
    left: 50%;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9999px;
    transform: translateX(-50%);
    background: #ffffff;
    color: #b91c1c;
    border: 1px solid #fecaca;
    font-size: 10px;
    font-weight: 700;
    line-height: 16px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(30, 41, 59, 0.18);
}

:deep(.gmap-pin-shell.is-high .gmap-pin-badge) {
    color: #b91c1c;
    border-color: #fecaca;
}

:deep(.gmap-pin-shell.is-medium .gmap-pin-badge) {
    color: #c2410c;
    border-color: #fed7aa;
}

:deep(.gmap-pin-shell.is-low .gmap-pin-badge) {
    color: #0e7490;
    border-color: #a5f3fc;
}

:deep(.gmap-cluster-wrapper) {
    background: transparent;
    border: 0;
}

:deep(.gmap-cluster) {
    width: 44px;
    height: 44px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    border: 3px solid rgba(255, 255, 255, 0.95);
    box-shadow: 0 8px 18px rgba(2, 6, 23, 0.32);
    background: radial-gradient(circle at 30% 30%, #38bdf8, #0e7490 70%);
}

:deep(.gmap-cluster.is-medium) {
    width: 48px;
    height: 48px;
    font-size: 13px;
    background: radial-gradient(circle at 30% 30%, #fb923c, #c2410c 70%);
}

:deep(.gmap-cluster.is-large) {
    width: 54px;
    height: 54px;
    font-size: 14px;
    background: radial-gradient(circle at 30% 30%, #f87171, #b91c1c 70%);
}

.map-legend-pin {
    width: 10px;
    height: 10px;
    border-radius: 9999px;
    display: inline-block;
}

.map-legend-pin-high {
    background: #ef4444;
}

.map-legend-pin-medium {
    background: #f97316;
}

.map-legend-pin-low {
    background: #06b6d4;
}

@keyframes gmap-pin-drop {
    from {
        opacity: 0;
        transform: translateY(-16px) scale(0.92);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.leaflet-host:fullscreen {
    background: #f8fafc;
    padding: 0.75rem;
}

.leaflet-host:fullscreen > div {
    height: calc(100vh - 1.5rem);
    border-radius: 0.75rem;
}

.leaflet-host:-webkit-full-screen {
    background: #f8fafc;
    padding: 0.75rem;
}

.leaflet-host:-webkit-full-screen > div {
    height: calc(100vh - 1.5rem);
    border-radius: 0.75rem;
}
</style>
