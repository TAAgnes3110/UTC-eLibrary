<script setup>
import { Icon } from '@iconify/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    cards: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    mode: { type: String, default: 'manage' },
    clearSelectionToken: { type: Number, default: 0 },
});

const emit = defineEmits(['edit', 'approve', 'selection-change', 'delete-soft', 'update-photo', 'toggle-lock']);

const selectedIds = ref(new Set());

const hasSelection = computed(() => selectedIds.value.size > 0);

const isAllSelected = computed(() => {
    if (!props.cards.length) return false;
    return props.cards.every((c) => selectedIds.value.has(c.id));
});

const toggleAll = (checked) => {
    if (checked) {
        props.cards.forEach((c) => selectedIds.value.add(c.id));
    } else {
        selectedIds.value.clear();
    }
    emit('selection-change', Array.from(selectedIds.value));
};

const toggleOne = (id, checked) => {
    if (checked) selectedIds.value.add(id);
    else selectedIds.value.delete(id);
    emit('selection-change', Array.from(selectedIds.value));
};

watch(
    () => props.cards,
    (nextCards) => {
        const nextIds = new Set(nextCards.map((c) => c.id));
        selectedIds.value = new Set(Array.from(selectedIds.value).filter((id) => nextIds.has(id)));
        emit('selection-change', Array.from(selectedIds.value));
    },
    { immediate: true },
);

watch(
    () => props.clearSelectionToken,
    () => {
        if (selectedIds.value.size === 0) return;
        selectedIds.value = new Set();
        emit('selection-change', []);
    },
);

const lockLabel = (isActive) => {
    return isActive === false ? 'Đã khóa' : 'Đang hoạt động';
};

const holderTypeLabel = (holderType) => {
    if (holderType === 'external') return 'Bạn đọc ngoài';
    if (holderType === 'student') return 'Sinh viên';
    if (holderType === 'teacher') return 'Giáo viên';
    if (holderType === 'member') return 'Nội bộ (cũ)';
    return holderType || '—';
};

const holderTypeClass = (holderType) => {
    if (holderType === 'external') {
        return 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
    if (holderType === 'student') {
        return 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800';
    }
    if (holderType === 'teacher') {
        return 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-800';
    }
    if (holderType === 'member') {
        return 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 border-amber-200 dark:border-amber-800';
    }
    return 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
};

const lockClass = (isActive) => {
    return isActive === false
        ? 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800'
        : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
};

const lockActionClass = (isActive) => {
    return isActive === false ? 'text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20' : 'text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20';
};

const lockActionIcon = (isActive) => {
    return isActive === false ? 'lucide:unlock' : 'lucide:lock';
};

const emptyText = computed(() => (props.loading ? 'Đang tải...' : 'Chưa có dữ liệu.'));

const photoUrl = (path) => {
    if (!path || typeof path !== 'string') return '/images/default-avatar.png';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    if (path.startsWith('blob:')) return path;
    if (path.startsWith('/')) return path;
    return `/storage/${path.replace(/^\/+/, '')}`;
};
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[860px]">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="p-4 w-12">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                :indeterminate="hasSelection && !isAllSelected"
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                @change="(e) => toggleAll(e.target.checked)"
                            />
                        </th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-[130px]">Mã thẻ</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-[120px]">Ảnh thẻ</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-[200px]">Họ tên</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-[140px]">Ngày sinh</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-[140px]">Loại thẻ</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-[140px]">Trạng thái thẻ</th>
                        <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right w-[120px]">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr
                        v-for="card in cards"
                        :key="card.id"
                        :class="selectedIds.has(card.id) ? 'bg-blue-50 dark:bg-blue-900/15' : ''"
                    >
                        <td class="p-4">
                            <input
                                type="checkbox"
                                :checked="selectedIds.has(card.id)"
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                @change="(e) => toggleOne(card.id, e.target.checked)"
                            />
                        </td>
                        <td class="p-4">
                            <p class="text-[12px] font-semibold text-slate-800 dark:text-slate-100 tracking-wide font-mono whitespace-nowrap">
                                {{ card.card_number || '—' }}
                            </p>
                        </td>
                        <td class="p-4">
                            <div
                                class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold text-sm shrink-0 overflow-hidden relative group/avatar cursor-default"
                            >
                                <img
                                    :src="photoUrl(card.photo_path)"
                                    :alt="card.full_name || 'Avatar'"
                                    class="h-full w-full object-cover"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover/avatar:opacity-100 transition-opacity flex items-center justify-center rounded-lg"
                                    title="Cập nhật ảnh thẻ"
                                    @click.stop="emit('update-photo', card)"
                                >
                                    <Icon icon="lucide:camera" class="w-4 h-4 text-white" />
                                </button>
                            </div>
                        </td>
                        <td class="p-4">
                            <p
                                class="text-[12px] font-semibold text-slate-900 dark:text-white truncate"
                                :title="card.full_name || card.user?.name || '—'"
                            >
                                {{ card.full_name || card.user?.name || '—' }}
                            </p>
                        </td>
                        <td class="p-4">
                            <p class="text-[12px] font-semibold text-slate-800 dark:text-slate-100 truncate" :title="card.date_of_birth || '—'">
                                {{ card.date_of_birth ? String(card.date_of_birth).slice(0, 10) : '—' }}
                            </p>
                        </td>
                        <td class="p-4">
                            <span
                                :class="[
                                    'inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold border',
                                    holderTypeClass(card.holder_type),
                                ]"
                            >
                                {{ holderTypeLabel(card.holder_type) }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span :class="['inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold', lockClass(card.is_active)]">
                                {{ lockLabel(card.is_active) }}
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="p-1.5 text-blue-600 hover:text-blue-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                    title="Chỉnh sửa chi tiết"
                                    @click="emit('edit', card)"
                                >
                                    <Icon icon="lucide:pencil" class="w-4 h-4" />
                                </button>

                                <button
                                    type="button"
                                    :class="[
                                        'p-1.5 rounded-lg transition-colors',
                                        lockActionClass(card.is_active),
                                    ]"
                                    :title="card.is_active === true ? 'Khóa thẻ' : 'Mở khóa thẻ'"
                                    @click="emit('toggle-lock', card)"
                                >
                                    <Icon :icon="lockActionIcon(card.is_active)" class="w-4 h-4" />
                                </button>

                                <button
                                    v-if="mode === 'manage'"
                                    type="button"
                                    class="p-1.5 text-rose-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                    title="Xóa mềm"
                                    @click="emit('delete-soft', card)"
                                >
                                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                                </button>

                                <button
                                    v-if="mode === 'approve'"
                                    type="button"
                                    class="p-1.5 text-emerald-600 hover:text-emerald-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                    title="Duyệt yêu cầu cấp thẻ"
                                    @click="emit('approve', card)"
                                >
                                    <Icon icon="lucide:check" class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="cards.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">
            {{ emptyText }}
        </p>
    </div>
</template>

