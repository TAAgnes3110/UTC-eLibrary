<script setup>
import { Button } from '@/Components/ui/button';
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
    <div class="space-y-2">
        <!-- Bulk Actions Bar -->
        <div v-if="selectedCount > 0"
            class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 text-xs">
            <span class="font-semibold text-blue-700 dark:text-blue-300">Đã chọn: {{ selectedCount }}</span>
            <Button variant="outline" size="sm" class="h-7 rounded text-[11px] border-gray-300" @click="$emit('deselect-all')">
                <Icon icon="lucide:x" class="w-3 h-3 mr-1" /> Bỏ chọn
            </Button>
            <Button variant="outline" size="sm" class="h-7 rounded text-[11px] border-gray-300" @click="$emit('export-excel')">
                <Icon icon="lucide:file-down" class="w-3 h-3 mr-1" /> Xuất Excel
            </Button>
            <Button variant="destructive" size="sm" class="h-7 rounded text-[11px]" @click="$emit('delete-selected')">
                <Icon icon="lucide:trash-2" class="w-3 h-3 mr-1" /> Xóa
            </Button>
        </div>

        <!-- Main Actions -->
        <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Sách in</h2>
                <div class="flex items-center gap-1.5">
                    <!-- Nhập excel -->
                    <button
                        @click="$emit('import-excel')"
                        class="btn-excel-import"
                    >
                        <Icon icon="lucide:file-spreadsheet" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Nhập excel</span>
                    </button>

                    <!-- Xuất excel -->
                    <button
                        @click="$emit('export-excel')"
                        class="btn-excel-export"
                    >
                        <Icon icon="lucide:file-down" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Xuất excel</span>
                    </button>

                    <!-- Thêm mới -->
                    <button
                        @click="$emit('add')"
                        class="btn-action-primary"
                    >
                        <Icon icon="lucide:plus" class="w-[18px] h-[18px]" />
                        <span>Thêm mới</span>
                    </button>

                    <!-- Cập nhật ảnh bìa -->
                    <button
                        @click="$emit('update-covers')"
                        class="btn-action-primary"
                    >
                        <Icon icon="lucide:image-plus" class="w-[18px] h-[18px]" />
                        <span>Cập nhật ảnh bìa</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
