<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    title: { type: String, required: true },
    open: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

defineEmits(['toggle']);
</script>

<template>
    <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <button
            type="button"
            class="w-full flex items-center justify-between gap-3 px-4 py-3.5 min-h-[52px] text-left bg-slate-50/90 dark:bg-slate-800/50 hover:bg-slate-100/90 dark:hover:bg-slate-800 transition-colors"
            :disabled="disabled"
            @click="$emit('toggle')"
        >
            <span class="font-bold text-slate-900 dark:text-white text-[15px]">{{ title }}</span>
            <Icon
                :icon="open ? 'lucide:chevron-up' : 'lucide:chevron-down'"
                class="w-5 h-5 text-slate-500 dark:text-slate-400 shrink-0"
            />
        </button>
        <div v-show="open" class="border-t border-slate-100 dark:border-slate-800 px-4 py-4 bg-white dark:bg-slate-900">
            <slot />
        </div>
    </div>
</template>
