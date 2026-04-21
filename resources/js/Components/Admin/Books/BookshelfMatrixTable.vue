<script setup>
import { computed } from 'vue';

const props = defineProps({
    books: { type: Array, default: () => [] },
    activeClassificationId: { type: [String, Number, null], default: null },
    activeClassificationDetailId: { type: [String, Number, null], default: null },
});

const emit = defineEmits(['select-cell', 'clear-filter']);

const classifications = computed(() => {
    const map = new Map();
    for (const book of props.books || []) {
        const cls = book?.classification;
        if (!cls?.id) continue;
        map.set(String(cls.id), {
            id: cls.id,
            code: cls.code || '',
            name: cls.name || '',
        });
    }
    return Array.from(map.values()).sort((a, b) => {
        const aCode = String(a.code || '').toLowerCase();
        const bCode = String(b.code || '').toLowerCase();
        if (aCode && bCode) return aCode.localeCompare(bCode, 'vi');
        return String(a.name || '').localeCompare(String(b.name || ''), 'vi');
    });
});

const classificationDetails = computed(() => {
    const map = new Map();
    for (const book of props.books || []) {
        const detail = book?.classification_detail;
        if (!detail?.id) continue;
        map.set(String(detail.id), {
            id: detail.id,
            code: detail.code || '',
            name: detail.name || '',
            classification_id: detail.classification_id ?? null,
        });
    }
    return Array.from(map.values()).sort((a, b) => {
        const aCode = String(a.code || '').toLowerCase();
        const bCode = String(b.code || '').toLowerCase();
        if (aCode && bCode) return aCode.localeCompare(bCode, 'vi');
        return String(a.name || '').localeCompare(String(b.name || ''), 'vi');
    });
});

const matrixCountMap = computed(() => {
    const counts = new Map();
    for (const book of props.books || []) {
        const rowId = book?.classification?.id;
        const colId = book?.classification_detail?.id;
        if (!rowId || !colId) continue;
        const key = `${rowId}:${colId}`;
        counts.set(key, (counts.get(key) || 0) + 1);
    }
    return counts;
});

function cellCount(rowId, colId) {
    return matrixCountMap.value.get(`${rowId}:${colId}`) || 0;
}

function isActiveCell(rowId, colId) {
    return (
        String(props.activeClassificationId || '') === String(rowId) &&
        String(props.activeClassificationDetailId || '') === String(colId)
    );
}

function labelOf(item) {
    if (!item) return '—';
    if (item.code && item.name) return `${item.code} - ${item.name}`;
    return item.code || item.name || '—';
}
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Bảng ma trận kệ sách</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Hàng là phân loại chính, cột là phân loại chi tiết. Bấm vào ô để lọc sách theo vị trí.
                </p>
            </div>
            <button
                type="button"
                class="px-3 h-8 rounded-md border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800"
                @click="emit('clear-filter')"
            >
                Bỏ lọc ô đã chọn
            </button>
        </div>

        <div v-if="classifications.length === 0 || classificationDetails.length === 0" class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400">
            Chưa đủ dữ liệu phân loại để hiển thị ma trận.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[900px] border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60">
                        <th class="sticky left-0 z-[1] bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 px-3 py-2 text-left min-w-[220px]">
                            Phân loại chính \ Chi tiết
                        </th>
                        <th
                            v-for="detail in classificationDetails"
                            :key="`col-${detail.id}`"
                            class="border border-slate-200 dark:border-slate-700 px-2 py-2 min-w-[110px] text-center text-slate-700 dark:text-slate-200"
                        >
                            <span class="line-clamp-2">{{ labelOf(detail) }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in classifications" :key="`row-${row.id}`">
                        <th class="sticky left-0 z-[1] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-3 py-2 text-left font-medium text-slate-700 dark:text-slate-200">
                            {{ labelOf(row) }}
                        </th>
                        <td
                            v-for="col in classificationDetails"
                            :key="`cell-${row.id}-${col.id}`"
                            class="border border-slate-200 dark:border-slate-700 p-1"
                        >
                            <button
                                type="button"
                                class="w-full min-h-[36px] rounded-md text-xs font-semibold transition-colors"
                                :class="
                                    isActiveCell(row.id, col.id)
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-50 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-slate-700'
                                "
                                @click="emit('select-cell', { classificationId: row.id, classificationDetailId: col.id })"
                            >
                                {{ cellCount(row.id, col.id) }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
