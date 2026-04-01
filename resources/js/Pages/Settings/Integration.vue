<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    defaults: {
        type: Object,
        default: () => ({
            endpoint: '',
            api_key: '',
        }),
    },
    integrationResult: {
        type: Object,
        default: null,
    },
    integrationError: {
        type: String,
        default: '',
    },
    integrationStatus: {
        type: Number,
        default: null,
    },
    integrationTest: {
        type: Object,
        default: null,
    },
    databaseError: {
        type: String,
        default: '',
    },
    databaseBackups: {
        type: Array,
        default: () => [],
    },
    maintenance: {
        type: Object,
        default: () => ({
            enabled: false,
            ends_at: null,
            remaining_seconds: null,
        }),
    },
});

const form = useForm({
    endpoint: props.defaults.endpoint ?? '',
    api_key: props.defaults.api_key ?? '',
});
const maintenanceForm = useForm({
    enabled: Boolean(props.maintenance?.enabled ?? false),
    duration_minutes: String(props.maintenance?.remaining_seconds
        ? Math.max(1, Math.ceil(props.maintenance.remaining_seconds / 60))
        : 60),
});
const backupForm = useForm({});
const restoreForm = useForm({
    file_name: '',
});
const deleteForm = useForm({
    file_name: '',
});
const importForm = useForm({
    sql_file: null,
});
const alumniPreviewForm = useForm({
    records: [],
});

const showApiKey = ref(false);
const activeAction = ref('');
const databaseAction = ref('');
const previewOnlyWithPhoto = ref(false);
const photoFitMode = ref('cover');
const fetchProgress = ref(0);
const detailModalOpen = ref(false);
const selectedDetailItem = ref(null);
const maintenanceRemainingSeconds = ref(props.maintenance?.remaining_seconds ?? null);
let fetchProgressTimer = null;
let maintenanceTimer = null;

const openDetailModal = (item) => {
    selectedDetailItem.value = item;
    detailModalOpen.value = true;
};

const fireSuccessAlert = (title, text = '') =>
    Swal.fire({
        icon: 'success',
        title,
        text,
        confirmButtonColor: '#4f46e5',
    });

const fireErrorAlert = (title, text = '') =>
    Swal.fire({
        icon: 'error',
        title,
        text,
        confirmButtonColor: '#dc2626',
    });

const escapeHtml = (value) =>
    String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

const stringifyApiResult = (result) => {
    if (!result) {
        return '';
    }

    return JSON.stringify(result.data ?? result, null, 2);
};

const openApiResultDialog = (result = props.integrationResult) => {
    if (!result) {
        return;
    }

    const prettyJson = stringifyApiResult(result);

    Swal.fire({
        title: 'Hasil Respons API',
        width: 'min(1100px, 95vw)',
        html: `<pre style="margin:0; max-height:60vh; overflow:auto; border-radius:10px; background:#0f172a; color:#e2e8f0; padding:14px; text-align:left; font-size:12px; line-height:1.5;">${escapeHtml(prettyJson)}</pre>`,
        showCloseButton: true,
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#4f46e5',
    });
};

const hasPreviewRows = computed(() => alumniPreviewForm.records.length > 0);
const maintenanceCountdown = computed(() => {
    if (maintenanceRemainingSeconds.value === null || maintenanceRemainingSeconds.value < 0) {
        return '-';
    }

    const total = maintenanceRemainingSeconds.value;
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const seconds = total % 60;
    const pad = (value) => String(value).padStart(2, '0');

    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
});
const visiblePreviewRows = computed(() => {
    if (!previewOnlyWithPhoto.value) {
        return alumniPreviewForm.records;
    }

    return alumniPreviewForm.records.filter((item) => item.has_api_photo);
});

const cleanString = (value) => {
    if (typeof value !== 'string') {
        return '';
    }

    return value.trim();
};

const normalizeGender = (value) => {
    const normalized = cleanString(value).toLowerCase();

    if (['l', 'lk', 'laki-laki', 'laki laki', 'male', 'm', 'pria'].includes(normalized)) {
        return 'L';
    }

    if (['p', 'pr', 'perempuan', 'female', 'f', 'wanita'].includes(normalized)) {
        return 'P';
    }

    return null;
};

