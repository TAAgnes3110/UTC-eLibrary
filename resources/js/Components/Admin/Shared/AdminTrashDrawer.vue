<script setup>
/**
 * Drawer Thùng rác / Lịch sử xóa.
 * Hiển thị danh sách bản ghi đã xóa mềm; cho phép Khôi phục hoặc Xóa vĩnh viễn.
 * Parent truyền :items (đã trashed) và @restore / @force-delete.
 */
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    show: { type: Boolean, default: false },
    /** Tiêu đề (vd: "Thùng rác – Sách", "Lịch sử xóa – Tài khoản") */
    title: { type: String, default: 'Thùng rác' },
    /** Tên trường hiển thị (vd: 'title', 'name') */
    itemLabelKey: { type: String, default: 'name' },
    /** Danh sách bản ghi đã xóa: [{ id, deleted_at, ... }] */
    items: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

defineEmits(['close', 'restore', 'force-delete']);

const getLabel = (item) => {
    const key = props.itemLabelKey;
    return item[key] ?? item.title ?? item.name ?? item.code ?? `#${item.id}`;
};

const formatDate = (v) => {
    if (!v) return '—';
    const s = String(v).slice(0, 19).replace('T', ' ');
    return s;
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[110] flex" @click.self="$emit('close')">
            <div class="absolute inset-0 bg-slate-900/50" @click="$emit('close')" />
            <div
                class="relative w-full max-w-md ml-auto h-full bg-white dark:bg-slate-900 shadow-xl border-l border-slate-200 dark:border-slate-800 flex flex-col animate-in slide-in-from-right duration-200"
            >
                <div class="px-4 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-2">
                        <Icon icon="lucide:trash-2" class="w-5 h-5 text-slate-500" />
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ title }}</h2>
                    </div>
                    <button type="button" @click="$emit('close')" class="p-1.5 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <p v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">Đang tải...</p>
                    <template v-else-if="items.length === 0">
                        <div class="py-8 text-center">
                            <Icon icon="lucide:trash-2" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                            <p class="text-sm text-slate-500 dark:text-slate-400">Chưa có mục nào trong thùng rác</p>
                        </div>
                    </template>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="item in items"
                            :key="item.id"
                            class="flex items-center justify-between gap-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-sm text-slate-900 dark:text-white truncate">{{ getLabel(item) }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Xóa lúc {{ formatDate(item.deleted_at) }}</p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    @click="$emit('restore', item.id)"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors"
                                    title="Khôi phục"
                                >
                                    <Icon icon="lucide:rotate-ccw" class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="$emit('force-delete', item.id)"
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                    title="Xóa vĩnh viễn"
                                >
                                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </Teleport>
</template>
