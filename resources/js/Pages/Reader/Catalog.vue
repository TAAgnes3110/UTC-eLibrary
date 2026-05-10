<script setup>
import { computed, reactive, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue'
import { readerCatalogPageStrings as C, readerLayoutStrings as L } from '@/config/readerStrings'
import { useImageFallback } from '@/composables/useImageFallback'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)

const q = reactive({
    keyword: '',
    resource_type: '',
    classification_id: '',
    stock: '',
    sort: 'newest',
})

function syncFromProps() {
    const f = page.props.filters ?? {}
    q.keyword = f.keyword ?? ''
    q.resource_type = f.resource_type != null && f.resource_type !== '' ? String(f.resource_type) : ''
    q.classification_id = f.classification_id != null && f.classification_id !== '' ? String(f.classification_id) : ''
    q.stock = f.stock != null && f.stock !== '' ? String(f.stock) : ''
    q.sort = f.sort === 'oldest' ? 'oldest' : 'newest'
}

syncFromProps()
watch(
    () => page.props.filters,
    () => {
        syncFromProps()
    },
    { deep: true }
)

const classifications = computed(() => page.props.classifications ?? [])
const resourceTypeOptions = computed(() => page.props.resourceTypeOptions ?? [{ value: '', label: C.filterResourceType }])
const books = computed(() => page.props.books)
const { withFallback } = useImageFallback()

function onClassificationChange() {
    submitSearch()
}

function buildQuery(pageOverride) {
    const payload = {}
    if (q.keyword?.trim()) {
        payload.keyword = q.keyword.trim()
    }
    if (q.resource_type) {
        payload.resource_type = q.resource_type
    }
    if (q.classification_id) {
        payload.classification_id = q.classification_id
    }
    if (q.stock) {
        payload.stock = q.stock
    }
    if (q.sort) {
        payload.sort = q.sort
    }
    if (pageOverride != null) {
        payload.page = pageOverride
    }
    return payload
}

function submitSearch() {
    router.get(route('reader.catalog'), buildQuery(1), {
        preserveState: false,
        preserveScroll: false,
        replace: true,
        only: ['books', 'filters'],
    })
}

function goPage(p) {
    if (p < 1) {
        return
    }
    router.get(route('reader.catalog'), buildQuery(p), {
        preserveState: false,
        preserveScroll: true,
        replace: true,
        only: ['books', 'filters'],
    })
}
</script>

<template>
    <ReaderLayout>
        <Head :title="C.headTitle" />
        <div class="mx-auto max-w-7xl animate-in fade-in-50 duration-500">
            <header class="mb-6">
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                    {{ C.heroTitle }}
                </h1>
            </header>

            <form
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5"
                @submit.prevent="submitSearch"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-stretch lg:gap-2">
                    <div class="min-w-0 sm:min-w-[200px] lg:max-w-xs">
                        <label class="sr-only" for="catalog-resource-type">{{ C.filterResourceType }}</label>
                        <select
                            id="catalog-resource-type"
                            v-model="q.resource_type"
                            @change="submitSearch"
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        >
                            <option v-for="opt in resourceTypeOptions" :key="String(opt.value) + opt.label" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div class="min-w-0 flex-1">
                        <label class="sr-only" for="catalog-keyword">{{ C.keywordPlaceholder }}</label>
                <input
                            id="catalog-keyword"
                            v-model="q.keyword"
                    type="search"
                            autocomplete="off"
                            :placeholder="C.keywordPlaceholder"
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 placeholder:text-slate-400 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        />
                    </div>
                    <button
                        type="submit"
                        class="inline-flex h-12 w-full min-w-0 shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-900 px-5 text-sm font-bold text-white hover:bg-blue-800 sm:w-auto sm:min-w-[120px]"
                    >
                        <Icon icon="lucide:search" class="h-5 w-5 shrink-0" aria-hidden="true" />
                        {{ C.searchBtn }}
                    </button>
                </div>

                <div
                    class="mt-4 grid gap-3 border-t border-slate-100 pt-4 dark:border-slate-800 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{
                            C.filterClassification
                        }}</label>
                        <select
                            v-model="q.classification_id"
                            @change="onClassificationChange"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        >
                            <option value="">{{ C.stockAll }}</option>
                            <option v-for="c in classifications" :key="c.id" :value="String(c.id)">
                                {{ c.code }} — {{ c.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{
                            C.filterStock
                        }}</label>
                        <select
                            v-model="q.stock"
                            @change="submitSearch"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        >
                            <option value="">{{ C.stockAll }}</option>
                            <option value="in_stock">{{ C.stockIn }}</option>
                            <option value="out_of_stock">{{ C.stockOut }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{
                            'Sắp xếp'
                        }}</label>
                        <select
                            v-model="q.sort"
                            @change="submitSearch"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        >
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                        </select>
                    </div>
                </div>
            </form>

            <div v-if="!books?.data?.length" class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center dark:border-slate-600 dark:bg-slate-800/50">
                <Icon icon="lucide:library-big" class="mx-auto h-12 w-12 text-slate-400" aria-hidden="true" />
                <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-200">
                    {{ C.emptyTitle }}
                </p>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                    {{ C.emptyHint }}
                </p>
            </div>

            <div v-else class="mt-8">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link
                        v-for="b in books.data"
                        :key="b.id"
                        :href="route('reader.catalog.show', b.id)"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-600"
                    >
                        <div class="relative aspect-[3/4] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                            <img
                                v-if="b.cover_image"
                                :src="b.cover_image"
                                :alt="b.title"
                                class="h-full w-full object-cover transition group-hover:scale-[1.02]"
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
                            <div class="mt-3 space-y-1 text-[11px] text-slate-500 dark:text-slate-400">
                                <p class="line-clamp-1">
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">Nhà xuất bản:</span>
                                    {{ b.publishers_label || '—' }}
                                </p>
                            </div>
                            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="
                                        Number(b.available_for_borrow || 0) > 0
                                            ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200'
                                            : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
                                    "
                                >
                                    {{ Number(b.available_for_borrow || 0) > 0 ? 'Còn sách' : 'Hết sách' }}
                                </span>
                                <span class="text-xs font-semibold text-blue-800 dark:text-blue-400">{{ C.seeDetail }} →</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <AdminPaginationBar
                    class="mt-8"
                    :current-page="books.current_page"
                    :last-page="books.last_page"
                    @go-page="goPage"
                />
            </div>

            <div v-if="!isAuthed" class="mt-10 flex flex-wrap gap-3">
                <Link
                    :href="route('login')"
                    class="inline-flex min-h-[44px] items-center rounded-xl bg-blue-900 px-5 text-sm font-bold text-white hover:bg-blue-800"
                >
                    {{ L.login }}
                </Link>
            </div>
        </div>
    </ReaderLayout>
</template>
