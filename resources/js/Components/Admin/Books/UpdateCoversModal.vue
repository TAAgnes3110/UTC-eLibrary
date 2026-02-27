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
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#1a1c29] rounded-[16px] shadow-2xl w-full max-w-[600px] overflow-hidden border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800/80">
                    <h3 class="text-[18px] font-bold text-slate-800 dark:text-white tracking-tight">Cập nhật ảnh bìa</h3>
                    <button @click="emit('close')" class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors group">
                        <Icon icon="lucide:x" class="w-5 h-5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200" />
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6">
                    <!-- Selection Info (Only visible if selectedCodes is not empty) -->
                    <div v-if="selectedCodes.length > 0" class="p-4 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/50 rounded-xl">
                        <p class="text-[13px] text-blue-800 dark:text-blue-300 font-bold mb-2 flex items-center gap-1.5">
                            <Icon icon="lucide:info" class="w-4 h-4" />
                            Đang chọn {{ selectedCodes.length }} ấn phẩm cần cập nhật:
                        </p>
                        <div class="flex flex-wrap gap-2 max-h-24 overflow-y-auto pr-1">
                            <span v-for="code in selectedCodes" :key="code" class="px-2 py-1 bg-white dark:bg-slate-800 border border-blue-200/50 dark:border-slate-700/50 rounded-md text-[11px] font-mono font-bold text-blue-600 dark:text-blue-400 shadow-sm">
                                {{ code }}
                            </span>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="space-y-1.5">
                        <p class="text-[15px] text-rose-600 dark:text-rose-500 font-bold italic">
                            Lưu ý: Tên ảnh trong file .zip phải là Mã ấn phẩm.jpg hoặc .png
                        </p>
                        <p class="text-[13px] text-slate-500 dark:text-slate-400 font-medium">
                            Ví dụ: <span class="text-blue-600 dark:text-blue-400 font-mono">{{ selectedCodes[0] || 'SI0000001' }}.jpg</span> hoặc <span class="text-blue-600 dark:text-blue-400 font-mono">{{ selectedCodes[0] || 'SI0000001' }}.png</span>
                        </p>
                    </div>

                    <!-- File input -->
                    <div class="space-y-2">
                        <label class="block text-[15px] font-bold text-slate-700 dark:text-white">File .zip</label>
                        <div class="relative group mt-1">
                            <input
                                type="file"
                                accept=".zip"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                @change="onFileChange"
                            />
                            <div :class="[
                                'rounded-[12px] p-12 flex flex-col items-center justify-center gap-2 border border-slate-200 dark:border-slate-700 transition-all duration-300',
                                selectedFile ? 'bg-blue-50 dark:bg-slate-700 border-blue-300 dark:border-blue-500' : 'bg-slate-50 dark:bg-[#5a5f70]'
                            ]">
                                <div class="flex items-center gap-2 text-slate-700 dark:text-white">
                                    <Icon icon="lucide:upload" class="w-[20px] h-[20px] opacity-80" />
                                    <span class="text-[15px] font-medium">{{ selectedFile ? selectedFile.name : 'Chọn file tải lên' }}</span>
                                </div>
                                <p class="text-[13px] text-slate-400 dark:text-[#a0a5ba] mt-1">(Tối đa 50MB)</p>
                            </div>
                        </div>
                    </div>

                    <p class="text-[14px] text-slate-600 dark:text-[#949ab1] font-medium">
                        (Chú ý: Hỗ trợ file .zip; File zip chứa ảnh định dạng .jpg, .png)
                    </p>

                    <!-- Save and add another -->
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative flex items-center justify-center">
                            <input
                                type="checkbox"
                                v-model="saveAndContinue"
                                class="w-[18px] h-[18px] rounded-[6px] border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500/20 bg-white dark:bg-white checked:bg-blue-600 dark:checked:bg-white appearance-none cursor-pointer transition-colors"
                            />
                             <Icon v-if="saveAndContinue" icon="lucide:check" class="absolute w-3.5 h-3.5 text-white dark:text-slate-900 pointer-events-none" />
                        </div>
                        <span class="text-[15px] font-medium text-slate-700 dark:text-[#949ab1]">Lưu và thêm tiếp</span>
                    </label>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-[#151722] border-t border-slate-100 dark:border-slate-800/80 flex justify-end gap-3">
                    <button @click="emit('close')" class="h-11 px-8 text-[14px] font-bold border border-slate-200 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-800/50 rounded-[8px] text-slate-600 dark:text-white transition-colors">
                        Hủy bỏ
                    </button>
                    <button
                        @click="handleSave"
                        :disabled="!selectedFile"
                        class="h-11 px-10 bg-[#0f3b74] hover:bg-[#1a4a8a] text-white text-[14px] font-bold rounded-[8px] shadow-lg shadow-[#0f3b74]/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        Lưu
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
