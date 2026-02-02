<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const loans = ref([
    { id: 1, reader: 'Lê Văn Tùng', book: 'Giáo trình Cấu trúc dữ liệu', date: '01/01/2024', dueDate: '15/01/2024', status: 'Borrrowed' },
    { id: 2, reader: 'Nguyễn Thị Mai', book: 'Lập trình Java', date: '05/01/2024', dueDate: '20/01/2024', status: 'Overdue' },
    { id: 3, reader: 'Trần Minh Quân', book: 'Xác suất thống kê', date: '02/01/2024', dueDate: '16/01/2024', status: 'Returned' },
]);
</script>

<template>
    <Head title="Quản lý Phiếu mượn - Admin" />
    <AdminLayout title="Quản lý Phiếu mượn">
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <div class="flex justify-between items-center mb-6">
                <Button class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg">
                    <Icon icon="lucide:clipboard-plus" class="mr-2" />
                    Tạo Phiếu Mượn
                </Button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b dark:border-slate-800">
                        <tr>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400">Độc giả / Sách</th>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400">Ngày mượn</th>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400">Hạn trả</th>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400">Trạng thái</th>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-slate-800">
                        <tr v-for="l in loans" :key="l.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                            <td class="p-5">
                                <div class="font-bold text-slate-900 dark:text-white mb-1">{{ l.reader }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 italic">{{ l.book }}</div>
                            </td>
                            <td class="p-5 text-sm text-slate-600 dark:text-slate-400">{{ l.date }}</td>
                            <td class="p-5 text-sm font-bold text-slate-700 dark:text-slate-300">{{ l.dueDate }}</td>
                            <td class="p-5">
                                <span v-if="l.status === 'Borrrowed'" class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 rounded text-[10px] font-black uppercase">ĐANG MƯỢN</span>
                                <span v-else-if="l.status === 'Overdue'" class="px-2 py-0.5 bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 rounded text-[10px] font-black uppercase">QUÁ HẠN</span>
                                <span v-else class="px-2 py-0.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 rounded text-[10px] font-black uppercase">ĐÃ TRẢ</span>
                            </td>
                            <td class="p-5 text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" class="h-8 rounded-lg text-xs font-bold border-blue-200 text-blue-600 hover:bg-blue-50">Trả sách</Button>
                                    <button class="p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg"><Icon icon="lucide:more-vertical" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
