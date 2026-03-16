<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import { getRoleInfo, getStatusInfo } from '@/config/enums';
import { usersApi } from '@/api/users';

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
});

const usersData = ref(null);
const rolesData = ref(null);
const loadingFallback = ref(false);

onMounted(() => {
    usersData.value = props.users;
    rolesData.value = props.roles;
});
const fetchUsers = async () => {
    loadingFallback.value = true;
    try {
        const payload = await usersApi.list();
        const data = payload?.data ?? payload;
        const items = Array.isArray(payload) ? payload : (payload?.data ?? []);
        const meta = payload?.meta ?? {};
        usersData.value = {
            data: items,
            current_page: meta?.current_page ?? 1,
            last_page: meta?.last_page ?? 1,
            per_page: meta?.per_page ?? 20,
            total: meta?.total ?? items.length,
            from: meta?.from ?? null,
            to: meta?.to ?? null,
        };
    } catch (e) {
        console.error('Lỗi khi tải lại danh sách tài khoản:', e);
    } finally {
        loadingFallback.value = false;
    }
};

const showModal = ref(false);
const showDeleteModal = ref(false);
const showToggleConfirmModal = ref(false);
const userToToggle = ref(null);
const showTrashDrawer = ref(false);
const trashedUsers = ref([]);
const loadingTrash = ref(false);
const showAvatarModal = ref(false);
const avatarTargetUserId = ref(null);
const avatarUploadLoading = ref(false);
const avatarBulkMode = ref(false);
const isEditing = ref(false);
const selectedUser = ref(null);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const formModalTitle = computed(() =>
    isEditing.value ? 'Chỉnh sửa tài khoản' : 'Thêm tài khoản',
);

const form = useForm({
    id: null,
    name: '',
    email: '',
    phone: '',
    code: '',
    role: 'MEMBER',
    password: '',
    password_confirmation: '',
});

const usersList = computed(() => (usersData.value ?? props.users)?.data ?? []);

const SEARCH_IN_OPTIONS = [
    { key: 'name', label: 'Họ tên' },
    { key: 'email', label: 'Email' },
    { key: 'code', label: 'Mã số' },
    { key: 'phone', label: 'Số điện thoại' },
];

const filterValues = ref({
    status: '',
    searchKeyword: '',
    searchIn: { name: true, email: true, code: true, phone: true },
    roleFilter: {},
});
const showFilterPanel = ref(false);

const roleOptions = computed(() => {
    const roles = rolesData.value ?? props.roles;
    return roles?.length ? roles : [];
});

const ROLE_FILTER_OPTIONS = computed(() => ({
    title: 'Phân quyền',
    options: (roleOptions.value || []).map((r) => ({
        key: r.id ?? r.value ?? r.role ?? '',
        label: r.text ?? r.label ?? r.name ?? r.id ?? r.value ?? '',
    })).filter((o) => o.key),
}));

const filteredUsers = computed(() => {
    let result = usersList.value;
    const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
    const sin = filterValues.value.searchIn || {};
    if (kw) {
        const anyChecked = Object.values(sin).some(Boolean);
        if (anyChecked) {
            result = result.filter((u) => {
                const m = [];
                if (sin.name) m.push((u.name || '').toLowerCase().includes(kw));
                if (sin.email) m.push((u.email || '').toLowerCase().includes(kw));
                if (sin.code) m.push((u.code || '').toLowerCase().includes(kw));
                if (sin.phone) m.push((u.phone || '').toLowerCase().includes(kw));
                return m.some(Boolean);
            });
        }
    }
    if (filterValues.value.status) result = result.filter(u => u.status === filterValues.value.status);
    const rf = filterValues.value.roleFilter || {};
    const checkedRoles = Object.entries(rf).filter(([, v]) => v).map(([k]) => k);
    if (checkedRoles.length) result = result.filter(u => checkedRoles.includes(u.role));
    return result;
});

const formatDateTime = (value) => {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString('vi-VN');
};

