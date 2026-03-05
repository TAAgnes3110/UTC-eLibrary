<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import { getRoleInfo, getStatusInfo } from '@/config/enums';

const props = defineProps({
    users: { type: Object, default: () => ({ data: [], current_page: 1, last_page: 1, per_page: 20, total: 0, from: 0, to: 0 }) },
    roles: { type: Array, default: () => [] },
});

const usersData = ref(null);
const rolesData = ref(null);
const loadingFallback = ref(false);

onMounted(async () => {
    const fromProps = props.users?.data ?? [];
    if (fromProps.length > 0) {
        usersData.value = props.users;
        rolesData.value = props.roles;
        return;
    }
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('token') : null;
    if (!token) return;
    loadingFallback.value = true;
    try {
        const [usersRes, masterRes] = await Promise.all([
            window.axios.get('/users'),
            window.axios.get('/master-data'),
        ]);
        const payload = usersRes?.data?.data ?? usersRes?.data;
        const items = Array.isArray(payload) ? payload : (payload?.data ?? []);
        const meta = payload?.meta ?? {};
        usersData.value = {
            data: items,
            current_page: meta?.current_page ?? 1,
            last_page: meta?.last_page ?? 1,
            per_page: meta?.per_page ?? 20,
            total: meta?.total ?? 0,
            from: meta?.from ?? null,
            to: meta?.to ?? null,
        };
        const md = masterRes?.data?.data ?? masterRes?.data ?? {};
        rolesData.value = md?.role_types ?? props.roles ?? [];
    } catch {
        usersData.value = props.users;
        rolesData.value = props.roles;
    } finally {
        loadingFallback.value = false;
    }
});

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
const isEditing = ref(false);
const selectedUser = ref(null);

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

// Selection (giống Quản lý sách)
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
    form.role = 'MEMBER';
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    selectedUser.value = user;
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone || '';
    form.code = user.code;
    form.role = user.role;
    showModal.value = true;
};

const confirmDelete = (user) => {
    selectedUser.value = user;
    showDeleteModal.value = true;
};

const confirmBulkDelete = () => {
    selectedUser.value = null;
    showDeleteModal.value = true;
};

const saveUser = () => {
    showModal.value = false;
    form.reset();
};

const deleteUser = async () => {
    try {
        if (selectedUser.value) {
            await window.axios.delete(`/users/${selectedUser.value.id}`);
        } else if (selectedIds.value.length > 0) {
            for (const id of selectedIds.value) {
                await window.axios.delete(`/users/${id}`);
            }
        }
        router.reload();
    } catch (_) {
        router.reload();
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
        const { data } = await window.axios.get('/users/trash');
        const payload = data?.data ?? data;
        trashedUsers.value = Array.isArray(payload) ? payload : (payload?.data ?? []);
    } catch {
        trashedUsers.value = [];
    }
    loadingTrash.value = false;
};
const onRestoreUser = async (id) => {
    try {
        await window.axios.post(`/users/restore/${id}`);
        fetchTrash();
        router.reload();
    } catch (_) {}
};
const onForceDeleteUser = async (id) => {
    if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
    try {
        await window.axios.delete(`/users/force/${id}`);
        fetchTrash();
        router.reload();
    } catch (_) {}
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
        const { data } = await window.axios.post(`/users/${user.id}/toggle-status`);
        const res = data?.data ?? data;
        if (res?.is_active !== undefined) {
            user.is_active = res.is_active;
            user.status = res.is_active ? 'active' : 'inactive';
        }
        closeToggleConfirm();
        router.reload();
    } catch (_) {
        closeToggleConfirm();
    }
};
const isLockAction = computed(() => userToToggle.value?.status === 'active');

const exportExcel = () => { window.location.href = route('admin.users.export') || '#'; };
const openImportModal = () => { /* TODO: modal nhập excel */ };

