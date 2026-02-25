<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const props = defineProps({
    title: { type: String, default: 'Dashboard' },
    breadcrumbs: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const sidebarOpen = ref(window.innerWidth >= 1024);
const collapsed = computed(() => !sidebarOpen.value);

router.on('navigate', () => {
    if (window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }
});

// =============================================
// FLAT TREE NAVIGATION (no grouping headers)
// =============================================
const navigation = [
    { name: 'Bảng điều khiển', href: 'admin.dashboard', icon: 'lucide:layout-grid', active: 'admin.dashboard' },

    { type: 'header', name: 'Dữ liệu Thư viện' },
    { name: 'Quản lý Sách', href: 'admin.books.index', icon: 'lucide:book', active: 'admin.books.*' },
    { name: 'Quản lý Tác giả', href: 'admin.authors.index', icon: 'lucide:user-pen', active: 'admin.authors.*' },
    { name: 'Quản lý Nhà xuất bản', href: 'admin.publishers.index', icon: 'lucide:building-2', active: 'admin.publishers.*' },
    { name: 'Quản lý Thể loại', href: 'admin.categories.index', icon: 'lucide:tags', active: 'admin.categories.*' },

    { type: 'header', name: 'Nghiệp vụ Kho' },
    { name: 'Nhập / Xuất sách', href: 'admin.library.slips', icon: 'lucide:file-text', active: 'admin.library.slips.*' },
    { name: 'Kiểm kê & Thanh lý', href: 'admin.library.inventory', icon: 'lucide:clipboard-check', active: 'admin.library.inventory.*' },

    { type: 'header', name: 'Mượn & Trả' },
    { name: 'Cho mượn & Trả sách', href: 'admin.loans.index', icon: 'lucide:clipboard-list', active: 'admin.loans.index' },
    { name: 'Sách trả muộn / Phạt', href: 'admin.loans.penalties', icon: 'lucide:alert-circle', active: 'admin.loans.penalties' },

    { type: 'header', name: 'Người dùng' },
    { name: 'Quản lý Bạn đọc', href: 'admin.readers.index', icon: 'lucide:users', active: 'admin.readers.*' },
    { name: 'Cấp thẻ thư viện', href: 'admin.cards.index', icon: 'lucide:contact', active: 'admin.cards.*' },

    { type: 'header', name: 'Hệ thống' },
    { name: 'Cài đặt & Báo cáo', icon: 'lucide:settings-2', children: [
        { name: 'Cấu hình quy định', href: 'admin.settings', icon: 'lucide:sliders' },
        { name: 'Tài khoản nhân viên', href: 'admin.users.index', icon: 'lucide:user-cog' },
        { name: 'Báo cáo thống kê', href: 'admin.stats.index', icon: 'lucide:trending-up' },
    ]},
];

// =============================================
// TREE STATE
// =============================================
const expandedGroups = ref({});

const isActive = (pattern) => {
    if (!pattern) return false;
    try {
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

const groupHasActiveChild = (group) => {
    if (!group.children) return false;
    return group.children.some(child => isActive(child.active));
};

const toggleGroup = (name) => {
    expandedGroups.value[name] = !expandedGroups.value[name];
};

const isExpanded = (name) => {
    return !!expandedGroups.value[name];
};

onMounted(() => {
    navigation.forEach(item => {
        if (item.children && groupHasActiveChild(item)) {
            expandedGroups.value[item.name] = true;
        }
    });
});

const logout = () => {
    router.post(route('logout'));
};

const searchQuery = ref('');
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.visit(route('admin.search', { q: searchQuery.value }));
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-slate-950 transition-colors duration-300">
        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden transition-opacity duration-300"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-800 flex flex-col transition-all duration-300 ease-in-out shadow-sm',
                sidebarOpen
                    ? 'translate-x-0 w-60'
                    : '-translate-x-full lg:translate-x-0 lg:w-[56px]'
            ]"
        >
            <!-- Logo -->
            <div :class="[
                'h-14 flex items-center border-b border-gray-100 dark:border-slate-800 shrink-0 transition-all duration-300',
                collapsed ? 'lg:justify-center lg:px-0 px-4 gap-2.5' : 'px-4 gap-2.5'
            ]">
                <div class="w-8 h-8 rounded overflow-hidden flex-shrink-0 bg-gray-50 dark:bg-slate-800 flex items-center justify-center p-0.5">
                    <img src="/Image/logoUTC.png" alt="UTC" class="w-full h-full object-contain" />
                </div>
                <div v-show="sidebarOpen" class="flex flex-col min-w-0">
                    <h1 class="font-bold text-slate-800 dark:text-white text-sm leading-tight tracking-tight truncate">UTC eLibrary</h1>
                    <span class="text-[9px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Quản trị</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav :class="[
                'flex-1 overflow-y-auto py-2 space-y-0.5 transition-all duration-300 sidebar-nav',
                collapsed ? 'lg:px-1.5 px-2' : 'px-2'
            ]">
                <template v-for="(item, index) in navigation" :key="index">
                    <!-- SECTION HEADER -->
                    <div v-if="item.type === 'header'"
                         v-show="sidebarOpen"
                         class="px-5 pt-5 pb-2 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[2px] transition-all duration-300"
                    >
                        {{ item.name }}
                    </div>

                    <!-- LEAF NODE (no children) -->
                    <template v-else-if="!item.children">
                        <Link
                            v-if="hasRoute(item.href)"
                            :href="route(item.href)"
                            :class="[
                                'nav-sidebar-link',
                                collapsed ? 'lg:justify-center lg:px-0 lg:py-2 px-2.5 py-[7px]' : 'px-2.5 py-[7px]',
                                isActive(item.active)
                                    ? 'nav-sidebar-link-active'
                                    : 'nav-sidebar-link-inactive'
                            ]"
                            :title="item.name"
                        >
                            <Icon :icon="item.icon" class="w-[18px] h-[18px] shrink-0" />
                            <span v-show="sidebarOpen" class="truncate text-[13px]">{{ item.name }}</span>
                            <Icon v-if="item.external" v-show="sidebarOpen" icon="lucide:external-link" class="w-3 h-3 ml-auto text-gray-400 shrink-0" />
                        </Link>
                    </template>

                    <!-- TREE NODE (has children) -->
                    <template v-else>
                        <div class="tree-group">
                            <button
                                @click="toggleGroup(item.name)"
                                :class="[
                                    'w-full nav-sidebar-link transition-all duration-150',
                                    collapsed ? 'lg:justify-center lg:px-0 lg:py-2 px-2.5 py-[7px]' : 'px-2.5 py-[7px]',
                                    groupHasActiveChild(item)
                                        ? 'nav-sidebar-link-active'
                                        : 'nav-sidebar-link-inactive'
                                ]"
                                :title="item.name"
                            >
                                <Icon :icon="item.icon" class="w-[18px] h-[18px] shrink-0" />
                                <span v-show="sidebarOpen" class="truncate text-[13px] flex-1 text-left">{{ item.name }}</span>
                                <Icon
                                    v-show="sidebarOpen"
                                    icon="lucide:chevron-down"
                                    :class="[
                                        'w-3.5 h-3.5 shrink-0 transition-transform duration-200 text-gray-400',
                                        isExpanded(item.name) ? 'rotate-180' : ''
                                    ]"
                                />
                            </button>

                            <!-- Children -->
                            <div
                                v-show="sidebarOpen && isExpanded(item.name)"
                                class="tree-children ml-4 pl-3 border-l border-gray-200 dark:border-slate-800 space-y-1 mt-1 mb-1"
                            >
                                <template v-for="(child, ci) in item.children" :key="ci">
                                    <Link
                                        v-if="hasRoute(child.href)"
                                        :href="route(child.href)"
                                        :class="[
                                            'nav-sidebar-link px-2.5 py-[7px]',
                                            isActive(child.active)
                                                ? 'nav-sidebar-link-active'
                                                : 'nav-sidebar-link-inactive'
                                        ]"
                                    >
                                        <Icon :icon="child.icon" class="w-[18px] h-[18px] shrink-0" />
                                        <span class="truncate">{{ child.name }}</span>
                                    </Link>
                                    <div
                                        v-else
                                        class="flex items-center gap-2 rounded-md px-2.5 py-[6px] text-gray-300 dark:text-slate-600 cursor-not-allowed text-[12.5px]"
                                        :title="child.name + ' (sắp ra mắt)'"
                                    >
                                        <Icon :icon="child.icon" class="w-[15px] h-[15px] shrink-0" />
                                        <span class="truncate">{{ child.name }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </template>
            </nav>
        </aside>

        <!-- Main Content -->
        <div :class="sidebarOpen ? 'lg:ml-60' : 'lg:ml-[56px]'" class="transition-all duration-300 min-h-screen flex flex-col">
            <!-- Top Bar -->
            <header class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-gray-200 dark:border-slate-800 px-4 lg:px-6 h-12 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="p-1.5 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors text-gray-500 dark:text-slate-400"
                    >
                        <Icon :icon="sidebarOpen ? 'lucide:panel-left-close' : 'lucide:panel-left-open'" class="w-[18px] h-[18px]" />
                    </button>
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-white truncate">{{ title }}</h2>
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- Search -->
                    <div class="relative hidden lg:block">
                        <Icon icon="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-3.5 h-3.5" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm..."
                            class="bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg pl-9 pr-3 py-1.5 text-xs focus:ring-1 focus:ring-blue-500/30 focus:border-blue-400 w-52 transition-all text-gray-700 dark:text-white dark:placeholder-slate-500"
                            @keyup.enter="handleSearch"
                        />
                    </div>

                    <ThemeToggle />

                    <!-- Notifications -->
                    <Button variant="ghost" size="icon" class="w-8 h-8 text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-lg">
                        <Icon icon="lucide:bell" class="h-4 w-4" />
                    </Button>

                    <!-- User Menu -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" class="h-8 w-8 rounded-full overflow-hidden bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 p-0">
                                <Icon icon="lucide:user" class="h-4 w-4 text-gray-600 dark:text-slate-300" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-52 dark:bg-slate-900 dark:border-slate-800">
                            <DropdownMenuLabel>
                                <div class="flex flex-col space-y-0.5">
                                    <p class="text-xs font-medium leading-none dark:text-white">{{ user?.name }}</p>
                                    <p class="text-[11px] text-muted-foreground dark:text-slate-400">{{ user?.email }}</p>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator class="dark:bg-slate-800" />
                            <DropdownMenuItem v-if="hasRoute('admin.profile')" @click="router.visit(route('admin.profile'))" class="cursor-pointer dark:hover:bg-slate-800 text-xs">
                                <Icon icon="lucide:user" class="mr-2 h-3.5 w-3.5" />
                                <span class="dark:text-slate-300">Hồ sơ cá nhân</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="hasRoute('admin.settings')" @click="router.visit(route('admin.settings'))" class="cursor-pointer dark:hover:bg-slate-800 text-xs">
                                <Icon icon="lucide:settings" class="mr-2 h-3.5 w-3.5" />
                                <span class="dark:text-slate-300">Cài đặt</span>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator class="dark:bg-slate-800" />
                            <DropdownMenuItem @click="logout" class="text-red-600 focus:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20 text-xs">
                                <Icon icon="lucide:log-out" class="mr-2 h-3.5 w-3.5" />
                                <span>Đăng xuất</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <!-- Breadcrumb -->
            <div v-if="breadcrumbs.length" class="px-4 lg:px-6 py-1.5 text-[11px] text-gray-500 dark:text-slate-400 bg-gray-50/80 dark:bg-slate-900/50 border-b border-gray-100 dark:border-slate-800">
                <div class="flex items-center gap-1 flex-wrap">
                    <Link :href="route('admin.dashboard')" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Trang chủ</Link>
                    <template v-for="(crumb, i) in breadcrumbs" :key="i">
                        <Icon icon="lucide:chevron-right" class="w-3 h-3 text-gray-400" />
                        <Link v-if="crumb.href && hasRoute(crumb.href)" :href="route(crumb.href)" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ crumb.label }}</Link>
                        <span v-else class="text-gray-700 dark:text-slate-300 font-medium">{{ crumb.label }}</span>
                    </template>
                </div>
            </div>

            <!-- Page Content -->
            <main class="p-3 lg:p-5 max-w-[1600px] w-full mx-auto flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.tree-children {
    animation: slideDown 0.15s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-3px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.nav-link {
    transition: all 0.12s ease;
}

.sidebar-nav {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.2) transparent;
}
.sidebar-nav::-webkit-scrollbar {
    width: 3px;
}
.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.2);
    border-radius: 3px;
}
.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.4);
}
</style>
