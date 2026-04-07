<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { LIBRARY_CARD_STATUS, workflowQuyTrinhAdminLabel, holderLabel, statusLabel } from '@/config/libraryCardUi';

const props = defineProps({
    rows: { type: Array, required: true },
    selectedIds: { type: Array, required: true },
    loadingFallback: { type: Boolean, default: false },
    isAllSelected: { type: Boolean, required: true },
    hasSelection: { type: Boolean, required: true },
    /** Hiện nút duyệt (kích hoạt thẻ) khi workflow = pending_review */
    showApprove: { type: Boolean, default: false },
    /** Màn quản lý thẻ (đã thanh toán): ẩn cột quy trình */
    showWorkflow: { type: Boolean, default: true },
    /** Màn duyệt yêu cầu: ẩn cột trạng thái thẻ (Hoạt động/Khóa/…) */
    showCardStatus: { type: Boolean, default: true },
    /** Màn duyệt yêu cầu: ẩn ảnh; vẫn có checkbox / chọn tất cả */
    reviewMode: { type: Boolean, default: false },
});

const effectiveShowPhotoColumn = computed(() => !props.reviewMode);

const tableColspan = computed(() => {
    let n = 7;
    if (effectiveShowPhotoColumn.value) {
        n += 1;
    }
    if (props.showWorkflow) {
        n += 1;
    }
    if (props.showCardStatus) {
        n += 1;
    }
    return n;
});

const tableMinWidthClass = computed(() => {
    if (props.reviewMode) {
        return 'min-w-[800px]';
    }
    if (props.showWorkflow && props.showCardStatus) {
        return 'min-w-[1020px]';
    }
    if (props.showWorkflow || props.showCardStatus) {
        return 'min-w-[960px]';
    }
    return 'min-w-[900px]';
});

const emit = defineEmits(['toggle-all', 'toggle', 'edit', 'delete', 'photo', 'lock', 'approve', 'reject']);
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" :class="tableMinWidthClass">
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
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">Mã thẻ</th>
                        <th
                            v-if="effectiveShowPhotoColumn"
                            class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200"
                        >
                            Ảnh thẻ
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">Họ tên</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">Email</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">Số điện thoại</th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">Loại thẻ</th>
                        <th
                            v-if="showWorkflow"
                            class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200"
                        >
                            Quy trình
                        </th>
                        <th
                            v-if="showCardStatus"
                            class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200"
                        >
                            Trạng thái thẻ
                        </th>
                        <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 w-[220px]">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr v-if="loadingFallback">
                        <td :colspan="tableColspan" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Đang tải…</td>
                    </tr>
                    <tr v-else-if="!rows.length">
                        <td :colspan="tableColspan" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Không có bản ghi.</td>
                    </tr>
                    <template v-else>
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        :class="[selectedIds.includes(row.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                    >
                        <td class="p-4 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(row.id)"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle', row.id)"
                                />
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">
                                {{ row.card_number || row.code || '—' }}
                            </p>
                        </td>
                        <td v-if="effectiveShowPhotoColumn" class="p-4 align-middle">
                            <div
                                class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden relative group/photo shrink-0"
                            >
                                <img
                                    :src="row.photo_url || '/images/default-avatar.png'"
                                    :alt="row.full_name || 'Ảnh thẻ'"
                                    class="h-full w-full object-cover"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover/photo:opacity-100 transition-opacity flex items-center justify-center rounded-lg cursor-pointer min-w-[44px] min-h-[44px]"
                                    title="Cập nhật ảnh thẻ"
                                    @click.stop="emit('photo', row)"
                                >
                                    <Icon icon="lucide:camera" class="w-4 h-4 text-white" />
                                </button>
                            </div>
                        </td>
                        <td class="p-4 align-middle max-w-[200px] xl:max-w-[260px]">
                            <p class="font-semibold text-sm text-slate-900 dark:text-white truncate" :title="row.full_name">
                                {{ row.full_name }}
                            </p>
                        </td>
                        <td class="p-4 align-middle max-w-[220px] xl:max-w-[280px]">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300 truncate" :title="row.email">{{ row.email }}</p>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <p class="text-[12px] text-slate-600 dark:text-slate-300">{{ row.phone || '—' }}</p>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <span class="text-[12px] text-slate-700 dark:text-slate-200">{{ holderLabel(row.holder_type) }}</span>
                        </td>
                        <td v-if="showWorkflow" class="p-4 align-middle whitespace-nowrap">
                            <span class="text-[12px] text-slate-600 dark:text-slate-300">{{ workflowQuyTrinhAdminLabel(row.workflow_status) }}</span>
                        </td>
                        <td v-if="showCardStatus" class="p-4 align-middle">
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold whitespace-nowrap',
                                    LIBRARY_CARD_STATUS[Number(row.status)]?.class ?? 'bg-slate-500 dark:bg-slate-600 text-white',
                                ]"
                            >
                                {{ statusLabel(row.status) }}
                            </span>
                        </td>
                        <td class="p-4 align-middle whitespace-nowrap">
                            <div class="flex flex-nowrap justify-start gap-1">
                                <template v-if="reviewMode">
                                    <button
                                        v-if="showApprove && row.workflow_status === 'pending_review'"
                                        type="button"
                                        class="min-h-[38px] inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-2.5 py-1.5 text-[12px] font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50 transition-colors"
                                        title="Đồng ý — kích hoạt thẻ"
                                        @click="emit('approve', row)"
                                    >
                                        <Icon icon="lucide:check-circle-2" class="w-3.5 h-3.5 shrink-0" />
                                        <span class="leading-none">Đồng ý</span>
                                    </button>
                                    <button
                                        v-if="showApprove && row.workflow_status === 'pending_review'"
                                        type="button"
                                        class="min-h-[38px] inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-2.5 py-1.5 text-[12px] font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45 transition-colors"
                                        title="Từ chối hồ sơ"
                                        @click="emit('reject', row)"
                                    >
                                        <Icon icon="lucide:x-circle" class="w-3.5 h-3.5 shrink-0" />
                                        <span class="leading-none">Từ chối</span>
                                    </button>
                                </template>
                                <template v-else>
                                <button
                                    v-if="showApprove && row.workflow_status === 'pending_review'"
                                    type="button"
                                    class="min-h-[36px] min-w-[36px] inline-flex items-center justify-center rounded-lg p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/25 transition-colors"
                                    title="Đồng ý — kích hoạt thẻ"
                                    @click="emit('approve', row)"
                                >
                                    <Icon icon="lucide:check-circle" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="min-h-[36px] min-w-[36px] inline-flex items-center justify-center rounded-lg p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                    title="Chỉnh sửa"
                                    @click="emit('edit', row)"
                                >
                                    <Icon icon="lucide:pencil" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="min-h-[36px] min-w-[36px] inline-flex items-center justify-center rounded-lg p-1.5 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                                    :title="Number(row.status) === 3 ? 'Mở khóa thẻ' : 'Khóa thẻ'"
                                    @click="emit('lock', row)"
                                >
                                    <Icon :icon="Number(row.status) === 3 ? 'lucide:lock-open' : 'lucide:lock'" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="min-h-[36px] min-w-[36px] inline-flex items-center justify-center rounded-lg p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"
                                    title="Xóa mềm"
                                    @click="emit('delete', row)"
                                >
                                    <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                                </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>
