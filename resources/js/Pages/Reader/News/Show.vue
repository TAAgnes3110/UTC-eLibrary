<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { useImageFallback } from '@/composables/useImageFallback'

const props = defineProps({
    post: { type: Object, required: true },
    relatedNews: { type: Array, default: () => [] },
})

const type = computed(() => String(props.post?.type || 'news'))
const typeLabel = computed(() => (type.value === 'notice' ? 'Thông báo' : 'Tin tức'))
const publishedAtText = computed(() =>
    props.post?.published_at ? new Date(props.post.published_at).toLocaleString('vi-VN') : 'Chưa có ngày đăng',
)
const typePillClasses = computed(() =>
    type.value === 'notice'
        ? 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800/40 dark:bg-amber-950/30 dark:text-amber-200'
        : 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800/40 dark:bg-blue-950/30 dark:text-blue-200',
)
const relatedItems = computed(() => (Array.isArray(props.relatedNews) ? props.relatedNews : []))
const DEFAULT_NEWS_COVER = '/images/default-news-cover.jpg'
const { withFallback } = useImageFallback()

function backLink() {
    return route('reader.news.index', { type: type.value === 'notice' ? 'notice' : 'news' })
}

function toDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('vi-VN')
}

</script>

<template>
    <ReaderLayout>
        <Head :title="post.title || 'Chi tiết tin tức'" />
        <article class="mx-auto max-w-6xl px-4 pb-10 sm:px-6 lg:px-8">
            <div class="mb-5 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <Link :href="route('reader.home')" class="hover:text-blue-700 dark:hover:text-blue-300">Trang chủ</Link>
                <span>›</span>
                <Link :href="route('reader.news.index')" class="hover:text-blue-700 dark:hover:text-blue-300">Tin tức</Link>
            </div>

            <div class="grid grid-cols-1 gap-8">
                <section>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide" :class="typePillClasses">
                            {{ typeLabel }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-extrabold leading-tight text-slate-900 dark:text-white sm:text-3xl md:text-[38px]">
                        {{ post.title }}
                    </h1>
                    <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                        <span>{{ publishedAtText }}</span>
                        <span>Đăng bởi {{ post.posted_by?.name || 'Ban quản trị' }}</span>
                    </div>

                    <div class="mt-6">
                        <div
                            class="prose prose-slate max-w-none text-[15px] leading-7 dark:prose-invert sm:text-[17px] sm:leading-8
                                   prose-headings:font-bold prose-p:my-4 prose-img:rounded-sm
                                   prose-a:text-blue-700 dark:prose-a:text-blue-300"
                            v-html="post.content || '<p>Không có nội dung.</p>'"
                        />
                    </div>
                </section>
            </div>

            <section class="mt-10">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Tin tức liên quan</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="item in relatedItems"
                        :key="`r-${item.id}`"
                        :href="route('reader.news.show', item.slug)"
                        class="group overflow-hidden rounded-md border border-slate-200 bg-white hover:border-blue-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-700"
                    >
                        <img
                            :src="item.thumbnail_url || DEFAULT_NEWS_COVER"
                            alt="Ảnh liên quan"
                            @error="withFallback(DEFAULT_NEWS_COVER)($event)"
                            loading="lazy"
                            decoding="async"
                            class="h-36 w-full object-cover"
                        />
                        <div class="p-3">
                            <p class="line-clamp-2 text-sm font-semibold text-slate-900 group-hover:text-blue-700 dark:text-slate-100 dark:group-hover:text-blue-300">
                                {{ item.title }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ toDate(item.published_at) }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>
        </article>
    </ReaderLayout>
</template>
