<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import LibraryCardsTable from '@/Components/Admin/LibraryCards/LibraryCardsTable.vue';
import LibraryCardFormModal from '@/Components/Admin/LibraryCards/LibraryCardFormModal.vue';
import LibraryCardLockConfirmModal from '@/Components/Admin/LibraryCards/LibraryCardLockConfirmModal.vue';
import { useLibraryCardsAdminPage } from '@/composables/admin/useLibraryCardsAdminPage';

const props = defineProps({
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
});

const lc = useLibraryCardsAdminPage(props, { screen: 'manage' });

function goCounter() {
    router.visit(route('admin.library-cards.counter'));
}
</script>

<template>
    <Head title="Quản lý thẻ thư viện — Admin" />
    <AdminLayout
        title="Thẻ thư viện"
        :breadcrumbs="[
            { label: 'Thẻ thư viện' },
            { label: 'Quản lý thẻ thư viện' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Quản lý thẻ thư viện</h2>
                <button type="button" class="admin-filter-btn inline-flex items-center gap-1.5 min-h-[44px] !h-auto py-2.5 px-3" @click="lc.openTrashDrawer">
                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                    Thùng rác
                </button>
            </div>

            <AdminImportExportBar
                :has-selection="lc.hasSelection"
                :selected-count="lc.selectedIds.length"
                update-file-label="Cập nhật ảnh thẻ"
                :show-import="false"
                add-label="Cấp thẻ tại quầy"
                @add="goCounter"
                @export-excel="lc.exportExcel"
                @update-file="lc.openPhotoBulkModal"
                @delete-selected="lc.openDeleteMultiple"
                @deselect-all="lc.deselectAll"
            />

            <AdminFilterSearch
                v-model="lc.filterValues.searchKeyword"
                search-placeholder="Mã thẻ, mã định danh, họ tên, email, SĐT..."
                :show-filter-button="false"
                @search="() => lc.searchCards()"
            />

            <LibraryCardsTable
                :rows="lc.cards"
                :selected-ids="lc.selectedIds"
                :loading-fallback="lc.loadingFallback"
                :is-all-selected="lc.isAllSelected"
                :has-selection="lc.hasSelection"
                :show-workflow="false"
                :show-approve="false"
                @toggle-all="lc.toggleSelectAll"
                @toggle="lc.toggleSelect"
                @edit="lc.openEditModal"
                @delete="lc.openDeleteOne"
                @photo="lc.openPhotoModal"
                @lock="lc.openLockModal"
            />

            <div v-if="lc.meta.last_page > 1" class="flex items-center justify-center gap-2 flex-wrap">
                <button
                    type="button"
                    class="admin-filter-btn min-h-[44px] !h-auto py-2.5 px-4 disabled:opacity-50 disabled:pointer-events-none"
                    :disabled="lc.pageNum <= 1"
                    @click="lc.goPage(lc.pageNum - 1)"
                >
                    Trước
                </button>
                <span class="text-sm text-slate-600 dark:text-slate-300">
                    Trang {{ lc.meta.current_page }} / {{ lc.meta.last_page }}
                </span>
                <button
                    type="button"
                    class="admin-filter-btn min-h-[44px] !h-auto py-2.5 px-4 disabled:opacity-50 disabled:pointer-events-none"
                    :disabled="lc.pageNum >= lc.meta.last_page"
                    @click="lc.goPage(lc.pageNum + 1)"
                >
                    Sau
                </button>
            </div>
        </div>

        <LibraryCardFormModal
            :show="lc.showModal"
            :form="lc.form"
            :faculties="lc.facultiesList"
            :periods="lc.periodsList"
            :save-loading="lc.saveLoading"
            :field-errors="lc.formErrors"
            :clear-field-error="lc.clearFormFieldError"
            @close="lc.showModal = false"
            @save="lc.saveCard"
        />

        <AdminFileModal
            :show="lc.showPhotoModal"
            :title="lc.photoBulkMode ? 'Cập nhật ảnh thẻ (hàng loạt)' : 'Cập nhật ảnh thẻ'"
            :description="
                lc.photoBulkMode
                    ? 'Hiện chỉ hỗ trợ cập nhật từng thẻ. Chọn một thẻ rồi dùng biểu tượng máy ảnh trên dòng.'
                    : 'Kéo thả ảnh hoặc chọn file (jpg, png, webp…).'
            "
            accept=".jpg,.jpeg,.png,.gif,.webp"
            :max-size-mb="10"
            submit-label="Lưu"
            :loading="lc.photoUploadLoading"
            @close="lc.closePhotoModal"
            @submit="(file) => lc.uploadPhoto(file)"
        />

        <AdminDeleteConfirmModal
            :show="lc.showDeleteModal"
            title="Xác nhận xóa thẻ"
            item-label="thẻ"
            :item="lc.cardToDelete"
            :selected-count="lc.cardToDelete ? 0 : lc.selectedIds.length"
            @close="lc.showDeleteModal = false"
            @confirm="lc.confirmDelete"
        />

        <LibraryCardLockConfirmModal
            :show="lc.showLockModal"
            :is-lock-action="lc.isLockAction"
            :card-label="lc.cardToLock?.full_name ?? lc.cardToLock?.card_number ?? '—'"
            @close="lc.closeLockModal"
            @confirm="lc.confirmLockStatus"
        />

        <AdminTrashDrawer
            :show="lc.showTrashDrawer"
            title="Thùng rác — thẻ thư viện"
            item-label-key="full_name"
            :items="lc.trashedCards"
            :loading="lc.loadingTrash"
            @close="lc.showTrashDrawer = false"
            @restore="lc.restoreCard"
            @restore-many="lc.restoreManyCards"
            @force-delete="lc.forceDeleteCard"
            @force-delete-many="lc.forceDeleteManyCards"
        />
    </AdminLayout>
</template>
