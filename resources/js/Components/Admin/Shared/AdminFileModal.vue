<script setup>
import { ref, watch } from 'vue';
import { Icon } from '@iconify/vue';

/**
 * Định dạng chung: Modal nhập file / upload ảnh cho mọi trang.
 * Dùng cho: Nhập excel, Cập nhật ảnh thẻ, Cập nhật ảnh bìa – cùng layout.
 * Props: title, description, accept (".xls,.xlsx" | ".zip"), maxSizeMb, templateLabel + templateHref (optional), submitLabel
 */
const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Tải file lên' },
    description: { type: String, default: 'Chọn file hoặc kéo thả vào vùng bên dưới.' },
    /** accept attribute, vd: ".xls,.xlsx" hoặc ".zip" */
    accept: { type: String, default: '.xls,.xlsx' },
    /** Dung lượng tối đa (MB) */
    maxSizeMb: { type: Number, default: 10 },
    /** Có hiện "Tải file mẫu" (nhập excel) */
    templateLabel: { type: String, default: '' },
    templateHref: { type: String, default: '' },
    /** Nhãn nút xác nhận */
    submitLabel: { type: String, default: 'Tải lên' },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit', 'download-template']);

const file = ref(null);
const fileName = ref('');
const dragOver = ref(false);

watch(() => props.show, (val) => {
    if (!val) {
        file.value = null;
        fileName.value = '';
    }
});

const onFileSelect = (e) => {
    const f = e.target?.files?.[0];
    if (f) {
        file.value = f;
        fileName.value = f.name;
    }
};

const onDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;
    const f = e.dataTransfer?.files?.[0];
    if (f) {
        file.value = f;
        fileName.value = f.name;
    }
};

const reset = () => {
    file.value = null;
    fileName.value = '';
};

const submit = () => {
    if (file.value) emit('submit', file.value);
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="emit('close'); reset()"
        >
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ title }}</h3>
                    <button type="button" @click="emit('close'); reset()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                        <Icon icon="lucide:x" class="w-4 h-4" />
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ description }}</p>

                    <!-- Tải file mẫu (khi có) -->
                    <div v-if="templateLabel" class="flex items-center justify-between py-1">
                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ templateLabel }}</span>
                        <button
                            type="button"
                            @click="emit('download-template')"
                            class="text-sm font-medium text-[#2E86DE] hover:underline flex items-center gap-1"
                        >
                            <Icon icon="lucide:download" class="w-4 h-4" />
                            Tải xuống
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            File <span class="text-rose-500">*</span>
                        </label>
                        <div
                            @dragover.prevent="dragOver = true"
                            @dragleave="dragOver = false"
                            @drop="onDrop"
                            :class="[
                                'border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer relative',
                                dragOver ? 'border-[#2E86DE] bg-blue-50/50 dark:bg-blue-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600'
                            ]"
                        >
                            <input
                                type="file"
                                :accept="accept"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                @change="onFileSelect"
                            />
                            <div v-if="fileName" class="space-y-2">
                                <Icon icon="lucide:file" class="w-10 h-10 text-[#2E86DE] mx-auto" />
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 truncate max-w-full">{{ fileName }}</p>
                                <button type="button" @click.stop="reset" class="text-xs text-rose-500 hover:underline">Xóa file</button>
                            </div>
                            <div v-else class="space-y-2">
                                <Icon icon="lucide:upload" class="w-8 h-8 text-slate-400 mx-auto" />
                                <p class="text-sm text-slate-500 dark:text-slate-400">Chọn file hoặc kéo thả vào đây</p>
                            </div>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">(Tối đa {{ maxSizeMb }}MB)</p>
                        </div>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">(Định dạng: {{ accept }})</p>
                    </div>

                    <slot name="hint" />
                </div>

                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                    <button
                        type="button"
                        @click="emit('close'); reset()"
                        class="h-10 px-5 rounded-lg font-semibold text-sm border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700"
                    >
                        Hủy bỏ
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="!file || loading"
                        class="h-10 px-5 rounded-lg font-semibold text-sm bg-[#2E86DE] text-white hover:bg-[#2563eb] disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <Icon v-if="loading" icon="lucide:loader-2" class="w-4 h-4 animate-spin" />
                        {{ submitLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
