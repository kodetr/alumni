<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    newsPost: {
        type: Object,
        required: true,
    },
    relatedNews: {
        type: Array,
        default: () => [],
    },
});

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(value));
};
</script>

<template>
    <Head :title="newsPost.title" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Universitas Bumigora</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">Portal Alumni</p>
                </div>

                <nav class="flex items-center gap-3">
                    <Link
                        :href="route('home')"
                        class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-cyan-700 hover:text-cyan-700"
                    >
                        Landing Page
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-full bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-600"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else-if="canLogin"
                        :href="route('login')"
                        class="rounded-full bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-600"
                    >
                        Masuk
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-6 py-12 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1.35fr_0.65fr]">
                <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-cyan-700">Berita Alumni</p>
                    <h1 class="font-display mt-4 text-3xl leading-tight text-slate-900 sm:text-4xl">
                        {{ newsPost.title }}
                    </h1>
                    <p class="mt-3 text-sm text-slate-500">Dipublikasikan: {{ formatDateTime(newsPost.published_at) }}</p>

                    <img
                        v-if="newsPost.cover_image_url"
                        :src="newsPost.cover_image_url"
                        alt="Cover berita alumni"
                        class="mt-6 h-64 w-full rounded-xl border border-slate-200 object-cover"
                    />

                    <div class="rich-content mt-6 text-base leading-relaxed text-slate-700" v-html="newsPost.content || newsPost.excerpt || '-'"></div>
                </article>

                <aside class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">Navigasi</h2>
                        <div class="mt-4 space-y-2">
                            <Link :href="route('home')" class="block text-sm font-medium text-cyan-700 hover:text-cyan-600">
                                Kembali ke Landing Page
                            </Link>
                            <a href="#" class="block text-sm font-medium text-slate-500">Bagikan Berita (opsional)</a>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">Berita Terkait</h2>
                        <div v-if="relatedNews.length" class="mt-4 space-y-4">
                            <article
                                v-for="item in relatedNews"
                                :key="item.id"
                                class="rounded-xl border border-slate-100 bg-slate-50 p-4"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-cyan-700">
                                    {{ formatDateTime(item.published_at) }}
                                </p>
                                <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ item.title }}</h3>
                                <img
                                    v-if="item.cover_image_url"
                                    :src="item.cover_image_url"
                                    alt="Gambar berita terkait"
                                    class="mt-2 h-24 w-full rounded-md border border-slate-200 object-cover"
                                />
                                <Link
                                    :href="route('landing.berita.show', item.slug)"
                                    class="mt-3 inline-flex text-sm font-medium text-cyan-700 hover:text-cyan-600"
                                >
                                    Baca detail
                                </Link>
                            </article>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-500">Belum ada berita terkait.</p>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>
