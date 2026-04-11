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

function go(p) {
    if (props.disabled) return;
    const n = Number(p);
    if (!Number.isFinite(n) || n < 1 || n > props.lastPage) return;
    emit('go-page', n);
}

const show = computed(() => props.alwaysShow || props.lastPage > 1);
const isSinglePage = computed(() => (Number(props.lastPage) || 1) <= 1);
</script>

<template>
    <div
        v-if="show"
        class="flex items-center justify-center gap-1 sm:gap-2 flex-wrap pt-2"
        role="navigation"
        aria-label="Phân trang"
    >
        <span
            v-if="isSinglePage"
            class="text-sm text-slate-600 dark:text-slate-300"
        >
            Trang {{ currentPage }} / {{ lastPage }}
        </span>

        <template v-else>
        <button
            type="button"
            class="admin-filter-btn min-h-[44px] min-w-[44px] !h-auto py-2.5 px-2 sm:px-3 disabled:opacity-50 disabled:pointer-events-none shrink-0"
            title="Về trang 1"
            aria-label="Về trang đầu (trang 1)"
            :disabled="disabled || currentPage <= 1"
            @click="go(1)"
        >
            Đầu
        </button>
        <button
            type="button"
            class="admin-filter-btn min-h-[44px] min-w-[44px] !h-auto py-2.5 px-3 sm:px-4 disabled:opacity-50 disabled:pointer-events-none shrink-0"
            :disabled="disabled || currentPage <= 1"
            @click="go(currentPage - 1)"
        >
            Trước
        </button>

        <div class="flex items-center justify-center gap-1 flex-wrap">
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
                        item.value === currentPage
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'admin-filter-btn !min-h-[44px] !min-w-[44px]'
                    "
                    :aria-current="item.value === currentPage ? 'page' : undefined"
                    :disabled="disabled"
                    @click="go(item.value)"
                >
                    {{ item.value }}
                </button>
            </template>
        </div>

        <button
            type="button"
            class="admin-filter-btn min-h-[44px] min-w-[44px] !h-auto py-2.5 px-3 sm:px-4 disabled:opacity-50 disabled:pointer-events-none shrink-0"
            :disabled="disabled || currentPage >= lastPage"
            @click="go(currentPage + 1)"
        >
            Sau
        </button>
        <button
            type="button"
            class="admin-filter-btn min-h-[44px] min-w-[44px] !h-auto py-2.5 px-2 sm:px-3 disabled:opacity-50 disabled:pointer-events-none shrink-0"
            title="Về trang cuối"
            :aria-label="`Về trang cuối (trang ${lastPage})`"
            :disabled="disabled || currentPage >= lastPage"
            @click="go(lastPage)"
        >
            Cuối
        </button>

        <span class="w-full sm:w-auto text-center sm:text-left text-xs text-slate-500 dark:text-slate-400 basis-full sm:basis-auto mt-1 sm:mt-0 sm:ml-2">
            Trang {{ currentPage }} / {{ lastPage }}
        </span>
        </template>
    </div>
</template>
