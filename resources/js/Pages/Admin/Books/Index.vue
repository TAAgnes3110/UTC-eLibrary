<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';

// Dữ liệu mẫu – sau này thay bằng props/API
const sampleBooks = ref([
    {
        id: 1,
        registration_number: 'UTC0001',
        book_code: '624-UTC-0001',
        title: 'Cơ sở thiết kế đường ô tô',
        authors: 'Nguyễn Viết Trung',
        publisher: 'Giao thông Vận tải',
        published_year: 2018,
        classification: '624 / 624.2',
        warehouse: 'Thư viện Trung tâm UTC',
        status: 'available',
    },
    {
        id: 2,
        registration_number: 'UTC0002',
        book_code: '624-UTC-0002',
        title: 'Tổ chức vận tải và dịch vụ logistics',
        authors: 'Đỗ Bá Lâm',
        publisher: 'Giao thông Vận tải',
        published_year: 2019,
        classification: '624 / 624.2',
        warehouse: 'Thư viện Trung tâm UTC',
        status: 'available',
    },
    {
        id: 3,
        registration_number: 'UTC0003',
        book_code: '624-UTC-0003',
        title: 'Kết cấu bê tông cốt thép – Cầu đường bộ',
        authors: 'Phạm Hữu Vinh, Trần Thị Thanh',
        publisher: 'Xây dựng',
        published_year: 2017,
        classification: '624 / 624.2',
        warehouse: 'Thư viện Trung tâm UTC',
        status: 'on_loan',
    },
]);

const filterValues = ref({
    searchKeyword: '',
    status: '',
});

const showModal = ref(false);
const isEditing = ref(false);
const selectedBook = ref(null);
const showDeleteConfirm = ref(false);
const selectedIds = ref(new Set());

const books = computed(() => sampleBooks.value);

const filteredBooks = computed(() => {
    let list = [...books.value];
    const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
    if (kw) {
        list = list.filter((b) => {
            return (
                (b.title || '').toLowerCase().includes(kw) ||
                (b.book_code || '').toLowerCase().includes(kw) ||
                (b.registration_number || '').toLowerCase().includes(kw) ||
                (b.authors || '').toLowerCase().includes(kw)
            );
        });
    }
    if (filterValues.value.status) {
        list = list.filter((b) => b.status === filterValues.value.status);
    }
    return list;
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
    warehouse: '',
    status: 'available',
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
        sampleBooks.value = sampleBooks.value.map((b) => (b.id === form.value.id ? { ...form.value } : b));
    } else {
        const nextId = Math.max(0, ...sampleBooks.value.map((b) => b.id || 0)) + 1;
        sampleBooks.value.push({ ...form.value, id: nextId });
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
    if (selectedBook.value) {
        sampleBooks.value = sampleBooks.value.filter((b) => b.id !== selectedBook.value.id);
    } else if (hasSelection.value) {
        sampleBooks.value = sampleBooks.value.filter((b) => !selectedIds.value.has(b.id));
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
</script>

<template>
    <Head title="Quản lý Sách - Admin" />
    <AdminLayout
        title="Quản lý tài liệu"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý tài liệu' },
            { label: 'Sách' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Danh sách sách</h2>
            </div>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.size"
                update-file-label="Cập nhật file đính kèm"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @update-file="() => {}"
                @delete-selected="openDeleteMultiple"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Tìm theo tên sách, mã sách, DKCB, tác giả..."
                :show-filter-button="false"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Tất cả trạng thái</option>
                            <option value="available">Có sẵn</option>
                            <option value="on_loan">Đang cho mượn</option>
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
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên sách</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã sách / DKCB</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tác giả</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">NXB / Năm XB</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Phân loại</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Kho</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Trạng thái</th>
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
                                <td class="p-4">
                                    <button
                                        type="button"
                                        @click="openEditModal(book)"
                                        class="font-semibold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 text-left"
                                    >
                                        {{ book.title }}
                                    </button>
                                </td>
                                <td class="p-4">
                                    <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">
                                        {{ book.book_code }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                        DKCB: {{ book.registration_number }}
                                    </p>
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    {{ book.authors }}
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    <div class="flex flex-col">
                                        <span>{{ book.publisher }}</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Năm: {{ book.published_year }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    {{ book.classification }}
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">
                                    {{ book.warehouse }}
                                </td>
                                <td class="p-4 text-right">
                                    <span
                                        :class="[
                                            'inline-flex items-center justify-end gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase text-white',
                                            book.status === 'available'
                                                ? 'bg-emerald-500 dark:bg-emerald-600'
                                                : 'bg-amber-500 dark:bg-amber-600',
                                        ]"
                                    >
                                        <Icon
                                            :icon="book.status === 'available' ? 'lucide:check-circle' : 'lucide:clock'"
                                            class="w-3 h-3"
                                        />
                                        {{ book.status === 'available' ? 'Có sẵn' : 'Đang mượn' }}
                                    </span>
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
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Phân loại</label>
                            <Input
                                v-model="form.classification"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Ví dụ: 624 / 624.2"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Kho sách</label>
                            <Input
                                v-model="form.warehouse"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Ví dụ: Thư viện Trung tâm UTC"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Trạng thái</label>
                            <select
                                v-model="form.status"
                                class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            >
                                <option value="available">Có sẵn</option>
                                <option value="on_loan">Đang cho mượn</option>
                            </select>
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
    </AdminLayout>
</template>

