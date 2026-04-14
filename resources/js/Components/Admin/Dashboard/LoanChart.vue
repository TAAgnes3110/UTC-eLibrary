<script setup>
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Icon } from '@iconify/vue';
import { computed } from 'vue';

const props = defineProps({
    granularity: {
        type: String,
        default: 'month',
    },
    series: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    forecast: {
        type: Object,
        default: () => ({ next_label: '-', expected_borrowed: 0 }),
    },
});

const emit = defineEmits(['update:granularity', 'refresh']);

const modes = [
    { key: 'day', label: 'Theo ngày' },
    { key: 'month', label: 'Theo tháng' },
    { key: 'year', label: 'Theo năm' },
];

const maxValue = computed(() => {
    const vals = (props.series || []).flatMap((item) => [Number(item.borrowed || 0), Number(item.returned || 0)]);
    const max = vals.length ? Math.max(...vals) : 0;
    return max > 0 ? max : 1;
});

function chooseMode(mode) {
    if (mode === props.granularity) return;
    emit('update:granularity', mode);
}

const chartWidth = 760;
const chartHeight = 260;
const chartPadding = { top: 18, right: 18, bottom: 30, left: 24 };
const innerWidth = chartWidth - chartPadding.left - chartPadding.right;
const innerHeight = chartHeight - chartPadding.top - chartPadding.bottom;

function pointX(index) {
    const count = props.series.length;
    if (count <= 1) return chartPadding.left + innerWidth / 2;
    const step = innerWidth / (count - 1);
    return chartPadding.left + step * index;
}

function pointY(value) {
    const numeric = Number(value || 0);
    const ratio = Math.min(Math.max(numeric / maxValue.value, 0), 1);
    return chartPadding.top + (1 - ratio) * innerHeight;
}

const borrowedLinePath = computed(() => {
    if (!props.series.length) return '';
    return props.series
        .map((item, index) => `${index === 0 ? 'M' : 'L'} ${pointX(index)} ${pointY(item.borrowed)}`)
        .join(' ');
});

const returnedLinePath = computed(() => {
    if (!props.series.length) return '';
    return props.series
        .map((item, index) => `${index === 0 ? 'M' : 'L'} ${pointX(index)} ${pointY(item.returned)}`)
        .join(' ');
});

const borrowedAreaPath = computed(() => {
    if (!props.series.length) return '';
    const firstX = pointX(0);
    const lastX = pointX(props.series.length - 1);
    return `${borrowedLinePath.value} L ${lastX} ${chartPadding.top + innerHeight} L ${firstX} ${chartPadding.top + innerHeight} Z`;
});

const totals = computed(() => {
    const borrowed = props.series.reduce((sum, item) => sum + Number(item.borrowed || 0), 0);
    const returned = props.series.reduce((sum, item) => sum + Number(item.returned || 0), 0);
    const outstanding = Math.max(0, borrowed - returned);
    return { borrowed, returned, outstanding };
});

const donutSegments = computed(() => {
    const items = [
        { key: 'borrowed', label: 'Mượn', value: totals.value.borrowed, color: '#4f46e5' },
        { key: 'returned', label: 'Trả', value: totals.value.returned, color: '#10b981' },
        { key: 'outstanding', label: 'Đang giữ', value: totals.value.outstanding, color: '#f59e0b' },
    ];
    const total = items.reduce((sum, item) => sum + item.value, 0);
    if (total <= 0) {
        return items.map((item) => ({ ...item, percent: 0, dash: 0, offset: 0, total: 0 }));
    }
    const circumference = 2 * Math.PI * 44;
    let consumed = 0;
    return items.map((item) => {
        const percent = item.value / total;
        const dash = percent * circumference;
        const offset = -consumed;
        consumed += dash;
        return { ...item, percent, dash, offset, total };
    });
});
</script>

