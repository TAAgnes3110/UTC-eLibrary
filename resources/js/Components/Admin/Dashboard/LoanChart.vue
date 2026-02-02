<script setup>
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Icon } from '@iconify/vue';
</script>

<template>
    <Card class="border-none shadow-sm dark:bg-slate-900 transition-colors">
        <CardHeader class="pb-4">
            <CardTitle class="text-lg font-bold dark:text-white">Thống kê Mượn/Trả (2026)</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="h-[320px] flex items-center justify-center text-slate-400 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                <div class="text-center">
                    <Icon icon="lucide:bar-chart-3" class="h-12 w-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" />
                    <p class="font-medium dark:text-slate-400">Biểu đồ mượn/trả sẽ hiển thị ở đây</p>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Tích hợp với Chart.js hoặc ApexCharts</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
