<script setup>
import { Icon } from '@iconify/vue';

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

const emit = defineEmits(['toggle-all', 'toggle', 'edit', 'delete']);
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th class="p-4 w-12">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                :indeterminate="hasSelection && !isAllSelected"
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                @change="emit('toggle-all')"
                            />
                        </th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã kho</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên kho</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Cập nhật gần nhất</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr
                        v-for="w in rows"
                        :key="w.id"
                        :class="[selectedIds.includes(w.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                    >
                        <td class="p-4">
                            <input
                                type="checkbox"
                                :checked="selectedIds.includes(w.id)"
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                @change="emit('toggle', w.id)"
                            />
                        </td>
                        <td class="p-4">
                            <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">{{ w.code }}</p>
                        </td>
                        <td class="p-4">
                            <p class="font-semibold text-sm text-slate-900 dark:text-white">{{ w.name }}</p>
                        </td>
                        <td class="p-4">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300">
                                {{ formatDateTime(w.updated_at || w.created_at) }}
                            </p>
                        </td>
                        <td class="p-4">
                            <span :class="[statusClass(w.is_active), 'px-2 py-0.5 rounded text-[11px] font-semibold']">
                                {{ statusLabel(w.is_active) }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex justify-end gap-0.5">
                                <button
                                    type="button"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                    title="Chỉnh sửa"
                                    @click="emit('edit', w)"
                                >
                                    <Icon icon="lucide:pencil" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                    title="Xóa"
                                    @click="emit('delete', w)"
                                >
                                    <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                                </button>
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