const selectedIds = ref([]);
const hasSelection = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed(() => filteredUsers.value.length > 0 && selectedIds.value.length === filteredUsers.value.length);

const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
};
const toggleAll = () => {
    if (isAllSelected.value) selectedIds.value = [];
    else selectedIds.value = filteredUsers.value.map(u => u.id);
};
const deselectAll = () => { selectedIds.value = []; };

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.role = 'MEMBER';
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    selectedUser.value = user;
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone || '';
    form.code = user.code;
    form.role = user.role;
    form.is_active = !!user.is_active;
    showModal.value = true;
};

const closeUserModal = () => {
    showModal.value = false;
    isEditing.value = false;
    selectedUser.value = null;
    form.clearErrors();
};

const confirmDelete = (user) => {
    selectedUser.value = user;
    showDeleteModal.value = true;
};

const confirmBulkDelete = () => {
    selectedUser.value = null;
    showDeleteModal.value = true;
};

const saveUser = async () => {
    try {
        form.clearErrors();
        const payload = {
            name: form.name,
            email: form.email,
            phone: form.phone || null,
            code: form.code,
            role: form.role,
            is_active: form.is_active,
            password: form.password,
            password_confirmation: form.password_confirmation,
        };
        if (isEditing.value && form.id) {
            await usersApi.update(form.id, payload);
        } else {
            await usersApi.create(payload);
        }
        await fetchUsers();

        showModal.value = false;
        form.reset();
    } catch (e) {
        const errors = e?.response?.data?.errors || e?.response?.data?.data?.errors || {};
        const flatErrors = {};
        if (errors && typeof errors === 'object') {
            Object.keys(errors).forEach((key) => {
                const val = errors[key];
                if (Array.isArray(val) && val.length > 0) {
                    flatErrors[key] = val[0];
                } else if (typeof val === 'string') {
                    flatErrors[key] = val;
                }
            });
        }
        if (Object.keys(flatErrors).length > 0) {
            form.errors = flatErrors;
        } else {
            console.error('Lỗi khi lưu tài khoản:', e);
        }
    }
};

const deleteUser = async () => {
    try {
        if (selectedUser.value) {
            await usersApi.remove(selectedUser.value.id);
        } else if (selectedIds.value.length > 0) {
            for (const id of selectedIds.value) {
                await usersApi.remove(id);
            }
        }
        await fetchUsers();
        if (showTrashDrawer.value) {
            await fetchTrash();
        }
    } catch (e) {
        console.error('Lỗi khi xóa tài khoản:', e);
    }
    selectedUser.value = null;
    selectedIds.value = [];
    showDeleteModal.value = false;
};

const openTrashDrawer = () => {
    showTrashDrawer.value = true;
    fetchTrash();
};

const fetchTrash = async () => {
    loadingTrash.value = true;
    try {
        const payload = await usersApi.trash();
        const data = payload?.data ?? payload;
        trashedUsers.value = Array.isArray(payload) ? payload : (payload?.data ?? []);
    } catch (e) {
        trashedUsers.value = [];
        console.error('Lỗi khi tải thùng rác tài khoản:', e);
    }
    loadingTrash.value = false;
};

const onRestoreUser = async (id) => {
    try {
        await usersApi.restore(id);
        if (typeof fetchUsers === 'function') {
            await fetchUsers();
        }
        await fetchTrash();
    } catch (e) {
        console.error('Lỗi khi khôi phục tài khoản:', e);
    }
};

const onForceDeleteUser = async (id) => {
    if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
    try {
        await usersApi.forceDelete(id);
        if (typeof fetchUsers === 'function') {
            await fetchUsers();
        }
        await fetchTrash();
    } catch (e) {
        console.error('Lỗi khi xóa vĩnh viễn tài khoản:', e);
    }
};

const openToggleConfirm = (user) => {
    userToToggle.value = user;
    showToggleConfirmModal.value = true;
};

const closeToggleConfirm = () => {
    showToggleConfirmModal.value = false;
    userToToggle.value = null;
};

