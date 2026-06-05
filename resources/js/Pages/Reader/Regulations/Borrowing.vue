<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerAboutPageStrings as A, readerRegulationsBorrowingPageStrings as R } from '@/config/readerStrings'
import { formatDamageFinePolicyShort } from '@/utils/loanPolicyDamageFine'

const props = defineProps({
    loanPolicies: { type: Array, default: () => [] },
})

const CARD_TYPE_ORDER = ['STUDENT', 'TEACHER', 'MEMBER', '']

const orderedPolicies = computed(() => {
    const list = [...(props.loanPolicies ?? [])]
    const orderIndex = (ut) => {
        const k = ut ?? ''
        const i = CARD_TYPE_ORDER.indexOf(k)
        return i === -1 ? 999 : i
    }
    list.sort((a, b) => {
        const cmp = orderIndex(a.user_type) - orderIndex(b.user_type)
        if (cmp !== 0) {
            return cmp
        }
        return (Number(a.id) || 0) - (Number(b.id) || 0)
    })
    return list
})

const sectionTitle = (userType) => {
    const m = {
        STUDENT: R.sectionStudentCard,
        TEACHER: R.sectionTeacherCard,
        MEMBER: R.sectionReaderCard,
    }
    if (userType === '' || userType == null) {
        return R.sectionOtherCard
    }
    return m[userType] ?? `${R.sectionOtherCard} (${userType})`
}

/** Tên hiển thị: tên chính sách (thường = tên loại thẻ), fallback theo user_type. */
const cardDisplayName = (p) => {
    const n = p?.name
    if (n != null && String(n).trim() !== '') {
        return String(n).trim()
    }
    return sectionTitle(p?.user_type)
}

const formatYesNo = (v) => (v ? 'Có' : 'Không')

const formatLoanTerm = (p) => {
    if (!p.allow_home) {
        return 'Không mượn về nhà'
    }
    if (!p.max_days) {
        return '—'
    }
    return `${p.max_days} ngày`
}

const formatFine = (n) => {
    const x = Number(n)
    if (!Number.isFinite(x) || x <= 0) {
        return null
    }
    return `${new Intl.NumberFormat('vi-VN').format(x)} đ/ngày`
}

const paramInt = (params, key) => {
    if (!params || typeof params !== 'object') {
        return null
    }
    const v = params[key]
    if (v == null || v === '') {
        return null
    }
    const n = Number(v)
    return Number.isFinite(n) ? n : null
}

const badgeYesNo = (v) =>
    v
        ? 'bg-emerald-100 text-emerald-900 ring-emerald-600/20 dark:bg-emerald-950/80 dark:text-emerald-200 dark:ring-emerald-500/30'
        : 'bg-slate-200/90 text-slate-800 ring-slate-500/15 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-400/20'
</script>

