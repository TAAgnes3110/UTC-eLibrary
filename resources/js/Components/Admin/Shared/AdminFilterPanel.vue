<script setup>
import { ref } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { Icon } from '@iconify/vue';

const props = defineProps({
    options: { type: Array, default: () => [] },
    modelValue: { type: Object, default: () => ({}) },
    filterGroup: { type: Object, default: null },
    filterValue: { type: Object, default: () => ({}) },
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'update:filterValue', 'update:show']);

const panelRef = ref(null);
onClickOutside(panelRef, () => emit('update:show', false));

const toggle = (key) => {
    const next = { ...props.modelValue, [key]: !props.modelValue[key] };
    emit('update:modelValue', next);
};

const toggleFilter = (key) => {
    const next = { ...props.filterValue, [key]: !props.filterValue[key] };
    emit('update:filterValue', next);
};
</script>

<template>
    <div ref="panelRef" class="relative flex items-center">
        <button
            type="button"
            class="flex items-center gap-1.5 h-9 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700"
            :class="{ 'bg-slate-100 dark:bg-slate-800 ring-1 ring-blue-500/30': show }"
            @click="$emit('update:show', !show)"
        >
            <Icon icon="lucide:filter" class="w-3.5 h-3.5" />
            Lọc
        </button>
        <div
            v-show="show"
            class="absolute top-full left-0 mt-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg p-3 z-30 flex gap-6"
        >
            <div class="min-w-[160px]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Tìm theo các cột</p>
                <div class="space-y-1.5">
                    <label
                        v-for="opt in options"
                        :key="opt.key"
                        class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded px-2 py-1 -mx-2"
                    >
                        <input
                            type="checkbox"
                            :checked="modelValue[opt.key]"
                            class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500/30"
                            @change="toggle(opt.key)"
                        />
                        {{ opt.label }}
                    </label>
                </div>
            </div>
            <template v-if="filterGroup && filterGroup.options?.length">
                <div class="min-w-[160px] border-l border-slate-200 dark:border-slate-700 pl-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">{{ filterGroup.title }}</p>
                    <div class="space-y-1.5">
                        <label
                            v-for="opt in filterGroup.options"
                            :key="opt.key"
                            class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded px-2 py-1 -mx-2"
                        >
                            <input
                                type="checkbox"
                                :checked="filterValue[opt.key]"
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500/30"
                                @change="toggleFilter(opt.key)"
                            />
                            {{ opt.label }}
                        </label>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
