<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    defaultMode: {
        type: String,
        default: 'alumni',
    },
});

const isSuperadminMode = computed(() => props.defaultMode === 'superadmin');

const pageTitle = computed(() =>
    isSuperadminMode.value ? 'Login Superadmin' : 'Login Alumni',
);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const alumniForm = useForm({
    nim: '',
    tanggal_lahir: '',
    remember: false,
});

const submitSuperadmin = () => {
    form.post(route('admin.login.store'), {
        onFinish: () => form.reset('password'),
    });
};

const submitAlumni = () => {
    alumniForm.post(route('login'));
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="relative min-h-screen overflow-hidden bg-slate-950">
        <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl" />
        <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-indigo-500/20 blur-3xl" />
        <div class="relative mx-auto flex min-h-screen max-w-6xl items-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid w-full overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-2xl lg:grid-cols-5">
                <aside class="hidden bg-gradient-to-b from-slate-900 to-slate-800 p-10 text-white lg:col-span-2 lg:flex lg:flex-col lg:justify-between">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <ApplicationLogo class="h-10 w-10 fill-current text-cyan-300" />
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Portal</p>
                            <p class="text-base font-semibold">Alumni Kampus</p>
                        </div>
                    </Link>

                    <div class="space-y-5">
                        <p class="text-sm font-medium uppercase tracking-[0.18em] text-cyan-200">
                            {{ isSuperadminMode ? 'Akses Terbatas' : 'Akses Alumni' }}
                        </p>
                        <h2 class="text-3xl font-semibold leading-tight">
                            {{ isSuperadminMode ? 'Kelola sistem dengan kontrol penuh.' : 'Lihat layanan alumni secara cepat dan aman.' }}
                        </h2>
                        <p class="text-sm leading-6 text-slate-300">
                            {{ isSuperadminMode ? 'Halaman ini khusus untuk superadmin. Semua aktivitas tercatat untuk menjaga keamanan sistem.' : 'Gunakan NIM dan tanggal lahir untuk masuk ke dashboard alumni tanpa perlu email dan password.' }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/5 px-5 py-4 text-sm text-slate-200">
                        {{ isSuperadminMode ? 'Endpoint superadmin: /admin/login' : 'Endpoint alumni: /login' }}
                    </div>
                </aside>

                <section class="col-span-3 p-6 sm:p-10">
                    <div class="flex items-center justify-between gap-4">
                        <Link href="/" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900">
                            <span aria-hidden="true">&larr;</span>
                            Kembali ke beranda
                        </Link>
                        <Link
                            v-if="isSuperadminMode"
                            :href="route('login')"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                        >
                            Login Alumni
                        </Link>
                    </div>

                    <div class="mt-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">{{ isSuperadminMode ? 'Portal Superadmin' : 'Portal Alumni' }}</p>
                        <h1 class="mt-2 text-3xl font-bold text-slate-900">
                            {{ isSuperadminMode ? 'Masuk sebagai Superadmin' : 'Masuk sebagai Alumni' }}
                        </h1>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{ isSuperadminMode ? 'Gunakan akun superadmin untuk mengelola konten, data alumni, dan agenda.' : 'Masukkan NIM dan tanggal lahir sesuai data resmi alumni.' }}
                        </p>
                    </div>

                    <div v-if="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ status }}
                    </div>

                    <form v-if="!isSuperadminMode" class="mt-8 space-y-5" @submit.prevent="submitAlumni">
                        <div>
                            <InputLabel for="nim" value="NIM" />
                            <TextInput
                                id="nim"
                                type="text"
                                class="mt-2 block w-full rounded-xl border-slate-300 px-3 py-2.5"
                                v-model="alumniForm.nim"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Contoh: 20260001"
                            />
                            <InputError class="mt-2" :message="alumniForm.errors.nim" />
                        </div>

                        <div>
                            <InputLabel for="tanggal_lahir" value="Tanggal Lahir" />
                            <TextInput
                                id="tanggal_lahir"
                                type="date"
                                class="mt-2 block w-full rounded-xl border-slate-300 px-3 py-2.5"
                                v-model="alumniForm.tanggal_lahir"
                                required
                            />
                            <InputError class="mt-2" :message="alumniForm.errors.tanggal_lahir" />
                        </div>

                        <label class="flex items-center text-sm text-slate-600">
                            <Checkbox name="remember_alumni" v-model:checked="alumniForm.remember" />
                            <span class="ms-2">Ingat saya di perangkat ini</span>
                        </label>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="alumniForm.processing"
                        >
                            Masuk ke Akun Alumni
                        </button>
                    </form>

                    <form v-else class="mt-8 space-y-5" @submit.prevent="submitSuperadmin">
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-2 block w-full rounded-xl border-slate-300 px-3 py-2.5"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@kampus.ac.id"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Password" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-2 block w-full rounded-xl border-slate-300 px-3 py-2.5"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center text-sm text-slate-600">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span class="ms-2">Ingat saya</span>
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-slate-600 underline-offset-2 hover:text-slate-900 hover:underline"
                            >
                                Lupa password?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            Masuk ke Panel Superadmin
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</template>
