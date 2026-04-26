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
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-6">
        <div
            v-for="stat in stats"
            :key="stat.title"
            :class="[
                'bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border hover:shadow-md transition-all group',
                stat.emphasis
                    ? 'border-red-300/70 dark:border-red-700/60 ring-1 ring-red-200/60 dark:ring-red-800/40'
                    : 'border-slate-100 dark:border-slate-800',
            ]"
        >
            <div :class="[stat.bg, 'dark:bg-slate-800 w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-colors']">
                <Icon :icon="stat.icon" :class="[stat.color, 'w-6 h-6']" />
            </div>
            <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ stat.value }}</h3>
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">{{ stat.title }}</p>
            <p
                v-if="stat.note"
                :class="[
                    'mt-2 text-[11px] font-medium',
                    stat.emphasis ? 'text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400',
                ]"
            >
                {{ stat.note }}
            </p>
        </div>
    </div>
</template>
