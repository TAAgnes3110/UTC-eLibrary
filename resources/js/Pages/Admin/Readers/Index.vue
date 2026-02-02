<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const readers = ref([
    { id: 1, name: 'Lê Văn Tùng', code: '2021601234', class: 'CNTT1-K62', type: 'Sinh viên', status: 'Active' },
    { id: 2, name: 'Nguyễn Thị Mai', code: '2022605678', class: 'KT-K63', type: 'Sinh viên', status: 'Active' },
    { id: 3, name: 'Trần Minh Quân', code: 'GV0012', class: 'Khoa Cơ khí', type: 'Giảng viên', status: 'Active' },
    { id: 4, name: 'Phạm Hồng Nam', code: '2020600111', class: 'ĐTVT-K61', type: 'Sinh viên', status: 'Blocked' },
]);

const searchQuery = ref('');
const filtered = computed(() => readers.value.filter(r => r.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || r.code.includes(searchQuery.value)));
</script>

<template>
    <Head title="Quản lý Độc giả - Admin" />
    <AdminLayout title="Quản lý Độc giả">
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="relative w-full max-w-md">
                    <Icon icon="lucide:search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <Input v-model="searchQuery" placeholder="Tìm tên, mã sinh viên..." class="pl-12 h-12 rounded-xl dark:bg-slate-900 border-none dark:text-white" />
                </div>
                <Button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-lg w-full sm:w-auto">
                    <Icon icon="lucide:user-plus" class="mr-2" />
                    Thêm Độc Giả
                </Button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 border-b dark:border-slate-800">
                            <tr>
                                <th class="p-5 text-[10px] uppercase font-black text-slate-400">Độc giả</th>
                                <th class="p-5 text-[10px] uppercase font-black text-slate-400">Mã Số / Lớp</th>
                                <th class="p-5 text-[10px] uppercase font-black text-slate-400">Loại</th>
                                <th class="p-5 text-[10px] uppercase font-black text-slate-400">Trạng thái</th>
                                <th class="p-5 text-[10px] uppercase font-black text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-800">
                            <tr v-for="r in filtered" :key="r.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 font-bold">
                                            {{ r.name.charAt(0) }}
                                        </div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ r.name }}</div>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="font-mono text-sm text-slate-700 dark:text-slate-300">{{ r.code }}</div>
                                    <div class="text-xs text-slate-400 font-medium">{{ r.class }}</div>
                                </td>
                                <td class="p-5">
                                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full text-[10px] font-bold dark:text-slate-300">{{ r.type }}</span>
                                </td>
                                <td class="p-5">
                                    <span :class="r.status === 'Active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400'" class="px-2 py-0.5 rounded text-[10px] font-black uppercase">
                                        {{ r.status }}
                                    </span>
                                </td>
                                <td class="p-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all"><Icon icon="lucide:id-card" /></button>
                                        <button class="p-2 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-all"><Icon icon="lucide:edit" /></button>
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
