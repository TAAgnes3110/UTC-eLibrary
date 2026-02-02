<script setup>
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';

defineProps({
    activities: {
        type: Array,
        required: true,
        default: () => []
    }
});
</script>

<template>
    <Card class="border-none shadow-sm dark:bg-slate-900 transition-colors">
        <CardHeader class="pb-4">
            <CardTitle class="text-lg font-bold dark:text-white">Hoạt động gần đây</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-6">
                <div
                    v-for="(activity, index) in activities"
                    :key="index"
                    class="flex items-start gap-4"
                >
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm shrink-0">
                        {{ activity.user.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ activity.user }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate">{{ activity.action }}</p>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500 shrink-0">{{ activity.time }}</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
