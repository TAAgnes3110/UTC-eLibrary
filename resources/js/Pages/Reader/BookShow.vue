<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerBookShowStrings as S } from '@/config/readerStrings'
import { toast } from '@/store/toast'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)
const saving = ref(false)

const props = defineProps({
    book: { type: Object, required: true },
    availability: {
        type: Object,
        required: true,
        validator: (v) => v && typeof v.total === 'number',
    },
    is_saved: { type: Boolean, default: false },
})

const saved = ref(props.is_saved)
watch(
    () => props.is_saved,
    (value) => {
        saved.value = value
    }
)

const savedBookEndpoint = computed(() => `${route('reader.catalog.show', { book: props.book.id })}/luu`)

function toggleSave() {
    if (!isAuthed.value || saving.value) {
        return
    }
    const previous = saved.value
    const next = !previous
    saved.value = next
    saving.value = true
    const opts = {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            toast.success(previous ? 'Đã bỏ sách khỏi danh sách lưu.' : 'Đã lưu sách thành công.', {
                title: 'Sách đã lưu',
            })
        },
        onError: () => {
            saved.value = previous
            toast.error('Không thể cập nhật danh sách lưu. Vui lòng thử lại.', {
                title: 'Sách đã lưu',
            })
        },
        onFinish: () => {
            saving.value = false
        },
    }
    if (previous) {
        router.delete(savedBookEndpoint.value, opts)
    } else {
        router.post(savedBookEndpoint.value, {}, opts)
    }
}

const publicationLine = computed(() => {
    const b = props.book
    const parts = []
    if (b.publishers_label) {
        parts.push(b.publishers_label)
    }
    if (b.published_year) {
        parts.push(String(b.published_year))
    }
    if (b.publisher_place) {
        parts.push(b.publisher_place)
    }
    return parts.length ? parts.join(' — ') : '—'
})

const physicalLine = computed(() => {
    const b = props.book
    const parts = []
    if (b.pages) {
        parts.push(`${b.pages} tr.`)
    }
    if (b.book_size) {
        parts.push(b.book_size)
    }
    return parts.length ? parts.join(' · ') : '—'
})

const priceFmt = computed(() => {
    const n = Number(props.book.price)
    if (!Number.isFinite(n)) {
        return '—'
    }
    return new Intl.NumberFormat('vi-VN').format(n) + ' đ'
})

const subjectLine = computed(() => {
    const b = props.book
    const c = b.classification?.name
    return c || '—'
})

const keywordLine = computed(() => {
    const k = props.book.thesis_metadata?.keywords
    if (k && String(k).trim() !== '') {
        return String(k)
    }
    return '—'
})

const headTitle = computed(() => `${props.book.title} — ${S.headTitleSuffix}`)
</script>

