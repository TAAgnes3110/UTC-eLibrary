<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const breadcrumbs = [
    { label: 'Quản lý mượn, trả' },
    { label: 'Đọc tại chỗ' },
];
</script>

<template>
    <AdminLayout title="Quản lý đọc tại chỗ" :breadcrumbs="breadcrumbs">
        <Head title="Đọc tại chỗ" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Lượt đọc trong ngày</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Theo dõi độc giả mượn sách đọc tại không gian thư viện.</p>
                </div>
                <Button class="h-10 rounded-xl px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg shadow-indigo-500/20">
                    <Icon icon="lucide:plus" class="w-4 h-4 mr-2" /> Ghi nhận lượt đọc
                </Button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-12 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-6">
                    <Icon icon="lucide:book-open" class="w-10 h-10" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Chưa có lượt đọc nào</h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-sm">Ghi nhận các lượt đọc tại chỗ để thống kê lưu lượng sử dụng thư viện.</p>
            </div>
        </div>
    </AdminLayout>
</template>
