<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

defineProps({
    title: { type: String, default: 'Tra cứu sách' },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isStaff = computed(() => !!page.props.auth?.is_staff);
const searchQuery = computed(() => page.props.filters?.q ?? '');
const currentUrl = computed(() => page.url);
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-300">
        <!-- Header: cùng phong cách admin -->
        <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/95">
            <div class="mx-auto max-w-6xl px-4 py-3 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <Link
                            :href="route('library.search')"
                            class="flex shrink-0 items-center gap-2.5 rounded-lg transition hover:opacity-90"
                        >
                            <img src="/Image/logoUTC.png" alt="UTC" class="h-9 w-9 object-contain sm:h-10 sm:w-10" />
                            <div class="flex flex-col leading-tight">
                                <span class="font-bold text-slate-900 dark:text-white">UTC eLibrary</span>
                                <span class="hidden text-xs font-medium text-slate-500 dark:text-slate-400 sm:inline">Thư viện số</span>
                            </div>
                        </Link>
                        <!-- end logo -->
                        <form :action="route('library.search')" method="get" class="hidden min-w-0 flex-1 max-w-sm lg:block">
                            <div class="relative w-full">
                                <Icon icon="lucide:search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    type="search"
                                    name="q"
                                    :value="searchQuery"
                                    placeholder="Tên sách, mã sách, tác giả..."
                                    class="h-10 w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400 dark:border-slate-600 dark:bg-slate-800/80 dark:text-white dark:placeholder-slate-500 dark:focus:border-amber-500/50 dark:focus:ring-amber-500/20"
                                />
                                <Button type="submit" size="sm" class="absolute right-1 top-1/2 h-7 -translate-y-1/2 rounded-md bg-slate-700 px-2.5 text-xs text-white hover:bg-slate-600 dark:bg-amber-500 dark:text-slate-900 dark:hover:bg-amber-400">
                                    Tìm
                                </Button>
                            </div>
                        </form>
                    </div>
                    <!-- nav -->
                    <nav class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <Link
                            :href="route('library.search')"
                            :class="[
                                currentUrl.startsWith('/library') && !currentUrl.includes('/saved')
                                    ? 'bg-slate-200 text-slate-900 dark:bg-slate-700/80 dark:text-amber-400'
                                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
                            ]"
                            class="rounded-lg px-3 py-2 text-sm font-medium transition"
                        >
                            Tra cứu sách
                        </Link>
                        <Link
                            :href="route('library.saved')"
                            :class="[
                                currentUrl.includes('/saved') ? 'bg-slate-200 text-slate-900 dark:bg-slate-700/80 dark:text-amber-400' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
                            ]"
                            class="rounded-lg px-3 py-2 text-sm font-medium transition"
                        >
                            Sách đã lưu
                        </Link>
                        <Link
                            v-if="user && isStaff"
                            :href="route('admin.dashboard')"
                            class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                        >
                            Quản trị
                        </Link>
                        <template v-if="!user">
                            <Link
                                :href="route('register')"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                            >
                                Đăng ký
                            </Link>
                            <Link
                                :href="route('login')"
                                class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-amber-500 dark:text-slate-900 dark:hover:bg-amber-400"
                            >
                                Đăng nhập
                            </Link>
                        </template>
                        <DropdownMenu v-else>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                class="h-10 w-10 rounded-full border border-slate-300 bg-slate-100 text-slate-700 hover:bg-slate-200 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:bg-slate-700"
                            >
                                <span class="text-sm font-semibold">{{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-52 rounded-xl border-slate-700 bg-slate-900">
                            <div class="px-3 py-2 border-b border-slate-700">
                                <p class="text-sm font-medium truncate text-white">{{ user?.name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ user?.email }}</p>
                            </div>
                            <DropdownMenuItem as-child>
                                <Link :href="route('admin.profile')" class="cursor-pointer">Hồ sơ cá nhân</Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator class="bg-slate-200 dark:bg-slate-700" />
                            <DropdownMenuItem as-child>
                                <Link :href="route('logout')" method="post" as="button" class="cursor-pointer text-rose-600 focus:text-rose-500 dark:text-rose-400 dark:focus:text-rose-300">
                                    Đăng xuất
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </nav>
            </div>
            </div>
        </header>

        <!-- Gợi ý đăng nhập cho khách -->
        <div
            v-if="!user"
            class="relative z-40 border-b border-slate-200 bg-slate-50 px-4 py-2 dark:border-slate-800/60 dark:bg-slate-800/40 sm:px-6"
        >
            <p class="mx-auto max-w-6xl text-center text-xs text-slate-500 dark:text-slate-400">
                Bạn có thể tra cứu sách không cần đăng nhập.
                <Link :href="route('login')" class="font-medium text-slate-700 hover:text-slate-900 dark:text-amber-400 dark:hover:text-amber-300 underline">
                    Đăng nhập
                </Link>
                để mượn sách, gia hạn và xem phiếu mượn.
                <Link :href="route('register')" class="ml-1 font-medium text-slate-600 hover:text-slate-800 dark:text-slate-300 dark:hover:text-white underline">Đăng ký</Link>
            </p>
        </div>

        <main class="relative z-10 mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <slot />
        </main>
    </div>
</template>
