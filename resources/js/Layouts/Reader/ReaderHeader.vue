<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import UserAccountDropdown from '@/Components/UserAccountDropdown.vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import { accountMenuStrings } from '@/config/accountMenuStrings'
import { readerNavItems } from '@/config/readerNavigation'
import { readerLayoutStrings as S } from '@/config/readerStrings'
import { useNotifications } from '@/composables/useNotifications'
import { useImageFallback } from '@/composables/useImageFallback'
import { clearClientApiCredentials } from '@/utils/apiAuthStorage'
import { clearStaffWorkQueueSessionHint } from '@/utils/staffWorkQueueHint'
import { digitalPurchaseCartApi } from '@/api/digitalPurchaseCart'
import { READER_BORROW_CART_KEY, READER_CART_UPDATED_EVENT } from '@/config/readerCartKeys'

const page = usePage()
const user = computed(() => page.props.auth?.user)
/** Nhân viên thư viện / quản trị — vào admin; độc giả — về trang reader. */
const isStaff = computed(() => page.props.auth?.is_staff === true)
const mobileOpen = ref(false)
const borrowCartCount = ref(0)
const digitalCartCount = ref(0)
const totalBookCartCount = computed(() => borrowCartCount.value + digitalCartCount.value)
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
} = useNotifications()
const hasNotifications = computed(() => Array.isArray(notifications.value) && notifications.value.length > 0)

const syncBorrowCartCount = () => {
    try {
        const raw = JSON.parse(localStorage.getItem(READER_BORROW_CART_KEY) || '[]')
        const items = Array.isArray(raw) ? raw : []
        borrowCartCount.value = items.filter((x) => Number(x?.book_id) > 0).length
    } catch {
        borrowCartCount.value = 0
    }
}

const syncDigitalCartCount = async () => {
    if (!user.value) {
        digitalCartCount.value = 0
        return
    }
    try {
        const payload = await digitalPurchaseCartApi.count()
        const n = Number(payload?.data?.count ?? payload?.count ?? 0)
        digitalCartCount.value = Number.isFinite(n) ? n : 0
    } catch {
        digitalCartCount.value = 0
    }
}

const DIGITAL_CART_COUNT_DEBOUNCE_MS = 380
let digitalCartCountDebounceTimer = null
function scheduleDigitalCartCountDebounced() {
    if (digitalCartCountDebounceTimer) clearTimeout(digitalCartCountDebounceTimer)
    digitalCartCountDebounceTimer = setTimeout(() => {
        digitalCartCountDebounceTimer = null
        void syncDigitalCartCount()
    }, DIGITAL_CART_COUNT_DEBOUNCE_MS)
}

const syncAllBookCartCounts = () => {
    syncBorrowCartCount()
    scheduleDigitalCartCountDebounced()
}

const onStorage = (event) => {
    if (!event?.key || event.key === READER_BORROW_CART_KEY) {
        syncBorrowCartCount()
    }
}

const onReaderCartUpdated = () => {
    syncAllBookCartCounts()
}

onMounted(() => {
    syncBorrowCartCount()
    void syncDigitalCartCount()
    window.addEventListener('storage', onStorage)
    window.addEventListener(READER_CART_UPDATED_EVENT, onReaderCartUpdated)
})

onBeforeUnmount(() => {
    if (digitalCartCountDebounceTimer) {
        clearTimeout(digitalCartCountDebounceTimer)
        digitalCartCountDebounceTimer = null
    }
    window.removeEventListener('storage', onStorage)
    window.removeEventListener(READER_CART_UPDATED_EVENT, onReaderCartUpdated)
})

const hasRoute = (routeName) => {
    try {
        route(routeName)
        return true
    } catch {
        return false
    }
}

const logout = () => {
    clearStaffWorkQueueSessionHint()
    // Xóa token local ngay khi bấm đăng xuất để tránh dùng lại token cũ.
    clearClientApiCredentials()
    router.post(route('logout'), {}, {
        onSuccess: () => {
            window.location.href = '/'
        },
    })
}

const closeMobileAndLogout = () => {
    mobileOpen.value = false
    logout()
}

const getNotifIcon = (type) => {
    if (type.includes('overdue') || type.includes('expired') || type.includes('rejected')) return 'lucide:alert-circle'
    if (type.includes('approved')) return 'lucide:badge-check'
    if (type.includes('loan')) return 'lucide:clipboard-list'
    if (type.includes('card')) return 'lucide:id-card'
    if (type.includes('profile')) return 'lucide:user-round-check'
    return 'lucide:bell'
}

