<script setup>
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

const props = defineProps({
    modelValue: { type: Object, default: () => ({ status: '', classification_code: '', title: '' }) },
});

const emit = defineEmits(['update:modelValue', 'search']);

const status = ref(props.modelValue?.status || '');
const classification_code = ref(props.modelValue?.classification_code || '');
const title = ref(props.modelValue?.title || '');

const doSearch = () => {
    emit('update:modelValue', { status: status.value, classification_code: classification_code.value, title: title.value });
    emit('search', { status: status.value, classification_code: classification_code.value, title: title.value });
};
</script>

<template>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-gray-200 dark:border-slate-800">
        <!-- Status dropdown -->
        <div class="relative shrink-0 sm:w-[200px]">
            <select
                v-model="status"
                class="w-full h-9 px-3 pr-8 text-xs rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:ring-1 focus:ring-blue-500/30 focus:border-blue-400 appearance-none cursor-pointer"
            >
                <option value="">-- Trạng thái hiển thị --</option>
                <option value="available">Hiển thị</option>
                <option value="unavailable">Ẩn</option>
                <option value="processing">Đang xử lý</option>
            </select>
            <Icon icon="lucide:chevron-down" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
        </div>

        <!-- Code input -->
        <div class="relative shrink-0 sm:w-[150px]">
            <input
                v-model="classification_code"
                type="text"
                placeholder="Mã ấn phẩm"
                class="w-full h-9 px-3 text-xs rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:ring-1 focus:ring-blue-500/30 focus:border-blue-400"
                @keyup.enter="doSearch"
            />
        </div>

        <!-- Title input -->
        <div class="relative flex-1 min-w-[120px]">
            <input
                v-model="title"
                type="text"
                placeholder="Nhan đề / Từ khóa"
                class="w-full h-9 px-3 text-xs rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:ring-1 focus:ring-blue-500/30 focus:border-blue-400"
                @keyup.enter="doSearch"
            />
        </div>

        <!-- Search button -->
        <Button
            size="sm"
            class="h-9 rounded-lg px-5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shrink-0 shadow-sm"
            @click="doSearch"
        >
            <Icon icon="lucide:search" class="w-3.5 h-3.5 mr-1.5" /> Tìm kiếm
        </Button>
    </div>
</template>
