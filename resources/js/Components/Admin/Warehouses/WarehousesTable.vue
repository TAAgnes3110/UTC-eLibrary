<script setup>
import { Icon } from '@iconify/vue';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';

defineProps({
    rows: { type: Array, required: true },
    selectedIds: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    isAllSelected: { type: Boolean, required: true },
    hasSelection: { type: Boolean, required: true },
    formatDateTime: { type: Function, required: true },
    statusLabel: { type: Function, required: true },
    statusClass: { type: Function, required: true },
});

const emit = defineEmits(['toggle-all', 'toggle', 'view', 'edit', 'delete']);

function normalizedWarehouseName(warehouse) {
    const rawName = String(warehouse?.name || '').trim();
    const fromParams = String(warehouse?.params?.display_name || '').trim();
    if (fromParams) return fromParams;
    const idx = rawName.indexOf('(');
    if (idx > 0) return rawName.slice(0, idx).trim();
    return rawName;
}

function warehouseNote(warehouse) {
    const fromParams = String(warehouse?.params?.note || '').trim();
    if (fromParams) return fromParams;
    const rawName = String(warehouse?.name || '').trim();
    const start = rawName.indexOf('(');
    const end = rawName.lastIndexOf(')');
    if (start >= 0 && end > start) return rawName.slice(start + 1, end).trim();
    return '';
}
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left border-collapse">
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
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Mã kho</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Tên kho</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Notes</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Cập nhật gần nhất</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Trạng thái</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300 w-[88px]">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr
                        v-for="w in rows"
                        :key="w.id"
                        :class="[selectedIds.includes(w.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                    >
                        <td class="p-4 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(w.id)"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle', w.id)"
                                />
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">{{ w.code }}</p>
                        </td>
                        <td class="p-4 align-middle max-w-[280px]">
                            <p class="font-semibold text-sm text-slate-900 dark:text-white truncate" :title="normalizedWarehouseName(w)">
                                {{ normalizedWarehouseName(w) }}
                            </p>
                        </td>
                        <td class="p-4 align-middle max-w-[220px]">
                            <p class="text-sm text-slate-600 dark:text-slate-300 truncate" :title="warehouseNote(w) || '—'">
                                {{ warehouseNote(w) || '—' }}
                            </p>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300 tabular-nums">
                                {{ formatDateTime(w.updated_at || w.created_at) }}
                            </p>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <span
                                :class="[
                                    statusClass(w.is_active),
                                    'inline-flex whitespace-nowrap px-2 py-0.5 rounded text-[11px] font-semibold',
                                ]"
                            >
                                {{ statusLabel(w.is_active) }}
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <div class="flex flex-nowrap justify-start gap-0.5">
                                <AdminTableActionIcon
                                    icon="lucide:eye"
                                    title="Xem chi tiết"
                                    @click="emit('view', w)"
                                />
                                <AdminTableActionIcon
                                    icon="lucide:pencil"
                                    title="Chỉnh sửa"
                                    @click="emit('edit', w)"
                                />
                                <AdminTableActionIcon
                                    icon="lucide:trash-2"
                                    tone="rose"
                                    title="Xóa"
                                    @click="emit('delete', w)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="loading" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Đang tải...</p>
        <p v-else-if="rows.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Không có kho sách nào.</p>
    </div>
</template>
