<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: 'Hệ thống thư viện điện tử UTC' },
})
</script>

<template>
    <div class="mb-4 relative flex items-center min-h-[50px]">
        <!-- Icon on the Left -->
        <div class="absolute left-0 group">
            <div class="absolute -inset-4 bg-blue-500/20 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="relative w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center border border-white/10 text-blue-400 shadow-2xl ring-1 ring-white/5">
                <slot name="icon" />
            </div>
        </div>

        <!-- Centered Text -->
        <div class="flex-1 text-center pl-10 space-y-0.5">
            <h2 class="font-display text-2xl font-black text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-b from-white to-white/70 uppercase tracking-widest leading-normal">
                {{ title }}
            </h2>
            <div class="flex items-center justify-center gap-2">
                <div class="h-px w-6 bg-blue-500/20"></div>
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-[0.3em]">{{ subtitle }}</p>
                <div class="h-px w-6 bg-blue-500/20"></div>
            </div>
        </div>
    </div>
</template>
