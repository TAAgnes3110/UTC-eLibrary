<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    readers: { type: Array, default: () => [
        { id: 1, name: 'Lê Văn Tùng', code: '2021601234', faculty: 'CNTT', class: 'CNTT1-K62', type: 'student', status: 'active', gender: 'Nam', expiry_date: '2025-12-31', email: 'tung.lv@student.utc.edu.vn', phone: '0987654321' },
        { id: 2, name: 'Nguyễn Thị Mai', code: '2022605678', faculty: 'KT', class: 'KT-K63', type: 'student', status: 'active', gender: 'Nữ', expiry_date: '2026-06-30', email: 'mai.nt@student.utc.edu.vn', phone: '0123456789' },
        { id: 3, name: 'Trần Minh Quân', code: 'GV0012', faculty: 'Cơ khí', class: 'Khoa Cơ khí', type: 'teacher', status: 'active', gender: 'Nam', expiry_date: '2028-12-31', email: 'quan.tm@utc.edu.vn', phone: '0345678901' },
        { id: 4, name: 'Phạm Hồng Nam', code: '2020600111', faculty: 'ĐTVT', class: 'ĐTVT-K61', type: 'student', status: 'blocked', gender: 'Nam', expiry_date: '2024-05-20', email: 'nam.ph@student.utc.edu.vn', phone: '0567890123' },
    ]}
});

const activeTab = ref('students');
const searchQuery = ref('');
const statusFilter = ref('');
const showModal = ref(false);
const isEditing = ref(false);

const filtered = computed(() => {
    let result = props.readers;
    if (activeTab.value === 'students') {
        result = result.filter(r => r.type === 'student');
    } else if (activeTab.value === 'teachers') {
        result = result.filter(r => r.type === 'teacher');
    }

    if (statusFilter.value) {
        result = result.filter(r => r.status === statusFilter.value);
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(r =>
            (r.name || '').toLowerCase().includes(q) ||
            (r.code || '').toLowerCase().includes(q) ||
            (r.class || '').toLowerCase().includes(q) ||
            (r.faculty || '').toLowerCase().includes(q) ||
            (r.email || '').toLowerCase().includes(q) ||
            (r.phone || '').toLowerCase().includes(q)
        );
    }
    return result;
});

