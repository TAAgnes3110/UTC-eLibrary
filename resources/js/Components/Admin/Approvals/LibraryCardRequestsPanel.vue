<script setup>
import { Icon } from '@iconify/vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import LibraryCardsTable from '@/Components/Admin/LibraryCards/LibraryCardsTable.vue';
import { useLibraryCardsAdminPage } from '@/composables/admin/useLibraryCardsAdminPage';
import { HOLDER_LABELS } from '@/config/libraryCardUi';

const props = defineProps({
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
});

const lc = useLibraryCardsAdminPage(props, { screen: 'requests' });
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
            @reject="lc.onReject"
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
    </div>
</template>
