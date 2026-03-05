<script setup>
import { Icon } from '@iconify/vue';

/**
 * Thanh thao tác thống nhất các trang admin (theo form Quản lý sách).
 * Các trang không cần nhập/xuất excel hay cập nhật file có thể ẩn qua props.
 */
defineProps({
    hasSelection: { type: Boolean, default: false },
    selectedCount: { type: Number, default: 0 },
    addLabel: { type: String, default: 'Thêm mới' },
    updateFileLabel: { type: String, default: 'Cập nhật file / ảnh' },
    /** Hiện nút Thêm mới */
    showAdd: { type: Boolean, default: true },
    /** Hiện nút Xuất excel */
    showExport: { type: Boolean, default: true },
    /** Hiện nút Nhập excel */
    showImport: { type: Boolean, default: true },
    /** Hiện nút Cập nhật file / ảnh */
    showUpdateFile: { type: Boolean, default: true },
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
        <button v-if="showAdd" type="button" @click="$emit('add')" class="btn-admin-green">
            <Icon icon="lucide:plus" class="w-3.5 h-3.5" />
            {{ addLabel }}
        </button>
        <button v-if="showExport" type="button" @click="$emit('export-excel')" class="btn-admin-green">
            <Icon icon="lucide:file-down" class="w-3.5 h-3.5" />
            Xuất excel
        </button>
        <button v-if="showImport" type="button" @click="$emit('import-excel')" class="btn-admin-green">
            <Icon icon="lucide:file-up" class="w-3.5 h-3.5" />
            Nhập excel
        </button>
        <template v-if="hasSelection">
            <button
                type="button"
                @click="$emit('delete-selected')"
                class="btn-admin-green"
            >
                <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                Xóa
            </button>
            <span class="text-xs font-medium text-white dark:text-slate-400">Đã chọn {{ selectedCount }}</span>
            <button type="button" @click="$emit('deselect-all')" class="text-xs font-medium text-white dark:text-slate-400 hover:underline">Bỏ chọn</button>
        </template>
        <slot name="extra" />
        <button v-if="showUpdateFile" type="button" @click="$emit('update-file')" class="btn-admin-green">
            <Icon icon="lucide:image-plus" class="w-3.5 h-3.5" />
            {{ updateFileLabel }}
        </button>
    </div>
</template>
