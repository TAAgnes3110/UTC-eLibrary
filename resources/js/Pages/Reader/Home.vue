<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { Icon } from '@iconify/vue'
import { readerLayoutStrings as S } from '@/config/readerStrings'
import ReaderPageHeading from '@/Components/Reader/ReaderPageHeading.vue'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)

const servicesUrl = (hash) => `${route('reader.services')}${hash}`
</script>

<template>
    <ReaderLayout>
        <Head title="Trang chủ - Thư viện số UTC" />
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-950 via-blue-900 to-slate-900 text-white dark:border-slate-700">
                <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: url('/Image/utc_bg_v2.png'); background-size: cover; background-position: center" />
                <div class="relative px-5 py-10 sm:px-8 sm:py-14">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200/90">UTC eLibrary</p>
                    <h1 class="mt-3 max-w-2xl text-3xl font-black leading-tight sm:text-4xl">
                        Thư viện số Đại học Giao thông Vận tải
                    </h1>
                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-blue-100/95 sm:text-base">
                        Cổng tra cứu và dịch vụ trực tuyến của thư viện. Hạn mượn, số lượng và điều kiện theo
                        <Link :href="route('reader.regulations.borrowing')" class="font-semibold underline-offset-2 hover:underline">chính sách mượn</Link>
                        trong hệ thống.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <template v-if="!isAuthed">
                            <Link
                                :href="route('login')"
                                class="inline-flex min-h-[48px] min-w-[48px] items-center justify-center rounded-xl bg-white px-6 text-sm font-bold text-blue-900 shadow-lg hover:bg-blue-50"
                            >
                                {{ S.login }}
                            </Link>
                            <Link
                                :href="route('register')"
                                class="inline-flex min-h-[48px] min-w-[48px] items-center justify-center rounded-xl border-2 border-white/40 bg-white/10 px-6 text-sm font-bold text-white backdrop-blur-sm hover:bg-white/20"
                            >
                                {{ S.register }}
                            </Link>
                        </template>
                        <Link
                            :href="route('reader.catalog')"
                            class="inline-flex min-h-[48px] items-center justify-center rounded-xl border border-white/25 px-6 text-sm font-semibold text-white/95 hover:bg-white/10"
                        >
                            {{ S.catalog }}
                        </Link>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                <ReaderPageHeading title="Dịch vụ">
                    <template #description>
                        Các nhóm dịch vụ đang triển khai trên eLibrary. Chi tiết theo từng đối tượng độc giả xem mục Quy định.
                    </template>
                </ReaderPageHeading>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <Link
                                :href="route('reader.catalog')"
                                class="group flex min-h-[120px] flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                            >
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                    <Icon icon="lucide:search" class="h-6 w-6" />
                                </div>
                                <h3 class="mt-3 font-bold text-slate-900 dark:text-white">Tra cứu sách</h3>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    Tìm kiếm đầu mục; đăng nhập để lưu sách vào danh sách nhớ khi ra quầy mượn.
                                </p>
                            </Link>
                            <Link
                                :href="servicesUrl('#cap-the')"
                                class="group flex min-h-[120px] flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                            >
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <Icon icon="lucide:id-card" class="h-6 w-6" />
                                </div>
                                <h3 class="mt-3 font-bold text-slate-900 dark:text-white">Cấp thẻ thư viện</h3>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Đăng ký tài khoản và thủ tục thẻ đọc theo quy trình thư viện.</p>
                            </Link>
                            <Link
                                :href="servicesUrl('#muon-sach')"
                                class="group flex min-h-[120px] flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                            >
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                                    <Icon icon="lucide:book-open" class="h-6 w-6" />
                                </div>
                                <h3 class="mt-3 font-bold text-slate-900 dark:text-white">Mượn – trả</h3>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Quy trình mượn, gia hạn và trả sau khi đăng nhập.</p>
                            </Link>
                            <Link
                                :href="servicesUrl('#doc-tai-cho')"
                                class="group flex min-h-[120px] flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                            >
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">
                                    <Icon icon="lucide:armchair" class="h-6 w-6" />
                                </div>
                                <h3 class="mt-3 font-bold text-slate-900 dark:text-white">{{ S.homeServiceReadOnSite }}</h3>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ S.homeServiceReadOnSiteDesc }}</p>
                            </Link>
                            <Link
                                :href="servicesUrl('#muon-ve-nha')"
                                class="group flex min-h-[120px] flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 sm:max-xl:col-span-2 xl:col-span-1"
                            >
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300">
                                    <Icon icon="lucide:home" class="h-6 w-6" />
                                </div>
                                <h3 class="mt-3 font-bold text-slate-900 dark:text-white">{{ S.homeServiceBorrowHome }}</h3>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ S.homeServiceBorrowHomeDesc }}</p>
                            </Link>
                </div>
            </section>
        </div>
    </ReaderLayout>
</template>
