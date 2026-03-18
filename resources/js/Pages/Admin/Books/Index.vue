<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import apiClient from '@/api/axios';
import { booksApi } from '@/api/books';
import { toast } from '@/store/toast';

const books = ref([]);

const classifications = ref([]);
const classificationDetails = ref([]);
const selectedClassificationId = ref('');
const loading = ref(false);

const trashedBooks = ref([]);

const SEARCH_IN_OPTIONS = [
    { key: 'code', label: 'Mã sách' },
    { key: 'title', label: 'Tên sách' },
    { key: 'author', label: 'Tác giả' },
    { key: 'publisher', label: 'Nhà xuất bản' },
    { key: 'place', label: 'Nơi xuất bản' },
    { key: 'year', label: 'Năm xuất bản' },
    { key: 'classification', label: 'Phân loại' },
];

const filterValues = ref({
    searchKeyword: '',
    status: '',
    priceSort: '',
    searchIn: {
        code: true,
        title: true,
        author: true,
        publisher: true,
        place: true,
        year: true,
        classification: true,
    },
});

const showFilterPanel = ref(false);

const showModal = ref(false);
const isEditing = ref(false);
const selectedBook = ref(null);
const showDeleteConfirm = ref(false);
const deleteLoading = ref(false);
const selectedIds = ref(new Set());

const showTrashDrawer = ref(false);
const showCoverModal = ref(false);
const coverBulkMode = ref(false);
const coverUploadLoading = ref(false);
const coverTargetBookId = ref(null);

const showImportModal = ref(false);
const importLoading = ref(false);

const filteredBooks = computed(() => {
    let list = [...books.value];
    const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
    if (kw) {
        const sin = filterValues.value.searchIn || {};
        const anyChecked = Object.values(sin).some(Boolean);
        if (anyChecked) {
            list = list.filter((b) => {
                const checks = [];
                if (sin.code) checks.push((b.book_code || '').toLowerCase().includes(kw));
                if (sin.title) checks.push((b.title || '').toLowerCase().includes(kw));
                if (sin.author) checks.push((b.authors_label || '').toLowerCase().includes(kw));
                if (sin.publisher) checks.push((b.publishers_label || '').toLowerCase().includes(kw));
                if (sin.place) checks.push((b.publisher_place || '').toLowerCase().includes(kw));
                if (sin.year) checks.push(String(b.published_year || '').toLowerCase().includes(kw));
                if (sin.classification) {
                    checks.push((b.classification?.code || '').toLowerCase().includes(kw));
                    checks.push((b.classification?.name || '').toLowerCase().includes(kw));
                }
                return checks.some(Boolean);
            });
        }
    }
    if (filterValues.value.status) {
        if (filterValues.value.status === 'in_stock') {
            list = list.filter((b) => (b.quantity ?? 0) > 0);
        } else if (filterValues.value.status === 'out_of_stock') {
            list = list.filter((b) => (b.quantity ?? 0) <= 0);
        }
    }
    if (selectedClassificationId.value) {
        list = list.filter(
            (b) => String(b.classification_id) === String(selectedClassificationId.value) ||
                String(b.classification?.id ?? '') === String(selectedClassificationId.value),
        );
    }
    if (filterValues.value.priceSort) {
        const dir = filterValues.value.priceSort === 'asc' ? 1 : -1;
        list = [...list].sort((a, b) => {
            const pa = Number(a.price ?? 0);
            const pb = Number(b.price ?? 0);
            if (Number.isNaN(pa) && Number.isNaN(pb)) return 0;
            if (Number.isNaN(pa)) return 1;
            if (Number.isNaN(pb)) return -1;
            if (pa === pb) return 0;
            return pa < pb ? -dir : dir;
        });
    }
    return list;
});

const loadBooks = async () => {
    loading.value = true;
    try {
        const response = await apiClient.get('/books', {
            params: {
                per_page: 200,
                keyword: filterValues.value.searchKeyword || undefined,
            },
        });
        const payload = response?.data;
        const paginator = payload?.data;
        const items = Array.isArray(paginator?.data)
            ? paginator.data
            : Array.isArray(paginator)
                ? paginator
                : [];
        books.value = items;
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Failed to load books', e);
        books.value = [];
    } finally {
        loading.value = false;
    }
};

const loadClassifications = async () => {
    try {
        const response = await apiClient.get('/classifications/list');
        const payload = response?.data;
        classifications.value = Array.isArray(payload?.data) ? payload.data : [];
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Failed to load classifications', e);
        classifications.value = [];
    }
};

