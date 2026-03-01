<script setup>
import { ref, computed, watch } from 'vue';
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
import { BOOK_TYPES, BOOK_TYPE_OPTIONS, BOOK_STATUS_OPTIONS, BOOK_TYPES_BY_GROUP, getResourceGroupLabel, getResourceGroupByType } from '@/config/enums';

const props = defineProps({
    books: { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
    publishers: { type: Array, default: () => [] },
    faculties: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    cohorts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const currentGroupLabel = computed(() => getResourceGroupLabel(props.filters?.group));

const bookTypesForGroup = computed(() => {
    const g = form.group || 'printed';
    return BOOK_TYPES_BY_GROUP[g] || BOOK_TYPE_OPTIONS;
});

watch(() => form.group, (g) => {
    const types = BOOK_TYPES_BY_GROUP[g];
    if (types?.length && !types.some((t) => t.value === form.type)) {
        form.type = types[0].value;
    }
});

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
    group: 'printed',
    title: '',
    type: 'book',
    category_id: '',
    parallel_title: '',
    responsibility_info: '',
    description: '',
    author: '',
    co_authors: '',
    org_author: '',
    publication_place: '',
    publisher_id: null,
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
    file_url: '',
    image: null,
    image_url: null,
    faculty_id: null,
    department_id: null,
    cohort: '',
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
    const group = props.filters?.group || 'printed';
    form.group = group;
    const types = BOOK_TYPES_BY_GROUP[group];
    form.type = types?.length ? types[0].value : 'book';
    showFormModal.value = true;
};

const openEditModal = (book) => {
    isEditing.value = true;
    selectedBook.value = book;
    form.id = book.id;
    form.group = getResourceGroupByType(book.type || 'book', book.is_digital);
    form.title = book.title || '';
    form.type = book.type || 'book';
    form.category_id = book.category_id || '';
    form.classification_code = book.classification_code || '';
    form.author = book.authors?.[0]?.name || book.author || '';
    form.co_authors = (book.authors?.slice(1) || []).map(a => a.name).join(', ');
    form.publisher_id = book.publisher?.id ?? book.publisher_id ?? null;
    form.publisher = book.publisher_name || book.publisher?.name || '';
    form.publication_place = book.publication_place || '';
    form.published_year = book.published_year || '';
    form.total_pages = book.total_pages || '';
    form.book_size = book.book_size || '';
    form.volume_number = book.volume_number || '';
    form.quantity = book.quantity ?? 0;
    form.price = book.price || '';
    form.notes = book.notes || '';
    form.description = book.description || '';
    form.image_url = book.image_url || null;
    form.file_url = book.file_url || '';
    form.faculty_id = book.faculty_id ?? null;
    form.department_id = book.department_id ?? null;
    form.cohort = book.cohort || '';
    showFormModal.value = true;
};

const buildBookPayload = () => {
    const p = form;
    const payload = {
        title: p.title,
        type: p.type,
        category_id: p.category_id || null,
        author: p.author,
        co_authors: p.co_authors || null,
        publication_place: p.publication_place || null,
        published_year: p.published_year ? Number(p.published_year) : null,
        classification_code: p.classification_code || null,
        total_pages: p.total_pages ? Number(p.total_pages) : null,
        book_size: p.book_size || null,
        volume_number: p.volume_number ? Number(p.volume_number) : null,
        quantity: p.quantity ? Number(p.quantity) : 0,
        price: p.price !== '' && p.price != null ? Number(p.price) : null,
        notes: p.notes || null,
        faculty_id: p.faculty_id ? Number(p.faculty_id) : null,
        department_id: p.department_id ? Number(p.department_id) : null,
        cohort: p.cohort && String(p.cohort).trim() ? String(p.cohort).trim() : null,
        is_digital: p.group === 'digital',
        file_url: p.group === 'digital' && p.file_url ? String(p.file_url).trim() : null,
    };
    const pubId = p.publisher_id;
    if (pubId != null && pubId !== '' && pubId !== '__new__' && !Number.isNaN(Number(pubId))) {
        payload.publisher_id = Number(pubId);
    } else if (p.publisher && String(p.publisher).trim()) {
        payload.publisher = String(p.publisher).trim();
    }
    return payload;
};

const saveBook = async () => {
    try {
        const payload = buildBookPayload();
        if (isEditing.value && form.id) {
            await window.axios.put(`/books/${form.id}`, payload);
        } else {
            await window.axios.post('/books', payload);
        }
        showFormModal.value = false;
        form.reset();
        router.reload();
    } catch (e) {
        if (e.response?.data?.errors) form.setErrors(e.response.data.errors);
    }
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
        const { data } = await window.axios.get('/books/trash');
        trashedBooks.value = data.data || [];
    } catch {
        trashedBooks.value = [];
    }
    loadingTrash.value = false;
};
const onRestoreBook = async (id) => {
    try {
        await window.axios.post(`/books/restore/${id}`);
        fetchTrash();
        router.reload();
    } catch (_) {}
};
const onForceDeleteBook = async (id) => {
    if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
    try {
        await window.axios.delete(`/books/force/${id}`);
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
        window.location.href = window.axios.defaults.baseURL + '/books/export?ids=' + selectedIds.value.join(',');
    } else {
        window.location.href = window.axios.defaults.baseURL + '/books/export';
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
        :title="currentGroupLabel !== 'Tất cả tài liệu' ? currentGroupLabel : 'Quản lý Sách & Tài liệu'"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Danh mục tài liệu' },
            ...(currentGroupLabel !== 'Tất cả tài liệu' ? [{ label: currentGroupLabel }] : []),
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">
                    {{ currentGroupLabel !== 'Tất cả tài liệu' ? currentGroupLabel : 'Danh sách sách / tài liệu' }}
                </h2>
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
            >
                <template #filters>
                    <select v-model="filterValues.type" class="admin-filter-select">
                        <option v-for="opt in BOOK_TYPES" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <select v-model="filterValues.category_id" class="admin-filter-select">
                        <option value="">-- Thể loại --</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <select v-model="filterValues.status" class="admin-filter-select">
                        <option v-for="opt in BOOK_STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </template>
            </AdminFilterSearch>

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
            :faculties="faculties"
            :departments="departments"
            :cohorts="cohorts"
            :book-types="bookTypesForGroup"
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
