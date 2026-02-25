<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    publishers: { type: Array, default: () => [
        { id: 1, name: 'NXB Giao Thông Vận Tải', address: '80A Trần Hưng Đạo, Hà Nội', email: 'nxbgtvt@utc.edu.vn', phone: '024 3942 2167', books_count: 156 },
        { id: 2, name: 'NXB Bách Khoa', address: 'Số 1 Đại Cồ Việt, Hà Nội', email: 'bkpress@hust.edu.vn', phone: '024 3869 2242', books_count: 89 },
        { id: 3, name: 'NXB Giáo Dục', address: '81 Trần Hưng Đạo, Hà Nội', email: 'nxbgd@moet.gov.vn', phone: '024 3822 0801', books_count: 412 },
    ]}
});

const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);

const filtered = computed(() => {
    if (!searchQuery.value) return props.publishers;
    const q = searchQuery.value.toLowerCase();
    return props.publishers.filter(p =>
        p.name.toLowerCase().includes(q) ||
        (p.email || '').toLowerCase().includes(q) ||
        (p.phone || '').toLowerCase().includes(q) ||
        (p.address || '').toLowerCase().includes(q)
    );
});

const form = useForm({
    id: null,
    name: '',
    address: '',
    email: '',
    phone: '',
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const editPublisher = (p) => {
    isEditing.value = true;
    form.id = p.id;
    form.name = p.name;
    form.address = p.address || '';
    form.email = p.email || '';
    form.phone = p.phone || '';
    showModal.value = true;
};

const save = () => {
    showModal.value = false;
};
</script>

<template>
    <Head title="Quản lý Nhà xuất bản - Admin" />
    <AdminLayout
        title="Quản lý Nhà xuất bản"
        :breadcrumbs="[
            { label: 'Dữ liệu Thư viện' },
            { label: 'Quản lý Nhà xuất bản' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Action Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Nhà xuất bản</h2>
                <div class="flex items-center gap-1.5">
                    <button class="btn-excel-export">
                        <Icon icon="lucide:file-down" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Xuất excel</span>
                    </button>
                    <button @click="openAddModal" class="btn-action-primary">
                        <Icon icon="lucide:plus" class="w-[18px] h-[18px]" />
                        <span>Thêm Nhà xuất bản</span>
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                    <Input v-model="searchQuery" placeholder="Tìm tên NXB, địa chỉ, email..." class="pl-10 h-10 rounded-lg bg-slate-50 dark:bg-slate-800/50 border-none text-sm" />
                </div>
            </div>

            <!-- Table (Split Columns for DB Readiness) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-16 text-center">ID</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên Nhà xuất bản</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Địa chỉ trụ sở</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Số điện thoại</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Email</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Số sách</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="p in filtered" :key="p.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all">
                                <td class="p-4 text-center font-mono text-xs text-slate-400">#{{ String(p.id).padStart(3, '0') }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                                            <Icon icon="lucide:building-2" class="w-4 h-4" />
                                        </div>
                                        <div class="font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 transition-colors text-[13px] tracking-tight">{{ p.name }}</div>
                                    </div>
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-400 truncate max-w-[200px] xl:max-w-xs">{{ p.address }}</td>
                                <td class="p-4 text-[12px] font-medium text-slate-600 dark:text-slate-300">{{ p.phone }}</td>
                                <td class="p-4 text-[12px] text-blue-600 dark:text-blue-400 underline underline-offset-4 decoration-blue-500/30">{{ p.email }}</td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded text-[11px] font-bold">
                                        {{ p.books_count }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="editPublisher(p)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all" title="Chỉnh sửa">
                                            <Icon icon="lucide:edit-3" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-all" title="Xóa">
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
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">{{ isEditing ? 'Cập nhật' : 'Thêm mới' }} NXB</h3>
                        <button @click="showModal = false" class="text-white/80 hover:text-white">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div class="col-span-2 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tên Nhà xuất bản</label>
                            <Input v-model="form.name" placeholder="Ví dụ: NXB Giao Thông Vận Tải" class="h-9 rounded-md border-slate-200 text-xs" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Số điện thoại</label>
                            <Input v-model="form.phone" placeholder="024 3xxx..." class="h-9 rounded-md border-slate-200 text-xs" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Email liên hệ</label>
                            <Input v-model="form.email" placeholder="contact@publisher.com" class="h-9 rounded-md border-slate-200 text-xs" />
                        </div>
                        <div class="col-span-2 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Địa chỉ trụ sở</label>
                            <textarea v-model="form.address" placeholder="Nhập địa chỉ đầy đủ..." class="w-full h-20 p-3 rounded-md border border-slate-200 text-xs outline-none focus:ring-1 focus:ring-blue-500/50 transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                        <Button variant="outline" size="sm" @click="showModal = false" class="h-8 px-4 font-bold text-xs rounded-md">Bỏ qua</Button>
                        <Button size="sm" @click="save" class="h-8 px-6 font-bold text-xs rounded-md bg-blue-600 hover:bg-blue-700 text-white">Lưu thay đổi</Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
