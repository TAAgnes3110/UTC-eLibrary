<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import WarehousesTable from '@/Components/Admin/Warehouses/WarehousesTable.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
import WarehouseFormModal from '@/Components/Admin/Warehouses/WarehouseFormModal.vue';
import { useWarehousesAdminPage, WAREHOUSES_SEARCH_IN_OPTIONS } from '@/composables/admin/useWarehousesAdminPage';

const {
    warehousesPagination,
    goWarehousesPage,
    searchWarehouses,
    loading,
    showModal,
    showDeleteModal,
    showImportModal,
    importLoading,
    isEditing,
    selectedWarehouse,
    form,
    warehouseFormErrors,
    clearWarehouseFieldError,
    showTrashDrawer,
    trashedWarehouses,
    loadingTrash,
    filterValues,
    showFilterPanel,
    filteredWarehouses,
    formatDateTime,
    selectedIds,
    hasSelection,
    isAllSelected,
    toggleSelect,
    toggleAll,
    deselectAll,
    openImportModal,
    exportExcel,
    downloadTemplate,
    importExcel,
    openAddModal,
    openEditModal,
    confirmDelete,
    confirmBulkDelete,
    saveWarehouse,
    deleteWarehouse,
    openTrashDrawer,
    onRestoreWarehouse,
    onRestoreManyWarehouses,
    onForceDeleteWarehouse,
    onForceDeleteManyWarehouses,
    statusLabel,
    statusClass,
} = useWarehousesAdminPage();
</script>

<template>
    <Head title="Quản lý Kho sách - Admin" />
    <AdminLayout
        title="Quản lý kho sách"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý kho sách' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading title="Danh sách kho sách">
                <template #actions>
                    <Button variant="outline" size="sm" class="gap-1.5" @click="openTrashDrawer">
                        <Icon :icon="ADMIN_ICONS.trash" class="w-4 h-4" />
                        Thùng rác
                    </Button>
                </template>
            </AdminPageHeading>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                add-label="Thêm kho"
                :show-export="true"
                :show-import="true"
                :show-update-file="false"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @delete-selected="confirmBulkDelete"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Nhập từ khóa để tìm..."
                :show-filter-button="false"
                @search="searchWarehouses"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <AdminFilterPanel
                            :options="WAREHOUSES_SEARCH_IN_OPTIONS"
                            v-model:model-value="filterValues.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <WarehousesTable
                :rows="filteredWarehouses"
                :selected-ids="selectedIds"
                :loading="loading"
                :is-all-selected="isAllSelected"
                :has-selection="hasSelection"
                :format-date-time="formatDateTime"
                :status-label="statusLabel"
                :status-class="statusClass"
                @toggle-all="toggleAll"
                @toggle="toggleSelect"
                @edit="openEditModal"
                @delete="confirmDelete"
            />

            <AdminPaginationBar
                always-show
                :current-page="warehousesPagination.current_page"
                :last-page="warehousesPagination.last_page"
                :disabled="loading"
                @go-page="goWarehousesPage"
            />
        </div>

        <WarehouseFormModal
            :show="showModal"
            :is-editing="isEditing"
            :form="form"
            :field-errors="warehouseFormErrors"
            :clear-field-error="clearWarehouseFieldError"
            @close="showModal = false"
            @save="saveWarehouse"
        />

        <AdminDeleteConfirmModal
            :show="showDeleteModal"
            title="Xác nhận xóa kho sách"
            item-label="kho sách"
            :item="selectedWarehouse"
            :selected-count="selectedWarehouse ? 0 : selectedIds.length"
            @close="showDeleteModal = false"
            @confirm="deleteWarehouse"
        />

        <AdminFileModal
            :show="showImportModal"
            title="Nhập kho từ Excel"
            description="Tải file mẫu, điền danh sách kho, sau đó chọn file để nhập."
            accept=".xls,.xlsx,.csv"
            :max-size-mb="10"
            template-label="Tải file mẫu kho"
            submit-label="Nhập Excel"
            :loading="importLoading"
            @close="showImportModal = false"
            @submit="
                (file) => {
                    importExcel(file);
                    showImportModal = false;
                }
            "
            @download-template="downloadTemplate"
        />

        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác"
            item-label-key="name"
            :items="trashedWarehouses"
            :loading="loadingTrash"
            @close="showTrashDrawer = false"
            @restore="onRestoreWarehouse"
            @restore-many="onRestoreManyWarehouses"
            @force-delete="onForceDeleteWarehouse"
            @force-delete-many="onForceDeleteManyWarehouses"
        />
    </AdminLayout>
</template>
