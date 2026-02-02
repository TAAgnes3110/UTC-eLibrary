<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    description: '',
});

const categories = ref([
    { id: 1, name: 'Công nghệ thông tin', count: 125, description: 'Sách về lập trình, mạng, bảo mật...' },
    { id: 2, name: 'Kinh tế', count: 84, description: 'Quản trị kinh doanh, marketing, tài chính...' },
    { id: 3, name: 'Khoa học cơ bản', count: 210, description: 'Toán học, vật lý, hóa học...' },
    { id: 4, name: 'Văn học', count: 340, description: 'Tiểu thuyết, truyện ngắn, thơ...' },
]);

const searchQuery = ref('');
const filteredCategories = computed(() => {
    return categories.value.filter(cat =>
        cat.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const save = () => {
    showModal.value = false;
};
</script>

<template>
    <Head title="Quản lý Danh mục - Admin" />
    <AdminLayout title="Quản lý Danh mục">
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <Button @click="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg">
                    <Icon icon="lucide:plus" class="w-4 h-4 mr-2" />
                    Thêm Danh mục
                </Button>
            </div>

            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                    <Input v-model="searchQuery" placeholder="Tìm kiếm danh mục..." class="pl-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 border-none dark:text-white" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="cat in filteredCategories" :key="cat.id"
                    class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 hover:shadow-xl transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <Icon icon="lucide:folder" class="w-6 h-6" />
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-amber-500">
                                <Icon icon="lucide:edit" class="w-4 h-4" />
                            </button>
                            <button class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-rose-500">
                                <Icon icon="lucide:trash-2" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">{{ cat.name }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2">{{ cat.description }}</p>
                    <div class="flex items-center justify-between pt-4 border-t border-slate-50 dark:border-slate-800">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Số lượng sách</span>
                        <span class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-xs font-black">
                            {{ cat.count }} cuốn
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-300">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Thêm Danh mục Mới</h3>
                        <button @click="showModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full">
                            <Icon icon="lucide:x" class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>
                    <div class="p-8 space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Tên danh mục</label>
                            <Input v-model="form.name" placeholder="Ví dụ: Công nghệ thông tin" class="h-12 rounded-xl dark:bg-slate-800 dark:text-white border-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Mô tả</label>
                            <textarea v-model="form.description" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600/20 border-none h-32 dark:text-white" placeholder="Mô tả ngắn về danh mục này..."></textarea>
                        </div>
                    </div>
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <Button @click="showModal = false" variant="outline" class="rounded-xl dark:text-slate-300">Hủy</Button>
                        <Button @click="save" class="rounded-xl bg-blue-600 text-white px-8">Lưu lại</Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
