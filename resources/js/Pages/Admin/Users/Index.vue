<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import UsersTable from '@/Components/Admin/Users/UsersTable.vue';
import UserFormModal from '@/Components/Admin/Users/UserFormModal.vue';
import UserToggleConfirmModal from '@/Components/Admin/Users/UserToggleConfirmModal.vue';
import { useUsersAdminPage, USERS_SEARCH_IN_OPTIONS } from '@/composables/admin/useUsersAdminPage';

const props = defineProps({
    users: {
        type: Object,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0,
        }),
    },
    roles: {
        type: Array,
        default: () => [],
    },
    faculties: {
        type: Array,
        default: () => [],
    },
    periods: {
        type: Array,
        default: () => [],
    },
});

const {
    loadingFallback,
    showModal,
    showDeleteModal,
    showToggleConfirmModal,
    userToToggle,
    showTrashDrawer,
    trashedUsers,
    loadingTrash,
    showAvatarModal,
    avatarUploadLoading,
    avatarBulkMode,
    isEditing,
    selectedUser,
    saveUserLoading,
    userFormErrors,
    clearUserFieldError,
    form,
    filterValues,
    showFilterPanel,
    filteredUsers,
    roleOptions,
    facultiesOptions,
    periodsOptions,
    ROLE_FILTER_OPTIONS,
    formatDateTime,
    selectedIds,
    hasSelection,
    isAllSelected,
    toggleSelect,
    toggleAll,
    deselectAll,
    openAddModal,
    openEditModal,
    closeUserModal,
    confirmDelete,
    confirmBulkDelete,
    saveUser,
    deleteUser,
    openTrashDrawer,
    onRestoreUser,
    onRestoreManyUsers,
    onForceDeleteUser,
    onForceDeleteManyUsers,
    openToggleConfirm,
    closeToggleConfirm,
    toggleStatus,
    isLockAction,
    exportExcel,
    openImportModal,
    closeAvatarModal,
    openAvatarModal,
    uploadAvatar,
} = useUsersAdminPage(props);
</script>

<template>
    <Head title="Quản lý Tài khoản - Admin" />
    <AdminLayout
        title="Quản lý người dùng"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý người dùng' },
            { label: 'Tài khoản' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Danh sách tài khoản</h2>
                <Button variant="outline" size="sm" class="gap-1.5" @click="openTrashDrawer">
                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                    Thùng rác
                </Button>
            </div>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                update-file-label="Cập nhật ảnh đại diện"
                :show-import="false"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @update-file="openAvatarModal"
                @delete-selected="confirmBulkDelete"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Nhập từ khóa để tìm..."
                :show-filter-button="false"
                @search="() => {}"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <AdminFilterPanel
                            :options="USERS_SEARCH_IN_OPTIONS"
                            v-model:model-value="filterValues.searchIn"
                            :filter-group="ROLE_FILTER_OPTIONS"
                            v-model:filter-value="filterValues.roleFilter"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="active">Hoạt động</option>
                            <option value="blocked">Khóa</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <UsersTable
                :rows="filteredUsers"
                :selected-ids="selectedIds"
                :loading-fallback="loadingFallback"
                :is-all-selected="isAllSelected"
                :has-selection="hasSelection"
                :format-date-time="formatDateTime"
                @toggle-all="toggleAll"
                @toggle="toggleSelect"
                @edit="openEditModal"
                @toggle-status="openToggleConfirm"
                @delete="confirmDelete"
                @avatar="openAvatarModal"
            />
        </div>

        <UserFormModal
            :show="showModal"
            :is-editing="isEditing"
            :form="form"
            :role-options="roleOptions"
            :faculties="facultiesOptions"
            :periods="periodsOptions"
            :field-errors="userFormErrors"
            :clear-field-error="clearUserFieldError"
            :save-loading="saveUserLoading"
            @close="closeUserModal"
            @save="saveUser"
        />

        <AdminFileModal
            :show="showAvatarModal"
            :title="avatarBulkMode ? 'Cập nhật ảnh đại diện hàng loạt' : 'Cập nhật ảnh đại diện'"
            :description="
                avatarBulkMode
                    ? selectedIds.length > 0
                        ? `Đã chọn ${selectedIds.length} tài khoản — chỉ cập nhật ảnh cho các tài khoản đã chọn (ảnh trong .zip phải trùng mã, ví dụ UTCLIB001.jpg). Ảnh trùng mã nhưng không nằm trong lựa chọn sẽ bị bỏ qua.`
                        : 'File .zip: mỗi ảnh đặt tên đúng mã người dùng + đuôi (jpg, png...). Cập nhật mọi tài khoản có mã khớp trong zip (không chọn dòng nào).'
                    : 'Kéo thả ảnh vào đây hoặc chọn file. Tên file không quan trọng, hệ thống tự đặt tên.'
            "
            :accept="avatarBulkMode ? '.zip' : '.jpg,.jpeg,.png,.gif,.webp'"
            :max-size-mb="avatarBulkMode ? 50 : 10"
            submit-label="Lưu"
            :loading="avatarUploadLoading"
            @close="closeAvatarModal"
            @submit="(file) => uploadAvatar(file)"
        />

        <UserToggleConfirmModal
            :show="showToggleConfirmModal"
            :is-lock-action="isLockAction"
            :user-name="userToToggle?.name ?? userToToggle?.email ?? '—'"
            @close="closeToggleConfirm"
            @confirm="toggleStatus"
        />

        <AdminDeleteConfirmModal
            :show="showDeleteModal"
            title="Xác nhận xóa tài khoản"
            item-label="tài khoản"
            :item="selectedUser"
            :selected-count="selectedIds.length"
            @close="showDeleteModal = false"
            @confirm="deleteUser"
        />
        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác"
            item-label-key="name"
            :items="trashedUsers"
            :loading="loadingTrash"
            @close="showTrashDrawer = false"
            @restore="onRestoreUser"
            @restore-many="onRestoreManyUsers"
            @force-delete="onForceDeleteUser"
            @force-delete-many="onForceDeleteManyUsers"
        />
    </AdminLayout>
</template>
