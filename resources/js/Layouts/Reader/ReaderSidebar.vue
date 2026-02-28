<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { readerNavigation } from '@/config/readerNavigation';

defineProps({
    sidebarOpen: { type: Boolean, required: true },
    collapsed: { type: Boolean, required: true },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isStaff = computed(() => !!page.props.auth?.is_staff);

const isActive = (pattern) => {
    if (!pattern) return false;
    try {
        if (Array.isArray(pattern)) return pattern.some((p) => route().current(p));
        return route().current(pattern);
    } catch {
        return false;
    }
};

const hasRoute = (routeName) => {
    try {
        route(routeName);
        return true;
    } catch {
        return false;
    }
};

const showItem = (item) => {
    if (!item.auth) return hasRoute(item.href);
    return user && hasRoute(item.href);
};
</script>

<template>
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-50 bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-800 flex flex-col transition-all duration-300 ease-in-out shadow-sm',
            sidebarOpen ? 'translate-x-0 w-60' : '-translate-x-full lg:translate-x-0 lg:w-[56px]'
        ]"
    >
        <!-- Logo -->
        <Link
            :href="route('library.dashboard')"
            :class="[
                'h-14 flex items-center border-b border-gray-100 dark:border-slate-800 shrink-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-all',
                collapsed ? 'lg:justify-center lg:px-0 px-4 gap-2.5' : 'px-4 gap-2.5'
            ]"
            title="Thư viện số"
        >
            <div class="w-8 h-8 rounded overflow-hidden flex-shrink-0 bg-gray-50 dark:bg-slate-800 flex items-center justify-center p-0.5">
                <img src="/Image/logoUTC.png" alt="UTC" class="w-full h-full object-contain" />
            </div>
            <div v-show="sidebarOpen" class="flex flex-col min-w-0">
                <h1 class="font-bold text-slate-800 dark:text-white text-sm leading-tight tracking-tight truncate">UTC eLibrary</h1>
                <span class="text-[9px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Thư viện số</span>
            </div>
        </Link>

        <!-- Nav -->
        <nav :class="['flex-1 overflow-y-auto py-2 space-y-0.5', collapsed ? 'lg:px-1.5 px-2' : 'px-2']">
            <Link
                v-for="(item, index) in readerNavigation.filter(showItem)"
                :key="index"
                :href="route(item.href)"
                :class="[
                    'nav-sidebar-link flex items-center gap-2.5 rounded-lg px-2.5 py-[7px] text-[13px] transition-colors',
                    collapsed ? 'lg:justify-center lg:px-0 lg:py-2' : '',
                    isActive(item.active) ? 'nav-sidebar-link-active' : 'nav-sidebar-link-inactive'
                ]"
                :title="item.name"
            >
                <Icon :icon="item.icon" class="w-[18px] h-[18px] shrink-0" />
                <span v-show="sidebarOpen" class="truncate flex-1">{{ item.name }}</span>
            </Link>
        </nav>
    </aside>
</template>

<style scoped>
.nav-sidebar-link-active {
    @apply bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-medium;
}
.nav-sidebar-link-inactive {
    @apply text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-900 dark:hover:text-white;
}
</style>
