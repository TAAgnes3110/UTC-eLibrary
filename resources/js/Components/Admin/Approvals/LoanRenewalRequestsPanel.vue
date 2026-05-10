<script setup>
import { router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { computed, onMounted, ref, watch } from 'vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
import { loansApi } from '@/api/loans';
import { extractApiPaginator } from '@/utils/adminPagination';
import { toast } from '@/store/toast';
import { useImageFallback } from '@/composables/useImageFallback';

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const sortBy = ref('newest');
const searchKeyword = ref('');
const showFilterPanel = ref(false);
const searchIn = ref({
    loan_code: true,
    card: true,
    reader: true,
});
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const actionId = ref(null);
const selectedIds = ref([]);
const detailRow = ref(null);
const actionModal = ref({
    show: false,
    mode: 'approve',
    row: null,
    ids: [],
    note: '',
});

const SEARCH_IN_OPTIONS = [
    { key: 'loan_code', label: 'Mã phiếu' },
    { key: 'card', label: 'Mã thẻ / tên thẻ' },
    { key: 'reader', label: 'Bạn đọc' },
];

const sortOptions = [
    { key: 'newest', label: 'Mới nhất' },
    { key: 'oldest', label: 'Cũ nhất' },
];

function statusLabel(s) {
    if (s === 'approved') return 'Đã duyệt';
    if (s === 'rejected') return 'Đã từ chối';
    return 'Chờ duyệt';
}

function statusClass(s) {
    if (s === 'approved') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
    }
    if (s === 'rejected') {
        return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
    }
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
}

function formatDate(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('vi-VN');
}

async function loadRows() {
    loading.value = true;
    try {
        const q = searchKeyword.value.trim();
        const payload = await loansApi.renewalRequests({
            status: 'pending',
            search: q || undefined,
            search_in: selectedSearchIn.value,
            sort: sortBy.value,
            page: page.value,
            per_page: 20,
        });
        const { items, meta: m } = extractApiPaginator(payload, 20);
        rows.value = items;
        meta.value = {
            current_page: m.current_page,
            last_page: m.last_page,
            per_page: m.per_page,
            total: m.total,
        };
        page.value = m.current_page;
        const allowed = new Set(items.map((r) => r.id));
        selectedIds.value = selectedIds.value.filter((id) => allowed.has(id));
    } catch (e) {
        rows.value = [];
        selectedIds.value = [];
        toast.error(e?.response?.data?.messages || 'Không tải được danh sách yêu cầu gia hạn.', { title: 'Gia hạn' });
    } finally {
        loading.value = false;
    }
}

function runSearch() {
    page.value = 1;
    clearSelection();
    loadRows();
}

function goPage(p) {
    page.value = p;
    clearSelection();
    loadRows();
}

watch(sortBy, () => {
    page.value = 1;
    clearSelection();
    loadRows();
});

onMounted(loadRows);

const lastPage = computed(() => Math.max(1, Number(meta.value.last_page) || 1));

const pagination = computed(() => ({
    current_page: meta.value.current_page,
    last_page: meta.value.last_page,
}));
const selectedSearchIn = computed(() => Object.keys(searchIn.value).filter((k) => searchIn.value[k]));
const selectableRows = computed(() => rows.value.filter((r) => r.status === 'pending'));
const hasSelection = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed(
    () => selectableRows.value.length > 0 && selectableRows.value.every((r) => selectedIds.value.includes(r.id)),
);

function toggleSelect(id) {
    const row = rows.value.find((r) => r.id === id);
    if (!row || row.status !== 'pending') {
        return;
    }
    const i = selectedIds.value.indexOf(id);
    if (i === -1) {
        selectedIds.value = [...selectedIds.value, id];
    } else {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    }
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = selectableRows.value.map((r) => r.id);
    }
}

function clearSelection() {
    selectedIds.value = [];
}

function openDetails(row) {
    detailRow.value = row;
}

function closeDetails() {
    detailRow.value = null;
}

const detailProofUrl = computed(() => detailRow.value?.proof_file_url || detailRow.value?.proof_url || detailRow.value?.proof_image_url || '');
const detailProofIsImage = computed(() => /\.(png|jpe?g|webp|gif|bmp|svg)$/i.test(detailProofUrl.value));
const detailProofIsPdf = computed(() => /\.pdf($|\?)/i.test(detailProofUrl.value));
const { withFallback } = useImageFallback();

function formatDateTime(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString('vi-VN');
}

async function approveRow(row) {
    openActionModal(row, 'approve');
}

async function rejectRow(row) {
    openActionModal(row, 'reject');
}

function openActionModal(row, mode) {
    actionModal.value = {
        show: true,
        mode,
        row,
        ids: [],
        note: '',
    };
}

