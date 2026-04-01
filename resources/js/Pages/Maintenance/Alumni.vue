<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    maintenance: {
        type: Object,
        default: () => ({
            enabled: false,
            ends_at: null,
            remaining_seconds: null,
        }),
    },
});

const remainingSeconds = ref(props.maintenance?.remaining_seconds ?? 0);
let timer = null;

const countdownText = computed(() => {
    const total = Number(remainingSeconds.value ?? 0);

    if (!Number.isFinite(total) || total <= 0) {
        return '00:00:00';
    }

    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const seconds = total % 60;
    const pad = (value) => String(value).padStart(2, '0');

    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
});

const endsAtLabel = computed(() => {
    if (!props.maintenance?.ends_at) {
        return '-';
    }

    return new Date(props.maintenance.ends_at).toLocaleString('id-ID');
});

onMounted(() => {
    timer = setInterval(() => {
        if (remainingSeconds.value <= 0) {
            return;
        }

        remainingSeconds.value -= 1;
    }, 1000);
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <Head title="Maintenance Alumni" />

    <div class="relative min-h-screen overflow-hidden bg-slate-950">
        <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-red-500/20 blur-3xl" />
        <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-amber-500/20 blur-3xl" />

        <div class="relative mx-auto flex min-h-screen max-w-4xl items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <section class="w-full rounded-3xl border border-slate-200/70 bg-white p-8 text-center shadow-2xl sm:p-12">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Maintenance Mode</p>
                <h1 class="mt-3 text-3xl font-bold text-slate-900 sm:text-4xl">Aplikasi Alumni Sedang Maintenance</h1>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    Mohon tunggu sampai proses maintenance selesai. Selama periode ini, akun alumni tidak dapat menggunakan aplikasi.
                </p>

                <div class="mx-auto mt-8 max-w-md rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Hitung Mundur</p>
                    <p class="mt-2 text-4xl font-bold tabular-nums text-amber-900">{{ countdownText }}</p>
                    <p class="mt-2 text-xs text-amber-700">Estimasi selesai: {{ endsAtLabel }}</p>
                </div>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Link
                        :href="route('admin.login')"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Login Superadmin
                    </Link>
                    <Link
                        :href="route('home')"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        Kembali ke Beranda
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>
