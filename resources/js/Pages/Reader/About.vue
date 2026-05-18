<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerLayoutStrings as S, readerAboutPageStrings as A } from '@/config/readerStrings'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)

const quickLinksAll = [
    {
        key: 'catalog',
        title: S.catalog,
        description: A.quickCatalog,
        href: () => route('reader.catalog'),
        icon: 'lucide:library-big',
        iconClass: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    },
    {
        key: 'regulations',
        title: S.regulationsShort,
        description: A.quickRegulations,
        href: () => route('reader.regulations.index'),
        icon: 'lucide:scroll-text',
        iconClass: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    },
    {
        key: 'services',
        title: S.services,
        description: A.quickServices,
        href: () => route('reader.services'),
        icon: 'lucide:hand-heart',
        iconClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    },
    {
        key: 'login',
        title: S.login,
        description: A.quickLogin,
        href: () => route('login'),
        icon: 'lucide:log-in',
        iconClass: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300',
        guestOnly: true,
    },
]

const quickLinks = computed(() =>
    isAuthed.value ? quickLinksAll.filter((item) => !item.guestOnly) : quickLinksAll,
)
</script>

<template>
    <ReaderLayout>
        <Head :title="A.headTitle" />
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
                    <div
                        class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-blue-400/20 blur-3xl"
                    />
                    <div
                        class="pointer-events-none absolute -bottom-24 left-1/4 h-48 w-48 rounded-full bg-indigo-500/15 blur-3xl"
                    />

                    <div class="relative flex flex-col gap-6">
                        <div class="min-w-0 max-w-2xl space-y-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-200/90">
                                UTC eLibrary
                            </p>
                            <h1 class="text-3xl font-black leading-tight tracking-tight sm:text-4xl">
                                {{ S.about }}
                            </h1>
                            <p class="text-sm leading-relaxed text-blue-100/95 sm:text-base">
                                {{ A.heroSubtitle }}
                            </p>
                        </div>
                    </div>
                </header>

                <div class="border-t border-slate-100 px-5 py-8 dark:border-slate-800 sm:px-8 sm:py-10">
                    <section class="max-w-none leading-relaxed" aria-labelledby="about-university-heading">
                        <h2
                            id="about-university-heading"
                            class="text-lg font-bold text-slate-900 dark:text-white sm:text-xl"
                        >
                            {{ A.universitySectionTitle }}
                        </h2>
                        <div class="mt-4 space-y-4">
                            <p
                                v-for="(para, idx) in A.universityParagraphs"
                                :key="`uni-${idx}`"
                                class="text-base text-slate-700 dark:text-slate-200 sm:text-lg sm:leading-8"
                            >
                                {{ para }}
                            </p>
                        </div>
                    </section>

                    <section
                        class="mt-10 max-w-none leading-relaxed rounded-2xl border border-blue-100 bg-blue-50/50 p-5 dark:border-blue-900/50 dark:bg-blue-950/20 sm:p-6"
                        aria-labelledby="about-elibrary-heading"
                    >
                        <h2
                            id="about-elibrary-heading"
                            class="text-lg font-bold text-blue-900 dark:text-blue-200 sm:text-xl"
                        >
                            {{ A.elibrarySectionTitle }}
                        </h2>
                        <div class="mt-4 space-y-4">
                            <p
                                v-for="(para, idx) in A.elibraryParagraphs"
                                :key="`elib-${idx}`"
                                class="text-base text-slate-700 dark:text-slate-200 sm:text-lg sm:leading-8"
                            >
                                {{ para }}
                            </p>
                        </div>
                    </section>

                    <div class="mt-10">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            {{ A.exploreTitle }}
                        </h2>
                        <ul class="mt-4 grid gap-4 sm:grid-cols-2" role="list">
                            <li v-for="item in quickLinks" :key="item.key">
                                <Link
                                    :href="item.href()"
                                    class="group flex h-full min-h-[120px] flex-col rounded-2xl border border-slate-200 bg-slate-50/80 p-5 shadow-sm outline-none ring-blue-500/40 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:ring-2 dark:border-slate-700 dark:bg-slate-800/80 dark:hover:border-blue-500/40"
                                >
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-xl transition group-hover:scale-105"
                                        :class="item.iconClass"
                                    >
                                        <Icon :icon="item.icon" class="h-6 w-6" aria-hidden="true" />
                                    </div>
                                    <h3 class="mt-3 font-bold text-slate-900 dark:text-white">
                                        {{ item.title }}
                                    </h3>
                                    <p class="mt-1 flex-1 text-sm text-slate-600 dark:text-slate-400">
                                        {{ item.description }}
                                    </p>
                                    <span
                                        class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-blue-800 dark:text-blue-400"
                                    >
                                        {{ A.seeMore }}
                                        <Icon
                                            icon="lucide:arrow-right"
                                            class="h-4 w-4 transition group-hover:translate-x-0.5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </article>
        </div>
    </ReaderLayout>
</template>