function openBulkActionModal(mode) {
    if (!selectedIds.value.length) {
        return;
    }
    actionModal.value = {
        show: true,
        mode,
        row: null,
        ids: [...selectedIds.value],
        note: '',
    };
}

function closeActionModal() {
    actionModal.value = {
        show: false,
        mode: 'approve',
        row: null,
        ids: [],
        note: '',
    };
}

async function confirmActionModal() {
    const ids = actionModal.value.row ? [actionModal.value.row.id] : [...actionModal.value.ids];
    if (!ids.length) return;
    const mode = actionModal.value.mode;
    const note = actionModal.value.note.trim();
    actionId.value = ids[0];
    try {
        let ok = 0;
        let fail = 0;
        for (const id of ids) {
            try {
                if (mode === 'approve') {
                    await loansApi.approveRenewalRequest(id, { review_note: note || null });
                } else {
                    await loansApi.rejectRenewalRequest(id, { review_note: note || null });
                }
                ok += 1;
            } catch {
                fail += 1;
            }
        }
        if (mode === 'approve') {
            if (ok) {
                toast.success(`Đã duyệt ${ok} yêu cầu.${fail ? ` ${fail} lỗi.` : ''}`, { title: 'Gia hạn' });
            } else {
                toast.error('Không duyệt được yêu cầu nào.', { title: 'Gia hạn' });
            }
        } else if (ok) {
            toast.success(`Đã từ chối ${ok} yêu cầu.${fail ? ` ${fail} lỗi.` : ''}`, { title: 'Gia hạn' });
        } else {
            toast.error('Không từ chối được yêu cầu nào.', { title: 'Gia hạn' });
        }
        clearSelection();
        closeActionModal();
        await loadRows();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể thực hiện thao tác.', { title: 'Gia hạn' });
    } finally {
        actionId.value = null;
    }
}
</script>

