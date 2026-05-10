<script setup>
import { Icon } from '@iconify/vue';
import { computed, onMounted, ref, watch } from 'vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
import { usersApi } from '@/api/users';
import { extractApiPaginator } from '@/utils/adminPagination';
import { toast } from '@/store/toast';
import { useImageFallback } from '@/composables/useImageFallback';

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const sortBy = ref('newest');
const searchKeyword = ref('');
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const selectedIds = ref([]);
const detailDialogOpen = ref(false);
const detailDialogItem = ref(null);
const proofPreviewOpen = ref(false);
const proofPreviewUrl = ref('');
const rejectDialogOpen = ref(false);
const rejectDialogMode = ref('single');
const rejectDialogTargetId = ref(null);
const rejectDialogNote = ref('');
const rejectDialogSubmitting = ref(false);
const approvingIds = ref([]);
const approveBulkSubmitting = ref(false);
const hidingIds = ref([]);
const { withFallback } = useImageFallback();

function statusLabel(s) {
    if (s === 'approved') return 'Đã duyệt';
    if (s === 'rejected') return 'Đã từ chối';
    return 'Chờ duyệt';
}

function statusClass(s) {
    if (s === 'approved') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
    if (s === 'rejected') return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
}

function renderField(value) {
    return value === null || value === undefined || value === '' ? '—' : value;
}

function hasRequestedText(value) {
    return value != null && String(value).trim() !== '';
}

function userTypeLabel(value) {
    if (value === 'STUDENT') return 'Sinh viên';
    if (value === 'TEACHER') return 'Giáo viên';
    if (value === 'MEMBER') return 'Thành viên';
    return value || '—';
}

/**
 * Chỉ các trường user thực sự gửi trong yêu cầu (ẩn dòng không đổi / không có giá trị yêu cầu).
 * @returns {Array<{ key: string, label: string, from: unknown, to: unknown }>}
 */
function requestChangeLines(item) {
    const lines = [];
    if (hasRequestedText(item?.requested_user_type)) {
        lines.push({
            key: 'user_type',
            label: 'Loại tài khoản',
            from: userTypeLabel(item.user?.user_type || 'MEMBER'),
            to: userTypeLabel(item.requested_user_type),
        });
    }
    if (hasRequestedText(item?.requested_code)) {
        lines.push({
            key: 'code',
            label: 'Mã',
            from: item.user?.code,
            to: item.requested_code,
        });
    }
    const facId = item?.requested_faculty_id;
    if (facId != null && Number(facId) > 0) {
        lines.push({
            key: 'faculty',
            label: 'Khoa',
            from: item.user?.faculty_name,
            to: item.requested_faculty?.name,
        });
    }
    const perId = item?.requested_period_id;
    if (perId != null && Number(perId) > 0) {
        lines.push({
            key: 'period',
            label: 'Niên khóa',
            from: item.user?.period_name,
            to: item.requested_period?.name,
        });
    }
    if (hasRequestedText(item?.requested_class_code)) {
        lines.push({
            key: 'class',
            label: 'Lớp',
            from: item.user?.class_code,
            to: item.requested_class_code,
        });
    }
    return lines;
}

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

function deselectAll() {
    selectedIds.value = [];
}

function toggleDetails(id) {
    const item = rows.value.find((r) => r.id === id) || null;
    detailDialogItem.value = item;
    detailDialogOpen.value = Boolean(item);
}

function closeDetailDialog() {
    detailDialogOpen.value = false;
    detailDialogItem.value = null;
}

function openProofPreview(url) {
    if (!url) {
        return;
    }
    proofPreviewUrl.value = url;
    proofPreviewOpen.value = true;
}

function closeProofPreview() {
    proofPreviewOpen.value = false;
    proofPreviewUrl.value = '';
}

