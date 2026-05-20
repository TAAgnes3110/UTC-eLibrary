<script setup>
import { computed } from 'vue';
import { sanitizeHtml } from '@/utils/sanitizeHtml';

const props = defineProps({
    html: { type: String, default: '' },
    emptyText: { type: String, default: '—' },
    contentClass: { type: String, default: '' },
});

const rendered = computed(() => {
    const raw = String(props.html || '').trim();
    if (raw) {
        return sanitizeHtml(raw);
    }
    return `<p class="text-slate-500">${props.emptyText}</p>`;
});
</script>

<template>
    <div
        class="prose prose-slate max-w-none text-sm leading-7 dark:prose-invert
            prose-headings:font-bold prose-p:my-3 prose-img:max-w-full prose-img:rounded-md
            prose-a:text-blue-700 dark:prose-a:text-blue-300"
        :class="contentClass"
        v-html="rendered"
    />
</template>
