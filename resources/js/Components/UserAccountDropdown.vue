<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { Button } from '@/Components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { cn } from '@/lib/utils'
import { accountMenuStrings as M } from '@/config/accountMenuStrings'
import { clearClientApiCredentials } from '@/utils/apiAuthStorage'
import { clearStaffWorkQueueSessionHint } from '@/utils/staffWorkQueueHint'

const props = defineProps({
    /** Dữ liệu user từ Inertia `auth.user` */
    user: { type: Object, required: true },
    /** `compact` (header admin), `touch` (reader — ≥44px) */
    triggerSize: { type: String, default: 'compact' },
    /** Ghi đè aria-label nút avatar */
    ariaLabel: { type: String, default: '' },
    /** Tên route Inertia tới trang cập nhật thông tin (vd. `admin.profile` hoặc `reader.profile`) */
    personalInfoRouteName: { type: String, default: 'admin.profile' },
    /** Inertia route: `admin.change-password` or `reader.change-password`. */
    changePasswordRouteName: { type: String, default: '' },
})

const strings = computed(() => ({
    accountMenu: props.ariaLabel || M.accountMenu,
    updatePersonalInfo: M.updatePersonalInfo,
    changePassword: M.changePassword,
    logout: M.logout,
}))

const hasRoute = (name) => {
    try {
        route(name)
        return true
    } catch {
        return false
    }
}

const canUpdatePersonalInfo = computed(() => hasRoute(props.personalInfoRouteName))

const goUpdatePersonalInfo = () => {
    router.visit(route(props.personalInfoRouteName))
}

const canChangePassword = computed(() => props.changePasswordRouteName !== '' && hasRoute(props.changePasswordRouteName))

const goChangePassword = () => {
    router.visit(route(props.changePasswordRouteName))
}

const logout = () => {
    clearStaffWorkQueueSessionHint()
    router.post(route('logout'), {}, {
        onSuccess: () => {
            clearClientApiCredentials()
            if (hasRoute('reader.home')) {
                router.visit(route('reader.home'))
                return
            }
            router.visit('/')
        },
    })
}

const triggerClass = computed(() =>
    cn(
        'shrink-0 overflow-hidden rounded-full border border-slate-200/50 bg-slate-100 p-0 ring-0 hover:bg-slate-200 focus-visible:ring-2 focus-visible:ring-blue-500/30 dark:border-slate-700/50 dark:bg-slate-800 dark:hover:bg-slate-700',
        props.triggerSize === 'touch' ? 'h-11 w-11' : 'h-9 w-9',
    ),
)
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button type="button" variant="ghost" :aria-label="strings.accountMenu" :class="triggerClass">
                <img
                    :src="user?.avatar || '/images/default-avatar.png'"
                    :alt="user?.name || 'Avatar'"
                    class="h-full w-full object-cover"
                />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            class="w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 shadow-xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/30"
        >
            <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/60">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/40 dark:text-blue-400"
                    >
                        {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                            {{ user?.name }}
                        </p>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                            {{ user?.email }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="py-2">
                <DropdownMenuItem
                    v-if="canUpdatePersonalInfo"
                    class="mx-2 cursor-pointer rounded-xl px-3 py-2.5 text-sm focus:bg-blue-50 focus:text-blue-700 dark:focus:bg-slate-800 dark:focus:text-blue-300"
                    @click="goUpdatePersonalInfo"
                >
                    <Icon icon="lucide:user-pen" class="mr-3 h-4 w-4 shrink-0 text-blue-500 dark:text-blue-400" />
                    <span>{{ strings.updatePersonalInfo }}</span>
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="canChangePassword"
                    class="mx-2 cursor-pointer rounded-xl px-3 py-2.5 text-sm focus:bg-slate-100 dark:focus:bg-slate-800"
                    @click="goChangePassword"
                >
                    <Icon icon="lucide:key-round" class="mr-3 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                    <span class="text-slate-700 dark:text-slate-300">{{ strings.changePassword }}</span>
                </DropdownMenuItem>
                <slot name="items" />
            </div>

            <DropdownMenuSeparator class="bg-slate-100 dark:bg-slate-800" />

            <div class="p-2">
                <button
                    type="button"
                    class="mx-2 flex w-full cursor-pointer items-center rounded-xl px-3 py-2.5 text-sm text-rose-600 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none focus:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-950/40 dark:focus:bg-rose-950/40 dark:focus:text-rose-300"
                    @click="logout"
                >
                    <Icon icon="lucide:log-out" class="mr-3 h-4 w-4 shrink-0" />
                    <span>{{ strings.logout }}</span>
                </button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
