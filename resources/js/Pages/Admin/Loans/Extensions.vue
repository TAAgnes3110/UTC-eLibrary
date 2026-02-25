<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const breadcrumbs = [
    { label: 'Quản lý mượn, trả' },
    { label: 'Gia hạn sách' },
];
</script>

<template>
    <AdminLayout title="Gia hạn mượn sách" :breadcrumbs="breadcrumbs">
        <Head title="Gia hạn" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Yêu cầu gia hạn</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Xử lý các yêu cầu kéo dài thời gian mượn từ độc giả.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-12 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 mb-6">
                    <Icon icon="lucide:calendar-clock" class="w-10 h-10" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Không có yêu cầu chờ</h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-sm">Hiện tại không có yêu cầu gia hạn sách nào cần xử lý.</p>
            </div>
        </div>
    </AdminLayout>
</template>
