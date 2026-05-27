<script setup>
import { Icon } from '@iconify/vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
import { loansApi } from '@/api/loans';
import { extractApiPaginator } from '@/utils/adminPagination';
import { toast } from '@/store/toast';
import { bookResourceTypeLabel } from '@/utils/bookResourceTypeLabel';
import { useImageFallback } from '@/composables/useImageFallback';
import { stashBorrowRequestPrefill } from '@/utils/adminBorrowRequestPrefill';

const inertiaPage = usePage();
const staffUserId = computed(() => Number(inertiaPage.props.auth?.user?.id || 0));

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const sortBy = ref('newest');
const searchKeyword = ref('');
const showFilterPanel = ref(false);
const searchIn = ref({
    request_code: true,
    card: true,
    reader: true,
    book: true,
});
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const actionId = ref(null);
const detailRow = ref(null);
const rejectTargetRow = ref(null);
const rejectNote = ref('');
const rejectBulkOpen = ref(false);
const rejectBulkIds = ref([]);
const selectedIds = ref([]);

const SEARCH_IN_OPTIONS = [
    { key: 'request_code', label: 'Mã yêu cầu' },
    { key: 'card', label: 'Mã thẻ / tên thẻ' },
    { key: 'reader', label: 'Bạn đọc / mã định danh' },
    { key: 'book', label: 'Tên sách / mã sách' },
];

const sortOptions = [
    { key: 'newest', label: 'Mới nhất' },
    { key: 'oldest', label: 'Cũ nhất' },
];

function statusLabel(s) {
    if (s === 'approved') return 'Đã duyệt';
    if (s === 'rejected') return 'Đã từ chối';
    if (s === 'cancelled') return 'Đã hủy';
    return 'Chờ duyệt';
}

function statusClass(s) {
    if (s === 'approved') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
    }
    if (s === 'rejected') {
        return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
    }
    if (s === 'cancelled') {
        return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200';
    }
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
}

function formatDate(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return '—';
    const year = d.getFullYear();
    if (year < 2000 || year > 2100) return '—';
    return d.toLocaleDateString('vi-VN');
}

async function loadRows() {
    loading.value = true;
    try {
        const q = searchKeyword.value.trim();
        const payload = await loansApi.borrowRequests({
            status: 'pending',
            search: q || undefined,
            search_in: selectedSearchIn.value,
            sort: sortBy.value,
            page: page.value,
            per_page: 20,
        });
        const { items, meta: m } = extractApiPaginator(payload, 20);
        rows.value = items;
        const allowed = new Set(items.map((r) => r.id));
        selectedIds.value = selectedIds.value.filter((id) => allowed.has(id));
        meta.value = {
            current_page: m.current_page,
            last_page: m.last_page,
            per_page: m.per_page,
            total: m.total,
        };
        page.value = m.current_page;
    } catch (e) {
        rows.value = [];
        clearSelection();
        toast.error(e?.response?.data?.messages || 'Không tải được danh sách yêu cầu mượn.', { title: 'Duyệt yêu cầu' });
    } finally {
        loading.value = false;
    }
}

function clearSelection() {
    selectedIds.value = [];
}

const pendingOnPage = computed(() => (rows.value || []).filter((r) => r.status === 'pending'));
const pendingIdsOnPage = computed(() => pendingOnPage.value.map((r) => r.id));
const hasSelection = computed(() => selectedIds.value.length > 0);
const selectedOnPageCount = computed(() => pendingIdsOnPage.value.filter((id) => selectedIds.value.includes(id)).length);
const allPendingOnPageSelected = computed(
    () => pendingIdsOnPage.value.length > 0 && selectedOnPageCount.value === pendingIdsOnPage.value.length
);

function isRowSelected(id) {
    return selectedIds.value.includes(id);
}

