<script setup>
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';
import { LIBRARY_CARD_STATUS, workflowLabel, holderLabel, statusLabel } from '@/config/libraryCardUi';
import { libraryCardWorkflowOrValidityCell } from '@/utils/libraryCardValidity';
import { useImageFallback } from '@/composables/useImageFallback';

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
    let n = 8;
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

const HOLDER_SHORT_LABELS = {
    student: 'SV',
    teacher: 'GV',
    external: 'Ngoài',
};

const emit = defineEmits(['toggle-all', 'toggle', 'edit', 'delete', 'photo', 'lock', 'approve', 'reject', 'detail', 'confirm-pickup']);

const previewCard = ref(null);
const showPreviewModal = ref(false);
const { withFallback } = useImageFallback();

function openPhotoPreview(card) {
    previewCard.value = card;
    showPreviewModal.value = true;
}

function closePhotoPreview() {
    showPreviewModal.value = false;
    previewCard.value = null;
}

function triggerPhotoChange() {
    if (!previewCard.value) return;
    emit('photo', previewCard.value);
    closePhotoPreview();
}

function formatDateOnly(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '—';
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(d);
}

function holderShortLabel(key) {
    return HOLDER_SHORT_LABELS[key] ?? holderLabel(key);
}

</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto lg:overflow-x-visible">
            <table class="w-full table-fixed text-left border-collapse">
                <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th class="w-10 py-2 pl-2 pr-1 align-middle text-left">
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
                        <th class="w-[9%] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200">Mã thẻ</th>
                        <th
                            v-if="effectiveShowPhotoColumn"
                            class="w-11 px-1 py-2 align-middle text-center text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200"
                        >
                            Ảnh
                        </th>
                        <th class="w-[11%] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200">Họ tên</th>
                        <th class="w-[14%] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200">Email</th>
                        <th class="w-[10%] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200">SĐT</th>
                        <th class="w-[6%] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200">Loại</th>
                        <th class="w-[9%] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200">
                            Ngày tạo
                        </th>
                        <th
                            v-if="showWorkflow"
                            class="w-[12%] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200"
                        >
                            Hiệu lực
                        </th>
                        <th
                            v-if="showCardStatus"
                            class="w-[8%] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200"
                        >
                            Trạng thái
                        </th>
                        <th class="w-[128px] px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-200">Thao tác</th>
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
                        <td class="py-2 pl-2 pr-1 align-middle">
                            <span class="admin-table-checkbox-wrap--compact">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(row.id)"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle', row.id)"
                                />
                            </span>
                        </td>
                        <td class="px-2 py-2 align-middle overflow-hidden">
                            <span class="block font-mono text-[11px] leading-tight text-slate-700 dark:text-slate-300 truncate" :title="row.card_number || row.code">
                                {{ row.card_number || row.code || '—' }}
                            </span>
                        </td>
                        <td v-if="effectiveShowPhotoColumn" class="px-1 py-2 align-middle text-center">
                            <button
                                type="button"
                                class="mx-auto w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden shrink-0 ring-1 ring-slate-200/80 dark:ring-slate-700/80"
                                :title="`Xem ảnh thẻ: ${row.full_name || 'Bạn đọc'}`"
                                @click.stop="openPhotoPreview(row)"
                            >
                                <img
                                    :src="row.photo_url || '/images/default-avatar.png'"
                                    :alt="row.full_name || 'Ảnh thẻ'"
                                    @error="withFallback('/images/default-avatar.png')($event)"
                                    class="h-full w-full object-cover"
                                />
                            </button>
                        </td>
                        <td class="px-2 py-2 align-middle overflow-hidden">
                            <span class="block font-semibold text-[12px] leading-tight text-slate-900 dark:text-white truncate" :title="row.full_name">
                                {{ row.full_name }}
                            </span>
                        </td>
                        <td class="px-2 py-2 align-middle overflow-hidden">
                            <span class="block text-[11px] leading-tight text-slate-600 dark:text-slate-300 truncate" :title="row.email">{{ row.email }}</span>
                        </td>
                        <td class="px-2 py-2 align-middle overflow-hidden">
                            <span class="block text-[11px] leading-tight text-slate-600 dark:text-slate-300 truncate tabular-nums" :title="row.phone">{{ row.phone || '—' }}</span>
                        </td>
                        <td class="px-2 py-2 align-middle">
                            <span
                                class="block text-[11px] leading-tight font-medium text-slate-700 dark:text-slate-200"
                                :title="holderLabel(row.holder_type)"
                            >{{ holderShortLabel(row.holder_type) }}</span>
                        </td>
                        <td class="px-2 py-2 align-middle">
                            <span class="block text-[11px] leading-tight text-slate-600 dark:text-slate-300 tabular-nums whitespace-nowrap">
                                {{ formatDateOnly(row.created_at) }}
                            </span>
                        </td>
                        <td v-if="showWorkflow" class="px-2 py-2 align-middle overflow-hidden">
                            <span
                                class="block text-[11px] leading-tight text-slate-600 dark:text-slate-300 tabular-nums truncate"
                                :title="libraryCardWorkflowOrValidityCell(row, workflowLabel)"
                            >
                                {{ libraryCardWorkflowOrValidityCell(row, workflowLabel) }}
                            </span>
                        </td>
                        <td v-if="showCardStatus" class="px-2 py-2 align-middle">
                            <span
                                :class="[
                                    'inline-flex max-w-full items-center px-1.5 py-0.5 rounded text-[10px] font-semibold',
                                    LIBRARY_CARD_STATUS[Number(row.status)]?.class ?? 'bg-slate-500 dark:bg-slate-600 text-white',
                                ]"
                                :title="statusLabel(row.status)"
                            >
                                {{ statusLabel(row.status) }}
                            </span>
                        </td>
                        <td class="px-2 py-2 align-middle">
                            <div class="flex flex-nowrap items-center justify-start gap-0.5">
                                <template v-if="reviewMode">
                                    <button
                                        v-if="showApprove && row.workflow_status === 'pending_review'"
                                        type="button"
                                        class="min-h-[38px] inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-slate-50 px-2.5 py-1.5 text-[12px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                                        title="Xem chi tiết hồ sơ"
                                        @click="emit('detail', row)"
                                    >
                                        <Icon icon="lucide:eye" class="w-3.5 h-3.5 shrink-0" />
                                        <span class="leading-none">Chi tiết</span>
                                    </button>
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
                                    v-if="row.workflow_status === 'pending_pickup'"
                                    type="button"
                                    class="min-h-[32px] min-w-[32px] inline-flex items-center justify-center rounded-md p-1 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/25 transition-colors"
                                    title="Xác nhận đã giao thẻ — kích hoạt hiệu lực"
                                    @click="emit('confirm-pickup', row)"
                                >
                                    <Icon icon="lucide:package-check" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    v-if="showApprove && row.workflow_status === 'pending_review'"
                                    type="button"
                                    class="min-h-[32px] min-w-[32px] inline-flex items-center justify-center rounded-md p-1 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/25 transition-colors"
                                    title="Đồng ý — kích hoạt thẻ"
                                    @click="emit('approve', row)"
                                >
                                    <Icon icon="lucide:check-circle" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="min-h-[32px] min-w-[32px] inline-flex items-center justify-center rounded-md p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                    title="Chỉnh sửa"
                                    @click="emit('edit', row)"
                                >
                                    <Icon icon="lucide:pencil" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="min-h-[32px] min-w-[32px] inline-flex items-center justify-center rounded-md p-1 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                                    :title="Number(row.status) === 3 ? 'Mở khóa thẻ' : 'Khóa thẻ'"
                                    @click="emit('lock', row)"
                                >
                                    <Icon :icon="Number(row.status) === 3 ? 'lucide:lock-open' : 'lucide:lock'" class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="min-h-[32px] min-w-[32px] inline-flex items-center justify-center rounded-md p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"
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
    <div v-if="showPreviewModal && previewCard" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60" @click="closePhotoPreview" />
        <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ảnh thẻ thư viện</h4>
                <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closePhotoPreview">
                    <Icon icon="lucide:x" class="h-4 w-4" />
                </button>
            </div>
            <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                <img
                    :src="previewCard.photo_url || '/images/default-avatar.png'"
                    :alt="previewCard.full_name || 'Ảnh thẻ'"
                    @error="withFallback('/images/default-avatar.png')($event)"
                    class="h-[320px] w-full object-contain bg-slate-50 dark:bg-slate-800"
                />
            </div>
            <div class="mt-3 flex justify-end">
                <button
                    type="button"
                    class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/35 dark:text-blue-300 dark:hover:bg-blue-900/50"
                    @click="triggerPhotoChange"
                >
                    <Icon icon="lucide:camera" class="h-3.5 w-3.5" />
                    Đổi ảnh
                </button>
            </div>
        </div>
    </div>
</template>
