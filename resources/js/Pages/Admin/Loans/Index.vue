<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    loans: { type: Array, default: () => [
        { id: 1, reader_name: 'Lê Văn Tùng', reader_code: '2021601234', book_title: 'Giáo trình Cấu trúc dữ liệu', book_code: 'CNTT-0012', loan_date: '2024-02-15', due_date: '2024-03-01', status: 'borrowed' },
        { id: 2, reader_name: 'Nguyễn Thị Mai', reader_code: '2022605678', book_title: 'Lập trình Java cho người mới', book_code: 'CNTT-0567', loan_date: '2024-02-10', due_date: '2024-02-24', status: 'overdue' },
        { id: 3, reader_name: 'Trần Minh Quân', reader_code: 'GV0012', book_title: 'Xác suất thống kê ứng dụng', book_code: 'TOAN-0001', loan_date: '2024-01-20', due_date: '2024-02-03', status: 'returned', return_date: '2024-02-02' },
        { id: 4, reader_name: 'Phạm Hồng Nam', reader_code: '2020600111', book_title: 'Kỹ thuật số', book_code: 'DTVT-0099', loan_date: '2024-02-20', due_date: '2024-03-05', status: 'borrowed' },
    ]}
});

const searchQuery = ref('');
const statusFilter = ref('');

const filtered = computed(() => {
    return props.loans.filter(l => {
        const q = searchQuery.value.toLowerCase();
        const matchesSearch = (l.reader_name || '').toLowerCase().includes(q) ||
                             (l.book_title || '').toLowerCase().includes(q) ||
                             (l.reader_code || '').toLowerCase().includes(q) ||
                             (l.book_code || '').toLowerCase().includes(q);
        const matchesStatus = statusFilter.value ? l.status === statusFilter.value : true;
        return matchesSearch && matchesStatus;
    });
});

const getStatusStyle = (status) => {
    switch(status) {
        case 'borrowed': return 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800';
        case 'overdue': return 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800';
        case 'returned': return 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800';
        default: return 'bg-slate-50 text-slate-700 border-slate-100';
    }
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'borrowed': return 'Đang mượn';
        case 'overdue': return 'Quá hạn';
        case 'returned': return 'Đã trả';
        default: return status;
    }
};
</script>

<template>
    <Head title="Quản lý Mượn trả - Admin" />
    <AdminLayout
        title="Quản lý Mượn trả"
        :breadcrumbs="[
            { label: 'Mượn & Trả' },
            { label: 'Danh sách phiếu' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Stats overview -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4 shadow-sm">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <Icon icon="lucide:book-up" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-slate-900 dark:text-white leading-tight">124</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đang mượn</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4 shadow-sm border-l-4 border-l-rose-500">
                    <div class="w-10 h-10 rounded-lg bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <Icon icon="lucide:alert-triangle" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-rose-600 leading-tight">12</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Quá hạn</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4 shadow-sm">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <Icon icon="lucide:book-check" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-slate-900 dark:text-white leading-tight">850</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đã trả (Tháng)</div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <button class="btn-excel-export">
                        <Icon icon="lucide:file-down" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Xuất excel</span>
                    </button>
                    <button class="btn-action-primary">
                        <Icon icon="lucide:plus" class="w-[18px] h-[18px]" />
                        <span>Tạo phiếu mượn mới</span>
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="flex items-center gap-3 bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                    <Input v-model="searchQuery" placeholder="Tìm tên bạn đọc, mã SV, tên sách, mã sách..." class="pl-10 h-10 rounded-lg bg-slate-50 dark:bg-slate-800/50 border-none text-sm" />
                </div>
                <select v-model="statusFilter" class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-[13px] font-bold text-slate-600 dark:text-slate-300 outline-none min-w-[160px] appearance-none focus:border-blue-500 transition-all">
                    <option value="">Tất cả trạng thái</option>
                    <option value="borrowed">Đang mượn</option>
                    <option value="overdue">Quá hạn</option>
                    <option value="returned">Đã trả</option>
                </select>
            </div>

            <!-- Table (Split Columns) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Bạn đọc</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã bạn đọc</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên sách</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã cá biệt</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Ngày mượn</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Hạn trả</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Trạng thái</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="l in filtered" :key="l.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white text-[13px] tracking-tight group-hover:text-blue-600 transition-colors">{{ l.reader_name }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="text-[12px] font-bold font-mono text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">{{ l.reader_code }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="text-[13px] font-medium text-slate-600 dark:text-slate-300 max-w-[200px] truncate" :title="l.book_title">{{ l.book_title }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="text-[12px] font-bold font-mono text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded border border-blue-100 dark:border-blue-800 uppercase">{{ l.book_code }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="text-[12px] text-slate-500 font-medium">{{ l.loan_date }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="text-[12px] font-bold" :class="l.status === 'overdue' ? 'text-rose-600' : 'text-slate-700 dark:text-slate-200'">{{ l.due_date }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border shadow-sm', getStatusStyle(l.status)]">
                                        {{ getStatusLabel(l.status) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-1">
                                        <button v-if="l.status !== 'returned'" class="px-3 py-1 bg-blue-600 text-white rounded-md text-[10px] font-bold uppercase tracking-wider hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/10">
                                            Trả sách
                                        </button>
                                        <button class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all">
                                            <Icon icon="lucide:eye" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button class="p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-all">
                                            <Icon icon="lucide:printer" class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
