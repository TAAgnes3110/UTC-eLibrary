<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import {
    QUILL_WORD_TOOLBAR,
    escapeHtmlAttr,
    forceQuillWordToolbarStyle,
    normalizeEditorHtml,
    readFileAsDataUrl,
} from '@/utils/quillEditor';
import { toast } from '@/store/toast';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Nhập nội dung...' },
    /** Bật khi modal/panel hiển thị — khởi tạo Quill sau khi DOM sẵn sàng */
    active: { type: Boolean, default: true },
    minHeight: { type: String, default: '220px' },
    enableImage: { type: Boolean, default: true },
    editorClass: { type: String, default: 'utc-quill-editor' },
});

const emit = defineEmits(['update:modelValue']);

const editorRef = ref(null);
const imageInputRef = ref(null);
const quill = ref(null);
const syncingFromParent = ref(false);

function syncFromModel() {
    if (!quill.value) return;
    const html = String(props.modelValue || '').trim() || '<p><br></p>';
    if (quill.value.root.innerHTML !== html) {
        quill.value.root.innerHTML = html;
    }
}

function syncToModel() {
    if (!quill.value) return;
    const normalized = normalizeEditorHtml(quill.value.root.innerHTML);
    syncingFromParent.value = true;
    emit('update:modelValue', normalized);
    nextTick(() => {
        syncingFromParent.value = false;
    });
}

async function prepareEditor() {
    await nextTick();
    if (!props.active || !editorRef.value) return;

    const shouldReinitialize =
        !quill.value ||
        !quill.value.root ||
        !quill.value.root.isConnected ||
        quill.value.root.closest(`.${props.editorClass}`) !== editorRef.value;

    if (shouldReinitialize) {
        editorRef.value.innerHTML = '';
        const handlers = props.enableImage
            ? { image: () => imageInputRef.value?.click() }
            : {};

        quill.value = new Quill(editorRef.value, {
            theme: 'snow',
            placeholder: props.placeholder,
            modules: {
                toolbar: {
                    container: QUILL_WORD_TOOLBAR,
                    handlers,
                },
            },
        });

        quill.value.on('text-change', syncToModel);
    }

    forceQuillWordToolbarStyle(quill.value);
    syncFromModel();
}

async function onInlineImageSelected(event) {
    const file = event?.target?.files?.[0];
    if (!file) return;

    if (!String(file.type || '').startsWith('image/')) {
        toast.error('Vui lòng chọn đúng tệp ảnh (jpg, png, webp, gif).');
        if (event?.target) event.target.value = '';
        return;
    }
    if ((file.size || 0) > 10 * 1024 * 1024) {
        toast.error('Ảnh chèn nội dung không vượt quá 10MB.');
        if (event?.target) event.target.value = '';
        return;
    }

    try {
        if (!quill.value) {
            throw new Error('Trình soạn thảo chưa sẵn sàng. Vui lòng thử lại.');
        }

        const dataUrl = await readFileAsDataUrl(file);
        const safeSrc = escapeHtmlAttr(dataUrl);
        const safeAlt = escapeHtmlAttr(file.name || 'image');
        const root = quill.value.root;
        const wrapper = document.createElement('p');
        const image = document.createElement('img');
        image.setAttribute('src', safeSrc);
        image.setAttribute('alt', safeAlt);
        image.style.maxWidth = '100%';
        image.style.height = 'auto';
        wrapper.appendChild(image);
        root.appendChild(wrapper);
        syncToModel();
    } catch (e) {
        toast.error(e?.message || 'Không thể chèn ảnh vào nội dung.');
    } finally {
        if (event?.target) event.target.value = '';
    }
}

watch(
    () => props.active,
    (visible) => {
        if (visible) {
            prepareEditor();
        }
    },
    { immediate: true }
);

watch(
    () => props.modelValue,
    () => {
        if (syncingFromParent.value) return;
        syncFromModel();
    }
);

onBeforeUnmount(() => {
    quill.value = null;
});

defineExpose({ prepare: prepareEditor, syncToModel });
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm dark:border-slate-600">
        <input
            v-if="enableImage"
            ref="imageInputRef"
            type="file"
            accept=".jpg,.jpeg,.png,.webp,.gif"
            class="hidden"
            @change="onInlineImageSelected"
        />
        <div
            ref="editorRef"
            :class="editorClass"
            :style="{ '--utc-quill-min-height': minHeight }"
        />
    </div>
</template>

<style scoped>
.utc-quill-editor :deep(.ql-toolbar.ql-snow) {
    border: 0;
    border-bottom: 1px solid #cbd5e1 !important;
    background: #e5e7eb;
    padding: 10px 12px;
}

.utc-quill-editor :deep(.ql-container.ql-snow) {
    border: 0;
    min-height: var(--utc-quill-min-height, 220px);
    font-size: 14px;
    background: #ffffff !important;
}

.utc-quill-editor :deep(.ql-editor) {
    min-height: var(--utc-quill-min-height, 220px);
    color: #111827 !important;
    line-height: 1.7;
    caret-color: #111827 !important;
}

.utc-quill-editor :deep(.ql-editor.ql-blank::before) {
    color: #6b7280;
    font-style: normal;
}

.utc-quill-editor :deep(.ql-editor *),
.utc-quill-editor :deep(.ql-editor a) {
    color: #111827 !important;
    text-shadow: none !important;
}

.utc-quill-editor :deep(.ql-editor a) {
    color: #2563eb !important;
}

.utc-quill-editor :deep(.ql-toolbar button),
.utc-quill-editor :deep(.ql-toolbar .ql-picker-label),
.utc-quill-editor :deep(.ql-toolbar .ql-picker-label::before) {
    color: #1f2937 !important;
    opacity: 1 !important;
}

.utc-quill-editor :deep(.ql-toolbar button svg .ql-stroke),
.utc-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-stroke) {
    stroke: #1f2937 !important;
}

.utc-quill-editor :deep(.ql-toolbar button svg .ql-fill),
.utc-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-fill) {
    fill: #1f2937 !important;
}

.utc-quill-editor :deep(.ql-toolbar button:hover),
.utc-quill-editor :deep(.ql-toolbar button.ql-active),
.utc-quill-editor :deep(.ql-toolbar .ql-picker-label:hover),
.utc-quill-editor :deep(.ql-toolbar .ql-picker-label.ql-active) {
    background: #d1d5db !important;
    border-radius: 6px;
}

:global(html.dark) .utc-quill-editor :deep(.ql-toolbar.ql-snow),
:global(body.dark) .utc-quill-editor :deep(.ql-toolbar.ql-snow),
:global(.dark) .utc-quill-editor :deep(.ql-toolbar.ql-snow) {
    background: #e5e7eb !important;
    border-bottom: 1px solid #cbd5e1 !important;
}
</style>
