<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import WelcomeBanner from '@/Components/Admin/Dashboard/WelcomeBanner.vue';
import StatsCards from '@/Components/Admin/Dashboard/StatsCards.vue';

const statsCards = [
    { title: 'Sách hiện có trong kho', value: '15,240', icon: 'lucide:book', color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { title: 'Người đang mượn sách', value: '1,204', icon: 'lucide:users', color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { title: 'Sách đang cho mượn', value: '342', icon: 'lucide:clipboard-list', color: 'text-amber-600', bg: 'bg-amber-50' },
    { title: 'Sách chờ thu hồi (Quá hạn)', value: '15', icon: 'lucide:alert-triangle', color: 'text-rose-600', bg: 'bg-rose-50' },
];

const quickActions = [];
</script>

<template>
    <Head title="Tổng quan - Admin" />
    <AdminLayout title="Tổng quan">
        <div class="space-y-8 animate-in fade-in-50 duration-500">
            <!-- Welcome Banner -->
            <WelcomeBanner :quick-actions="quickActions" />

            <!-- Stats Cards -->
            <StatsCards :stats="statsCards" />

            <!-- Các block thống kê chi tiết về sách/mượn đã được tạm thời ẩn khi thiết kế lại hệ thống thư viện -->
        </div>
    </AdminLayout>
</template>
