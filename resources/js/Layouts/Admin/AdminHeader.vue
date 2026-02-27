<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
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
    title: { type: String, default: 'Dashboard' },
    sidebarOpen: { type: Boolean, required: true },
    user: { type: Object, default: null },
});

const emit = defineEmits(['toggle-sidebar']);

const hasRoute = (routeName) => {
    try {
        route(routeName);
        return true;
    } catch {
        return false;
    }
};

const searchQuery = ref('');
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.visit(route('admin.search', { q: searchQuery.value }));
    }
};


// Thông báo (mock – có thể nhận từ props hoặc API sau)
const notifications = ref([
    { id: 1, type: 'return', title: 'Sách sắp đến hạn trả', message: 'Lê Văn Tùng – "Giáo trình Cấu trúc dữ liệu" hạn trả 01/03/2024', time: '10 phút trước', read: false },
    { id: 2, type: 'overdue', title: 'Sách trả quá hạn', message: 'Nguyễn Thị Mai – "Lập trình Java" quá hạn 2 ngày', time: '1 giờ trước', read: false },
    { id: 3, type: 'loan', title: 'Phiếu mượn mới', message: 'Trần Minh Quân đã mượn "Xác suất thống kê ứng dụng"', time: '3 giờ trước', read: true },
    { id: 4, type: 'system', title: 'Cập nhật hệ thống', message: 'Quy định mượn trả đã được cập nhật. Vui lòng xem Cấu hình thư viện.', time: 'Hôm qua', read: true },
]);
const unreadCount = ref(notifications.value.filter((n) => !n.read).length);

const markAsRead = (id) => {
    const n = notifications.value.find((x) => x.id === id);
    if (n && !n.read) {
        n.read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
};
const markAllRead = () => {
    notifications.value.forEach((n) => (n.read = true));
    unreadCount.value = 0;
};

const getNotifIcon = (type) => {
    const map = { return: 'lucide:book-check', overdue: 'lucide:alert-circle', loan: 'lucide:book-plus', system: 'lucide:info' };
    return map[type] || 'lucide:bell';
};
const getNotifIconBg = (type) => {
    const map = {
        return: 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400',
        overdue: 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400',
        loan: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400',
        system: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
    };
    return map[type] || 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <header class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/95 backdrop-blur-md border-b border-gray-200 dark:border-slate-800 px-4 lg:px-6 h-12 flex items-center justify-between">
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

            <!-- Thông báo (chuông) -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="relative w-9 h-9 text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-xl">
                        <Icon icon="lucide:bell" class="h-4 w-4" />
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-rose-500 text-white text-[10px] font-bold"
                        >
                            {{ unreadCount > 99 ? '99+' : unreadCount }}
                        </span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    class="w-[380px] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl shadow-slate-900/10 dark:shadow-black/30 p-0 overflow-hidden"
                >
                    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Thông báo</h3>
                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            @click="markAllRead"
                            class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            Đánh dấu đã đọc
                        </button>
                    </div>
                    <div class="max-h-[360px] overflow-y-auto">
                        <template v-if="notifications.length">
                            <button
                                v-for="n in notifications"
                                :key="n.id"
                                type="button"
                                @click="markAsRead(n.id)"
                                :class="[
                                    'w-full flex gap-3 px-4 py-3 text-left transition-colors border-b border-slate-50 dark:border-slate-800/50 last:border-0',
                                    n.read ? 'hover:bg-slate-50 dark:hover:bg-slate-800/50' : 'bg-blue-50/50 dark:bg-blue-950/20 hover:bg-blue-50 dark:hover:bg-blue-950/30'
                                ]"
                            >
                                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', getNotifIconBg(n.type)]">
                                    <Icon :icon="getNotifIcon(n.type)" class="w-5 h-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ n.title }}</p>
                                        <span v-if="!n.read" class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-1.5" />
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">{{ n.message }}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">{{ n.time }}</p>
                                </div>
                            </button>
                        </template>
                        <div v-else class="px-4 py-10 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-400 dark:text-slate-500">
                                <Icon icon="lucide:bell-off" class="w-6 h-6" />
                            </div>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có thông báo</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Các thông báo mượn trả, quá hạn sẽ hiển thị tại đây</p>
                        </div>
                    </div>
                    <div class="px-4 py-2 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                        <button
                            type="button"
                            class="w-full py-2 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            Xem tất cả thông báo
                        </button>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" class="h-9 w-9 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 p-0 border border-slate-200/50 dark:border-slate-700/50 ring-0 focus-visible:ring-2 focus-visible:ring-blue-500/30">
                        <img v-if="user?.avatar" :src="user.avatar" :alt="user?.name" class="h-full w-full object-cover" />
                        <span v-else class="flex h-full w-full items-center justify-center text-slate-600 dark:text-slate-300 font-bold text-sm">
                            {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                        </span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    class="w-60 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl shadow-slate-900/10 dark:shadow-black/30 p-0 overflow-hidden"
                >
                    <!-- User info -->
                    <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm shrink-0">
                                {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ user?.email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="py-2">
                        <DropdownMenuItem v-if="hasRoute('admin.profile')" @click="router.visit(route('admin.profile'))" class="cursor-pointer mx-2 rounded-xl py-2.5 px-3 text-sm focus:bg-blue-50 dark:focus:bg-slate-800 focus:text-blue-700 dark:focus:text-blue-300">
                            <Icon icon="lucide:user-circle" class="mr-3 h-4 w-4 text-blue-500 dark:text-blue-400 shrink-0" />
                            <span>Hồ sơ cá nhân</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="hasRoute('admin.settings.rules')" @click="router.visit(route('admin.settings.rules'))" class="cursor-pointer mx-2 rounded-xl py-2.5 px-3 text-sm focus:bg-slate-100 dark:focus:bg-slate-800">
                            <Icon icon="lucide:sliders" class="mr-3 h-4 w-4 text-slate-500 dark:text-slate-400 shrink-0" />
                            <span class="text-slate-700 dark:text-slate-300">Cấu hình thư viện</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="hasRoute('admin.settings.appearance')" @click="router.visit(route('admin.settings.appearance'))" class="cursor-pointer mx-2 rounded-xl py-2.5 px-3 text-sm focus:bg-slate-100 dark:focus:bg-slate-800">
                            <Icon icon="lucide:settings" class="mr-3 h-4 w-4 text-slate-500 dark:text-slate-400 shrink-0" />
                            <span class="text-slate-700 dark:text-slate-300">Cài đặt giao diện</span>
                        </DropdownMenuItem>
                    </div>

                    <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800" />

                    <div class="p-2">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex w-full cursor-pointer items-center mx-2 rounded-xl py-2.5 px-3 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 focus:bg-rose-50 dark:focus:bg-rose-950/40 focus:outline-none focus:text-rose-700 dark:focus:text-rose-300"
                        >
                            <Icon icon="lucide:log-out" class="mr-3 h-4 w-4 shrink-0" />
                            <span>Đăng xuất</span>
                        </Link>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