<template>
    <Card class="border-none shadow-sm dark:bg-slate-900 transition-colors">
        <CardHeader class="pb-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <CardTitle class="text-lg font-bold dark:text-white">Thống kê mượn/trả sách</CardTitle>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="mode in modes"
                        :key="mode.key"
                        type="button"
                        class="h-11 min-w-[88px] rounded-xl border px-3 text-sm font-semibold transition"
                        :class="mode.key === granularity
                            ? 'border-indigo-500 bg-indigo-600 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                        @click="chooseMode(mode.key)"
                    >
                        {{ mode.label }}
                    </button>
                    <button
                        type="button"
                        class="h-11 min-w-[88px] rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        @click="emit('refresh')"
                    >
                        Làm mới
                    </button>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <div
                v-if="loading"
                class="h-[320px] flex items-center justify-center text-slate-400 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-700"
            >
                <div class="text-center">
                    <Icon icon="lucide:loader-2" class="h-12 w-12 mx-auto mb-3 animate-spin text-slate-300 dark:text-slate-600" />
                    <p class="font-medium dark:text-slate-400">Đang tải dữ liệu thống kê...</p>
                </div>
            </div>

            <div
                v-else-if="!series.length"
                class="h-[320px] flex items-center justify-center text-slate-400 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-700"
            >
                <div class="text-center">
                    <Icon icon="lucide:bar-chart-3" class="h-12 w-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" />
                    <p class="font-medium dark:text-slate-400">Chưa có dữ liệu mượn/trả cho kỳ đã chọn</p>
                </div>
            </div>

            <div v-else class="space-y-4">
                <div class="flex items-center gap-4 text-sm">
                    <span class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-300">
                        <span class="h-3 w-3 rounded-full bg-indigo-500" />
                        Lượt mượn
                    </span>
                    <span class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-300">
                        <span class="h-3 w-3 rounded-full bg-emerald-500" />
                        Lượt trả
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <div class="xl:col-span-2 rounded-xl border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                        <div class="overflow-x-auto">
                            <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="min-w-[720px]">
                                <defs>
                                    <linearGradient id="borrowedFill" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#6366f1" stop-opacity="0.32" />
                                        <stop offset="100%" stop-color="#6366f1" stop-opacity="0.03" />
                                    </linearGradient>
                                </defs>

                                <g>
                                    <line
                                        v-for="level in 4"
                                        :key="`grid-${level}`"
                                        :x1="chartPadding.left"
                                        :x2="chartPadding.left + innerWidth"
                                        :y1="chartPadding.top + (innerHeight / 4) * level"
                                        :y2="chartPadding.top + (innerHeight / 4) * level"
                                        stroke="#e2e8f0"
                                        stroke-dasharray="4 4"
                                    />
                                </g>

                                <path :d="borrowedAreaPath" fill="url(#borrowedFill)" />
                                <path :d="borrowedLinePath" fill="none" stroke="#4f46e5" stroke-width="3" />
                                <path :d="returnedLinePath" fill="none" stroke="#10b981" stroke-width="3" />

                                <g v-for="(item, index) in series" :key="item.key">
                                    <circle :cx="pointX(index)" :cy="pointY(item.borrowed)" r="3.5" fill="#4f46e5" />
                                    <circle :cx="pointX(index)" :cy="pointY(item.returned)" r="3.5" fill="#10b981" />
                                    <text
                                        :x="pointX(index)"
                                        :y="chartPadding.top + innerHeight + 18"
                                        text-anchor="middle"
                                        font-size="10"
                                        fill="#64748b"
                                    >
                                        {{ item.label }}
                                    </text>
                                </g>
                            </svg>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">Tổng hợp kỳ hiện tại</h4>
                        <div class="flex items-center justify-center">
                            <svg viewBox="0 0 120 120" class="h-40 w-40 -rotate-90">
                                <circle cx="60" cy="60" r="44" fill="none" stroke="#e2e8f0" stroke-width="14" />
                                <circle
                                    v-for="segment in donutSegments"
                                    :key="segment.key"
                                    cx="60"
                                    cy="60"
                                    r="44"
                                    fill="none"
                                    :stroke="segment.color"
                                    stroke-width="14"
                                    stroke-linecap="butt"
                                    :stroke-dasharray="`${segment.dash} ${2 * Math.PI * 44 - segment.dash}`"
                                    :stroke-dashoffset="segment.offset"
                                />
                            </svg>
                        </div>

                        <div class="mt-3 space-y-2 text-sm">
                            <div
                                v-for="segment in donutSegments"
                                :key="`legend-${segment.key}`"
                                class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800"
                            >
                                <div class="inline-flex items-center gap-2 text-slate-700 dark:text-slate-200">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: segment.color }" />
                                    <span>{{ segment.label }}</span>
                                </div>
                                <div class="font-semibold text-slate-700 dark:text-slate-100">
                                    {{ segment.value }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4 text-sm text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-900/20 dark:text-indigo-100">
                    Dự báo kỳ kế tiếp (<strong>{{ forecast.next_label }}</strong>):
                    khoảng <strong>{{ forecast.expected_borrowed }}</strong> lượt mượn.
                </div>
            </div>
        </CardContent>
    </Card>
</template>
