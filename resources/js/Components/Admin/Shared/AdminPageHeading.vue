<script setup>
/**
 * Standard list-page heading: title row + optional actions (right) and description (below).
 */
defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <div :class="$slots.description ? 'space-y-1' : undefined">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <h2 class="text-sm sm:text-base font-bold text-gray-800 dark:text-white leading-8">
                {{ title }}
            </h2>
            <div v-if="$slots.actions" class="flex items-center gap-2 flex-wrap justify-end">
                <slot name="actions" />
            </div>
        </div>
        <p v-if="$slots.description" class="text-xs text-slate-500 dark:text-slate-400">
            <slot name="description" />
        </p>
    </div>
</template>
