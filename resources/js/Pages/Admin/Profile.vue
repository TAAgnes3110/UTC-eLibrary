<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountProfileContent from '@/Pages/Shared/AccountProfileContent.vue'
</script>

<template>
    <AdminLayout
        title="Hồ sơ cá nhân"
        :breadcrumbs="[
            { label: 'Hệ thống' },
            { label: 'Hồ sơ cá nhân' },
        ]"
    >
        <Head title="Hồ sơ cá nhân - Admin" />
        <AccountProfileContent />
    </AdminLayout>
</template>