<template>
    <div class="space-y-4 animate-in fade-in-50 duration-500">
        <AdminPageHeading title="Duyệt yêu cầu gia hạn mượn">
            <template #description>
                Chỉ hiển thị yêu cầu đang chờ duyệt. Dùng tìm kiếm và sắp xếp để xử lý nhanh hơn.
            </template>
            <template #actions>
                <button
                    type="button"
                    class="admin-filter-btn inline-flex min-h-[44px] items-center gap-2 px-3"
                    @click="router.visit(route('admin.loans.index'))"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                    Danh sách phiếu
                </button>
            </template>
        </AdminPageHeading>

        <AdminFilterSearch
            v-model="searchKeyword"
            search-placeholder="Mã phiếu, mã thẻ, tên bạn đọc..."
            :show-filter-button="false"
            @search="runSearch"
        >
            <template #filters>
                <div class="flex flex-wrap items-center gap-2">
                    <AdminFilterPanel
                        :options="SEARCH_IN_OPTIONS"
                        v-model:model-value="searchIn"
                        :show="showFilterPanel"
                        @update:show="showFilterPanel = $event"
                    />
                    <div class="relative">
                        <select v-model="sortBy" class="admin-filter-select !h-9 !py-0 leading-9 min-w-[128px] w-auto max-w-full pr-9">
                            <option v-for="opt in sortOptions" :key="opt.key" :value="opt.key">
                                {{ opt.label }}
                            </option>
                        </select>
                        <Icon
                            :icon="ADMIN_ICONS.chevronDown"
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                    </div>
                </div>
            </template>
        </AdminFilterSearch>

        <div
            v-if="hasSelection"
            class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200/90 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40"
        >
            <span class="text-sm text-slate-600 dark:text-slate-300">
                Đã chọn <strong>{{ selectedIds.length }}</strong> dòng
            </span>
            <button
                type="button"
                class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                @click="openBulkActionModal('approve')"
            >
                <Icon :icon="ADMIN_ICONS.checkCircle" class="h-4 w-4" />
                Đồng ý đã chọn
            </button>
            <button
                type="button"
                class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                @click="openBulkActionModal('reject')"
            >
                <Icon :icon="ADMIN_ICONS.xCircle" class="h-4 w-4" />
                Từ chối đã chọn
            </button>
            <button
                type="button"
                class="min-h-[44px] px-2 text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                @click="clearSelection"
            >
                Bỏ chọn
            </button>
        </div>

        <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="w-full min-w-0 overflow-x-auto">
                <table class="w-full table-fixed border-collapse text-left text-sm leading-snug">
                    <colgroup>
                        <col class="w-12 shrink-0" />
                        <col class="w-[9%]" />
                        <col class="w-[15%]" />
                        <col class="w-[10%]" />
                        <col class="w-[19%]" />
                        <col class="w-[17%]" />
                        <col class="w-[8%]" />
                        <col class="w-[22%]" />
                    </colgroup>
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/60">
                        <tr>
                            <th class="px-3 py-2.5">
                                <span class="admin-table-checkbox-wrap">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        :disabled="!selectableRows.length || loading"
                                        :indeterminate="hasSelection && !isAllSelected"
                                        class="admin-table-checkbox"
                                        @change="toggleSelectAll"
                                    />
                                </span>
                            </th>
                            <th
                                class="max-w-0 px-2 py-2.5 text-left text-xs font-semibold leading-tight text-slate-600 dark:text-slate-200 sm:text-sm"
                                title="Mã yêu cầu"
                            >
                                <span class="block line-clamp-2">Mã yêu cầu</span>
                            </th>
                            <th class="px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Phiếu mượn
                            </th>
                            <th class="px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Thẻ thư viện
                            </th>
                            <th class="px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Bạn đọc
                            </th>
                            <th class="whitespace-nowrap px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Hạn cũ → mới
                            </th>
                            <th class="whitespace-nowrap px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Trạng thái
                            </th>
                            <th class="px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-if="loading">
                            <td colspan="8" class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">Đang tải…</td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td colspan="8" class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">Không có bản ghi.</td>
                        </tr>
                        <template v-else>
                            <tr v-for="row in rows" :key="row.id" class="admin-table-row">
                                <td class="px-3 py-2 align-middle">
                                    <span v-if="row.status === 'pending'" class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.includes(row.id)"
                                            class="admin-table-checkbox"
                                            @change="toggleSelect(row.id)"
                                        />
                                    </span>
                                    <span v-else class="inline-block w-6" />
                                </td>
                                <td
                                    class="max-w-0 px-2 py-2 align-middle tabular-nums text-slate-800 dark:text-slate-200"
                                    :title="row.request_code || `#${row.id}`"
                                >
                                    <span class="break-words">{{ row.request_code || `#${row.id}` }}</span>
                                </td>
                                <td class="max-w-0 px-3 py-2 align-middle">
                                    <div
                                        class="break-words font-medium leading-snug text-slate-900 dark:text-white"
                                        :title="row.loan?.loan_code || ''"
                                    >
                                        {{ row.loan?.loan_code || '—' }}
                                    </div>
                                </td>
                                <td
                                    class="max-w-0 truncate px-3 py-2 align-middle font-medium tabular-nums text-slate-900 dark:text-white"
                                    :title="row.loan?.library_card_number || ''"
                                >
                                    {{ row.loan?.library_card_number || '—' }}
                                </td>
                                <td class="max-w-0 px-3 py-2 align-middle">
                                    <div
                                        class="truncate whitespace-nowrap text-slate-900 dark:text-white"
                                        :title="row.requester?.name || ''"
                                    >
                                        {{ row.requester?.name || '—' }}
                                    </div>
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 py-2 align-middle tabular-nums text-slate-700 dark:text-slate-300"
                                    :title="`${formatDate(row.current_due_date)} → ${formatDate(row.requested_due_date)}`"
                                >
                                    {{ formatDate(row.current_due_date) }}→{{ formatDate(row.requested_due_date) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 align-middle">
                                    <span
                                        class="inline-flex whitespace-nowrap rounded-md px-2 py-1 text-xs font-semibold leading-tight"
                                        :class="statusClass(row.status)"
                                    >
                                        {{ statusLabel(row.status) }}
                                    </span>
                                </td>
                                <td class="min-w-0 px-1.5 py-2 align-middle">
                                    <div
                                        v-if="row.status === 'pending'"
                                        class="flex flex-nowrap items-center justify-end gap-0.5 sm:justify-start"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex h-9 shrink-0 items-center justify-center gap-0.5 rounded-md border border-slate-300 bg-slate-50 px-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                            title="Xem chi tiết yêu cầu gia hạn"
                                            @click="openDetails(row)"
                                        >
                                            <Icon icon="lucide:eye" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                            <span class="whitespace-nowrap">Chi tiết</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-9 shrink-0 items-center justify-center gap-0.5 rounded-md border border-emerald-300 bg-emerald-50 px-1.5 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            title="Đồng ý — cập nhật hạn trả theo ngày đề xuất"
                                            :disabled="actionId === row.id"
                                            @click="approveRow(row)"
                                        >
                                            <Icon :icon="ADMIN_ICONS.checkCircle" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                            <span class="whitespace-nowrap">Đồng ý</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-9 shrink-0 items-center justify-center gap-0.5 rounded-md border border-rose-300 bg-rose-50 px-1.5 text-xs font-medium text-rose-700 transition-colors hover:bg-rose-100 disabled:opacity-50 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                                            title="Từ chối yêu cầu gia hạn"
                                            :disabled="actionId === row.id"
                                            @click="rejectRow(row)"
                                        >
                                            <Icon :icon="ADMIN_ICONS.xCircle" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                            <span class="whitespace-nowrap">Từ chối</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-sm text-slate-500 dark:text-slate-400">—</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <AdminPaginationBar
            always-show
            :current-page="Number(pagination.current_page)"
            :last-page="Number(lastPage)"
            :disabled="loading"
            @go-page="goPage"
        />

        <p class="flex items-start gap-1.5 text-sm text-slate-500 dark:text-slate-400">
            <Icon icon="lucide:info" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>
                « Đồng ý » cập nhật hạn trả phiếu theo ngày đề xuất; « Từ chối » giữ hạn cũ và đánh dấu yêu cầu đã từ chối.
            </span>
        </p>

        <div v-if="detailRow" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeDetails" />
            <div class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết yêu cầu gia hạn</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Xem đầy đủ thông tin trước khi duyệt hoặc từ chối.</p>
                    </div>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeDetails">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Thông tin yêu cầu</p>
                        <div class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                            <p><span class="text-slate-400">Mã yêu cầu:</span> {{ detailRow.request_code || `#${detailRow.id}` }}</p>
                            <p><span class="text-slate-400">Mã phiếu:</span> {{ detailRow.loan?.loan_code || '—' }}</p>
                            <p><span class="text-slate-400">Ngày yêu cầu:</span> {{ formatDateTime(detailRow.created_at) }}</p>
                            <p><span class="text-slate-400">Hạn hiện tại:</span> {{ formatDate(detailRow.current_due_date) }}</p>
                            <p><span class="text-slate-400">Hạn đề xuất:</span> {{ formatDate(detailRow.requested_due_date) }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Bạn đọc & thẻ</p>
                        <div class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                            <p><span class="text-slate-400">Bạn đọc:</span> {{ detailRow.requester?.name || '—' }}</p>
                            <p><span class="text-slate-400">Mã định danh:</span> {{ detailRow.requester?.code || '—' }}</p>
                            <p><span class="text-slate-400">Mã thẻ:</span> {{ detailRow.loan?.library_card_number || '—' }}</p>
                            <p><span class="text-slate-400">Tên thẻ:</span> {{ detailRow.loan?.library_card_name || '—' }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ghi chú yêu cầu</p>
                        <p class="mt-2 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ detailRow.request_note || '—' }}</p>
                    </div>
                    <div v-if="detailProofUrl" class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Minh chứng</p>
                        <div class="mt-2">
                            <img v-if="detailProofIsImage" :src="detailProofUrl" alt="Minh chứng gia hạn" class="max-h-[60vh] w-auto rounded-lg border border-slate-200 dark:border-slate-700" @error="withFallback('/images/default-news-cover.jpg')($event)" />
                            <iframe v-else-if="detailProofIsPdf" :src="detailProofUrl" class="h-[60vh] w-full rounded-lg border border-slate-200 dark:border-slate-700" />
                            <a v-else :href="detailProofUrl" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-300 underline">Mở tệp minh chứng</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="actionModal.show" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeActionModal" />
            <div class="relative w-full max-w-lg rounded-xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{ actionModal.mode === 'approve' ? 'Duyệt yêu cầu gia hạn' : 'Từ chối yêu cầu gia hạn' }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{
                                actionModal.row
                                    ? `Mã yêu cầu: ${actionModal.row.request_code || `#${actionModal.row.id}`}`
                                    : `Số yêu cầu đã chọn: ${actionModal.ids.length}`
                            }}
                        </p>
                    </div>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeActionModal">
                        <Icon icon="lucide:x" class="h-5 w-5" />
                    </button>
                </div>

                <label class="block space-y-1">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ actionModal.mode === 'approve' ? 'Ghi chú duyệt (tuỳ chọn)' : 'Lý do từ chối (tuỳ chọn)' }}
                    </span>
                    <textarea
                        v-model="actionModal.note"
                        rows="4"
                        class="admin-filter-input w-full"
                        :placeholder="actionModal.mode === 'approve' ? 'Nhập ghi chú xử lý (nếu có)...' : 'Nhập lý do để bạn đọc dễ theo dõi...'"
                    />
                </label>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" class="admin-filter-btn px-4 py-2 min-h-[40px]" @click="closeActionModal">
                        Hủy
                    </button>
                    <button
                        type="button"
                        class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold transition-colors disabled:opacity-50"
                        :class="
                            actionModal.mode === 'approve'
                                ? 'border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50'
                                : 'border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45'
                        "
                        :disabled="actionModal.row ? actionId === actionModal.row.id : actionId !== null"
                        @click="confirmActionModal"
                    >
                        {{ actionModal.mode === 'approve' ? 'Xác nhận duyệt' : 'Xác nhận từ chối' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
