<script setup>
import { ref, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    loanCount: { type: Number, default: 0 },
    skippedCount: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'confirm']);

const returnDate = ref('');
const conditionOnReturn = ref('tot');

const conditions = [
    { value: 'tot', label: 'Sách còn tốt (áp dụng mọi dòng)' },
    { value: 'hong', label: 'Sách hư hỏng' },
    { value: 'mat', label: 'Sách bị mất' },
];

watch(
    () => props.show,
    (v) => {
        if (v) {
            returnDate.value = new Date().toISOString().slice(0, 10);
            conditionOnReturn.value = 'tot';
        }
    },
);

function onConfirm() {
    if (!returnDate.value) return;
    emit('confirm', {
        return_date: returnDate.value,
        condition_on_return: conditionOnReturn.value,
    });
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="$emit('close')">
            <div class="absolute inset-0 bg-slate-900/70" @click="$emit('close')" />
            <div
                class="relative w-full max-w-md shadow-xl overflow-hidden rounded-xl border-t-4 border-t-emerald-500"
                style="background-color: #20222d"
            >
                <div class="flex justify-end pt-3 pr-3">
                    <button
                        type="button"
                        class="p-1.5 text-slate-400 hover:text-white rounded transition-colors"
                        @click="$emit('close')"
                    >
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="px-6 pb-2 flex flex-col items-center">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center mb-3" style="background-color: rgba(30, 31, 42, 1)">
                        <Icon icon="lucide:book-check" class="w-7 h-7 text-emerald-400" />
                    </div>
                    <h3 class="text-lg font-bold text-white">Trả sách hàng loạt</h3>
                </div>
                <div class="px-6 pb-4 text-center space-y-2">
                    <p class="text-sm text-slate-300">
                        Xác nhận trả <span class="font-semibold text-white">{{ loanCount }}</span> phiếu đang mượn / quá hạn.
                    </p>
                    <p v-if="skippedCount > 0" class="text-xs text-amber-300/95">
                        ({{ skippedCount }} phiếu đã trả trong lựa chọn sẽ bỏ qua.)
                    </p>
                </div>
                <div class="px-6 pb-5 space-y-3 text-left">
                    <label class="block space-y-1">
                        <span class="text-xs font-medium text-slate-400">Ngày trả</span>
                        <input v-model="returnDate" type="date" class="admin-filter-input w-full text-slate-900 dark:text-white" />
                    </label>
                    <label class="block space-y-1">
                        <span class="text-xs font-medium text-slate-400">Tình trạng khi trả (mặc định cả phiếu)</span>
                        <select v-model="conditionOnReturn" class="admin-filter-select w-full dark:bg-slate-800 dark:text-white">
                            <option v-for="c in conditions" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </label>
                    <p class="text-[11px] text-slate-500 leading-snug">
                        Phạt quá hạn / hư / mất vẫn được hệ thống tính theo chính sách và giá sách.
                    </p>
                </div>
                <div class="px-6 py-4 flex justify-center gap-3 border-t border-slate-700/80" style="background-color: #20222d">
                    <Button
                        type="button"
                        variant="outline"
                        class="bg-slate-700/50 border-slate-600 text-white hover:bg-slate-600 hover:text-white"
                        :disabled="loading"
                        @click="$emit('close')"
                    >
                        Hủy
                    </Button>
                    <Button type="button" class="bg-emerald-600 hover:bg-emerald-700 text-white gap-2" :disabled="loading" @click="onConfirm">
                        <Icon v-if="loading" icon="lucide:loader-2" class="w-4 h-4 animate-spin" />
                        {{ loading ? 'Đang xử lý...' : 'Xác nhận trả' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
