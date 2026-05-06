<script setup>
import { computed } from 'vue';
import { buildAdminPaginationItems } from '@/utils/adminPagination';

const props = defineProps({
    /** Trang đang hiển thị / đang tải (1-based) */
    currentPage: { type: Number, default: 1 },
    lastPage: { type: Number, default: 1 },
    /** Số trang mỗi phía quanh trang hiện tại (mặc định ±3) */
    neighbor: { type: Number, default: 3 },
    /** Luôn hiển thị N trang cuối (mặc định 3) */
    tail: { type: Number, default: 3 },
    /** Vô hiệu hóa toàn bộ (đang tải…) */
    disabled: { type: Boolean, default: false },
    /** Luôn hiển thị (kể cả 1 trang — vẫn có “Trang 1 / 1”, nút Đầu/Cuối bị khóa) */
    alwaysShow: { type: Boolean, default: false },
});

const emit = defineEmits(['go-page']);

const pageItems = computed(() =>
    buildAdminPaginationItems(props.currentPage, props.lastPage, {
        neighbor: props.neighbor,
        tail: props.tail,
    }),
);

const safeCurrentPage = computed(() => Math.max(1, Number(props.currentPage) || 1));
const safeLastPage = computed(() => Math.max(1, Number(props.lastPage) || 1));
const isFirstPage = computed(() => safeCurrentPage.value <= 1);
const isLastPage = computed(() => safeCurrentPage.value >= safeLastPage.value);

function go(p) {
    if (props.disabled) return;
    const n = Number(p);
    if (!Number.isFinite(n) || n < 1 || n > safeLastPage.value) return;
    emit('go-page', n);
}

const show = computed(() => props.alwaysShow || safeLastPage.value > 1);
</script>

<template>
    <div
        v-if="show"
        class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 pt-2"
        role="navigation"
        aria-label="Phân trang"
    >
        <div class="flex items-center gap-1.5 sm:gap-2 order-1">
            <button
                type="button"
                class="admin-filter-btn min-h-[44px] min-w-[44px] !h-auto py-2.5 px-2 sm:px-3 disabled:opacity-50 disabled:pointer-events-none shrink-0"
                title="Về trang 1"
                aria-label="Về trang đầu (trang 1)"
                :disabled="disabled || isFirstPage"
                @click="go(1)"
            >
                Đầu
            </button>
            <button
                type="button"
                class="admin-filter-btn min-h-[44px] min-w-[44px] !h-auto py-2.5 px-3 sm:px-4 disabled:opacity-50 disabled:pointer-events-none shrink-0"
                :disabled="disabled || isFirstPage"
                @click="go(safeCurrentPage - 1)"
            >
                Trước
            </button>
        </div>

        <div class="flex items-center justify-center gap-1 flex-wrap order-2">
            <template v-for="(item, idx) in pageItems" :key="item.type === 'page' ? `p-${item.value}` : `e-${idx}`">
                <span
                    v-if="item.type === 'ellipsis'"
                    class="min-w-[44px] min-h-[44px] inline-flex items-center justify-center text-sm text-slate-500 dark:text-slate-400 px-1 select-none"
                    aria-hidden="true"
                >
                    …
                </span>
                <button
                    v-else
                    type="button"
                    class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors disabled:opacity-50 disabled:pointer-events-none shrink-0 px-2"
                    :class="
                        item.value === safeCurrentPage
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'admin-filter-btn !min-h-[44px] !min-w-[44px]'
                    "
                    :aria-current="item.value === safeCurrentPage ? 'page' : undefined"
                    :disabled="disabled || item.value === safeCurrentPage"
                    @click="go(item.value)"
                >
                    {{ item.value }}
                </button>
            </template>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 order-3">
            <button
                type="button"
                class="admin-filter-btn min-h-[44px] min-w-[44px] !h-auto py-2.5 px-3 sm:px-4 disabled:opacity-50 disabled:pointer-events-none shrink-0"
                :disabled="disabled || isLastPage"
                @click="go(safeCurrentPage + 1)"
            >
                Sau
            </button>
            <button
                type="button"
                class="admin-filter-btn min-h-[44px] min-w-[44px] !h-auto py-2.5 px-2 sm:px-3 disabled:opacity-50 disabled:pointer-events-none shrink-0"
                title="Về trang cuối"
                :aria-label="`Về trang cuối (trang ${safeLastPage})`"
                :disabled="disabled || isLastPage"
                @click="go(safeLastPage)"
            >
                Cuối
            </button>
        </div>

        <span class="order-4 w-full text-center text-xs text-slate-500 dark:text-slate-400 mt-1">
            Trang {{ safeCurrentPage }} / {{ safeLastPage }}
        </span>
    </div>
</template>
