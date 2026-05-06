<script setup>
import { Icon } from '@iconify/vue';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
import { digitalDocumentsApi } from '@/api/digitalDocuments';
import {
    BTN_SUBMISSION_DANGER_BAR,
    BTN_SUBMISSION_DANGER_INLINE,
    BTN_SUBMISSION_DANGER_MODAL,
    BTN_SUBMISSION_NEUTRAL_INLINE,
    BTN_SUBMISSION_SUCCESS_BAR,
    BTN_SUBMISSION_SUCCESS_INLINE,
    BTN_SUBMISSION_SUCCESS_MODAL,
    LINK_SUBMISSION_FILE,
    submissionStatusBadgeClass,
} from '@/config/digitalSubmissionUi';
import { toast } from '@/store/toast';

const rows = ref([]);
const loading = ref(false);
const searchKeyword = ref('');
/** Trạng thái lọc từ API: '', pending, approved, rejected */
const statusFilter = ref('pending');
const sortBy = ref('newest');
const currentPage = ref(1);
const perPage = 15;

const selectedIds = ref([]);
const detailRow = ref(null);
const rejectRow = ref(null);
const rejectNote = ref('');
const rejectSubmitting = ref(false);
const rejectBulk = ref(false);
const actionSubmissionId = ref(null);

const dateFmt = new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

/** Hiển thị mã tham chiếu duyệt (đồng bộ tìm kiếm: TLS-000042). */
function submissionRef(row) {
    if (!row?.id) return '—';
    return `TLS-${String(row.id).padStart(6, '0')}`;
}

function statusLabel(s) {
    if (s === 'approved') return 'Đã duyệt';
    if (s === 'rejected') return 'Từ chối';
    return 'Chờ duyệt';
}

function formatDt(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return '—';
    return dateFmt.format(d);
}

async function loadRows() {
    loading.value = true;
    try {
        const params = {};
        if (statusFilter.value !== '' && statusFilter.value !== 'all') {
            params.status = statusFilter.value;
        }
        const payload = await digitalDocumentsApi.list(params);
        const list = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : [];
        rows.value = list;
        const allowed = new Set(list.map((r) => r.id));
        selectedIds.value = selectedIds.value.filter((id) => allowed.has(id));
    } catch (e) {
        rows.value = [];
        selectedIds.value = [];
        toast.error(e?.response?.data?.messages || 'Không tải được danh sách yêu cầu.', { title: 'Đồ án, luận văn' });
    } finally {
        loading.value = false;
    }
}

function runSearch() {
    currentPage.value = 1;
}

watch(statusFilter, () => {
    currentPage.value = 1;
    loadRows();
});

watch(sortBy, () => {
    currentPage.value = 1;
});

const filteredRows = computed(() => {
    const q = searchKeyword.value.trim().toLowerCase();
    const list = rows.value;
    if (!q) return list;
    return list.filter((item) => {
        const ref = item?.id != null ? `TLS-${String(item.id).padStart(6, '0')}`.toLowerCase() : '';
        const blob = [
            ref,
            String(item?.id ?? ''),
            item?.title,
            item?.author_names,
            item?.description,
            item?.original_name,
            item?.submitter?.name,
            item?.submitter?.email,
            item?.approved_book?.book_code,
        ]
            .map((x) => String(x || '').toLowerCase())
            .join(' ');
        return blob.includes(q);
    });
});

const sortedRows = computed(() => {
    const list = filteredRows.value.slice();
    list.sort((a, b) => (sortBy.value === 'oldest' ? a.id - b.id : b.id - a.id));
    return list;
});

const lastPage = computed(() => Math.max(1, Math.ceil(sortedRows.value.length / perPage)));

const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return sortedRows.value.slice(start, start + perPage);
});

const pendingOnPageIds = computed(() => paginatedRows.value.filter((r) => r.status === 'pending').map((r) => r.id));

const hasSelection = computed(() => selectedIds.value.length > 0);

const isAllSelected = computed(
    () =>
        pendingOnPageIds.value.length > 0 && pendingOnPageIds.value.every((id) => selectedIds.value.includes(id))
);

watch(lastPage, (lp) => {
    if (currentPage.value > lp) currentPage.value = lp;
});

watch(sortedRows, () => {
    if (detailRow.value && !sortedRows.value.some((r) => r.id === detailRow.value.id)) {
        detailRow.value = null;
    }
});

