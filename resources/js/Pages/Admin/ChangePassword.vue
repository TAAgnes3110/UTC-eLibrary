<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AccountChangePasswordContent from '@/Pages/Shared/AccountChangePasswordContent.vue'
</script>

<template>
    <AdminLayout
        title="Đổi mật khẩu"
        :breadcrumbs="[
            { label: 'Hệ thống' },
            { label: 'Hồ sơ cá nhân', href: 'admin.profile' },
            { label: 'Đổi mật khẩu' },
        ]"
    >
        <Head title="Đổi mật khẩu - Admin" />
        <AccountChangePasswordContent profile-route-name="admin.profile" />
    </AdminLayout>
</template>
