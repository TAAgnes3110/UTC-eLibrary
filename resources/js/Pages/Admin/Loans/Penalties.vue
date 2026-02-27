<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const breadcrumbs = [
    { label: 'Mượn – Trả sách' },
    { label: 'Quản lý Trả muộn & Phạt' },
];
</script>

<template>
    <AdminLayout title="Quản lý Trả muộn & Phạt" :breadcrumbs="breadcrumbs">
        <Head title="Quản lý Trả muộn & Phạt - Admin" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Quản lý Trả muộn & Phạt</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Xử lý các trường hợp trả muộn, làm hỏng hoặc mất sách.</p>
                </div>
                <Button class="h-10 rounded-xl px-4 bg-orange-600 hover:bg-orange-700 text-white font-bold shadow-lg shadow-orange-500/20">
                    <Icon icon="lucide:alert-circle" class="w-4 h-4 mr-2" /> Ghi nhận vi phạm
                </Button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-12 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 rounded-2xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-600 dark:text-orange-400 mb-6">
                    <Icon icon="lucide:shield-alert" class="w-10 h-10" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Chưa có vi phạm nào</h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-sm">Tất cả độc giả đều đang tuân thủ tốt nội quy thư viện.</p>
            </div>
        </div>
    </AdminLayout>
</template>
