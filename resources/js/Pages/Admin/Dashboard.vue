<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import WelcomeBanner from '@/Components/Admin/Dashboard/WelcomeBanner.vue';
import StatsCards from '@/Components/Admin/Dashboard/StatsCards.vue';
import LoanChart from '@/Components/Admin/Dashboard/LoanChart.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { loansApi } from '@/api/loans';
import { toast } from '@/store/toast';

const loadingStats = ref(false);
const granularity = ref('month');
const summary = ref({
    total_books: 0,
    total_registered_cards: 0,
    active_borrowers: 0,
    books_on_loan: 0,
    overdue_loans: 0,
    today_borrowed: 0,
});
const chartSeries = ref([]);
const forecast = ref({ next_label: '-', expected_borrowed: 0 });

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN').format(Number(value || 0));
}

const statsCards = computed(() => [
    {
        title: 'Sách hiện có trong kho',
        value: formatNumber(summary.value.total_books),
        icon: 'lucide:book',
        color: 'text-indigo-600',
        bg: 'bg-indigo-50',
    },
    {
        title: 'Thẻ thư viện đã đăng ký',
        value: formatNumber(summary.value.total_registered_cards),
        icon: 'lucide:id-card',
        color: 'text-cyan-600',
        bg: 'bg-cyan-50',
    },
    {
        title: 'Người đang mượn sách',
        value: formatNumber(summary.value.active_borrowers),
        icon: 'lucide:users',
        color: 'text-emerald-600',
        bg: 'bg-emerald-50',
    },
    {
        title: 'Sách đang cho mượn',
        value: formatNumber(summary.value.books_on_loan),
        icon: 'lucide:clipboard-list',
        color: 'text-amber-600',
        bg: 'bg-amber-50',
    },
    {
        title: 'Sách chờ thu hồi (Quá hạn)',
        value: formatNumber(summary.value.overdue_loans),
        icon: 'lucide:alert-triangle',
        color: 'text-rose-600',
        bg: 'bg-rose-50',
    },
]);

const quickActions = [];

async function loadDashboardStats() {
    loadingStats.value = true;
    try {
        const response = await loansApi.statistics({ granularity: granularity.value });
        const payload = response?.data || {};
        summary.value = {
            ...summary.value,
            ...(payload.summary || {}),
        };
        chartSeries.value = Array.isArray(payload.series) ? payload.series : [];
        forecast.value = payload.forecast || { next_label: '-', expected_borrowed: 0 };
    } catch (e) {
        chartSeries.value = [];
        toast.error(e?.response?.data?.messages || 'Không tải được thống kê dashboard.', { title: 'Dashboard' });
    } finally {
        loadingStats.value = false;
    }
}

onMounted(() => {
    loadDashboardStats();
});

watch(granularity, () => {
    loadDashboardStats();
});
</script>

<template>
    <Head title="Tổng quan - Admin" />
    <AdminLayout title="Tổng quan">
        <div class="space-y-8 animate-in fade-in-50 duration-500">
            <!-- Welcome Banner -->
            <WelcomeBanner
                :quick-actions="quickActions"
                :today-borrow-count="summary.today_borrowed"
                :overdue-count="summary.overdue_loans"
            />

            <!-- Stats Cards -->
            <StatsCards :stats="statsCards" />

            <LoanChart
                v-model:granularity="granularity"
                :series="chartSeries"
                :loading="loadingStats"
                :forecast="forecast"
                @refresh="loadDashboardStats"
            />
        </div>
    </AdminLayout>
</template>
