<script setup>
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';
import { useImageFallback } from '@/composables/useImageFallback';

const emit = defineEmits(['toggle-select-all', 'toggle-select', 'view', 'edit', 'delete', 'cover']);

const previewBook = ref(null);
const showPreviewModal = ref(false);
const { withFallback } = useImageFallback();

function openCoverPreview(book) {
    previewBook.value = book;
    showPreviewModal.value = true;
}

function closeCoverPreview() {
    showPreviewModal.value = false;
    previewBook.value = null;
}

function triggerCoverChange() {
    if (!previewBook.value) return;
    emit('cover', previewBook.value);
    closeCoverPreview();
}

const props = defineProps({
    pageKind: { type: String, default: 'printed' },
    books: { type: Array, required: true },
    selectedIds: { type: Object, required: true },
    isAllSelected: { type: Boolean, required: true },
    hasSelection: { type: Boolean, required: true },
});

const isDigitalPage = computed(() => props.pageKind === 'digital');
const books = computed(() => props.books);
const selectedIds = computed(() => props.selectedIds);
const isAllSelected = computed(() => props.isAllSelected);
const hasSelection = computed(() => props.hasSelection);

/** Mã + tên kho (API `warehouse` khi đã eager load). */
function warehouseLine(book) {
    const w = book?.warehouse;
    if (!w || typeof w !== 'object') return '—';
    const code = String(w.code || '').trim();
    const rawName = String(w.name || '').trim();
    const name = rawName.replace(/\s*\([^)]*\)\s*$/u, '').trim();
    if (code && name) return `${code} – ${name}`;
    return name || code || '—';
}

function fileAttachmentUrl(book) {
    return book?.primary_digital_asset_url || book?.digital_assets?.[0]?.url || null;
}

function fileAttachmentName(book) {
    const raw = book?.digital_assets?.[0]?.original_name;
    const name = String(raw || '').trim();
    return name || 'Mở file đính kèm';
}

function circulationStatusLabel(book) {
    const label = String(book?.circulation_status_label || '').trim();
    if (label) return label;
    const available = Number(book?.real_quantity ?? book?.available_quantity ?? book?.quantity ?? 0);
    return available > 0 ? 'Còn lưu hành' : 'Không lưu hành';
}

