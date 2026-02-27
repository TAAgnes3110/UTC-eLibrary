<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';

const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
    roles: { type: Array, default: () => [] },
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const showToggleConfirmModal = ref(false);
const userToToggle = ref(null);
const showTrashDrawer = ref(false);
const trashedUsers = ref([]);
const loadingTrash = ref(false);
const isEditing = ref(false);
const selectedUser = ref(null);

const form = useForm({
    id: null,
    name: '',
    email: '',
    phone: '',
    code: '',
    role: 'STUDENT',
    password: '',
    password_confirmation: '',
});

const sampleUsers = ref([
    { id: 1, name: 'Nguyễn Văn Admin', email: 'admin@utc.edu.vn', phone: '0901234567', code: 'AD001', role: 'ADMIN', status: 'active', created_at: '01/01/2024' },
    { id: 2, name: 'Trần Thị Thủ Thư', email: 'thuthu@utc.edu.vn', phone: '0912345678', code: 'LB001', role: 'LIBRARIAN', status: 'active', created_at: '15/01/2024' },
    { id: 3, name: 'Sinh Viên A', email: 'sva@student.utc.edu.vn', phone: '0923456789', code: '2024001', role: 'STUDENT', status: 'active', created_at: '01/09/2024' },
    { id: 4, name: 'Sinh Viên B', email: 'svb@student.utc.edu.vn', phone: '0934567890', code: '2024002', role: 'STUDENT', status: 'active', created_at: '01/09/2024' },
    { id: 5, name: 'Giảng Viên C', email: 'gvc@utc.edu.vn', phone: '0945678901', code: 'GV001', role: 'TEACHER', status: 'active', created_at: '01/02/2024' },
    { id: 6, name: 'Khách D', email: 'khachd@gmail.com', phone: '0956789012', code: 'KH001', role: 'GUEST', status: 'inactive', created_at: '01/12/2024' },
]);

const usersList = computed(() => props.users?.data?.length ? props.users.data : sampleUsers.value);

const searchQuery = ref('');
const roleFilter = ref('');

const filteredUsers = computed(() => {
    let result = usersList.value;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(user =>
            (user.name || '').toLowerCase().includes(q) ||
            (user.email || '').toLowerCase().includes(q) ||
            (user.code || '').toLowerCase().includes(q) ||
            (user.phone || '').toLowerCase().includes(q)
        );
    }
    if (roleFilter.value) {
        result = result.filter(user => user.role === roleFilter.value);
    }
    return result;
});

const roleLabels = {
    'SUPER_ADMIN': { label: 'Super Admin', class: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' },
    'ADMIN': { label: 'Admin', class: 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200' },
    'LIBRARIAN': { label: 'Thủ thư', class: 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300' },
    'TEACHER': { label: 'Giảng viên', class: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' },
    'STUDENT': { label: 'Sinh viên', class: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    'GUEST': { label: 'Khách', class: 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' },
};
const getRoleInfo = (role) => roleLabels[role] || { label: role, class: 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' };

const statusLabels = {
    active: { label: 'Hoạt động', class: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' },
    inactive: { label: 'Tạm khóa', class: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300' },
};
const getStatusInfo = (status) => statusLabels[status] || statusLabels.active;

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
    form.role = 'STUDENT';
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
            await window.axios.delete(route('admin.users.destroy', { id: selectedUser.value.id }));
        } else if (selectedIds.value.length > 0) {
            for (const id of selectedIds.value) {
                await window.axios.delete(route('admin.users.destroy', { id }));
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
        const { data } = await window.axios.get(route('admin.users.trash'));
        trashedUsers.value = data.data || [];
    } catch {
        trashedUsers.value = [];
    }
    loadingTrash.value = false;
};
const onRestoreUser = async (id) => {
    try {
        await window.axios.post(route('admin.users.restore', { id }));
        fetchTrash();
        router.reload();
    } catch (_) {}
};
const onForceDeleteUser = async (id) => {
    if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
    try {
        await window.axios.delete(route('admin.users.force', { id }));
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
        const { data } = await window.axios.post(route('admin.users.toggle-status', { id: user.id }));
        if (data?.is_active !== undefined) {
            user.is_active = data.is_active;
            user.status = data.is_active ? 'active' : 'inactive';
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
</script>

<template>
    <Head title="Quản lý Tài khoản - Admin" />
    <AdminLayout
        title="Quản lý người dùng"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Thư viện số' },
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
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @update-file="() => {}"
                @delete-selected="confirmBulkDelete"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="searchQuery"
                search-placeholder="Tìm theo tên, email, mã số, SĐT..."
                :show-filter-button="false"
                @search="() => {}"
            >
                <template #filters>
                    <select v-model="roleFilter" class="admin-filter-select">
                        <option value="">-- Phân quyền --</option>
                        <option v-for="(info, role) in roleLabels" :key="role" :value="role">{{ info.label }}</option>
                    </select>
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
                                        <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold text-sm shrink-0">
                                            {{ (user.name || '?').charAt(0).toUpperCase() }}
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
                <p v-if="filteredUsers.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Không có tài khoản nào.</p>
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
                                <option v-for="(info, role) in roleLabels" :key="role" :value="role">{{ info.label }}</option>
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
