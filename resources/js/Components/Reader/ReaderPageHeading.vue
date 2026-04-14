<script setup>
/**
 * Reader page heading: title + optional description and actions.
 * Mirrors admin heading pattern for consistency.
 */
defineProps({
    title: { type: String, required: true },
})
</script>

<template>
    <div :class="$slots.description ? 'space-y-1' : undefined">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-xl font-black text-slate-900 dark:text-white sm:text-2xl">
                {{ title }}
            </h1>
            <div v-if="$slots.actions" class="flex items-center gap-2 flex-wrap justify-end">
                <slot name="actions" />
            </div>
        </div>
        <p v-if="$slots.description" class="text-sm text-slate-600 dark:text-slate-400">
            <slot name="description" />
        </p>
    </div>
</template>