const loadClassificationDetails = async () => {
    try {
        const response = await apiClient.get('/classification-details', {
            params: {
                per_page: 500,
            },
        });
        const payload = response?.data;
        const paginator = payload?.data;
        const items = Array.isArray(paginator?.data)
            ? paginator.data
            : Array.isArray(paginator)
                ? paginator
                : [];
        classificationDetails.value = items;
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Failed to load classification details', e);
        classificationDetails.value = [];
    }
};

onMounted(async () => {
    await Promise.all([loadBooks(), loadClassifications(), loadClassificationDetails()]);
});

const hasSelection = computed(() => selectedIds.value.size > 0);
const isAllSelected = computed(
    () => filteredBooks.value.length > 0 && selectedIds.value.size === filteredBooks.value.length,
);

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value.clear();
    } else {
        filteredBooks.value.forEach((b) => selectedIds.value.add(b.id));
    }
    selectedIds.value = new Set(selectedIds.value);
}

function toggleSelect(id) {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id);
    } else {
        selectedIds.value.add(id);
    }
    selectedIds.value = new Set(selectedIds.value);
}

function deselectAll() {
    selectedIds.value.clear();
    selectedIds.value = new Set(selectedIds.value);
}

const emptyForm = () => ({
    id: null,
    registration_number: '',
    book_code: '',
    title: '',
    authors: '',
    publisher: '',
    published_year: '',
    description: '',
    price: '',
    classification: '',
    classification_detail: '',
    warehouse: '',
    quantity: 1,
});

const form = ref(emptyForm());

const openAddModal = () => {
    isEditing.value = false;
    form.value = emptyForm();
    showModal.value = true;
};

const openEditModal = (book) => {
    isEditing.value = true;
    form.value = {
        id: book.id ?? null,
        registration_number: book.registration_number || '',
        book_code: book.book_code || '',
        title: book.title || '',
        authors: book.authors_label || '',
        publisher: book.publishers_label || '',
        published_year: book.published_year || '',
        description: book.summary || '',
        price: book.price ?? '',
        classification: book.classification
            ? `${book.classification.code || ''} – ${book.classification.name || ''}`.trim()
            : '',
        classification_detail: book.classification_detail
            ? `${book.classification_detail.code || ''} – ${book.classification_detail.name || ''}`.trim()
            : '',
        warehouse: book.warehouse?.name || '',
        quantity: book.quantity ?? 1,
    };
    showModal.value = true;
};

const saveBook = () => {
    if (isEditing.value && form.value.id != null) {
        books.value = books.value.map((b) => (b.id === form.value.id ? { ...form.value } : b));
    } else {
        const nextId = Math.max(0, ...books.value.map((b) => b.id || 0)) + 1;
        books.value.push({ ...form.value, id: nextId });
    }
    showModal.value = false;
};

const openDeleteOne = (book) => {
    selectedBook.value = book;
    showDeleteConfirm.value = true;
};

const openDeleteMultiple = () => {
    if (!hasSelection.value) return;
    selectedBook.value = null;
    showDeleteConfirm.value = true;
};

const confirmDelete = async () => {
    if (deleteLoading.value) return;
    deleteLoading.value = true;
    try {
        if (selectedBook.value?.id) {
            await booksApi.remove(selectedBook.value.id);
            toast.success('Đã đưa sách vào thùng rác.', { title: 'Xóa' });
        } else if (hasSelection.value) {
            const ids = Array.from(selectedIds.value);
            await Promise.all(ids.map((id) => booksApi.remove(id)));
            deselectAll();
            toast.success(`Đã đưa ${ids.length} sách vào thùng rác.`, { title: 'Xóa' });
        } else {
            showDeleteConfirm.value = false;
            selectedBook.value = null;
            return;
        }

        showDeleteConfirm.value = false;
        selectedBook.value = null;
        await loadBooks();
        if (showTrashDrawer.value) {
            await fetchTrash();
        }
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Lỗi khi xóa sách:', e);
        const status = e?.response?.status;
        if (status === 404) {
            toast.info('Sách không tồn tại hoặc đã bị xóa trước đó.', { title: 'Xóa sách' });
            await loadBooks();
            if (showTrashDrawer.value) {
                await fetchTrash();
            }
        } else {
            const err = e?.response?.data || {};
            const msg = err?.message || err?.error || 'Không thể xóa sách. Vui lòng thử lại.';
            toast.error(msg, { title: 'Xóa sách' });
        }
    } finally {
        deleteLoading.value = false;
    }
};

