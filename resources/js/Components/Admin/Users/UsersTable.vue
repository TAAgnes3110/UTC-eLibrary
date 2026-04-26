<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import { getRoleInfo, getStatusInfo } from '@/config/enums';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';

defineProps({
    rows: { type: Array, required: true },
    selectedIds: { type: Array, required: true },
    loadingFallback: { type: Boolean, default: false },
    isAllSelected: { type: Boolean, required: true },
    hasSelection: { type: Boolean, required: true },
    formatDateTime: { type: Function, required: true },
});

const emit = defineEmits(['toggle-all', 'toggle', 'edit', 'toggle-status', 'delete', 'avatar']);

const previewUser = ref(null);
const showPreviewModal = ref(false);

function openAvatarPreview(user) {
    previewUser.value = user;
    showPreviewModal.value = true;
}

function closeAvatarPreview() {
    showPreviewModal.value = false;
    previewUser.value = null;
}

function triggerAvatarChange() {
    if (!previewUser.value) return;
    emit('avatar', previewUser.value);
    closeAvatarPreview();
}
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left border-collapse">
                <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th class="py-2 pl-3 pr-1 w-10 align-middle">
                            <span class="admin-table-checkbox-wrap--compact">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    :indeterminate="hasSelection && !isAllSelected"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle-all')"
                                />
                            </span>
                        </th>
                        <th class="py-2 px-2 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Mã</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Ảnh đại diện</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Họ tên</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Email</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Số điện thoại</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Phân quyền</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Ngày cập nhật</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Trạng thái</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300 w-[108px]">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr
                        v-for="user in rows"
                        :key="user.id"
                        :class="[selectedIds.includes(user.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                    >
                        <td class="py-2 pl-3 pr-1 align-middle">
                            <span class="admin-table-checkbox-wrap--compact">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(user.id)"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle', user.id)"
                                />
                            </span>
                        </td>
                        <td class="py-2 px-2 align-middle whitespace-nowrap">
                            <p class="font-mono text-[11px] leading-tight text-slate-700 dark:text-slate-300">
                                {{ user.code }}
                            </p>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap w-[86px]">
                            <div class="flex flex-col items-start gap-1.5">
                                <button
                                    type="button"
                                    class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold text-sm shrink-0 overflow-hidden ring-1 ring-slate-200/80 dark:ring-slate-700/80"
                                    :title="`Xem ảnh đại diện của ${user.name || 'người dùng'}`"
                                    @click.stop="openAvatarPreview(user)"
                                >
                                    <img
                                        :src="user.avatar || '/images/default-avatar.png'"
                                        :alt="user.name || 'Avatar'"
                                        class="h-full w-full object-cover"
                                    />
                                </button>
                            </div>
                        </td>
                        <td class="p-4 align-middle max-w-[200px] xl:max-w-[260px]">
                            <p class="font-semibold text-sm text-slate-900 dark:text-white truncate" :title="user.name">
                                {{ user.name }}
                            </p>
                        </td>
                        <td class="p-4 align-middle max-w-[220px] xl:max-w-[280px]">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300 truncate" :title="user.email">
                                {{ user.email }}
                            </p>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300">
                                {{ user.phone || '—' }}
                            </p>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <span
                                :class="[
                                    getRoleInfo(user.role).class,
                                    'inline-flex whitespace-nowrap px-2 py-0.5 rounded text-[11px] font-semibold',
                                ]"
                            >
                                {{ getRoleInfo(user.role).label }}
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300 tabular-nums">
                                {{ formatDateTime(user.updated_at || user.created_at) }}
                            </p>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <span
                                :class="[
                                    getStatusInfo(user.status).class,
                                    'inline-flex whitespace-nowrap px-2 py-0.5 rounded text-[11px] font-semibold',
                                ]"
                            >
                                {{ getStatusInfo(user.status).label }}
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <div class="flex flex-nowrap justify-start gap-0.5">
                                <AdminTableActionIcon
                                    icon="lucide:pencil"
                                    title="Chỉnh sửa"
                                    @click="emit('edit', user)"
                                />
                                <AdminTableActionIcon
                                    :icon="user.status === 'active' ? 'lucide:user-x' : 'lucide:user-check'"
                                    :tone="user.status === 'active' ? 'rose' : 'emerald'"
                                    :title="user.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa'"
                                    @click="emit('toggle-status', user)"
                                />
                                <AdminTableActionIcon
                                    icon="lucide:trash-2"
                                    tone="rose"
                                    title="Xóa"
                                    @click="emit('delete', user)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="loadingFallback" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Đang tải...</p>
        <p v-else-if="rows.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Không có tài khoản nào.</p>
    </div>

    <div v-if="showPreviewModal && previewUser" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60" @click="closeAvatarPreview" />
        <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ảnh đại diện</h4>
                <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeAvatarPreview">
                    <Icon icon="lucide:x" class="h-4 w-4" />
                </button>
            </div>
            <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                <img
                    :src="previewUser.avatar || '/images/default-avatar.png'"
                    :alt="previewUser.name || 'Avatar'"
                    class="h-[320px] w-full object-contain bg-slate-50 dark:bg-slate-800"
                />
            </div>
            <div class="mt-3 flex justify-end">
                <button
                    type="button"
                    class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/35 dark:text-blue-300 dark:hover:bg-blue-900/50"
                    @click="triggerAvatarChange"
                >
                    <Icon icon="lucide:camera" class="h-3.5 w-3.5" />
                    Đổi ảnh
                </button>
            </div>
        </div>
    </div>
</template>