const getNotifIconBg = (severity) => {
    if (severity === 'critical') return 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
    if (severity === 'warning') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
}

const onNotificationClick = async (notification) => {
    await markAsRead(notification.id)
    if (notification.actionUrl) {
        router.visit(notification.actionUrl)
    }
}

const isRouteActive = (routeName) => route().current(routeName)

const isNavItemActive = (item) => {
    if (!item.children?.length) {
        return isRouteActive(item.route)
    }
    if (isRouteActive(item.route)) {
        return true
    }
    return item.children.some((c) => isRouteActive(c.route))
}
const { withFallback } = useImageFallback()

const navLinkClass = (item) => {
    const active = isNavItemActive(item)
    return [
        'min-h-[44px] inline-flex items-center whitespace-nowrap rounded-lg px-2 text-sm font-semibold transition-colors xl:px-3',
        active
            ? 'bg-blue-900 text-white dark:bg-blue-800'
            : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800',
    ]
}

/** Link con (mobile): chỉ so khớp đúng route của mục đó. */
const navChildLinkClass = (childRoute) => {
    const active = isRouteActive(childRoute)
    return [
        'min-h-[44px] inline-flex w-full items-center rounded-lg py-2 pl-8 pr-3 text-sm font-medium transition-colors',
        active
            ? 'bg-blue-900 text-white dark:bg-blue-800'
            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
    ]
}

</script>