const genderLabel = (value) => {
    const normalized = normalizeGender(value);

    if (normalized === 'L') {
        return 'Laki-laki';
    }

    if (normalized === 'P') {
        return 'Perempuan';
    }

    return '-';
};

const toAbsolutePhotoUrl = (path) => {
    const cleanedPath = cleanString(path);

    if (!cleanedPath) {
        return null;
    }

    if (/^https?:\/\//i.test(cleanedPath)) {
        return cleanedPath;
    }

    const endpointSource =
        cleanString(props.integrationResult?.endpoint) ||
        cleanString(form.endpoint) ||
        'http://127.0.0.1:8001';

    try {
        const endpointUrl = new URL(endpointSource);
        const normalizedPath = cleanedPath.startsWith('/') ? cleanedPath : `/${cleanedPath}`;

        return `${endpointUrl.origin}${normalizedPath}`;
    } catch {
        return cleanedPath;
    }
};

const previewFallbackPhoto = (name) => {
    const source = cleanString(name);
    const chunks = source.split(/\s+/).filter(Boolean);
    const initials = (chunks[0]?.[0] ?? 'A') + (chunks[1]?.[0] ?? 'L');
    const safeInitials = initials.toUpperCase();
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="120" viewBox="0 0 80 120"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#0f172a"/><stop offset="100%" stop-color="#0369a1"/></linearGradient></defs><rect width="80" height="120" rx="10" fill="url(#g)"/><text x="40" y="68" text-anchor="middle" font-size="24" font-family="Arial, sans-serif" font-weight="700" fill="#e0f2fe">${safeInitials}</text></svg>`;

    return `data:image/svg+xml;base64,${btoa(svg)}`;
};

const parseGraduationYear = (row) => {
    if (row?.tahun_lulus) {
        return Number(row.tahun_lulus);
    }

    const candidateTexts = [
        row?.source_ceremony_name,
        row?.source_session_name,
    ];

    for (const text of candidateTexts) {
        const matched = cleanString(text).match(/(?:19|20)\d{2}/);

        if (matched) {
            return Number(matched[0]);
        }
    }

    return null;
};

const extractPreviewRows = (result) => {
    const payload = result?.data;

    if (!payload) {
        return [];
    }

    let rawRows = [];

    if (Array.isArray(payload?.pagination?.data)) {
        rawRows = payload.pagination.data;
    } else if (Array.isArray(payload?.data)) {
        rawRows = payload.data;
    } else if (Array.isArray(payload)) {
        rawRows = payload;
    }

    return rawRows
        .map((item) => {
            const nim = cleanString(item?.nim);
            const nama = cleanString(item?.full_name || item?.name);
            const jurisdiction = cleanString(item?.study_program_name || item?.faculty_name || '-');

            if (!nim || !nama) {
                return null;
            }

            return {
                nim,
                nama,
                jurusan: jurisdiction,
                has_api_photo: Boolean(cleanString(item?.photo_path) || cleanString(item?.photo_3x4_path)),
                photo_url:
                    toAbsolutePhotoUrl(item?.photo_path) ||
                    toAbsolutePhotoUrl(item?.photo_3x4_path) ||
                    previewFallbackPhoto(nama),
                email_kampus: cleanString(item?.campus_email) || null,
                email_pribadi: cleanString(item?.personal_email) || null,
                no_telepon: cleanString(item?.phone_number) || null,
                tahun_lulus: parseGraduationYear(item),
                pekerjaan:
                    item?.is_employed === true
                        ? 'Bekerja'
                        : item?.is_employed === false
                          ? 'Belum Bekerja'
                          : null,
                organisasi: cleanString(item?.study_program_name) || null,
                fakultas: cleanString(item?.faculty_name) || null,
                alamat: cleanString(item?.full_address) || null,

                tempat_lahir: cleanString(item?.birth_place) || null,
                tanggal_lahir: item?.birth_date || null,
                agama: cleanString(item?.religion) || null,
                jenis_kelamin: normalizeGender(item?.jenis_kelamin ?? item?.gender),
                no_ktp: cleanString(item?.ktp_number) || null,
                ipk: item?.ipk || null,
                predikat: cleanString(item?.predicate) || null,
                judul_skripsi: cleanString(item?.thesis_title) || null,
                pembimbing_1: cleanString(item?.supervisor_1) || null,
                pembimbing_2: cleanString(item?.supervisor_2) || null,
                ukuran_toga: cleanString(item?.gown_size) || null,
                status_bekerja: item?.is_employed !== undefined ? item.is_employed : null,
                nama_ayah: cleanString(item?.father_name) || null,
                nama_ibu: cleanString(item?.mother_name) || null,
                no_telepon_orang_tua: cleanString(item?.parent_phone) || null,
                link_dokumen_tambahan: item?.additional_document_link || null,

                integration_payload: item,
            };
        })
        .filter((item) => item !== null);
};

