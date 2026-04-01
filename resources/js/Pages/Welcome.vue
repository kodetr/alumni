<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    stats: {
        type: Object,
        default: () => ({
            totalAlumni: 0,
            jumlahJurusan: 0,
            lulusanTahunIni: 0,
        }),
    },
    alumniSearch: {
        type: String,
        default: '',
    },
    alumniResults: {
        type: Array,
        default: () => [],
    },
    newsPosts: {
        type: Array,
        default: () => [],
    },
    events: {
        type: Array,
        default: () => [],
    },
});

const layanan = [
    {
        title: 'Direktori Alumni Terpusat',
        description: 'Data alumni tersimpan rapi berdasarkan jurusan, tahun lulus, dan riwayat karier.',
    },
    {
        title: 'Tracer Study Berkala',
        description: 'Universitas dapat memantau perkembangan lulusan untuk evaluasi kurikulum dan akreditasi.',
    },
    {
        title: 'Jejaring Profesional',
        description: 'Memudahkan kolaborasi antara alumni, mahasiswa aktif, dan mitra industri.',
    },
    {
        title: 'Informasi Karier',
        description: 'Publikasi peluang kerja, magang, dan kegiatan kampus yang relevan untuk alumni.',
    },
];

const alur = [
    'Alumni mencari data atau login ke portal resmi sesuai hak akses.',
    'Data profil, akademik, dan pekerjaan diperbarui secara mandiri.',
    'Admin memvalidasi, mengelola, dan menyajikan laporan alumni.',
];

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'long',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Portal Alumni Universitas Bumigora" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <div class="relative overflow-hidden bg-grid-soft">
            <div class="hero-glow"></div>

            <header class="relative z-10">
                <div class="flex w-full items-center justify-between px-6 py-6 lg:px-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700">
                            Universitas Bumigora
                        </p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">Portal Alumni</p>
                    </div>

                    <nav v-if="canLogin" class="flex items-center gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="rounded-full bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-600"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-cyan-700 hover:text-cyan-700"
                            >
                                Masuk
                            </Link>

                            <a
                                href="#cari-alumni"
                                class="rounded-full bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-600"
                            >
                                Cari Alumni
                            </a>
                        </template>
                    </nav>
                </div>
            </header>

            <section class="relative z-10 mx-auto w-full max-w-7xl px-6 pb-20 pt-10 lg:px-8 lg:pb-28 lg:pt-16">
                <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="rise-in">
                        <p class="inline-flex rounded-full border border-cyan-200 bg-white/80 px-4 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-800">
                            Sistem Informasi Alumni
                        </p>
                        <h1 class="font-display mt-6 text-4xl leading-tight text-slate-900 sm:text-5xl lg:text-6xl">
                            Platform Alumni Profesional untuk Universitas Bumigora
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">
                            Satu portal resmi untuk mengelola data alumni, memperkuat jejaring profesional,
                            dan menyajikan informasi lulusan secara akurat untuk kebutuhan institusi.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="rounded-full bg-cyan-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-cyan-600"
                            >
                                Kelola Data Sekarang
                            </Link>
                            <Link
                                v-else
                                :href="route('login')"
                                class="rounded-full bg-cyan-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-cyan-600"
                            >
                                Mulai Akses Portal
                            </Link>
                            <a
                                href="#cari-alumni"
                                class="rounded-full border border-cyan-700 px-6 py-3 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-50"
                            >
                                Cari Alumni
                            </a>
                            <a
                                href="#layanan"
                                class="rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-cyan-700 hover:text-cyan-700"
                            >
                                Lihat Layanan
                            </a>
                        </div>
                    </div>

                    <div class="rise-in relative lg:justify-self-end" style="animation-delay: 120ms">
                        <div class="rounded-3xl border border-cyan-100 bg-white/90 p-7 shadow-[0_24px_80px_-30px_rgba(14,116,144,0.45)]">
                            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-cyan-700">
                                Ringkasan Alumni
                            </p>

                            <div class="mt-6 grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Total Alumni</p>
                                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.totalAlumni }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Jurusan Tercatat</p>
                                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.jumlahJurusan }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Lulusan Tahun Ini</p>
                                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.lulusanTahunIni }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section id="cari-alumni" class="mx-auto w-full max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="rise-in rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-cyan-700">Pencarian Alumni</p>
                        <h2 class="font-display mt-2 text-3xl text-slate-900">Cari data alumni</h2>
                        <p class="mt-2 text-sm text-slate-600">Masukkan NIM atau nama untuk menemukan data alumni.</p>
                    </div>
                </div>

                <form class="mt-6 flex flex-col gap-3 sm:flex-row" method="get" action="/">
                    <input
                        type="text"
                        name="q"
                        :value="alumniSearch"
                        placeholder="Contoh: 20260001 atau Alumni Demo"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100"
                    />
                    <button
                        type="submit"
                        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-cyan-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-cyan-600"
                    >
                        Cari Alumni
                    </button>
                </form>

                <div class="mt-8">
                    <p v-if="alumniSearch" class="text-sm font-medium text-slate-600">
                        Hasil pencarian untuk: <span class="font-semibold text-slate-900">{{ alumniSearch }}</span>
                    </p>
                    <p v-else class="text-sm text-slate-500">
                        Masukkan kata kunci untuk menampilkan hasil pencarian alumni.
                    </p>

                    <div v-if="alumniResults.length" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="item in alumniResults"
                            :key="item.id"
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex items-start gap-4">
                                <img
                                    :src="item.photo"
                                    alt="Foto alumni"
                                    class="h-[120px] w-[80px] shrink-0 rounded-xl border border-slate-200 object-cover"
                                />

                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">NIM</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ item.nim }}</p>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">Nama</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ item.nama }}</p>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">Tahun Lulus</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ item.tahun_lulus || '-' }}</p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <p
                        v-else-if="alumniSearch"
                        class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"
                    >
                        Data alumni tidak ditemukan.
                    </p>
                </div>
            </div>
        </section>

        <section id="layanan" class="mx-auto w-full max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="rise-in text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Layanan Utama</p>
                <h2 class="font-display mt-4 text-3xl text-slate-900 sm:text-4xl">
                    Solusi Lengkap Pengelolaan Alumni
                </h2>
                <p class="mx-auto mt-4 max-w-3xl text-base leading-relaxed text-slate-600">
                    Dirancang mengikuti kebutuhan umum aplikasi alumni universitas: validasi data lulusan,
                    pelacakan karier, dan penguatan relasi antaralumni.
                </p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-2">
                <article
                    v-for="item in layanan"
                    :key="item.title"
                    class="rise-in rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                >
                    <div class="mb-4 h-1.5 w-16 rounded-full bg-cyan-600"></div>
                    <h3 class="text-xl font-semibold text-slate-900">{{ item.title }}</h3>
                    <p class="mt-3 leading-relaxed text-slate-600">{{ item.description }}</p>
                </article>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="grid gap-8 lg:grid-cols-2">
                <article class="rise-in rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-display text-2xl text-slate-900">Berita Alumni</h3>
                        <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                            Terbaru
                        </span>
                    </div>

                    <div v-if="newsPosts.length" class="mt-6 space-y-4">
                        <article
                            v-for="item in newsPosts"
                            :key="item.id"
                            class="rounded-xl border border-slate-100 bg-slate-50 p-4"
                        >
                            <img
                                v-if="item.cover_image_url"
                                :src="item.cover_image_url"
                                alt="Gambar berita alumni"
                                class="h-36 w-full rounded-md border border-slate-200 object-cover"
                            />
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                                {{ formatDate(item.published_at) }}
                            </p>
                            <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ item.title }}</h4>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                {{ item.excerpt || 'Informasi terbaru seputar program alumni Universitas Bumigora.' }}
                            </p>
                            <Link
                                :href="route('landing.berita.show', item.slug)"
                                class="mt-3 inline-flex text-sm font-semibold text-cyan-700 hover:text-cyan-600"
                            >
                                Baca selengkapnya
                            </Link>
                        </article>
                    </div>

                    <p v-else class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                        Belum ada berita alumni yang dipublikasikan.
                    </p>
                </article>

                <article class="rise-in rounded-2xl border border-slate-200 bg-white p-7 shadow-sm" style="animation-delay: 120ms">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-display text-2xl text-slate-900">Agenda Alumni</h3>
                        <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                            Mendatang
                        </span>
                    </div>

                    <div v-if="events.length" class="mt-6 space-y-4">
                        <article
                            v-for="item in events"
                            :key="item.id"
                            class="rounded-xl border border-slate-100 bg-slate-50 p-4"
                        >
                            <img
                                v-if="item.poster_image_url"
                                :src="item.poster_image_url"
                                alt="Poster agenda alumni"
                                class="h-36 w-full rounded-md border border-slate-200 object-cover"
                            />
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                                    {{ formatDate(item.event_date) }}
                                </p>
                                <p class="text-xs font-medium text-slate-500">{{ item.location || 'Lokasi menyusul' }}</p>
                            </div>
                            <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ item.title }}</h4>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                {{ item.description || 'Agenda resmi alumni Universitas Bumigora.' }}
                            </p>
                            <Link
                                :href="route('landing.agenda.show', item.slug)"
                                class="mt-3 inline-flex text-sm font-semibold text-cyan-700 hover:text-cyan-600"
                            >
                                Lihat detail agenda
                            </Link>
                        </article>
                    </div>

                    <p v-else class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                        Belum ada agenda alumni yang dijadwalkan.
                    </p>
                </article>
            </div>
        </section>

        <section class="bg-slate-900 py-16 text-slate-100 lg:py-20">
            <div class="mx-auto grid w-full max-w-7xl gap-10 px-6 lg:grid-cols-[1fr_1fr] lg:items-center lg:px-8">
                <div class="rise-in">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-300">Alur Penggunaan</p>
                    <h2 class="font-display mt-4 text-3xl leading-tight sm:text-4xl">
                        Alur sederhana, hasil data lebih terpercaya
                    </h2>
                    <p class="mt-4 text-slate-300">
                        Setiap proses dibuat jelas agar alumni nyaman mengisi data dan admin mudah memantau
                        perkembangan lulusan.
                    </p>
                </div>

                <ol class="space-y-4">
                    <li
                        v-for="(step, index) in alur"
                        :key="step"
                        class="rise-in flex items-start gap-4 rounded-2xl border border-white/15 bg-white/5 p-5"
                        :style="{ animationDelay: `${index * 110}ms` }"
                    >
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-500 text-xs font-bold text-slate-900">
                            {{ index + 1 }}
                        </span>
                        <p class="leading-relaxed text-slate-100">{{ step }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-6 py-16 lg:px-8">
            <div class="rise-in rounded-3xl bg-gradient-to-r from-cyan-700 to-sky-700 p-8 text-white shadow-[0_25px_60px_-25px_rgba(3,105,161,0.65)] sm:p-10">
                <h2 class="font-display text-3xl leading-tight sm:text-4xl">
                    Bangun ekosistem alumni yang aktif dan terukur
                </h2>
                <p class="mt-4 max-w-3xl text-cyan-50">
                    Portal Alumni Universitas Bumigora membantu kampus mempertahankan koneksi jangka panjang
                    dengan lulusan sekaligus mendukung data strategis institusi.
                </p>

                <div class="mt-7 flex flex-wrap gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-cyan-800 transition hover:bg-cyan-50"
                    >
                        Buka Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-cyan-800 transition hover:bg-cyan-50"
                    >
                        Masuk ke Portal
                    </Link>
                    <a
                        href="#cari-alumni"
                        class="rounded-full border border-white/60 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
                    >
                        Cari Alumni
                    </a>
                </div>
            </div>
        </section>

        <footer class="border-t border-slate-200 py-8">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <p>Portal Alumni Universitas Bumigora</p>
                <p>Alamat kampus dan kontak resmi dapat ditambahkan sesuai kebutuhan institusi.</p>
            </div>
        </footer>
    </div>
</template>
