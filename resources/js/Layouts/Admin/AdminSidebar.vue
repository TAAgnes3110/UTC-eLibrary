<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { adminNavigation } from '@/config/adminNavigation';

const props = defineProps({
    sidebarOpen: { type: Boolean, required: true },
    collapsed: { type: Boolean, required: true },
});

const isActive = (pattern) => {
    if (!pattern) return false;
    try {
        if (Array.isArray(pattern)) {
            return pattern.some((p) => route().current(p));
        }
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

// Mục có children: mở rộng khi đang ở một trong các child; ấn vào mặc định sang Bạn đọc
const expandedKey = ref(null);
const toggleExpanded = (key, item) => {
    const isExpanded = expandedKey.value === key;
    expandedKey.value = isExpanded ? null : key;
    // Khi ấn vào lần đầu (mở ra), nếu chưa ở trang con thì mặc định chuyển sang mục con đầu tiên
    if (!isExpanded && item?.children?.length && hasRoute(item.children[0].href)) {
        if (!isActive(item.active)) {
            const first = item.children[0];
            const url = first.query ? route(first.href, first.query) : route(first.href);
            router.visit(url);
        }
    }
};

const isParentActive = (item) => {
    if (!item.children) return false;
    return item.active && isActive(item.active);
};

const isChildActive = (child) => {
    if (!isActive(child.active)) return false;
    if (!child.query || (Object.keys(child.query).length === 0)) return true;
    const url = page.url || '';
    const params = new URLSearchParams(url.split('?')[1] || '');
    for (const [key, value] of Object.entries(child.query)) {
        const urlVal = params.get(key);
        const defaultVal = key === 'tab' && route().current('admin.library.slips') ? 'import' : (key === 'tab' ? 'category' : '');
        if ((urlVal || defaultVal) !== (value ?? '')) return false;
    }
    return true;
};

// Tự mở "Quản lý người dùng" khi vào Bạn đọc hoặc Tài khoản
const page = usePage();
watch(() => page.url, () => {
    const idx = adminNavigation.findIndex((i) => i.children && isActive(i.active));
    if (idx !== -1) expandedKey.value = idx;
    else if (expandedKey.value !== null && !adminNavigation[expandedKey.value]?.children) expandedKey.value = null;
}, { immediate: true });
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
            v-if="hasRoute('admin.dashboard')"
            :href="route('admin.dashboard')"
            :class="[
                'h-14 flex items-center border-b border-gray-100 dark:border-slate-800 shrink-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-all',
                collapsed ? 'lg:justify-center lg:px-0 px-4 gap-2.5' : 'px-4 gap-2.5'
            ]"
            title="Về trang chủ"
        >
            <div class="w-8 h-8 rounded overflow-hidden flex-shrink-0 bg-gray-50 dark:bg-slate-800 flex items-center justify-center p-0.5">
                <img src="/Image/logoUTC.png" alt="UTC" class="w-full h-full object-contain" />
            </div>
            <div v-show="sidebarOpen" class="flex flex-col min-w-0">
                <h1 class="font-bold text-slate-800 dark:text-white text-sm leading-tight tracking-tight truncate">UTC eLibrary</h1>
                <span class="text-[9px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Quản trị</span>
            </div>
        </Link>
        <div
            v-else
            :class="['h-14 flex items-center border-b border-gray-100 dark:border-slate-800 shrink-0', collapsed ? 'lg:justify-center lg:px-0 px-4 gap-2.5' : 'px-4 gap-2.5']"
        >
            <div class="w-8 h-8 rounded overflow-hidden flex-shrink-0 bg-gray-50 dark:bg-slate-800 flex items-center justify-center p-0.5">
                <img src="/Image/logoUTC.png" alt="UTC" class="w-full h-full object-contain" />
            </div>
            <div v-show="sidebarOpen" class="flex flex-col min-w-0">
                <h1 class="font-bold text-slate-800 dark:text-white text-sm leading-tight tracking-tight truncate">UTC eLibrary</h1>
                <span class="text-[9px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Quản trị</span>
            </div>
        </div>

        <!-- Nav -->
        <nav :class="['flex-1 overflow-y-auto py-2 space-y-0.5 sidebar-nav', collapsed ? 'lg:px-1.5 px-2' : 'px-2']">
            <template v-for="(item, index) in adminNavigation" :key="index">
                <!-- Mục có children: ấn sổ ra Bạn đọc, Tài khoản -->
                <div v-if="item.children && item.children.length" class="space-y-0.5">
                    <button
                        type="button"
                        :class="[
                            'nav-sidebar-link w-full text-left',
                            collapsed ? 'lg:justify-center lg:px-0 lg:py-2 px-2.5 py-[7px]' : 'px-2.5 py-[7px]',
                            isParentActive(item) ? 'nav-sidebar-link-active' : 'nav-sidebar-link-inactive'
                        ]"
                        :title="item.name"
                        @click="toggleExpanded(index, item)"
                    >
                        <Icon :icon="item.icon" class="w-[18px] h-[18px] shrink-0" />
                        <span v-show="sidebarOpen" class="truncate text-[13px] flex-1">{{ item.name }}</span>
                    </button>
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1 max-h-0"
                        enter-to-class="opacity-100 translate-y-0 max-h-[200px]"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0 max-h-[200px]"
                        leave-to-class="opacity-0 -translate-y-1 max-h-0"
                    >
                        <div v-if="sidebarOpen && expandedKey === index" class="pl-2 space-y-0.5 overflow-hidden">
                            <Link
                                v-for="(child, cIdx) in item.children"
                                :key="cIdx"
                                v-show="hasRoute(child.href)"
                                :href="child.query ? route(child.href, child.query) : route(child.href)"
                                :class="[
                                    'nav-sidebar-link flex items-center gap-2.5 rounded-lg px-2.5 py-[6px] text-[12px] transition-colors',
                                    isChildActive(child) ? 'nav-sidebar-link-active' : 'nav-sidebar-link-inactive'
                                ]"
                                :title="child.name"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70 shrink-0" />
                                <span class="truncate">{{ child.name }}</span>
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- Mục thường (không có children) -->
                <Link
                    v-else-if="hasRoute(item.href)"
                    :href="route(item.href)"
                    :class="[
                        'nav-sidebar-link',
                        collapsed ? 'lg:justify-center lg:px-0 lg:py-2 px-2.5 py-[7px]' : 'px-2.5 py-[7px]',
                        isActive(item.active) ? 'nav-sidebar-link-active' : 'nav-sidebar-link-inactive'
                    ]"
                    :title="item.name"
                >
                    <Icon :icon="item.icon" class="w-[18px] h-[18px] shrink-0" />
                    <span v-show="sidebarOpen" class="truncate text-[13px]">{{ item.name }}</span>
                    <Icon v-if="item.external" v-show="sidebarOpen" icon="lucide:external-link" class="w-3 h-3 ml-auto text-gray-400 shrink-0" />
                </Link>
            </template>
        </nav>
    </aside>
</template>

<style scoped>
.sidebar-nav {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.2) transparent;
}
.sidebar-nav::-webkit-scrollbar { width: 3px; }
.sidebar-nav::-webkit-scrollbar-track { background: transparent; }
.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.2);
    border-radius: 3px;
}
.sidebar-nav::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.4); }
</style>
