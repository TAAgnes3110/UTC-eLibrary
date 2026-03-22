<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    books: { type: Array, required: true },
    selectedIds: { type: Object, required: true },
    isAllSelected: { type: Boolean, required: true },
    hasSelection: { type: Boolean, required: true },
});

const emit = defineEmits(['toggle-select-all', 'toggle-select', 'edit', 'delete', 'cover']);
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="p-4 w-12">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                :indeterminate="hasSelection && !isAllSelected"
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                @change="emit('toggle-select-all')"
                            />
                        </th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-[120px]">Mã sách</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Tên sách</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tác giả</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Nhà xuất bản</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Phân loại</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Giá</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Số lượng</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right w-[110px]">Trạng thái</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right w-[88px]">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr v-for="book in books" :key="book.id" class="admin-table-row">
                        <td class="p-4">
                            <input
                                type="checkbox"
                                :checked="selectedIds.has(book.id)"
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                @change="emit('toggle-select', book.id)"
                            />
                        </td>
                        <td class="p-4 text-center align-middle">
                            <p
                                class="text-[12px] font-semibold text-slate-100 dark:text-slate-50 tracking-wide font-mono whitespace-nowrap"
                            >
                                {{ book.book_code }}
                            </p>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-9 w-7 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 flex-shrink-0 flex items-center justify-center ring-1 ring-slate-200/80 dark:ring-slate-700/80 relative group/cover"
                                >
                                    <img
                                        :src="book.cover_image || '/images/default-book-cover.png'"
                                        :alt="book.title"
                                        class="h-full w-full object-cover"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-0 bg-black/35 opacity-0 group-hover/cover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer"
                                        title="Cập nhật ảnh bìa"
                                        @click.stop="emit('cover', book)"
                                    >
                                        <Icon icon="lucide:camera" class="w-3.5 h-3.5 text-white" />
                                    </button>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <button
                                        type="button"
                                        class="font-semibold text-slate-100 dark:text-white hover:text-blue-400 text-sm leading-snug line-clamp-2 text-left"
                                        @click="emit('edit', book)"
                                    >
                                        {{ book.title }}
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 align-top">
                            <p class="text-[12px] font-medium text-slate-100 dark:text-slate-100 line-clamp-2">
                                {{ book.authors_label || '—' }}
                            </p>
                        </td>
                        <td class="p-4 align-top">
                            <p class="text-[12px] font-medium text-slate-100 dark:text-slate-100 line-clamp-2">
                                {{ book.publishers_label || '—' }}
                            </p>
                        </td>
                        <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                            {{ book.classification?.name || '—' }}
                        </td>
                        <td class="p-4 text-right text-[12px] text-slate-100 dark:text-slate-100">
                            <span class="font-semibold whitespace-nowrap">
                                {{ (book.price ?? 0).toLocaleString('vi-VN') }} đ
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <span
                                class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-slate-50 dark:bg-slate-800 text-[11px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 min-w-[3rem]"
                            >
                                {{ book.quantity ?? 0 }}
                            </span>
                        </td>
                        <td class="p-4 text-right w-[110px]">
                            <span
                                :class="[
                                    'inline-flex items-center justify-end gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase text-white',
                                    (book.quantity ?? 0) > 0
                                        ? 'bg-emerald-500 dark:bg-emerald-600'
                                        : 'bg-rose-500 dark:bg-rose-600',
                                ]"
                            >
                                <Icon
                                    :icon="(book.quantity ?? 0) > 0 ? 'lucide:check-circle' : 'lucide:x-circle'"
                                    class="w-3 h-3"
                                />
                                {{ (book.quantity ?? 0) > 0 ? 'Còn' : 'Hết' }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-50 dark:hover:bg-slate-800 rounded"
                                    title="Chỉnh sửa"
                                    @click="emit('edit', book)"
                                >
                                    <Icon icon="lucide:pen-square" class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded"
                                    title="Xóa"
                                    @click="emit('delete', book)"
                                >
                                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="books.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">
            Chưa có sách nào trong danh sách mẫu.
        </p>
    </div>
</template>
