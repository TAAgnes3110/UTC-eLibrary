<script setup>
import { Icon } from '@iconify/vue';

/**
 * Định dạng chung theo mẫu: Thêm mới (xanh #2D72D9), còn lại nền xám đậm #282A2E, chữ trắng.
 * Xóa: nền xám đậm, icon + chữ đỏ. Đã chọn / Bỏ chọn: chữ trắng.
 */
defineProps({
    hasSelection: { type: Boolean, default: false },
    selectedCount: { type: Number, default: 0 },
    updateFileLabel: { type: String, default: 'Cập nhật file / ảnh' },
    /** dark = toàn bộ nền tối (Readers, Sách); light = giữ style tương tự trên nền sáng */
    variant: { type: String, default: 'dark' },
});

defineEmits([
    'add',
    'export-excel',
    'import-excel',
    'update-file',
    'delete-selected',
    'deselect-all',
]);
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" @click="$emit('add')" class="btn-admin-primary">
            <Icon icon="lucide:plus" class="w-3.5 h-3.5" />
            Thêm mới
        </button>
        <button type="button" @click="$emit('export-excel')" class="btn-admin-secondary">
            <Icon icon="lucide:file-down" class="w-3.5 h-3.5" />
            Xuất excel
        </button>
        <button type="button" @click="$emit('import-excel')" class="btn-admin-secondary">
            <Icon icon="lucide:file-up" class="w-3.5 h-3.5" />
            Nhập excel
        </button>
        <template v-if="hasSelection">
            <button
                type="button"
                @click="$emit('delete-selected')"
                class="btn-admin-secondary text-rose-400 hover:text-rose-300"
            >
                <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                Xóa
            </button>
            <span class="text-xs font-medium text-white dark:text-slate-400">Đã chọn {{ selectedCount }}</span>
            <button type="button" @click="$emit('deselect-all')" class="text-xs font-medium text-white dark:text-slate-400 hover:underline">Bỏ chọn</button>
        </template>
        <slot name="extra" />
        <button type="button" @click="$emit('update-file')" class="btn-admin-secondary">
            <Icon icon="lucide:image-plus" class="w-3.5 h-3.5" />
            {{ updateFileLabel }}
        </button>
    </div>
</template>
