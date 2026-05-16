<script setup>
import { Icon } from '@iconify/vue'
import { readerLayoutStrings as S } from '@/config/readerStrings'

const contactRows = [
    {
        key: 'github',
        label: S.footerGithubLabel,
        icon: 'lucide:github',
        href: S.footerGithubUrl,
        text: S.footerGithub,
        external: true,
    },
    {
        key: 'email',
        label: S.footerEmailLabel,
        icon: 'lucide:mail',
        href: `mailto:${S.footerEmail}`,
        text: S.footerEmail,
    },
    {
        key: 'facebook',
        label: S.footerFacebookLabel,
        icon: 'mdi:facebook',
        href: S.footerFacebookUrl,
        text: S.footerFacebook,
        external: true,
    },
]
</script>

<template>
    <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <div class="max-w-lg">
                <p class="text-base font-bold text-blue-900 dark:text-blue-300">{{ S.footerTitle }}</p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ S.footerSubtitle }}</p>
                <ul class="mt-4 space-y-2.5" role="list">
                    <li
                        v-for="row in contactRows"
                        :key="row.key"
                        class="flex gap-2.5 text-sm text-slate-600 dark:text-slate-400"
                    >
                        <Icon
                            :icon="row.icon"
                            class="mt-0.5 h-4 w-4 shrink-0 text-blue-800 dark:text-blue-400"
                            aria-hidden="true"
                        />
                        <div class="min-w-0">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ row.label }}:</span>
                            <a
                                v-if="row.href"
                                :href="row.href"
                                class="ml-1 break-all font-medium text-blue-800 underline-offset-2 hover:underline dark:text-blue-400"
                                :target="row.external ? '_blank' : undefined"
                                :rel="row.external ? 'noopener noreferrer' : undefined"
                            >
                                {{ row.text }}
                            </a>
                            <span v-else class="ml-1">{{ row.text }}</span>
                        </div>
                    </li>
                </ul>
            </div>
            <p class="mt-8 border-t border-slate-100 pt-6 text-center text-xs text-slate-500 dark:border-slate-800 dark:text-slate-500">
                © {{ new Date().getFullYear() }} — UTC eLibrary · {{ S.footerCopyrightNote }}
            </p>
        </div>
    </footer>
</template>
