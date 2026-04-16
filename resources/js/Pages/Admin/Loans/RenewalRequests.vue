<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import LoanRenewalRequestsPanel from '@/Components/Admin/Approvals/LoanRenewalRequestsPanel.vue';
</script>

<template>
    <Head title="Duyệt gia hạn phiếu mượn" />
    <AdminLayout
        title="Gia hạn"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Phiếu mượn', href: route('admin.loans.index') }, { label: 'Duyệt gia hạn' }]"
    >
        <LoanRenewalRequestsPanel />
    </AdminLayout>
</template>
