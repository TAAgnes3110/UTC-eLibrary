<script setup>
import { ref, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

const BOOK_TYPE_OPTIONS = [
    { value: '', label: 'Tất cả loại tài liệu' },
    { value: 'book', label: 'Sách' },
    { value: 'textbook', label: 'Giáo trình' },
    { value: 'thesis', label: 'Bài luận / Khóa luận / Đồ án' },
    { value: 'dissertation', label: 'Luận văn / Luận án' },
    { value: 'research', label: 'Báo cáo khoa học' },
    { value: 'magazine', label: 'Tạp chí' },
    { value: 'other', label: 'Tài liệu khác' },
];

const STATUS_OPTIONS = [
    { value: '', label: 'Trạng thái' },
    { value: 'available', label: 'Sẵn có' },
    { value: 'unavailable', label: 'Ẩn' },
    { value: 'processing', label: 'Đang xử lý' },
];

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            status: '',
            type: '',
            category_id: '',
            classification_code: '',
            title: '',
        }),
    },
    categories: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'search']);

const status = ref(props.modelValue?.status ?? '');
const type = ref(props.modelValue?.type ?? '');
const category_id = ref(props.modelValue?.category_id ?? '');
const classification_code = ref(props.modelValue?.classification_code ?? '');
const title = ref(props.modelValue?.title ?? '');

watch(() => props.modelValue, (v) => {
    if (!v) return;
    status.value = v.status ?? '';
    type.value = v.type ?? '';
    category_id.value = v.category_id ?? '';
    classification_code.value = v.classification_code ?? '';
    title.value = v.title ?? '';
}, { deep: true });

const applyFilter = () => {
    const payload = {
        status: status.value,
        type: type.value,
        category_id: category_id.value,
        classification_code: classification_code.value,
        title: title.value,
    };
    emit('update:modelValue', payload);
    emit('search', payload);
};

const clearFilter = () => {
    status.value = '';
    type.value = '';
    category_id.value = '';
    classification_code.value = '';
    title.value = '';
    const payload = { status: '', type: '', category_id: '', classification_code: '', title: '' };
    emit('update:modelValue', payload);
    emit('search', payload);
};

const hasActiveFilter = () => status.value || type.value || category_id.value || classification_code.value || title.value;
</script>

<template>
    <div class="flex flex-col gap-3 bg-white dark:bg-slate-900 p-3 rounded-xl border border-gray-200 dark:border-slate-800">
        <div class="flex flex-wrap items-end gap-2">
            <!-- Loại tài liệu -->
            <div class="w-full sm:w-[220px]">
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">Loại tài liệu</label>
                <select
                    v-model="type"
                    class="w-full h-9 px-3 pr-8 text-xs rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:ring-1 focus:ring-blue-500/30 focus:border-blue-400 appearance-none cursor-pointer"
                >
                    <option v-for="opt in BOOK_TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>

            <!-- Thể loại -->
            <div class="w-full sm:w-[200px]">
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">Thể loại</label>
                <select
                    v-model="category_id"
                    class="w-full h-9 px-3 pr-8 text-xs rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:ring-1 focus:ring-blue-500/30 focus:border-blue-400 appearance-none cursor-pointer"
                >
                    <option value="">Tất cả thể loại</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name || c.code }}</option>
                </select>
            </div>

            <!-- Trạng thái -->
            <div class="w-full sm:w-[160px]">
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">Trạng thái</label>
                <select
                    v-model="status"
                    class="w-full h-9 px-3 pr-8 text-xs rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:ring-1 focus:ring-blue-500/30 focus:border-blue-400 appearance-none cursor-pointer"
                >
                    <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>

            <!-- Mã phân loại -->
            <div class="w-full sm:w-[140px]">
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">Mã phân loại</label>
                <input
                    v-model="classification_code"
                    type="text"
                    placeholder="VD: BK001"
                    class="w-full h-9 px-3 text-xs rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:ring-1 focus:ring-blue-500/30"
                    @keyup.enter="applyFilter"
                />
            </div>

            <!-- Tên / Từ khóa -->
            <div class="flex-1 min-w-[160px]">
                <label class="block text-[10px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">Tên sách hoặc từ khóa</label>
                <input
                    v-model="title"
                    type="text"
                    placeholder="Nhập tên sách hoặc từ khóa..."
                    class="w-full h-9 px-3 text-xs rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:ring-1 focus:ring-blue-500/30"
                    @keyup.enter="applyFilter"
                />
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <Button
                    size="sm"
                    class="h-9 rounded-lg px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs"
                    @click="applyFilter"
                >
                    <Icon icon="lucide:search" class="w-3.5 h-3.5 mr-1.5" /> Tìm kiếm
                </Button>
                <Button
                    v-if="hasActiveFilter()"
                    size="sm"
                    variant="outline"
                    class="h-9 rounded-lg px-4 text-xs"
                    @click="clearFilter"
                >
                    <Icon icon="lucide:x" class="w-3.5 h-3.5 mr-1" /> Xóa bộ lọc
                </Button>
            </div>
        </div>
    </div>
</template>