<template>
    <ReaderLayout>
        <Head :title="headTitle" />
        <div class="mx-auto max-w-5xl animate-in fade-in-50 duration-500">
            <div class="mb-4">
                <Link
                    :href="route('reader.catalog')"
                    class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-400"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ S.backCatalog }}
                </Link>
            </div>
            <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-400" aria-label="Breadcrumb">
                <Link :href="route('reader.home')" class="font-medium hover:text-blue-800 dark:hover:text-blue-400">{{
                    S.breadcrumbHome
                }}</Link>
                <span aria-hidden="true">/</span>
                <Link :href="route('reader.catalog')" class="font-medium hover:text-blue-800 dark:hover:text-blue-400">{{
                    S.breadcrumbCatalog
                }}</Link>
                <span aria-hidden="true">/</span>
                <span class="line-clamp-1 font-semibold text-slate-900 dark:text-slate-100">{{ book.title }}</span>
            </nav>

            <article
                class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg dark:border-slate-700/80 dark:bg-slate-900"
            >
                <div class="grid gap-8 p-5 sm:p-8 lg:grid-cols-[240px_1fr] lg:gap-10">
                    <div class="relative mx-auto w-full max-w-[240px] shrink-0">
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
                            <img
                                v-if="book.cover_image"
                                :src="book.cover_image"
                                :alt="book.title"
                                class="aspect-[3/4] w-full object-cover"
                            />
                            <div v-else class="flex aspect-[3/4] w-full items-center justify-center text-slate-400">
                                <Icon icon="lucide:book-open" class="h-20 w-20 opacity-40" aria-hidden="true" />
                                <span class="sr-only">{{ S.noCover }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <h1 class="text-2xl font-black leading-tight text-slate-900 dark:text-white sm:text-3xl">
                            {{ book.title }}
                        </h1>
                        <p v-if="book.sub_title" class="mt-2 text-base text-slate-600 dark:text-slate-300">
                            {{ book.sub_title }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                            >
                                {{ book.resource_type_label }}
                            </span>
                            <span
                                v-if="book.book_code"
                                class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-600 dark:text-slate-400"
                            >
                                {{ book.book_code }}
                            </span>
                        </div>

                        <dl class="mt-6 space-y-3 text-sm">
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.authors }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ book.authors_label || '—' }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.publicationInfo }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ publicationLine }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.physicalDesc }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ physicalLine }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.price }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ priceFmt }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.subject }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ subjectLine }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.keywords }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ keywordLine }}</dd>
                            </div>
                        </dl>

                        <p class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-200">
                            {{ S.borrowAtDeskHint }}
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <template v-if="!isAuthed">
                                <Link
                                    :href="route('login')"
                                    class="inline-flex min-h-[48px] min-w-[48px] items-center justify-center gap-2 rounded-xl bg-blue-900 px-6 text-sm font-bold text-white hover:bg-blue-800"
                                >
                                    <Icon icon="lucide:bookmark" class="h-5 w-5 shrink-0" aria-hidden="true" />
                                    {{ S.saveCta }}
                                </Link>
                            </template>
                            <template v-else>
                                <button
                                    type="button"
                                    class="inline-flex min-h-[48px] min-w-[48px] items-center justify-center gap-2 rounded-xl px-6 text-sm font-bold transition-colors"
                                    :class="
                                        saved
                                            ? 'border-2 border-blue-900 bg-white text-blue-900 hover:bg-blue-50 dark:border-blue-400 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-slate-800'
                                            : 'bg-blue-900 text-white hover:bg-blue-800'
                                    "
                                    :disabled="saving"
                                    :aria-busy="saving"
                                    @click="toggleSave"
                                >
                                    <Icon
                                        :icon="saved ? 'lucide:bookmark-check' : 'lucide:bookmark'"
                                        class="h-5 w-5 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ saved ? S.savedCta : S.saveCta }}
                                </button>
                            </template>
                        </div>
                        <p v-if="!isAuthed" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ S.loginToSave }}
                        </p>
                        <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ S.saveHelpAuthed }}
                        </p>
                    </div>
                </div>

                <div class="border-t border-slate-100 px-5 py-6 dark:border-slate-800 sm:px-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ S.availabilityTitle }}
                    </h2>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <span class="inline-flex rounded-full bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                            {{ S.totalCopies }}: {{ availability.total }}
                        </span>
                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                            {{ S.availableCopies }}: {{ availability.available }}
                        </span>
                        <span class="inline-flex rounded-full bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-900 dark:bg-rose-950 dark:text-rose-200">
                            {{ S.borrowedCopies }}: {{ availability.borrowed }}
                        </span>
                    </div>
                </div>

                <div v-if="book.summary" class="border-t border-slate-100 px-5 py-6 dark:border-slate-800 sm:px-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ S.summaryTitle }}
                    </h2>
                    <p class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                        {{ book.summary }}
                    </p>
                </div>

                <div
                    v-if="Array.isArray(book.digital_assets) && book.digital_assets.length"
                    class="border-t border-slate-100 px-5 py-6 dark:border-slate-800 sm:px-8"
                >
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ S.digitalAssets }}
                    </h2>
                    <ul class="mt-3 list-inside list-disc text-sm text-slate-700 dark:text-slate-300">
                        <li v-for="(asset, idx) in book.digital_assets" :key="asset.id ?? idx">
                            {{ asset.original_name || 'Tài liệu' }}
                        </li>
                    </ul>
                </div>

            </article>
        </div>
    </ReaderLayout>
</template>
