<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import BooksTable from '@/Components/Admin/Books/BooksTable.vue';
import BookFormModal from '@/Components/Admin/Books/BookFormModal.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';

const props = defineProps({
    books: { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
    publishers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const BOOK_TYPES = [
    { value: '', label: 'Tất cả loại tài liệu' },
    { value: 'book', label: 'Sách' },
    { value: 'textbook', label: 'Giáo trình' },
    { value: 'thesis', label: 'Bài luận / Khóa luận / Đồ án' },
    { value: 'dissertation', label: 'Luận văn / Luận án' },
    { value: 'research', label: 'Báo cáo khoa học' },
    { value: 'magazine', label: 'Tạp chí' },
    { value: 'other', label: 'Tài liệu khác' },
];
const STATUS_OPTIONS = [
    { value: '', label: 'Tất cả trạng thái' },
    { value: 'available', label: 'Sẵn có' },
    { value: 'unavailable', label: 'Ẩn' },
    { value: 'processing', label: 'Đang xử lý' },
];

const showFormModal = ref(false);
const showDeleteModal = ref(false);
const showImportModal = ref(false);
const showUpdateCoversModal = ref(false);
const showTrashDrawer = ref(false);
const trashedBooks = ref([]);
const loadingTrash = ref(false);
const isEditing = ref(false);
const selectedBook = ref(null);
const importLoading = ref(false);

const selectedIds = ref([]);

const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
};

const toggleAll = () => {
    const books = booksData.value;
    const allSelected = books.every(b => selectedIds.value.includes(b.id));
    selectedIds.value = allSelected ? [] : books.map(b => b.id);
};

const deselectAll = () => { selectedIds.value = []; };

const form = useForm({
    id: null,
    title: '',
    type: 'book',
    category_id: '',
    parallel_title: '',
    language: '',
    responsibility_info: '',
    description: '',
    author: '',
    co_authors: '',
    org_author: '',
    publication_place: '',
    publisher: '',
    published_year: '',
    classification_code: '',
    total_pages: '',
    book_size: '',
    volume_number: '',
    quantity: 0,
    price: '',
    notes: '',
    digital_url: '',
    image: null,
    image_url: null,
});

const sampleBooks = ref([
    { id: 1, title: 'Ngữ văn 6. T.1', classification_code: 'SI0000001', status: 'available', publication_place: 'Hà Nội', publisher_name: 'Giáo dục Việt Nam', published_year: 2021, quantity: 5, authors: [{ name: 'Nguyễn Thị Hồng Nam' }] },
    { id: 2, title: 'Ngữ văn 6. T.2', classification_code: 'SI0000002', status: 'available', publication_place: 'Hà Nội', publisher_name: 'Giáo dục Việt Nam', published_year: 2022, quantity: 3, authors: [{ name: 'Nguyễn Thị Hồng Nam' }] },
    { id: 3, title: 'Bài tập Ngữ văn. T.1', classification_code: 'SI0000003', status: 'available', publication_place: 'Hà Nội', publisher_name: 'Giáo dục Việt Nam', published_year: 2021, quantity: 8, authors: [{ name: 'Nguyễn Thị Hồng Nam' }] },
]);

const booksData = computed(() => props.books?.data?.length ? props.books.data : sampleBooks.value);

const pagination = computed(() => ({
    current_page: props.books?.current_page || 1,
    last_page: props.books?.last_page || 1,
    per_page: props.books?.per_page || 20,
    total: props.books?.total || booksData.value.length,
    from: props.books?.from || 1,
    to: props.books?.to || booksData.value.length,
}));

const filterValues = ref({
    status: '',
    type: '',
    category_id: '',
    classification_code: '',
    title: '',
});

const selectedCodes = computed(() =>
    booksData.value
        .filter(b => selectedIds.value.includes(b.id))
        .map(b => b.classification_code || `SI${String(b.id).padStart(7, '0')}`)
);

const filteredBooks = computed(() => {
    let data = booksData.value;
    if (filterValues.value.title) {
        const q = filterValues.value.title.toLowerCase();
        data = data.filter(b => (b.title || '').toLowerCase().includes(q));
    }
    if (filterValues.value.classification_code) {
        data = data.filter(b => (b.classification_code || '').toLowerCase().includes(String(filterValues.value.classification_code).toLowerCase()));
    }
    if (filterValues.value.status) data = data.filter(b => b.status === filterValues.value.status);
    if (filterValues.value.type) data = data.filter(b => (b.type || 'book') === filterValues.value.type);
    if (filterValues.value.category_id) {
        const cid = Number(filterValues.value.category_id);
        data = data.filter(b => Number(b.category_id) === cid);
    }
    return data;
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showFormModal.value = true;
};

const openEditModal = (book) => {
    isEditing.value = true;
    selectedBook.value = book;
    form.id = book.id;
    form.title = book.title || '';
    form.type = book.type || 'book';
    form.category_id = book.category_id || '';
    form.classification_code = book.classification_code || '';
    form.author = book.authors?.[0]?.name || book.author || '';
    form.co_authors = (book.authors?.slice(1) || []).map(a => a.name).join(', ');
    form.publisher = book.publisher_name || book.publisher?.name || '';
    form.publication_place = book.publication_place || '';
    form.published_year = book.published_year || '';
    form.total_pages = book.total_pages || '';
    form.book_size = book.book_size || '';
    form.volume_number = book.volume_number || '';
    form.price = book.price || '';
    form.notes = book.notes || '';
    form.description = book.description || '';
    form.language = book.language || '';
    form.image_url = book.image_url || null;
    showFormModal.value = true;
};

const saveBook = () => {
    showFormModal.value = false;
    form.reset();
};

const confirmDelete = (book) => {
    selectedBook.value = book;
    showDeleteModal.value = true;
};

const confirmBulkDelete = () => {
    selectedBook.value = null;
    showDeleteModal.value = true;
};

const deleteBook = async () => {
    try {
        if (selectedBook.value) {
            await window.axios.delete(`/books/${selectedBook.value.id}`);
        } else if (selectedIds.value.length > 0) {
            for (const id of selectedIds.value) {
                await window.axios.delete(`/books/${id}`);
            }
        }
        router.reload();
    } catch (_) {
        // fallback: reload để đồng bộ
        router.reload();
    }
    showDeleteModal.value = false;
    selectedBook.value = null;
    selectedIds.value = [];
};

const openTrashDrawer = () => {
    showTrashDrawer.value = true;
    fetchTrash();
};
const fetchTrash = async () => {
    loadingTrash.value = true;
    try {
        const { data } = await window.axios.get(route('admin.books.trash'));
        trashedBooks.value = data.data || [];
    } catch {
        trashedBooks.value = [];
    }
    loadingTrash.value = false;
};
const onRestoreBook = async (id) => {
    try {
        await window.axios.post(route('admin.books.restore', { id }));
        fetchTrash();
        router.reload();
    } catch (_) {}
};
const onForceDeleteBook = async (id) => {
    if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
    try {
        await window.axios.delete(route('admin.books.force', { id }));
        fetchTrash();
        router.reload();
    } catch (_) {}
};

const importExcel = async (file) => {
    importLoading.value = true;
    // TODO: API nhập excel
    await new Promise(r => setTimeout(r, 1000));
    importLoading.value = false;
    showImportModal.value = false;
};

const onImportSubmit = (file) => {
    importExcel(file);
};
const onPhotoSubmit = (file) => {
    updateCovers(file);
    showUpdateCoversModal.value = false;
};

const exportExcel = () => {
    if (selectedIds.value.length > 0) {
        window.location.href = route('admin.books.export') + '?ids=' + selectedIds.value.join(',');
    } else {
        window.location.href = route('admin.books.export');
    }
};

const updateCovers = (file) => {
    // TODO: API cập nhật ảnh bìa (zip)
    setTimeout(() => { showUpdateCoversModal.value = false; }, 1000);
};

const downloadTemplate = () => {
    window.location.href = '/templates/01-sach-tai-lieu/Mau_nhap_sach.csv';
};

const doSearch = () => {
    // Bộ lọc đã reactive qua filterValues → filteredBooks
};
</script>

<template>
    <Head title="Quản lý Sách & Tài liệu - Admin" />
    <AdminLayout
        title="Quản lý Sách & Tài liệu"
        :breadcrumbs="[
            { label: 'Dữ liệu thư viện' },
            { label: 'Quản lý Sách & Tài liệu' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Danh sách sách / tài liệu</h2>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" class="gap-1.5" @click="openTrashDrawer">
                        <Icon icon="lucide:trash-2" class="w-4 h-4" />
                        Thùng rác
                    </Button>
                    <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon" class="rounded-full w-8 h-8 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 shrink-0" title="Hướng dẫn">
                            <Icon icon="lucide:circle-help" class="w-5 h-5" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-80 p-4">
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Quản lý, thêm/sửa thông tin sách và tài liệu; nhập/xuất Excel; cập nhật ảnh bìa.
                        </p>
                    </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <AdminImportExportBar
                :has-selection="selectedIds.length > 0"
                :selected-count="selectedIds.length"
                update-file-label="Cập nhật ảnh bìa"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="showImportModal = true"
                @update-file="showUpdateCoversModal = true"
                @deselect-all="deselectAll"
                @delete-selected="confirmBulkDelete"
            />

            <AdminFilterSearch
                :model-value="filterValues.title"
                @update:model-value="(v) => (filterValues.title = v)"
                search-placeholder="Tên sách hoặc từ khóa..."
                :show-filter-button="false"
                @search="doSearch"
            />

            <BooksTable
                :books="filteredBooks"
                :selected-ids="selectedIds"
                :page="pagination.current_page"
                :per-page="pagination.per_page"
                @edit="openEditModal"
                @delete="confirmDelete"
                @toggle-select="toggleSelect"
                @toggle-all="toggleAll"
                @update-single-cover="(book) => { selectedIds = [book.id]; showUpdateCoversModal = true; }"
            />
        </div>

        <BookFormModal
            :show="showFormModal"
            :form="form"
            :is-editing="isEditing"
            :categories="categories"
            :publishers="publishers"
            :book-types="BOOK_TYPES"
            @close="showFormModal = false"
            @submit="saveBook"
        />

        <AdminDeleteConfirmModal
            :show="showDeleteModal"
            title="Xác nhận xóa tài liệu"
            item-label="sách"
            :item="selectedBook"
            :selected-count="selectedIds.length"
            @close="showDeleteModal = false"
            @confirm="deleteBook"
        />
        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác – Sách / Tài liệu"
            item-label-key="title"
            :items="trashedBooks"
            :loading="loadingTrash"
            @close="showTrashDrawer = false"
            @restore="onRestoreBook"
            @force-delete="onForceDeleteBook"
        />

        <!-- Modal nhập / upload file: định dạng chung -->
        <AdminFileModal
            :show="showImportModal"
            title="Nhập excel"
            description="Tải file mẫu, điền đầy đủ thông tin bắt buộc, sau đó chọn file tải lên."
            accept=".xls,.xlsx"
            :max-size-mb="10"
            template-label="Tải file mẫu"
            submit-label="Nhập excel"
            :loading="importLoading"
            @close="showImportModal = false"
            @submit="onImportSubmit"
            @download-template="downloadTemplate"
        />
        <AdminFileModal
            :show="showUpdateCoversModal"
            title="Cập nhật ảnh bìa"
            description="Tải lên file .zip chứa ảnh (tên ảnh = mã sách, định dạng .jpg, .png)."
            accept=".zip"
            :max-size-mb="50"
            submit-label="Lưu"
            @close="showUpdateCoversModal = false"
            @submit="onPhotoSubmit"
        >
            <template #hint>
                <p class="text-xs text-slate-500 dark:text-slate-400">Tên ảnh trong file .zip phải trùng mã sách (vd: SI0000001.jpg).</p>
            </template>
        </AdminFileModal>
    </AdminLayout>
</template>
