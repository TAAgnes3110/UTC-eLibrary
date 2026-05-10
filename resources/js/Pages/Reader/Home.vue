<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { Icon } from '@iconify/vue'
import { readerLayoutStrings as S } from '@/config/readerStrings'
import ReaderPageHeading from '@/Components/Reader/ReaderPageHeading.vue'
import { useImageFallback } from '@/composables/useImageFallback'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)
const latestNews = computed(() => Array.isArray(page.props.latestNews) ? page.props.latestNews : [])
const latestNotices = computed(() => Array.isArray(page.props.latestNotices) ? page.props.latestNotices : [])
const latestBooks = computed(() => Array.isArray(page.props.latestBooks) ? page.props.latestBooks : [])
const DEFAULT_NEWS_COVER = '/images/default-news-cover.jpg'
const DEFAULT_BOOK_COVER = '/images/default-book-cover.png'
const { withFallback } = useImageFallback()

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
                    <h1 class="mt-3 max-w-2xl text-2xl font-black leading-tight sm:text-4xl">
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
                                prefetch
                                :href="route('login')"
                                class="inline-flex min-h-[48px] min-w-[48px] items-center justify-center rounded-xl bg-white px-6 text-sm font-bold text-blue-900 shadow-lg hover:bg-blue-50"
                            >
                                {{ S.login }}
                            </Link>
                            <Link
                                prefetch
                                :href="route('register')"
                                class="inline-flex min-h-[48px] min-w-[48px] items-center justify-center rounded-xl border-2 border-white/40 bg-white/10 px-6 text-sm font-bold text-white backdrop-blur-sm hover:bg-white/20"
                            >
                                {{ S.register }}
                            </Link>
                        </template>
                        <Link
                            prefetch
                            :href="route('reader.catalog')"
                            class="inline-flex min-h-[48px] items-center justify-center rounded-xl border border-white/25 px-6 text-sm font-semibold text-white/95 hover:bg-white/10"
                        >
                            {{ S.catalog }}
                        </Link>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                <div class="border-l-4 border-amber-500 bg-amber-50 px-3 py-2 text-base font-bold uppercase tracking-wide text-amber-900 dark:border-amber-400 dark:bg-amber-950/40 dark:text-amber-200">
                    Thông báo mới
                </div>
                <div v-if="latestNotices.length > 0" class="mt-5 space-y-5">
                    <Link
                        v-for="item in latestNotices"
                        :key="`notice-${item.id}`"
                        prefetch
                        :href="route('reader.news.show', item.slug)"
                        class="group flex flex-col gap-3 border-b border-slate-200 pb-4 last:border-b-0 last:pb-0 sm:flex-row dark:border-slate-700"
                    >
                        <img
                            :src="item.thumbnail_url || DEFAULT_NEWS_COVER"
                            alt="Ảnh thông báo"
                            @error="withFallback(DEFAULT_NEWS_COVER)($event)"
                            loading="lazy"
                            decoding="async"
                            class="h-24 w-full shrink-0 rounded-md border border-amber-200 object-cover dark:border-amber-800/50 sm:w-44"
                        />
                        <div class="min-w-0 space-y-2">
                            <p class="line-clamp-2 text-sm font-bold uppercase text-slate-900 group-hover:text-amber-700 group-hover:underline dark:text-slate-100 dark:group-hover:text-amber-300 sm:text-base">
                                {{ item.title }}
                            </p>
                            <p class="line-clamp-2 text-sm text-slate-700 dark:text-slate-300">
                                {{ item.excerpt || '' }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ item.published_at ? new Date(item.published_at).toLocaleDateString('vi-VN') : 'Chưa có ngày đăng' }}
                            </p>
                        </div>
                    </Link>
                </div>
                <div v-else class="mt-5 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    Chưa có thông báo mới.
                </div>
                <div class="mt-4">
                    <Link
                        prefetch
                        :href="route('reader.news.index', { type: 'notice' })"
                        class="inline-flex min-h-[44px] items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        Xem tất cả thông báo
                    </Link>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                <div class="border-l-4 border-blue-500 bg-blue-50 px-3 py-2 text-base font-bold uppercase tracking-wide text-blue-900 dark:border-blue-400 dark:bg-blue-950/40 dark:text-blue-200">
                    Tin tức mới
                </div>
                <div v-if="latestNews.length > 0" class="mt-5 space-y-5">
                    <Link
                        v-for="item in latestNews"
                        :key="item.id"
                        prefetch
                        :href="route('reader.news.show', item.slug)"
                        class="group flex flex-col gap-3 border-b border-slate-200 pb-4 last:border-b-0 last:pb-0 sm:flex-row"
                    >
                        <img
                            :src="item.thumbnail_url || DEFAULT_NEWS_COVER"
                            alt="Ảnh bài viết"
                            @error="withFallback(DEFAULT_NEWS_COVER)($event)"
                            loading="lazy"
                            decoding="async"
                            class="h-24 w-full shrink-0 rounded-md border border-blue-200 object-cover sm:w-44"
                        />
                        <div class="min-w-0 space-y-2">
                            <p class="line-clamp-2 text-sm font-bold uppercase text-slate-900 group-hover:text-blue-700 group-hover:underline dark:text-slate-100 dark:group-hover:text-blue-300 sm:text-base">
                                {{ item.title }}
                            </p>
                            <p class="line-clamp-2 text-sm text-slate-700 dark:text-slate-300">
                                {{ item.excerpt || '' }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ item.published_at ? new Date(item.published_at).toLocaleDateString('vi-VN') : 'Chưa có ngày đăng' }}
                            </p>
                        </div>
                    </Link>
                </div>
                <div v-else class="mt-5 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    Chưa có bài viết mới.
                </div>
                <div class="mt-4">
                    <Link
                        prefetch
                        :href="route('reader.news.index')"
                        class="inline-flex min-h-[44px] items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        Xem tất cả tin tức
                    </Link>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                <div class="border-l-4 border-emerald-500 bg-emerald-50 px-3 py-2 text-base font-bold uppercase tracking-wide text-emerald-900 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-200">
                    Sách mới cập nhật
                </div>
                <div v-if="latestBooks.length > 0" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <Link
                        v-for="book in latestBooks"
                        :key="book.id"
                        prefetch
                        :href="route('reader.catalog.show', book.id)"
                        class="group rounded-xl border border-slate-200 bg-slate-50 p-3 transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800"
                    >
                        <img
                            :src="book.cover_image || DEFAULT_BOOK_COVER"
                            alt="Bìa sách"
                            @error="withFallback(DEFAULT_BOOK_COVER)($event)"
                            loading="lazy"
                            decoding="async"
                            class="h-40 w-full rounded-md object-cover"
                        />
                        <div class="mt-3 space-y-1.5">
                            <p class="line-clamp-2 text-sm font-bold text-slate-900 group-hover:text-blue-700 dark:text-slate-100 dark:group-hover:text-blue-300">
                                {{ book.title }}
                            </p>
                            <p class="line-clamp-1 text-xs text-slate-600 dark:text-slate-300">
                                {{ book.authors_label || 'Đang cập nhật tác giả' }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ book.resource_type_label || 'Sách tham khảo' }}
                            </p>
                        </div>
                    </Link>
                </div>
                <div v-else class="mt-5 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    Chưa có sách mới.
                </div>
                <div class="mt-4">
                    <Link
                        prefetch
                        :href="route('reader.catalog')"
                        class="inline-flex min-h-[44px] items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        Xem toàn bộ sách
                    </Link>
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
                        prefetch
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
                        prefetch
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
                        prefetch
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
                        prefetch
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
                        prefetch
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