const form = useForm({
    id: null,
    name: '',
    code: '',
    faculty: '',
    class: '',
    type: 'student',
    gender: 'Nam',
    email: '',
    phone: '',
    expiry_date: '',
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.type = activeTab.value === 'teachers' ? 'teacher' : 'student';
    showModal.value = true;
};

const editReader = (r) => {
    isEditing.value = true;
    form.id = r.id;
    form.name = r.name;
    form.code = r.code;
    form.faculty = r.faculty || '';
    form.class = r.class || '';
    form.type = r.type;
    form.gender = r.gender || 'Nam';
    form.email = r.email || '';
    form.phone = r.phone || '';
    form.expiry_date = r.expiry_date || '';
    showModal.value = true;
};

const downloadTemplate = () => {
    window.location.href = '/templates/mau_nhap_ban_doc.csv';
};

const save = () => {
    showModal.value = false;
};
</script>

<template>
    <Head title="Quản lý Bạn đọc - Admin" />
    <AdminLayout
        title="Quản lý Bạn đọc"
        :breadcrumbs="[
            { label: 'Người dùng' },
            { label: 'Quản lý Bạn đọc' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Header Actions -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button
                        @click="activeTab = 'students'"
                        :class="[
                            'px-4 py-1.5 rounded-md text-[13px] font-bold transition-all',
                            activeTab === 'students' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        Học sinh / Sinh viên
                    </button>
                    <button
                        @click="activeTab = 'teachers'"
                        :class="[
                            'px-4 py-1.5 rounded-md text-[13px] font-bold transition-all',
                            activeTab === 'teachers' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        Giáo viên / Cán bộ
                    </button>
                </div>

                <div class="flex items-center gap-1.5">
                    <button @click="downloadTemplate" class="btn-excel-import">
                        <Icon icon="lucide:file-spreadsheet" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Nhập excel</span>
                    </button>
                    <button class="btn-excel-export">
                        <Icon icon="lucide:file-down" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Xuất excel</span>
                    </button>
                    <button @click="openAddModal" class="btn-action-primary">
                        <Icon icon="lucide:user-plus" class="w-[18px] h-[18px]" />
                        <span>Thêm bạn đọc</span>
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="flex items-center gap-3 bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                    <Input v-model="searchQuery" placeholder="Tìm tên, mã số, lớp, email, SĐT..." class="pl-10 h-10 rounded-lg bg-slate-50 dark:bg-slate-800/50 border-none text-sm" />
                </div>
                <!-- Fixed bug select visually by adding min-width and better padding -->
                <select v-model="statusFilter" class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-[13px] font-bold text-slate-600 dark:text-slate-300 outline-none min-w-[160px] appearance-none transition-all focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Đang hoạt động</option>
                    <option value="blocked">Đang khóa</option>
                </select>
            </div>

            <!-- Table (Split Columns) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Họ và tên</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã định danh</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Khoa</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Lớp / Đơn vị</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Giới tính</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Số điện thoại</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Email</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Hạn thẻ</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Trạng thái</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="r in filtered" :key="r.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm">
                                            {{ r.name.split(' ').pop().charAt(0) }}
                                        </div>
                                        <div class="font-bold text-slate-900 dark:text-white text-[13px] group-hover:text-blue-600 transition-colors">{{ r.name }}</div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="text-[12px] font-bold font-mono text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 shadow-sm">{{ r.code }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="text-[13px] font-medium text-slate-600 dark:text-slate-300">{{ r.faculty }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="text-[13px] font-medium text-slate-600 dark:text-slate-300">{{ r.class }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="text-[12px] text-slate-500">{{ r.gender }}</span>
                                </td>
                                <td class="p-4 font-mono text-[12px] text-slate-600">{{ r.phone }}</td>
                                <td class="p-4 text-[12px] text-blue-600 dark:text-blue-400 underline underline-offset-4 decoration-blue-500/30">{{ r.email }}</td>
                                <td class="p-4 text-center">
                                    <span class="text-[11px] font-bold text-slate-500">{{ r.expiry_date }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border shadow-sm inline-flex items-center gap-1',
                                            r.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100'
                                        ]"
                                    >
                                        <span class="w-1 h-1 rounded-full animate-pulse" :class="r.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        {{ r.status === 'active' ? 'Hoạt động' : 'Đã khóa' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-1">
                                        <button @click="editReader(r)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all">
                                            <Icon icon="lucide:edit-3" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-all">
                                            <Icon icon="lucide:trash-2" class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal (Standard) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-xl overflow-hidden shadow-xl border border-slate-200 dark:border-slate-800">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-blue-600">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">{{ isEditing ? 'Cập nhật' : 'Thêm mới' }} bạn đọc</h3>
                        <button @click="showModal = false" class="text-white/80 hover:text-white">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Họ và tên</label>
                            <Input v-model="form.name" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-2 sm:col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mã định danh</label>
                            <Input v-model="form.code" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs font-mono text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Khoa</label>
                            <Input v-model="form.faculty" placeholder="Ví dụ: CNTT" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Lớp / Đơn vị</label>
                            <Input v-model="form.class" placeholder="Ví dụ: CNTT1-K62" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Giới tính</label>
                            <select v-model="form.gender" class="w-full h-9 px-3 rounded-md border border-slate-200 dark:border-slate-700 text-xs outline-none bg-white dark:bg-slate-800 text-slate-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark]">
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                            </select>
                        </div>
                        <div class="col-span-2 sm:col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Số điện thoại</label>
                            <Input v-model="form.phone" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-2 sm:col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Email</label>
                            <Input v-model="form.email" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ngày hết hạn</label>
                            <Input v-model="form.expiry_date" type="date" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark]" />
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                        <Button variant="outline" size="sm" @click="showModal = false" class="h-8 px-4 font-bold text-xs rounded-md">Hủy bỏ</Button>
                        <Button size="sm" @click="save" class="h-8 px-6 font-bold text-xs rounded-md bg-blue-600 hover:bg-blue-700 text-white">Lưu thay đổi</Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
