<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import LibraryCardRequestsPanel from '@/Components/Admin/Approvals/LibraryCardRequestsPanel.vue';

const props = defineProps({
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Duyệt yêu cầu thẻ — Admin" />
    <AdminLayout
        title="Thẻ thư viện"
        :breadcrumbs="[
            { label: 'Thẻ thư viện' },
            { label: 'Duyệt yêu cầu' },
        ]"
    >
        <LibraryCardRequestsPanel :faculties="faculties" :periods="periods" />
    </AdminLayout>
</template>
