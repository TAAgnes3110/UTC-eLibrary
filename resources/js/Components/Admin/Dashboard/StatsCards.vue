<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    stats: {
        type: Array,
        required: true,
        default: () => []
    }
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
            v-for="stat in stats"
            :key="stat.title"
            class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md transition-all group"
        >
            <div :class="[stat.bg, 'dark:bg-slate-800 w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-colors']">
                <Icon :icon="stat.icon" :class="[stat.color, 'w-6 h-6']" />
            </div>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white">{{ stat.value }}</h3>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">{{ stat.title }}</p>
        </div>
    </div>
</template>
