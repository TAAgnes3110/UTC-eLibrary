<script setup>
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

defineProps({
    show: Boolean,
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'import', 'download-template']);

const file = ref(null);
const fileName = ref('');
const dragOver = ref(false);

const onFileSelect = (e) => {
    const selected = e.target.files[0];
    if (selected) {
        file.value = selected;
        fileName.value = selected.name;
    }
};

const onDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;
    const dropped = e.dataTransfer.files[0];
    if (dropped) {
        file.value = dropped;
        fileName.value = dropped.name;
    }
};

const submit = () => {
    if (file.value) {
        emit('import', file.value);
    }
};

const reset = () => {
    file.value = null;
    fileName.value = '';
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            @click.self="$emit('close')"
        >
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Nhập excel</h3>
                    <button @click="$emit('close'); reset()" class="w-8 h-8 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg flex items-center justify-center transition-colors">
                        <Icon icon="lucide:x" class="w-4 h-4 text-slate-500" />
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 space-y-4">
                    <!-- File label -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            File excel <span class="text-red-500">*</span>
                        </label>

                        <!-- Drop zone -->
                        <div
                            @dragover.prevent="dragOver = true"
                            @dragleave="dragOver = false"
                            @drop="onDrop"
                            :class="[
                                'border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer relative',
                                dragOver
                                    ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                                    : 'border-slate-200 dark:border-slate-700 hover:border-blue-400'
                            ]"
                        >
                            <input type="file" @change="onFileSelect" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept=".xls,.xlsx" />
                            <div v-if="fileName" class="space-y-2">
                                <Icon icon="lucide:file-spreadsheet" class="w-10 h-10 text-emerald-600 mx-auto" />
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ fileName }}</p>
                                <button @click.stop="reset" class="text-xs text-red-500 hover:underline">Xóa file</button>
                            </div>
                            <div v-else class="space-y-2">
                                <Icon icon="lucide:upload" class="w-8 h-8 text-slate-400 mx-auto" />
                                <p class="text-sm text-slate-500">Chọn file tải lên <Icon icon="lucide:paperclip" class="w-4 h-4 inline" /></p>
                            </div>
                            <p class="text-xs text-red-500 mt-2">(Tối đa 10MB)</p>
                        </div>

                        <p class="text-xs text-slate-400 mt-2">(Chú ý: Hỗ trợ file .xls, .xlsx)</p>
                    </div>

                    <!-- Download template -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Tải file mẫu</span>
                        <button @click="$emit('download-template')" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                            <Icon icon="lucide:download" class="w-4 h-4" /> Tải xuống
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                    <Button variant="outline" @click="$emit('close'); reset()" class="h-9 rounded-lg px-5 text-sm font-medium">
                        Hủy bỏ
                    </Button>
                    <Button
                        @click="submit"
                        :disabled="!file || loading"
                        class="h-9 rounded-lg px-5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold disabled:opacity-50"
                    >
                        <Icon v-if="loading" icon="lucide:loader-2" class="w-4 h-4 mr-1.5 animate-spin" />
                        Nhập excel
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
