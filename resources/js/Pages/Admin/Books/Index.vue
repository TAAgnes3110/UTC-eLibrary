<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import BookActions from '@/Components/Admin/Books/BookActions.vue';
import BooksFilter from '@/Components/Admin/Books/BooksFilter.vue';
import BooksTable from '@/Components/Admin/Books/BooksTable.vue';
import BookPagination from '@/Components/Admin/Books/BookPagination.vue';
import BookFormModal from '@/Components/Admin/Books/BookFormModal.vue';
import DeleteConfirmModal from '@/Components/Admin/Books/DeleteConfirmModal.vue';

const props = defineProps({
    books: { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

// Modal state
const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const selectedBook = ref(null);

// Form
const form = useForm({
    id: null,
    title: '',
    category_id: '',
    author: '',
    publisher: '',
    year: '',
    pages: '',
    price: '',
    quantity: 10,
    description: '',
    image: null,
});

// Sample data for demo
const sampleBooks = ref([
    { id: 101, title: 'Giáo trình Cấu trúc dữ liệu', category: 'Công nghệ thông tin', author: 'Nguyễn Đức Nghĩa', publisher: 'NXB Bách Khoa', quantity_total: 50, quantity_remaining: 32, price: '120,000', year: '2019', pages: 250 },
    { id: 102, title: 'Lập trình Hướng đối tượng Java', category: 'Công nghệ thông tin', author: 'Phạm Văn Ất', publisher: 'NXB Giáo Dục', quantity_total: 30, quantity_remaining: 5, price: '95,000', year: '2021', pages: 400 },
    { id: 103, title: 'Xác suất thống kê', category: 'Khoa học cơ bản', author: 'Đặng Hùng Thắng', publisher: 'NXB ĐH Quốc gia', quantity_total: 100, quantity_remaining: 88, price: '60,000', year: '2018', pages: 180 },
    { id: 104, title: 'Kinh tế vi mô', category: 'Kinh tế', author: 'N. Gregory Mankiw', publisher: 'NXB Kinh Tế', quantity_total: 45, quantity_remaining: 0, price: '150,000', year: '2022', pages: 550 },
    { id: 105, title: 'Giải tích 1 & 2', category: 'Khoa học cơ bản', author: 'Nguyễn Đình Trí', publisher: 'NXB Giao Thông', quantity_total: 80, quantity_remaining: 45, price: '85,000', year: '2020', pages: 320 },
    { id: 106, title: 'Mạng Máy Tính', category: 'Công nghệ thông tin', author: 'Andrew S. Tanenbaum', publisher: 'NXB Thống Kê', quantity_total: 25, quantity_remaining: 12, price: '180,000', year: '2021', pages: 600 },
]);

const booksData = computed(() => props.books?.data?.length ? props.books.data : sampleBooks.value);

// Search
const searchQuery = ref('');
const filteredBooks = computed(() => {
    if (!searchQuery.value) return booksData.value;
    const query = searchQuery.value.toLowerCase();
    return booksData.value.filter(book =>
        book.title.toLowerCase().includes(query) ||
        book.author.toLowerCase().includes(query) ||
        book.category.toLowerCase().includes(query)
    );
});

// Actions
const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (book) => {
    isEditing.value = true;
    selectedBook.value = book;
    form.id = book.id;
    form.title = book.title;
    form.category_id = ''; // Assuming category needs ID mapping, logic remains same
    form.author = book.author;
    form.publisher = book.publisher;
    form.year = book.year;
    form.pages = book.pages;
    form.price = book.price;
    form.quantity = book.quantity_total; // Map quantity_total to quantity input
    showModal.value = true;
};

const confirmDelete = (book) => {
    selectedBook.value = book;
    showDeleteModal.value = true;
};

const saveBook = () => {
    // In real app, submit to server
    console.log('Saving book:', form);
    showModal.value = false;
    form.reset();
};

const deleteBook = () => {
    // In real app, delete on server
    console.log('Deleting book:', selectedBook.value);
    showDeleteModal.value = false;
    selectedBook.value = null;
};
</script>

<template>
    <Head title="Quản lý Sách - Admin" />
    <AdminLayout title="Quản lý Sách">
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <!-- Header Actions -->
            <BookActions
                @add="openAddModal"
                @import="() => {}"
                @download-template="() => {}"
            />

            <!-- Search & Filter Bar -->
            <BooksFilter
                v-model="searchQuery"
                :categories="['Công nghệ thông tin', 'Khoa học cơ bản', 'Kinh tế']"
            />

            <!-- Books Table -->
            <BooksTable
                :books="filteredBooks"
                @edit="openEditModal"
                @delete="confirmDelete"
            />

            <!-- Pagination -->
            <BookPagination />
        </div>

        <!-- Add/Edit Modal -->
        <BookFormModal
            :show="showModal"
            :form="form"
            :is-editing="isEditing"
            @close="showModal = false"
            @submit="saveBook"
        />

        <!-- Delete Modal -->
        <DeleteConfirmModal
            :show="showDeleteModal"
            :book="selectedBook"
            @close="showDeleteModal = false"
            @confirm="deleteBook"
        />
    </AdminLayout>
</template>
