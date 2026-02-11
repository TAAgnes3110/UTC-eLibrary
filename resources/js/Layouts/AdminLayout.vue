<script setup>
import { ref, computed } from 'vue';
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
    title: { type: String, default: 'Dashboard' }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const sidebarOpen = ref(window.innerWidth >= 1024);

// Trạng thái collapsed: sidebar thu gọn trên desktop (chỉ icon)
const collapsed = computed(() => !sidebarOpen.value);

router.on('navigate', () => {
    if (window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }
});

const navigation = [
    { name: 'Tổng quan', href: 'admin.dashboard', icon: 'lucide:layout-grid', active: 'admin.dashboard' },
    { type: 'divider', label: 'Quản Lý Tài Nguyên' },
    { name: 'Quản lý Sách', href: 'admin.books.index', icon: 'lucide:book', active: 'admin.books.*' },
    { name: 'Danh mục', href: 'admin.categories.index', icon: 'lucide:list', active: 'admin.categories.*' },
    { name: 'Tác giả', href: 'admin.authors.index', icon: 'lucide:user-pen', active: 'admin.authors.*' },
    { name: 'Nhà xuất bản', href: 'admin.publishers.index', icon: 'lucide:building-2', active: 'admin.publishers.*' },
    { type: 'divider', label: 'Quản Lý Nghiệp Vụ' },
    { name: 'Độc giả', href: 'admin.readers.index', icon: 'lucide:users', active: 'admin.readers.*' },
    { name: 'Thẻ thư viện', href: 'admin.cards.index', icon: 'lucide:contact', active: 'admin.cards.*' },
    { name: 'Phiếu mượn', href: 'admin.loans.index', icon: 'lucide:clipboard-list', active: 'admin.loans.*' },
    { type: 'divider', label: 'Báo Cáo & Hệ Thống' },
    { name: 'Thống kê', href: 'admin.stats.index', icon: 'lucide:bar-chart-3', active: 'admin.stats.*' },
    { name: 'Người dùng', href: 'admin.users.index', icon: 'lucide:user-cog', active: 'admin.users.*', adminOnly: true },
];

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

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden transition-opacity duration-300"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col transition-all duration-300 ease-in-out',
                sidebarOpen
                    ? 'translate-x-0 w-72'
                    : '-translate-x-full lg:translate-x-0 lg:w-[72px]'
            ]"
        >
            <!-- Header -->
            <div :class="[
                'h-16 flex items-center border-b border-slate-100 dark:border-slate-800 shrink-0 transition-all duration-300',
                collapsed ? 'lg:justify-center lg:px-0 px-6 gap-3' : 'px-6 gap-3'
            ]">
                <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-slate-800 flex items-center justify-center p-1">
                    <img src="/Image/logoUTC.png" alt="UTC Logo" class="w-full h-full object-contain" />
                </div>
                <div v-show="sidebarOpen" class="flex flex-col min-w-0">
                    <h1 class="font-black text-slate-900 dark:text-white text-lg leading-tight tracking-tight truncate">UTC Library</h1>
                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Admin Panel</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav :class="[
                'flex-1 overflow-y-auto py-6 space-y-1 transition-all duration-300',
                collapsed ? 'lg:px-2 px-4' : 'px-4'
            ]">
                <template v-for="(item, index) in navigation" :key="index">
                    <!-- Divider -->
                    <template v-if="item.type === 'divider'">
                        <p v-show="sidebarOpen" class="px-4 pt-6 pb-2 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                            {{ item.label }}
                        </p>
                        <div v-show="!sidebarOpen" class="border-t border-slate-200 dark:border-slate-800 my-4 lg:mx-1"></div>
                    </template>
                    <!-- Nav Link -->
                    <template v-else>
                        <Link
                            v-if="hasRoute(item.href)"
                            :href="route(item.href)"
                            :class="[
                                'flex items-center gap-3 rounded-xl transition-all duration-200 group',
                                collapsed ? 'lg:justify-center lg:px-0 lg:py-2.5 px-4 py-3' : 'px-4 py-3',
                                isActive(item.active)
                                    ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 font-bold shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:translate-x-1 font-medium'
                            ]"
                            :title="item.name"
                        >
                            <Icon :icon="item.icon" class="w-5 h-5 shrink-0" />
                            <span v-show="sidebarOpen" class="truncate">{{ item.name }}</span>
                        </Link>
                        <div
                            v-else
                            :class="[
                                'flex items-center gap-3 rounded-xl text-slate-400 dark:text-slate-600 cursor-not-allowed',
                                collapsed ? 'lg:justify-center lg:px-0 lg:py-2.5 px-4 py-3' : 'px-4 py-3',
                            ]"
                            :title="item.name + ' (coming soon)'"
                        >
                            <Icon :icon="item.icon" class="w-5 h-5 shrink-0" />
                            <span v-show="sidebarOpen" class="truncate">{{ item.name }}</span>
                        </div>
                    </template>
                </template>
            </nav>


        </aside>

        <!-- Main Content -->
        <div :class="sidebarOpen ? 'lg:ml-72' : 'lg:ml-[72px]'" class="transition-all duration-300 min-h-screen flex flex-col">
            <!-- Top Bar -->
            <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 px-4 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2 lg:gap-4">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-500 dark:text-slate-400"
                    >
                        <Icon :icon="sidebarOpen ? 'lucide:panel-left-close' : 'lucide:panel-left-open'" class="w-5 h-5" />
                    </button>
                    <h2 class="text-lg lg:text-xl font-bold text-slate-800 dark:text-white truncate">{{ title }}</h2>
                </div>

                <div class="flex items-center gap-2 lg:gap-4">
                    <!-- Search -->
                    <div class="relative hidden lg:block">
                        <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                        <input
                            type="text"
                            placeholder="Tìm kiếm..."
                            class="bg-slate-100 dark:bg-slate-800 border-none rounded-xl pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 w-64 transition-all text-slate-900 dark:text-white dark:placeholder-slate-500"
                        />
                    </div>

                    <!-- Theme Toggle -->
                    <ThemeToggle />

                    <!-- Notifications -->
                    <Button variant="ghost" size="icon" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-xl">
                        <Icon icon="lucide:bell" class="h-5 w-5" />
                    </Button>

                    <!-- User Menu -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" class="h-10 w-10 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700">
                                <Icon icon="lucide:user" class="h-5 w-5 text-slate-600 dark:text-slate-300" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56 dark:bg-slate-900 dark:border-slate-800 transition-colors">
                            <DropdownMenuLabel>
                                <div class="flex flex-col space-y-1">
                                    <p class="text-sm font-medium leading-none dark:text-white">{{ user?.name }}</p>
                                    <p class="text-xs text-muted-foreground dark:text-slate-400">{{ user?.email }}</p>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator class="dark:bg-slate-800" />
                            <DropdownMenuItem v-if="hasRoute('admin.profile')" @click="router.visit(route('admin.profile'))" class="cursor-pointer dark:hover:bg-slate-800">
                                <Icon icon="lucide:user" class="mr-2 h-4 w-4" />
                                <span class="dark:text-slate-300">Hồ sơ cá nhân</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="hasRoute('admin.settings')" @click="router.visit(route('admin.settings'))" class="cursor-pointer dark:hover:bg-slate-800">
                                <Icon icon="lucide:settings" class="mr-2 h-4 w-4" />
                                <span class="dark:text-slate-300">Cài đặt</span>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator class="dark:bg-slate-800" />
                            <DropdownMenuItem @click="logout" class="text-red-600 focus:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20">
                                <Icon icon="lucide:log-out" class="mr-2 h-4 w-4" />
                                <span>Đăng xuất</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 lg:p-8 max-w-[1600px] w-full mx-auto flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
