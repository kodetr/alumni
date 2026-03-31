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
    description: '',
    poster_image: null,
    event_date: '',
    location: '',
    registration_url: '',
    is_published: true,
});

const posterPreviewUrl = ref(null);

const updatePosterImage = (event) => {
    const file = event.target.files?.[0] ?? null;

    if (posterPreviewUrl.value) {
        URL.revokeObjectURL(posterPreviewUrl.value);
    }

    form.poster_image = file;
    posterPreviewUrl.value = file ? URL.createObjectURL(file) : null;
};

onBeforeUnmount(() => {
    if (posterPreviewUrl.value) {
        URL.revokeObjectURL(posterPreviewUrl.value);
    }
});

const submit = () => {
    form.post(route('agenda.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Tambah Agenda Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Tambah Agenda Alumni
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel for="title" value="Judul Agenda" />
                        <TextInput id="title" type="text" class="mt-1 block w-full" v-model="form.title" required />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Deskripsi" />
                        <div class="mt-1">
                            <RichTextEditor
                                id="description"
                                v-model="form.description"
                                placeholder="Tulis deskripsi agenda alumni..."
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div>
                        <InputLabel for="poster_image" value="Poster Agenda" />
                        <input
                            id="poster_image"
                            type="file"
                            accept="image/png,image/jpeg,image/jpg,image/webp"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm file:me-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            @change="updatePosterImage"
                        />
                        <InputError class="mt-2" :message="form.errors.poster_image" />

                        <div v-if="posterPreviewUrl" class="mt-3">
                            <img
                                :src="posterPreviewUrl"
                                alt="Preview poster agenda"
                                class="h-44 w-full rounded-lg border border-gray-200 object-cover md:w-80"
                            />
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <InputLabel for="event_date" value="Tanggal Agenda" />
                            <TextInput id="event_date" type="date" class="mt-1 block w-full" v-model="form.event_date" required />
                            <InputError class="mt-2" :message="form.errors.event_date" />
                        </div>

                        <div>
                            <InputLabel for="location" value="Lokasi" />
                            <TextInput id="location" type="text" class="mt-1 block w-full" v-model="form.location" />
                            <InputError class="mt-2" :message="form.errors.location" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="registration_url" value="Link Pendaftaran" />
                            <TextInput
                                id="registration_url"
                                type="url"
                                class="mt-1 block w-full"
                                v-model="form.registration_url"
                                placeholder="https://"
                            />
                            <InputError class="mt-2" :message="form.errors.registration_url" />
                        </div>

                        <div class="md:col-span-2 flex items-center gap-3">
                            <Checkbox id="is_published" v-model:checked="form.is_published" />
                            <InputLabel for="is_published" value="Publikasikan agenda" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <Link
                            :href="route('agenda.index')"
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
