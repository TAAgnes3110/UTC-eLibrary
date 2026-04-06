<script setup>
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

defineProps({
    show: { type: Boolean, required: true },
    isLockAction: { type: Boolean, required: true },
    cardLabel: { type: String, default: '—' },
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="$emit('close')">
            <div class="absolute inset-0 bg-slate-900/70" @click="$emit('close')" />
            <div class="relative w-full max-w-md shadow-xl overflow-hidden rounded-xl border-t-4 border-t-amber-500" style="background-color: #20222d">
                <div class="flex justify-end pt-3 pr-3">
                    <button type="button" class="p-1.5 text-slate-400 hover:text-white rounded transition-colors" @click="$emit('close')">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="px-6 pb-2 flex flex-col items-center">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center mb-3" style="background-color: rgba(30, 31, 42, 1)">
                        <Icon :icon="isLockAction ? 'lucide:lock' : 'lucide:lock-open'" class="w-7 h-7 text-amber-500" />
                    </div>
                    <h3 class="text-lg font-bold text-white">
                        {{ isLockAction ? 'Xác nhận khóa thẻ?' : 'Xác nhận mở khóa thẻ?' }}
                    </h3>
                </div>
                <div class="px-6 pb-5 text-center">
                    <p class="text-sm text-slate-300">
                        {{ isLockAction ? 'Trạng thái thẻ sẽ được đặt thành Khóa.' : 'Trạng thái thẻ sẽ được đặt thành Hoạt động.' }}
                    </p>
                    <p class="mt-2 text-sm font-medium text-white">"{{ cardLabel }}"</p>
                </div>
                <div class="px-6 py-4 flex justify-center gap-3 border-t border-slate-700/80" style="background-color: #20222d">
                    <Button
                        type="button"
                        variant="outline"
                        class="bg-slate-700/50 border-slate-600 text-white hover:bg-slate-600 hover:text-white"
                        @click="$emit('close')"
                    >
                        Quay lại
                    </Button>
                    <Button
                        type="button"
                        :class="isLockAction ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-500 hover:bg-emerald-600'"
                        class="text-white"
                        @click="$emit('confirm')"
                    >
                        {{ isLockAction ? 'Khóa thẻ' : 'Mở khóa thẻ' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
