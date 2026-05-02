<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import UserAccountDropdown from '@/Components/UserAccountDropdown.vue';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps({
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

const {
    notifications,
    unreadCount,
    markAsRead,
    markAllAsRead,
    markingAll,
    deleteNotification,
    deleteAllNotifications,
    deletingAll,
    deletingIds,
} = useNotifications();

const getNotifIcon = (type) => {
    if (type.includes('overdue') || type.includes('expired') || type.includes('rejected')) return 'lucide:alert-circle';
    if (type.includes('approved')) return 'lucide:badge-check';
    if (type.includes('loan')) return 'lucide:book-plus';
    if (type.includes('card')) return 'lucide:id-card';
    if (type.includes('profile')) return 'lucide:user-round-check';
    return 'lucide:bell';
};
const getNotifIconBg = (severity) => {
    if (severity === 'critical') return 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400';
    if (severity === 'warning') return 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400';
    return 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400';
};

const hasNotifications = computed(() => Array.isArray(notifications.value) && notifications.value.length > 0);

const onNotificationClick = async (notification) => {
    await markAsRead(notification.id);
    if (notification.actionUrl) {
        router.visit(notification.actionUrl);
    }
};
</script>

<template>
    <header class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/95 backdrop-blur-md border-b border-gray-200 dark:border-slate-800 pt-[env(safe-area-inset-top)]">
        <div class="px-4 lg:px-6 h-12 flex items-center justify-between">
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
                    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white shrink-0">Thông báo</h3>
                        <div class="flex flex-wrap items-center justify-end gap-x-2 gap-y-1 text-xs">
                            <button
                                v-if="unreadCount > 0"
                                type="button"
                                :disabled="markingAll || deletingAll"
                                @click="markAllAsRead"
                                class="font-semibold text-blue-600 dark:text-blue-400 hover:underline disabled:opacity-50"
                            >
                                Đánh dấu đã đọc
                            </button>
                            <button
                                v-if="hasNotifications"
                                type="button"
                                :disabled="deletingAll || markingAll"
                                @click="deleteAllNotifications"
                                class="font-semibold text-rose-600 dark:text-rose-400 hover:underline disabled:opacity-50"
                            >
                                Xóa tất cả
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[360px] overflow-y-auto">
                        <template v-if="hasNotifications">
                            <div
                                v-for="n in notifications"
                                :key="n.id"
                                :class="[
                                    'flex w-full items-stretch border-b border-slate-50 dark:border-slate-800/50 last:border-0',
                                    n.read ? '' : 'bg-blue-50/50 dark:bg-blue-950/20',
                                ]"
                            >
                                <button
                                    type="button"
                                    @click="onNotificationClick(n)"
                                    :class="[
                                        'flex min-w-0 flex-1 gap-3 px-4 py-3 text-left transition-colors',
                                        n.read ? 'hover:bg-slate-50 dark:hover:bg-slate-800/50' : 'hover:bg-blue-50 dark:hover:bg-blue-950/30',
                                    ]"
                                >
                                    <div :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', getNotifIconBg(n.severity)]">
                                        <Icon :icon="getNotifIcon(n.type)" class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ n.title }}</p>
                                            <span v-if="!n.read" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500" />
                                        </div>
                                        <p class="mt-0.5 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ n.message }}</p>
                                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ n.time }}</p>
                                    </div>
                                </button>
                                <button
                                    type="button"
                                    :disabled="deletingIds.has(n.id) || deletingAll"
                                    class="flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center text-slate-400 transition-colors hover:bg-slate-100 hover:text-rose-600 disabled:opacity-40 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-rose-400"
                                    aria-label="Xóa thông báo"
                                    title="Xóa"
                                    @click.stop="deleteNotification(n.id)"
                                >
                                    <Icon icon="lucide:trash-2" class="h-4 w-4" />
                                </button>
                            </div>
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

            <UserAccountDropdown
                v-if="user"
                :user="user"
                personal-info-route-name="reader.profile"
                change-password-route-name="admin.change-password"
            >
                <template #items>
                    <DropdownMenuItem v-if="hasRoute('reader.home')" @click="router.visit(route('reader.home'))" class="cursor-pointer mx-2 rounded-xl py-2.5 px-3 text-sm focus:bg-slate-100 dark:focus:bg-slate-800">
                        <Icon icon="lucide:house" class="mr-3 h-4 w-4 text-slate-500 dark:text-slate-400 shrink-0" />
                        <span class="text-slate-700 dark:text-slate-300">Về trang người dùng</span>
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="hasRoute('admin.settings.rules')" @click="router.visit(route('admin.settings.rules'))" class="cursor-pointer mx-2 rounded-xl py-2.5 px-3 text-sm focus:bg-slate-100 dark:focus:bg-slate-800">
                        <Icon icon="lucide:sliders" class="mr-3 h-4 w-4 text-slate-500 dark:text-slate-400 shrink-0" />
                        <span class="text-slate-700 dark:text-slate-300">Cấu hình thư viện</span>
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="hasRoute('admin.settings.appearance')" @click="router.visit(route('admin.settings.appearance'))" class="cursor-pointer mx-2 rounded-xl py-2.5 px-3 text-sm focus:bg-slate-100 dark:focus:bg-slate-800">
                        <Icon icon="lucide:settings" class="mr-3 h-4 w-4 text-slate-500 dark:text-slate-400 shrink-0" />
                        <span class="text-slate-700 dark:text-slate-300">Cài đặt giao diện</span>
                    </DropdownMenuItem>
                </template>
            </UserAccountDropdown>
        </div>
        </div>
    </header>
</template>
