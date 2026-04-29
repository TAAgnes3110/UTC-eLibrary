<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'

const tiles = [
    {
        key: 'library-card',
        title: 'Thẻ thư viện',
        description: 'Xem thông tin thẻ hiện có hoặc gửi yêu cầu cấp thẻ khi chưa có.',
        route: 'reader.services.library-card',
        icon: 'lucide:id-card',
        iconClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    },
    {
        key: 'loan-requests',
        title: 'Quản lý phiếu mượn',
        description: 'Theo dõi danh sách phiếu mượn và thao tác gia hạn theo trạng thái.',
        route: 'reader.services.loan-requests',
        icon: 'lucide:clipboard-list',
        iconClass: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    },
]
</script>

<template>
    <ReaderLayout>
        <Head title="Dịch vụ thư viện" />
        <div class="mx-auto max-w-5xl animate-in fade-in-50 duration-500">
            <article
                class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900 dark:shadow-black/20 sm:rounded-3xl"
            >
                <header
                    class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-blue-900 to-slate-900 px-5 py-10 text-white sm:px-8 sm:py-12"
                >
                    <div
                        class="pointer-events-none absolute inset-0 opacity-25"
                        style="background-image: url('/Image/utc_bg_v2.png'); background-size: cover; background-position: center"
                    />
                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="min-w-0 max-w-3xl">
                            <h1 class="text-3xl font-black leading-tight tracking-tight sm:text-4xl">
                                Dịch vụ thư viện
                            </h1>
                            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-blue-100/95 sm:text-base">
                                Chọn nhóm dịch vụ để thao tác nhanh theo từng nghiệp vụ, tách riêng giống luồng quản trị.
                            </p>
                        </div>
                        <Link
                            :href="route('reader.home')"
                            class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 self-start rounded-xl border border-white/35 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 lg:self-auto"
                        >
                            <Icon icon="lucide:arrow-left" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            Về trang chủ
                        </Link>
                    </div>
                </header>

                <div class="border-t border-slate-100 px-4 py-8 dark:border-slate-800 sm:px-6 sm:py-10">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="t in tiles"
                            :key="t.key"
                            :href="route(t.route)"
                            class="group flex min-h-[44px] flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:border-blue-300 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-800/80 dark:hover:border-blue-600 dark:hover:bg-slate-800"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl"
                                :class="t.iconClass"
                            >
                                <Icon :icon="t.icon" class="h-7 w-7" aria-hidden="true" />
                            </div>
                            <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-blue-900 dark:text-white dark:group-hover:text-blue-200">
                                {{ t.title }}
                            </h2>
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                {{ t.description }}
                            </p>
                            <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-800 dark:text-blue-400">
                                Xem chi tiết
                                <Icon icon="lucide:arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" aria-hidden="true" />
                            </span>
                        </Link>
                    </div>
                </div>
            </article>
        </div>
    </ReaderLayout>
</template>
