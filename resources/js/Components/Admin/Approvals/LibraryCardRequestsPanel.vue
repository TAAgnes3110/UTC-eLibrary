<script setup>
import { Icon } from '@iconify/vue';
import { ref } from 'vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import LibraryCardsTable from '@/Components/Admin/LibraryCards/LibraryCardsTable.vue';
import { libraryCardsApi } from '@/api/libraryCards';
import { toast } from '@/store/toast';
import { useLibraryCardsAdminPage } from '@/composables/admin/useLibraryCardsAdminPage';
import { HOLDER_LABELS } from '@/config/libraryCardUi';

const props = defineProps({
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
});

const lc = useLibraryCardsAdminPage(props, { screen: 'requests' });
const detailRow = ref(null);
const rejectRow = ref(null);
const rejectNote = ref('');
const rejectLoading = ref(false);

function openDetail(row) {
    detailRow.value = row;
}

function closeDetail() {
    detailRow.value = null;
}

function openReject(row) {
    rejectRow.value = row;
    rejectNote.value = '';
}

function closeReject() {
    rejectRow.value = null;
    rejectNote.value = '';
}

async function confirmReject() {
    if (!rejectRow.value) return;
    rejectLoading.value = true;
    try {
        const notes = rejectNote.value.trim();
        await libraryCardsApi.rejectReview(rejectRow.value.id, notes ? { notes } : {});
        toast.success('Đã từ chối hồ sơ.', { title: 'Thành công' });
        closeReject();
        await lc.loadCards();
    } catch (e) {
        const msg = e?.response?.data?.messages || e?.response?.data?.message || 'Không từ chối được.';
        toast.error(msg, { title: 'Lỗi' });
    } finally {
        rejectLoading.value = false;
    }
}
</script>

