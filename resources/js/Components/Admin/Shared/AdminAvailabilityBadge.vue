<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
    available: { type: Boolean, default: false },
    availableLabel: { type: String, default: 'Còn' },
    unavailableLabel: { type: String, default: 'Hết' },
})

const badgeClass = computed(() =>
    props.available
        ? 'bg-emerald-500 dark:bg-emerald-600'
        : 'bg-rose-500 dark:bg-rose-600'
)
</script>

<template>
    <span
        :class="[
            'inline-flex items-center justify-end gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase text-white',
            badgeClass,
        ]"
    >
        <Icon :icon="available ? 'lucide:check-circle' : 'lucide:x-circle'" class="h-3 w-3" />
        {{ available ? availableLabel : unavailableLabel }}
    </span>
</template>
