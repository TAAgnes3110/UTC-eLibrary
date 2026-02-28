<script setup>
import { Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    title: { type: String, default: 'Thư viện số' },
    sidebarOpen: { type: Boolean, required: true },
    user: { type: Object, default: null },
    isStaff: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-sidebar']);

const hasRoute = (name) => {
    try {
        route(name);
        return true;
    } catch {
        return false;
    }
};
</script>

<template>
    <header class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/95 backdrop-blur-md border-b border-gray-200 dark:border-slate-800 pt-[env(safe-area-inset-top)] px-4 lg:px-6 min-h-[3rem] flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="emit('toggle-sidebar')"
                class="p-1.5 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors text-gray-500 dark:text-slate-400"
            >
                <Icon :icon="sidebarOpen ? 'lucide:panel-left-close' : 'lucide:panel-left-open'" class="w-[18px] h-[18px]" />
            </button>
            <h2 class="text-sm font-semibold text-gray-700 dark:text-white truncate">{{ title }}</h2>
        </div>

        <div class="flex items-center gap-1.5">
            <ThemeToggle />
            <template v-if="user">
                <Link
                    v-if="isStaff && hasRoute('admin.dashboard')"
                    :href="route('admin.dashboard')"
                    class="hidden sm:flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                >
                    <Icon icon="lucide:shield" class="w-3.5 h-3.5" />
                    Quản trị
                </Link>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" class="h-9 w-9 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 p-0 border border-slate-200/50 dark:border-slate-700/50">
                            <span class="flex h-full w-full items-center justify-center text-slate-600 dark:text-slate-300 font-bold text-sm">
                                {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-60 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl p-0 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ user?.email }}</p>
                        </div>
                        <div class="py-2">
                            <DropdownMenuItem v-if="hasRoute('admin.profile')" as-child>
                                <Link :href="route('admin.profile')" class="cursor-pointer mx-2 rounded-xl py-2.5 px-3 text-sm">Hồ sơ cá nhân</Link>
                            </DropdownMenuItem>
                        </div>
                        <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800" />
                        <div class="p-2">
                            <Link :href="route('logout')" method="post" as="button" class="flex w-full cursor-pointer items-center mx-2 rounded-xl py-2.5 px-3 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                                <Icon icon="lucide:log-out" class="mr-3 h-4 w-4 shrink-0" />
                                Đăng xuất
                            </Link>
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>
            <template v-else>
                <Link :href="route('login')" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700 dark:bg-amber-500 dark:text-slate-900 dark:hover:bg-amber-400">
                    Đăng nhập
                </Link>
                <Link :href="route('register')" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    Đăng ký
                </Link>
            </template>
        </div>
    </header>
</template>
