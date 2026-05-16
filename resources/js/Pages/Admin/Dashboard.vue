<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import WelcomeBanner from '@/Components/Admin/Dashboard/WelcomeBanner.vue';
import StatsCards from '@/Components/Admin/Dashboard/StatsCards.vue';
import LoanChart from '@/Components/Admin/Dashboard/LoanChart.vue';
import DigitalPurchaseChart from '@/Components/Admin/Dashboard/DigitalPurchaseChart.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { loansApi } from '@/api/loans';
import { booksApi } from '@/api/books';
import { toast } from '@/store/toast';

const loadingLoanStats = ref(false);
const loadingDigitalStats = ref(false);
const loanGranularity = ref('month');
const digitalGranularity = ref('month');
const summary = ref({
    total_books: 0,
    total_registered_cards: 0,
    active_borrowers: 0,
    books_on_loan: 0,
    lost_books: 0,
    overdue_loans: 0,
    today_borrowed: 0,
    digital_books_purchased: 0,
    digital_revenue_vnd: 0,
});
const chartSeries = ref([]);
const digitalChartSeries = ref([]);
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
        title: 'Sách đã mất',
        value: formatNumber(summary.value.lost_books),
        icon: 'lucide:book-x',
        color: 'text-red-600',
        bg: 'bg-red-50',
    },
    {
        title: 'Sách chờ thu hồi',
        value: formatNumber(summary.value.overdue_loans),
        icon: 'lucide:alert-triangle',
        color: 'text-rose-600',
        bg: 'bg-rose-50',
    },
]);

const quickActions = [];

async function exportLostBooks() {
    try {
        const response = await booksApi.exportLost();
        const blob = new Blob([response.data], {
            type:
                response.headers['content-type'] ||
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'Danh_sach_sach_da_mat.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        toast.success('Đã xuất danh sách sách đã mất.', { title: 'Dashboard' });
    } catch (e) {
        toast.error(e?.response?.data?.message || 'Không thể xuất danh sách sách đã mất.', { title: 'Dashboard' });
    }
}

function applySummary(payload) {
    if (!payload?.summary) return;
    summary.value = {
        ...summary.value,
        ...payload.summary,
    };
}

async function fetchStatisticsPayload(params = {}) {
    const response = await loansApi.statistics({
        granularity: loanGranularity.value,
        digital_granularity: digitalGranularity.value,
        ...params,
    });
    return response?.data || {};
}

async function loadLoanChartStats() {
    loadingLoanStats.value = true;
    try {
        const payload = await fetchStatisticsPayload({
            parts: 'series,forecast',
        });
        chartSeries.value = Array.isArray(payload.series) ? payload.series : [];
        forecast.value = payload.forecast || { next_label: '-', expected_borrowed: 0 };
    } catch (e) {
        chartSeries.value = [];
        toast.error(e?.response?.data?.messages || 'Không tải được thống kê mượn/trả.', { title: 'Dashboard' });
    } finally {
        loadingLoanStats.value = false;
    }
}

async function loadDigitalChartStats() {
    loadingDigitalStats.value = true;
    try {
        const payload = await fetchStatisticsPayload({
            parts: 'summary,digital_series',
        });
        applySummary(payload);
        digitalChartSeries.value = Array.isArray(payload.digital_series) ? payload.digital_series : [];
    } catch (e) {
        digitalChartSeries.value = [];
        toast.error(e?.response?.data?.messages || 'Không tải được thống kê mua tài liệu số.', { title: 'Dashboard' });
    } finally {
        loadingDigitalStats.value = false;
    }
}

async function loadDashboardStats() {
    loadingLoanStats.value = true;
    loadingDigitalStats.value = true;
    try {
        const payload = await fetchStatisticsPayload();
        applySummary(payload);
        chartSeries.value = Array.isArray(payload.series) ? payload.series : [];
        digitalChartSeries.value = Array.isArray(payload.digital_series) ? payload.digital_series : [];
        forecast.value = payload.forecast || { next_label: '-', expected_borrowed: 0 };
    } catch (e) {
        chartSeries.value = [];
        digitalChartSeries.value = [];
        toast.error(e?.response?.data?.messages || 'Không tải được thống kê dashboard.', { title: 'Dashboard' });
    } finally {
        loadingLoanStats.value = false;
        loadingDigitalStats.value = false;
    }
}

onMounted(() => {
    loadDashboardStats();
});

watch(loanGranularity, () => {
    loadLoanChartStats();
});

watch(digitalGranularity, () => {
    loadDigitalChartStats();
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
            <div class="flex items-center justify-end">
                <Button
                    type="button"
                    variant="destructive"
                    class="gap-2"
                    @click="exportLostBooks"
                >
                    <Icon icon="lucide:file-down" class="w-4 h-4" />
                    Xuất sách đã mất
                </Button>
            </div>
            <StatsCards :stats="statsCards" />

            <LoanChart
                v-model:granularity="loanGranularity"
                :series="chartSeries"
                :loading="loadingLoanStats"
                :forecast="forecast"
                @refresh="loadLoanChartStats"
            />

            <DigitalPurchaseChart
                v-model:granularity="digitalGranularity"
                :series="digitalChartSeries"
                :summary="summary"
                :loading="loadingDigitalStats"
                @refresh="loadDigitalChartStats"
            />
        </div>
    </AdminLayout>
</template>
