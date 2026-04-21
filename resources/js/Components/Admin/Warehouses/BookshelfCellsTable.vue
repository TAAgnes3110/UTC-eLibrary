<script setup>
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';

defineProps({
    rows: { type: Array, required: true },
    selectedIds: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    isAllSelected: { type: Boolean, required: true },
    hasSelection: { type: Boolean, required: true },
});

const emit = defineEmits(['toggle-all', 'toggle', 'view', 'edit', 'delete']);
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left border-collapse">
                <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th class="p-4 w-12 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    :indeterminate="hasSelection && !isAllSelected"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle-all')"
                                />
                            </span>
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Vị trí</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Nhãn</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Phân loại</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Phân loại chi tiết</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Kho sách</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300 text-right">Số lượng</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Trạng thái</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300 w-[98px]">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr
                        v-for="cell in rows"
                        :key="cell.id"
                        :class="[selectedIds.includes(cell.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                    >
                        <td class="p-4 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(cell.id)"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle', cell.id)"
                                />
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap font-mono text-[12px] text-slate-700 dark:text-slate-200">
                            R{{ String(cell.row_index).padStart(2, '0') }}-C{{ String(cell.column_index).padStart(2, '0') }}
                        </td>
                        <td class="p-4 align-middle max-w-[130px] truncate text-sm text-slate-800 dark:text-slate-200">
                            {{ cell.label || '—' }}
                        </td>
                        <td class="p-4 align-middle max-w-[200px]">
                            <span class="text-sm text-slate-800 dark:text-slate-200">
                                {{ cell.classification?.name || '—' }}
                            </span>
                        </td>
                        <td class="p-4 align-middle max-w-[220px]">
                            <span class="text-sm text-slate-800 dark:text-slate-200">
                                {{ cell.classification_detail?.name || '—' }}
                            </span>
                        </td>
                        <td class="p-4 align-middle max-w-[220px]">
                            <span class="text-sm text-slate-700 dark:text-slate-200">
                                {{ cell.warehouse?.code || '—' }} - {{ cell.warehouse?.name || '—' }}
                            </span>
                        </td>
                        <td class="p-4 align-middle text-right">
                            <span class="font-semibold text-sm text-slate-900 dark:text-white">
                                {{ cell.book_stats?.quantity_total ?? 0 }}/{{ Number(cell.params?.books_per_rack || 30) }}
                            </span>
                        </td>
                        <td class="p-4 align-middle">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="cell.is_active
                                    ? (cell.book_stats?.has_stock ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300')
                                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                            >
                                {{ cell.is_active && cell.book_stats?.has_stock ? 'Còn' : 'Trống' }}
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <div class="flex flex-nowrap justify-start gap-0.5">
                                <AdminTableActionIcon icon="lucide:eye" title="Xem" @click="emit('view', cell)" />
                                <AdminTableActionIcon icon="lucide:pencil" title="Sửa" tone="slate" @click="emit('edit', cell)" />
                                <AdminTableActionIcon icon="lucide:trash-2" tone="rose" title="Xóa" @click="emit('delete', cell)" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="loading" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Đang tải...</p>
        <p v-else-if="rows.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Không có ô kệ sách nào.</p>
    </div>
</template>
