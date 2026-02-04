<script setup>
import { ref, watchEffect } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
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

const sidebarOpen = ref(true);
const showingNavigationDropdown = ref(false);
const page = usePage();
const user = page.props.auth.user;

// Sync token to localStorage for API calls
watchEffect(() => {
    const token = page.props.auth?.token;
    if (token) {
        localStorage.setItem('token', token);
    }
});
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0 lg:w-20'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white transition-all duration-300 ease-in-out lg:block"
        >
            <div class="flex h-16 items-center justify-between px-6 border-b border-slate-800">
                <div class="flex items-center gap-2 overflow-hidden">
                     <div class="bg-white rounded-full p-1 h-8 w-8 flex items-center justify-center shrink-0">
                        <img src="/Image/logoUTC.png" alt="Logo" class="h-6 w-6 object-contain" />
                    </div>
                    <span v-show="sidebarOpen" class="text-lg font-bold font-sans tracking-tight truncate">UTC e-Library</span>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                    <Icon icon="lucide:x" class="h-6 w-6" />
                </button>
            </div>

            <nav class="mt-6 px-4 space-y-2">
                <Link :href="route('dashboard')" :class="{'bg-blue-600 text-white': route().current('dashboard'), 'text-slate-300 hover:bg-slate-800 hover:text-white': !route().current('dashboard')}" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors group">
                    <Icon icon="lucide:layout-dashboard" class="h-5 w-5 shrink-0" />
                    <span v-show="sidebarOpen" class="font-medium">Tổng quan</span>
                </Link>

                <Link href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
                    <Icon icon="lucide:book-open" class="h-5 w-5 shrink-0" />
                    <span v-show="sidebarOpen" class="font-medium">Quản lý Sách</span>
                </Link>

                 <Link href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
                    <Icon icon="lucide:users" class="h-5 w-5 shrink-0" />
                    <span v-show="sidebarOpen" class="font-medium">Người dùng</span>
                </Link>

                <Link href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
                    <Icon icon="lucide:settings" class="h-5 w-5 shrink-0" />
                    <span v-show="sidebarOpen" class="font-medium">Cấu hình</span>
                </Link>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'" class="flex flex-col min-h-screen transition-all duration-300">
            <!-- Top Header -->
            <header class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-40 shadow-sm">
                <!-- Mobile Menu Button -->
                <button class="p-2 rounded-md text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="sidebarOpen = !sidebarOpen">
                    <Icon :icon="sidebarOpen ? 'lucide:panel-left-close' : 'lucide:panel-left-open'" class="h-6 w-6" />
                </button>

                <!-- Search / Breadcrumbs -->
                <div class="hidden md:flex items-center text-slate-500 text-sm">
                   <span class="font-medium text-slate-800 dark:text-slate-200">Dashboard</span>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center gap-2 lg:gap-4">
                    <ThemeToggle />

                    <Button variant="ghost" size="icon" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        <Icon icon="lucide:bell" class="h-5 w-5" />
                    </Button>

                     <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                             <Button variant="ghost" class="relative h-8 w-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-800">
                                <Icon icon="lucide:user" class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56 dark:bg-slate-900 dark:border-slate-800 transition-colors">
                            <DropdownMenuLabel>
                                <div class="flex flex-col space-y-1">
                                    <p class="text-sm font-medium leading-none dark:text-white">{{ user?.name }}</p>
                                    <p class="text-xs leading-none text-muted-foreground dark:text-slate-400">{{ user?.email }}</p>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator class="dark:bg-slate-800" />
                            <DropdownMenuItem class="dark:hover:bg-slate-800">
                                <Icon icon="lucide:user" class="mr-2 h-4 w-4" />
                                <span class="dark:text-slate-300">Hồ sơ cá nhân</span>
                            </DropdownMenuItem>
                             <DropdownMenuItem class="dark:hover:bg-slate-800">
                                <Icon icon="lucide:settings" class="mr-2 h-4 w-4" />
                                <span class="dark:text-slate-300">Cài đặt</span>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator class="dark:bg-slate-800" />
                            <DropdownMenuItem class="text-red-600 focus:text-red-700 dark:hover:bg-red-950/20">
                                <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center">
                                    <Icon icon="lucide:log-out" class="mr-2 h-4 w-4" />
                                    <span>Đăng xuất</span>
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 lg:p-6 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
                <slot />
            </main>
        </div>
    </div>
</template>

