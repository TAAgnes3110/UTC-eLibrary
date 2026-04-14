<script setup>
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { computed, onMounted, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
import { usersApi } from '@/api/users';
import { extractApiPaginator } from '@/utils/adminPagination';
import { toast } from '@/store/toast';

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const status = ref('pending');
const sortBy = ref('newest');
const searchKeyword = ref('');
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const selectedIds = ref([]);
const expandedRowId = ref(null);

const statusOptions = [
    { key: '', label: 'Trạng thái: Tất cả' },
    { key: 'pending', label: 'Chờ duyệt' },
    { key: 'approved', label: 'Đã duyệt' },
    { key: 'rejected', label: 'Đã từ chối' },
];

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
    expandedRowId.value = expandedRowId.value === id ? null : id;
}

async function loadRequests() {
    loading.value = true;
    try {
        const q = searchKeyword.value.trim();
        const payload = await usersApi.listProfileUpdateRequests({
            status: status.value || undefined,
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
        if (expandedRowId.value && !allowed.has(expandedRowId.value)) {
            expandedRowId.value = null;
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
    try {
        await usersApi.approveProfileUpdateRequest(item.id, { review_note: null });
        toast.success('Đã duyệt yêu cầu và áp dụng cập nhật.', { title: 'Duyệt yêu cầu' });
        await loadRequests();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể duyệt yêu cầu này.', { title: 'Duyệt yêu cầu' });
    }
}

async function rejectRequest(item) {
    const note = window.prompt('Lý do từ chối (tuỳ chọn):', '');
    try {
        await usersApi.rejectProfileUpdateRequest(item.id, { review_note: note || null });
        toast.success('Đã từ chối yêu cầu.', { title: 'Duyệt yêu cầu' });
        await loadRequests();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể từ chối yêu cầu này.', { title: 'Duyệt yêu cầu' });
    }
}

async function approveSelected() {
    if (!selectedIds.value.length) {
        return;
    }
    let ok = 0;
    let fail = 0;
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
}

async function rejectSelected() {
    if (!selectedIds.value.length) {
        return;
    }
    const note = window.prompt(`Lý do từ chối cho ${selectedIds.value.length} yêu cầu (tuỳ chọn):`, '');
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
    await loadRequests();
}

const pagination = computed(() => ({
    current_page: meta.value.current_page,
    last_page: meta.value.last_page,
}));

watch([status, sortBy], () => {
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
    <Head title="Duyệt yêu cầu cập nhật - Admin" />
    <AdminLayout
        title="Duyệt yêu cầu cập nhật hồ sơ"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý người dùng' },
            { label: 'Duyệt yêu cầu cập nhật' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading title="Duyệt yêu cầu cập nhật hồ sơ">
                <template #description>
                    Thay đổi mã định danh, khoa, niên khóa hoặc lớp cần ảnh minh chứng. Mặc định chỉ hiện yêu cầu « Chờ duyệt »; đổi « Trạng thái » để xem đã xử lý.
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
                            <select v-model="status" class="admin-filter-select !h-9 !py-0 leading-9 min-w-[200px] max-w-full pr-9">
                                <option v-for="opt in statusOptions" :key="opt.key" :value="opt.key">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <Icon
                                :icon="ADMIN_ICONS.chevronDown"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                            />
                        </div>
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
                    @click="approveSelected"
                >
                    <Icon :icon="ADMIN_ICONS.checkCircle" class="w-4 h-4" />
                    Đồng ý đã chọn
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
                    class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 min-h-[44px] px-2"
                    @click="deselectAll"
                >
                    Bỏ chọn
                </button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1100px]">
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
                                    Mã ĐD
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Họ tên
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Email
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Số ĐT
                                </th>
                                <th class="p-4 align-middle text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 min-w-[200px]">
                                    Hiện tại → Yêu cầu
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Minh chứng
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200">
                                    Trạng thái
                                </th>
                                <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 w-[220px]">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-if="loading">
                                <td colspan="9" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Đang tải…</td>
                            </tr>
                            <tr v-else-if="!rows.length">
                                <td colspan="9" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Không có bản ghi.</td>
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
                                    <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">
                                        {{ renderField(item.user?.code) }}
                                    </p>
                                </td>
                                <td class="p-4 align-middle max-w-[200px] xl:max-w-[240px]">
                                    <p class="font-semibold text-sm text-slate-900 dark:text-white truncate" :title="item.user?.name">
                                        {{ item.user?.name || '—' }}
                                    </p>
                                </td>
                                <td class="p-4 align-middle max-w-[200px] xl:max-w-[260px]">
                                    <p class="text-[12px] text-slate-600 dark:text-slate-300 truncate" :title="item.user?.email">
                                        {{ item.user?.email || '—' }}
                                    </p>
                                </td>
                                <td class="p-4 align-middle whitespace-nowrap">
                                    <p class="text-[12px] text-slate-600 dark:text-slate-300">{{ renderField(item.user?.phone) }}</p>
                                </td>
                                <td class="p-4 align-middle text-[12px] text-slate-600 dark:text-slate-300">
                                    <div class="space-y-1">
                                        <p>
                                            <span class="text-slate-400 dark:text-slate-500">Mã:</span>
                                            {{ renderField(item.user?.code) }} → {{ renderField(item.requested_code) }}
                                        </p>
                                        <p>
                                            <span class="text-slate-400 dark:text-slate-500">Khoa:</span>
                                            {{ renderField(item.user?.faculty_name) }} → {{ renderField(item.requested_faculty?.name) }}
                                        </p>
                                        <p>
                                            <span class="text-slate-400 dark:text-slate-500">Niên khóa:</span>
                                            {{ renderField(item.user?.period_name) }} → {{ renderField(item.requested_period?.name) }}
                                        </p>
                                        <p>
                                            <span class="text-slate-400 dark:text-slate-500">Lớp:</span>
                                            {{ renderField(item.user?.class_code) }} → {{ renderField(item.requested_class_code) }}
                                        </p>
                                        <p v-if="item.reason" class="text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800">
                                            Lý do SV: {{ item.reason }}
                                        </p>
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    <a
                                        v-if="item.proof_image_url"
                                        :href="item.proof_image_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden hover:ring-2 hover:ring-blue-500/30"
                                        title="Xem ảnh minh chứng"
                                    >
                                        <img
                                            :src="item.proof_image_url"
                                            alt="Minh chứng"
                                            class="h-11 w-11 object-cover"
                                        />
                                    </a>
                                    <span v-else class="text-[12px] text-slate-500">—</span>
                                </td>
                                <td class="p-4 align-middle whitespace-nowrap">
                                    <span
                                        :class="[
                                            statusClass(item.status),
                                            'inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold whitespace-nowrap',
                                        ]"
                                    >
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </td>
                                <td class="p-4 align-middle whitespace-nowrap">
                                    <div v-if="item.status === 'pending'" class="flex flex-nowrap justify-start gap-1">
                                        <button
                                            type="button"
                                            class="min-h-[38px] inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-slate-50 px-2.5 py-1.5 text-[12px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                                            title="Xem chi tiết thay đổi"
                                            @click="toggleDetails(item.id)"
                                        >
                                            <Icon icon="lucide:eye" class="w-3.5 h-3.5 shrink-0" />
                                            <span class="leading-none">Chi tiết</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="min-h-[38px] inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-2.5 py-1.5 text-[12px] font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50 transition-colors"
                                            title="Đồng ý — cập nhật hồ sơ người dùng"
                                            @click="approveRequest(item)"
                                        >
                                            <Icon icon="lucide:check-circle-2" class="w-3.5 h-3.5 shrink-0" />
                                            <span class="leading-none">Đồng ý</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="min-h-[38px] inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-2.5 py-1.5 text-[12px] font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45 transition-colors"
                                            title="Từ chối — không đổi dữ liệu"
                                            @click="rejectRequest(item)"
                                        >
                                            <Icon icon="lucide:x-circle" class="w-3.5 h-3.5 shrink-0" />
                                            <span class="leading-none">Từ chối</span>
                                        </button>
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="min-h-[36px] inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-slate-50 px-2 py-1 text-[12px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                                            title="Xem chi tiết thay đổi"
                                            @click="toggleDetails(item.id)"
                                        >
                                            <Icon icon="lucide:eye" class="w-3.5 h-3.5 shrink-0" />
                                            <span class="leading-none">Chi tiết</span>
                                        </button>
                                        <p class="text-[12px] text-slate-500 dark:text-slate-400 max-w-[130px] truncate" :title="item.review_note || ''">
                                            {{ item.review_note || '—' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="expandedRowId === item.id" class="bg-slate-50/70 dark:bg-slate-800/40">
                                <td colspan="9" class="px-4 py-4">
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Thông tin hiện tại</p>
                                            <div class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                                                <p><span class="text-slate-400">Mã định danh:</span> {{ renderField(item.user?.code) }}</p>
                                                <p><span class="text-slate-400">Khoa:</span> {{ renderField(item.user?.faculty_name) }}</p>
                                                <p><span class="text-slate-400">Niên khóa:</span> {{ renderField(item.user?.period_name) }}</p>
                                                <p><span class="text-slate-400">Lớp:</span> {{ renderField(item.user?.class_code) }}</p>
                                            </div>
                                        </div>
                                        <div class="rounded-lg border border-blue-200 bg-blue-50/60 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">Yêu cầu thay đổi</p>
                                            <div class="mt-2 space-y-1.5 text-sm text-slate-800 dark:text-slate-200">
                                                <p><span class="text-slate-500 dark:text-slate-400">Mã định danh:</span> {{ renderField(item.requested_code) }}</p>
                                                <p><span class="text-slate-500 dark:text-slate-400">Khoa:</span> {{ renderField(item.requested_faculty?.name) }}</p>
                                                <p><span class="text-slate-500 dark:text-slate-400">Niên khóa:</span> {{ renderField(item.requested_period?.name) }}</p>
                                                <p><span class="text-slate-500 dark:text-slate-400">Lớp:</span> {{ renderField(item.requested_class_code) }}</p>
                                            </div>
                                        </div>
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Lý do sinh viên</p>
                                            <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ item.reason || '—' }}</p>
                                        </div>
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ghi chú xử lý</p>
                                            <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ item.review_note || '—' }}</p>
                                        </div>
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
                    « Đồng ý » áp dụng mã định danh / khoa / niên khóa / lớp đã duyệt vào tài khoản; « Từ chối » giữ nguyên dữ liệu hiện tại và đánh dấu yêu cầu là đã từ chối.
                </span>
            </p>
        </div>
    </AdminLayout>
</template>
