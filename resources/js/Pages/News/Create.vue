<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

const form = useForm({
    title: '',
    excerpt: '',
    content: '',
    cover_image: null,
    published_at: '',
    is_published: true,
});

const coverPreviewUrl = ref(null);

const updateCoverImage = (event) => {
    const file = event.target.files?.[0] ?? null;

    if (coverPreviewUrl.value) {
        URL.revokeObjectURL(coverPreviewUrl.value);
    }

    form.cover_image = file;
    coverPreviewUrl.value = file ? URL.createObjectURL(file) : null;
};

onBeforeUnmount(() => {
    if (coverPreviewUrl.value) {
        URL.revokeObjectURL(coverPreviewUrl.value);
    }
});

const submit = () => {
    form.post(route('berita.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Tambah Berita Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Tambah Berita Alumni
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel for="title" value="Judul Berita" />
                        <TextInput id="title" type="text" class="mt-1 block w-full" v-model="form.title" required />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div>
                        <InputLabel for="excerpt" value="Ringkasan" />
                        <textarea
                            id="excerpt"
                            v-model="form.excerpt"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.excerpt" />
                    </div>

                    <div>
                        <InputLabel for="content" value="Konten Berita" />
                        <div class="mt-1">
                            <RichTextEditor
                                id="content"
                                v-model="form.content"
                                placeholder="Tulis isi berita alumni di sini..."
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.content" />
                    </div>

                    <div>
                        <InputLabel for="cover_image" value="Gambar Cover" />
                        <input
                            id="cover_image"
                            type="file"
                            accept="image/png,image/jpeg,image/jpg,image/webp"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm file:me-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            @change="updateCoverImage"
                        />
                        <InputError class="mt-2" :message="form.errors.cover_image" />

                        <div v-if="coverPreviewUrl" class="mt-3">
                            <img
                                :src="coverPreviewUrl"
                                alt="Preview cover berita"
                                class="h-44 w-full rounded-lg border border-gray-200 object-cover md:w-80"
                            />
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <InputLabel for="published_at" value="Tanggal Publikasi" />
                            <TextInput
                                id="published_at"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                v-model="form.published_at"
                            />
                            <InputError class="mt-2" :message="form.errors.published_at" />
                        </div>

                        <div class="flex items-center gap-3 pt-8">
                            <Checkbox id="is_published" v-model:checked="form.is_published" />
                            <InputLabel for="is_published" value="Publikasikan sekarang" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <Link
                            :href="route('berita.index')"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </Link>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Simpan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
