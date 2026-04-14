<script setup>
import { Icon } from '@iconify/vue';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';
import AdminAvailabilityBadge from '@/Components/Admin/Shared/AdminAvailabilityBadge.vue';

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
            <table class="w-full min-w-[1000px] text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="p-4 w-12 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    :indeterminate="hasSelection && !isAllSelected"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle-select-all')"
                                />
                            </span>
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 w-[120px]">Mã sách</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 text-center min-w-[200px]">Tên sách</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 min-w-[140px]">Tác giả</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 min-w-[140px]">Nhà xuất bản</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Phân loại</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 text-right">Giá</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 text-center">Số lượng</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 text-right w-[110px]">Trạng thái</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 w-[88px]">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr v-for="book in books" :key="book.id" class="admin-table-row">
                        <td class="p-4 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.has(book.id)"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle-select', book.id)"
                                />
                            </span>
                        </td>
                        <td class="p-4 text-center align-middle">
                            <p
                                class="text-[12px] font-semibold text-slate-700 dark:text-slate-200 tracking-wide font-mono whitespace-nowrap"
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
                                <div class="flex-1 min-w-0 max-w-[min(100%,22rem)]">
                                    <button
                                        type="button"
                                        class="font-semibold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 text-sm leading-snug line-clamp-2 text-left break-words"
                                        @click="emit('edit', book)"
                                    >
                                        {{ book.title }}
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 align-top max-w-[220px]">
                            <p class="text-[12px] font-medium text-slate-700 dark:text-slate-200 line-clamp-2 break-words">
                                {{ book.authors_label || '—' }}
                            </p>
                        </td>
                        <td class="p-4 align-top max-w-[220px]">
                            <p class="text-[12px] font-medium text-slate-700 dark:text-slate-200 line-clamp-2 break-words">
                                {{ book.publishers_label || '—' }}
                            </p>
                        </td>
                        <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300 max-w-[200px]">
                            <span class="line-clamp-2 break-words">{{ book.classification?.name || '—' }}</span>
                        </td>
                        <td class="p-4 text-right text-[12px] text-slate-800 dark:text-slate-100">
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
                            <AdminAvailabilityBadge :available="(book.quantity ?? 0) > 0" />
                        </td>
                        <td class="p-4 whitespace-nowrap">
                            <div class="flex flex-nowrap items-center justify-start gap-1">
                                <AdminTableActionIcon
                                    icon="lucide:pen-square"
                                    tone="slate"
                                    title="Chỉnh sửa"
                                    icon-class="w-4 h-4"
                                    @click="emit('edit', book)"
                                />
                                <AdminTableActionIcon
                                    icon="lucide:trash-2"
                                    tone="rose"
                                    title="Xóa"
                                    icon-class="w-4 h-4"
                                    @click="emit('delete', book)"
                                />
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
