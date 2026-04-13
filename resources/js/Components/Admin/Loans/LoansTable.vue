<script setup>
import { Icon } from '@iconify/vue';
import { formatDate } from '@/utils/index.js';

defineProps({
    rows: { type: Array, required: true },
    selectedIds: { type: Array, required: true },
    isAllSelected: { type: Boolean, required: true },
    hasSelection: { type: Boolean, required: true },
    loadingFallback: { type: Boolean, default: false },
    emptyText: { type: String, default: 'Chưa có phiếu mượn nào.' },
});

const emit = defineEmits(['toggle-select-all', 'toggle-select', 'show', 'edit', 'return', 'delete']);

function formatLoanDate(value) {
    return formatDate(value, 'DD/MM/YYYY');
}

function formatReturnCell(value) {
    if (value == null || value === '') {
        return 'Chưa trả';
    }
    return formatLoanDate(value);
}

function isBorrowedOpen(row) {
    return row.status === 'da_muon';
}

function isOverdue(row) {
    return row.status === 'qua_han';
}

function isOpenLoan(row) {
    return isBorrowedOpen(row) || isOverdue(row);
}

function canEdit(row) {
    return isOpenLoan(row);
}

function canReturn(row) {
    return isOpenLoan(row);
}

/** Xóa được mọi trạng thái hiển thị trên danh sách (đã trả: backend không cộng tồn kho lại). */
function canDelete(row) {
    return ['da_muon', 'qua_han', 'da_tra'].includes(row.status);
}

