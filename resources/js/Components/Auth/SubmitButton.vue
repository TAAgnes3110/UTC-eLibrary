<script setup>
defineProps({
    loading: { type: Boolean, default: false },
    label: { type: String, default: 'Xác nhận' },
    loadingLabel: { type: String, default: 'Đang xử lý...' },
})
</script>

<template>
    <button
        type="submit"
        class="group relative w-full h-14 overflow-hidden rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_0_20px_rgba(59,130,246,0.5)] transition-all hover:bg-blue-500 hover:shadow-[0_0_30px_rgba(59,130,246,0.7)] active:scale-[0.98] disabled:opacity-50"
        :disabled="loading"
    >
        <div class="absolute inset-0 flex items-center justify-center transition-all duration-300 group-hover:translate-x-1">
            <span v-if="loading" class="flex items-center gap-2">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ loadingLabel }}
            </span>
            <span v-else class="flex items-center gap-2 uppercase tracking-widest leading-none">
                <slot>{{ label }}</slot>
            </span>
        </div>
    </button>
</template>
