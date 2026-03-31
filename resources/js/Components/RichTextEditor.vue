<script setup>
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Tulis konten di sini...',
    },
});

const emit = defineEmits(['update:modelValue']);

const content = computed({
    get: () => props.modelValue ?? '',
    set: (value) => emit('update:modelValue', value ?? ''),
});

const toolbar = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ align: [] }],
    ['blockquote', 'link'],
    ['clean'],
];
</script>

<template>
    <div class="rich-editor rounded-md border border-gray-300 bg-white">
        <QuillEditor
            v-model:content="content"
            content-type="html"
            theme="snow"
            :toolbar="toolbar"
            :placeholder="placeholder"
        />
    </div>
</template>