<template>
    <ReaderLayout>
        <Head :title="R.headTitle" />
        <div class="mx-auto max-w-7xl animate-in fade-in-50 duration-500">
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
                        class="pointer-events-none absolute -right-16 -top-24 h-72 w-72 rounded-full bg-amber-400/10 blur-3xl"
                    />
                    <div
                        class="pointer-events-none absolute -bottom-20 left-1/3 h-56 w-56 rounded-full bg-blue-400/15 blur-3xl"
                    />

                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="min-w-0 max-w-3xl">
                            <h1 class="text-3xl font-black leading-tight tracking-tight sm:text-4xl">
                                {{ R.heroTitle }}
                            </h1>
                        </div>
                        <Link
                            :href="route('reader.home')"
                            class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 self-start rounded-xl border border-white/35 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 lg:self-auto"
                        >
                            <Icon icon="lucide:arrow-left" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            {{ A.backHome }}
                        </Link>
                    </div>
                </header>

                <div class="border-t border-slate-100 px-4 py-8 dark:border-slate-800 sm:px-6 sm:py-10">
                    <div
                        v-if="!loanPolicies.length"
                        class="mt-2 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center dark:border-slate-600 dark:bg-slate-800/50"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-200/80 text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                        >
                            <Icon icon="lucide:clipboard-x" class="h-7 w-7" aria-hidden="true" />
                        </div>
                        <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-200">
                            {{ R.emptyTitle }}
                        </p>
                        <p class="mt-1 max-w-sm text-xs text-slate-600 dark:text-slate-400">
                            {{ R.emptyHint }}
                        </p>
                    </div>

                    <div
                        v-else
                        class="overflow-x-auto rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900/50 dark:ring-white/5"
                    >
                        <table class="w-full min-w-[980px] table-fixed border-collapse text-sm">
                            <thead>
                                <tr
                                    class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-slate-100/90 dark:border-slate-700 dark:from-slate-800 dark:to-slate-800/70"
                                >
                                    <th
                                        scope="col"
                                        class="sticky left-0 z-10 min-w-[160px] bg-gradient-to-b from-slate-50 to-slate-100/95 px-3 py-3 text-left text-[11px] font-semibold leading-tight text-slate-500 dark:from-slate-800 dark:to-slate-800 dark:text-slate-400"
                                    >
                                        {{ R.colCardName }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-2 py-3 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colMaxBooks }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="min-w-[12.5rem] px-2 py-3 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colLoanTerm }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-2 py-3 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colRenewCount }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-2 py-3 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colLateFine }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-2 py-3 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colDamageFine }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-2 py-3 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colMaxTextbooks }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-2 py-3 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colMaxReference }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-2 py-3 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colBorrowHome }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="min-w-[100px] px-3 py-3 pr-4 text-center text-[11px] font-semibold leading-tight text-slate-500 dark:text-slate-400"
                                    >
                                        {{ R.colReadOnsite }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                <tr
                                    v-for="p in orderedPolicies"
                                    :key="p.id"
                                    class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/30"
                                >
                                    <td
                                        class="sticky left-0 z-[1] bg-white px-3 py-3 align-middle font-semibold text-slate-900 shadow-[4px_0_12px_-8px_rgba(0,0,0,0.15)] dark:bg-slate-900 dark:text-white dark:shadow-[4px_0_12px_-8px_rgba(0,0,0,0.4)]"
                                    >
                                        <span class="leading-snug">{{ cardDisplayName(p) }}</span>
                                        <span class="sr-only">. Mã {{ p.code }}.</span>
                                    </td>
                                    <td class="px-2 py-3 text-center align-middle tabular-nums">
                                        <span class="text-base font-bold text-slate-900 dark:text-white">{{ p.max_books }}</span>
                                    </td>
                                    <td class="min-w-[12.5rem] px-2 py-3 text-center align-middle">
                                        <span
                                            class="inline-flex min-h-[44px] max-w-none items-center justify-center whitespace-nowrap text-sm font-semibold leading-none text-slate-800 dark:text-slate-200"
                                        >
                                            {{ formatLoanTerm(p) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-center align-middle tabular-nums text-slate-800 dark:text-slate-200">
                                        {{ p.allow_home ? p.max_renewals : '—' }}
                                    </td>
                                    <td class="px-2 py-3 text-center align-middle">
                                        <span
                                            v-if="formatFine(p.overdue_fine_per_day)"
                                            class="inline-flex items-center justify-center gap-1 rounded-lg bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                                        >
                                            <Icon icon="lucide:coins" class="h-3.5 w-3.5 shrink-0 opacity-80" aria-hidden="true" />
                                            {{ formatFine(p.overdue_fine_per_day) }}
                                        </span>
                                        <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                                    </td>
                                    <td class="px-2 py-3 text-center align-middle">
                                        <span
                                            v-if="formatDamageFinePolicyShort(p.params?.damage_fine_percent)"
                                            class="inline-flex items-center justify-center gap-1 rounded-lg bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-900 dark:bg-rose-950/40 dark:text-rose-200"
                                        >
                                            <Icon icon="lucide:book-x" class="h-3.5 w-3.5 shrink-0 opacity-80" aria-hidden="true" />
                                            {{ formatDamageFinePolicyShort(p.params?.damage_fine_percent) }}
                                        </span>
                                        <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                                    </td>
                                    <td class="px-2 py-3 text-center align-middle tabular-nums text-slate-800 dark:text-slate-200">
                                        {{ paramInt(p.params, 'max_textbooks') ?? '—' }}
                                    </td>
                                    <td class="px-2 py-3 text-center align-middle tabular-nums text-slate-800 dark:text-slate-200">
                                        {{ paramInt(p.params, 'max_reference') ?? '—' }}
                                    </td>
                                    <td class="px-2 py-3 text-center align-middle">
                                        <span
                                            class="inline-flex min-h-[44px] min-w-[3.25rem] items-center justify-center rounded-full px-2.5 py-1.5 text-xs font-bold ring-1 ring-inset"
                                            :class="badgeYesNo(p.allow_home)"
                                        >
                                            {{ formatYesNo(p.allow_home) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 pr-4 text-center align-middle">
                                        <span
                                            class="inline-flex min-h-[44px] min-w-[3.25rem] items-center justify-center rounded-full px-2.5 py-1.5 text-xs font-bold ring-1 ring-inset"
                                            :class="badgeYesNo(p.allow_onsite)"
                                        >
                                            {{ formatYesNo(p.allow_onsite) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <section
                        class="mx-auto mt-8 max-w-3xl rounded-2xl border border-slate-200/90 bg-slate-50/90 px-4 py-6 shadow-sm dark:border-slate-700 dark:bg-slate-800/40 sm:mt-10 sm:px-6 sm:py-8"
                        aria-labelledby="borrowing-fines-guide-heading"
                    >
                        <h2
                            id="borrowing-fines-guide-heading"
                            class="text-base font-bold text-slate-900 dark:text-white sm:text-lg"
                        >
                            {{ R.finesSectionTitle }}
                        </h2>
                        <div class="mt-4 space-y-5 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ R.finesMoneyTitle }}</h3>
                                <p class="mt-1.5">{{ R.finesMoneyBody }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ R.finesSuspendTitle }}</h3>
                                <p class="mt-1.5">{{ R.finesSuspendBody }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ R.finesNoticeTitle }}</h3>
                                <p class="mt-1.5">{{ R.finesNoticeBody }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ R.finesDamageTitle }}</h3>
                                <p class="mt-1.5">{{ R.finesDamageBody }}</p>
                            </div>
                            <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-3 py-3 dark:border-amber-900/40 dark:bg-amber-950/25">
                                <p class="font-semibold text-amber-950 dark:text-amber-100">{{ R.finesNoteTitle }}</p>
                                <ul class="mt-2 list-disc space-y-1.5 pl-5 text-amber-950/95 dark:text-amber-100/95">
                                    <li>{{ R.finesNoteBody1 }}</li>
                                    <li>{{ R.finesNoteBody2 }}</li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </div>
            </article>
        </div>
    </ReaderLayout>
</template>
