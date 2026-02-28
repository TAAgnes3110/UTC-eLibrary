<script setup>
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            activeLoans: 0,
            overdueCount: 0,
            hasCard: false,
        }),
    },
});

const statsCards = [
    { title: 'Sách đang mượn', value: String(props.stats.activeLoans ?? 0), icon: 'lucide:book-open', color: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-50 dark:bg-indigo-950/40' },
    { title: 'Sách quá hạn', value: String(props.stats.overdueCount ?? 0), icon: 'lucide:alert-circle', color: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-50 dark:bg-rose-950/40' },
    { title: 'Thẻ thư viện', value: props.stats.hasCard ? 'Có thẻ' : 'Chưa có', icon: 'lucide:credit-card', color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/40' },
];

const quickActions = [
    { label: 'Tra cứu sách', icon: 'lucide:search', href: 'library.search' },
    { label: 'Sách mượn', icon: 'lucide:clipboard-list', href: 'library.loans' },
    { label: 'Xem thẻ', icon: 'lucide:credit-card', href: 'library.card' },
];

const handleAction = (action) => {
    if (action?.href) router.visit(route(action.href));
};
</script>

<template>
    <Head title="Tổng quan - Thư viện số" />
    <ReaderDashboardLayout title="Tổng quan">
        <div class="space-y-8 animate-in fade-in-50 duration-500">
            <!-- Welcome -->
            <div class="bg-blue-900 dark:bg-blue-950 rounded-3xl p-6 lg:p-10 text-white relative overflow-hidden">
                <div class="relative z-10 max-w-2xl">
                    <h2 class="text-2xl lg:text-3xl font-black mb-4">Chào mừng đến Thư viện số</h2>
                    <p class="text-blue-200 dark:text-blue-300 text-base lg:text-lg mb-8">
                        Tra cứu sách, xem thẻ thư viện và quản lý sách mượn của bạn.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <button
                            v-for="action in quickActions"
                            :key="action.label"
                            type="button"
                            class="px-4 lg:px-6 py-2 lg:py-3 bg-white text-blue-900 rounded-xl font-bold hover:bg-blue-50 transition-all hover:scale-105 active:scale-95 shadow-lg border-none"
                            @click="handleAction(action)"
                        >
                            <Icon :icon="action.icon" class="w-4 h-4 mr-2 inline" />
                            {{ action.label }}
                        </button>
                    </div>
                </div>
                <Icon icon="lucide:library" class="absolute -right-12 -bottom-12 w-64 h-64 opacity-10" />
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="stat in statsCards"
                    :key="stat.title"
                    class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md transition-all"
                >
                    <div :class="[stat.bg, 'w-12 h-12 rounded-xl flex items-center justify-center mb-4']">
                        <Icon :icon="stat.icon" :class="[stat.color, 'w-6 h-6']" />
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ stat.value }}</h3>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">{{ stat.title }}</p>
                </div>
            </div>

            <!-- Quick links -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    :href="route('library.search')"
                    class="flex items-center gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all"
                >
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center">
                        <Icon icon="lucide:search" class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">Tra cứu sách</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tìm sách, xem tình trạng mượn</p>
                    </div>
                </Link>
                <Link
                    :href="route('library.saved')"
                    class="flex items-center gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all"
                >
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center">
                        <Icon icon="lucide:bookmark" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">Sách đã lưu</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Danh sách sách bạn đã lưu</p>
                    </div>
                </Link>
                <Link
                    :href="route('library.loans')"
                    class="flex items-center gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all"
                >
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center">
                        <Icon icon="lucide:clipboard-list" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">Sách mượn</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Phiếu mượn, hạn trả, gia hạn</p>
                    </div>
                </Link>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>
