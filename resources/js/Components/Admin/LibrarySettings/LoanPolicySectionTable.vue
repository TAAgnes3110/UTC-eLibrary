<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    variant: {
        type: String,
        default: 'student',
        validator: (v) => ['student', 'teacher', 'external'].includes(v),
    },
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    showTextbookLimits: { type: Boolean, default: true },
    boolLabel: { type: Function, required: true },
});

const emit = defineEmits(['edit']);

const variantClasses = {
    student: {
        ring: 'ring-blue-200/80 dark:ring-blue-900/50',
        bar: 'bg-blue-600 dark:bg-blue-500',
        icon: 'text-blue-600 dark:text-blue-400',
        iconName: 'lucide:graduation-cap',
    },
    teacher: {
        ring: 'ring-violet-200/80 dark:ring-violet-900/50',
        bar: 'bg-violet-600 dark:bg-violet-500',
        icon: 'text-violet-600 dark:text-violet-400',
        iconName: 'lucide:chalkboard-user',
    },
    external: {
        ring: 'ring-amber-200/80 dark:ring-amber-900/50',
        bar: 'bg-amber-600 dark:bg-amber-500',
        icon: 'text-amber-600 dark:text-amber-400',
        iconName: 'lucide:users-round',
    },
};

const vc = computed(() => variantClasses[props.variant] ?? variantClasses.student);
</script>

<template>
    <section
        class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden ring-1"
        :class="vc.ring"
    >
        <div class="flex items-start gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/40">
            <div class="w-1 self-stretch rounded-full shrink-0 min-h-[44px]" :class="vc.bar" aria-hidden="true" />
            <div class="min-w-0 flex-1 pt-0.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <Icon :icon="vc.iconName" class="w-5 h-5 shrink-0" :class="vc.icon" />
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ title }}</h3>
                </div>
                <p v-if="description" class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ description }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[640px] w-full text-sm text-left">
                <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300">
                    <tr>
                        <th class="px-3 py-2.5 font-semibold whitespace-nowrap">Mã</th>
                        <th class="px-3 py-2.5 font-semibold min-w-[140px]">Tên cấu hình</th>
                        <th class="px-3 py-2.5 font-semibold text-center whitespace-nowrap">Tối đa sách</th>
                        <th class="px-3 py-2.5 font-semibold text-center whitespace-nowrap">Hạn (ngày)</th>
                        <th class="px-3 py-2.5 font-semibold text-center whitespace-nowrap">Gia hạn</th>
                        <th class="px-3 py-2.5 font-semibold text-center whitespace-nowrap">Phạt/ngày</th>
                        <th v-if="showTextbookLimits" class="px-3 py-2.5 font-semibold text-center whitespace-nowrap">GT / TLTK</th>
                        <th class="px-3 py-2.5 font-semibold text-center whitespace-nowrap">Về nhà</th>
                        <th class="px-3 py-2.5 font-semibold text-center whitespace-nowrap">Tại chỗ</th>
                        <th class="px-3 py-2.5 font-semibold text-right whitespace-nowrap w-24">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td :colspan="showTextbookLimits ? 10 : 9" class="px-3 py-8 text-center text-slate-500">Đang tải…</td>
                    </tr>
                    <tr v-else-if="!rows.length">
                        <td :colspan="showTextbookLimits ? 10 : 9" class="px-3 py-8 text-center text-slate-500">
                            <slot name="empty">Chưa có dòng cấu hình cho nhóm này.</slot>
                        </td>
                    </tr>
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        class="border-t border-slate-100 dark:border-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/30"
                    >
                        <td class="px-3 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ row.code }}</td>
                        <td class="px-3 py-2.5 text-slate-800 dark:text-slate-200">{{ row.name }}</td>
                        <td class="px-3 py-2.5 text-center tabular-nums">{{ row.max_books }}</td>
                        <td class="px-3 py-2.5 text-center tabular-nums">{{ row.max_days }}</td>
                        <td class="px-3 py-2.5 text-center tabular-nums">{{ row.max_renewals }}</td>
                        <td class="px-3 py-2.5 text-center tabular-nums text-xs">{{ row.overdue_fine_per_day }}</td>
                        <td v-if="showTextbookLimits" class="px-3 py-2.5 text-center text-xs text-slate-600 dark:text-slate-400">
                            <template v-if="row.params && (row.params.max_textbooks != null || row.params.max_reference != null)">
                                {{ row.params.max_textbooks ?? '—' }} / {{ row.params.max_reference ?? '—' }}
                            </template>
                            <span v-else>—</span>
                        </td>
                        <td class="px-3 py-2.5 text-center">{{ boolLabel(row.allow_home) }}</td>
                        <td class="px-3 py-2.5 text-center">{{ boolLabel(row.allow_onsite) }}</td>
                        <td class="px-3 py-2.5 text-right">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="min-h-11 min-w-11 px-3 text-blue-600 dark:text-blue-400"
                                @click="emit('edit', row)"
                            >
                                <Icon icon="lucide:pencil" class="w-4 h-4 sm:mr-1" />
                                <span class="hidden sm:inline">Sửa</span>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