function toggleRowSelect(row) {
    if (row.status !== 'pending') return;
    const id = row.id;
    const i = selectedIds.value.indexOf(id);
    if (i === -1) {
        selectedIds.value = [...selectedIds.value, id];
    } else {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    }
}

function toggleSelectAllOnPage() {
    const pids = pendingIdsOnPage.value;
    if (pids.length === 0) return;
    if (allPendingOnPageSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !pids.includes(id));
    } else {
        const set = new Set([...selectedIds.value, ...pids]);
        selectedIds.value = Array.from(set);
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

function holderTypeLabel(type) {
    if (type === 'student') return 'Sinh viên';
    if (type === 'teacher') return 'Giảng viên';
    if (type === 'external') return 'Bạn đọc ngoài';
    return type || '—';
}

function totalRequestedBooks(items = []) {
    return items.reduce((sum, it) => sum + Number(it?.quantity || 0), 0);
}

async function approveRow(row) {
    const payload = {
        request_id: row.id,
        request_code: row.request_code || '',
        library_card_id: row.library_card?.id || null,
        card_number: row.library_card?.card_number || '',
        card_full_name: row.library_card?.full_name || '',
        holder_type: row.library_card?.holder_type || '',
        loan_type: row.loan_type || 'home',
        requested_loan_date: row.requested_loan_date || '',
        requested_due_date: row.requested_due_date || row.suggested_due_date || '',
        request_note: row.request_note || '',
        items: (row.items || []).map((it) => ({
            request_item_id: it.id,
            book_id: it.book_id,
            book_title: it.book_title || '',
            book_code: it.book_code || '',
            quantity: Number(it.quantity || 1),
            resource_type: it.resource_type || '',
            cabinet: it.cabinet || '',
            warehouse_name: it.warehouse_name || '',
            warehouse_code: it.warehouse_code || '',
            book_total_quantity: it.book_total_quantity ?? null,
            available_for_borrow: it.available_for_borrow ?? null,
            available_for_approval: it.available_for_approval ?? null,
        })),
    };
    stashBorrowRequestPrefill(staffUserId.value, payload);
    router.visit(route('admin.loans.create', { from_borrow_request: row.id }));
}

async function rejectRow(row) {
    rejectBulkOpen.value = false;
    rejectBulkIds.value = [];
    rejectTargetRow.value = row;
    rejectNote.value = '';
}

function openBulkReject(ids) {
    if (!ids.length) {
        toast.warn('Không có yêu cầu nào để từ chối.', { title: 'Duyệt yêu cầu' });
        return;
    }
    rejectTargetRow.value = null;
    rejectBulkOpen.value = true;
    rejectBulkIds.value = [...ids];
    rejectNote.value = '';
}

function rejectSelected() {
    const ids = selectedIds.value.filter((id) => rows.value.some((r) => r.id === id && r.status === 'pending'));
    if (!ids.length) {
        toast.warn('Vui lòng chọn ít nhất một yêu cầu đang chờ duyệt.', { title: 'Duyệt yêu cầu' });
        return;
    }
    openBulkReject(ids);
}

function closeRejectModal() {
    rejectTargetRow.value = null;
    rejectBulkOpen.value = false;
    rejectBulkIds.value = [];
    rejectNote.value = '';
}

const rejectModalOpen = computed(() => rejectTargetRow.value != null || rejectBulkOpen.value);

async function confirmReject() {
    const note = rejectNote.value.trim();
    if (rejectBulkOpen.value && rejectBulkIds.value.length) {
        actionId.value = -1;
        try {
            await loansApi.bulkRejectBorrowRequests({
                ids: rejectBulkIds.value,
                review_note: note || null,
            });
            toast.success('Đã từ chối các yêu cầu mượn đã chọn.', { title: 'Duyệt yêu cầu' });
            clearSelection();
            closeRejectModal();
            await loadRows();
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không thể từ chối yêu cầu mượn.', { title: 'Duyệt yêu cầu' });
        } finally {
            actionId.value = null;
        }
        return;
    }
    if (!rejectTargetRow.value) return;
    const row = rejectTargetRow.value;
    actionId.value = row.id;
    try {
        await loansApi.rejectBorrowRequest(row.id, { review_note: note || null });
        toast.success('Đã từ chối yêu cầu mượn.', { title: 'Duyệt yêu cầu' });
        selectedIds.value = selectedIds.value.filter((id) => id !== row.id);
        closeRejectModal();
        await loadRows();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể từ chối yêu cầu mượn.', { title: 'Duyệt yêu cầu' });
    } finally {
        actionId.value = null;
    }
}
</script>

<template>
    <div class="space-y-4 animate-in fade-in-50 duration-500">
        <AdminPageHeading title="Duyệt phiếu mượn từ yêu cầu bạn đọc">
            <template #description>
                Chỉ hiển thị yêu cầu đang chờ duyệt để thủ thư duyệt/từ chối và tạo phiếu mượn.
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
            search-placeholder="Mã yêu cầu, mã định danh, mã thẻ, tên bạn đọc..."
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
                class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400/45 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-700"
                aria-label="Bỏ chọn tất cả các dòng đã tick"
                @click="clearSelection"
            >
                <Icon icon="lucide:x" class="h-4 w-4 shrink-0 opacity-90" aria-hidden="true" />
                Bỏ chọn
            </button>
            <button
                type="button"
                class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                @click="rejectSelected"
            >
                <Icon :icon="ADMIN_ICONS.xCircle" class="h-4 w-4" />
                Từ chối đã chọn
            </button>
        </div>

        <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="w-full min-w-0 overflow-x-auto">
                <table class="w-full table-fixed border-collapse text-left text-sm leading-snug">
                    <colgroup>
                        <col class="w-12 shrink-0" />
                        <col class="w-[11%]" />
                        <col class="w-[18%]" />
                        <col class="w-[13%]" />
                        <col class="w-[12%]" />
                        <col class="w-[7%]" />
                        <col class="w-[11%]" />
                        <col class="w-[9%]" />
                        <col class="w-[19%]" />
                    </colgroup>
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/60">
                        <tr>
                            <th class="px-3 py-2.5">
                                <span class="admin-table-checkbox-wrap">
                                    <input
                                        type="checkbox"
                                        class="admin-table-checkbox"
                                        :checked="allPendingOnPageSelected"
                                        :disabled="loading || pendingIdsOnPage.length === 0"
                                        :indeterminate="selectedOnPageCount > 0 && !allPendingOnPageSelected"
                                        @change="toggleSelectAllOnPage"
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
                                Họ tên
                            </th>
                            <th class="px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Mã định danh
                            </th>
                            <th class="px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Loại thẻ
                            </th>
                            <th class="whitespace-nowrap px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Số lượng
                            </th>
                            <th class="whitespace-nowrap px-3 py-2.5 text-left font-semibold text-slate-600 dark:text-slate-200">
                                Ngày trả dự kiến
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
                            <td colspan="9" class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">Đang tải…</td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td colspan="9" class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">Không có bản ghi.</td>
                        </tr>
                        <template v-else>
                            <tr v-for="row in rows" :key="row.id" class="admin-table-row">
                                <td class="px-3 py-2 align-middle">
                                    <span v-if="row.status === 'pending'" class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            class="admin-table-checkbox"
                                            :checked="isRowSelected(row.id)"
                                            @change="toggleRowSelect(row)"
                                        />
                                    </span>
                                    <span v-else class="inline-block w-6" />
                                </td>
                                <td
                                    class="max-w-0 px-2 py-2 align-middle tabular-nums text-slate-900 dark:text-white"
                                    :title="row.request_code || `#${row.id}`"
                                >
                                    <span class="break-words">{{ row.request_code || `#${row.id}` }}</span>
                                </td>
                                <td class="max-w-0 px-3 py-2 align-middle">
                                    <div
                                        class="truncate font-medium text-slate-900 dark:text-white"
                                        :title="row.requester?.name || ''"
                                    >
                                        {{ row.requester?.name || '—' }}
                                    </div>
                                </td>
                                <td
                                    class="max-w-0 truncate px-3 py-2 align-middle tabular-nums text-slate-700 dark:text-slate-300"
                                    :title="row.requester?.code || ''"
                                >
                                    {{ row.requester?.code || '—' }}
                                </td>
                                <td
                                    class="max-w-0 truncate px-3 py-2 align-middle text-slate-700 dark:text-slate-300"
                                    :title="holderTypeLabel(row.library_card?.holder_type)"
                                >
                                    {{ holderTypeLabel(row.library_card?.holder_type) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 align-middle tabular-nums text-slate-700 dark:text-slate-300">
                                    {{ totalRequestedBooks(row.items || []) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 align-middle tabular-nums text-slate-700 dark:text-slate-300">
                                    {{ formatDate(row.requested_due_date) }}
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
                                            title="Xem chi tiết yêu cầu và danh sách sách"
                                            @click="openDetails(row)"
                                        >
                                            <Icon icon="lucide:eye" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                            <span class="whitespace-nowrap">Chi tiết</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-9 shrink-0 items-center justify-center gap-0.5 rounded-md border border-emerald-300 bg-emerald-50 px-1.5 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            title="Đồng ý và lập phiếu mượn"
                                            :disabled="actionId === row.id"
                                            @click="approveRow(row)"
                                        >
                                            <Icon :icon="ADMIN_ICONS.checkCircle" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                            <span class="whitespace-nowrap">Đồng ý</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-9 shrink-0 items-center justify-center gap-0.5 rounded-md border border-rose-300 bg-rose-50 px-1.5 text-xs font-medium text-rose-700 transition-colors hover:bg-rose-100 disabled:opacity-50 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                                            title="Từ chối yêu cầu mượn"
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
                Tick chọn nhiều dòng để « Từ chối đã chọn » (giống duyệt gia hạn). Đồng ý và lập phiếu mượn chỉ dùng nút « Đồng ý » trên từng dòng — mỗi lần một yêu cầu.
            </span>
        </p>

        <div v-if="detailRow" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeDetails" />
            <div class="relative w-full max-w-6xl max-h-[90vh] overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết yêu cầu mượn</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Xem kỹ nội dung yêu cầu trước khi tạo phiếu.</p>
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
                            <p><span class="text-slate-400">Ngày yêu cầu:</span> {{ formatDateTime(detailRow.created_at) }}</p>
                            <p><span class="text-slate-400">Ngày mượn đề xuất:</span> {{ formatDate(detailRow.requested_loan_date) }}</p>
                            <p><span class="text-slate-400">Hạn trả đề xuất:</span> {{ formatDate(detailRow.requested_due_date) }}</p>
                            <p><span class="text-slate-400">Loại mượn:</span> {{ detailRow.loan_type || '—' }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Bạn đọc & thẻ</p>
                        <div class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                            <p><span class="text-slate-400">Bạn đọc:</span> {{ detailRow.requester?.name || '—' }}</p>
                            <p><span class="text-slate-400">Mã định danh:</span> {{ detailRow.requester?.code || '—' }}</p>
                            <p><span class="text-slate-400">Mã thẻ:</span> {{ detailRow.library_card?.card_number || '—' }}</p>
                            <p><span class="text-slate-400">Tên thẻ:</span> {{ detailRow.library_card?.full_name || '—' }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Danh sách sách yêu cầu</p>
                        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[980px] border-collapse text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                                        <tr class="border-b border-slate-200 dark:border-slate-700">
                                            <th class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                                Mã sách
                                            </th>
                                            <th class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                                Sách
                                            </th>
                                            <th class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                                Loại sách
                                            </th>
                                            <th class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                                Tủ lưu trữ
                                            </th>
                                            <th class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                                Kho lưu trữ
                                            </th>
                                            <th class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                                Số lượng
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                        <tr v-for="it in (detailRow.items || [])" :key="`detail-${detailRow.id}-${it.id}`" class="bg-white dark:bg-slate-900">
                                            <td class="px-3 py-2 text-slate-700 dark:text-slate-300">{{ it.book_code || '—' }}</td>
                                            <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ it.book_title || 'Chưa có tên' }}</td>
                                            <td class="px-3 py-2 text-slate-700 dark:text-slate-300">{{ bookResourceTypeLabel(it.resource_type) }}</td>
                                            <td class="px-3 py-2 text-slate-700 dark:text-slate-300">{{ it.cabinet || '—' }}</td>
                                            <td class="px-3 py-2 text-slate-700 dark:text-slate-300">
                                                {{ it.warehouse_name || '—' }}
                                                <template v-if="it.warehouse_code">({{ it.warehouse_code }})</template>
                                            </td>
                                            <td class="px-3 py-2 text-left font-semibold tabular-nums text-slate-700 dark:text-slate-200">
                                                {{ it.quantity || 1 }}
                                            </td>
                                        </tr>
                                        <tr v-if="!(detailRow.items || []).length" class="bg-white dark:bg-slate-900">
                                            <td colspan="6" class="px-3 py-4 text-center text-slate-500 dark:text-slate-400">
                                                Không có sách trong yêu cầu.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ghi chú yêu cầu</p>
                        <p class="mt-2 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ detailRow.request_note || '—' }}</p>
                    </div>
                    <div v-if="detailProofUrl" class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Minh chứng</p>
                        <div class="mt-2">
                            <img v-if="detailProofIsImage" :src="detailProofUrl" alt="Minh chứng yêu cầu mượn" class="max-h-[60vh] w-auto rounded-lg border border-slate-200 dark:border-slate-700" @error="withFallback('/images/default-news-cover.jpg')($event)" />
                            <iframe v-else-if="detailProofIsPdf" :src="detailProofUrl" class="h-[60vh] w-full rounded-lg border border-slate-200 dark:border-slate-700" />
                            <a v-else :href="detailProofUrl" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-300 underline">Mở tệp minh chứng</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="rejectModalOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeRejectModal" />
            <div class="relative w-full max-w-lg rounded-xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            <template v-if="rejectBulkOpen">Từ chối {{ rejectBulkIds.length }} yêu cầu mượn</template>
                            <template v-else>Từ chối yêu cầu mượn</template>
                        </h3>
                        <p v-if="rejectTargetRow" class="text-xs text-slate-500 dark:text-slate-400">
                            Mã yêu cầu: {{ rejectTargetRow.request_code || `#${rejectTargetRow.id}` }}
                        </p>
                        <p v-else-if="rejectBulkOpen" class="text-xs text-slate-500 dark:text-slate-400">
                            Ghi chú chung áp dụng cho tất cả yêu cầu trong lần xử lý này.
                        </p>
                    </div>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeRejectModal">
                        <Icon icon="lucide:x" class="h-5 w-5" />
                    </button>
                </div>

                <label class="block space-y-1">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Lý do từ chối (tuỳ chọn)</span>
                    <textarea
                        v-model="rejectNote"
                        rows="4"
                        class="admin-filter-input w-full"
                        placeholder="Nhập lý do để bạn đọc dễ theo dõi..."
                    />
                </label>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" class="admin-filter-btn px-4 py-2 min-h-[40px]" @click="closeRejectModal">
                        Hủy
                    </button>
                    <button
                        type="button"
                        class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100 disabled:opacity-50 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                        :disabled="actionId !== null"
                        @click="confirmReject"
                    >
                        Xác nhận từ chối
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