<template>
    <header
        class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur-md pt-[env(safe-area-inset-top)] dark:border-slate-800 dark:bg-slate-900/95"
    >
        <div
            class="mx-auto flex min-w-0 max-w-[1400px] items-center justify-between gap-2 px-3 py-3 sm:px-4 lg:px-5"
        >
            <!-- Nhóm trái: thương hiệu + menu (lg); nhóm phải: đăng nhập / avatar — tách rõ hai đầu -->
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-2.5 lg:gap-3">
                <button
                    type="button"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-700 lg:hidden dark:border-slate-700 dark:text-slate-200"
                    :aria-label="S.openMenu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Icon :icon="mobileOpen ? 'lucide:x' : 'lucide:menu'" class="h-6 w-6" />
                </button>
                <Link prefetch :href="route('reader.home')" class="flex min-w-0 shrink-0 items-center gap-2 sm:gap-2.5">
                    <img src="/Image/logoUTC.png" alt="UTC" class="h-9 w-9 shrink-0 object-contain sm:h-10 sm:w-10" />
                    <div class="hidden min-w-0 leading-tight lg:block">
                        <p class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            {{ S.digitalLibrary }}
                        </p>
                        <p class="truncate text-sm font-black text-blue-900 dark:text-blue-300 sm:text-base">
                            {{ S.universityShort }}
                        </p>
                    </div>
                </Link>

                <nav
                    class="ml-1 hidden min-w-0 flex-1 flex-nowrap items-center gap-0.5 overflow-visible whitespace-nowrap pr-0.5 lg:flex lg:gap-1"
                    :aria-label="S.mainNav"
                >
                    <template v-for="item in readerNavItems" :key="item.key">
                        <div v-if="item.children" class="group relative shrink-0">
                            <Link
                                prefetch
                                :href="route(item.route)"
                                :class="[...navLinkClass(item), 'gap-1']"
                            >
                                {{ item.label }}
                                <Icon icon="lucide:chevron-down" class="h-4 w-4 shrink-0 opacity-80" aria-hidden="true" />
                            </Link>
                            <div
                                class="invisible absolute left-0 top-full z-50 min-w-[14rem] pt-1 opacity-0 transition-[opacity,visibility] duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100"
                                role="menu"
                            >
                                <div
                                    class="rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-white/5"
                                >
                                    <Link
                                        v-for="c in item.children"
                                        :key="c.key"
                                        prefetch
                                        :href="route(c.route)"
                                        class="flex min-h-[44px] items-center px-4 text-sm font-medium text-slate-800 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                                        role="menuitem"
                                    >
                                        {{ c.label }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <Link
                            v-else
                            prefetch
                            :href="route(item.route)"
                            :class="[...navLinkClass(item), 'shrink-0']"
                        >
                            {{ item.label }}
                        </Link>
                    </template>
                </nav>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 lg:gap-2">
                <ThemeToggle />
                <template v-if="!user">
                    <Link
                        prefetch
                        :href="route('login')"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 sm:min-w-0"
                    >
                        {{ S.login }}
                    </Link>
                    <Link
                        prefetch
                        :href="route('register')"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-gradient-to-r from-blue-700 to-blue-900 px-4 text-sm font-semibold text-white shadow-md shadow-blue-900/20 hover:brightness-110"
                    >
                        {{ S.register }}
                    </Link>
                </template>
                <template v-else>
                    <Link
                        prefetch
                        :href="route('reader.services.book-cart')"
                        class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        aria-label="Giỏ sách"
                        title="Giỏ sách"
                    >
                        <Icon icon="lucide:shopping-cart" class="h-5 w-5" />
                        <span
                            v-if="totalBookCartCount > 0"
                            class="absolute -right-1 -top-1 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-emerald-500 px-1 text-[10px] font-bold text-white"
                        >
                            {{ totalBookCartCount > 99 ? '99+' : totalBookCartCount }}
                        </span>
                    </Link>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                aria-label="Thông báo"
                                title="Thông báo"
                            >
                                <Icon icon="lucide:bell" class="h-5 w-5" />
                                <span
                                    v-if="unreadCount > 0"
                                    class="absolute -right-1 -top-1 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
                                >
                                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                                </span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-[360px] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-0 shadow-xl dark:border-slate-700 dark:bg-slate-900"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Thông báo</h3>
                                <div class="flex flex-wrap items-center justify-end gap-x-2 gap-y-1 text-xs">
                                    <button
                                        v-if="unreadCount > 0"
                                        type="button"
                                        class="font-semibold text-blue-700 hover:underline dark:text-blue-400 disabled:opacity-50"
                                        :disabled="markingAll || deletingAll"
                                        @click="markAllAsRead"
                                    >
                                        Đánh dấu đã đọc
                                    </button>
                                    <button
                                        v-if="hasNotifications"
                                        type="button"
                                        class="font-semibold text-rose-600 hover:underline dark:text-rose-400 disabled:opacity-50"
                                        :disabled="deletingAll || markingAll"
                                        @click="deleteAllNotifications"
                                    >
                                        Xóa tất cả
                                    </button>
                                </div>
                            </div>
                            <div class="max-h-[340px] overflow-y-auto">
                                <template v-if="hasNotifications">
                                <div
                                    v-for="n in notifications"
                                    :key="n.id"
                                    :class="[
                                        'flex w-full items-stretch border-b border-slate-50 dark:border-slate-800/60',
                                        n.read ? '' : 'bg-blue-50/60 dark:bg-blue-950/20',
                                    ]"
                                >
                                    <button
                                        type="button"
                                        class="flex min-w-0 flex-1 items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                                        @click="onNotificationClick(n)"
                                    >
                                        <div :class="['mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', getNotifIconBg(n.severity)]">
                                            <Icon :icon="getNotifIcon(n.type)" class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ n.title }}</p>
                                                <span v-if="!n.read" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500" />
                                            </div>
                                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ n.message }}</p>
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
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                        <Icon icon="lucide:bell-off" class="h-6 w-6" />
                                    </div>
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có thông báo</p>
                                    <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Thông báo mượn trả và trạng thái hồ sơ sẽ hiển thị ở đây</p>
                                </div>
                            </div>
                            <div class="border-t border-slate-100 bg-slate-50/80 px-4 py-2 text-center dark:border-slate-800 dark:bg-slate-800/40">
                                <Link
                                    prefetch
                                    :href="route('reader.services.loan-requests')"
                                    class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400"
                                >
                                    Xem quản lý phiếu mượn
                                </Link>
                            </div>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <UserAccountDropdown
                        :user="user"
                        trigger-size="touch"
                        personal-info-route-name="reader.profile"
                        :change-password-route-name="isStaff ? 'admin.change-password' : 'reader.change-password'"
                    >
                        <template #items>
                            <DropdownMenuItem
                                v-if="!isStaff && hasRoute('reader.services.digital-orders')"
                                class="mx-2 cursor-pointer rounded-xl px-3 py-2.5 text-sm focus:bg-slate-100 dark:focus:bg-slate-800"
                                @click="router.visit(route('reader.services.digital-orders'))"
                            >
                                <Icon icon="lucide:receipt" class="mr-3 h-4 w-4 shrink-0 text-violet-600 dark:text-violet-400" />
                                <span class="text-slate-700 dark:text-slate-300">{{ accountMenuStrings.myDigitalOrders }}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="!isStaff"
                                class="mx-2 cursor-pointer rounded-xl px-3 py-2.5 text-sm focus:bg-slate-100 dark:focus:bg-slate-800"
                                @click="router.visit(route('reader.profile-update-requests'))"
                            >
                                <Icon icon="lucide:history" class="mr-3 h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" />
                                <span class="text-slate-700 dark:text-slate-300">Lịch sử yêu cầu cập nhật</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="isStaff"
                                class="mx-2 cursor-pointer rounded-xl px-3 py-2.5 text-sm focus:bg-slate-100 dark:focus:bg-slate-800"
                                @click="router.visit(route('admin.dashboard'))"
                            >
                                <Icon icon="lucide:layout-dashboard" class="mr-3 h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" />
                                <span class="text-slate-700 dark:text-slate-300">{{ S.goToApp }}</span>
                            </DropdownMenuItem>
                        </template>
                    </UserAccountDropdown>
                </template>
            </div>
        </div>

        <div
            v-show="mobileOpen"
            class="border-t border-slate-200 px-4 py-3 dark:border-slate-800 lg:hidden"
        >
            <div class="flex flex-col gap-1">
                <template v-for="item in readerNavItems" :key="item.key">
                    <div v-if="item.children" class="flex flex-col gap-1">
                        <Link
                            prefetch
                            :href="route(item.route)"
                            :class="[...navLinkClass(item), 'w-full justify-start']"
                            @click="mobileOpen = false"
                        >
                            {{ item.label }}
                        </Link>
                        <Link
                            v-for="c in item.children"
                            :key="c.key"
                            prefetch
                            :href="route(c.route)"
                            :class="navChildLinkClass(c.route)"
                            @click="mobileOpen = false"
                        >
                            {{ c.label }}
                        </Link>
                    </div>
                    <Link
                        v-else
                        prefetch
                        :href="route(item.route)"
                        :class="[...navLinkClass(item), 'w-full justify-start']"
                        @click="mobileOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </template>
                <div v-if="user" class="mt-2 flex flex-col gap-2 border-t border-slate-200 pt-3 dark:border-slate-700">
                    <div class="flex items-center gap-3 px-1 py-1">
                        <img
                            :src="user?.avatar || '/images/default-avatar.png'"
                            :alt="user?.name || 'Avatar'"
                            @error="withFallback('/images/default-avatar.png')($event)"
                            class="h-11 w-11 shrink-0 rounded-full border border-slate-200 object-cover dark:border-slate-600"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                {{ user?.name }}
                            </p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ user?.email }}
                            </p>
                        </div>
                    </div>
                    <Link
                        prefetch
                        :href="route('reader.home')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ S.goToReader }}
                    </Link>
                    <Link
                        prefetch
                        :href="route('reader.catalog')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ S.catalog }}
                    </Link>
                    <Link
                        prefetch
                        :href="route('reader.services.book-cart')"
                        class="inline-flex min-h-[44px] w-full items-center justify-between gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-100 dark:hover:bg-slate-800"
                        @click="mobileOpen = false"
                    >
                        <span class="flex items-center gap-2">
                            <Icon icon="lucide:shopping-cart" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            Giỏ sách
                        </span>
                        <span
                            v-if="totalBookCartCount > 0"
                            class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-emerald-500 px-2 text-xs font-bold text-white"
                        >
                            {{ totalBookCartCount > 99 ? '99+' : totalBookCartCount }}
                        </span>
                    </Link>
                    <Link
                        v-if="isStaff"
                        prefetch
                        :href="route('admin.dashboard')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-slate-900 text-sm font-semibold text-white dark:bg-blue-800"
                        @click="mobileOpen = false"
                    >
                        {{ S.goToApp }}
                    </Link>
                    <Link
                        v-if="!isStaff && hasRoute('reader.services.digital-orders')"
                        prefetch
                        :href="route('reader.services.digital-orders')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        <Icon icon="lucide:receipt" class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                        {{ accountMenuStrings.myDigitalOrders }}
                    </Link>
                    <Link
                        v-if="hasRoute('reader.profile')"
                        prefetch
                        :href="route('reader.profile')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ accountMenuStrings.updatePersonalInfo }}
                    </Link>
                    <Link
                        v-if="(isStaff && hasRoute('admin.change-password')) || (!isStaff && hasRoute('reader.change-password'))"
                        prefetch
                        :href="route(isStaff ? 'admin.change-password' : 'reader.change-password')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ accountMenuStrings.changePassword }}
                    </Link>
                    <button
                        type="button"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="closeMobileAndLogout"
                    >
                        {{ S.logout }}
                    </button>
                </div>
                <div v-else class="mt-2 flex flex-col gap-2 border-t border-slate-200 pt-3 dark:border-slate-700">
                    <Link
                        prefetch
                        :href="route('login')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ S.login }}
                    </Link>
                    <Link
                        prefetch
                        :href="route('register')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-blue-900 text-sm font-semibold text-white"
                        @click="mobileOpen = false"
                    >
                        {{ S.register }}
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>
