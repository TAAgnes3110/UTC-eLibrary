<script setup>
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

defineProps({
    books: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <Card class="border-none shadow-sm dark:bg-slate-900 transition-colors">
        <CardHeader class="pb-4 flex flex-row items-center justify-between">
            <CardTitle class="text-lg font-bold dark:text-white">
                <Icon icon="lucide:trending-up" class="w-5 h-5 inline mr-2 text-emerald-600 dark:text-emerald-400" />
                Sách được mượn nhiều
            </CardTitle>
            <Button variant="ghost" size="sm" class="dark:text-slate-300 dark:hover:bg-slate-800 px-4">Xem tất cả</Button>
        </CardHeader>
        <CardContent>
            <div class="space-y-3">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="flex items-center gap-4 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl transition-colors"
                >
                    <div class="w-12 h-16 bg-slate-200 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                        <Icon icon="lucide:book" class="w-6 h-6 text-slate-400 dark:text-slate-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900 dark:text-white truncate">Giáo trình {{ ['CTDL', 'OOP Java', 'Giải tích', 'Mạng MT'][i-1] }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ 50 - i * 5 }} lượt mượn tháng này</p>
                    </div>
                    <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">#{{ i }}</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
