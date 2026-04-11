<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    rows: { type: Array, required: true },
    loadingFallback: { type: Boolean, default: false },
    emptyText: { type: String, default: 'Chưa có phiếu mượn nào.' },
});

const emit = defineEmits(['show', 'edit', 'return', 'delete']);
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left border-collapse">
                <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                            Mã phiếu
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                            Mã thẻ
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                            Tên độc giả
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                            Người tạo
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                            Ngày mượn
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                            Ngày hẹn trả
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                            Ngày trả
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                            Trạng thái
                        </th>
                        <th
                            class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300 w-[200px]"
                        >
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr v-for="row in rows" :key="row.id" class="admin-table-row">
                        <td class="p-4 align-middle whitespace-nowrap">
                            <span class="font-mono text-sm font-medium text-slate-800 dark:text-slate-200">
                                {{ row.loan_code || `#${row.id}` }}
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                            {{ row.library_card_number || '—' }}
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap text-sm text-slate-800 dark:text-slate-200">
                            {{ row.library_card_name || '—' }}
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                            {{ row.created_by_name || '—' }}
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                            {{ row.loan_date || '—' }}
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                            {{ row.due_date || '—' }}
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                            {{ row.return_date || 'Chưa trả' }}
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                {{ row.status_label || row.status }}
                            </span>
                        </td>
                        <td class="p-4 align-middle">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                    title="Xem"
                                    @click="emit('show', row.id)"
                                >
                                    <Icon icon="lucide:eye" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                    title="Sửa hạn trả"
                                    @click="emit('edit', row.id)"
                                >
                                    <Icon icon="lucide:pencil" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors"
                                    title="Trả sách"
                                    @click="emit('return', row.id)"
                                >
                                    <Icon icon="lucide:book-check" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"
                                    title="Xóa"
                                    @click="emit('delete', row.id)"
                                >
                                    <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="loadingFallback" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Đang tải...</p>
        <p v-else-if="rows.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">{{ emptyText }}</p>
    </div>
</template>
