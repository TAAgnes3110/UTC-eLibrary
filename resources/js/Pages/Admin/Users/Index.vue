<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
    roles: { type: Array, default: () => [] },
});

// Modal state
const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const selectedUser = ref(null);

// Form
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

// Sample data for demo
const sampleUsers = ref([
    { id: 1, name: 'Nguyễn Văn Admin', email: 'admin@utc.edu.vn', phone: '0901234567', code: 'AD001', role: 'ADMIN', status: 'active', created_at: '01/01/2024' },
    { id: 2, name: 'Trần Thị Thủ Thư', email: 'thuthu@utc.edu.vn', phone: '0912345678', code: 'LB001', role: 'LIBRARIAN', status: 'active', created_at: '15/01/2024' },
    { id: 3, name: 'Sinh Viên A', email: 'sva@student.utc.edu.vn', phone: '0923456789', code: '2024001', role: 'STUDENT', status: 'active', created_at: '01/09/2024' },
    { id: 4, name: 'Sinh Viên B', email: 'svb@student.utc.edu.vn', phone: '0934567890', code: '2024002', role: 'STUDENT', status: 'active', created_at: '01/09/2024' },
    { id: 5, name: 'Giảng Viên C', email: 'gvc@utc.edu.vn', phone: '0945678901', code: 'GV001', role: 'TEACHER', status: 'active', created_at: '01/02/2024' },
    { id: 6, name: 'Khách D', email: 'khachd@gmail.com', phone: '0956789012', code: 'KH001', role: 'GUEST', status: 'inactive', created_at: '01/12/2024' },
]);

const users = computed(() => props.users?.data?.length ? props.users.data : sampleUsers.value);

// Search & Filter
const searchQuery = ref('');
const roleFilter = ref('all');

const filteredUsers = computed(() => {
    let result = users.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            user.code.toLowerCase().includes(query)
        );
    }

    if (roleFilter.value !== 'all') {
        result = result.filter(user => user.role === roleFilter.value);
    }

    return result;
});

// Role mappings
const roleLabels = {
    'SUPER_ADMIN': { label: 'Super Admin', class: 'bg-purple-100 text-purple-700' },
    'ADMIN': { label: 'Admin', class: 'bg-slate-900 text-white' },
    'LIBRARIAN': { label: 'Thủ thư', class: 'bg-sky-100 text-sky-700' },
    'TEACHER': { label: 'Giảng viên', class: 'bg-indigo-100 text-indigo-700' },
    'STUDENT': { label: 'Sinh viên', class: 'bg-blue-100 text-blue-700' },
    'GUEST': { label: 'Khách', class: 'bg-slate-100 text-slate-700' },
};

const getRoleInfo = (role) => roleLabels[role] || { label: role, class: 'bg-slate-100 text-slate-700' };

const statusLabels = {
    'active': { label: 'Hoạt động', class: 'bg-emerald-100 text-emerald-700' },
    'inactive': { label: 'Tạm khóa', class: 'bg-rose-100 text-rose-700' },
};

const getStatusInfo = (status) => statusLabels[status] || statusLabels.active;

// Actions
const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    selectedUser.value = user;
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone;
    form.code = user.code;
    form.role = user.role;
    showModal.value = true;
};

const confirmDelete = (user) => {
    selectedUser.value = user;
    showDeleteModal.value = true;
};

const saveUser = () => {
    console.log('Saving user:', form);
    showModal.value = false;
    form.reset();
};

const deleteUser = () => {
    console.log('Deleting user:', selectedUser.value);
    showDeleteModal.value = false;
    selectedUser.value = null;
};

const toggleStatus = (user) => {
    console.log('Toggle status for:', user);
};
</script>