watch(
    () => props.integrationResult,
    (result) => {
        if (!result) {
            return;
        }

        const rows = extractPreviewRows(result);
        alumniPreviewForm.records = rows;

        alumniPreviewForm.clearErrors();
    },
    { immediate: true },
);

const startFetchProgress = () => {
    if (fetchProgressTimer) {
        clearInterval(fetchProgressTimer);
    }

    fetchProgress.value = 8;

    fetchProgressTimer = setInterval(() => {
        if (fetchProgress.value >= 92) {
            return;
        }

        fetchProgress.value += Math.max(1, Math.round((92 - fetchProgress.value) / 8));
    }, 220);
};

const stopFetchProgress = () => {
    if (fetchProgressTimer) {
        clearInterval(fetchProgressTimer);
        fetchProgressTimer = null;
    }

    fetchProgress.value = 100;

    setTimeout(() => {
        fetchProgress.value = 0;
    }, 350);
};

const submit = () => {
    activeAction.value = 'fetch';
    startFetchProgress();

    form.post(route('settings.integration.fetch'), {
        onSuccess: (page) => {
            if (page.props.integrationError) {
                fireErrorAlert('Gagal ambil data API', page.props.integrationError);

                return;
            }

            fireSuccessAlert('Ambil data berhasil', page.props.flash?.success ?? 'Data alumni berhasil diambil dari API.')
                .then(() => {
                    openApiResultDialog(page.props.integrationResult);
                });
        },
        onError: () => {
            fireErrorAlert('Validasi gagal', 'Periksa endpoint dan API key lalu coba lagi.');
        },
        onFinish: () => {
            stopFetchProgress();
            activeAction.value = '';
        },
    });
};

const savePreviewToAlumni = async () => {
    if (!hasPreviewRows.value) {
        fireErrorAlert('Data preview kosong', 'Ambil data API terlebih dahulu sebelum menyimpan ke tabel alumni.');

        return;
    }

    const confirmation = await Swal.fire({
        icon: 'question',
        title: 'Simpan ke tabel alumni?',
        text: `${alumniPreviewForm.records.length} data preview akan disimpan ke data alumni.`,
        showCancelButton: true,
        confirmButtonText: 'Ya, simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    activeAction.value = 'store-alumni';

    alumniPreviewForm
        .transform(() => ({}))
        .post(route('settings.integration.store-alumni'), {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.integrationError) {
                    fireErrorAlert('Gagal simpan data alumni', page.props.integrationError);

                    return;
                }

                fireSuccessAlert('Data alumni tersimpan', page.props.flash?.success ?? 'Data alumni berhasil disimpan.');
            },
            onError: () => {
                fireErrorAlert('Validasi gagal', 'Periksa data preview dan coba lagi.');
            },
            onFinish: () => {
                alumniPreviewForm.transform((data) => data);
                activeAction.value = '';
            },
        });
};

const saveConfig = () => {
    activeAction.value = 'save';

    form.post(route('settings.integration.save'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.integrationError) {
                fireErrorAlert('Gagal simpan pengaturan', page.props.integrationError);

                return;
            }

            fireSuccessAlert('Pengaturan tersimpan', page.props.flash?.success ?? 'Endpoint API dan API key berhasil disimpan.');
        },
        onError: () => {
            fireErrorAlert('Validasi gagal', 'Periksa endpoint dan API key lalu coba lagi.');
        },
        onFinish: () => {
            activeAction.value = '';
        },
    });
};

