<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    books: { type: Array, required: true, default: () => [] },
    selectedIds: { type: Array, default: () => [] },
    page: { type: Number, default: 1 },
    perPage: { type: Number, default: 20 },
});

const emit = defineEmits(['edit', 'delete', 'toggle-select', 'toggle-all']);

const allSelected = computed(() =>
    props.books.length > 0 && props.books.every(b => props.selectedIds.includes(b.id))
);

const isSelected = (id) => props.selectedIds.includes(id);

const getStatusLabel = (status) => {
    switch (status) {
        case 'available': return { text: 'Hiển thị', bg: 'bg-emerald-100 dark:bg-emerald-900/30', color: 'text-emerald-700 dark:text-emerald-400' };
        case 'unavailable': return { text: 'Ẩn', bg: 'bg-slate-100 dark:bg-slate-700', color: 'text-slate-600 dark:text-slate-300' };
        case 'processing': return { text: 'Đang xử lý', bg: 'bg-amber-100 dark:bg-amber-900/30', color: 'text-amber-700 dark:text-amber-400' };
        default: return { text: 'Hiển thị', bg: 'bg-emerald-100 dark:bg-emerald-900/30', color: 'text-emerald-700 dark:text-emerald-400' };
    }
};

const getRowIndex = (index) => (props.page - 1) * props.perPage + index + 1;

const getPublisherInfo = (book) => {
    const parts = [];
    if (book.publication_place) parts.push(book.publication_place);
    if (book.publisher_name || book.publisher?.name) parts.push(book.publisher_name || book.publisher?.name);
    if (book.published_year) parts.push(book.published_year);
    return parts.join(',') || '—';
};

const getAuthorNames = (book) => {
    if (book.authors && book.authors.length > 0) {
        return book.authors.map(a => a.name).join(', ');
    }
    return book.author || '';
};

// Shared border class for column dividers
const thBorder = 'border-r border-gray-200 dark:border-slate-700';
const tdBorder = 'border-r border-gray-100 dark:border-slate-800';
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-fixed">
                <colgroup>
                    <col class="w-[40px]" />
                    <col class="w-[50px]" />
                    <col class="w-[120px]" />
                    <col />
                    <col class="w-[220px]" />
                    <col class="w-[60px]" />
                    <col class="w-[85px]" />
                    <col class="w-[85px]" />
                </colgroup>
                <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th :class="['px-2 py-2.5 text-center', thBorder]">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="emit('toggle-all')"
                                class="w-3.5 h-3.5 rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                            />
                        </th>
                        <th :class="['px-2 py-2.5 text-xs font-bold text-gray-500 dark:text-slate-400 text-center', thBorder]">STT</th>
                        <th :class="['px-2 py-2.5 text-xs font-bold text-gray-500 dark:text-slate-400', thBorder]">Mã sách</th>
                        <th :class="['px-2 py-2.5 text-xs font-bold text-gray-500 dark:text-slate-400', thBorder]">Tên sách</th>
                        <th :class="['px-2 py-2.5 text-xs font-bold text-gray-500 dark:text-slate-400', thBorder]">Thông tin xuất bản</th>
                        <th :class="['px-2 py-2.5 text-xs font-bold text-gray-500 dark:text-slate-400 text-center', thBorder]">SL</th>
                        <th :class="['px-2 py-2.5 text-xs font-bold text-gray-500 dark:text-slate-400 text-center', thBorder]">Trạng thái</th>
                        <th class="px-2 py-2.5 text-xs font-bold text-gray-500 dark:text-slate-400 text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    <tr
                        v-for="(book, index) in books"
                        :key="book.id"
                        :class="[
                            isSelected(book.id)
                                ? 'bg-blue-50 dark:bg-blue-900/15'
                                : 'admin-table-row'
                        ]"
                    >
                        <td :class="['px-2 py-2.5 text-center', tdBorder]">
                            <input
                                type="checkbox"
                                :checked="isSelected(book.id)"
                                @change="emit('toggle-select', book.id)"
                                class="w-3.5 h-3.5 rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                            />
                        </td>
                        <td :class="['px-2 py-2.5 text-center text-xs text-gray-500 dark:text-slate-400', tdBorder]">{{ getRowIndex(index) }}</td>
                        <td :class="['px-2 py-2.5 font-mono text-xs font-semibold text-gray-700 dark:text-slate-300', tdBorder]">
                            {{ book.classification_code || `SI${String(book.id).padStart(7, '0')}` }}
                        </td>
                        <td :class="['px-2 py-2.5', tdBorder]">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-10 h-14 bg-gray-50 dark:bg-slate-800 rounded-lg overflow-hidden shrink-0 border border-gray-100 dark:border-slate-800 flex items-center justify-center relative group/cover">
                                    <img v-if="book.image_url" :src="book.image_url" class="w-full h-full object-cover" />
                                    <div v-else class="flex flex-col items-center justify-center opacity-40">
                                        <Icon icon="lucide:book" class="w-5 h-5 text-gray-400" />
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/cover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer" @click.stop="emit('update-single-cover', book)">
                                        <Icon icon="lucide:camera" class="w-4 h-4 text-white" />
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm text-gray-800 dark:text-white leading-snug truncate" :title="book.title">{{ book.title }}</p>
                                    <p v-if="getAuthorNames(book)" class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 truncate uppercase tracking-tighter font-medium" :title="getAuthorNames(book)">
                                        {{ getAuthorNames(book) }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td :class="['px-2 py-2.5 text-xs text-gray-600 dark:text-slate-400', tdBorder]">
                            {{ getPublisherInfo(book) }}
                        </td>
                        <td :class="['px-2 py-2.5 text-center text-xs font-semibold text-gray-700 dark:text-slate-300', tdBorder]">
                            {{ book.quantity ?? 0 }}
                        </td>
                        <td :class="['px-2 py-2.5 text-center', tdBorder]">
                            <span :class="[getStatusLabel(book.status).bg, getStatusLabel(book.status).color, 'px-2 py-0.5 rounded text-[11px] font-semibold inline-block whitespace-nowrap']">
                                {{ getStatusLabel(book.status).text }}
                            </span>
                        </td>
                        <td class="px-2 py-2.5">
                            <div class="flex items-center justify-center gap-0.5">
                                <button
                                    @click="emit('update-single-cover', book)"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors"
                                    title="Cập nhật ảnh bìa"
                                >
                                    <Icon icon="lucide:image-plus" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    @click="emit('edit', book)"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                    title="Sửa"
                                >
                                    <Icon icon="lucide:pencil" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    @click="emit('delete', book)"
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                    title="Xóa"
                                >
                                    <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-if="books.length === 0" class="py-16 text-center">
            <div class="w-14 h-14 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                <Icon icon="lucide:book-x" class="w-7 h-7 text-gray-400 dark:text-slate-500" />
            </div>
            <h3 class="text-sm font-bold text-gray-700 dark:text-white mb-1">Không tìm thấy sách</h3>
            <p class="text-xs text-gray-500 dark:text-slate-400">Thử tìm kiếm với từ khóa khác hoặc thêm sách mới.</p>
        </div>
    </div>
</template>
