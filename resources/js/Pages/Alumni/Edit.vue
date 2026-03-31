<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    alumni: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    nama: props.alumni.nama ?? '',
    nim: props.alumni.nim ?? '',
    email: props.alumni.email ?? '',
    no_telepon: props.alumni.no_telepon ?? '',
    jurusan: props.alumni.jurusan ?? '',
    angkatan: props.alumni.angkatan?.toString() ?? '',
    tahun_lulus: props.alumni.tahun_lulus?.toString() ?? '',
    pekerjaan: props.alumni.pekerjaan ?? '',
    instansi: props.alumni.instansi ?? '',
    alamat: props.alumni.alamat ?? '',
});

const submit = () => {
    form.put(route('alumni.update', props.alumni.id));
};
</script>

<template>
    <Head title="Edit Alumni" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Alumni
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <InputLabel for="nama" value="Nama Lengkap" />
                            <TextInput id="nama" type="text" class="mt-1 block w-full" v-model="form.nama" required />
                            <InputError class="mt-2" :message="form.errors.nama" />
                        </div>

                        <div>
                            <InputLabel for="nim" value="NIM" />
                            <TextInput id="nim" type="text" class="mt-1 block w-full" v-model="form.nim" required />
                            <InputError class="mt-2" :message="form.errors.nim" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="no_telepon" value="No. Telepon" />
                            <TextInput id="no_telepon" type="text" class="mt-1 block w-full" v-model="form.no_telepon" />
                            <InputError class="mt-2" :message="form.errors.no_telepon" />
                        </div>

                        <div>
                            <InputLabel for="jurusan" value="Jurusan" />
                            <TextInput id="jurusan" type="text" class="mt-1 block w-full" v-model="form.jurusan" required />
                            <InputError class="mt-2" :message="form.errors.jurusan" />
                        </div>

                        <div>
                            <InputLabel for="angkatan" value="Angkatan" />
                            <TextInput id="angkatan" type="number" class="mt-1 block w-full" v-model="form.angkatan" required />
                            <InputError class="mt-2" :message="form.errors.angkatan" />
                        </div>

                        <div>
                            <InputLabel for="tahun_lulus" value="Tahun Lulus" />
                            <TextInput id="tahun_lulus" type="number" class="mt-1 block w-full" v-model="form.tahun_lulus" />
                            <InputError class="mt-2" :message="form.errors.tahun_lulus" />
                        </div>

                        <div>
                            <InputLabel for="pekerjaan" value="Pekerjaan" />
                            <TextInput id="pekerjaan" type="text" class="mt-1 block w-full" v-model="form.pekerjaan" />
                            <InputError class="mt-2" :message="form.errors.pekerjaan" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="instansi" value="Instansi / Perusahaan" />
                            <TextInput id="instansi" type="text" class="mt-1 block w-full" v-model="form.instansi" />
                            <InputError class="mt-2" :message="form.errors.instansi" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="alamat" value="Alamat" />
                            <textarea
                                id="alamat"
                                v-model="form.alamat"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.alamat" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <Link
                            :href="route('alumni.index')"
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