/** Giống UsersTable: badge góc nhỏ, chữ 11px, không pill tròn */
function statusBadgeClass(row) {
    if (row.status === 'da_tra') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200';
    }
    if (row.status === 'qua_han') {
        return 'bg-amber-100 text-amber-900 dark:bg-amber-900/45 dark:text-amber-100';
    }
    return 'bg-sky-100 text-sky-900 dark:bg-sky-900/45 dark:text-sky-100';
}
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-0 text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="p-3 w-11 align-middle">
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
                        <th
                            class="p-3 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300"
                        >
                            Mã phiếu
                        </th>
                        <th
                            class="p-3 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300"
                        >
                            Mã thẻ
                        </th>
                        <th
                            class="p-3 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 min-w-[132px]"
                        >
                            Tên độc giả
                        </th>
                        <th
                            class="p-3 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 hidden xl:table-cell"
                        >
                            Người tạo
                        </th>
                        <th
                            class="p-3 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300"
                        >
                            Ngày mượn
                        </th>
                        <th
                            class="p-3 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 min-w-[6.5rem]"
                        >
                            Hạn trả
                        </th>
                        <th
                            class="p-3 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 min-w-[6.5rem] hidden 2xl:table-cell"
                        >
                            Ngày trả
                        </th>
                        <th
                            class="p-3 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 min-w-[6.5rem]"
                        >
                            Trạng thái
                        </th>
                        <th
                            class="p-2 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 w-[1%] min-w-[148px]"
                        >
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        :class="[
                            selectedIds.includes(row.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row',
                        ]"
                    >
                        <td class="p-3 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(row.id)"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle-select', row.id)"
                                />
                            </span>
                        </td>
                        <td class="p-3 align-middle whitespace-nowrap">
                            <p class="font-mono text-[12px] font-semibold text-slate-800 dark:text-slate-100">
                                {{ row.loan_code || `#${row.id}` }}
                            </p>
                        </td>
                        <td class="p-3 align-middle whitespace-nowrap text-[12px] text-slate-700 dark:text-slate-300">
                            {{ row.library_card_number || '—' }}
                        </td>
                        <td
                            class="p-3 align-middle text-[12px] text-slate-800 dark:text-slate-100 max-w-[150px] truncate"
                            :title="row.library_card_name"
                        >
                            {{ row.library_card_name || '—' }}
                        </td>
                        <td
                            class="p-3 align-middle whitespace-nowrap text-[12px] text-slate-600 dark:text-slate-400 hidden xl:table-cell max-w-[128px] truncate"
                        >
                            {{ row.created_by_name || '—' }}
                        </td>
                        <td class="p-3 align-middle whitespace-nowrap text-[12px] tabular-nums text-slate-700 dark:text-slate-300">
                            {{ formatLoanDate(row.loan_date) }}
                        </td>
                        <td
                            class="p-3 align-middle whitespace-nowrap text-[12px] tabular-nums text-slate-700 dark:text-slate-300 min-w-[6.5rem]"
                        >
                            {{ formatLoanDate(row.due_date) }}
                        </td>
                        <td
                            class="p-3 align-middle whitespace-nowrap text-[12px] tabular-nums text-slate-700 dark:text-slate-300 min-w-[6.5rem] hidden 2xl:table-cell"
                        >
                            <span :class="!row.return_date ? 'text-amber-700 dark:text-amber-300 font-medium' : ''">
                                {{ formatReturnCell(row.return_date) }}
                            </span>
                        </td>
                        <td class="p-3 align-middle whitespace-nowrap">
                            <span
                                :class="[
                                    statusBadgeClass(row),
                                    'inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-sm text-[11px] font-semibold leading-tight',
                                ]"
                            >
                                {{ row.status_label || row.status }}
                            </span>
                        </td>
                        <td class="p-1.5 sm:p-2 align-middle">
                            <div
                                class="grid grid-cols-2 gap-1 w-full max-w-[148px]"
                                role="group"
                                aria-label="Thao tác phiếu mượn"
                            >
                                <button
                                    type="button"
                                    class="loan-action-btn border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                    title="Xem chi tiết"
                                    @click="emit('show', row.id)"
                                >
                                    <Icon icon="lucide:eye" class="w-4 h-4 shrink-0" />
                                    <span class="loan-action-btn__label">Xem</span>
                                </button>
                                <button
                                    type="button"
                                    class="loan-action-btn border-blue-200 bg-blue-50/90 text-blue-800 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-200 dark:hover:bg-blue-900/40 disabled:opacity-40 disabled:pointer-events-none"
                                    title="Sửa hạn trả"
                                    :disabled="!canEdit(row)"
                                    @click="emit('edit', row.id)"
                                >
                                    <Icon icon="lucide:pencil" class="w-4 h-4 shrink-0" />
                                    <span class="loan-action-btn__label">Sửa</span>
                                </button>
                                <button
                                    type="button"
                                    class="loan-action-btn border-emerald-200 bg-emerald-50/90 text-emerald-900 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/45 dark:text-emerald-100 dark:hover:bg-emerald-900/35 disabled:opacity-40 disabled:pointer-events-none"
                                    title="Trả sách"
                                    :disabled="!canReturn(row)"
                                    @click="emit('return', row.id)"
                                >
                                    <Icon icon="lucide:book-check" class="w-4 h-4 shrink-0" />
                                    <span class="loan-action-btn__label">Trả</span>
                                </button>
                                <button
                                    type="button"
                                    class="loan-action-btn border-rose-200 bg-rose-50/90 text-rose-800 hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/35 dark:text-rose-200 dark:hover:bg-rose-900/30 disabled:opacity-40 disabled:pointer-events-none"
                                    title="Xóa phiếu"
                                    :disabled="!canDelete(row)"
                                    @click="emit('delete', row.id)"
                                >
                                    <Icon icon="lucide:trash-2" class="w-4 h-4 shrink-0" />
                                    <span class="loan-action-btn__label">Xóa</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="loadingFallback" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Đang tải...</p>
        <p v-else-if="rows.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">
            {{ emptyText }}
        </p>
    </div>
</template>

<style scoped>
/* Hàng 1: Xem | Sửa — Hàng 2: Trả | Xóa; icon + chữ một dòng trong ô */
.loan-action-btn {
    @apply inline-flex flex-row items-center justify-center gap-1 border rounded-sm px-1.5 py-1.5 min-h-[40px] w-full text-[10px] font-semibold leading-tight transition-colors;
}
.loan-action-btn__label {
    @apply shrink-0;
}
</style>
