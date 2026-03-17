<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import apiClient from '@/api/axios';

const books = ref([]);

const classifications = ref([]);
const classificationDetails = ref([]);
const selectedClassificationId = ref('');
const loading = ref(false);

// Dữ liệu thùng rác (demo, chưa nối API)
const trashedBooks = ref([]);

const filterValues = ref({
    searchKeyword: '',
    status: '',
    warehouse: '',
});

const showModal = ref(false);
const isEditing = ref(false);
const selectedBook = ref(null);
const showDeleteConfirm = ref(false);
const selectedIds = ref(new Set());

const showTrashDrawer = ref(false);

const filteredBooks = computed(() => {
    let list = [...books.value];
    const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
    if (kw) {
        list = list.filter((b) => {
            return (
                (b.title || '').toLowerCase().includes(kw) ||
                (b.book_code || '').toLowerCase().includes(kw) ||
                (b.registration_number || '').toLowerCase().includes(kw) ||
                (b.authors_label || '').toLowerCase().includes(kw)
            );
        });
    }
    if (filterValues.value.status) {
        if (filterValues.value.status === 'in_stock') {
            list = list.filter((b) => (b.quantity ?? 0) > 0);
        } else if (filterValues.value.status === 'out_of_stock') {
            list = list.filter((b) => (b.quantity ?? 0) <= 0);
        }
    }
    if (filterValues.value.warehouse) {
        const kwWarehouse = filterValues.value.warehouse.toLowerCase();
        list = list.filter((b) =>
            (b.warehouse?.name || '').toLowerCase().includes(kwWarehouse) ||
            (b.warehouse?.code || '').toLowerCase().includes(kwWarehouse),
        );
    }
    if (selectedClassificationId.value) {
        list = list.filter(
            (b) => String(b.classification_id) === String(selectedClassificationId.value) ||
                String(b.classification?.id ?? '') === String(selectedClassificationId.value),
        );
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
    form.value = { ...book };
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

const confirmDelete = () => {
    const now = new Date().toISOString();
    if (selectedBook.value) {
        const toTrash = books.value.find((b) => b.id === selectedBook.value.id);
        if (toTrash) {
            trashedBooks.value.push({ ...toTrash, deleted_at: now });
        }
        books.value = books.value.filter((b) => b.id !== selectedBook.value.id);
    } else if (hasSelection.value) {
        books.value.forEach((b) => {
            if (selectedIds.value.has(b.id)) {
                trashedBooks.value.push({ ...b, deleted_at: now });
            }
        });
        books.value = books.value.filter((b) => !selectedIds.value.has(b.id));
        deselectAll();
    }
    showDeleteConfirm.value = false;
    selectedBook.value = null;
};

const exportExcel = () => {
    alert('Export sách – backend sẽ được nối sau.');
};

const openImportModal = () => {
    alert('Import sách từ Excel – backend sẽ được nối sau.');
};

const restoreBook = (id) => {
    const index = trashedBooks.value.findIndex((b) => b.id === id);
    if (index === -1) return;
    const [book] = trashedBooks.value.splice(index, 1);
    const restored = { ...book };
    delete restored.deleted_at;
    books.value.push(restored);
};

const forceDeleteBook = (id) => {
    trashedBooks.value = trashedBooks.value.filter((b) => b.id !== id);
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
                add-label="Thêm sách in"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @update-file="() => {}"
                @delete-selected="openDeleteMultiple"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Nhập từ khóa để tìm..."
                :show-filter-button="true"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="in_stock">Còn</option>
                            <option value="out_of_stock">Hết</option>
                        </select>
                        <input
                            v-model="filterValues.warehouse"
                            :list="'filter-warehouse-options'"
                            class="admin-filter-input"
                            placeholder="Kho sách"
                        />
                        <datalist id="filter-warehouse-options">
                            <option
                                v-for="b in books"
                                :key="b.id"
                                :value="b.warehouse?.name || ''"
                            />
                        </datalist>
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
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên sách</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tác giả</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">NXB / Năm XB</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Phân loại</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Kho</th>
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
                                <td class="p-4 align-top">
                                    <p class="text-[13px] font-semibold text-slate-100 dark:text-slate-50 tracking-wide">
                                        {{ book.book_code }}
                                    </p>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-9 w-7 rounded-md overflow-hidden bg-slate-100 dark:bg-slate-800 flex-shrink-0 flex items-center justify-center ring-1 ring-slate-200/80 dark:ring-slate-700/80"
                                        >
                                            <img
                                                v-if="book.cover_image"
                                                :src="book.cover_image"
                                                :alt="book.title"
                                                class="h-full w-full object-cover"
                                            />
                                            <Icon
                                                v-else
                                                icon="lucide:book-open"
                                                class="w-4 h-4 text-slate-400"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="openEditModal(book)"
                                            class="font-semibold text-slate-100 dark:text-white hover:text-blue-400 text-left line-clamp-2"
                                        >
                                            {{ book.title }}
                                        </button>
                                    </div>
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    {{ book.authors_label }}
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    <div class="flex flex-col">
                                        <span>{{ book.publishers_label }}</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                            Năm: {{ book.published_year }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    {{ book.classification?.code }} / {{ book.classification?.name }}
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    {{ book.warehouse?.name }}
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
                        class="sticky top-0 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 z-10"
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
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
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

        <!-- Modal xác nhận xóa sách -->
        <AdminDeleteConfirmModal
            :show="showDeleteConfirm"
            title="Xác nhận xóa sách"
            item-label="sách"
            :item="selectedBook"
            :selected-count="selectedBook ? 0 : selectedIds.size"
            @close="showDeleteConfirm = false"
            @confirm="confirmDelete"
        />

        <!-- Thùng rác sách -->
        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác – Sách"
            item-label-key="title"
            :items="trashedBooks"
            :loading="false"
            @close="showTrashDrawer = false"
            @restore="restoreBook"
            @force-delete="forceDeleteBook"
        />
    </AdminLayout>
</template>

