<script setup>
import { Head } from '@inertiajs/vue3'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AccountChangePasswordContent from '@/Pages/Shared/AccountChangePasswordContent.vue'
</script>

<template>
    <ReaderLayout>
        <Head title="Đổi mật khẩu - Thư viện số UTC" />
        <AccountChangePasswordContent profile-route-name="reader.profile" />
    </ReaderLayout>
</template>
