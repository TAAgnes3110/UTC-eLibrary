<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DigitalDocumentSubmissionsPanel from '@/Components/Admin/Approvals/DigitalDocumentSubmissionsPanel.vue';
</script>

<template>
    <Head title="Duyệt đồ án, luận văn — Admin" />
    <AdminLayout
        title="Duyệt đồ án, luận văn"
        :breadcrumbs="[
            { label: 'Đồ án, luận văn', href: 'admin.books.digital' },
            { label: 'Duyệt đồ án, luận văn' },
        ]"
    >
        <DigitalDocumentSubmissionsPanel />
    </AdminLayout>
</template>
