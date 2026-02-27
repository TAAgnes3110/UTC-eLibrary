<script setup>
import { Icon } from '@iconify/vue';

/**
 * Thanh lọc + Tìm kiếm thống nhất mọi màn hình admin.
 * Layout: [Bộ lọc (tùy chọn)] [slot filters] [Ô tìm kiếm] [Tìm kiếm - xanh] | [slot actions]
 * Chế độ tối: nút Tìm kiếm dùng blue-600, nền slate.
 */
defineProps({
    searchPlaceholder: { type: String, default: 'Họ và tên, số phiếu, mã thẻ...' },
    /** Có hiện nút "Bộ lọc" bên trái */
    showFilterButton: { type: Boolean, default: false },
    modelValue: { type: String, default: '' },
});

defineEmits(['search', 'update:modelValue']);
</script>

<template>
    <div class="admin-filter-bar">
        <button
            v-if="showFilterButton"
            type="button"
            class="admin-filter-btn"
            aria-label="Bộ lọc"
        >
            <Icon icon="lucide:filter" class="w-4 h-4" />
            Bộ lọc
        </button>
        <slot name="filters" />
        <label class="sr-only">Tìm kiếm</label>
        <div class="admin-search-input-wrap">
            <Icon icon="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none text-slate-400 dark:text-slate-400" />
            <input
                :value="modelValue"
                type="text"
                :placeholder="searchPlaceholder"
                class="admin-search-input"
                @input="$emit('update:modelValue', $event.target?.value ?? '')"
            />
        </div>
        <button type="button" class="admin-search-btn" @click="$emit('search')">
            <Icon icon="lucide:search" class="w-4 h-4" />
            Tìm kiếm
        </button>
        <div v-if="$slots.actions" class="ml-auto flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