const testConnection = () => {
    activeAction.value = 'test';

    form.post(route('settings.integration.test'), {
        onSuccess: (page) => {
            const testResult = page.props.integrationTest;

            if (testResult?.ok) {
                fireSuccessAlert('Koneksi berhasil', testResult.message ?? 'Koneksi API berhasil.');

                return;
            }

            fireErrorAlert('Koneksi gagal', testResult?.message ?? 'Tidak dapat terhubung ke endpoint API.');
        },
        onError: () => {
            fireErrorAlert('Validasi gagal', 'Periksa endpoint dan API key lalu coba lagi.');
        },
        onFinish: () => {
            activeAction.value = '';
        },
    });
};

const backupDatabase = () => {
    databaseAction.value = 'backup';

    backupForm.post(route('settings.database.backup'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.databaseError) {
                fireErrorAlert('Backup gagal', page.props.databaseError);

                return;
            }

            fireSuccessAlert('Backup berhasil', page.props.flash?.success ?? 'Backup SQL berhasil dibuat.');
        },
        onFinish: () => {
            databaseAction.value = '';
        },
    });
};

const restoreDatabase = (fileName) => {
    databaseAction.value = `restore-${fileName}`;
    restoreForm.file_name = fileName;

    restoreForm.post(route('settings.database.restore'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.databaseError) {
                fireErrorAlert('Restore gagal', page.props.databaseError);

                return;
            }

            fireSuccessAlert('Restore berhasil', page.props.flash?.success ?? 'Restore database berhasil.');
        },
        onFinish: () => {
            databaseAction.value = '';
        },
    });
};

const deleteBackup = async (fileName) => {
    const confirmation = await Swal.fire({
        icon: 'warning',
        title: 'Hapus file backup?',
        text: `File ${fileName} akan dihapus permanen.`,
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    databaseAction.value = `delete-${fileName}`;
    deleteForm.file_name = fileName;

    deleteForm.delete(route('settings.database.delete'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.databaseError) {
                fireErrorAlert('Hapus gagal', page.props.databaseError);

                return;
            }

            fireSuccessAlert('File terhapus', page.props.flash?.success ?? 'Backup SQL berhasil dihapus.');
        },
        onFinish: () => {
            databaseAction.value = '';
        },
    });
};

const onSqlFileChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    importForm.sql_file = file;
};

const importDatabase = () => {
    if (!importForm.sql_file) {
        return;
    }

    databaseAction.value = 'import';

    importForm.post(route('settings.database.import'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.databaseError) {
                fireErrorAlert('Import gagal', page.props.databaseError);

                return;
            }

            fireSuccessAlert('Import berhasil', page.props.flash?.success ?? 'Import SQL berhasil diproses.');
        },
        onError: () => {
            fireErrorAlert('Validasi gagal', 'Pastikan file SQL valid dan ukuran tidak melebihi batas.');
        },
        onFinish: () => {
            databaseAction.value = '';
        },
    });
};

const updateMaintenance = async () => {
    const isEnableAction = maintenanceForm.enabled;
    const duration = Number(maintenanceForm.duration_minutes || 0);

    if (isEnableAction && (!Number.isInteger(duration) || duration < 1)) {
        fireErrorAlert('Durasi tidak valid', 'Durasi maintenance minimal 1 menit.');

        return;
    }

    const confirmation = await Swal.fire({
        icon: 'warning',
        title: isEnableAction ? 'Aktifkan maintenance alumni?' : 'Nonaktifkan maintenance alumni?',
        text: isEnableAction
            ? `Alumni tidak bisa login/menggunakan aplikasi selama ${duration} menit.`
            : 'Akses aplikasi alumni akan kembali normal.',
        showCancelButton: true,
        confirmButtonText: isEnableAction ? 'Ya, aktifkan' : 'Ya, nonaktifkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: isEnableAction ? '#dc2626' : '#16a34a',
        cancelButtonColor: '#6b7280',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    activeAction.value = 'maintenance';

    maintenanceForm
        .transform(() => ({
            enabled: Boolean(maintenanceForm.enabled),
            duration_minutes: Number(maintenanceForm.duration_minutes || 0),
        }))
        .post(route('settings.maintenance.update'), {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.success) {
                    fireSuccessAlert('Maintenance diperbarui', page.props.flash.success);
                }
            },
            onError: () => {
                fireErrorAlert('Gagal memperbarui maintenance', 'Periksa input durasi maintenance.');
            },
            onFinish: () => {
                maintenanceForm.transform((data) => data);
                activeAction.value = '';
            },
        });
};

