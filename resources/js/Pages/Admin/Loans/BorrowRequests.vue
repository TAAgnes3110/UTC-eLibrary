<script setup>
import { Head } from '@inertiajs/vue3';
import LoanBorrowRequestsPanel from '@/Components/Admin/Approvals/LoanBorrowRequestsPanel.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
</script>

<template>
    <Head title="Duyệt phiếu mượn từ yêu cầu bạn đọc" />
    <AdminLayout
        title="Duyệt yêu cầu"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Phiếu mượn', href: route('admin.loans.index') },
            { label: 'Duyệt yêu cầu' },
            { label: 'Duyệt phiếu mượn' },
        ]"
    >
        <LoanBorrowRequestsPanel />
    </AdminLayout>
</template>
