<script setup>
import { computed, ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { DropdownMenuItem } from '@/Components/ui/dropdown-menu'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import UserAccountDropdown from '@/Components/UserAccountDropdown.vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import { accountMenuStrings } from '@/config/accountMenuStrings'
import { readerNavItems } from '@/config/readerNavigation'
import { readerLayoutStrings as S } from '@/config/readerStrings'

const page = usePage()
const user = computed(() => page.props.auth?.user)
/** Nhân viên thư viện / quản trị — vào admin; độc giả — về trang reader. */
const isStaff = computed(() => page.props.auth?.is_staff === true)
const mobileOpen = ref(false)
const notifications = ref([
    {
        id: 1,
        type: 'loan',
        title: 'Phiếu mượn gần đến hạn',
        message: 'Bạn có 1 phiếu mượn cần kiểm tra gia hạn.',
        time: '10 phút trước',
        read: false,
    },
    {
        id: 2,
        type: 'saved',
        title: 'Sách đã lưu có bản khả dụng',
        message: 'Một đầu sách trong danh sách lưu hiện đang còn bản để mượn.',
        time: '1 giờ trước',
        read: false,
    },
    {
        id: 3,
        type: 'card',
        title: 'Nhắc cập nhật hồ sơ thẻ',
        message: 'Vui lòng rà soát thông tin hồ sơ để dùng dịch vụ ổn định.',
        time: 'Hôm qua',
        read: true,
    },
])
const unreadCount = computed(() => notifications.value.filter((n) => !n.read).length)

const hasRoute = (routeName) => {
    try {
        route(routeName)
        return true
    } catch {
        return false
    }
}

const logout = () => {
    router.post(route('logout'), {}, {
        onSuccess: () => router.visit(route('reader.home')),
    })
}

const closeMobileAndLogout = () => {
    mobileOpen.value = false
    logout()
}

const markAsRead = (id) => {
    const item = notifications.value.find((n) => n.id === id)
    if (item) item.read = true
}

const markAllRead = () => {
    notifications.value = notifications.value.map((n) => ({ ...n, read: true }))
}

const getNotifIcon = (type) => {
    if (type === 'loan') return 'lucide:clipboard-list'
    if (type === 'saved') return 'lucide:bookmark'
    if (type === 'card') return 'lucide:id-card'
    return 'lucide:bell'
}

const getNotifIconBg = (type) => {
    if (type === 'loan') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
    if (type === 'saved') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
    if (type === 'card') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
    return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
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

const navLinkClass = (item) => {
    const active = isNavItemActive(item)
    return [
        'min-h-[44px] inline-flex items-center rounded-lg px-3 text-sm font-semibold transition-colors',
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
            class="mx-auto flex min-w-0 max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6"
        >
            <!-- Nhóm trái: thương hiệu + menu (lg); nhóm phải: đăng nhập / avatar — tách rõ hai đầu -->
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3 lg:gap-4">
                <button
                    type="button"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-700 lg:hidden dark:border-slate-700 dark:text-slate-200"
                    :aria-label="S.openMenu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Icon :icon="mobileOpen ? 'lucide:x' : 'lucide:menu'" class="h-6 w-6" />
                </button>
                <Link
                    :href="route('reader.home')"
                    class="flex min-w-0 shrink-0 items-center gap-2 sm:gap-3"
                >
                    <img src="/Image/logoUTC.png" alt="UTC" class="h-9 w-9 shrink-0 object-contain sm:h-10 sm:w-10" />
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            {{ S.digitalLibrary }}
                        </p>
                        <p class="truncate text-sm font-black text-blue-900 dark:text-blue-300 sm:text-base">
                            {{ S.universityShort }}
                        </p>
                    </div>
                </Link>

                <nav
                    class="ml-2 hidden min-w-0 flex-wrap items-center gap-1 lg:flex lg:gap-1.5"
                    :aria-label="S.mainNav"
                >
                    <template v-for="item in readerNavItems" :key="item.key">
                        <div v-if="item.children" class="group relative shrink-0">
                            <Link
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
                            :href="route(item.route)"
                            :class="[...navLinkClass(item), 'shrink-0']"
                        >
                            {{ item.label }}
                        </Link>
                    </template>
                </nav>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <ThemeToggle />
                <template v-if="!user">
                    <Link
                        :href="route('login')"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 sm:min-w-0"
                    >
                        {{ S.login }}
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-gradient-to-r from-blue-700 to-blue-900 px-4 text-sm font-semibold text-white shadow-md shadow-blue-900/20 hover:brightness-110"
                    >
                        {{ S.register }}
                    </Link>
                </template>
                <template v-else>
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
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Thông báo</h3>
                                <button
                                    v-if="unreadCount > 0"
                                    type="button"
                                    class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400"
                                    @click="markAllRead"
                                >
                                    Đánh dấu đã đọc
                                </button>
                            </div>
                            <div class="max-h-[340px] overflow-y-auto">
                                <button
                                    v-for="n in notifications"
                                    :key="n.id"
                                    type="button"
                                    class="flex w-full items-start gap-3 border-b border-slate-50 px-4 py-3 text-left transition hover:bg-slate-50 dark:border-slate-800/60 dark:hover:bg-slate-800/60"
                                    @click="markAsRead(n.id)"
                                >
                                    <div :class="['mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', getNotifIconBg(n.type)]">
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
                            </div>
                            <div class="border-t border-slate-100 bg-slate-50/80 px-4 py-2 text-center dark:border-slate-800 dark:bg-slate-800/40">
                                <Link
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
                        :personal-info-route-name="isStaff ? 'admin.profile' : 'reader.profile'"
                        :change-password-route-name="isStaff ? 'admin.change-password' : 'reader.change-password'"
                    >
                        <template #items>
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
                            :href="route(item.route)"
                            :class="[...navLinkClass(item), 'w-full justify-start']"
                            @click="mobileOpen = false"
                        >
                            {{ item.label }}
                        </Link>
                        <Link
                            v-for="c in item.children"
                            :key="c.key"
                            :href="route(c.route)"
                            :class="navChildLinkClass(c.route)"
                            @click="mobileOpen = false"
                        >
                            {{ c.label }}
                        </Link>
                    </div>
                    <Link
                        v-else
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
                        :href="route('reader.home')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ S.goToReader }}
                    </Link>
                    <Link
                        :href="route('reader.catalog')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ S.catalog }}
                    </Link>
                    <Link
                        v-if="hasRoute('reader.saved-books')"
                        :href="route('reader.saved-books')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ S.savedBooks }}
                    </Link>
                    <Link
                        v-if="isStaff"
                        :href="route('admin.dashboard')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-slate-900 text-sm font-semibold text-white dark:bg-blue-800"
                        @click="mobileOpen = false"
                    >
                        {{ S.goToApp }}
                    </Link>
                    <Link
                        v-if="(isStaff && hasRoute('admin.profile')) || (!isStaff && hasRoute('reader.profile'))"
                        :href="route(isStaff ? 'admin.profile' : 'reader.profile')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ accountMenuStrings.updatePersonalInfo }}
                    </Link>
                    <Link
                        v-if="(isStaff && hasRoute('admin.change-password')) || (!isStaff && hasRoute('reader.change-password'))"
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
                        :href="route('login')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-100"
                        @click="mobileOpen = false"
                    >
                        {{ S.login }}
                    </Link>
                    <Link
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
