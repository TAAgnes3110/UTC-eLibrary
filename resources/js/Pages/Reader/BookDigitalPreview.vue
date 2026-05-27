<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerBookDigitalPreviewStrings as S } from '@/config/readerStrings'

const props = defineProps({
    book: { type: Object, required: true },
    asset: { type: Object, required: true },
    pages: { type: Array, default: () => [] },
    back_url: { type: String, required: true },
    preview_state: { type: String, default: 'ready' },
    preview_message: { type: String, default: '' },
})

const previewUnavailable = computed(() => props.preview_state !== 'ready')

const unavailableTitle = computed(() => {
    const state = String(props.preview_state || '')
    if (state === 'pending' || state === 'processing') {
        return S.previewPendingTitle
    }
    return S.previewUnavailableTitle
})

const documentTitle = computed(() => {
    const name = String(props.asset?.original_name || '').trim()
    const bookTitle = String(props.book?.title || '').trim()
    if (name && bookTitle) {
        return `${name} — ${bookTitle}`
    }
    return name || bookTitle || S.defaultTitle
})

const normalizePreviewPageImageUrl = (row) => {
    const pageNo = Number(row?.page ?? 0)
    const raw = row?.image_url ? String(row.image_url).trim() : ''
    if (!raw || pageNo < 1) {
        return ''
    }

    if (/^https?:\/\//i.test(raw)) {
        return raw
    }

    if (/^\d+\.png$/i.test(raw)) {
        return `/tra-cuu-sach/${props.book.id}/tai-lieu/${props.asset.id}/xem-truoc/trang/${pageNo}.png`
    }

    if (raw.startsWith('/')) {
        return raw
    }

    if (raw.startsWith('tra-cuu-sach/')) {
        return `/${raw}`
    }

    return raw
}

const previewPages = computed(() =>
    (props.pages || [])
        .map((row) => ({
            page: Number(row?.page ?? 0),
            image_url: normalizePreviewPageImageUrl(row),
        }))
        .filter((row) => row.page > 0 && row.image_url)
)
</script>

<template>
    <ReaderLayout :title="S.pageTitle" full-width>
        <Head :title="`${S.pageTitle} — ${documentTitle}`" />

        <div class="flex min-h-0 flex-1 flex-col gap-3">
            <div
                class="flex shrink-0 flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white px-3 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ S.previewBadge }}
                    </p>
                    <h1 class="truncate text-base font-bold text-slate-900 dark:text-slate-100 sm:text-lg">
                        {{ documentTitle }}
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                        {{ S.previewHint }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-amber-800 dark:text-amber-300">
                        {{ S.paywallNote }}
                    </p>
                </div>
                <Link
                    :href="back_url"
                    class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                >
                    <Icon icon="lucide:arrow-left" class="h-5 w-5" aria-hidden="true" />
                    {{ S.backToBook }}
                </Link>
            </div>

            <div
                class="min-h-[min(72dvh,720px)] flex-1 space-y-6 overflow-y-auto rounded-xl border border-slate-200/80 bg-slate-100 p-3 dark:border-slate-700 dark:bg-slate-900/80 sm:p-5"
            >
                <article
                    v-for="row in previewPages"
                    :key="row.page"
                    class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-950"
                >
                    <p
                        class="border-b border-slate-200/80 bg-slate-50 px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                    >
                        {{ S.pageLabel }} {{ row.page }}
                    </p>
                    <img
                        :src="row.image_url"
                        :alt="`${S.pageLabel} ${row.page}`"
                        class="mx-auto block h-auto w-full max-w-[920px] bg-white"
                        loading="lazy"
                        decoding="async"
                    />
                </article>

                <div
                    v-if="previewUnavailable"
                    class="mx-auto max-w-lg py-16 text-center"
                >
                    <Icon
                        :icon="preview_state === 'pending' || preview_state === 'processing' ? 'lucide:loader-circle' : 'lucide:file-x-2'"
                        :class="[
                            'mx-auto h-12 w-12 text-slate-400 dark:text-slate-500',
                            preview_state === 'pending' || preview_state === 'processing' ? 'animate-spin' : '',
                        ]"
                        aria-hidden="true"
                    />
                    <h2 class="mt-4 text-base font-bold text-slate-900 dark:text-slate-100">
                        {{ unavailableTitle }}
                    </h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ preview_message || S.loadError }}
                    </p>
                </div>
                <p
                    v-else-if="!previewPages.length"
                    class="py-12 text-center text-sm text-slate-600 dark:text-slate-400"
                >
                    {{ S.loadError }}
                </p>
            </div>
        </div>
    </ReaderLayout>
</template>
