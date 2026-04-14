<script setup>
import { Link } from '@inertiajs/vue3'
import { readerLayoutStrings as S } from '@/config/readerStrings'
</script>

<template>
    <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-md">
                    <p class="text-sm font-bold text-blue-900 dark:text-blue-300">{{ S.footerTitle }}</p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ S.footerBlurb }}</p>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ S.footerEmailLabel }}:</span>
                        <a href="mailto:thuvien@utc.edu.vn" class="ml-1 font-medium text-blue-800 underline-offset-2 hover:underline dark:text-blue-400">thuvien@utc.edu.vn</a>
                    </p>
                </div>
                <nav class="flex flex-col gap-2 text-sm font-semibold" :aria-label="S.quickLinks">
                    <Link :href="route('reader.about')" class="text-slate-700 hover:text-blue-800 dark:text-slate-300 dark:hover:text-blue-400">{{ S.about }}</Link>
                    <Link :href="route('reader.regulations.index')" class="text-slate-700 hover:text-blue-800 dark:text-slate-300 dark:hover:text-blue-400">{{ S.regulationsShort }}</Link>
                    <Link :href="route('reader.catalog')" class="text-slate-700 hover:text-blue-800 dark:text-slate-300 dark:hover:text-blue-400">{{ S.catalog }}</Link>
                    <Link :href="route('reader.services')" class="text-slate-700 hover:text-blue-800 dark:text-slate-300 dark:hover:text-blue-400">{{ S.services }}</Link>
                </nav>
            </div>
            <p class="mt-8 text-center text-xs text-slate-500 dark:text-slate-500">
                © {{ new Date().getFullYear() }} — UTC eLibrary · {{ S.footerReferenceNote }}
            </p>
        </div>
    </footer>
</template>
