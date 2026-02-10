<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);

const authors = ref([]);
const filteredAuthors = computed(() => {
    if (!authors.value) return [];
    if (!searchQuery.value) return authors.value;
    return authors.value.filter(item =>
        item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const form = useForm({
    id: null,
    name: '',
    tieu_su: '',
    birth_date: '',
});

const fetchAuthors = async () => {
    try {
        const response = await axios.get('/api/authors');
        // Check if response.data.data is the array (pagination) or if it's nested
        authors.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error fetching authors:', error);
    }
};

onMounted(() => {
    fetchAuthors();
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const save = async () => {
    try {
        if (isEditing.value) {
            await axios.put(`/api/authors/${form.id}`, form);
        } else {
            await axios.post('/api/authors', form);
        }
        showModal.value = false;
        fetchAuthors();
    } catch (error) {
        console.error('Error saving author:', error);
    }
};

const editAuthor = (author) => {
    isEditing.value = true;
    form.id = author.id;
    form.name = author.name;
    form.tieu_su = author.tieu_su;
    form.birth_date = author.birth_date;
    showModal.value = true;
};

const deleteAuthor = async (id) => {
    if (confirm('Bạn có chắc chắn muốn xóa tác giả này?')) {
        try {
            await axios.delete(`/api/authors/${id}`);
            fetchAuthors();
        } catch (error) {
            console.error('Error deleting author:', error);
        }
    }
};
</script>

<template>
    <Head title="Quản lý Tác giả - Admin" />
    <AdminLayout title="Quản lý Tác giả">
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <Button @click="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition-all active:scale-95">
                    <Icon icon="lucide:user-plus" class="w-4 h-4 mr-2" />
                    Thêm Tác giả
                </Button>
            </div>

            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                    <Input v-model="searchQuery" placeholder="Tìm kiếm tác giả..." class="pl-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 border-none text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500" />
                </div>
            </div>

            <div v-if="filteredAuthors.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="author in filteredAuthors" :key="author.id"
                    class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 hover:shadow-xl dark:hover:shadow-slate-900/50 transition-all group relative overflow-hidden text-center flex flex-col items-center">

                    <div class="mb-4 relative inline-block">
                        <div class="w-20 h-20 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 mx-auto border-4 border-white dark:border-slate-800 shadow-md group-hover:scale-110 transition-transform duration-300">
                             <!-- Use avatar if available, otherwise icon -->
                            <img v-if="author.avatar" :src="author.avatar" class="w-full h-full rounded-full object-cover" alt="Author Avatar" />
                            <Icon v-else icon="lucide:user" class="w-10 h-10" />
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1 line-clamp-1" :title="author.name">{{ author.name }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2 line-clamp-2 h-8">{{ author.tieu_su || 'Chưa có tiểu sử' }}</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mb-4 font-mono">{{ author.birth_date || 'N/A' }}</p>

                    <div class="mt-auto w-full flex gap-2 justify-center">
                        <button @click="editAuthor(author)" class="flex-1 py-2 px-3 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-500/20 transition-colors text-sm font-bold flex items-center justify-center gap-1">
                            <Icon icon="lucide:edit-3" class="w-3.5 h-3.5" /> Sửa
                        </button>
                        <button @click="deleteAuthor(author.id)" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-xl transition-colors">
                            <Icon icon="lucide:trash-2" class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-800 mb-4">
                    <Icon icon="lucide:inbox" class="w-8 h-8 text-slate-400" />
                </div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Không tìm thấy dữ liệu tác giả</p>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

                <!-- Modal Content -->
                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-300 border border-slate-100 dark:border-slate-800">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 relative z-10">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ isEditing ? 'Cập nhật' : 'Thêm mới' }} Tác giả</h3>
                        <button @click="showModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors text-slate-500 dark:text-slate-400">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider ml-1">Tên tác giả <span class="text-rose-500">*</span></label>
                            <Input v-model="form.name" class="h-11 rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="Nhập tên tác giả" />
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider ml-1">Ngày sinh</label>
                            <Input type="date" v-model="form.birth_date" class="h-11 rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 [color-scheme:light] dark:[color-scheme:dark]" />
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider ml-1">Tiểu sử</label>
                            <textarea v-model="form.tieu_su" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 h-32 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-500 outline-none resize-none transition-all placeholder:text-slate-400" placeholder="Mô tả ngắn về tác giả..."></textarea>
                        </div>
                    </div>

                    <div class="p-5 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                        <Button @click="showModal = false" variant="ghost" class="rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-200/50 dark:hover:bg-slate-800">Hủy bỏ</Button>
                        <Button @click="save" class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-6 shadow-lg shadow-blue-500/20">
                            {{ isEditing ? 'Lưu thay đổi' : 'Thêm tác giả' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