async function loadRequests() {
    loading.value = true;
    try {
        const q = searchKeyword.value.trim();
        const payload = await usersApi.listProfileUpdateRequests({
            status: 'pending',
            page: page.value,
            per_page: 20,
            search: q || undefined,
            sort_by: sortBy.value,
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
        if (detailDialogItem.value && !allowed.has(detailDialogItem.value.id)) {
            closeDetailDialog();
        }
    } catch (e) {
        rows.value = [];
        toast.error(e?.response?.data?.messages || 'Không tải được danh sách yêu cầu.', { title: 'Duyệt yêu cầu' });
    } finally {
        loading.value = false;
    }
}

function runSearch() {
    page.value = 1;
    selectedIds.value = [];
    loadRequests();
}

async function approveRequest(item) {
    if (!item?.id || approvingIds.value.includes(item.id)) {
        return;
    }
    approvingIds.value = [...approvingIds.value, item.id];
    try {
        await usersApi.approveProfileUpdateRequest(item.id, { review_note: null });
        toast.success('Đã duyệt yêu cầu và áp dụng cập nhật.', { title: 'Duyệt yêu cầu' });
        await loadRequests();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể duyệt yêu cầu này.', { title: 'Duyệt yêu cầu' });
    } finally {
        approvingIds.value = approvingIds.value.filter((id) => id !== item.id);
    }
}

async function rejectRequest(item) {
    rejectDialogMode.value = 'single';
    rejectDialogTargetId.value = item.id;
    rejectDialogNote.value = '';
    rejectDialogOpen.value = true;
}

async function approveSelected() {
    if (!selectedIds.value.length || approveBulkSubmitting.value) {
        return;
    }
    approveBulkSubmitting.value = true;
    let ok = 0;
    let fail = 0;
    try {
        for (const id of [...selectedIds.value]) {
            try {
                await usersApi.approveProfileUpdateRequest(id, { review_note: null });
                ok += 1;
            } catch {
                fail += 1;
            }
        }

        if (ok) {
            toast.success(`Đã duyệt ${ok} yêu cầu.${fail ? ` ${fail} lỗi.` : ''}`, { title: 'Duyệt yêu cầu' });
        } else if (fail) {
            toast.error('Không duyệt được yêu cầu nào.', { title: 'Duyệt yêu cầu' });
        }
        selectedIds.value = [];
        await loadRequests();
    } finally {
        approveBulkSubmitting.value = false;
    }
}

async function hideRequest(item) {
    if (!item?.id || hidingIds.value.includes(item.id)) {
        return;
    }
    hidingIds.value = [...hidingIds.value, item.id];
    try {
        await usersApi.hideProfileUpdateRequest(item.id);
        toast.success('Đã ẩn yêu cầu.', { title: 'Duyệt yêu cầu' });
        selectedIds.value = selectedIds.value.filter((id) => id !== item.id);
        await loadRequests();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể ẩn yêu cầu này.', { title: 'Duyệt yêu cầu' });
    } finally {
        hidingIds.value = hidingIds.value.filter((id) => id !== item.id);
    }
}

async function rejectSelected() {
    if (!selectedIds.value.length) {
        return;
    }
    rejectDialogMode.value = 'bulk';
    rejectDialogTargetId.value = null;
    rejectDialogNote.value = '';
    rejectDialogOpen.value = true;
}

function closeRejectDialog(force = false) {
    if (rejectDialogSubmitting.value && !force) {
        return;
    }
    rejectDialogOpen.value = false;
    rejectDialogNote.value = '';
    rejectDialogTargetId.value = null;
}

async function submitRejectDialog() {
    if (rejectDialogSubmitting.value) {
        return;
    }

    rejectDialogSubmitting.value = true;
    const note = rejectDialogNote.value.trim();

    if (rejectDialogMode.value === 'single') {
        if (!rejectDialogTargetId.value) {
            rejectDialogSubmitting.value = false;
            closeRejectDialog();
            return;
        }
        try {
            await usersApi.rejectProfileUpdateRequest(rejectDialogTargetId.value, { review_note: note || null });
            toast.success('Đã từ chối yêu cầu.', { title: 'Duyệt yêu cầu' });
            closeRejectDialog(true);
            await loadRequests();
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không thể từ chối yêu cầu này.', { title: 'Duyệt yêu cầu' });
        } finally {
            rejectDialogSubmitting.value = false;
        }
        return;
    }

    let ok = 0;
    let fail = 0;
    for (const id of [...selectedIds.value]) {
        try {
            await usersApi.rejectProfileUpdateRequest(id, { review_note: note || null });
            ok += 1;
        } catch {
            fail += 1;
        }
    }
    if (ok) {
        toast.success(`Đã từ chối ${ok} yêu cầu.${fail ? ` ${fail} lỗi.` : ''}`, { title: 'Duyệt yêu cầu' });
    } else if (fail) {
        toast.error('Không từ chối được yêu cầu nào.', { title: 'Duyệt yêu cầu' });
    }
    selectedIds.value = [];
    closeRejectDialog(true);
    await loadRequests();
    rejectDialogSubmitting.value = false;
}

const pagination = computed(() => ({
    current_page: meta.value.current_page,
    last_page: meta.value.last_page,
}));

watch(sortBy, () => {
    page.value = 1;
    selectedIds.value = [];
    loadRequests();
});

function goPage(p) {
    page.value = p;
    selectedIds.value = [];
    loadRequests();
}

onMounted(() => {
    loadRequests();
});
</script>

<template>
    <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading title="Duyệt yêu cầu cập nhật hồ sơ">
                <template #description>
                    Duyệt xác nhận Sinh viên/Giáo viên và các thay đổi hồ sơ kèm minh chứng. Mặc định chỉ hiện yêu cầu « Chờ duyệt ».
                </template>
            </AdminPageHeading>

            <AdminFilterSearch
                v-model="searchKeyword"
                search-placeholder="Mã định danh, họ tên, email, SĐT..."
                :show-filter-button="false"
                @search="runSearch"
            >
                <template #filters>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <select v-model="sortBy" class="admin-filter-select !h-9 !py-0 leading-9 w-[112px] max-w-full pr-9">
                                <option value="newest">Mới nhất</option>
                                <option value="oldest">Cũ nhất</option>
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
                class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200/90 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/40 px-4 py-3"
            >
                <span class="text-sm text-slate-600 dark:text-slate-300">
                    Đã chọn <strong>{{ selectedIds.length }}</strong> dòng
                </span>
                <button
                    type="button"
                    class="min-h-[44px] !h-auto py-2.5 px-4 inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                    :disabled="approveBulkSubmitting"
                    @click="approveSelected"
                >
                    <Icon :icon="ADMIN_ICONS.checkCircle" class="w-4 h-4" />
                    {{ approveBulkSubmitting ? 'Đang duyệt...' : 'Đồng ý đã chọn' }}
                </button>
                <button
                    type="button"
                    class="min-h-[44px] !h-auto py-2.5 px-4 inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 text-sm font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                    @click="rejectSelected"
                >
                    <Icon :icon="ADMIN_ICONS.xCircle" class="w-4 h-4" />
                    Từ chối đã chọn
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-[44px] items-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    @click="deselectAll"
                >
                    Bỏ chọn
                </button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[760px]">
                        <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th class="p-4 w-12 align-middle">
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
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Mã yêu cầu
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Mã ĐD
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Họ tên
                                </th>
                                <th class="p-4 align-middle text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 min-w-[180px]">
                                    Yêu cầu
                                </th>
                                <th class="p-4 align-middle text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 min-w-[150px]">
                                    Lý do
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Minh chứng
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 w-[125px]">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-if="loading">
                                <td colspan="8" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Đang tải…</td>
                            </tr>
                            <tr v-else-if="!rows.length">
                                <td colspan="8" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Không có bản ghi.</td>
                            </tr>
                            <template v-else>
                            <template v-for="item in rows" :key="item.id">
                            <tr
                                :class="[selectedIds.includes(item.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                            >
                                <td class="p-4 align-middle">
                                    <span v-if="item.status === 'pending'" class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.includes(item.id)"
                                            class="admin-table-checkbox"
                                            @change="toggleSelect(item.id)"
                                        />
                                    </span>
                                    <span v-else class="inline-block w-6" />
                                </td>
                                <td class="p-4 align-middle whitespace-nowrap">
                                    <p class="inline-flex rounded-md bg-blue-50 px-2 py-1 font-mono text-[12px] text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ item.request_code || `REQ-${item.id}` }}
                                    </p>
                                </td>
                                <td class="p-4 align-middle whitespace-nowrap">
                                    <p class="inline-flex rounded-md bg-slate-100 px-2 py-1 font-mono text-[12px] text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ renderField(item.user?.code) }}
                                    </p>
                                </td>
                                <td class="p-3 align-middle max-w-[130px]">
                                    <p class="font-semibold text-sm text-slate-900 dark:text-white truncate" :title="item.user?.name">
                                        {{ item.user?.name || '—' }}
                                    </p>
                                </td>
                                <td class="p-3 align-middle text-[12px] text-slate-600 dark:text-slate-300 max-w-[210px]">
                                    <div class="space-y-1.5">
                                        <template v-if="requestChangeLines(item).length">
                                            <div
                                                v-for="line in requestChangeLines(item)"
                                                :key="`${item.id}-${line.key}`"
                                                class="flex items-center gap-1.5 truncate rounded-md border border-slate-200/70 px-2 py-1 dark:border-slate-700/80"
                                            >
                                                <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                    {{ line.label }}:
                                                </span>
                                                <span class="min-w-0 truncate text-[11px]" :title="`${renderField(line.from)} -> ${renderField(line.to)}`">
                                                    {{ renderField(line.from) }} -> {{ renderField(line.to) }}
                                                </span>
                                            </div>
                                        </template>
                                        <p v-else class="text-slate-400 dark:text-slate-500">
                                            Không có mô tả trường thay đổi (xem minh chứng / chi tiết).
                                        </p>
                                    </div>
                                </td>
                                <td class="p-3 align-middle text-[12px] text-slate-600 dark:text-slate-300 max-w-[130px]">
                                    <p class="truncate whitespace-nowrap" :title="item.reason || ''">{{ item.reason || '—' }}</p>
                                </td>
                                <td class="p-3 align-middle">
                                    <a
                                        v-if="item.proof_image_url"
                                        href="#"
                                        class="inline-flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800 hover:ring-2 hover:ring-blue-500/30"
                                        title="Xem ảnh minh chứng"
                                        @click.prevent="openProofPreview(item.proof_image_url)"
                                    >
                                        <img
                                            :src="item.proof_image_url"
                                            alt="Minh chứng"
                                            class="h-full w-full object-cover"
                                            @error="withFallback('/images/default-news-cover.jpg')($event)"
                                        />
                                    </a>
                                    <span v-else class="text-[12px] text-slate-500">—</span>
                                </td>
                                <td class="p-3 align-middle">
                                    <div v-if="item.status === 'pending'" class="grid grid-cols-1 gap-1.5">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-slate-50 px-2 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                                            title="Xem chi tiết thay đổi"
                                            @click="toggleDetails(item.id)"
                                        >
                                            <Icon icon="lucide:eye" class="w-3.5 h-3.5 shrink-0" />
                                            <span>Chi tiết</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-2 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50 transition-colors"
                                            title="Đồng ý — cập nhật hồ sơ người dùng"
                                            :disabled="approvingIds.includes(item.id)"
                                            @click="approveRequest(item)"
                                        >
                                            <Icon icon="lucide:check-circle-2" class="w-3.5 h-3.5 shrink-0" />
                                            <span>{{ approvingIds.includes(item.id) ? 'Đang...' : 'Đồng ý' }}</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-2 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45 transition-colors"
                                            title="Từ chối — không đổi dữ liệu"
                                            @click="rejectRequest(item)"
                                        >
                                            <Icon icon="lucide:x-circle" class="w-3.5 h-3.5 shrink-0" />
                                            <span>Từ chối</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                                            title="Ẩn yêu cầu"
                                            :disabled="hidingIds.includes(item.id)"
                                            @click="hideRequest(item)"
                                        >
                                            <Icon icon="lucide:trash-2" class="w-3.5 h-3.5 shrink-0" />
                                            <span>{{ hidingIds.includes(item.id) ? 'Đang xóa...' : 'Xóa' }}</span>
                                        </button>
                                    </div>
                                    <div v-else class="grid grid-cols-1 gap-1.5">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-slate-50 px-2 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                                            title="Xem chi tiết thay đổi"
                                            @click="toggleDetails(item.id)"
                                        >
                                            <Icon icon="lucide:eye" class="w-3.5 h-3.5 shrink-0" />
                                            <span>Chi tiết</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                                            title="Ẩn yêu cầu"
                                            :disabled="hidingIds.includes(item.id)"
                                            @click="hideRequest(item)"
                                        >
                                            <Icon icon="lucide:trash-2" class="w-3.5 h-3.5 shrink-0" />
                                            <span>{{ hidingIds.includes(item.id) ? 'Đang xóa...' : 'Xóa' }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <AdminPaginationBar
                :current-page="pagination.current_page"
                :last-page="pagination.last_page"
                :disabled="loading"
                @go-page="goPage"
            />

            <p class="text-xs text-slate-500 dark:text-slate-400 flex gap-1.5 items-start">
                <Icon icon="lucide:info" class="w-3.5 h-3.5 shrink-0 mt-0.5" />
                <span>
                    « Đồng ý » áp dụng loại xác nhận và thông tin hồ sơ đã duyệt vào tài khoản; « Từ chối » giữ nguyên dữ liệu hiện tại.
                </span>
            </p>

            <div
                v-if="detailDialogOpen && detailDialogItem"
                class="fixed inset-0 z-[61] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                @click.self="closeDetailDialog"
            >
                <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-[1px]" />
                <div class="relative w-full max-w-5xl rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết yêu cầu cập nhật</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ detailDialogItem.user?.name || 'Người dùng' }} · {{ renderField(detailDialogItem.user?.code) }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="min-h-[40px] rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="closeDetailDialog"
                        >
                            Đóng
                        </button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-3">
                            <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Thông tin cập nhật</p>
                                <div class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                                    <template v-if="requestChangeLines(detailDialogItem).length">
                                        <p v-for="line in requestChangeLines(detailDialogItem)" :key="`modal-${detailDialogItem.id}-${line.key}`">
                                            <span class="text-slate-400 dark:text-slate-500">{{ line.label }}:</span>
                                            {{ renderField(line.from) }} → {{ renderField(line.to) }}
                                        </p>
                                    </template>
                                    <p v-else class="text-slate-500 dark:text-slate-400">Không có trường được liệt kê trong yêu cầu.</p>
                                </div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Lý do người dùng</p>
                                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ detailDialogItem.reason || '—' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ghi chú xử lý</p>
                                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ detailDialogItem.review_note || '—' }}</p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-blue-200 bg-blue-50/40 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">Minh chứng</p>
                            <div class="mt-2">
                                <button
                                    v-if="detailDialogItem.proof_image_url"
                                    type="button"
                                    class="block w-full overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900"
                                    @click="openProofPreview(detailDialogItem.proof_image_url)"
                                >
                                    <img
                                        :src="detailDialogItem.proof_image_url"
                                        alt="Minh chứng"
                                        class="h-[260px] w-full object-contain bg-slate-100 dark:bg-slate-800"
                                        @error="withFallback('/images/default-news-cover.jpg')($event)"
                                    />
                                </button>
                                <p v-else class="text-sm text-slate-500 dark:text-slate-400">Không có ảnh minh chứng.</p>
                            </div>
                            <div v-if="detailDialogItem.proof_image_url" class="mt-3 flex justify-end">
                                <a
                                    :href="detailDialogItem.proof_image_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-[40px] items-center rounded-lg border border-blue-300 bg-blue-50 px-3 text-sm font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/45"
                                >
                                    Mở ảnh tab mới
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="proofPreviewOpen && proofPreviewUrl"
                class="fixed inset-0 z-[62] flex items-center justify-center bg-slate-950/80 p-4"
                role="dialog"
                aria-modal="true"
                @click.self="closeProofPreview"
            >
                <div class="relative w-full max-w-6xl">
                    <button
                        type="button"
                        class="absolute right-0 top-0 -translate-y-12 rounded-lg border border-slate-500 bg-slate-900/80 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-800"
                        @click="closeProofPreview"
                    >
                        Đóng
                    </button>
                    <img :src="proofPreviewUrl" alt="Ảnh minh chứng chi tiết" class="max-h-[85vh] w-full rounded-xl object-contain" @error="withFallback('/images/default-news-cover.jpg')($event)" />
                </div>
            </div>

            <div
                v-if="rejectDialogOpen"
                class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                @click.self="closeRejectDialog"
            >
                <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-[1px]" />
                <div class="relative w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                    <div class="mb-4">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{ rejectDialogMode === 'bulk' ? `Từ chối ${selectedIds.length} yêu cầu đã chọn` : 'Từ chối yêu cầu cập nhật hồ sơ' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            Bạn có thể nhập lý do để lưu lại lịch sử xử lý (tuỳ chọn).
                        </p>
                    </div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        Lý do từ chối
                        <textarea
                            v-model="rejectDialogNote"
                            rows="4"
                            maxlength="1000"
                            class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            placeholder="Nhập lý do (nếu có)..."
                        />
                    </label>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ rejectDialogNote.length }}/1000 ký tự
                    </p>

                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button
                            type="button"
                            class="min-h-[44px] rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            :disabled="rejectDialogSubmitting"
                            @click="closeRejectDialog"
                        >
                            Hủy
                        </button>
                        <button
                            type="button"
                            class="min-h-[44px] rounded-lg border border-rose-300 bg-rose-50 px-4 text-sm font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-60 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                            :disabled="rejectDialogSubmitting"
                            @click="submitRejectDialog"
                        >
                            {{ rejectDialogSubmitting ? 'Đang xử lý...' : 'Xác nhận từ chối' }}
                        </button>
                    </div>
                </div>
            </div>
    </div>
</template>
