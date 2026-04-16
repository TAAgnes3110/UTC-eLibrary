<script setup>
import { router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { computed, onMounted, ref, watch } from 'vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
import { loansApi } from '@/api/loans';
import { extractApiPaginator } from '@/utils/adminPagination';
import { toast } from '@/store/toast';

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const status = ref('pending');
const searchKeyword = ref('');
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const actionId = ref(null);

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
            status: status.value || undefined,
            search: q || undefined,
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
    } catch (e) {
        rows.value = [];
        toast.error(e?.response?.data?.messages || 'Không tải được danh sách yêu cầu gia hạn.', { title: 'Gia hạn' });
    } finally {
        loading.value = false;
    }
}

function runSearch() {
    page.value = 1;
    loadRows();
}

function goPage(p) {
    page.value = p;
    loadRows();
}

watch(status, () => {
    page.value = 1;
    loadRows();
});

onMounted(loadRows);

const lastPage = computed(() => Math.max(1, Number(meta.value.last_page) || 1));

const pagination = computed(() => ({
    current_page: meta.value.current_page,
    last_page: meta.value.last_page,
}));

async function approveRow(row) {
    const note = window.prompt('Ghi chú duyệt (tuỳ chọn):', '') ?? '';
    actionId.value = row.id;
    try {
        await loansApi.approveRenewalRequest(row.id, { review_note: note || null });
        toast.success('Đã duyệt gia hạn.', { title: 'Gia hạn' });
        await loadRows();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể duyệt.', { title: 'Gia hạn' });
    } finally {
        actionId.value = null;
    }
}

async function rejectRow(row) {
    const note = window.prompt('Lý do từ chối (tuỳ chọn):', '') ?? '';
    actionId.value = row.id;
    try {
        await loansApi.rejectRenewalRequest(row.id, { review_note: note || null });
        toast.success('Đã từ chối yêu cầu.', { title: 'Gia hạn' });
        await loadRows();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể từ chối.', { title: 'Gia hạn' });
    } finally {
        actionId.value = null;
    }
}
</script>

<template>
    <div class="space-y-4 animate-in fade-in-50 duration-500">
        <AdminPageHeading title="Duyệt yêu cầu gia hạn mượn">
            <template #description>
                Bạn đọc xin gia hạn hạn trả theo chính sách thư viện. Mặc định hiển thị « Chờ duyệt »; đổi « Trạng thái » để xem đã xử lý.
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
                    <div class="relative">
                        <select v-model="status" class="admin-filter-select !h-9 !py-0 leading-9 min-w-[200px] max-w-full pr-9">
                            <option v-for="opt in statusOptions" :key="opt.key || 'all'" :value="opt.key">
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

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/60">
                        <tr>
                            <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 sm:p-4">
                                Mã yêu cầu
                            </th>
                            <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 sm:p-4">
                                Phiếu / thẻ
                            </th>
                            <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 sm:p-4">
                                Bạn đọc
                            </th>
                            <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 sm:p-4">
                                Hạn hiện tại → đề xuất
                            </th>
                            <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 sm:p-4">
                                Trạng thái
                            </th>
                            <th
                                class="p-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-200 sm:p-4"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-if="loading">
                            <td colspan="6" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Đang tải…</td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td colspan="6" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Không có bản ghi.</td>
                        </tr>
                        <template v-else>
                            <tr v-for="row in rows" :key="row.id" class="admin-table-row">
                                <td class="p-3 tabular-nums sm:p-4">#{{ row.id }}</td>
                                <td class="p-3 sm:p-4">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ row.loan?.loan_code || '—' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ row.loan?.library_card_number || '—' }} — {{ row.loan?.library_card_name || '' }}
                                    </div>
                                </td>
                                <td class="p-3 sm:p-4">
                                    <div class="text-slate-900 dark:text-white">{{ row.requester?.name || '—' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ row.requester?.code || '' }}</div>
                                </td>
                                <td class="whitespace-nowrap p-3 text-slate-700 dark:text-slate-300 sm:p-4">
                                    {{ formatDate(row.current_due_date) }} → {{ formatDate(row.requested_due_date) }}
                                </td>
                                <td class="p-3 sm:p-4">
                                    <span
                                        class="inline-flex rounded-md px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap"
                                        :class="statusClass(row.status)"
                                    >
                                        {{ statusLabel(row.status) }}
                                    </span>
                                </td>
                                <td class="p-3 text-right sm:p-4">
                                    <div v-if="row.status === 'pending'" class="flex flex-wrap justify-end gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            :disabled="actionId === row.id"
                                            title="Duyệt gia hạn"
                                            @click="approveRow(row)"
                                        >
                                            <Icon :icon="ADMIN_ICONS.checkCircle" class="h-4 w-4 shrink-0" />
                                            Đồng ý
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-100 disabled:opacity-50 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                                            :disabled="actionId === row.id"
                                            title="Từ chối yêu cầu"
                                            @click="rejectRow(row)"
                                        >
                                            <Icon :icon="ADMIN_ICONS.xCircle" class="h-4 w-4 shrink-0" />
                                            Từ chối
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-slate-500 dark:text-slate-400">—</span>
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

        <p class="flex items-start gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <Icon icon="lucide:info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
            <span>
                « Đồng ý » cập nhật hạn trả phiếu theo ngày đề xuất; « Từ chối » giữ hạn cũ và đánh dấu yêu cầu đã từ chối.
            </span>
        </p>
    </div>
</template>