<template>
    <div class="space-y-4 animate-in fade-in-50 duration-500">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white">Duyệt yêu cầu cấp thẻ</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Chỉ hiển thị hồ sơ đang « Chờ duyệt ». Thẻ đã kích hoạt hoặc đã từ chối không nằm ở đây.
        </p>

        <AdminFilterSearch
            v-model="lc.filterValues.searchKeyword"
            search-placeholder="Mã thẻ, mã định danh, họ tên, email, SĐT..."
            :show-filter-button="false"
            @search="() => lc.searchCards()"
        >
            <template #filters>
                <div class="flex flex-wrap items-center gap-2">
                    <AdminFilterPanel
                        :options="lc.LIBRARY_CARD_SEARCH_IN_OPTIONS"
                        v-model:model-value="lc.filterValues.searchIn"
                        :show="lc.showFilterPanel"
                        @update:show="lc.showFilterPanel = $event"
                    />
                    <div class="relative">
                        <select v-model="lc.filterValues.holderType" class="admin-filter-select !h-9 !py-0 leading-9 w-[170px] max-w-full pr-9">
                            <option value="">Loại thẻ: Tất cả</option>
                            <option value="student">{{ HOLDER_LABELS.student }}</option>
                            <option value="teacher">{{ HOLDER_LABELS.teacher }}</option>
                            <option value="external">{{ HOLDER_LABELS.external }}</option>
                        </select>
                        <Icon
                            icon="lucide:chevron-down"
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                        />
                    </div>
                    <div class="relative">
                        <select v-model="lc.filterValues.sortBy" class="admin-filter-select !h-9 !py-0 leading-9 w-[112px] max-w-full pr-9">
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                            <option value="name_asc">Tên A-Z</option>
                            <option value="name_desc">Tên Z-A</option>
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
            v-if="lc.hasSelection"
            class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200/90 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/40 px-4 py-3"
        >
            <span class="text-sm text-slate-600 dark:text-slate-300">
                Đã chọn <strong>{{ lc.selectedIds.length }}</strong> dòng
            </span>
            <button
                type="button"
                class="min-h-[44px] !h-auto py-2.5 px-4 inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                @click="lc.onApproveSelected"
            >
                <Icon icon="lucide:check-circle" class="w-4 h-4" />
                Đồng ý đã chọn
            </button>
            <button
                type="button"
                class="min-h-[44px] !h-auto py-2.5 px-4 inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 text-sm font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                @click="lc.onRejectSelected"
            >
                <Icon icon="lucide:x-circle" class="w-4 h-4" />
                Từ chối đã chọn
            </button>
            <button
                type="button"
                class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 min-h-[44px] px-2"
                @click="lc.deselectAll"
            >
                Bỏ chọn
            </button>
        </div>

        <LibraryCardsTable
            :rows="lc.cards"
            :selected-ids="lc.selectedIds"
            :loading-fallback="lc.loadingFallback"
            :is-all-selected="lc.isAllSelected"
            :has-selection="lc.hasSelection"
            :show-card-status="false"
            :show-approve="true"
            review-mode
            @toggle-all="lc.toggleSelectAll"
            @toggle="lc.toggleSelect"
            @approve="lc.onApprove"
            @reject="openReject"
            @detail="openDetail"
        />

        <AdminPaginationBar
            :current-page="lc.pageNum"
            :last-page="lc.meta.last_page"
            :disabled="lc.loadingFallback"
            @go-page="lc.goPage"
        />

        <p class="text-xs text-slate-500 dark:text-slate-400">
            <Icon icon="lucide:info" class="inline w-3.5 h-3.5 -mt-0.5" />
            « Đồng ý » kích hoạt thẻ (hoạt động); « Từ chối » loại hồ sơ khỏi hệ thống (xóa mềm).
        </p>

        <div v-if="detailRow" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeDetail" />
            <div class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết hồ sơ cấp thẻ</h3>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeDetail">
                        <Icon icon="lucide:x" class="h-5 w-5" />
                    </button>
                </div>
                <div class="grid gap-3 md:grid-cols-2 text-sm">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Thông tin thẻ</p>
                        <div class="mt-2 space-y-1.5 text-slate-700 dark:text-slate-300">
                            <p><span class="text-slate-400">Mã thẻ:</span> {{ detailRow.card_number || '—' }}</p>
                            <p><span class="text-slate-400">Loại thẻ:</span> {{ HOLDER_LABELS[detailRow.holder_type] || detailRow.holder_type || '—' }}</p>
                            <p><span class="text-slate-400">Quy trình:</span> {{ detailRow.workflow_status || '—' }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Thông tin bạn đọc</p>
                        <div class="mt-2 space-y-1.5 text-slate-700 dark:text-slate-300">
                            <p><span class="text-slate-400">Họ tên:</span> {{ detailRow.full_name || '—' }}</p>
                            <p><span class="text-slate-400">Email:</span> {{ detailRow.email || '—' }}</p>
                            <p><span class="text-slate-400">SĐT:</span> {{ detailRow.phone || '—' }}</p>
                            <p><span class="text-slate-400">Mã định danh:</span> {{ detailRow.code || '—' }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Địa chỉ / ghi chú</p>
                        <div class="mt-2 space-y-1.5 text-slate-700 dark:text-slate-300">
                            <p><span class="text-slate-400">Địa chỉ:</span> {{ detailRow.address || '—' }}</p>
                            <p><span class="text-slate-400">Ghi chú:</span> {{ detailRow.notes || '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="rejectRow" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeReject" />
            <div class="relative w-full max-w-lg rounded-xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Từ chối hồ sơ cấp thẻ</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Mã thẻ: {{ rejectRow.card_number || '—' }}</p>
                    </div>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeReject">
                        <Icon icon="lucide:x" class="h-5 w-5" />
                    </button>
                </div>
                <label class="block space-y-1">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Lý do từ chối (tuỳ chọn)</span>
                    <textarea v-model="rejectNote" rows="4" class="admin-filter-input w-full" placeholder="Nhập lý do để bạn đọc dễ theo dõi..." />
                </label>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" class="admin-filter-btn px-4 py-2 min-h-[40px]" @click="closeReject">Hủy</button>
                    <button
                        type="button"
                        class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100 disabled:opacity-50 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/45"
                        :disabled="rejectLoading"
                        @click="confirmReject"
                    >
                        Xác nhận từ chối
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
