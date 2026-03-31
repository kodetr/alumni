<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    newsPost: {
        type: Object,
        required: true,
    },
});

const toDateTimeLocal = (value) => {
    if (!value) {
        return '';
    }

    return value.slice(0, 16);
};

const form = useForm({
    _method: 'put',
    title: props.newsPost.title ?? '',
    excerpt: props.newsPost.excerpt ?? '',
    content: props.newsPost.content ?? '',
    cover_image: null,
    remove_image: false,
    published_at: toDateTimeLocal(props.newsPost.published_at),
    is_published: props.newsPost.is_published ?? true,
});

const coverPreviewUrl = ref(null);

const coverImageUrl = computed(() => coverPreviewUrl.value || props.newsPost.cover_image_url || null);

const updateCoverImage = (event) => {
    const file = event.target.files?.[0] ?? null;

    if (coverPreviewUrl.value) {
        URL.revokeObjectURL(coverPreviewUrl.value);
    }

    form.cover_image = file;
    coverPreviewUrl.value = file ? URL.createObjectURL(file) : null;

    if (file) {
        form.remove_image = false;
    }
};

onBeforeUnmount(() => {
    if (coverPreviewUrl.value) {
        URL.revokeObjectURL(coverPreviewUrl.value);
    }
});

const submit = async () => {
    const result = await Swal.fire({
        icon: 'question',
        title: 'Perbarui berita?',
        text: 'Perubahan berita akan disimpan.',
        showCancelButton: true,
        confirmButtonText: 'Ya, simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
    });

    if (!result.isConfirmed) {
        return;
    }

    form.post(route('berita.update', props.newsPost.id), {
        forceFormData: true,
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal menyimpan',
                text: 'Periksa kembali data berita yang diisi.',
                confirmButtonColor: '#4f46e5',
            });
        },
    });
};
</script>

<template>
    <Head title="Edit Berita Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Berita Alumni
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
                                placeholder="Perbarui isi berita alumni..."
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

                        <div v-if="coverImageUrl" class="mt-3">
                            <img
                                :src="coverImageUrl"
                                alt="Preview cover berita"
                                class="h-44 w-full rounded-lg border border-gray-200 object-cover md:w-80"
                            />
                        </div>

                        <div v-if="props.newsPost.cover_image_url" class="mt-3 flex items-center gap-2">
                            <Checkbox id="remove_image" v-model:checked="form.remove_image" />
                            <InputLabel for="remove_image" value="Hapus gambar lama saat simpan" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.remove_image" />
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
                            Perbarui
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
