<script setup>
import { computed } from 'vue';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

const props = defineProps({
    show: Boolean,
    book: { type: Object, default: null },
    selectedCount: { type: Number, default: 0 },
});

defineEmits(['close', 'confirm']);

// Single delete or bulk delete
const isBulk = computed(() => props.selectedCount > 0 && !props.book);

const message = computed(() => {
    if (isBulk.value) {
        return `Bạn có chắc chắn muốn xóa ${props.selectedCount} sách in?`;
    }
    return `Bạn có chắc chắn muốn xóa sách in ${props.book?.title || ''}?`;
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            @click.self="$emit('close')"
        >
            <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-[0_20px_50px_rgba(0,0,0,0.2)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] w-full max-w-[400px] overflow-hidden animate-in zoom-in-95 fade-in duration-300 border border-slate-100 dark:border-slate-800/60 relative">
                <!-- Decorative Top Border -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 via-rose-600 to-rose-500"></div>

                <!-- Close Button -->
                <button @click="$emit('close')" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800/50 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-all z-10">
                    <Icon icon="lucide:x" class="w-4 h-4" />
                </button>

                <!-- Body Content -->
                <div class="px-8 pt-10 pb-8 text-center">
                    <!-- Warning Icon with Pulsing Effect -->
                    <div class="relative w-20 h-20 mx-auto mb-6">
                        <div class="absolute inset-0 bg-rose-500/20 dark:bg-rose-500/10 rounded-full animate-ping duration-[2000ms]"></div>
                        <div class="relative w-full h-full rounded-full bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center ring-4 ring-white dark:ring-slate-900">
                            <Icon icon="lucide:trash-2" class="w-10 h-10 text-rose-600 dark:text-rose-500" />
                        </div>
                    </div>

                    <!-- Texts -->
                    <div class="space-y-3">
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                            Xác nhận xóa tài liệu?
                        </h3>
                        <div class="px-2">
                             <p class="text-[14px] font-medium text-slate-600 dark:text-slate-300 leading-relaxed">
                                Bạn đang thực hiện xóa tài liệu: <br/>
                                <span class="font-bold text-slate-900 dark:text-white mt-1 block">"{{ props.book?.title || 'Các mục đã chọn' }}"</span>
                            </p>
                        </div>
                        <div class="mt-6 p-4 rounded-2xl bg-rose-50/50 dark:bg-rose-900/10 border border-rose-100/50 dark:border-rose-900/20">
                             <p class="text-[12px] font-bold text-rose-600 dark:text-rose-400 flex items-center justify-center gap-1.5 uppercase tracking-wider">
                                <Icon icon="lucide:shield-alert" class="w-3.5 h-3.5" />
                                Lưu ý: Không thể khôi phục
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer / Buttons -->
                <div class="px-8 pb-8 flex flex-col gap-3">
                    <Button @click="$emit('confirm')" class="w-full h-12 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white text-[15px] font-bold shadow-lg shadow-rose-500/25 transition-all hover:scale-[1.01] active:scale-95 flex items-center justify-center gap-2">
                         <Icon icon="lucide:check" class="w-4 h-4" />
                        Đồng ý và xóa ngay
                    </Button>
                    <Button @click="$emit('close')" variant="ghost" class="w-full h-12 rounded-2xl text-[14px] font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                        Hủy bỏ thực hiện
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
