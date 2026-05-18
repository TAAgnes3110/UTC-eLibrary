<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { readerBookShowStrings as S } from '@/config/readerStrings'
import { useImageFallback } from '@/composables/useImageFallback'

const props = defineProps({
    books: { type: Array, default: () => [] },
    sourceBookId: { type: [Number, String], required: true },
})

const items = computed(() => (Array.isArray(props.books) ? props.books : []))

const seeMoreHref = computed(() => {
    const id = Number(props.sourceBookId)
    if (!Number.isFinite(id) || id < 1) {
        return '/tra-cuu-sach'
    }
    try {
        return route('reader.catalog.related', { book: id })
    } catch {
        return `/tra-cuu-sach/${id}/lien-quan`
    }
})

const { withFallback } = useImageFallback()

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

function viewCount(b) {
    const n = Number(b?.view_count ?? 0)
    return Number.isFinite(n) && n >= 0 ? n.toLocaleString('vi-VN') : '0'
}
</script>

<template>
    <section
        v-if="items.length"
        class="mt-10 border-t border-slate-200 pt-8 dark:border-slate-700"
        aria-labelledby="related-books-heading"
    >
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 sm:mb-6">
            <div class="flex min-w-0 items-start gap-3">
                <span class="mt-1 hidden h-8 w-1 shrink-0 rounded-full bg-blue-800 sm:block dark:bg-blue-500" aria-hidden="true" />
                <div>
                    <h2 id="related-books-heading" class="text-lg font-bold text-slate-900 dark:text-white sm:text-xl">
                        {{ S.relatedBooksTitle }}
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-slate-600 dark:text-slate-400 sm:text-sm">
                        {{ S.relatedBooksHint }}
                    </p>
                </div>
            </div>
            <Link
                :href="seeMoreHref"
                class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-1 rounded-lg border border-blue-200 bg-white px-4 text-sm font-bold text-blue-900 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 dark:border-blue-800 dark:bg-slate-900 dark:text-blue-100 dark:hover:bg-blue-950/60"
            >
                {{ S.relatedBooksSeeMore }}
                <Icon icon="lucide:arrow-right" class="h-4 w-4" aria-hidden="true" />
            </Link>
        </div>

        <div class="hidden gap-4 sm:grid sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            <Link
                v-for="b in items"
                :key="`grid-${b.id}`"
                :href="route('reader.catalog.show', { book: b.id })"
                class="group flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-600"
            >
                <div class="relative aspect-[3/4] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                    <img
                        v-if="b.cover_image"
                        :src="b.cover_image"
                        :alt="b.title"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        loading="lazy"
                        decoding="async"
                        @error="withFallback('/images/default-book-cover.png')($event)"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                        <Icon icon="lucide:book-open" class="h-10 w-10 opacity-50" aria-hidden="true" />
                    </div>
                </div>
                <div class="flex flex-1 flex-col p-3">
                    <h3 class="line-clamp-2 min-h-[2.5rem] text-sm font-bold leading-snug text-slate-900 dark:text-white">
                        {{ b.title }}
                    </h3>
                    <p class="mt-1 line-clamp-1 text-xs text-slate-600 dark:text-slate-400">
                        {{ b.authors_label || '—' }}
                    </p>
                    <p class="mt-2 line-clamp-1 text-[11px] text-slate-500 dark:text-slate-400">
                        {{ categoryLabel(b) }}
                    </p>
                    <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-2 dark:border-slate-800">
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                            <Icon icon="lucide:eye" class="h-3 w-3 opacity-80" aria-hidden="true" />
                            {{ viewCount(b) }}
                        </span>
                        <span class="text-[11px] font-bold text-blue-800 dark:text-blue-400">{{ S.seeDetail }} →</span>
                    </div>
                </div>
            </Link>
        </div>

        <div class="-mx-1 overflow-x-auto pb-2 sm:hidden">
            <div class="flex min-w-min snap-x snap-mandatory gap-3 px-1">
                <Link
                    v-for="b in items"
                    :key="`scroll-${b.id}`"
                    :href="route('reader.catalog.show', { book: b.id })"
                    class="group w-[9.5rem] shrink-0 snap-start overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div class="relative aspect-[3/4] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <img
                            v-if="b.cover_image"
                            :src="b.cover_image"
                            :alt="b.title"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                            @error="withFallback('/images/default-book-cover.png')($event)"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                            <Icon icon="lucide:book-open" class="h-8 w-8 opacity-50" aria-hidden="true" />
                        </div>
                    </div>
                    <div class="p-2.5">
                        <h3 class="line-clamp-2 text-xs font-bold leading-snug text-slate-900 dark:text-white">
                            {{ b.title }}
                        </h3>
                        <p class="mt-1 line-clamp-1 text-[10px] text-slate-500">{{ b.authors_label || '—' }}</p>
                    </div>
                </Link>
            </div>
        </div>
    </section>
</template>
