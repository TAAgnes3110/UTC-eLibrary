<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProfileUpdateRequestsPanel from '@/Components/Admin/Approvals/ProfileUpdateRequestsPanel.vue';
</script>

<template>
    <Head title="Duyệt yêu cầu cập nhật - Admin" />
    <AdminLayout
        title="Duyệt yêu cầu cập nhật hồ sơ"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý người dùng' },
            { label: 'Duyệt yêu cầu cập nhật' },
        ]"
    >
        <ProfileUpdateRequestsPanel />
    </AdminLayout>
</template>
