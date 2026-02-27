<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const period = ref('month');

const stats = ref([
    { label: 'Sách đang mượn', value: '1,240', change: '+12%', changeUp: true, icon: 'lucide:book-open', color: 'bg-blue-500', desc: 'So với kỳ trước' },
    { label: 'Số thẻ cấp mới', value: '145', change: '+5%', changeUp: true, icon: 'lucide:id-card', color: 'bg-emerald-500', desc: 'Trong kỳ' },
    { label: 'Phạt trễ hạn', value: '2.4M', change: '-2%', changeUp: false, icon: 'lucide:alert-circle', color: 'bg-rose-500', desc: 'Thu trong kỳ' },
    { label: 'Lượt truy cập', value: '14.5K', change: '+18%', changeUp: true, icon: 'lucide:trending-up', color: 'bg-indigo-500', desc: 'Tổng lượt' },
]);

const exportReport = () => {
    // TODO: Gọi API xuất báo cáo
    alert('Chức năng xuất báo cáo đang được xây dựng');
};
</script>

<template>
    <Head title="Quản lý Báo cáo - Admin" />
    <AdminLayout
        title="Quản lý Báo cáo"
        :breadcrumbs="[
            { label: 'Hệ thống' },
            { label: 'Quản lý Báo cáo' },
        ]"
    >
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <!-- Header + Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Quản lý Báo cáo</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Thống kê tổng quan và xu hướng mượn sách</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <label class="sr-only">Chọn kỳ báo cáo</label>
                    <select v-model="period"
                        class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-blue-500/20 outline-none appearance-none cursor-pointer min-w-[140px]">
                        <option value="week">Tuần này</option>
                        <option value="month">Tháng này</option>
                        <option value="quarter">Quý này</option>
                        <option value="year">Năm nay</option>
                    </select>
                    <Button type="button" @click="exportReport" variant="outline" size="sm"
                        class="h-10 px-4 rounded-xl text-sm font-bold border-slate-200 dark:border-slate-700">
                        <Icon icon="lucide:file-down" class="w-4 h-4 mr-2" />
                        Xuất báo cáo
                    </Button>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="s in stats" :key="s.label"
                    class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 rounded-full opacity-10 -mr-6 -mt-6 transition-transform group-hover:scale-110" :class="s.color"></div>
                    <div class="relative">
                        <div :class="[s.color, 'w-11 h-11 rounded-xl flex items-center justify-center text-white shadow-sm mb-3']">
                            <Icon :icon="s.icon" class="w-5 h-5" />
                        </div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ s.value }}</p>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-0.5">{{ s.label }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">{{ s.desc }}</p>
                        <span :class="[
                            'inline-flex items-center gap-0.5 text-[11px] font-bold mt-2',
                            s.changeUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                        ]">
                            <Icon :icon="s.changeUp ? 'lucide:trending-up' : 'lucide:trending-down'" class="w-3 h-3" />
                            {{ s.change }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Xu hướng mượn sách</h3>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Theo {{ period === 'week' ? 'ngày' : period === 'month' ? 'tuần' : 'tháng' }}</span>
                    </div>
                    <div class="h-64 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center justify-center">
                        <div class="text-center">
                            <Icon icon="lucide:bar-chart-3" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                            <p class="text-slate-400 dark:text-slate-500 font-medium text-sm">Biểu đồ sẽ hiển thị khi có dữ liệu</p>
                            <p class="text-slate-400 dark:text-slate-600 text-xs mt-0.5">Chart placeholder</p>
                        </div>
                    </div>
                </section>
                <section class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Phân loại độc giả</h3>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Theo loại thẻ</span>
                    </div>
                    <div class="h-64 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center justify-center">
                        <div class="text-center">
                            <Icon icon="lucide:pie-chart" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                            <p class="text-slate-400 dark:text-slate-500 font-medium text-sm">Biểu đồ sẽ hiển thị khi có dữ liệu</p>
                            <p class="text-slate-400 dark:text-slate-600 text-xs mt-0.5">Chart placeholder</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
