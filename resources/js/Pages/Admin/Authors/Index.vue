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
    bio: '',
});

const authors = ref([
    { id: 1, name: 'Nguyễn Đức Nghĩa', count: 12, bio: 'Giảng viên ĐH Bách Khoa, chuyên gia CNTT.' },
    { id: 2, name: 'Phạm Văn Ất', count: 8, bio: 'Tác giả giáo trình lập trình nổi tiếng.' },
    { id: 3, name: 'Andrew S. Tanenbaum', count: 5, bio: 'Chuyên gia mạng máy tính và hệ điều hành.' },
    { id: 4, name: 'N. Gregory Mankiw', count: 3, bio: 'Nhà kinh tế học Harvard.' },
]);

const searchQuery = ref('');
const filteredAuthors = computed(() => {
    return authors.value.filter(item =>
        item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
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
    <Head title="Quản lý Tác giả - Admin" />
    <AdminLayout title="Quản lý Tác giả">
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <Button @click="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg">
                    <Icon icon="lucide:user-plus" class="w-4 h-4 mr-2" />
                    Thêm Tác giả
                </Button>
            </div>

            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                    <Input v-model="searchQuery" placeholder="Tìm kiếm tác giả..." class="pl-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 border-none dark:text-white" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="author in filteredAuthors" :key="author.id"
                    class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 hover:shadow-xl transition-all group relative overflow-hidden text-center">
                    <div class="mb-4 relative inline-block">
                        <div class="w-20 h-20 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 mx-auto border-4 border-white dark:border-slate-950 shadow-md">
                            <Icon icon="lucide:user" class="w-10 h-10" />
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">{{ author.name }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 line-clamp-2 h-8">{{ author.bio }}</p>

                    <div class="inline-block px-4 py-1.5 bg-slate-50 dark:bg-slate-800 rounded-full mb-6">
                        <span class="text-xs font-black text-blue-600 dark:text-blue-400">{{ author.count }} tác phẩm</span>
                    </div>

                    <div class="flex gap-2 justify-center">
                        <button class="flex-1 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-100 transition-colors text-sm font-bold">Sửa</button>
                        <button class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors">
                            <Icon icon="lucide:trash-2" class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-300">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Thêm Tác giả</h3>
                        <button @click="showModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full">
                            <Icon icon="lucide:x" class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>
                    <div class="p-8 space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Tên tác giả</label>
                            <Input v-model="form.name" class="h-12 rounded-xl dark:bg-slate-800 dark:text-white border-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Tiểu sử</label>
                            <textarea v-model="form.bio" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-none h-32 dark:text-white focus:ring-2 focus:ring-blue-600/20"></textarea>
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
