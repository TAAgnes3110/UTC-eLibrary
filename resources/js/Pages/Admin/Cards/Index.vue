<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const cards = ref([
    { id: 1, readerName: 'Lê Văn Tùng', cardNumber: 'UTC-2024-001', issueDate: '01/01/2024', expiryDate: '01/01/2025', status: 'Active' },
    { id: 2, readerName: 'Nguyễn Thị Mai', cardNumber: 'UTC-2024-002', issueDate: '15/01/2024', expiryDate: '15/01/2025', status: 'Expired' },
    { id: 3, readerName: 'Trần Minh Quân', cardNumber: 'UTC-2024-003', issueDate: '20/01/2024', expiryDate: '20/01/2025', status: 'Active' },
]);
</script>

<template>
    <Head title="Quản lý Thẻ thư viện - Admin" />
    <AdminLayout title="Quản lý Thẻ thư viện">
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="card in cards" :key="card.id"
                    class="relative bg-gradient-to-br from-blue-600 to-indigo-800 p-6 rounded-[2rem] shadow-2xl overflow-hidden aspect-[1.6/1] text-white group hover:scale-[1.02] transition-transform">
                    <!-- Card Background Pattern -->
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-colors"></div>

                    <div class="relative h-full flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] uppercase font-black tracking-widest opacity-80 mb-1">TRƯỜNG ĐH GIAO THÔNG VẬN TẢI</p>
                                <h4 class="text-xl font-black italic">e-Library Card</h4>
                            </div>
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center">
                                <Icon icon="lucide:library" class="w-7 h-7" />
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] uppercase font-bold opacity-60">Reader Name</p>
                                <p class="text-lg font-black tracking-tight uppercase">{{ card.readerName }}</p>
                            </div>
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[10px] uppercase font-bold opacity-60">Card Number</p>
                                    <p class="font-mono text-sm font-black">{{ card.cardNumber }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] uppercase font-bold opacity-60">Expires</p>
                                    <p class="text-xs font-black">{{ card.expiryDate }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute top-4 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="p-2 bg-white/20 rounded-full hover:bg-white/40"><Icon icon="lucide:printer" class="w-4 h-4" /></button>
                        <button class="p-2 bg-white/20 rounded-full hover:bg-white/40"><Icon icon="lucide:refresh-cw" class="w-4 h-4" /></button>
                    </div>

                    <div v-if="card.status === 'Expired'" class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] flex items-center justify-center">
                        <span class="px-6 py-2 bg-rose-600 text-white font-black text-sm rounded-full shadow-lg border-2 border-white/20">HẾT HẠN</span>
                    </div>
                </div>

                <button class="border-4 border-dashed border-slate-200 dark:border-slate-800 rounded-[2rem] aspect-[1.6/1] flex flex-col items-center justify-center text-slate-400 hover:text-blue-500 hover:border-blue-500 hover:bg-blue-50/10 transition-all group">
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4 group-hover:bg-blue-100 group-hover:scale-110 transition-all">
                        <Icon icon="lucide:plus" class="w-8 h-8" />
                    </div>
                    <p class="font-black">Gia hạn / Cấp mới thẻ</p>
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
