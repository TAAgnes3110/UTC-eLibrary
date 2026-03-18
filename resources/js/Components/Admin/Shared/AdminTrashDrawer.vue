<script setup>
import { ref, computed, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Thùng rác' },
    itemLabelKey: { type: String, default: 'name' },
    items: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Tìm trong thùng rác...' },
    enableBulkDelete: { type: Boolean, default: true },
    enableBulkRestore: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'restore', 'restore-many', 'force-delete', 'force-delete-many']);

const keyword = ref('');
const selectedIds = ref([]);

const getLabel = (item) => {
    const key = props.itemLabelKey;
    return item[key] ?? item.title ?? item.name ?? item.code ?? `#${item.id}`;
};

const filteredItems = computed(() => {
    const kw = keyword.value.trim().toLowerCase();
    if (!kw) return props.items;
    return props.items.filter((item) => {
        const label = String(getLabel(item)).toLowerCase();
        const code = String(item.code ?? '').toLowerCase();
        return label.includes(kw) || code.includes(kw);
    });
});

watch(
    () => [props.show, props.items],
    () => {
        selectedIds.value = [];
    },
    { deep: true }
);

const filteredIds = computed(() => filteredItems.value.map((it) => it.id));
const allSelected = computed(() => {
    const ids = filteredIds.value;
    return ids.length > 0 && ids.every((id) => selectedIds.value.includes(id));
});
const selectedCount = computed(() => selectedIds.value.length);

const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};
const toggleSelectAll = () => {
    const ids = filteredIds.value;
    if (ids.length === 0) return;
    if (allSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id));
    } else {
        const merged = new Set([...selectedIds.value, ...ids]);
        selectedIds.value = Array.from(merged);
    }
};
const bulkForceDelete = () => {
    if (selectedIds.value.length === 0) return;
    emit('force-delete-many', [...selectedIds.value]);
};

const bulkForceDeleteAll = () => {
    const ids = filteredIds.value;
    if (!ids || ids.length === 0) return;
    emit('force-delete-many', [...ids]);
};

const bulkRestoreSelected = () => {
    if (selectedIds.value.length === 0) return;
    emit('restore-many', [...selectedIds.value]);
};

const bulkRestoreAll = () => {
    const ids = filteredIds.value;
    if (!ids || ids.length === 0) return;
    emit('restore-many', [...ids]);
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
                class="relative w-full max-w-lg md:max-w-xl ml-auto h-full bg-white dark:bg-slate-900 shadow-xl border-l border-slate-200 dark:border-slate-800 flex flex-col animate-in slide-in-from-right duration-200"
            >
                <div class="px-4 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <Icon icon="lucide:trash-2" class="w-5 h-5 text-slate-500" />
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ title }}</h2>
                        </div>
                        <button type="button" @click="$emit('close')" class="p-1.5 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <label v-if="enableBulkDelete || enableBulkRestore" class="flex items-center gap-2 select-none">
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 dark:border-slate-600"
                                :checked="allSelected"
                                @change="toggleSelectAll"
                            />
                            <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ selectedCount }}/{{ filteredIds.length }}
                            </span>
                        </label>
                        <Input
                            v-model="keyword"
                            type="search"
                            class="h-9 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900"
                            :placeholder="searchPlaceholder"
                        />
                    </div>
                    <div v-if="enableBulkDelete || enableBulkRestore" class="pt-1">
                        <div class="flex items-center gap-1.5 w-full">
                            <Button
                                v-if="enableBulkRestore"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-7 px-2 text-[11px] gap-1 whitespace-nowrap flex-1 justify-center"
                                :disabled="selectedCount === 0"
                                @click="bulkRestoreSelected"
                                title="Khôi phục đã chọn"
                            >
                                <Icon icon="lucide:rotate-ccw" class="w-4 h-4" />
                                <span class="hidden md:inline">Khôi phục chọn</span>
                            </Button>
                            <Button
                                v-if="enableBulkRestore"
                                type="button"
                                variant="secondary"
                                size="sm"
                                class="h-7 px-2 text-[11px] gap-1 whitespace-nowrap flex-1 justify-center"
                                :disabled="filteredIds.length === 0"
                                @click="bulkRestoreAll"
                                title="Khôi phục tất cả"
                            >
                                <Icon icon="lucide:rotate-ccw" class="w-4 h-4" />
                                <span class="hidden md:inline">Khôi phục tất cả</span>
                            </Button>
                            <Button
                                v-if="enableBulkDelete"
                                type="button"
                                variant="destructive"
                                size="sm"
                                class="h-7 px-2 text-[11px] gap-1 whitespace-nowrap flex-1 justify-center"
                                :disabled="selectedCount === 0"
                                @click="bulkForceDelete"
                                title="Xóa đã chọn"
                            >
                                <Icon icon="lucide:trash-2" class="w-4 h-4" />
                                <span class="hidden md:inline">Xóa chọn</span>
                            </Button>
                            <Button
                                v-if="enableBulkDelete"
                                type="button"
                                variant="destructive"
                                size="sm"
                                class="h-7 px-2 text-[11px] gap-1 whitespace-nowrap flex-1 justify-center"
                                :disabled="filteredIds.length === 0"
                                @click="bulkForceDeleteAll"
                                title="Xóa tất cả"
                            >
                                <Icon icon="lucide:trash-2" class="w-4 h-4" />
                                <span class="hidden md:inline">Xóa tất cả</span>
                            </Button>
                        </div>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <p v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">Đang tải...</p>
                    <template v-else-if="filteredItems.length === 0">
                        <div class="py-8 text-center">
                            <Icon icon="lucide:trash-2" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                            <p class="text-sm text-slate-500 dark:text-slate-400">Không tìm thấy mục phù hợp trong thùng rác</p>
                        </div>
                    </template>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="item in filteredItems"
                            :key="item.id"
                            class="flex items-center justify-between gap-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30"
                        >
                            <div class="flex items-start gap-2 min-w-0 flex-1">
                                <input
                                    v-if="enableBulkDelete"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-slate-300 dark:border-slate-600"
                                    :checked="selectedIds.includes(item.id)"
                                    @change="toggleSelect(item.id)"
                                />
                                <div class="min-w-0 flex-1">
                                <p class="font-medium text-sm text-slate-900 dark:text-white truncate">{{ getLabel(item) }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Xóa lúc {{ formatDate(item.deleted_at) }}</p>
                                </div>
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