const exportExcel = async () => {
    try {
        const params = {};
        if (selectedIds.value.size > 0) {
            params.ids = Array.from(selectedIds.value);
        } else if (filteredBooks.value.length > 0) {
            params.ids = filteredBooks.value.map((b) => b.id);
        }
        const response = await booksApi.export(params);
        const blob = new Blob([response.data], {
            type:
                response.headers['content-type'] ||
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'Danh_sach_sach_in.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        toast.success('Đã xuất Excel.', { title: 'Xuất Excel' });
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error(e);
        toast.error('Không thể xuất Excel. Vui lòng thử lại sau.', { title: 'Xuất Excel' });
    }
};

const openImportModal = () => {
    showImportModal.value = true;
};

const downloadBooksTemplate = async () => {
    try {
        const response = await booksApi.downloadImportTemplate();
        const blob = new Blob([response.data], {
            type:
                response.headers['content-type'] ||
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'Mau_nhap_sach.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        toast.success('Đã tải file mẫu.', { title: 'File mẫu' });
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error(e);
        toast.error('Không thể tải file mẫu. Vui lòng thử lại sau.', { title: 'File mẫu' });
    }
};

const importBooksExcel = async (file) => {
    if (!file) return;
    importLoading.value = true;
    try {
        const formData = new FormData();
        formData.append('file', file);
        const res = await booksApi.import(formData);
        await loadBooks();
        const payload = res?.data ?? res;
        const summary = payload?.summary || {};
        const errors = payload?.errors || [];
        const msg = `Import xong. Thành công: ${summary.success ?? 0}, Bỏ qua: ${summary.skipped ?? 0}, Lỗi: ${summary.errors ?? 0}.`;
        if (Array.isArray(errors) && errors.length > 0) {
            const lines = errors
                .slice(0, 8)
                .map((it) => `- Dòng ${it?.row ?? '?'}: ${it?.message ?? ''}`)
                .join('\n');
            toast.error(`Có lỗi khi import:\n${lines}`, { title: 'Import Excel', duration: 9000 });
        } else {
            toast.success(msg, { title: 'Import Excel' });
        }
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error(e);
        const err = e?.response?.data || {};
        const msg = err?.message || err?.error || 'Không thể nhập sách từ Excel. Vui lòng kiểm tra file và thử lại.';
        toast.error(msg, { title: 'Import Excel' });
    } finally {
        importLoading.value = false;
    }
};

const fetchTrash = async () => {
    try {
        const payload = await booksApi.trash();
        const data = payload?.data ?? payload;
        trashedBooks.value = Array.isArray(data) ? data : (data?.data ?? []);
    } catch (e) {
        trashedBooks.value = [];
        console.error('Lỗi khi tải thùng rác sách:', e);
    }
};

watch(showTrashDrawer, (open) => {
    if (open) fetchTrash();
});

const restoreBook = async (id) => {
    try {
        await booksApi.restore(id);
        await loadBooks();
        await fetchTrash();
        toast.success('Đã khôi phục.', { title: 'Thùng rác' });
    } catch (e) {
        console.error('Lỗi khi khôi phục sách:', e);
        toast.error('Không thể khôi phục. Vui lòng thử lại.', { title: 'Thùng rác' });
    }
};

const restoreManyBooks = async (ids) => {
    if (!Array.isArray(ids) || ids.length === 0) return;
    if (!confirm(`Khôi phục ${ids.length} mục?`)) return;
    try {
        if (typeof booksApi.restoreMany === 'function') {
            await booksApi.restoreMany(ids);
        } else {
            await Promise.all(ids.map((id) => booksApi.restore(id)));
        }
        await loadBooks();
        await fetchTrash();
        toast.success(`Đã khôi phục ${ids.length} mục.`, { title: 'Thùng rác' });
    } catch (e) {
        console.error('Lỗi khi khôi phục nhiều sách:', e);
        toast.error('Không thể khôi phục các mục đã chọn.', { title: 'Thùng rác' });
    }
};

const forceDeleteBook = async (id) => {
    if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
    try {
        await booksApi.forceDelete(id);
        trashedBooks.value = (trashedBooks.value || []).filter((b) => b.id !== id);
        await loadBooks();
        await fetchTrash();
        toast.success('Đã xóa vĩnh viễn.', { title: 'Thùng rác' });
    } catch (e) {
        console.error('Lỗi khi xóa vĩnh viễn sách:', e);
        toast.error('Không thể xóa vĩnh viễn. Vui lòng thử lại.', { title: 'Thùng rác' });
    }
};

const forceDeleteManyBooks = async (ids) => {
    if (!Array.isArray(ids) || ids.length === 0) return;
    if (!confirm(`Xóa vĩnh viễn ${ids.length} mục? Không thể khôi phục.`)) return;
    try {
        if (typeof booksApi.forceDeleteMany === 'function') {
            await booksApi.forceDeleteMany(ids);
        } else {
            await Promise.all(ids.map((id) => booksApi.forceDelete(id)));
        }
        trashedBooks.value = (trashedBooks.value || []).filter((b) => !ids.includes(b.id));
        await loadBooks();
        await fetchTrash();
        toast.success(`Đã xóa vĩnh viễn ${ids.length} mục.`, { title: 'Thùng rác' });
    } catch (e) {
        console.error('Lỗi khi xóa vĩnh viễn nhiều sách:', e);
        toast.error('Không thể xóa vĩnh viễn các mục đã chọn.', { title: 'Thùng rác' });
    }
};

const openCoverModal = (book = null) => {
    if (book) {
        coverBulkMode.value = false;
        coverTargetBookId.value = book.id;
    } else {
        const ids = Array.from(selectedIds.value);
        coverBulkMode.value = ids.length !== 1;
        coverTargetBookId.value = ids.length === 1 ? ids[0] : null;
    }
    showCoverModal.value = true;
};

const closeCoverModal = () => {
    showCoverModal.value = false;
    coverTargetBookId.value = null;
    coverBulkMode.value = false;
};

const uploadCover = async (file) => {
    if (!file) return;
    coverUploadLoading.value = true;
    try {
        const formData = new FormData();
        if (coverBulkMode.value) {
            formData.append('file', file);
            await booksApi.bulkUpdateCover(formData);
        } else {
            const ids = Array.from(selectedIds.value);
            const bookId = coverTargetBookId.value ?? ids[0];
            if (!bookId) {
                toast.info('Vui lòng chọn đúng 1 sách để cập nhật ảnh bìa.', { title: 'Ảnh bìa' });
                coverUploadLoading.value = false;
                return;
            }
            formData.append('book_cover', file);
            await booksApi.updateCover(bookId, formData);
        }
        await loadBooks();
        toast.success('Cập nhật ảnh bìa sách thành công.', { title: 'Ảnh bìa' });
        closeCoverModal();
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Lỗi khi cập nhật ảnh bìa:', e);
        const res = e?.response?.data || {};
        const message = res.message || res.error || 'Cập nhật ảnh bìa không thành công. Vui lòng kiểm tra lại file.';
        toast.error(message, { title: 'Ảnh bìa' });
    } finally {
        coverUploadLoading.value = false;
    }
};
</script>

<template>
    <Head title="Danh mục – Sách in" />
    <AdminLayout
        title="Danh mục tài liệu"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Danh mục tài liệu' },
            { label: 'Sách in' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Sách in theo danh mục</h2>
                <Button variant="outline" size="sm" class="gap-1.5" @click="showTrashDrawer = true">
                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                    Thùng rác
                </Button>
            </div>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.size"
                update-file-label="Cập nhật ảnh bìa"
                :show-update-file="true"
                add-label="Thêm sách in"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @update-file="() => openCoverModal()"
                @delete-selected="openDeleteMultiple"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Mã sách, tên sách, tác giả, NXB, nơi XB, năm XB..."
                :show-filter-button="false"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <AdminFilterPanel
                            :options="SEARCH_IN_OPTIONS"
                            v-model:model-value="filterValues.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="in_stock">Còn</option>
                            <option value="out_of_stock">Hết</option>
                        </select>
                        <select v-model="filterValues.priceSort" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Giá sách</option>
                            <option value="asc">Giá tăng dần</option>
                            <option value="desc">Giá giảm dần</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 w-12">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        :indeterminate="hasSelection && !isAllSelected"
                                        @change="toggleSelectAll"
                                        class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                    />
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-[120px]">
                                    Mã sách
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">
                                    Tên sách
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tác giả</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    Nhà xuất bản
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Phân loại</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Giá</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">
                                    Số lượng
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right w-[110px]">
                                    Trạng thái
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right w-[88px]">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="book in filteredBooks"
                                :key="book.id"
                                class="admin-table-row"
                            >
                                <td class="p-4">
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.has(book.id)"
                                        @change="toggleSelect(book.id)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <td class="p-4 text-center align-middle">
                                    <p class="text-[12px] font-semibold text-slate-100 dark:text-slate-50 tracking-wide font-mono whitespace-nowrap">
                                        {{ book.book_code }}
                                    </p>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-9 w-7 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 flex-shrink-0 flex items-center justify-center ring-1 ring-slate-200/80 dark:ring-slate-700/80 relative group/cover"
                                        >
                                            <img
                                                :src="book.cover_image || '/images/default-book-cover.png'"
                                                :alt="book.title"
                                                class="h-full w-full object-cover"
                                            />
                                            <button
                                                type="button"
                                                class="absolute inset-0 bg-black/35 opacity-0 group-hover/cover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer"
                                                @click.stop="openCoverModal(book)"
                                                title="Cập nhật ảnh bìa"
                                            >
                                                <Icon icon="lucide:camera" class="w-3.5 h-3.5 text-white" />
                                            </button>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <button
                                                type="button"
                                                @click="openEditModal(book)"
                                                class="font-semibold text-slate-100 dark:text-white hover:text-blue-400 text-sm leading-snug line-clamp-2 text-left"
                                            >
                                                {{ book.title }}
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-top">
                                    <p class="text-[12px] font-medium text-slate-100 dark:text-slate-100 line-clamp-2">
                                        {{ book.authors_label || '—' }}
                                    </p>
                                </td>
                                <td class="p-4 align-top">
                                    <p class="text-[12px] font-medium text-slate-100 dark:text-slate-100 line-clamp-2">
                                        {{ book.publishers_label || '—' }}
                                    </p>
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    {{ book.classification?.name || '—' }}
                                </td>
                                <td class="p-4 text-right text-[12px] text-slate-100 dark:text-slate-100">
                                    <span class="font-semibold whitespace-nowrap">
                                        {{ (book.price ?? 0).toLocaleString('vi-VN') }} đ
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-slate-50 dark:bg-slate-800 text-[11px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 min-w-[3rem]">
                                        {{ book.quantity ?? 0 }}
                                    </span>
                                </td>
                                <td class="p-4 text-right w-[110px]">
                                    <span
                                        :class="[
                                            'inline-flex items-center justify-end gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase text-white',
                                            (book.quantity ?? 0) > 0
                                                ? 'bg-emerald-500 dark:bg-emerald-600'
                                                : 'bg-rose-500 dark:bg-rose-600',
                                        ]"
                                    >
                                        <Icon
                                            :icon="(book.quantity ?? 0) > 0 ? 'lucide:check-circle' : 'lucide:x-circle'"
                                            class="w-3 h-3"
                                        />
                                        {{ (book.quantity ?? 0) > 0 ? 'Còn' : 'Hết' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-50 dark:hover:bg-slate-800 rounded"
                                            @click="openEditModal(book)"
                                            title="Chỉnh sửa"
                                        >
                                            <Icon icon="lucide:pen-square" class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded"
                                            @click="openDeleteOne(book)"
                                            title="Xóa"
                                        >
                                            <Icon icon="lucide:trash-2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="filteredBooks.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">
                    Chưa có sách nào trong danh sách mẫu.
                </p>
            </div>
        </div>

        <!-- Modal Thêm / Sửa sách -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showModal = false"></div>
                <div
                    class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800"
                >
                    <div
                        class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50"
                    >
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Chỉnh sửa sách' : 'Thêm sách mới' }}
                        </h3>
                        <button
                            type="button"
                            @click="showModal = false"
                            class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        >
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="px-6 pb-6 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Tên sách <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="form.title"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Nhập tên sách"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã sách</label>
                            <Input
                                v-model="form.book_code"
                                class="h-10 rounded-lg font-mono border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Mã sách trong hệ thống"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số đăng ký cá biệt (DKCB)</label>
                            <Input
                                v-model="form.registration_number"
                                class="h-10 rounded-lg font-mono border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Mã DKCB"
                            />
                        </div>
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tác giả</label>
                            <Input
                                v-model="form.authors"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Tên tác giả, phân tách bởi dấu phẩy"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Nhà xuất bản</label>
                            <Input
                                v-model="form.publisher"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="NXB"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Năm xuất bản</label>
                            <Input
                                v-model="form.published_year"
                                type="number"
                                min="1900"
                                max="2100"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="YYYY"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Phân loại sách <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.classification"
                                :list="'book-classification-options'"
                                class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                placeholder="Gõ mã / tên phân loại, ví dụ: 624 / 624.2"
                            />
                            <datalist id="book-classification-options">
                                <option
                                    v-for="c in classifications"
                                    :key="c.id"
                                    :value="c.code && c.name ? `${c.code} – ${c.name}` : (c.name || c.code || '')"
                                />
                            </datalist>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Phân loại chi tiết <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.classification_detail"
                                :list="'book-classification-detail-options'"
                                class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                placeholder="Gõ mã / tên phân loại chi tiết"
                            />
                            <datalist id="book-classification-detail-options">
                                <option
                                    v-for="d in classificationDetails"
                                    :key="d.id"
                                    :value="d.code && d.name ? `${d.code} – ${d.name}` : (d.name || d.code || '')"
                                />
                            </datalist>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Kho sách <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.warehouse"
                                :list="'book-warehouse-options'"
                                class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                placeholder="Gõ mã / tên kho, ví dụ: Thư viện Trung tâm UTC"
                            />
                            <datalist id="book-warehouse-options">
                                <option
                                    v-for="w in books"
                                    :key="w.id"
                                    :value="w.warehouse?.name || ''"
                                />
                            </datalist>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Số lượng bản in <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="form.quantity"
                                type="number"
                                min="0"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Ví dụ: 10"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Giá tiền (đ)</label>
                            <Input
                                v-model="form.price"
                                type="number"
                                min="0"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Ví dụ: 98000"
                            />
                        </div>
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mô tả / tóm tắt</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white px-3 py-2 resize-y"
                                placeholder="Nhập mô tả ngắn về nội dung sách"
                            />
                        </div>
                    </div>
                    <div
                        class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30"
                    >
                        <Button variant="outline" @click="showModal = false">Hủy bỏ</Button>
                        <Button @click="saveBook" class="bg-blue-600 hover:bg-blue-700 text-white">
                            {{ isEditing ? 'Cập nhật' : 'Lưu' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal cập nhật ảnh bìa sách -->
        <AdminFileModal
            :show="showCoverModal"
            :title="coverBulkMode ? 'Cập nhật ảnh bìa hàng loạt' : 'Cập nhật ảnh bìa sách'"
            :description="
                coverBulkMode
                    ? 'Chọn một file .zip chứa các ảnh bìa. Mỗi ảnh đặt tên đúng mã sách + đuôi ảnh (jpg, png, ...).'
                    : 'Kéo thả ảnh vào đây hoặc chọn file. Tên file không quan trọng, hệ thống tự đặt tên.'
            "
            :accept="coverBulkMode ? '.zip' : '.jpg,.jpeg,.png,.gif,.webp'"
            :max-size-mb="coverBulkMode ? 50 : 10"
            submit-label="Lưu"
            :loading="coverUploadLoading"
            @close="closeCoverModal"
            @submit="(file) => uploadCover(file)"
        />

        <!-- Modal nhập sách từ Excel -->
        <AdminFileModal
            :show="showImportModal"
            title="Nhập sách in từ Excel"
            description="Tải file mẫu, điền danh sách sách in (một dòng một bản ghi), sau đó chọn file để nhập."
            accept=".xls,.xlsx,.csv"
            :max-size-mb="10"
            template-label="Tải file mẫu sách"
            submit-label="Nhập Excel"
            :loading="importLoading"
            @close="showImportModal = false"
            @submit="(file) => { importBooksExcel(file); showImportModal = false; }"
            @download-template="downloadBooksTemplate"
        />

        <!-- Modal xác nhận xóa sách -->
        <AdminDeleteConfirmModal
            :show="showDeleteConfirm"
            title="Xác nhận xóa sách"
            item-label="sách"
            :item="selectedBook"
            :selected-count="selectedBook ? 0 : selectedIds.size"
            :loading="deleteLoading"
            @close="showDeleteConfirm = false"
            @confirm="confirmDelete"
        />

        <!-- Thùng rác sách -->
        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác"
            item-label-key="title"
            :items="trashedBooks"
            :loading="false"
            @close="showTrashDrawer = false"
            @restore="restoreBook"
            @restore-many="restoreManyBooks"
            @force-delete="forceDeleteBook"
            @force-delete-many="forceDeleteManyBooks"
        />
    </AdminLayout>
</template>

