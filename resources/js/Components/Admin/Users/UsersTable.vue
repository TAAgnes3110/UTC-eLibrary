<script setup>
import { Icon } from '@iconify/vue';
import { getRoleInfo, getStatusInfo } from '@/config/enums';

defineProps({
    rows: { type: Array, required: true },
    selectedIds: { type: Array, required: true },
    loadingFallback: { type: Boolean, default: false },
    isAllSelected: { type: Boolean, required: true },
    hasSelection: { type: Boolean, required: true },
    formatDateTime: { type: Function, required: true },
});

const emit = defineEmits(['toggle-all', 'toggle', 'edit', 'toggle-status', 'delete', 'avatar']);
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
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ảnh đại diện</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Họ tên</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Email</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Số điện thoại</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Phân quyền</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ngày cập nhật</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr
                        v-for="user in rows"
                        :key="user.id"
                        :class="[selectedIds.includes(user.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                    >
                        <td class="p-4">
                            <input
                                type="checkbox"
                                :checked="selectedIds.includes(user.id)"
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                @change="emit('toggle', user.id)"
                            />
                        </td>
                        <td class="p-4">
                            <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">
                                {{ user.code }}
                            </p>
                        </td>
                        <td class="p-4">
                            <div
                                class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold text-sm shrink-0 overflow-hidden relative group/avatar"
                            >
                                <img
                                    :src="user.avatar || '/images/default-avatar.png'"
                                    :alt="user.name || 'Avatar'"
                                    class="h-full w-full object-cover"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover/avatar:opacity-100 transition-opacity flex items-center justify-center rounded-lg cursor-pointer"
                                    title="Cập nhật ảnh đại diện"
                                    @click.stop="emit('avatar', user)"
                                >
                                    <Icon icon="lucide:camera" class="w-4 h-4 text-white" />
                                </button>
                            </div>
                        </td>
                        <td class="p-4">
                            <p class="font-semibold text-sm text-slate-900 dark:text-white truncate">
                                {{ user.name }}
                            </p>
                        </td>
                        <td class="p-4">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300 truncate">
                                {{ user.email }}
                            </p>
                        </td>
                        <td class="p-4">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300">
                                {{ user.phone || '—' }}
                            </p>
                        </td>
                        <td class="p-4">
                            <span :class="[getRoleInfo(user.role).class, 'px-2 py-0.5 rounded text-[11px] font-semibold']">
                                {{ getRoleInfo(user.role).label }}
                            </span>
                        </td>
                        <td class="p-4">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300">
                                {{ formatDateTime(user.updated_at || user.created_at) }}
                            </p>
                        </td>
                        <td class="p-4">
                            <span :class="[getStatusInfo(user.status).class, 'px-2 py-0.5 rounded text-[11px] font-semibold']">
                                {{ getStatusInfo(user.status).label }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex justify-end gap-0.5">
                                <button
                                    type="button"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                    title="Chỉnh sửa"
                                    @click="emit('edit', user)"
                                >
                                    <Icon icon="lucide:pencil" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    :class="
                                        user.status === 'active'
                                            ? 'text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20'
                                            : 'text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'
                                    "
                                    class="p-1.5 rounded-lg transition-colors"
                                    :title="user.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa'"
                                    @click="emit('toggle-status', user)"
                                >
                                    <Icon
                                        :icon="user.status === 'active' ? 'lucide:user-x' : 'lucide:user-check'"
                                        class="w-3.5 h-3.5"
                                    />
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                    title="Xóa"
                                    @click="emit('delete', user)"
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
        <p v-else-if="rows.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Không có tài khoản nào.</p>
    </div>
</template>
