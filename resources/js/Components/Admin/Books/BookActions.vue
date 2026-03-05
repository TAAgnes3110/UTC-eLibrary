<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    selectedCount: { type: Number, default: 0 },
});

defineEmits([
    'add', 'import-excel', 'export-excel', 'update-covers',
    'deselect-all', 'delete-selected',
]);
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" @click="$emit('add')" class="btn-admin-green">
            <Icon icon="lucide:plus" class="w-3.5 h-3.5" />
            Thêm mới
        </button>
        <button type="button" @click="$emit('export-excel')" class="btn-admin-green">
            <Icon icon="lucide:file-down" class="w-3.5 h-3.5" />
            Xuất excel
        </button>
        <button type="button" @click="$emit('import-excel')" class="btn-admin-green">
            <Icon icon="lucide:file-up" class="w-3.5 h-3.5" />
            Nhập excel
        </button>
        <template v-if="selectedCount > 0">
            <button type="button" @click="$emit('delete-selected')" class="btn-admin-green">
                <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                Xóa
            </button>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Đã chọn {{ selectedCount }}</span>
            <button type="button" @click="$emit('deselect-all')" class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:underline">Bỏ chọn</button>
        </template>
        <button type="button" @click="$emit('update-covers')" class="btn-admin-green">
            <Icon icon="lucide:image-plus" class="w-3.5 h-3.5" />
            Cập nhật ảnh bìa
        </button>
    </div>
</template>