<template>
    <Head title="Quản lý Người dùng - Admin" />
    <AdminLayout title="Quản lý Người dùng">
        <div class="space-y-8 animate-in fade-in-50 duration-500 pb-10">
            <!-- Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div v-for="(count, key) in { 'TOTAL': users.length, 'STUDENT': users.filter(u => u.role === 'STUDENT').length, 'TEACHER': users.filter(u => u.role === 'TEACHER').length, 'ACTIVE': users.filter(u => u.status === 'active').length }" :key="key"
                    class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] shadow-sm border border-slate-100 dark:border-slate-800 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-blue-50 dark:bg-blue-900/10 rounded-full -mr-8 -mt-8"></div>
                    <div class="relative">
                        <p class="text-3xl font-black text-slate-900 dark:text-white mb-1 tracking-tighter">{{ count }}</p>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            {{ key === 'TOTAL' ? 'Tổng người dùng' : key === 'STUDENT' ? 'Sinh viên' : key === 'TEACHER' ? 'Giảng viên' : 'Đang hoạt động' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Header Actions & Search -->
            <div class="flex flex-col lg:flex-row gap-6">
                <div class="flex-1 bg-white dark:bg-slate-900 p-2 rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row gap-2">
                    <div class="relative flex-1 group">
                        <Icon icon="lucide:search" class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Tìm kiếm theo tên, email, mã số..."
                            class="pl-14 h-14 rounded-[1.5rem] bg-slate-50 dark:bg-slate-800 border-none dark:text-white focus:ring-2 focus:ring-blue-500/10 font-bold"
                        />
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" class="h-14 rounded-[1.5rem] px-8 font-black uppercase text-xs tracking-widest text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <Icon icon="lucide:filter" class="w-4 h-4 mr-2" />
                                {{ roleFilter === 'all' ? 'Tất cả' : getRoleInfo(roleFilter).label }}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-2 min-w-[200px]">
                            <DropdownMenuItem @click="roleFilter = 'all'" class="rounded-xl font-bold dark:text-slate-300">Tất cả vai trò</DropdownMenuItem>
                            <DropdownMenuItem v-for="(info, role) in roleLabels" :key="role" @click="roleFilter = role" class="rounded-xl font-bold dark:text-slate-300">
                                {{ info.label }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <Button
                    @click="openAddModal"
                    class="h-18 lg:h-auto bg-blue-600 hover:bg-blue-700 text-white rounded-[2rem] shadow-xl shadow-blue-600/20 px-10 transition-all hover:scale-[1.02] active:scale-95 py-6"
                >
                    <Icon icon="lucide:user-plus" class="w-6 h-6 mr-3" />
                    <span class="font-black uppercase tracking-widest text-sm">Thêm Thành viên</span>
                </Button>
            </div>

            <!-- Users Table -->
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 dark:bg-slate-800/40 border-b dark:border-slate-800">
                            <tr>
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Người Dùng</th>
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Định Danh</th>
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Phân Quyền</th>
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Trạng Thái</th>
                                <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] text-right">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr
                                v-for="user in filteredUsers"
                                :key="user.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors group"
                            >
                                <td class="p-8">
                                    <div class="flex items-center gap-5">
                                        <div class="relative">
                                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-lg transform group-hover:rotate-6 transition-transform">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div v-if="user.status === 'active'" class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 border-4 border-white dark:border-slate-900 rounded-full"></div>
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 dark:text-white text-lg tracking-tight">{{ user.name }}</p>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-8">
                                    <div class="space-y-1">
                                        <p class="font-mono text-sm font-black text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg inline-block">{{ user.code }}</p>
                                        <p class="text-xs font-bold text-slate-400">{{ user.phone }}</p>
                                    </div>
                                </td>
                                <td class="p-8">
                                    <span :class="[getRoleInfo(user.role).class, 'px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm']">
                                        {{ getRoleInfo(user.role).label }}
                                    </span>
                                </td>
                                <td class="p-8">
                                    <span :class="[getStatusInfo(user.status).class, 'px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-tighter']">
                                        {{ getStatusInfo(user.status).label }}
                                    </span>
                                </td>
                                <td class="p-8">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click="openEditModal(user)"
                                            class="w-10 h-10 flex items-center justify-center text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-all"
                                            title="Chỉnh sửa"
                                        >
                                            <Icon icon="lucide:edit" class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="toggleStatus(user)"
                                            :class="user.status === 'active' ? 'text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20' : 'text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'"
                                            class="w-10 h-10 flex items-center justify-center rounded-xl transition-all"
                                            :title="user.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa'"
                                        >
                                            <Icon :icon="user.status === 'active' ? 'lucide:user-x' : 'lucide:user-check'" class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="confirmDelete(user)"
                                            class="w-10 h-10 flex items-center justify-center text-slate-300 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all"
                                            title="Xóa"
                                        >
                                            <Icon icon="lucide:trash-2" class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit User Modal -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm shadow-2xl"
                @click.self="showModal = false"
            >
                <div class="bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl w-full max-w-2xl overflow-hidden animate-in zoom-in-95 duration-300 border-t-8 border-blue-600">
                    <!-- Header -->
                    <div class="p-10 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/30 dark:bg-slate-800/20">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">
                                {{ isEditing ? 'Cập nhật thành viên' : 'Thành viên mới' }}
                            </h3>
                            <p class="text-sm font-bold text-slate-400 mt-1">Thông tin chi tiết tài khoản truy cập hệ thống</p>
                        </div>
                        <button @click="showModal = false" class="w-12 h-12 flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors group">
                            <Icon icon="lucide:x" class="w-6 h-6 text-slate-300 group-hover:text-slate-600" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-10 space-y-8">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 px-2">Họ và tên đầy đủ</label>
                            <Input v-model="form.name" placeholder="Ví dụ: Nguyễn Văn A" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white shadow-inner" />
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 px-2">Email xác thực</label>
                                <Input v-model="form.email" type="email" placeholder="example@utc.edu.vn" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 px-2">Số điện thoại</label>
                                <Input v-model="form.phone" type="tel" placeholder="09xxxxx..." class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 px-2">Mã (MSV/CCCD)</label>
                                <Input v-model="form.code" placeholder="202160xxxx" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-black dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 px-2">Phân quyền vai trò</label>
                                <select
                                    v-model="form.role"
                                    class="w-full h-14 px-6 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/10 font-black dark:text-white appearance-none"
                                >
                                    <option v-for="(info, role) in roleLabels" :key="role" :value="role">{{ info.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="!isEditing" class="grid grid-cols-2 gap-6 pb-4">
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 px-2">Mật khẩu</label>
                                <Input v-model="form.password" type="password" placeholder="••••••••" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 px-2">Xác nhận lại</label>
                                <Input v-model="form.password_confirmation" type="password" placeholder="••••••••" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-10 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3 bg-slate-50/30 dark:bg-slate-800/20">
                        <Button @click="showModal = false" variant="ghost" class="h-14 rounded-2xl px-8 font-black text-slate-400 uppercase tracking-widest">Hủy bỏ</Button>
                        <Button @click="saveUser" class="h-14 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white shadow-xl shadow-blue-600/30 px-12 font-black uppercase tracking-widest">
                            {{ isEditing ? 'Cập nhật tài khoản' : 'Tạo tài khoản' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
                @click.self="showDeleteModal = false"
            >
                <div class="bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-300 p-10 text-center border-b-8 border-rose-600">
                    <div class="w-24 h-24 rounded-full bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center mx-auto mb-8 animate-bounce transition-all duration-1000">
                        <Icon icon="lucide:user-minus" class="w-12 h-12 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4 uppercase tracking-widest">Xác nhận xóa?</h3>
                    <p class="text-slate-500 dark:text-slate-400 font-bold mb-10 leading-relaxed px-4">
                        Bạn có chắc chắn muốn xóa tài khoản của <span class="text-rose-600">"{{ selectedUser?.name }}"</span>?
                    </p>
                    <div class="flex flex-col gap-3">
                        <Button @click="deleteUser" class="h-14 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-black uppercase tracking-widest shadow-lg shadow-rose-600/30">
                            Xác nhận xóa
                        </Button>
                        <Button @click="showDeleteModal = false" variant="ghost" class="h-12 rounded-2xl font-bold text-slate-400">
                            Hủy bỏ
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
