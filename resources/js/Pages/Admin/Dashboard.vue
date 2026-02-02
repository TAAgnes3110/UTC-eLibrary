<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import WelcomeBanner from '@/Components/Admin/Dashboard/WelcomeBanner.vue';
import StatsCards from '@/Components/Admin/Dashboard/StatsCards.vue';
import LoanChart from '@/Components/Admin/Dashboard/LoanChart.vue';
import RecentActivity from '@/Components/Admin/Dashboard/RecentActivity.vue';
import OverdueBooks from '@/Components/Admin/Dashboard/OverdueBooks.vue';
import PopularBooks from '@/Components/Admin/Dashboard/PopularBooks.vue';

defineProps({
    stats: { type: Object, default: () => ({}) },
    recentLoans: { type: Array, default: () => [] },
    overdueBooks: { type: Array, default: () => [] },
});

const statsCards = [
    { title: 'Tổng số Sách', value: '15,240', icon: 'lucide:book', color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { title: 'Độc Giả', value: '1,204', icon: 'lucide:users', color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { title: 'Phiếu Mượn', value: '342', icon: 'lucide:clipboard-list', color: 'text-amber-600', bg: 'bg-amber-50' },
    { title: 'Sách Quá Hạn', value: '15', icon: 'lucide:alert-triangle', color: 'text-rose-600', bg: 'bg-rose-50' },
];

const quickActions = [
    { label: 'Xử lý Mượn Trả', icon: 'lucide:qr-code', href: 'admin.loans' }, // Corrected route names to be more standard if needed, or keep as is.
    { label: 'Thêm Sách Mới', icon: 'lucide:plus', href: 'admin.books' },
    { label: 'Xem Báo Cáo', icon: 'lucide:bar-chart-3', href: 'admin.stats' },
];

const handleQuickAction = (action) => {
    // Basic navigation handling - assuming route names exist
     // In a real app, use route(action.href)
     console.log('Navigating to:', action.href);
     // router.visit(route(action.href));
};

const recentActivities = [
    { user: 'Nguyễn Văn A', action: 'Đã mượn "Lập trình PHP"', time: '5 phút trước' },
    { user: 'Trần Thị B', action: 'Đã trả "Cấu trúc dữ liệu"', time: '15 phút trước' },
    { user: 'Lê Văn C', action: 'Đăng ký thẻ mới', time: '1 giờ trước' },
    { user: 'Phạm Thị D', action: 'Đã mượn "Giải tích 1"', time: '2 giờ trước' },
    { user: 'Hoàng Văn E', action: 'Gia hạn sách', time: '3 giờ trước' },
];
</script>

<template>
    <Head title="Tổng quan - Admin" />
    <AdminLayout title="Tổng quan">
        <div class="space-y-8 animate-in fade-in-50 duration-500">
            <!-- Welcome Banner -->
            <WelcomeBanner
                :quick-actions="quickActions"
                @action-click="handleQuickAction"
            />

            <!-- Stats Cards -->
            <StatsCards :stats="statsCards" />

            <!-- Charts & Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-7 gap-6">
                <!-- Chart Section -->
                <LoanChart class="lg:col-span-4" />

                <!-- Recent Activity -->
                <RecentActivity
                    class="lg:col-span-3"
                    :activities="recentActivities"
                />
            </div>

            <!-- Quick Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Overdue Books -->
                <OverdueBooks :books="overdueBooks" />

                <!-- Popular Books -->
                <PopularBooks />
            </div>
        </div>
    </AdminLayout>
</template>
