<script setup>
import { computed } from 'vue';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: null,
            to: null,
            links: [],
        }),
    },
});

const pages = computed(() => {
    const p = [];
    const current = props.pagination.current_page;
    const last = props.pagination.last_page;
    const delta = 2;

    for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
        p.push(i);
    }
    return p;
});

const goToPage = (page) => {
    if (page >= 1 && page <= props.pagination.last_page && page !== props.pagination.current_page) {
        router.get(window.location.pathname, { page }, { preserveState: true, preserveScroll: true });
    }
};
</script>

<template>
    <div v-if="pagination.last_page > 1" class="flex items-center justify-between pt-2">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Hiển thị {{ pagination.from || 0 }} – {{ pagination.to || 0 }} / {{ pagination.total }} kết quả
        </p>
        <div class="flex gap-1">
            <Button
                variant="outline"
                size="icon"
                class="w-8 h-8 rounded-lg"
                :disabled="pagination.current_page === 1"
                @click="goToPage(pagination.current_page - 1)"
            >
                <Icon icon="lucide:chevron-left" class="w-4 h-4" />
            </Button>
            <Button
                v-for="p in pages"
                :key="p"
                :variant="p === pagination.current_page ? 'default' : 'outline'"
                size="icon"
                :class="[
                    'w-8 h-8 rounded-lg text-xs font-semibold',
                    p === pagination.current_page ? 'bg-blue-600 text-white' : 'dark:text-slate-400'
                ]"
                @click="goToPage(p)"
            >
                {{ p }}
            </Button>
            <Button
                variant="outline"
                size="icon"
                class="w-8 h-8 rounded-lg"
                :disabled="pagination.current_page === pagination.last_page"
                @click="goToPage(pagination.current_page + 1)"
            >
                <Icon icon="lucide:chevron-right" class="w-4 h-4" />
            </Button>
        </div>
    </div>
</template>
