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
import BooksTable from '@/Components/Admin/Books/BooksTable.vue';
import BookFormModal from '@/Components/Admin/Books/BookFormModal.vue';
import { useBooksAdminPage, SEARCH_IN_OPTIONS } from '@/composables/admin/useBooksAdminPage';

const {
    pageKind,
    pageLabel,
    books,
    warehouses,
    saveBookLoading,
    classifications,
    classificationDetails,
    filterValues,
    showFilterPanel,
    filteredBooks,
    showModal,
    isEditing,
    form,
    bookFormErrors,
    clearBookFieldError,
    selectedBook,
    showDeleteConfirm,
    deleteLoading,
    selectedIds,
    hasSelection,
    isAllSelected,
    trashedBooks,
    showTrashDrawer,
    showCoverModal,
    coverBulkMode,
    coverUploadLoading,
    showImportModal,
    importLoading,
    toggleSelectAll,
    toggleSelect,
    deselectAll,
    openAddModal,
    openEditModal,
    saveBook,
    openDeleteOne,
    openDeleteMultiple,
    confirmDelete,
    exportExcel,
    openImportModal,
    downloadBooksTemplate,
    importBooksExcel,
    restoreBook,
    restoreManyBooks,
    forceDeleteBook,
    forceDeleteManyBooks,
    openCoverModal,
    closeCoverModal,
    uploadCover,
} = useBooksAdminPage();
</script>

<template>
    <Head :title="`Danh mục – ${pageLabel}`" />
    <AdminLayout
        title="Danh mục tài liệu"
        :breadcrumbs="[
            { label: 'Danh mục tài liệu' },
            { label: pageLabel },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">{{ pageLabel }} theo danh mục</h2>
                <Button variant="outline" size="sm" class="gap-1.5" @click="showTrashDrawer = true">
                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                    Thùng rác
                </Button>
            </div>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.size"
                update-file-label="Cập nhật ảnh bìa"
                :show-update-file="true"
                :add-label="pageKind === 'digital' ? 'Thêm tài liệu số' : 'Thêm sách in'"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @update-file="() => openCoverModal()"
                @delete-selected="openDeleteMultiple"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Mã sách, tên sách, tác giả, NXB, nơi XB, năm XB..."
                :show-filter-button="false"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <AdminFilterPanel
                            :options="SEARCH_IN_OPTIONS"
                            v-model:model-value="filterValues.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="in_stock">Còn</option>
                            <option value="out_of_stock">Hết</option>
                        </select>
                        <select v-model="filterValues.priceSort" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Giá sách</option>
                            <option value="asc">Giá tăng dần</option>
                            <option value="desc">Giá giảm dần</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <BooksTable
                :books="filteredBooks"
                :selected-ids="selectedIds"
                :is-all-selected="isAllSelected"
                :has-selection="hasSelection"
                @toggle-select-all="toggleSelectAll"
                @toggle-select="toggleSelect"
                @edit="openEditModal"
                @delete="openDeleteOne"
                @cover="openCoverModal"
            />
        </div>

        <BookFormModal
            :show="showModal"
            :is-editing="isEditing"
            :form="form"
            :classifications="classifications"
            :classification-details="classificationDetails"
            :warehouses="warehouses"
            :save-loading="saveBookLoading"
            :field-errors="bookFormErrors"
            :clear-field-error="clearBookFieldError"
            @close="showModal = false"
            @save="saveBook"
        />

        <AdminFileModal
            :show="showCoverModal"
            :title="coverBulkMode ? 'Cập nhật ảnh bìa hàng loạt' : 'Cập nhật ảnh bìa sách'"
            :description="
                coverBulkMode
                    ? selectedIds.size > 0
                        ? `Đã chọn ${selectedIds.size} sách — chỉ cập nhật bìa cho các bản ghi đã chọn (tên file trong .zip = mã sách). Ảnh trong zip trùng mã nhưng không nằm trong lựa chọn sẽ bị bỏ qua.`
                        : 'File .zip: mỗi ảnh đặt tên đúng mã sách + đuôi (jpg, png...). Cập nhật mọi sách có mã khớp trong zip (không chọn dòng nào).'
                    : 'Kéo thả ảnh vào đây hoặc chọn file. Tên file không quan trọng, hệ thống tự đặt tên.'
            "
            :accept="coverBulkMode ? '.zip' : '.jpg,.jpeg,.png,.gif,.webp'"
            :max-size-mb="coverBulkMode ? 50 : 10"
            submit-label="Lưu"
            :loading="coverUploadLoading"
            @close="closeCoverModal"
            @submit="(file) => uploadCover(file)"
        />

        <AdminFileModal
            :show="showImportModal"
            title="Nhập sách in từ Excel"
            description="Tải file mẫu, điền danh sách sách in (một dòng một bản ghi), sau đó chọn file để nhập."
            accept=".xls,.xlsx,.csv"
            :max-size-mb="10"
            template-label="Tải file mẫu sách"
            submit-label="Nhập Excel"
            :loading="importLoading"
            @close="showImportModal = false"
            @submit="
                (file) => {
                    importBooksExcel(file);
                    showImportModal = false;
                }
            "
            @download-template="downloadBooksTemplate"
        />

        <AdminDeleteConfirmModal
            :show="showDeleteConfirm"
            title="Xác nhận xóa sách"
            item-label="sách"
            :item="selectedBook"
            :selected-count="selectedBook ? 0 : selectedIds.size"
            :loading="deleteLoading"
            @close="showDeleteConfirm = false"
            @confirm="confirmDelete"
        />

        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác"
            item-label-key="title"
            :items="trashedBooks"
            :loading="false"
            @close="showTrashDrawer = false"
            @restore="restoreBook"
            @restore-many="restoreManyBooks"
            @force-delete="forceDeleteBook"
            @force-delete-many="forceDeleteManyBooks"
        />
    </AdminLayout>
</template>
