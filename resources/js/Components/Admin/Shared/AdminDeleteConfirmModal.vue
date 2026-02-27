<script setup>
/**
 * Modal xác nhận xóa chung – xóa mềm.
 * Nội dung: mục sẽ chuyển vào Thùng rác, có thể khôi phục hoặc xóa vĩnh viễn từ đó.
 */
import { computed } from 'vue';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    /** Tiêu đề modal */
    title: { type: String, default: 'Xác nhận xóa' },
    /** Tên mục (sách, tài khoản, tác giả...) dùng trong câu */
    itemLabel: { type: String, default: 'mục' },
    /** Một bản ghi: { id, name hoặc title } */
    item: { type: Object, default: null },
    /** Số bản ghi khi xóa nhiều (ưu tiên hơn item) */
    selectedCount: { type: Number, default: 0 },
});

defineEmits(['close', 'confirm']);

const isBulk = computed(() => props.selectedCount > 0 && !props.item);

const displayName = computed(() => {
    if (isBulk.value) return `${props.selectedCount} ${props.itemLabel}`;
    const it = props.item;
    if (!it) return props.itemLabel;
    return it.title ?? it.name ?? it.code ?? `#${it.id}`;
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="$emit('close')">
            <div class="absolute inset-0 bg-slate-900/70" @click="$emit('close')" />
            <div class="relative w-full max-w-md shadow-xl overflow-hidden rounded-xl border-t-4 border-t-red-500" style="background-color: #20222D;">
                <div class="flex justify-end pt-3 pr-3">
                    <button type="button" @click="$emit('close')" class="p-1.5 text-slate-400 hover:text-white rounded transition-colors">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="px-6 pb-2 flex flex-col items-center">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center mb-3" style="background-color: rgba(30,31,42,1);">
                        <Icon icon="lucide:trash-2" class="w-7 h-7 text-red-500" />
                    </div>
                    <h3 class="text-lg font-bold text-white">Xác nhận xóa?</h3>
                </div>
                <div class="px-6 pb-5 text-center">
                    <p class="text-sm text-slate-300">
                        Bạn đang thực hiện xóa dữ liệu này:
                    </p>
                    <p class="mt-2 text-sm font-medium text-white">"{{ displayName }}"</p>
                </div>
                <div class="px-6 py-4 flex justify-center gap-3 border-t border-slate-700/80" style="background-color: #20222D;">
                    <Button type="button" variant="outline" class="bg-slate-700/50 border-slate-600 text-white hover:bg-slate-600 hover:text-white" @click="$emit('close')">
                        Quay lại
                    </Button>
                    <Button type="button" class="bg-red-500 hover:bg-red-600 text-white" @click="$emit('confirm')">
                        Xóa dữ liệu
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
