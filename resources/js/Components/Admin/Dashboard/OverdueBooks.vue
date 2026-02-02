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
            <CardTitle class="text-lg font-bold text-rose-600 dark:text-rose-400">
                <Icon icon="lucide:alert-circle" class="w-5 h-5 inline mr-2" />
                Sách quá hạn
            </CardTitle>
            <Button variant="ghost" size="sm" class="text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 px-4">Xem tất cả</Button>
        </CardHeader>
        <CardContent>
            <div class="space-y-3">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="flex items-center justify-between p-3 bg-rose-50 dark:bg-rose-950/20 rounded-xl transition-colors"
                >
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white">Nguyễn Văn {{ String.fromCharCode(64 + i) }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Lập trình Java - Quá {{ i * 2 }} ngày</p>
                    </div>
                    <Button size="sm" variant="outline" class="text-rose-600 border-rose-200 dark:border-rose-900/50 hover:bg-rose-100 dark:hover:bg-rose-900/30">
                        Nhắc nhở
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
