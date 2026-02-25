<script setup>
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

const props = defineProps({
    show: Boolean,
    selectedCodes: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'submit']);

const selectedFile = ref(null);
const saveAndContinue = ref(false);

const onFileChange = (e) => {
    selectedFile.value = e.target.files[0];
};

const handleSave = () => {
    if (selectedFile.value) {
        emit('submit', { file: selectedFile.value, saveAndContinue: saveAndContinue.value });
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 rounded shadow-sm w-full max-w-[600px] overflow-hidden border border-gray-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-slate-800">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Cập nhật ảnh bìa</h3>
                <button @click="emit('close')" class="p-1 hover:bg-gray-100 dark:hover:bg-slate-800 rounded transition-colors">
                    <Icon icon="lucide:x" class="w-5 h-5 text-gray-500" />
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
                <!-- Selection Info -->
                <div v-if="selectedCodes.length > 0" class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg">
                    <p class="text-xs text-blue-800 dark:text-blue-300 font-medium mb-1.5 flex items-center gap-1.5">
                        <Icon icon="lucide:info" class="w-3.5 h-3.5" />
                        Đang chọn {{ selectedCodes.length }} ấn phẩm cần cập nhật:
                    </p>
                    <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pr-1">
                        <span v-for="code in selectedCodes" :key="code" class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-700 rounded text-[10px] font-mono text-blue-600 dark:text-blue-400">
                            {{ code }}
                        </span>
                    </div>
                </div>

                <!-- Note -->
                <div class="space-y-1">
                    <p class="text-sm text-rose-600 font-bold italic">
                        Lưu ý: Tên ảnh trong file .zip phải là Mã ấn phẩm.jpg hoặc .png
                    </p>
                    <p class="text-[12px] text-gray-500 font-medium">
                        Ví dụ: <span class="text-blue-600 font-mono">{{ selectedCodes[0] || 'SI0000001' }}.jpg</span> hoặc <span class="text-blue-600 font-mono">{{ selectedCodes[0] || 'SI0000001' }}.png</span>
                    </p>
                </div>

                <!-- File input -->
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">File .zip</label>
                    <div class="relative group">
                        <input
                            type="file"
                            accept=".zip"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                            @change="onFileChange"
                        />
                        <div :class="[
                            'border border-dashed rounded-lg p-10 flex flex-col items-center justify-center gap-2 transition-all duration-300',
                            selectedFile ? 'border-blue-500 bg-blue-50/20' : 'border-blue-200 dark:border-slate-700 bg-blue-50/30'
                        ]">
                            <div class="flex items-center gap-2 text-gray-700 dark:text-slate-200">
                                <Icon icon="lucide:upload" class="w-5 h-5" />
                                <span class="text-sm">{{ selectedFile ? selectedFile.name : 'Chọn file tải lên' }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">(Tối đa 50MB)</p>
                        </div>
                    </div>
                </div>

                <p class="text-[13px] text-gray-600 dark:text-slate-400">
                    (Chú ý: Hỗ trợ file .zip; File zip chứa ảnh định dạng .jpg, .png)
                </p>

                <!-- Save and add another -->
                <label class="flex items-center gap-2 cursor-pointer select-none py-2">
                    <input
                        type="checkbox"
                        v-model="saveAndContinue"
                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <span class="text-sm text-gray-600 dark:text-slate-400">Lưu và thêm tiếp</span>
                </label>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-white dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-3">
                <button @click="emit('close')" class="h-10 px-6 text-sm border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Hủy bỏ
                </button>
                <button
                    @click="handleSave"
                    :disabled="!selectedFile"
                    class="h-10 px-10 bg-[#00478f] hover:bg-[#00366d] text-white text-sm font-bold rounded-md shadow-sm disabled:opacity-50 transition-colors"
                >
                    Lưu
                </button>
            </div>
        </div>
    </div>
</template>