const closeAvatarModal = () => {
    showAvatarModal.value = false;
    avatarTargetUserId.value = null;
};
const openAvatarModal = (user = null) => {
    const userId = user ? user.id : selectedIds.value[0];
    if (!userId) {
        alert('Vui lòng chọn đúng 1 người để cập nhật ảnh đại diện.');
        return;
    }
    if (!user && selectedIds.value.length !== 1) {
        alert('Vui lòng chọn đúng 1 người để cập nhật ảnh đại diện.');
        return;
    }
    avatarTargetUserId.value = userId;
    showAvatarModal.value = true;
};
const uploadAvatar = async (file) => {
    const userId = avatarTargetUserId.value ?? selectedIds.value[0];
    if (!userId) return;
    avatarUploadLoading.value = true;
    try {
        const formData = new FormData();
        formData.append('avatar', file);
        await window.axios.post(`/users/${userId}/avatar`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json',
            },
        });
        closeAvatarModal();
        router.reload();
    } catch (err) {
        const msg = err.response?.data?.message || err.message || 'Cập nhật ảnh thất bại.';
        alert(msg);
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
                            <option value="inactive">Tạm khóa</option>
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
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Người dùng</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Định danh</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Phân quyền</th>
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
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold text-sm shrink-0 overflow-hidden relative group/avatar">
                                            <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="h-full w-full object-cover" />
                                            <span v-else>{{ (user.name || '?').charAt(0).toUpperCase() }}</span>
                                            <button type="button" class="absolute inset-0 bg-black/40 opacity-0 group-hover/avatar:opacity-100 transition-opacity flex items-center justify-center rounded-lg cursor-pointer" @click.stop="openAvatarModal(user)" title="Cập nhật ảnh đại diện">
                                                <Icon icon="lucide:camera" class="w-4 h-4 text-white" />
                                            </button>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-sm text-slate-900 dark:text-white truncate">{{ user.name }}</p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">{{ user.code }}</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ user.phone || '—' }}</p>
                                </td>
                                <td class="p-4">
                                    <span :class="[getRoleInfo(user.role).class, 'px-2 py-0.5 rounded text-[11px] font-semibold']">
                                        {{ getRoleInfo(user.role).label }}
                                    </span>
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

        <!-- Modal Thêm / Sửa tài khoản (cùng kiểu Quản lý sách, Quản lý thẻ) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800">
                    <div class="sticky top-0 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 z-10">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ isEditing ? 'Chỉnh sửa tài khoản' : 'Thêm tài khoản' }}</h3>
                        <button type="button" @click="showModal = false" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Họ và tên <span class="text-rose-500">*</span></label>
                            <Input v-model="form.name" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="Nhập họ và tên" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Email <span class="text-rose-500">*</span></label>
                            <Input v-model="form.email" type="email" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="email@utc.edu.vn" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số điện thoại</label>
                            <Input v-model="form.phone" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="09xxxxx..." />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã định danh <span class="text-rose-500">*</span></label>
                            <Input v-model="form.code" class="h-10 rounded-lg font-mono border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="MSV, CCCD..." />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Phân quyền</label>
                            <select v-model="form.role" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm">
                                <option v-for="r in roleOptions" :key="r.id" :value="r.id">{{ r.text }}</option>
                            </select>
                        </div>
                        <template v-if="!isEditing">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mật khẩu</label>
                                <Input v-model="form.password" type="password" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="••••••••" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Xác nhận mật khẩu</label>
                                <Input v-model="form.password_confirmation" type="password" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="••••••••" />
                            </div>
                        </template>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                        <Button variant="outline" @click="showModal = false">Hủy bỏ</Button>
                        <Button @click="saveUser" class="bg-blue-600 hover:bg-blue-700 text-white">{{ isEditing ? 'Cập nhật' : 'Lưu' }}</Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <AdminFileModal
            :show="showAvatarModal"
            title="Cập nhật ảnh đại diện"
            description="Kéo thả ảnh vào đây hoặc chọn file. Tên file không quan trọng, hệ thống tự đặt tên."
            accept=".jpg,.jpeg,.png,.gif,.webp"
            :max-size-mb="10"
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
