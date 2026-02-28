<script setup>
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    loans: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Sách mượn - UTC eLibrary" />
    <ReaderDashboardLayout title="Sách mượn">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Sách đang mượn</h1>
                <Link :href="route('library.dashboard')" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Tổng quan</Link>
            </div>

            <div v-if="!loans.length" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <Icon icon="lucide:clipboard-list" class="w-8 h-8" />
                </div>
                <p class="text-slate-600 dark:text-slate-400">Bạn chưa có phiếu mượn nào.</p>
                <Link :href="route('library.search')" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Tra cứu sách
                    <Icon icon="lucide:arrow-right" class="h-4 w-4" />
                </Link>
            </div>

            <div v-else class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <th class="text-left py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Sách</th>
                                <th class="text-left py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Ngày mượn</th>
                                <th class="text-left py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Hạn trả</th>
                                <th class="text-left py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="loan in loans"
                                :key="loan.id"
                                class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30"
                            >
                                <td class="py-3 px-4">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ loan.book_title || '—' }}</p>
                                    <p v-if="loan.barcode" class="text-xs text-slate-500 dark:text-slate-400">Mã bản in: {{ loan.barcode }}</p>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ loan.loan_date || '—' }}</td>
                                <td class="py-3 px-4">
                                    <span :class="loan.is_overdue ? 'text-rose-600 dark:text-rose-400 font-medium' : 'text-slate-600 dark:text-slate-400'">
                                        {{ loan.due_date || '—' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        :class="{
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400': loan.status === 'active' && !loan.is_overdue,
                                            'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400': loan.is_overdue,
                                            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400': loan.status !== 'active',
                                        }"
                                        class="rounded-lg px-2 py-1 text-xs font-medium"
                                    >
                                        {{ loan.is_overdue ? 'Quá hạn' : (loan.status === 'active' ? 'Đang mượn' : loan.status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>
