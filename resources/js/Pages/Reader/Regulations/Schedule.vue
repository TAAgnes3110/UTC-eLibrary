<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerSchedulePageStrings as S } from '@/config/readerStrings'
</script>

<template>
    <ReaderLayout>
        <Head :title="S.headTitle" />
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
                                {{ S.kicker }}
                            </p>
                            <h1 class="mt-2 text-3xl font-black leading-tight tracking-tight sm:text-4xl">
                                {{ S.heroTitle }}
                            </h1>
                            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-blue-100/95">
                                {{ S.lead }}
                            </p>
                        </div>
                        <Link
                            :href="route('reader.regulations.index')"
                            class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-xl border border-white/35 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20"
                        >
                            <Icon icon="lucide:arrow-left" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            {{ S.backRegulations }}
                        </Link>
                    </div>
                </header>

                <div class="border-t border-slate-100 px-4 py-8 dark:border-slate-800 sm:px-6 sm:py-10">
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                        <table class="w-full min-w-[520px] border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/80">
                                    <th scope="col" class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">
                                        {{ S.colDay }}
                                    </th>
                                    <th scope="col" class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">
                                        {{ S.colHours }}
                                    </th>
                                    <th scope="col" class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">
                                        {{ S.colNote }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="(row, idx) in S.rows" :key="idx" class="bg-white dark:bg-slate-900/40">
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                        {{ row.day }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                        {{ row.hours }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                                        {{ row.note }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <ul class="mt-6 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li v-for="(line, idx) in S.bullets" :key="idx" class="flex gap-2">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600 dark:bg-blue-400" aria-hidden="true" />
                            <span>{{ line }}</span>
                        </li>
                    </ul>

                </div>
            </article>
        </div>
    </ReaderLayout>
</template>
