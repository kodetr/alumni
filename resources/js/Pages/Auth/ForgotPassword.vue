<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Lupa Password" />

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
                        <p class="text-sm font-medium uppercase tracking-[0.18em] text-cyan-200">Pemulihan Akun</p>
                        <h2 class="text-3xl font-semibold leading-tight">Reset password dengan proses yang aman.</h2>
                        <p class="text-sm leading-6 text-slate-300">
                            Masukkan email akun superadmin untuk menerima tautan reset password resmi dari sistem.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/5 p-4">
                        <svg class="h-28 w-full" viewBox="0 0 240 160" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi kampus">
                            <path d="M26 68L120 30L214 68L120 106L26 68Z" fill="#67E8F9" fill-opacity="0.3" />
                            <rect x="40" y="78" width="160" height="56" rx="8" fill="#0F172A" stroke="#7DD3FC" stroke-opacity="0.6" />
                            <rect x="58" y="94" width="20" height="24" rx="3" fill="#CFFAFE" />
                            <rect x="92" y="94" width="20" height="24" rx="3" fill="#CFFAFE" />
                            <rect x="126" y="94" width="20" height="24" rx="3" fill="#CFFAFE" />
                            <rect x="160" y="94" width="20" height="24" rx="3" fill="#CFFAFE" />
                        </svg>
                        <p class="mt-3 text-sm text-slate-200">Identitas visual kampus dan portal alumni terintegrasi dalam satu akses.</p>
                    </div>
                </aside>

                <section class="col-span-3 p-6 sm:p-10">
                    <div class="flex items-center justify-between gap-4">
                        <Link href="/admin/login" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900">
                            <span aria-hidden="true">&larr;</span>
                            Kembali ke login
                        </Link>
                        <div class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Superadmin
                        </div>
                    </div>

                    <div class="mt-8 lg:hidden">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="inline-flex items-center gap-2">
                                <ApplicationLogo class="h-8 w-8 fill-current text-slate-700" />
                                <p class="text-sm font-semibold text-slate-700">Portal Alumni Kampus</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Forgot Password</p>
                        <h1 class="mt-2 text-3xl font-bold text-slate-900">Minta tautan reset password</h1>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Kami akan kirimkan tautan reset ke email akun superadmin yang terdaftar.
                        </p>
                    </div>

                    <div v-if="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ status }}
                    </div>

                    <form class="mt-8 space-y-5" @submit.prevent="submit">
                        <div>
                            <InputLabel for="email" value="Email Superadmin" />
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

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            Kirim Tautan Reset
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</template>
