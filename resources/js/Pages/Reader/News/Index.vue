<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue'
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue'
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue'
import { useImageFallback } from '@/composables/useImageFallback'

const SEARCH_IN_OPTIONS = [
    { key: 'title', label: 'Tiêu đề' },
    { key: 'content', label: 'Nội dung' },
]

const props = defineProps({
    news: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ keyword: '', per_page: 12 }),
    },
})

const keywordInput = ref(String(props.filters?.keyword ?? ''))
watch(() => props.filters?.keyword, (v) => {
    keywordInput.value = String(v ?? '')
})

const typeFilter = ref(String(props.filters?.type ?? ''))
watch(() => props.filters?.type, (v) => {
    typeFilter.value = String(v ?? '')
})

const sortFilter = ref(String(props.filters?.sort ?? 'newest'))
watch(() => props.filters?.sort, (v) => {
    sortFilter.value = String(v ?? 'newest')
})

const showFilterPanel = ref(false)
const searchIn = ref({ title: true, content: true })
watch(() => props.filters?.search_in, (v) => {
    const arr = Array.isArray(v) ? v : []
    if (arr.length === 0) {
        searchIn.value = { title: true, content: true }
        return
    }
    searchIn.value = {
        title: arr.includes('title'),
        content: arr.includes('content'),
    }
})

const items = computed(() => Array.isArray(props.news?.data) ? props.news.data : [])
const currentPage = computed(() => Number(props.news?.current_page || 1))
const lastPage = computed(() => Number(props.news?.last_page || 1))
const DEFAULT_NEWS_COVER = '/images/default-news-cover.jpg'
const { withFallback } = useImageFallback()

function buildParams(page = 1) {
    const params = {
        page,
        per_page: Number(props.filters?.per_page) || 12,
    }
    const sortVal = sortFilter.value || 'newest'
    if (sortVal !== 'newest') {
        params.sort = sortVal
    }
    const kw = keywordInput.value.trim()
    if (kw !== '') {
        params.keyword = kw
    }
    if (typeFilter.value !== '') {
        params.type = typeFilter.value
    }

    const activeSearchIn = Object.entries(searchIn.value)
        .filter(([, enabled]) => !!enabled)
        .map(([key]) => key)
    if (activeSearchIn.length > 0) {
        params.search_in = activeSearchIn
    }

    return params
}

function runFetch(page = 1) {
    router.get(route('reader.news.index'), buildParams(page), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['news', 'filters'],
    })
}

function goToPage(page) {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return
    runFetch(page)
}

</script>

<template>
    <ReaderLayout>
        <Head title="Tin tức thư viện" />
        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                <div class="border-l-4 border-blue-500 bg-blue-50 px-3 py-2 text-base font-bold uppercase tracking-wide text-blue-900 dark:border-blue-400 dark:bg-blue-950/40 dark:text-blue-200">
                    Tin tức & thông báo
                </div>
                <AdminFilterSearch
                    v-model="keywordInput"
                    search-placeholder="Tìm theo tiêu đề hoặc nội dung..."
                    :show-filter-button="false"
                    class="mt-4"
                    @search="runFetch(1)"
                >
                    <template #filters>
                        <div class="flex min-w-0 items-center gap-2.5 flex-wrap">
                            <AdminFilterPanel
                                :options="SEARCH_IN_OPTIONS"
                                v-model:model-value="searchIn"
                                :show="showFilterPanel"
                                @update:show="showFilterPanel = $event"
                            />
                            <select
                                v-model="typeFilter"
                                class="admin-filter-select admin-filter-select-centered !h-10 !rounded-xl px-2.5 shadow-sm min-w-[132px] text-sm"
                                @change="runFetch(1)"
                            >
                                <option value="">Tất cả loại</option>
                                <option value="news">Tin tức</option>
                                <option value="notice">Thông báo</option>
                            </select>
                            <select
                                v-model="sortFilter"
                                class="admin-filter-select admin-filter-select-centered !h-10 !rounded-xl px-3 shadow-sm min-w-[120px] text-sm"
                                @change="runFetch(1)"
                            >
                                <option value="newest">Mới nhất</option>
                                <option value="oldest">Cũ nhất</option>
                            </select>
                        </div>
                    </template>
                </AdminFilterSearch>
            </section>

            <section v-if="items.length > 0" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-5">
                <article
                    v-for="item in items"
                    :key="item.id"
                    class="flex flex-col gap-4 border-b border-slate-200 pb-5 last:border-b-0 last:pb-0 sm:flex-row"
                >
                    <Link prefetch :href="route('reader.news.show', item.slug)" class="block shrink-0">
                        <img
                            :src="item.thumbnail_url || DEFAULT_NEWS_COVER"
                            alt="Ảnh bài viết"
                            @error="withFallback(DEFAULT_NEWS_COVER)($event)"
                            loading="lazy"
                            decoding="async"
                            class="h-28 w-full rounded-md border border-blue-200 object-cover sm:w-44"
                        />
                    </Link>
                        <div class="min-w-0 space-y-2">
                            <Link
                                prefetch
                                :href="route('reader.news.show', item.slug)"
                                class="block"
                            >
                                <p class="line-clamp-2 text-lg font-bold uppercase leading-tight text-slate-900 hover:text-blue-700 hover:underline dark:text-slate-100 dark:hover:text-blue-300 sm:text-xl md:text-2xl">
                                    {{ item.title }}
                                </p>
                                <p class="mt-1 text-sm leading-snug text-slate-700 dark:text-slate-300 sm:text-base md:text-lg">
                                    {{ item.excerpt || '' }}
                                </p>
                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ item.posted_by?.name || 'Ban quản trị' }} · {{ item.published_at ? new Date(item.published_at).toLocaleDateString('vi-VN') : '—' }}
                                </div>
                            </Link>
                        </div>
                </article>

                <AdminPaginationBar
                    :current-page="currentPage"
                    :last-page="lastPage"
                    :always-show="true"
                    @go-page="goToPage"
                />
            </section>

            <section v-else class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-8 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                Chưa có bài viết phù hợp.
            </section>
        </div>
    </ReaderLayout>
</template>