watch(
    () => props.maintenance,
    (value) => {
        maintenanceForm.enabled = Boolean(value?.enabled ?? false);
        maintenanceRemainingSeconds.value = value?.remaining_seconds ?? null;

        if (value?.enabled) {
            const minutes = value?.remaining_seconds
                ? Math.max(1, Math.ceil(value.remaining_seconds / 60))
                : 60;
            maintenanceForm.duration_minutes = String(minutes);
        }
    },
    { immediate: true, deep: true },
);

watch(
    () => maintenanceForm.enabled,
    (enabled) => {
        if (enabled && maintenanceTimer === null) {
            maintenanceTimer = setInterval(() => {
                if (maintenanceRemainingSeconds.value === null || maintenanceRemainingSeconds.value <= 0) {
                    return;
                }

                maintenanceRemainingSeconds.value -= 1;
            }, 1000);

            return;
        }

        if (!enabled && maintenanceTimer) {
            clearInterval(maintenanceTimer);
            maintenanceTimer = null;
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (fetchProgressTimer) {
        clearInterval(fetchProgressTimer);
    }

    if (maintenanceTimer) {
        clearInterval(maintenanceTimer);
    }
});
</script>

<template>
    <Head title="Pengaturan Integrasi API" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Pengaturan Alumni
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Ambil Data Alumni</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Masukkan endpoint dan API key, lalu jalankan sinkronisasi untuk mengambil data dari API eksternal.
                    </p>

                    <form class="mt-6 space-y-5" @submit.prevent="submit">
                        <div>
                            <label for="endpoint" class="mb-1 block text-sm font-medium text-gray-700">
                                Endpoint API
                            </label>
                            <input
                                id="endpoint"
                                v-model="form.endpoint"
                                type="url"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="http://127.0.0.1:8001/api/integration/alumni/menu-data"
                                required
                            />
                            <p v-if="form.errors.endpoint" class="mt-1 text-sm text-red-600">
                                {{ form.errors.endpoint }}
                            </p>
                        </div>

                        <div>
                            <label for="api_key" class="mb-1 block text-sm font-medium text-gray-700">
                                API Key
                            </label>
                            <div class="flex gap-2">
                                <input
                                    id="api_key"
                                    v-model="form.api_key"
                                    :type="showApiKey ? 'text' : 'password'"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Masukkan API key"
                                    required
                                />
                                <button
                                    type="button"
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50"
                                    @click="showApiKey = !showApiKey"
                                >
                                    {{ showApiKey ? 'Sembunyikan' : 'Tampilkan' }}
                                </button>
                            </div>
                            <p v-if="form.errors.api_key" class="mt-1 text-sm text-red-600">
                                {{ form.errors.api_key }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-600 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="form.processing"
                                @click="saveConfig"
                            >
                                {{ activeAction === 'save' ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-indigo-200 bg-white px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="form.processing"
                                @click="testConnection"
                            >
                                {{ activeAction === 'test' ? 'Mengecek koneksi...' : 'Test Koneksi' }}
                            </button>
                            <button
                                type="submit"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                {{ activeAction === 'fetch' ? 'Mengambil data...' : 'Ambil Data API' }}
                            </button>
                        </div>

                        <div
                            v-if="activeAction === 'fetch' && form.processing"
                            class="rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3"
                        >
                            <div class="mb-2 flex items-center justify-between text-sm font-medium text-indigo-700">
                                <span>Sedang mengambil data API...</span>
                                <span>{{ fetchProgress }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-indigo-100">
                                <div
                                    class="h-full rounded-full bg-indigo-600 transition-all duration-200"
                                    :style="{ width: `${fetchProgress}%` }"
                                />
                            </div>
                            <p class="mt-2 text-xs text-indigo-600">
                                Mohon tunggu, proses sinkronisasi data sedang berjalan.
                            </p>
                        </div>
                    </form>

                    <div
                        v-if="integrationTest"
                        class="mt-6 rounded-md border px-4 py-3 text-sm"
                        :class="integrationTest.ok ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
                    >
                        <p class="font-semibold">
                            {{ integrationTest.ok ? 'Test koneksi berhasil' : 'Test koneksi gagal' }}
                        </p>
                        <p class="mt-1">{{ integrationTest.message }}</p>
                        <p class="mt-1 text-xs" v-if="integrationTest.checked_at">
                            Waktu cek: {{ integrationTest.checked_at }}
                            <span v-if="integrationTest.status"> • HTTP {{ integrationTest.status }}</span>
                        </p>
                    </div>

                    <div
                        v-if="integrationError"
                        class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        <p class="font-semibold">Gagal sinkronisasi</p>
                        <p class="mt-1">{{ integrationError }}</p>
                        <p v-if="integrationStatus" class="mt-1 text-xs text-red-600">
                            HTTP Status: {{ integrationStatus }}
                        </p>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Maintenance Mode Alumni</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Saat maintenance aktif, alumni tidak bisa login atau menggunakan dashboard sampai durasi selesai.
                            </p>
                        </div>
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                            :class="maintenanceForm.enabled ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'"
                        >
                            {{ maintenanceForm.enabled ? 'Maintenance Aktif' : 'Normal' }}
                        </span>
                    </div>

                    <form class="mt-5 grid gap-4 md:grid-cols-3" @submit.prevent="updateMaintenance">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Status Maintenance
                            </label>
                            <select
                                v-model="maintenanceForm.enabled"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option :value="false">Nonaktif</option>
                                <option :value="true">Aktif</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Durasi (menit)
                            </label>
                            <input
                                v-model="maintenanceForm.duration_minutes"
                                type="number"
                                min="1"
                                max="10080"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :disabled="!maintenanceForm.enabled"
                            />
                            <p v-if="maintenanceForm.errors.duration_minutes" class="mt-1 text-sm text-red-600">
                                {{ maintenanceForm.errors.duration_minutes }}
                            </p>
                        </div>

                        <div class="flex items-end">
                            <button
                                type="submit"
                                class="w-full rounded-md px-4 py-2 text-sm font-medium text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                                :class="maintenanceForm.enabled ? 'bg-red-600 hover:bg-red-500' : 'bg-emerald-600 hover:bg-emerald-500'"
                                :disabled="maintenanceForm.processing"
                            >
                                {{ activeAction === 'maintenance' ? 'Memproses...' : (maintenanceForm.enabled ? 'Aktifkan Maintenance' : 'Nonaktifkan Maintenance') }}
                            </button>
                        </div>
                    </form>

                    <div
                        v-if="maintenanceForm.enabled"
                        class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                    >
                        <p class="font-semibold">Akses alumni sedang dibatasi.</p>
                        <p class="mt-1">Sisa waktu maintenance: <span class="font-semibold">{{ maintenanceCountdown }}</span></p>
                        <p v-if="props.maintenance?.ends_at" class="mt-1 text-xs text-amber-700">
                            Berakhir pada: {{ new Date(props.maintenance.ends_at).toLocaleString('id-ID') }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="integrationResult"
                    class="rounded-lg bg-white p-6 shadow-sm"
                >
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Hasil Respons API</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Hasil detail respons API ditampilkan dalam dialog.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-600"
                            @click="openApiResultDialog()"
                        >
                            Lihat Dialog Respons
                        </button>
                    </div>

                    <div class="mt-4 text-xs text-gray-500">
                        <p>Endpoint: {{ integrationResult.endpoint }}</p>
                        <p>Waktu Ambil: {{ integrationResult.fetched_at }}</p>
                        <p v-if="integrationStatus">HTTP Status: {{ integrationStatus }}</p>
                    </div>
                </div>

                <div
                    v-if="integrationResult"
                    class="rounded-lg bg-white p-6 shadow-sm"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Preview Data Alumni</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Cek hasil data API terlebih dahulu, lalu simpan ke tabel alumni.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="!hasPreviewRows || alumniPreviewForm.processing"
                            @click="savePreviewToAlumni"
                        >
                            {{ activeAction === 'store-alumni' ? 'Menyimpan data...' : 'Simpan ke Data Alumni' }}
                        </button>
                    </div>

                    <div v-if="hasPreviewRows" class="mt-4 flex flex-col gap-3 rounded-md border border-gray-200 bg-gray-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input
                                v-model="previewOnlyWithPhoto"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            Hanya tampilkan data yang punya foto API
                        </label>

                        <div class="inline-flex items-center gap-2">
                            <span class="text-sm text-gray-600">Tampilan foto:</span>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                                :class="photoFitMode === 'cover' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-100'"
                                @click="photoFitMode = 'cover'"
                            >
                                Cover
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                                :class="photoFitMode === 'contain' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-100'"
                                @click="photoFitMode = 'contain'"
                            >
                                Contain
                            </button>
                        </div>
                    </div>

                    <p v-if="hasPreviewRows" class="mt-3 text-xs text-gray-500">
                        Menampilkan {{ visiblePreviewRows.length }} dari {{ alumniPreviewForm.records.length }} data preview.
                    </p>

                    <div
                        v-if="!hasPreviewRows"
                        class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"
                    >
                        Data API tidak memiliki item alumni yang valid untuk dipreview.
                    </div>

                    <div v-else-if="visiblePreviewRows.length" class="mt-6 overflow-x-auto rounded-md border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Foto</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">NIM</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Program Studi</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Tahun Lulus</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="(item, index) in visiblePreviewRows" :key="`${item.nim}-${index}`">
                                    <td class="px-4 py-3">
                                        <img
                                            :src="item.photo_url"
                                            alt="Foto alumni"
                                            class="h-[120px] w-[80px] rounded-md border border-gray-200 bg-gray-100"
                                            :class="photoFitMode === 'contain' ? 'object-contain p-1' : 'object-cover'"
                                        />
                                        <p class="mt-1 text-[10px] font-medium" :class="item.has_api_photo ? 'text-emerald-600' : 'text-gray-500'">
                                            {{ item.has_api_photo ? 'Foto API' : 'Placeholder' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ item.nim }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ item.nama }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ item.organisasi || '-' }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-700">{{ item.tahun_lulus || '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="rounded-md bg-indigo-100 px-3 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-200"
                                            @click="openDetailModal(item)"
                                        >
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"
                    >
                        Tidak ada data dengan foto API sesuai filter.
                    </div>
                </div>

                <div
                    v-if="detailModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    @click.self="detailModalOpen = false"
                >
                    <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                        <div class="flex items-center justify-between border-b pb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Detail Alumni</h3>
                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-600"
                                @click="detailModalOpen = false"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div v-if="selectedDetailItem" class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2 flex gap-4">
                                <img
                                    v-if="selectedDetailItem.photo_url"
                                    :src="selectedDetailItem.photo_url"
                                    alt="Foto alumni"
                                    class="h-32 w-24 rounded-md border border-gray-200 object-cover"
                                />
                                <div class="flex flex-col justify-center">
                                    <p class="text-sm text-gray-500">NIM</p>
                                    <p class="font-medium text-gray-900">{{ selectedDetailItem.nim }}</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Nama</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.nama }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Program Studi</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.organisasi || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Fakultas</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.fakultas || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Tahun Lulus</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.tahun_lulus || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Email Kampus</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.email_kampus || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Email Pribadi</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.email_pribadi || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Tempat Lahir</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.tempat_lahir || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Tanggal Lahir</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.tanggal_lahir || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Agama</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.agama || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Jenis Kelamin</p>
                                <p class="font-medium text-gray-900">
                                    {{ genderLabel(selectedDetailItem.jenis_kelamin) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">No. KTP</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.no_ktp || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">No. Telepon</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.no_telepon || '-' }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-gray-500">Alamat</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.alamat || '-' }}</p>
                            </div>

                            <div class="sm:col-span-2 border-t pt-4 mt-2">
                                <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Akademik</h4>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">IPK</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.ipk || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Predikat</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.predikat || '-' }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-gray-500">Judul Skripsi/Tesis</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.judul_skripsi || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Pembimbing 1</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.pembimbing_1 || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Pembimbing 2</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.pembimbing_2 || '-' }}</p>
                            </div>

                            <div class="sm:col-span-2 border-t pt-4 mt-2">
                                <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Wisuda</h4>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Ukuran Toga</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.ukuran_toga || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Status Bekerja</p>
                                <p class="font-medium text-gray-900">
                                    {{ selectedDetailItem.status_bekerja === true ? 'Ya' : selectedDetailItem.status_bekerja === false ? 'Tidak' : '-' }}
                                </p>
                            </div>

                            <div class="sm:col-span-2 border-t pt-4 mt-2">
                                <h4 class="text-sm font-semibold text-gray-800 mb-3">Data Orang Tua</h4>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Nama Ayah</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.nama_ayah || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Nama Ibu</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.nama_ibu || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">No. Telepon Orang Tua</p>
                                <p class="font-medium text-gray-900">{{ selectedDetailItem.no_telepon_orang_tua || '-' }}</p>
                            </div>
                            <div v-if="selectedDetailItem.link_dokumen_tambahan" class="sm:col-span-2">
                                <p class="text-xs text-gray-500">Link Dokumen Tambahan</p>
                                <a
                                    :href="selectedDetailItem.link_dokumen_tambahan"
                                    target="_blank"
                                    class="font-medium text-indigo-600 hover:text-indigo-800"
                                >
                                    {{ selectedDetailItem.link_dokumen_tambahan }}
                                </a>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button
                                type="button"
                                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                                @click="detailModalOpen = false"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Backup & Restore Database</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Kelola backup SQL, download file backup, restore dari backup, atau import file SQL manual.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="backupForm.processing || restoreForm.processing || deleteForm.processing || importForm.processing"
                            @click="backupDatabase"
                        >
                            {{ databaseAction === 'backup' ? 'Membuat backup...' : 'Backup Data' }}
                        </button>
                    </div>

                    <div
                        v-if="databaseError"
                        class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        <p class="font-semibold">Gagal proses database</p>
                        <p class="mt-1">{{ databaseError }}</p>
                    </div>

                    <div class="mt-6 overflow-x-auto rounded-md border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama File</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Ukuran</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-if="!databaseBackups.length">
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                        Belum ada file backup. Klik tombol Backup Data untuk membuat backup pertama.
                                    </td>
                                </tr>
                                <tr v-for="item in databaseBackups" :key="item.name">
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ item.name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ item.size }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ item.created_at }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a
                                                :href="item.download_url"
                                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                            >
                                                Download SQL
                                            </a>
                                            <button
                                                type="button"
                                                class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="backupForm.processing || restoreForm.processing || deleteForm.processing || importForm.processing"
                                                @click="restoreDatabase(item.name)"
                                            >
                                                {{ databaseAction === `restore-${item.name}` ? 'Restore...' : 'Restore Data' }}
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-md bg-rose-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-rose-500 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="backupForm.processing || restoreForm.processing || deleteForm.processing || importForm.processing"
                                                @click="deleteBackup(item.name)"
                                            >
                                                {{ databaseAction === `delete-${item.name}` ? 'Menghapus...' : 'Delete' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-8 rounded-md border border-gray-200 bg-gray-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-800">Import Data SQL</h4>
                        <p class="mt-1 text-xs text-gray-600">
                            Upload file .sql untuk melakukan import data ke database aktif.
                        </p>

                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <input
                                type="file"
                                accept=".sql,.txt"
                                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700"
                                @change="onSqlFileChange"
                            />
                            <button
                                type="button"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="!importForm.sql_file || backupForm.processing || restoreForm.processing || deleteForm.processing || importForm.processing"
                                @click="importDatabase"
                            >
                                {{ databaseAction === 'import' ? 'Mengimpor...' : 'Import SQL' }}
                            </button>
                        </div>

                        <p v-if="importForm.errors.sql_file" class="mt-2 text-xs text-red-600">
                            {{ importForm.errors.sql_file }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
