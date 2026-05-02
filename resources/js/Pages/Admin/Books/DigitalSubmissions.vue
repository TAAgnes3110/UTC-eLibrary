<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DigitalDocumentSubmissionsPanel from '@/Components/Admin/Approvals/DigitalDocumentSubmissionsPanel.vue';
</script>

<template>
    <Head title="Duyệt tài liệu số — Admin" />
    <AdminLayout
        title="Duyệt tài liệu số"
        :breadcrumbs="[
            { label: 'Tài liệu số', href: 'admin.books.digital' },
            { label: 'Duyệt tài liệu số' },
        ]"
    >
        <DigitalDocumentSubmissionsPanel />
    </AdminLayout>
</template>
