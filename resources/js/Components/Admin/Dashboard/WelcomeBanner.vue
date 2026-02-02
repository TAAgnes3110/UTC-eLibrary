<script setup>
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

defineProps({
    quickActions: {
        type: Array,
        required: true,
        default: () => []
    }
});
</script>

<template>
    <div class="bg-blue-900 dark:bg-blue-950 rounded-3xl p-6 lg:p-10 text-white relative overflow-hidden transition-all">
        <div class="relative z-10 max-w-2xl">
            <h2 class="text-2xl lg:text-3xl font-black mb-4">Chào mừng quay trở lại!</h2>
            <p class="text-blue-200 dark:text-blue-300 text-base lg:text-lg mb-8">
                Hôm nay có <strong class="text-white">12</strong> phiếu mượn cần xử lý và
                <strong class="text-white">5</strong> sách quá hạn cần nhắc nhở.
            </p>
            <div class="flex flex-wrap gap-4">
                <Button
                    v-for="action in quickActions"
                    :key="action.label"
                    class="px-4 lg:px-6 py-2 lg:py-3 bg-white text-blue-900 rounded-xl font-bold hover:bg-blue-50 transition-all hover:scale-105 active:scale-95 shadow-lg border-none"
                    @click="$emit('action-click', action)"
                >
                    <Icon :icon="action.icon" class="w-4 h-4 mr-2" />
                    {{ action.label }}
                </Button>
            </div>
        </div>
        <Icon icon="lucide:library" class="absolute -right-12 -bottom-12 w-64 h-64 opacity-10" />
    </div>
</template>