function goPage(p) {
    currentPage.value = p;
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        const drop = new Set(pendingOnPageIds.value);
        selectedIds.value = selectedIds.value.filter((id) => !drop.has(id));
        return;
    }
    const merge = new Set([...selectedIds.value, ...pendingOnPageIds.value]);
    selectedIds.value = [...merge];
}

function toggleSelect(id, row) {
    if (row.status !== 'pending') return;
    const i = selectedIds.value.indexOf(id);
    if (i >= 0) {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    } else {
        selectedIds.value = [...selectedIds.value, id];
    }
}

function clearSelection() {
    selectedIds.value = [];
}

onMounted(loadRows);

function openDetail(row) {
    detailRow.value = row;
}

function closeDetail() {
    detailRow.value = null;
}

function openReject(row) {
    rejectBulk.value = false;
    rejectRow.value = row;
    rejectNote.value = '';
}

function openRejectBulk() {
    const pendingSelected = selectedIds.value.filter((id) => {
        const r = rows.value.find((x) => x.id === id);
        return r?.status === 'pending';
    });
    if (!pendingSelected.length) {
        toast.warn('Chỉ có thể từ chối các yêu cầu đang chờ duyệt.', { title: 'Đồ án, luận văn' });
        return;
    }
    rejectBulk.value = true;
    rejectRow.value = { id: 'bulk', _bulkIds: pendingSelected };
    rejectNote.value = '';
}

function closeReject() {
    rejectRow.value = null;
    rejectNote.value = '';
    rejectBulk.value = false;
}

async function confirmReject() {
    if (!rejectRow.value || rejectSubmitting.value) return;
    rejectSubmitting.value = true;
    const note = rejectNote.value.trim() || undefined;

    try {
        if (rejectBulk.value && Array.isArray(rejectRow.value._bulkIds)) {
            let ok = 0;
            let fail = 0;
            for (const id of rejectRow.value._bulkIds) {
                try {
                    await digitalDocumentsApi.reject(id, { review_note: note });
                    ok += 1;
                } catch {
                    fail += 1;
                }
            }
            if (ok) toast.success(`Đã từ chối ${ok} yêu cầu.${fail ? ` ${fail} lỗi.` : ''}`, { title: 'Đồ án, luận văn' });
            else if (fail) toast.error('Không từ chối được yêu cầu nào.', { title: 'Đồ án, luận văn' });
            clearSelection();
            closeReject();
            await loadRows();
            return;
        }

        actionSubmissionId.value = rejectRow.value.id;
        await digitalDocumentsApi.reject(rejectRow.value.id, { review_note: note });
        toast.success('Đã từ chối yêu cầu.', { title: 'Đồ án, luận văn' });
        closeReject();
        await loadRows();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không từ chối được yêu cầu.', { title: 'Đồ án, luận văn' });
    } finally {
        rejectSubmitting.value = false;
        actionSubmissionId.value = null;
    }
}

async function approveRow(row) {
    if (!row?.id || row.status !== 'pending' || actionSubmissionId.value) return;
    actionSubmissionId.value = row.id;
    try {
        await digitalDocumentsApi.approve(row.id, {});
        toast.success('Đã duyệt và tạo bản ghi đồ án, luận văn.', { title: 'Đồ án, luận văn' });
        detailRow.value = null;
        selectedIds.value = selectedIds.value.filter((x) => x !== row.id);
        await loadRows();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không duyệt được yêu cầu.', { title: 'Đồ án, luận văn' });
    } finally {
        actionSubmissionId.value = null;
    }
}

async function approveSelected() {
    const ids = selectedIds.value.filter((id) => {
        const r = rows.value.find((x) => x.id === id);
        return r?.status === 'pending';
    });
    if (!ids.length) {
        toast.warn('Chọn ít nhất một yêu cầu đang chờ duyệt.', { title: 'Đồ án, luận văn' });
        return;
    }
    let ok = 0;
    let fail = 0;
    for (const id of ids) {
        try {
            await digitalDocumentsApi.approve(id, {});
            ok += 1;
        } catch {
            fail += 1;
        }
    }
    if (ok) toast.success(`Đã duyệt ${ok} yêu cầu.${fail ? ` ${fail} lỗi.` : ''}`, { title: 'Đồ án, luận văn' });
    else toast.error('Không duyệt được yêu cầu nào.', { title: 'Đồ án, luận văn' });
    clearSelection();
    detailRow.value = null;
    await loadRows();
}

function coverSrc(row) {
    return row?.approved_book?.cover_image || row?.cover_image_url || '';
}

