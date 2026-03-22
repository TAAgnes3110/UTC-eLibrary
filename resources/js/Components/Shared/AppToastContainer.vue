<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { toastState, remove } from '@/store/toast';

const items = computed(() => toastState.items);

const typeMeta = (type) => {
    switch (type) {
        case 'success':
            return {
                icon: 'lucide:check-circle',
                bar: 'from-emerald-500 to-teal-600',
                iconBg: 'bg-emerald-500/20 text-emerald-200',
                border: 'border-emerald-500/25',
                glow: 'shadow-emerald-500/10',
            };
        case 'error':
            return {
                icon: 'lucide:alert-circle',
                bar: 'from-rose-500 to-red-600',
                iconBg: 'bg-rose-500/20 text-rose-100',
                border: 'border-rose-500/30',
                glow: 'shadow-rose-500/15',
            };
        case 'warn':
            return {
                icon: 'lucide:alert-triangle',
                bar: 'from-amber-500 to-orange-600',
                iconBg: 'bg-amber-500/20 text-amber-100',
                border: 'border-amber-500/25',
                glow: 'shadow-amber-500/10',
            };
        default:
            return {
                icon: 'lucide:info',
                bar: 'from-sky-500 to-indigo-600',
                iconBg: 'bg-sky-500/20 text-sky-100',
                border: 'border-sky-500/25',
                glow: 'shadow-sky-500/10',
            };
    }
};
</script>

<template>
    <div
        class="fixed top-4 right-4 z-[200] flex flex-col gap-3 w-[min(380px,calc(100vw-1.5rem))] pointer-events-none"
        aria-live="polite"
    >
        <TransitionGroup name="toast-slide">
            <div
                v-for="t in items"
                :key="t.id"
                class="pointer-events-auto rounded-2xl overflow-hidden shadow-2xl ring-1 backdrop-blur-xl transition-all duration-300 hover:scale-[1.01]"
                :class="[typeMeta(t.type).border, typeMeta(t.type).glow, 'ring-white/10']"
                role="status"
            >
                <div
                    class="h-1 w-full bg-gradient-to-r opacity-90"
                    :class="typeMeta(t.type).bar"
                />
                <div
                    class="flex items-center gap-2.5 px-3 py-2.5 bg-slate-950/85 dark:bg-slate-950/90 border-t border-white/5"
                >
                    <div
                        class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                        :class="typeMeta(t.type).iconBg"
                    >
                        <Icon :icon="typeMeta(t.type).icon" class="w-4 h-4 shrink-0 block" />
                    </div>
                    <div class="min-w-0 flex-1 flex flex-col justify-center gap-0.5">
                        <p
                            v-if="t.title"
                            class="text-[13px] font-semibold text-white tracking-tight leading-tight"
                        >
                            {{ t.title }}
                        </p>
                        <p
                            class="text-[13px] text-slate-200/95 leading-tight whitespace-pre-line break-words"
                        >
                            {{ t.message }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 -mr-0.5 p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors inline-flex items-center justify-center"
                        :aria-label="'Đóng'"
                        @click="remove(t.id)"
                    >
                        <Icon icon="lucide:x" class="w-4 h-4 block" />
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-slide-enter-active,
.toast-slide-leave-active {
    transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
.toast-slide-enter-from {
    opacity: 0;
    transform: translateX(120%) scale(0.96);
}
.toast-slide-leave-to {
    opacity: 0;
    transform: translateX(40%) scale(0.98);
}
.toast-slide-move {
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
</style>
