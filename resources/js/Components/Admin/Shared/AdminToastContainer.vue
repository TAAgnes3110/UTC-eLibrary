<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { toastState, remove } from '@/store/toast';

const items = computed(() => toastState.items);

const typeMeta = (type) => {
    switch (type) {
    case 'success':
        return { icon: 'lucide:check-circle', ring: 'ring-emerald-400/20', bg: 'bg-emerald-600', text: 'text-emerald-50' };
    case 'error':
        return { icon: 'lucide:alert-triangle', ring: 'ring-rose-400/20', bg: 'bg-rose-600', text: 'text-rose-50' };
    case 'warn':
        return { icon: 'lucide:alert-circle', ring: 'ring-amber-400/20', bg: 'bg-amber-600', text: 'text-amber-50' };
    default:
        return { icon: 'lucide:info', ring: 'ring-sky-400/20', bg: 'bg-sky-600', text: 'text-sky-50' };
    }
};
</script>

<template>
    <div class="fixed top-4 right-4 z-[200] space-y-2 w-[min(360px,calc(100vw-2rem))]">
        <div
            v-for="t in items"
            :key="t.id"
            class="group cursor-pointer rounded-xl shadow-lg ring-1 backdrop-blur border border-white/10 dark:border-slate-800/50 overflow-hidden"
            :class="[typeMeta(t.type).ring]"
            role="button"
            tabindex="0"
            @click="remove(t.id)"
        >
            <div class="flex items-start gap-3 p-3 bg-slate-900/90">
                <div class="mt-0.5 shrink-0">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                        :class="[typeMeta(t.type).bg, typeMeta(t.type).text]"
                    >
                        <Icon :icon="typeMeta(t.type).icon" class="w-4 h-4" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p v-if="t.title" class="text-sm font-semibold text-white truncate">{{ t.title }}</p>
                    <p class="text-sm text-slate-100 leading-snug whitespace-pre-line break-words">
                        {{ t.message }}
                    </p>
                </div>
                <button
                    type="button"
                    class="shrink-0 p-1 rounded-md text-slate-300 hover:text-white hover:bg-white/10 opacity-80 group-hover:opacity-100 transition"
                    @click.stop="remove(t.id)"
                    title="Đóng"
                >
                    <Icon icon="lucide:x" class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>

