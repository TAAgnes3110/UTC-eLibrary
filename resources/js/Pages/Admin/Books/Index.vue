<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import BookActions from '@/Components/Admin/Books/BookActions.vue';
import BooksFilter from '@/Components/Admin/Books/BooksFilter.vue';
import BooksTable from '@/Components/Admin/Books/BooksTable.vue';
import BookPagination from '@/Components/Admin/Books/BookPagination.vue';
import BookFormModal from '@/Components/Admin/Books/BookFormModal.vue';
import DeleteConfirmModal from '@/Components/Admin/Books/DeleteConfirmModal.vue';
import ImportExcelModal from '@/Components/Admin/Books/ImportExcelModal.vue';
import UpdateCoversModal from '@/Components/Admin/Books/UpdateCoversModal.vue';

const props = defineProps({
    books: { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

// =============================================
// Modal state
// =============================================
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const showImportModal = ref(false);
const showUpdateCoversModal = ref(false);
const isEditing = ref(false);
const selectedBook = ref(null);
const importLoading = ref(false);

// =============================================
// Selection state (checkbox)
// =============================================
const selectedIds = ref([]);

const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const toggleAll = () => {
    const books = booksData.value;
    const allSelected = books.every(b => selectedIds.value.includes(b.id));
    if (allSelected) {
        selectedIds.value = [];
    } else {
        selectedIds.value = books.map(b => b.id);
    }
};

const deselectAll = () => {
    selectedIds.value = [];
};

// =============================================
// Form
// =============================================
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

// =============================================
// Data: use API data or sample
// =============================================
const sampleBooks = ref([
    { id: 1, title: 'Ngữ văn 6. T.1', classification_code: 'SI0000001', status: 'available', publication_place: 'Hà Nội', publisher_name: 'Giáo dục Việt Nam', published_year: 2021, quantity: 5, authors: [{ name: 'Nguyễn Thị Hồng Nam' }] },
    { id: 2, title: 'Ngữ văn 6. T.2', classification_code: 'SI0000002', status: 'available', publication_place: 'Hà Nội', publisher_name: 'Giáo dục Việt Nam', published_year: 2022, quantity: 3, authors: [{ name: 'Nguyễn Thị Hồng Nam' }] },
    { id: 3, title: 'Bài tập Ngữ văn. T.1', classification_code: 'SI0000003', status: 'available', publication_place: 'Hà Nội', publisher_name: 'Giáo dục Việt Nam', published_year: 2021, quantity: 8, authors: [{ name: 'Nguyễn Thị Hồng Nam' }] },
    { id: 4, title: 'TK Tiếng Việt CNGD lớp 1 tập 3', classification_code: 'SI0000004', status: 'available', publication_place: 'H.', publisher_name: 'Giáo dục', published_year: 2020, quantity: 2, authors: [{ name: 'HỒ NGỌC ĐẠI' }] },
    { id: 5, title: 'TK Toán 1 tập 2', classification_code: 'SI0000005', status: 'available', publication_place: 'H.', publisher_name: 'Giáo dục', published_year: 2012, quantity: 10, authors: [{ name: 'NGUYỄN TUẤN' }] },
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

const filterValues = ref({ status: '', classification_code: '', title: '' });

const selectedCodes = computed(() => {
    return booksData.value
        .filter(b => selectedIds.value.includes(b.id))
        .map(b => b.classification_code || `SI${String(b.id).padStart(7, '0')}`);
});

const filteredBooks = computed(() => {
    let data = booksData.value;
    if (filterValues.value.title) {
        const q = filterValues.value.title.toLowerCase();
        data = data.filter(b => b.title.toLowerCase().includes(q));
    }
    if (filterValues.value.classification_code) {
        data = data.filter(b => (b.classification_code || '').toLowerCase().includes(filterValues.value.classification_code.toLowerCase()));
    }
    if (filterValues.value.status) {
        data = data.filter(b => b.status === filterValues.value.status);
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
    // TODO: Connect to API
    console.log('Saving book:', form);
    showFormModal.value = false;
    form.reset();
};

// =============================================
// Actions: Delete
// =============================================
const confirmDelete = (book) => {
    selectedBook.value = book;
    showDeleteModal.value = true;
};

const confirmBulkDelete = () => {
    selectedBook.value = null;
    showDeleteModal.value = true;
};

const deleteBook = () => {
    // TODO: Connect to API
    if (selectedBook.value) {
        console.log('Deleting book:', selectedBook.value);
    } else {
        console.log('Bulk deleting:', selectedIds.value);
    }
    showDeleteModal.value = false;
    selectedBook.value = null;
    selectedIds.value = [];
};

// =============================================
// Actions: Import Excel
// =============================================
const importExcel = async (file) => {
    importLoading.value = true;
    // TODO: Connect to backend API
    console.log('Importing file:', file.name);
    setTimeout(() => {
        importLoading.value = false;
        showImportModal.value = false;
    }, 1500);
};

const exportExcel = () => {
    // TODO: Connect to API
    console.log('Exporting Excel, selected:', selectedIds.value);
};

const updateCovers = (data) => {
    // TODO: Connect to API
    console.log('Updating covers with zip:', data.file.name);
    setTimeout(() => {
        showUpdateCoversModal.value = false;
    }, 1000);
};

const downloadTemplate = () => {
    window.location.href = '/templates/mau_nhap_sach.csv';
};
</script>

<template>
    <Head title="Quản lý Sách - Admin" />
    <AdminLayout
        title="Quản lý Sách"
        :breadcrumbs="[
            { label: 'Dữ liệu Thư viện' },
            { label: 'Quản lý Sách' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Top Actions -->
            <BookActions
                :selected-count="selectedIds.length"
                @add="openAddModal"
                @import-excel="showImportModal = true"
                @export-excel="exportExcel"
                @update-covers="showUpdateCoversModal = true"
                @deselect-all="deselectAll"
                @delete-selected="confirmBulkDelete"
            />

            <!-- Filter Bar -->
            <BooksFilter
                v-model="filterValues"
                @search="(v) => filterValues = v"
            />

            <!-- Table -->
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

            <!-- Pagination -->
            <BookPagination :pagination="pagination" />
        </div>

        <!-- Add/Edit Modal -->
        <BookFormModal
            :show="showFormModal"
            :form="form"
            :is-editing="isEditing"
            :categories="categories"
            @close="showFormModal = false"
            @submit="saveBook"
        />

        <!-- Delete Modal -->
        <DeleteConfirmModal
            :show="showDeleteModal"
            :book="selectedBook"
            :selected-count="selectedIds.length"
            @close="showDeleteModal = false"
            @confirm="deleteBook"
        />

        <!-- Import Modal -->
        <ImportExcelModal
            :show="showImportModal"
            :loading="importLoading"
            @close="showImportModal = false"
            @import="importExcel"
            @download-template="downloadTemplate"
        />

        <!-- Update Covers Modal -->
        <UpdateCoversModal
            :show="showUpdateCoversModal"
            :selected-codes="selectedCodes"
            @close="showUpdateCoversModal = false"
            @submit="updateCovers"
        />
    </AdminLayout>
</template>
