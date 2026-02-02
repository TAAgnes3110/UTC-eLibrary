<script setup>
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

defineEmits(['add', 'import', 'download-template']);
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-wrap gap-3">
            <Button
                @click="$emit('add')"
                class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg shadow-blue-600/20 px-6 py-6"
            >
                <Icon icon="lucide:plus" class="w-5 h-5 mr-2" />
                <span class="font-black">Thêm Sách Mới</span>
            </Button>
            <Button
                @click="$emit('import')"
                variant="outline"
                class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 px-6 py-6"
            >
                <Icon icon="lucide:file-spreadsheet" class="w-5 h-5 mr-2" />
                <span class="font-black">Nhập từ Excel</span>
            </Button>
        </div>
        <Button
            @click="$emit('download-template')"
            variant="ghost"
            class="rounded-xl dark:text-slate-400"
        >
            <Icon icon="lucide:download" class="w-4 h-4 mr-2" />
            Tải file mẫu
        </Button>
    </div>
</template>