/** Cột Họ tên: chỉ tên, không hiển thị email. */
function submitterLabel(row) {
    const n = String(row?.submitter?.name || '').trim();
    return n || '—';
}
</script>

<template>
    <div class="space-y-4 animate-in fade-in-50 duration-500">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">Duyệt tài liệu số</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 max-w-3xl">
                    Độc giả gửi PDF (có thể kèm ảnh bìa). Sau khi duyệt, hệ thống tạo đầu mục trong danh mục « Đồ án, luận văn ».
                </p>
            </div>
            <button
                type="button"
                class="admin-filter-btn inline-flex min-h-[40px] shrink-0 items-center gap-2 px-3 text-xs font-semibold"
                @click="router.visit(route('admin.books.digital'))"
            >
                <Icon icon="lucide:library-big" class="h-4 w-4" />
                Danh sách tài liệu số
            </button>
        </div>

        <AdminFilterSearch
            v-model="searchKeyword"
            search-placeholder="Mã TLS, họ tên, email, tên tài liệu, tác giả, mô tả..."
            :show-filter-button="false"
            @search="runSearch"
        >
            <template #filters>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <select v-model="statusFilter" class="admin-filter-select !h-9 !py-0 leading-9 w-[118px] max-w-full pr-9">
                            <option value="pending">Chờ duyệt</option>
                            <option value="approved">Đã duyệt</option>
                            <option value="rejected">Từ chối</option>
                            <option value="">Tất cả</option>
                        </select>
                        <Icon
                            icon="lucide:chevron-down"
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                    </div>
                    <div class="relative">
                        <select v-model="sortBy" class="admin-filter-select !h-9 !py-0 leading-9 w-[112px] max-w-full pr-9">
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                        </select>
                        <Icon
                            icon="lucide:chevron-down"
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
            <button type="button" :class="BTN_SUBMISSION_SUCCESS_BAR" @click="approveSelected">
                <Icon :icon="ADMIN_ICONS.checkCircle" class="h-4 w-4" />
                Đồng ý đã chọn
            </button>
            <button type="button" :class="BTN_SUBMISSION_DANGER_BAR" @click="openRejectBulk">
                <Icon :icon="ADMIN_ICONS.xCircle" class="h-4 w-4" />
                Từ chối đã chọn
            </button>
            <button type="button" class="min-h-[44px] px-2 text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="clearSelection">
                Bỏ chọn
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="max-w-full min-w-0 overflow-x-auto">
                <table class="w-full border-collapse text-left text-[13px] md:text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/60">
                        <tr>
                            <th class="w-12 p-3 sm:p-4">
                                <span class="admin-table-checkbox-wrap">
                                    <input
                                        type="checkbox"
                                        class="admin-table-checkbox"
                                        :checked="isAllSelected"
                                        :disabled="!pendingOnPageIds.length || loading"
                                        :indeterminate="hasSelection && !isAllSelected"
                                        @change="toggleSelectAll"
                                    />
                                </span>
                            </th>
                            <th class="px-2 py-2 align-middle whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                Mã duyệt
                            </th>
                            <th class="min-w-0 px-2 py-2 align-middle whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                Họ tên
                            </th>
                            <th class="min-w-0 px-2 py-2 align-middle whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                Tên tài liệu
                            </th>
                            <th class="min-w-0 px-2 py-2 align-middle whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                Tác giả
                            </th>
                            <th class="min-w-0 px-2 py-2 align-middle whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                Mô tả
                            </th>
                            <th class="min-w-0 px-2 py-2 align-middle whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                File đính kèm
                            </th>
                            <th class="w-[1%] whitespace-nowrap px-2 py-2 align-middle text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                        <tr v-if="loading">
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">Đang tải...</td>
                        </tr>
                        <template v-else>
                            <tr v-for="row in paginatedRows" :key="row.id" class="admin-table-row hover:bg-gray-50/80 dark:hover:bg-slate-800/40">
                                <td class="p-3 align-middle sm:p-4">
                                    <span class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            class="admin-table-checkbox"
                                            :checked="selectedIds.includes(row.id)"
                                            :disabled="row.status !== 'pending'"
                                            @change="toggleSelect(row.id, row)"
                                        />
                                    </span>
                                </td>
                                <td class="max-w-[5.5rem] px-2 py-2 align-middle font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                    <span class="block truncate">{{ submissionRef(row) }}</span>
                                </td>
                                <td class="max-w-[6rem] min-w-0 px-2 py-2 align-middle text-[12px] text-slate-800 dark:text-slate-200">
                                    <span class="line-clamp-2 break-words">{{ submitterLabel(row) }}</span>
                                </td>
                                <td class="max-w-[10rem] min-w-0 px-2 py-2 align-middle">
                                    <p class="line-clamp-2 break-words text-[12px] font-medium leading-snug text-slate-900 dark:text-white">
                                        {{ row.title || '—' }}
                                    </p>
                                </td>
                                <td class="max-w-[7rem] min-w-0 px-2 py-2 align-middle text-[12px] text-slate-700 dark:text-slate-300">
                                    <span class="line-clamp-2 break-words">{{ row.author_names || '—' }}</span>
                                </td>
                                <td class="max-w-[9rem] min-w-0 px-2 py-2 align-middle text-[12px] text-slate-700 dark:text-slate-300">
                                    <span class="line-clamp-2 break-words" :title="row.description || ''">{{
                                        row.description || '—'
                                    }}</span>
                                </td>
                                <td class="max-w-[8rem] min-w-0 px-2 py-2 align-middle text-[11px]">
                                    <a
                                        v-if="row.file_url"
                                        :href="row.file_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        :class="['line-clamp-1 break-all text-xs', LINK_SUBMISSION_FILE]"
                                        :title="row.original_name || 'Mở file'"
                                    >
                                        {{ row.original_name || 'PDF' }}
                                    </a>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="p-3 text-left sm:p-4 min-w-[280px]">
                                    <div
                                        class="flex flex-nowrap items-center justify-start gap-2 whitespace-nowrap"
                                        role="group"
                                        :aria-label="'Thao tác duyệt: ' + (row.title || '')"
                                    >
                                        <button
                                            type="button"
                                            :class="BTN_SUBMISSION_NEUTRAL_INLINE"
                                            title="Xem chi tiết yêu cầu"
                                            @click="openDetail(row)"
                                        >
                                            <Icon icon="lucide:eye" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                            Chi tiết
                                        </button>
                                        <template v-if="row.status === 'pending'">
                                            <button
                                                type="button"
                                                :class="BTN_SUBMISSION_SUCCESS_INLINE"
                                                :disabled="actionSubmissionId === row.id"
                                                title="Duyệt và tạo đầu mục tài liệu số"
                                                @click="approveRow(row)"
                                            >
                                                <Icon :icon="ADMIN_ICONS.checkCircle" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                                Đồng ý
                                            </button>
                                            <button
                                                type="button"
                                                :class="BTN_SUBMISSION_DANGER_INLINE"
                                                :disabled="actionSubmissionId === row.id"
                                                title="Từ chối yêu cầu"
                                                @click="openReject(row)"
                                            >
                                                <Icon :icon="ADMIN_ICONS.xCircle" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                                Từ chối
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!loading && !paginatedRows.length">
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    Không có yêu cầu phù hợp.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <AdminPaginationBar
            always-show
            :current-page="currentPage"
            :last-page="lastPage"
            :disabled="loading"
            @go-page="goPage"
        />

        <p class="text-xs text-slate-500 dark:text-slate-400">
            <Icon icon="lucide:info" class="-mt-0.5 inline h-3.5 w-3.5" />
            « Đồng ý » tạo đầu mục tài liệu số từ file độc giả gửi; « Từ chối » giữ bản ghi để tra cứu nhưng không xuất hiện cho độc giả ở trạng thái đã từ chối.
        </p>

        <!-- Chi tiết -->
        <div v-if="detailRow" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeDetail" />
            <div
                class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết {{ submissionRef(detailRow) }}</h3>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeDetail">
                        <Icon icon="lucide:x" class="h-5 w-5" />
                    </button>
                </div>
                <div class="grid gap-3 text-sm md:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Người đăng</p>
                        <div class="mt-2 space-y-1 text-slate-700 dark:text-slate-300">
                            <p>
                                <span class="text-slate-400">Họ tên:</span>
                                {{ detailRow.submitter?.name || '—' }}
                            </p>
                            <p>
                                <span class="text-slate-400">Email:</span>
                                {{ detailRow.submitter?.email || '—' }}
                            </p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tài liệu</p>
                        <div class="mt-2 space-y-1 text-slate-700 dark:text-slate-300">
                            <p class="flex flex-wrap items-center gap-2">
                                <span class="text-slate-400">Trạng thái:</span>
                                <span
                                    class="inline-flex min-w-[5.5rem] items-center justify-center rounded-md px-2.5 py-1 text-xs font-semibold leading-tight"
                                    :class="submissionStatusBadgeClass(detailRow.status)"
                                >
                                    {{ statusLabel(detailRow.status) }}
                                </span>
                            </p>
                            <p>
                                <span class="text-slate-400">Gửi lúc:</span>
                                {{ formatDt(detailRow.submitted_at) }}
                            </p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Nội dung</p>
                        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-[140px,minmax(0,1fr)] md:items-start">
                            <div
                                class="mx-auto aspect-[3/4] w-full max-w-[140px] shrink-0 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200/80 dark:bg-slate-800 dark:ring-slate-700/80 md:mx-0"
                            >
                                <img
                                    :src="coverSrc(detailRow) || '/images/default-book-cover.png'"
                                    alt="Ảnh bìa"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                    decoding="async"
                                />
                            </div>
                            <div class="min-w-0 space-y-2 text-slate-700 dark:text-slate-300">
                                <p><span class="text-slate-400">Tên sách:</span> {{ detailRow.title || '—' }}</p>
                                <p><span class="text-slate-400">Tác giả:</span> {{ detailRow.author_names || '—' }}</p>
                                <p class="whitespace-pre-wrap break-words">
                                    <span class="text-slate-400">Mô tả:</span> {{ detailRow.description || '—' }}
                                </p>
                                <p>
                                    <span class="text-slate-400">File PDF:</span>
                                    <a
                                        v-if="detailRow.file_url"
                                        :href="detailRow.file_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        :class="['font-semibold', LINK_SUBMISSION_FILE]"
                                    >
                                        {{ detailRow.original_name || 'Mở file' }}
                                    </a>
                                    <span v-else>—</span>
                                </p>
                                <p v-if="detailRow.status === 'approved' && detailRow.approved_book?.book_code">
                                    <span class="text-slate-400">Mã sách sau duyệt:</span>
                                    <span class="font-mono font-semibold">{{ detailRow.approved_book.book_code }}</span>
                                </p>
                                <p v-if="detailRow.review_note">
                                    <span class="text-slate-400">Ghi chú duyệt:</span>
                                    {{ detailRow.review_note }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-if="detailRow.status === 'pending'"
                    class="mt-6 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800"
                >
                    <button
                        type="button"
                        :class="BTN_SUBMISSION_DANGER_MODAL"
                        :disabled="actionSubmissionId === detailRow.id"
                        @click="openReject(detailRow)"
                    >
                        <Icon :icon="ADMIN_ICONS.xCircle" class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Từ chối
                    </button>
                    <button
                        type="button"
                        :class="BTN_SUBMISSION_SUCCESS_MODAL"
                        :disabled="actionSubmissionId === detailRow.id"
                        @click="approveRow(detailRow)"
                    >
                        <Icon :icon="ADMIN_ICONS.checkCircle" class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Đồng ý
                    </button>
                </div>
            </div>
        </div>

        <!-- Từ chối (đơn / hàng loạt) -->
        <div v-if="rejectRow" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeReject" />
            <div class="relative w-full max-w-lg rounded-xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{
                                rejectBulk
                                    ? `Từ chối ${rejectRow._bulkIds?.length || 0} yêu cầu`
                                    : `Từ chối ${submissionRef(rejectRow)}`
                            }}
                        </h3>
                        <p v-if="!rejectBulk" class="text-xs text-slate-500 dark:text-slate-400">{{ rejectRow.title }}</p>
                    </div>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeReject">
                        <Icon icon="lucide:x" class="h-5 w-5" />
                    </button>
                </div>
                <label class="block space-y-1">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Lý do từ chối (tuỳ chọn)</span>
                    <textarea v-model="rejectNote" rows="4" class="admin-filter-input w-full" placeholder="Nhập lý do để tra cứu nội bộ..." />
                </label>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" class="admin-filter-btn min-h-[40px] px-4 py-2" :disabled="rejectSubmitting" @click="closeReject">
                        Hủy
                    </button>
                    <button
                        type="button"
                        :class="BTN_SUBMISSION_DANGER_MODAL"
                        :disabled="rejectSubmitting"
                        @click="confirmReject"
                    >
                        <Icon v-if="!rejectSubmitting" :icon="ADMIN_ICONS.xCircle" class="h-4 w-4 shrink-0" aria-hidden="true" />
                        {{ rejectSubmitting ? 'Đang xử lý…' : 'Xác nhận từ chối' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
