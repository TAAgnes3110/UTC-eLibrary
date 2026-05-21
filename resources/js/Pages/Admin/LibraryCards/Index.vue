<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import LibraryCardsTable from '@/Components/Admin/LibraryCards/LibraryCardsTable.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
import LibraryCardFormModal from '@/Components/Admin/LibraryCards/LibraryCardFormModal.vue';
import LibraryCardLockConfirmModal from '@/Components/Admin/LibraryCards/LibraryCardLockConfirmModal.vue';
import { useLibraryCardsAdminPage } from '@/composables/admin/useLibraryCardsAdminPage';
import { LIBRARY_CARD_STATUS_FILTER_OPTIONS } from '@/config/libraryCardUi';

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
            <AdminPageHeading title="Quản lý thẻ thư viện">
                <template #actions>
                    <button type="button" class="admin-filter-btn inline-flex items-center gap-1.5 min-h-[44px] !h-auto py-2.5 px-3" @click="lc.openTrashDrawer">
                        <Icon :icon="ADMIN_ICONS.trash" class="w-4 h-4" />
                        Thùng rác
                    </button>
                </template>
            </AdminPageHeading>

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
                            <select v-model="lc.filterValues.holderType" class="admin-filter-select !h-10 !rounded-xl text-left w-[154px] max-w-full pr-8">
                                <option value="">Loại thẻ</option>
                                <option value="student">Thẻ sinh viên</option>
                                <option value="teacher">Thẻ giảng viên</option>
                                <option value="external">Thẻ bạn đọc ngoài</option>
                            </select>
                            <Icon
                                icon="lucide:chevron-down"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                            />
                        </div>
                        <div class="relative">
                            <select v-model="lc.filterValues.status" class="admin-filter-select !h-10 !rounded-xl text-left w-[154px] max-w-full pr-8">
                                <option
                                    v-for="opt in LIBRARY_CARD_STATUS_FILTER_OPTIONS"
                                    :key="opt.value === '' ? 'all' : opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <Icon
                                icon="lucide:chevron-down"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                            />
                        </div>
                        <div class="relative">
                            <select v-model="lc.filterValues.sortBy" class="admin-filter-select !h-10 !rounded-xl text-left w-[120px] max-w-full pr-8">
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

            <LibraryCardsTable
                :rows="lc.cards"
                :selected-ids="lc.selectedIds"
                :loading-fallback="lc.loadingFallback"
                :is-all-selected="lc.isAllSelected"
                :has-selection="lc.hasSelection"
                :show-workflow="true"
                :show-approve="false"
                @toggle-all="lc.toggleSelectAll"
                @toggle="lc.toggleSelect"
                @edit="lc.openEditModal"
                @delete="lc.openDeleteOne"
                @photo="lc.openPhotoModal"
                @lock="lc.openLockModal"
                @confirm-pickup="lc.onConfirmPickup"
            />

            <AdminPaginationBar
                :current-page="lc.pageNum"
                :last-page="lc.meta.last_page"
                :disabled="lc.loadingFallback"
                @go-page="lc.goPage"
            />
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
            :important-fields="[
                { key: 'card_number', label: 'Mã thẻ' },
                { key: 'code', label: 'Mã định danh' },
                { key: 'holder_type', label: 'Loại thẻ' },
                { key: 'status', label: 'Trạng thái' },
                { key: 'email', label: 'Email' },
            ]"
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
