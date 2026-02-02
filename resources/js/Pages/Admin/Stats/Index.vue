<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const stats = ref([
    { label: 'Sách đang mượn', value: '1,240', change: '+12%', icon: 'lucide:book-open', color: 'bg-blue-500' },
    { label: 'Số thẻ cấp mới', value: '145', change: '+5%', icon: 'lucide:id-card', color: 'bg-emerald-500' },
    { label: 'Phạt trễ hạn', value: '2.4M', change: '-2%', icon: 'lucide:alert-circle', color: 'bg-rose-500' },
    { label: 'Lượt truy cập', value: '14.5K', change: '+18%', icon: 'lucide:trending-up', color: 'bg-indigo-500' },
]);
</script>

<template>
    <Head title="Thống kê - Admin" />
    <AdminLayout title="Báo cáo Thống kê">
        <div class="space-y-8 animate-in fade-in-50 duration-500">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="s in stats" :key="s.label" class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-slate-50 dark:bg-slate-800/40 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-125"></div>
                    <div class="relative">
                        <div :class="s.color" class="w-12 h-12 rounded-2xl flex items-center justify-center text-white mb-4 shadow-lg">
                            <Icon :icon="s.icon" class="w-6 h-6" />
                        </div>
                        <p class="text-3xl font-black text-slate-900 dark:text-white mb-1">{{ s.value }}</p>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">{{ s.label }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-6">Xu hướng mượn sách</h3>
                    <div class="h-64 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-center">
                        <div class="text-center">
                            <Icon icon="lucide:bar-chart" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                            <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest">Chart Visualization Placeholder</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-6">Phân loại độc giả</h3>
                    <div class="h-64 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-center">
                        <div class="text-center">
                            <Icon icon="lucide:pie-chart" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                            <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest">Chart Visualization Placeholder</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
