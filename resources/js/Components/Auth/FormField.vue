<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
})
</script>

<template>
    <div class="space-y-2 group">
        <label :for="id" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div
            class="relative rounded-xl transition-all duration-300"
            :class="
                error
                    ? 'ring-2 ring-red-500/40'
                    : 'group-focus-within:ring-2 ring-blue-500/20'
            "
        >
            <!-- Icon slot -->
            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400">
                <slot name="icon" />
            </div>
            <!-- Input slot -->
            <slot />
        </div>
        <p v-if="error" class="text-xs text-red-500 font-medium pl-1 mt-0.5">{{ error }}</p>
    </div>
</template>
