<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import LoansTable from '@/Components/Admin/Loans/LoansTable.vue';
import { LOANS_SEARCH_IN_OPTIONS, useLoansAdminPage } from '@/composables/admin/useLoansAdminPage';

const {
    loading,
    rows,
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
        title="Phiếu mượn"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Phiếu mượn' }, { label: 'Danh sách' }]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Danh sách phiếu mượn</h2>
            </div>

            <AdminImportExportBar
                :show-import="false"
                :show-update-file="false"
                add-label="Thêm mới"
                @add="goCreate"
                @export-excel="exportExcel"
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
                            class="admin-filter-select"
                            @change="loadLoans(true)"
                        >
                            <option value="">Trạng thái</option>
                            <option value="da_muon">Đang mượn</option>
                            <option value="da_tra">Đã trả</option>
                            <option value="qua_han">Quá hạn</option>
                        </select>
                        <select
                            v-model="filterValues.sort_due_date"
                            class="admin-filter-select"
                            @change="loadLoans(true)"
                        >
                            <option value="asc">Hạn trả tăng dần</option>
                            <option value="desc">Hạn trả giảm dần</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <LoansTable
                :rows="rows"
                :loading-fallback="loading"
                :empty-text="emptyText"
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
    </AdminLayout>
</template>
