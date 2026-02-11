<script setup>
import { CheckCircle2 } from 'lucide-vue-next'

defineProps({
    message: { type: String, default: '' },
})
</script>

<template>
    <div v-if="message" class="mb-5 animate-in fade-in zoom-in-95 duration-500">
        <div class="relative overflow-hidden rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-3.5 transition-all hover:bg-emerald-500/15">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-500/10 blur-2xl rounded-full"></div>
            <div class="relative flex items-center gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <CheckCircle2 :size="18" class="animate-pulse" />
                </div>
                <div class="flex-1">
                    <p class="text-[11px] font-black text-emerald-400/80 tracking-widest leading-tight">Thành công</p>
                    <p class="text-[12px] text-emerald-200/60 font-medium leading-relaxed mt-0.5">{{ message }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
