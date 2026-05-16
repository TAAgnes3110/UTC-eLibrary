<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerCardProcedurePageStrings as P } from '@/config/readerStrings'
import { useImageFallback } from '@/composables/useImageFallback'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)
const { withFallback } = useImageFallback()

const detailSections = [
    { key: 'types', title: P.section1Title, items: P.section1Items },
    { key: 'account', title: P.section2Title, items: P.section2Items },
    { key: 'channels', title: P.section3Title, items: P.section3Items },
]

const workflowToneClass = {
    amber: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100',
    orange: 'border-orange-200 bg-orange-50 text-orange-900 dark:border-orange-800/60 dark:bg-orange-950/40 dark:text-orange-100',
    blue: 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800/60 dark:bg-blue-950/40 dark:text-blue-100',
    emerald: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-100',
    rose: 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-100',
}
</script>

<template>
    <ReaderLayout>
        <Head :title="P.headTitle" />
        <div class="mx-auto max-w-4xl animate-in fade-in-50 duration-500">
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
                            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-blue-100/95 sm:text-base">
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

                <div class="space-y-10 border-t border-slate-100 px-4 py-8 dark:border-slate-800 sm:px-6 sm:py-10">
                    <!-- Tổng quan -->
                    <section class="scroll-mt-28">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ P.overviewTitle }}
                        </h2>
                        <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300 sm:text-base">
                            <p v-for="(para, idx) in P.overviewParagraphs" :key="idx">
                                {{ para }}
                            </p>
                        </div>
                    </section>

                    <!-- Ảnh minh họa -->
                    <section class="scroll-mt-28">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ P.galleryTitle }}
                        </h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <figure
                                v-for="(img, idx) in P.gallery"
                                :key="idx"
                                class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/50"
                            >
                                <img
                                    :src="img.src"
                                    :alt="img.alt"
                                    loading="lazy"
                                    decoding="async"
                                    class="aspect-[4/3] w-full object-cover"
                                    @error="withFallback('/images/default-news-cover.jpg')($event)"
                                />
                                <figcaption class="px-3 py-2 text-center text-xs font-medium text-slate-600 dark:text-slate-400">
                                    {{ img.caption }}
                                </figcaption>
                            </figure>
                        </div>
                    </section>

                    <!-- Thủ tục tại quầy -->
                    <section class="scroll-mt-28">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ P.officialTitle }}
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400 sm:text-base">
                            {{ P.officialIntro }}
                        </p>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="block in P.officialSections"
                                :key="block.title"
                                class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-800/40"
                            >
                                <h3 class="text-sm font-bold text-blue-900 dark:text-blue-300">
                                    {{ block.title }}
                                </h3>
                                <ul class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                                    <li v-for="(line, i) in block.items" :key="i" class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 dark:bg-blue-400" aria-hidden="true" />
                                        <span>{{ line }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <aside
                            class="mt-5 rounded-xl border border-blue-200 bg-blue-50/80 p-4 dark:border-blue-800/50 dark:bg-blue-950/30"
                            aria-label="Liên hệ"
                        >
                            <h3 class="flex items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-200">
                                <Icon icon="lucide:building-2" class="h-4 w-4" aria-hidden="true" />
                                {{ P.contactBoxTitle }}
                            </h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                                <li class="flex gap-2">
                                    <Icon icon="lucide:map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-blue-700 dark:text-blue-400" aria-hidden="true" />
                                    <span>{{ P.contactAddress }}</span>
                                </li>
                                <li class="flex gap-2">
                                    <Icon icon="lucide:phone" class="mt-0.5 h-4 w-4 shrink-0 text-blue-700 dark:text-blue-400" aria-hidden="true" />
                                    <a href="tel:+842437669860" class="font-medium text-blue-800 hover:underline dark:text-blue-400">
                                        {{ P.contactPhone }}
                                    </a>
                                </li>
                                <li class="flex gap-2">
                                    <Icon icon="lucide:mail" class="mt-0.5 h-4 w-4 shrink-0 text-blue-700 dark:text-blue-400" aria-hidden="true" />
                                    <a :href="`mailto:${P.contactEmail}`" class="font-medium text-blue-800 hover:underline dark:text-blue-400">
                                        {{ P.contactEmail }}
                                    </a>
                                </li>
                            </ul>
                        </aside>
                    </section>

                    <!-- Đăng ký online -->
                    <section class="scroll-mt-28">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ P.onlineTitle }}
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400 sm:text-base">
                            {{ P.onlineIntro }}
                        </p>
                        <ol class="mt-6 space-y-4">
                            <li
                                v-for="step in P.onlineSteps"
                                :key="step.step"
                                class="flex gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/50"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300"
                                    aria-hidden="true"
                                >
                                    <Icon :icon="step.icon" class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Bước {{ step.step }}
                                    </p>
                                    <h3 class="mt-0.5 font-bold text-slate-900 dark:text-white">
                                        {{ step.title }}
                                    </h3>
                                    <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                        {{ step.body }}
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </section>

                    <!-- Workflow statuses -->
                    <section class="scroll-mt-28">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ P.workflowTitle }}
                        </h2>
                        <ul class="mt-4 space-y-3">
                            <li
                                v-for="ws in P.workflowStatuses"
                                :key="ws.key"
                                class="rounded-xl border p-4"
                                :class="workflowToneClass[ws.tone] ?? workflowToneClass.blue"
                            >
                                <p class="text-sm font-bold">
                                    {{ ws.label }}
                                </p>
                                <p class="mt-1 text-sm opacity-90">
                                    {{ ws.desc }}
                                </p>
                            </li>
                        </ul>
                    </section>

                    <!-- Chi tiết kỹ thuật -->
                    <section
                        v-for="s in detailSections"
                        :key="s.key"
                        class="scroll-mt-28 border-t border-slate-100 pt-8 dark:border-slate-800"
                    >
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ s.title }}
                        </h2>
                        <ul class="mt-4 space-y-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                            <li v-for="(line, idx) in s.items" :key="idx" class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400" aria-hidden="true" />
                                <span>{{ line }}</span>
                            </li>
                        </ul>
                    </section>

                    <!-- CTA -->
                    <section
                        class="scroll-mt-28 rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-slate-50 p-5 dark:border-blue-800/50 dark:from-blue-950/40 dark:to-slate-900/60 sm:p-6"
                    >
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ P.ctaTitle }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400 sm:text-base">
                            {{ P.ctaBody }}
                        </p>
                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <template v-if="!isAuthed">
                                <Link
                                    :href="route('register')"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-blue-800 px-5 text-sm font-bold text-white shadow-md hover:bg-blue-900 dark:bg-blue-700 dark:hover:bg-blue-600"
                                >
                                    <Icon icon="lucide:user-plus" class="h-4 w-4" aria-hidden="true" />
                                    {{ P.ctaRegister }}
                                </Link>
                                <Link
                                    :href="route('login')"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-blue-300 bg-white px-5 text-sm font-semibold text-blue-900 hover:bg-blue-50 dark:border-blue-600 dark:bg-slate-900 dark:text-blue-200 dark:hover:bg-slate-800"
                                >
                                    <Icon icon="lucide:log-in" class="h-4 w-4" aria-hidden="true" />
                                    {{ P.ctaLogin }}
                                </Link>
                            </template>
                            <Link
                                v-else
                                :href="route('reader.services.library-card')"
                                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-blue-800 px-5 text-sm font-bold text-white shadow-md hover:bg-blue-900 dark:bg-blue-700 dark:hover:bg-blue-600"
                            >
                                <Icon icon="lucide:id-card" class="h-4 w-4" aria-hidden="true" />
                                {{ P.ctaLibraryCard }}
                            </Link>
                            <Link
                                :href="route('reader.regulations.schedule')"
                                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <Icon icon="lucide:clock" class="h-4 w-4" aria-hidden="true" />
                                {{ P.ctaSchedule }}
                            </Link>
                        </div>
                        <p class="mt-5 text-xs text-slate-500 dark:text-slate-500">
                            {{ P.note }}
                            <a
                                :href="P.officialLinkUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="ml-1 font-medium text-blue-800 underline-offset-2 hover:underline dark:text-blue-400"
                            >
                                {{ P.officialLinkLabel }}
                            </a>
                        </p>
                    </section>
                </div>
            </article>
        </div>
    </ReaderLayout>
</template>
