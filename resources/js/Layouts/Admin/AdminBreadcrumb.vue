<script setup>
import { Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

defineProps({
    breadcrumbs: { type: Array, default: () => [] },
});

const hasRoute = (routeName) => {
    try {
        route(routeName);
        return true;
    } catch {
        return false;
    }
};
</script>

<template>
    <div
        v-if="breadcrumbs.length"
        class="px-4 lg:px-6 py-1.5 text-[11px] text-gray-500 dark:text-slate-400 bg-gray-50/80 dark:bg-slate-900/80 border-b border-gray-100 dark:border-slate-800"
    >
        <div class="flex items-center gap-1 flex-wrap">
            <Link :href="route('admin.dashboard')" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Trang chủ</Link>
            <template v-for="(crumb, i) in breadcrumbs" :key="i">
                <Icon icon="lucide:chevron-right" class="w-3 h-3 text-gray-400" />
                <Link v-if="crumb.href && hasRoute(crumb.href)" :href="route(crumb.href)" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ crumb.label }}</Link>
                <span v-else class="text-gray-700 dark:text-white font-medium">{{ crumb.label }}</span>
            </template>
        </div>
    </div>
</template>