const toggleStatus = async () => {
    const user = userToToggle.value;
    if (!user) return;
    try {
        const res = await usersApi.toggleStatus(user.id);
        if (res?.is_active !== undefined) {
            user.is_active = res.is_active;
            user.status = res.is_active ? 'active' : 'blocked';
        }
        closeToggleConfirm();
    } catch (e) {
        console.error('Lỗi khi khóa/mở khóa tài khoản:', e);
        closeToggleConfirm();
    }
};

const isLockAction = computed(() => userToToggle.value?.status === 'active');

const exportExcel = async () => {
    try {
        const params = {};
        if (selectedIds.value.length > 0) {
            params.ids = selectedIds.value;
        } else if (filterValues.value.searchKeyword
            || filterValues.value.status
            || Object.values(filterValues.value.searchIn || {}).some(Boolean !== undefined)
            || Object.values(filterValues.value.roleFilter || {}).some(Boolean)
        ) {
            params.ids = filteredUsers.value.map((u) => u.id);
        }
        const response = await usersApi.export(params);
        const blob = new Blob([response.data], {
            type: response.headers['content-type']
                || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'danh_sach_tai_khoan.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        console.error('Lỗi khi xuất Excel:', e);
    }
};

const openImportModal = () => {
    // TODO: sẽ triển khai import excel sau
};

const closeAvatarModal = () => {
    showAvatarModal.value = false;
    avatarTargetUserId.value = null;
    avatarBulkMode.value = false;
};

const openAvatarModal = (user = null) => {
    if (user) {
        avatarBulkMode.value = false;
        avatarTargetUserId.value = user.id;
    } else {
        avatarBulkMode.value = selectedIds.value.length !== 1;
        avatarTargetUserId.value = selectedIds.value.length === 1 ? selectedIds.value[0] : null;
    }
    showAvatarModal.value = true;
};

const uploadAvatar = async (file) => {
    if (!file) return;
    avatarUploadLoading.value = true;
    try {
        const formData = new FormData();
        if (avatarBulkMode.value) {
            formData.append('file', file);
            await usersApi.bulkUpdateAvatar(formData);
        } else {
            const userId = avatarTargetUserId.value ?? selectedIds.value[0];
            if (!userId) {
                alert('Vui lòng chọn đúng 1 người để cập nhật ảnh đại diện.');
                avatarUploadLoading.value = false;
                return;
            }
            formData.append('avatar', file);
            await usersApi.updateAvatar(userId, formData);
        }
        await fetchUsers();
        alert('Cập nhật ảnh đại diện thành công.');
        closeAvatarModal();
    } catch (err) {
        console.error('Lỗi khi cập nhật ảnh đại diện:', err);
        const res = err?.response?.data || {};
        const message = res.message || res.error || 'Cập nhật ảnh đại diện không thành công. Vui lòng kiểm tra lại file.';
        alert(message);
    } finally {
        avatarUploadLoading.value = false;
    }
};
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
                            :options="SEARCH_IN_OPTIONS"
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

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th class="p-4 w-12">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        :indeterminate="hasSelection && !isAllSelected"
                                        @change="toggleAll"
                                        class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                    />
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ảnh đại diện</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Họ tên</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Email</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Số điện thoại</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Phân quyền</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ngày cập nhật</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="user in filteredUsers"
                                :key="user.id"
                                :class="[selectedIds.includes(user.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                            >
                                <td class="p-4">
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.includes(user.id)"
                                        @change="toggleSelect(user.id)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <td class="p-4">
                                    <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">
                                        {{ user.code }}
                                    </p>
                                </td>
                                <td class="p-4">
                                    <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold text-sm shrink-0 overflow-hidden relative group/avatar">
                                        <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="h-full w-full object-cover" />
                                        <span v-else>{{ (user.name || '?').charAt(0).toUpperCase() }}</span>
                                        <button
                                            type="button"
                                            class="absolute inset-0 bg-black/40 opacity-0 group-hover/avatar:opacity-100 transition-opacity flex items-center justify-center rounded-lg cursor-pointer"
                                            @click.stop="openAvatarModal(user)"
                                            title="Cập nhật ảnh đại diện"
                                        >
                                            <Icon icon="lucide:camera" class="w-4 h-4 text-white" />
                                        </button>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <p class="font-semibold text-sm text-slate-900 dark:text-white truncate">
                                        {{ user.name }}
                                    </p>
                                </td>
                                <td class="p-4">
                                    <p class="text-[12px] text-slate-600 dark:text-slate-300 truncate">
                                        {{ user.email }}
                                    </p>
                                </td>
                                <td class="p-4">
                                    <p class="text-[12px] text-slate-600 dark:text-slate-300">
                                        {{ user.phone || '—' }}
                                    </p>
                                </td>
                                <td class="p-4">
                                    <span :class="[getRoleInfo(user.role).class, 'px-2 py-0.5 rounded text-[11px] font-semibold']">
                                        {{ getRoleInfo(user.role).label }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <p class="text-[12px] text-slate-600 dark:text-slate-300">
                                        {{ formatDateTime(user.updated_at || user.created_at) }}
                                    </p>
                                </td>
                                <td class="p-4">
                                    <span :class="[getStatusInfo(user.status).class, 'px-2 py-0.5 rounded text-[11px] font-semibold']">
                                        {{ getStatusInfo(user.status).label }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-0.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(user)"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                            title="Chỉnh sửa"
                                        >
                                            <Icon icon="lucide:pencil" class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openToggleConfirm(user)"
                                            :class="user.status === 'active' ? 'text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20' : 'text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'"
                                            class="p-1.5 rounded-lg transition-colors"
                                            :title="user.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa'"
                                        >
                                            <Icon :icon="user.status === 'active' ? 'lucide:user-x' : 'lucide:user-check'" class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(user)"
                                            class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                            title="Xóa"
                                        >
                                            <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="loadingFallback" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Đang tải...</p>
                <p v-else-if="filteredUsers.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Không có tài khoản nào.</p>
            </div>
        </div>

        <!-- Modal tạo / chỉnh sửa tài khoản -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="closeUserModal" />
                <div
                    class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800"
                >
                    <div
                        class="sticky top-0 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 z-10"
                    >
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ formModalTitle }}
                        </h3>
                        <button
                            type="button"
                            @click="closeUserModal"
                            class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        >
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Họ và tên <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="form.name"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Nhập họ và tên"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Email <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="form.email"
                                type="email"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="email@utc.edu.vn"
                            />
                            <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Số điện thoại
                            </label>
                            <Input
                                v-model="form.phone"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="09xxxxx..."
                            />
                            <p v-if="form.errors.phone" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.phone }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Mã định danh <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="form.code"
                                class="h-10 rounded-lg font-mono border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="MSV, CCCD..."
                            />
                            <p v-if="form.errors.code" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.code }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Phân quyền
                            </label>
                            <select
                                v-model="form.role"
                                class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            >
                                <option
                                    v-for="r in roleOptions"
                                    :key="r.id ?? r.value ?? r.role"
                                    :value="r.id ?? r.value ?? r.role"
                                >
                                    {{ r.text ?? r.label ?? r.name ?? r.id ?? r.value }}
                                </option>
                            </select>
                        </div>

                        <template v-if="!isEditing">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Mật khẩu <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <Input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="h-10 pr-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                        placeholder="••••••••"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                        @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                                    >
                                        <Icon :icon="showPassword ? 'lucide:eye-off' : 'lucide:eye'" class="w-4 h-4" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="text-xs text-rose-500 mt-1">
                                    {{ form.errors.password }}
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Xác nhận mật khẩu <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <Input
                                        v-model="form.password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        class="h-10 pr-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                        placeholder="••••••••"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                                        :aria-label="showPasswordConfirmation ? 'Ẩn xác nhận mật khẩu' : 'Hiện xác nhận mật khẩu'"
                                    >
                                        <Icon
                                            :icon="showPasswordConfirmation ? 'lucide:eye-off' : 'lucide:eye'"
                                            class="w-4 h-4"
                                        />
                                    </button>
                                </div>
                                <p v-if="form.errors.password_confirmation" class="text-xs text-rose-500 mt-1">
                                    {{ form.errors.password_confirmation }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <div
                        class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30"
                    >
                        <Button variant="outline" @click="closeUserModal">
                            Hủy bỏ
                        </Button>
                        <Button
                            :disabled="form.processing"
                            @click="saveUser"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            {{ isEditing ? 'Cập nhật' : 'Lưu' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal cập nhật ảnh đại diện -->
        <AdminFileModal
            :show="showAvatarModal"
            :title="avatarBulkMode ? 'Cập nhật ảnh đại diện hàng loạt' : 'Cập nhật ảnh đại diện'"
            :description="
                avatarBulkMode
                    ? 'Chọn một file .zip chứa các ảnh. Mỗi ảnh đặt tên đúng mã người dùng + đuôi ảnh (jpg, png, ...).'
                    : 'Kéo thả ảnh vào đây hoặc chọn file. Tên file không quan trọng, hệ thống tự đặt tên.'
            "
            :accept="avatarBulkMode ? '.zip' : '.jpg,.jpeg,.png,.gif,.webp'"
            :max-size-mb="avatarBulkMode ? 50 : 10"
            submit-label="Lưu"
            :loading="avatarUploadLoading"
            @close="closeAvatarModal"
            @submit="(file) => uploadAvatar(file)"
        />

        <!-- Modal xác nhận khóa / mở khóa tài khoản -->
        <Teleport to="body">
            <div v-if="showToggleConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="closeToggleConfirm">
                <div class="absolute inset-0 bg-slate-900/70" @click="closeToggleConfirm" />
                <div class="relative w-full max-w-md shadow-xl overflow-hidden rounded-xl border-t-4 border-t-amber-500" style="background-color: #20222D;">
                    <div class="flex justify-end pt-3 pr-3">
                        <button type="button" @click="closeToggleConfirm" class="p-1.5 text-slate-400 hover:text-white rounded transition-colors">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="px-6 pb-2 flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center mb-3" style="background-color: rgba(30,31,42,1);">
                            <Icon :icon="isLockAction ? 'lucide:lock' : 'lucide:lock-open'" class="w-7 h-7 text-amber-500" />
                        </div>
                        <h3 class="text-lg font-bold text-white">
                            {{ isLockAction ? 'Xác nhận khóa tài khoản?' : 'Xác nhận mở khóa tài khoản?' }}
                        </h3>
                    </div>
                    <div class="px-6 pb-5 text-center">
                        <p class="text-sm text-slate-300">
                            {{ isLockAction ? 'Bạn đang thực hiện khóa tài khoản:' : 'Bạn đang thực hiện mở khóa tài khoản:' }}
                        </p>
                        <p class="mt-2 text-sm font-medium text-white">"{{ userToToggle?.name ?? userToToggle?.email ?? '—' }}"</p>
                    </div>
                    <div class="px-6 py-4 flex justify-center gap-3 border-t border-slate-700/80" style="background-color: #20222D;">
                        <Button type="button" variant="outline" class="bg-slate-700/50 border-slate-600 text-white hover:bg-slate-600 hover:text-white" @click="closeToggleConfirm">
                            Quay lại
                        </Button>
                        <Button type="button" :class="isLockAction ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-500 hover:bg-emerald-600'" class="text-white" @click="toggleStatus">
                            {{ isLockAction ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

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
            title="Thùng rác – Tài khoản"
            item-label-key="name"
            :items="trashedUsers"
            :loading="loadingTrash"
            @close="showTrashDrawer = false"
            @restore="onRestoreUser"
            @force-delete="onForceDeleteUser"
        />
    </AdminLayout>
</template>
