<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import LoansTable from '@/Components/Admin/Loans/LoansTable.vue';
import { LOANS_SEARCH_IN_OPTIONS, useLoansAdminPage } from '@/composables/admin/useLoansAdminPage';

const {
    loading,
    rows,
    selectedIds,
    hasSelection,
    isAllSelected,
    toggleSelect,
    toggleSelectAll,
    deselectAll,
    bulkDeletableLoanIds,
    showBulkDeleteModal,
    bulkDeleteLoading,
    showSingleDeleteModal,
    singleDeleteLoading,
    deletingLoan,
    openBulkDelete,
    confirmBulkDelete,
    closeSingleDeleteModal,
    confirmSingleDelete,
    filterValues,
    showFilterPanel,
    loansPagination,
    loadLoans,
    goLoansPage,
    emptyText,
    goCreate,
    goShow,
    goEdit,
    goReturn,
    removeLoan,
    exportExcel,
} = useLoansAdminPage();
</script>

<template>
    <Head title="Quản lý phiếu mượn" />
    <AdminLayout
        title="Quản lý phiếu"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Phiếu mượn' }, { label: 'Quản lý phiếu' }]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading title="Danh sách phiếu mượn" />

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                :show-delete-selected="true"
                :show-return-selected="false"
                :show-import="false"
                :show-update-file="false"
                add-label="Thêm mới"
                @add="goCreate"
                @export-excel="exportExcel"
                @delete-selected="openBulkDelete"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Tìm mã phiếu, mã thẻ, tên độc giả..."
                :show-filter-button="false"
                @search="loadLoans(true)"
            >
                <template #filters>
                    <div class="flex items-center gap-3 flex-wrap">
                        <AdminFilterPanel
                            :options="LOANS_SEARCH_IN_OPTIONS"
                            v-model:model-value="filterValues.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select
                            v-model="filterValues.status"
                            class="admin-filter-select admin-filter-select-centered min-w-[148px]"
                        >
                            <option value="">Trạng thái</option>
                            <option value="da_muon">Đang mượn</option>
                            <option value="da_tra">Đã trả</option>
                            <option value="qua_han">Quá hạn</option>
                        </select>
                        <select
                            v-model="filterValues.sort"
                            class="admin-filter-select admin-filter-select-centered min-w-[188px]"
                        >
                            <option value="">Sắp xếp</option>
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                            <option value="due_asc">Hạn trả tăng dần</option>
                            <option value="due_desc">Hạn trả giảm dần</option>
                            <option value="loan_asc">Ngày mượn tăng dần</option>
                            <option value="loan_desc">Ngày mượn giảm dần</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <LoansTable
                :rows="rows"
                :selected-ids="selectedIds"
                :is-all-selected="isAllSelected"
                :has-selection="hasSelection"
                :loading-fallback="loading"
                :empty-text="emptyText"
                @toggle-select-all="toggleSelectAll"
                @toggle-select="toggleSelect"
                @show="goShow"
                @edit="goEdit"
                @return="goReturn"
                @delete="removeLoan"
            />

            <AdminPaginationBar
                always-show
                :current-page="loansPagination.current_page"
                :last-page="loansPagination.last_page"
                :disabled="loading"
                @go-page="goLoansPage"
            />
        </div>

        <AdminDeleteConfirmModal
            :show="showBulkDeleteModal"
            title="Xóa phiếu khỏi danh sách"
            confirm-button-label="Xóa"
            item-label="phiếu mượn"
            :item="null"
            :selected-count="bulkDeletableLoanIds.length"
            :loading="bulkDeleteLoading"
            @close="showBulkDeleteModal = false"
            @confirm="confirmBulkDelete"
        />

        <AdminDeleteConfirmModal
            :show="showSingleDeleteModal"
            title="Xóa phiếu khỏi danh sách"
            confirm-button-label="Xóa"
            item-label="phiếu mượn"
            :item="deletingLoan"
            :selected-count="0"
            :loading="singleDeleteLoading"
            @close="closeSingleDeleteModal"
            @confirm="confirmSingleDelete"
        />

    </AdminLayout>
</template>
