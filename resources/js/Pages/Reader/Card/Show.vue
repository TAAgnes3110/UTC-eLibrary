<script setup>
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    card: {
        type: Object,
        default: null,
    },
});

const statusLabel = (s) => {
    const map = { active: 'Đang hoạt động', locked: 'Bị khóa', expired: 'Hết hạn', lost: 'Mất thẻ' };
    return map[s] || s;
};
</script>

<template>
    <Head title="Thẻ thư viện - UTC eLibrary" />
    <ReaderDashboardLayout title="Xem thẻ / Quản lý thẻ">
        <div class="space-y-6 max-w-2xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Thẻ thư viện</h1>
                <Link :href="route('library.dashboard')" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Tổng quan</Link>
            </div>

            <div v-if="!card" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <Icon icon="lucide:credit-card" class="w-8 h-8" />
                </div>
                <p class="text-slate-600 dark:text-slate-400">Bạn chưa có thẻ thư viện.</p>
                <p class="text-sm text-slate-500 dark:text-slate-500 mt-1">Liên hệ thủ thư để được cấp thẻ.</p>
            </div>

            <div v-else class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Số thẻ</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white font-mono">{{ card.card_number }}</p>
                        </div>
                        <span
                            :class="{
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400': card.status === 'active',
                                'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400': card.status === 'expired' || card.status === 'locked',
                                'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400': card.status === 'lost',
                            }"
                            class="rounded-lg px-3 py-1 text-sm font-medium"
                        >
                            {{ statusLabel(card.status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Ngày cấp</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ card.issue_date || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Ngày hết hạn</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ card.expiry_date || '—' }}</p>
                        </div>
                        <div v-if="card.faculty" class="col-span-2">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Khoa / Lớp</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ card.faculty }}{{ card.class ? ' · ' + card.class : '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>
