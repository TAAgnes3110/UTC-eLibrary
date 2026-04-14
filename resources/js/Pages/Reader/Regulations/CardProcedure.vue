<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerCardProcedurePageStrings as P } from '@/config/readerStrings'

const sections = [
    { key: 'types', title: P.section1Title, items: P.section1Items },
    { key: 'account', title: P.section2Title, items: P.section2Items },
    { key: 'channels', title: P.section3Title, items: P.section3Items },
    { key: 'workflow', title: P.section4Title, items: P.section4Items },
]
</script>

<template>
    <ReaderLayout>
        <Head :title="P.headTitle" />
        <div class="mx-auto max-w-3xl animate-in fade-in-50 duration-500">
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
                    <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-blue-200/90">
                                {{ P.kicker }}
                            </p>
                            <h1 class="mt-2 text-3xl font-black leading-tight tracking-tight sm:text-4xl">
                                {{ P.heroTitle }}
                            </h1>
                            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-blue-100/95">
                                {{ P.lead }}
                            </p>
                        </div>
                        <Link
                            :href="route('reader.regulations.index')"
                            class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-xl border border-white/35 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20"
                        >
                            <Icon icon="lucide:arrow-left" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            {{ P.backRegulations }}
                        </Link>
                    </div>
                </header>

                <div class="space-y-8 border-t border-slate-100 px-4 py-8 dark:border-slate-800 sm:px-6 sm:py-10">
                    <section v-for="s in sections" :key="s.key" class="scroll-mt-28">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ s.title }}
                        </h2>
                        <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                            <li v-for="(line, idx) in s.items" :key="idx">
                                {{ line }}
                            </li>
                        </ol>
                    </section>

                </div>
            </article>
        </div>
    </ReaderLayout>
</template>