</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-full table-auto text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-3 py-3.5 w-12 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    :indeterminate="hasSelection && !isAllSelected"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle-select-all')"
                                />
                            </span>
                        </th>
                        <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Mã sách</th>
                        <th class="px-3 py-3.5 align-middle whitespace-nowrap text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                            <span class="sr-only">Ảnh bìa</span>
                        </th>
                        <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Tên sách</th>
                        <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Tác giả</th>
                        <template v-if="isDigitalPage">
                            <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Mô tả</th>
                            <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Nhà xuất bản</th>
                            <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">File đính kèm</th>
                            <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Trạng thái</th>
                        </template>
                        <template v-else>
                            <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Nhà xuất bản</th>
                            <th class="px-3 py-3.5 align-middle whitespace-nowrap text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Kho</th>
                            <th class="px-3 py-3.5 align-middle whitespace-nowrap text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Tủ lưu trữ</th>
                            <th class="px-3 py-3.5 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Trạng thái</th>
                        </template>
                        <th class="px-2 py-3.5 align-middle whitespace-nowrap text-center text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr v-for="book in books" :key="book.id" class="admin-table-row">
                        <td class="px-3 py-3 align-middle">
                            <span class="admin-table-checkbox-wrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.has(book.id)"
                                    class="admin-table-checkbox"
                                    @change="emit('toggle-select', book.id)"
                                />
                            </span>
                        </td>
                        <td class="px-3 py-3 align-middle">
                            <p
                                class="text-[12px] font-semibold text-slate-700 dark:text-slate-200 tracking-wide font-mono whitespace-nowrap truncate"
                                :title="book.book_code || '—'"
                            >
                                {{ book.book_code || '—' }}
                            </p>
                        </td>
                        <td class="px-3 py-3 align-middle">
                            <div class="flex flex-col items-start gap-1.5">
                                <button
                                    type="button"
                                    class="h-10 w-8 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center ring-1 ring-slate-200/80 dark:ring-slate-700/80"
                                    :title="`Xem ảnh bìa: ${book.title || 'Sách'}`"
                                    @click.stop="openCoverPreview(book)"
                                >
                                    <img
                                        :src="book.cover_image || '/images/default-book-cover.png'"
                                        :alt="book.title"
                                        @error="withFallback('/images/default-book-cover.png')($event)"
                                        class="h-full w-full object-cover"
                                    />
                                </button>
                            </div>
                        </td>
                        <td class="px-3 py-3 align-middle">
                            <button
                                type="button"
                                class="block w-full text-left font-semibold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 text-sm leading-snug line-clamp-2 break-words"
                                :title="book.title || '—'"
                                @click="emit('edit', book)"
                            >
                                {{ book.title || '—' }}
                            </button>
                        </td>
                        <td class="px-3 py-3 align-middle">
                            <p class="text-[12px] font-medium text-slate-700 dark:text-slate-200 line-clamp-2 break-words text-left" :title="book.authors_label || '—'">
                                {{ book.authors_label || '—' }}
                            </p>
                        </td>
                        <template v-if="isDigitalPage">
                            <td class="px-3 py-3 align-middle">
                                <p class="text-[12px] font-medium text-slate-700 dark:text-slate-200 line-clamp-2 break-words text-left" :title="book.summary || '—'">
                                    {{ book.summary || '—' }}
                                </p>
                            </td>
                            <td class="px-3 py-3 align-middle">
                                <p class="text-[12px] font-medium text-slate-700 dark:text-slate-200 line-clamp-2 break-words" :title="book.publishers_label || '—'">
                                    {{ book.publishers_label || '—' }}
                                </p>
                            </td>
                            <td class="px-3 py-3 align-middle">
                                <a
                                    v-if="fileAttachmentUrl(book)"
                                    :href="fileAttachmentUrl(book)"
                                    target="_blank"
                                    class="text-[12px] font-semibold text-blue-700 hover:underline dark:text-blue-300 line-clamp-1 break-all"
                                    :title="fileAttachmentName(book)"
                                >
                                    {{ fileAttachmentName(book) }}
                                </a>
                                <span v-else class="text-[12px] text-slate-500 dark:text-slate-400">—</span>
                            </td>
                            <td class="px-3 py-3 align-middle whitespace-nowrap">
                                <span
                                    class="inline-flex min-h-[28px] items-center rounded-md px-3 py-1 text-[12px] font-semibold leading-none"
                                    :class="circulationStatusLabel(book) === 'Còn lưu hành'
                                        ? 'bg-emerald-950/60 text-emerald-300 ring-1 ring-emerald-700/40'
                                        : 'bg-rose-950/60 text-rose-300 ring-1 ring-rose-700/40'"
                                >
                                    {{ circulationStatusLabel(book) }}
                                </span>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-3 py-3 align-middle text-[12px] text-slate-600 dark:text-slate-300">
                                <p class="font-medium leading-snug line-clamp-2 break-words" :title="book.publishers_label || '—'">
                                    {{ book.publishers_label || '—' }}
                                </p>
                            </td>
                            <td class="px-3 py-3 align-middle text-[12px] text-slate-600 dark:text-slate-300">
                                <p
                                    class="font-medium leading-snug line-clamp-2 break-words"
                                    :title="warehouseLine(book)"
                                >
                                    {{ warehouseLine(book) }}
                                </p>
                            </td>
                            <td class="px-3 py-3 align-middle text-[12px] text-slate-600 dark:text-slate-300">
                                <p
                                    class="font-medium leading-snug line-clamp-2 break-words"
                                    :title="book.cabinet ? String(book.cabinet) : 'Chưa gán tủ'"
                                >
                                    {{ book.cabinet || '—' }}
                                </p>
                            </td>
                            <td class="px-3 py-3 align-middle whitespace-nowrap text-[12px]">
                                <span
                                    class="inline-flex min-h-[28px] items-center rounded-md px-3 py-1 text-[12px] font-semibold leading-none"
                                    :class="circulationStatusLabel(book) === 'Còn lưu hành'
                                        ? 'bg-emerald-950/60 text-emerald-300 ring-1 ring-emerald-700/40'
                                        : 'bg-rose-950/60 text-rose-300 ring-1 ring-rose-700/40'"
                                >
                                    {{ circulationStatusLabel(book) }}
                                </span>
                            </td>
                        </template>
                        <td class="px-2 py-3 align-middle whitespace-nowrap text-center">
                            <div class="inline-flex flex-nowrap items-center justify-center gap-0.5">
                                <AdminTableActionIcon
                                    icon="lucide:eye"
                                    tone="sky"
                                    title="Xem chi tiết"
                                    icon-class="w-4 h-4"
                                    @click="emit('view', book)"
                                />
                                <AdminTableActionIcon
                                    icon="lucide:pen-square"
                                    tone="slate"
                                    title="Chỉnh sửa"
                                    icon-class="w-4 h-4"
                                    @click="emit('edit', book)"
                                />
                                <AdminTableActionIcon
                                    icon="lucide:trash-2"
                                    tone="rose"
                                    title="Xóa"
                                    icon-class="w-4 h-4"
                                    @click="emit('delete', book)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="books.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">
            Chưa có sách nào trong danh sách mẫu.
        </p>
    </div>
    <div v-if="showPreviewModal && previewBook" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60" @click="closeCoverPreview" />
        <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ảnh bìa sách</h4>
                <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeCoverPreview">
                    <Icon icon="lucide:x" class="h-4 w-4" />
                </button>
            </div>
            <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                <img
                    :src="previewBook.cover_image || '/images/default-book-cover.png'"
                    :alt="previewBook.title"
                    @error="withFallback('/images/default-book-cover.png')($event)"
                    class="h-[360px] w-full object-contain bg-slate-50 dark:bg-slate-800"
                />
            </div>
            <div class="mt-3 flex justify-end">
                <button
                    type="button"
                    class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/35 dark:text-blue-300 dark:hover:bg-blue-900/50"
                    @click="triggerCoverChange"
                >
                    <Icon icon="lucide:camera" class="h-3.5 w-3.5" />
                    Đổi ảnh
                </button>
            </div>
        </div>
    </div>
</template>
