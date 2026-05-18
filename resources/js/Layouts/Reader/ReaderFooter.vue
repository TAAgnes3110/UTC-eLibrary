<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { readerFooterStrings as F, readerLayoutStrings as S } from '@/config/readerStrings'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)

const serviceLinks = [
    { label: F.linkCatalog, route: 'reader.catalog' },
    { label: F.linkServicesHub, route: 'reader.services' },
    { label: F.linkLibraryCard, route: 'reader.services.library-card' },
    { label: F.linkDigitalDocs, route: 'reader.services.digital-documents' },
    { label: F.linkLoanRequests, route: 'reader.services.loan-requests' },
]

const supportLinks = [
    { label: F.linkAbout, route: 'reader.about' },
    { label: F.linkNews, route: 'reader.news.index' },
    { label: F.linkRegulations, route: 'reader.regulations.index' },
    { label: F.linkCardProcedure, route: 'reader.regulations.card' },
    { label: F.linkSchedule, route: 'reader.regulations.schedule' },
    { label: F.linkBorrowing, route: 'reader.regulations.borrowing' },
    { label: F.linkOfficialLib, href: F.officialLibUrl, external: true },
]

const accountLinksGuest = [
    { label: F.linkLoginRegister, route: 'login' },
    { label: F.linkRegister, route: 'register' },
]

const accountLinksAuthed = [
    { label: F.linkProfile, route: 'reader.profile' },
    { label: F.linkProfileRequests, route: 'reader.profile-update-requests' },
    { label: F.linkChangePassword, route: 'reader.change-password' },
]

const accountLinks = computed(() => (isAuthed.value ? accountLinksAuthed : accountLinksGuest))

const socialLinks = [
    {
        key: 'facebook',
        label: F.socialFacebookLabel,
        icon: 'mdi:facebook',
        href: S.footerFacebookUrl,
        external: true,
    },
    {
        key: 'github',
        label: F.socialGithubLabel,
        icon: 'lucide:github',
        href: S.footerGithubUrl,
        external: true,
    },
    {
        key: 'email',
        label: F.socialEmailLabel,
        icon: 'lucide:mail',
        href: `mailto:${S.footerEmail}`,
        external: false,
    },
]

const contactItems = [
    {
        key: 'address',
        label: F.contactAddressLabel,
        icon: 'lucide:map-pin',
        text: F.contactAddress,
    },
    {
        key: 'email',
        label: F.contactEmailLabel,
        icon: 'lucide:mail',
        href: `mailto:${F.contactEmail}`,
        text: F.contactEmail,
    },
    {
        key: 'phone',
        label: F.contactPhoneLabel,
        icon: 'lucide:phone',
        href: `tel:${F.contactPhone.replace(/[^\d+]/g, '')}`,
        text: F.contactPhone,
    },
]

</script>

<template>
    <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:py-12">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12 lg:gap-8">
                <div class="sm:col-span-2 lg:col-span-4">
                    <Link prefetch :href="route('reader.home')" class="inline-flex items-center gap-3">
                        <img src="/Image/logoUTC.png" alt="UTC" class="h-12 w-12 shrink-0 object-contain" />
                        <div>
                            <p class="text-lg font-black text-blue-900 dark:text-blue-300">{{ S.footerTitle }}</p>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                {{ S.footerSubtitle }}
                            </p>
                        </div>
                    </Link>
                    <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ F.brandDesc }}
                    </p>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ F.addressLine1 }}</span>
                        <br />
                        {{ F.addressLine2 }}
                    </p>
                    <div class="mt-5 flex flex-wrap gap-2" role="list" aria-label="Mạng xã hội">
                        <a
                            v-for="item in socialLinks"
                            :key="item.key"
                            :href="item.href"
                            :aria-label="item.label"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-700 transition hover:bg-blue-100 hover:text-blue-900 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-blue-950 dark:hover:text-blue-300"
                            :target="item.external ? '_blank' : undefined"
                            :rel="item.external ? 'noopener noreferrer' : undefined"
                        >
                            <Icon :icon="item.icon" class="h-5 w-5" aria-hidden="true" />
                        </a>
                    </div>
                </div>

                <nav class="lg:col-span-2" :aria-label="F.colServices">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">
                        {{ F.colServices }}
                    </h3>
                    <ul class="mt-4 space-y-2.5" role="list">
                        <li v-for="link in serviceLinks" :key="link.route">
                            <Link
                                prefetch
                                :href="route(link.route)"
                                class="text-sm text-slate-600 transition hover:text-blue-800 dark:text-slate-400 dark:hover:text-blue-400"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <nav class="lg:col-span-3" :aria-label="F.colSupport">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">
                        {{ F.colSupport }}
                    </h3>
                    <ul class="mt-4 space-y-2.5" role="list">
                        <li v-for="(link, idx) in supportLinks" :key="link.route ?? link.href ?? idx">
                            <Link
                                v-if="link.route"
                                prefetch
                                :href="route(link.route)"
                                class="text-sm text-slate-600 transition hover:text-blue-800 dark:text-slate-400 dark:hover:text-blue-400"
                            >
                                {{ link.label }}
                            </Link>
                            <a
                                v-else
                                :href="link.href"
                                class="text-sm text-slate-600 transition hover:text-blue-800 dark:text-slate-400 dark:hover:text-blue-400"
                                :target="link.external ? '_blank' : undefined"
                                :rel="link.external ? 'noopener noreferrer' : undefined"
                            >
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </nav>

                <nav class="lg:col-span-3" :aria-label="F.colAccount">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white">
                        {{ F.colAccount }}
                    </h3>
                    <ul class="mt-4 space-y-2.5" role="list">
                        <li v-for="link in accountLinks" :key="link.route">
                            <Link
                                prefetch
                                :href="route(link.route)"
                                class="text-sm text-slate-600 transition hover:text-blue-800 dark:text-slate-400 dark:hover:text-blue-400"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>

            <section
                class="mt-10 border-t border-slate-100 pt-8 dark:border-slate-800"
                aria-labelledby="footer-contact-heading"
            >
                <h3
                    id="footer-contact-heading"
                    class="text-sm font-bold uppercase tracking-wide text-slate-900 dark:text-white"
                >
                    {{ F.colContact }}
                </h3>
                <ul class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
                    <li
                        v-for="item in contactItems"
                        :key="item.key"
                        class="flex gap-3 text-sm text-slate-600 dark:text-slate-400"
                    >
                        <span
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-blue-900 dark:bg-slate-800 dark:text-blue-300"
                        >
                            <Icon :icon="item.icon" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ item.label }}</p>
                            <a
                                v-if="item.href"
                                :href="item.href"
                                class="mt-0.5 block break-words font-medium text-blue-800 hover:underline dark:text-blue-400"
                            >
                                {{ item.text }}
                            </a>
                            <p v-else class="mt-0.5 break-words">{{ item.text }}</p>
                        </div>
                    </li>
                </ul>
            </section>

            <p class="mt-8 border-t border-slate-100 pt-6 text-center text-xs text-slate-500 dark:border-slate-800 dark:text-slate-500">
                © {{ new Date().getFullYear() }} — {{ S.footerTitle }} · {{ S.footerCopyrightNote }}
            </p>
        </div>
    </footer>
</template>
