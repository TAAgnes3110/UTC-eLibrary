<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue'
import { readerRelatedBooksPageStrings as P } from '@/config/readerStrings'
import { useImageFallback } from '@/composables/useImageFallback'

const props = defineProps({
    source_book: { type: Object, required: true },
    books: { type: Object, required: true },
})

const sourceBook = computed(() => props.source_book ?? {})
const books = computed(() => props.books)
const { withFallback } = useImageFallback()

const paginationSummary = computed(() => {
    const b = books.value
    const total = Number(b?.total ?? 0)
    const from = Number(b?.from ?? 0)
    const to = Number(b?.to ?? 0)
    if (!Number.isFinite(total) || total < 1 || !from || !to) {
        return ''
    }
    const fromStr = from.toLocaleString('vi-VN')
    const toStr = to.toLocaleString('vi-VN')
    const totalStr = total.toLocaleString('vi-VN')
    return P.paginationSummary.replace('{from}', fromStr).replace('{to}', toStr).replace('{total}', totalStr)
})

const showPagination = computed(() => Number(books.value?.last_page ?? 1) > 1)

const headTitle = computed(() => {
    const title = String(sourceBook.value?.title ?? '').trim()
    return title ? `${P.pageTitle} — ${title}` : P.headTitleSuffix
})

function categoryLabel(b) {
    const label = String(b?.category_label ?? '').trim()
    if (label) {
        return label
    }
    if (b?.resource_type === 'digital' || b?.is_digital === true) {
        return 'Đồ án, luận văn'
    }
    return String(b?.classification_name || b?.resource_type_label || '—').trim() || '—'
}

function formatViewCount(b) {
    const n = Number(b?.view_count ?? 0)
    return Number.isFinite(n) && n >= 0 ? n.toLocaleString('vi-VN') : '0'
}

function goPage(p) {
    const last = Number(books.value?.last_page ?? 1)
    if (p < 1 || p > last) {
        return
    }
    router.get(
        route('reader.catalog.related', { book: sourceBook.value.id }),
        { page: p },
        {
            preserveState: false,
            preserveScroll: true,
            replace: true,
            only: ['books'],
        }
    )
}
</script>

<template>
    <ReaderLayout>
        <Head :title="headTitle" />
        <div class="mx-auto max-w-7xl animate-in fade-in-50 duration-500">
            <nav class="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-600 dark:text-slate-400 sm:text-sm" aria-label="Breadcrumb">
                <Link :href="route('reader.catalog')" class="font-medium hover:text-blue-800 dark:hover:text-blue-400">
                    {{ P.breadcrumbCatalog }}
                </Link>
                <span aria-hidden="true">/</span>
                <Link
                    :href="route('reader.catalog.show', { book: sourceBook.id })"
                    class="max-w-[min(100%,14rem)] truncate font-medium hover:text-blue-800 dark:hover:text-blue-400 sm:max-w-md"
                >
                    {{ sourceBook.title }}
                </Link>
                <span aria-hidden="true">/</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ P.pageTitle }}</span>
            </nav>

            <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        {{ P.pageTitle }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400">
                        {{ P.pageHint }}
                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ sourceBook.title }}</span>.
                    </p>
                </div>
                <Link
                    :href="route('reader.catalog.show', { book: sourceBook.id })"
                    class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-blue-900 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-600 dark:bg-slate-900 dark:text-blue-100 dark:hover:bg-slate-800"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" aria-hidden="true" />
                    {{ P.backToBook }}
                </Link>
            </header>

            <div
                v-if="!books?.data?.length"
                class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center dark:border-slate-600 dark:bg-slate-800/50"
            >
                <Icon icon="lucide:library-big" class="mx-auto h-12 w-12 text-slate-400" aria-hidden="true" />
                <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-200">
                    {{ P.emptyTitle }}
                </p>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                    {{ P.emptyHint }}
                </p>
            </div>

            <div v-else>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link
                        v-for="b in books.data"
                        :key="b.id"
                        :href="route('reader.catalog.show', { book: b.id })"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-600"
                    >
                        <div class="relative aspect-[3/4] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                            <img
                                v-if="b.cover_image"
                                :src="b.cover_image"
                                :alt="b.title"
                                class="h-full w-full object-cover transition group-hover:scale-[1.02]"
                                loading="lazy"
                                decoding="async"
                                @error="withFallback('/images/default-book-cover.png')($event)"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                <Icon icon="lucide:book-open" class="h-16 w-16 opacity-50" aria-hidden="true" />
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <h2 class="line-clamp-2 min-h-[2.5rem] text-sm font-bold leading-snug text-slate-900 dark:text-white">
                                {{ b.title }}
                            </h2>
                            <p class="mt-2 line-clamp-1 text-xs text-slate-600 dark:text-slate-400">
                                {{ b.authors_label || '—' }}
                            </p>
                            <p class="mt-3 line-clamp-2 text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="font-semibold text-slate-600 dark:text-slate-300">{{ P.categoryLabel }}:</span>
                                {{ categoryLabel(b) }}
                            </p>
                            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    <Icon icon="lucide:eye" class="h-3 w-3 shrink-0 opacity-80" aria-hidden="true" />
                                    {{ formatViewCount(b) }} {{ P.viewsLabel }}
                                </span>
                                <span class="text-xs font-semibold text-blue-800 dark:text-blue-400">{{ P.seeDetail }} →</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <p
                    v-if="paginationSummary"
                    class="mt-6 text-center text-xs text-slate-600 dark:text-slate-400 sm:text-sm"
                >
                    {{ paginationSummary }}
                </p>

                <AdminPaginationBar
                    v-if="showPagination"
                    class="mt-4"
                    :current-page="books.current_page"
                    :last-page="books.last_page"
                    @go-page="goPage"
                />
            </div>
        </div>
    </ReaderLayout>
</template>
