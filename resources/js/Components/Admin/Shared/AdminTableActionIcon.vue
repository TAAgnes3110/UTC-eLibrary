<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'

const props = defineProps({
    icon: { type: String, required: true },
    title: { type: String, default: '' },
    href: { type: String, default: '' },
    tone: { type: String, default: 'blue' },
    disabled: { type: Boolean, default: false },
    spin: { type: Boolean, default: false },
    iconClass: { type: String, default: 'w-3.5 h-3.5' },
})

const emit = defineEmits(['click'])

const toneClass = computed(() => {
    if (props.tone === 'rose') return 'text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20'
    if (props.tone === 'emerald') return 'text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'
    if (props.tone === 'slate') return 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
    return 'text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20'
})

const baseClass = computed(
    () =>
        `inline-flex items-center justify-center rounded-lg p-1.5 transition-colors ${toneClass.value} ${
            props.disabled ? 'pointer-events-none opacity-50' : ''
        }`
)
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :class="baseClass"
        :title="title"
    >
        <Icon :icon="icon" :class="[iconClass, spin ? 'animate-spin' : '']" />
    </Link>
    <button
        v-else
        type="button"
        :class="baseClass"
        :title="title"
        :disabled="disabled"
        @click="emit('click')"
    >
        <Icon :icon="icon" :class="[iconClass, spin ? 'animate-spin' : '']" />
    </button>
</template>
