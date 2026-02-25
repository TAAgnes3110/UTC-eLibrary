<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    categories: { type: Array, default: () => [
        { id: 1, name: 'Công nghệ thông tin', description: 'Sách về lập trình, mạng, bảo mật, AI và Khoa học máy tính.', count: 125, type: 'category' },
        { id: 2, name: 'Kinh tế & Quản trị', description: 'Quản trị kinh doanh, marketing, tài chính, kế toán.', count: 84, type: 'category' },
        { id: 3, name: 'Khoa học cơ bản', description: 'Toán học, vật lý, hóa học, sinh học đại cương.', count: 210, type: 'category' },
        { id: 4, name: 'Kỹ thuật Cơ khí', description: 'Cơ khí chế tạo, tự động hóa, kỹ thuật nhiệt.', count: 67, type: 'category' },
        { id: 5, name: 'Tiếng Việt', description: 'Tài liệu bằng ngôn ngữ Tiếng Việt', count: 1200, type: 'language' },
        { id: 6, name: 'Tiếng Anh', description: 'Tài liệu bằng ngôn ngữ Tiếng Anh (English)', count: 450, type: 'language' },
    ]}
});

const activeTab = ref('category');
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);

const filtered = computed(() => {
    return props.categories.filter(item =>
        item.type === activeTab.value &&
        (item.name || '').toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const form = useForm({
    id: null,
    name: '',
    description: '',
    type: 'category',
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.type = activeTab.value;
    showModal.value = true;
};

const editItem = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.name = item.name;
    form.description = item.description || '';
    form.type = item.type;
    showModal.value = true;
};

const save = () => {
    showModal.value = false;
};
</script>

<template>
    <Head :title="(activeTab === 'category' ? 'Thể loại' : 'Ngôn ngữ') + ' - Admin'" />
    <AdminLayout
        title="Quản lý Phân loại"
        :breadcrumbs="[
            { label: 'Dữ liệu Thư viện' },
            { label: activeTab === 'category' ? 'Quản lý Thể loại' : 'Quản lý Ngôn ngữ' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Action Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button
                        @click="activeTab = 'category'"
                        :class="[
                            'px-4 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-tight transition-all',
                            activeTab === 'category' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        Thể loại
                    </button>
                    <button
                        @click="activeTab = 'language'"
                        :class="[
                            'px-4 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-tight transition-all',
                            activeTab === 'language' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        Ngôn ngữ
                    </button>
                </div>

                <div class="flex items-center gap-1.5">
                    <button class="btn-excel-export">
                        <Icon icon="lucide:file-down" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Xuất excel</span>
                    </button>
                    <button @click="openAddModal" class="btn-action-primary">
                        <Icon icon="lucide:plus" class="w-[18px] h-[18px]" />
                        <span>Thêm {{ activeTab === 'category' ? 'thể loại' : 'ngôn ngữ' }}</span>
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                    <Input v-model="searchQuery" :placeholder="'Tìm kiếm tên ' + (activeTab === 'category' ? 'thể loại' : 'ngôn ngữ') + '...'" class="pl-10 h-10 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm focus:ring-1 focus:ring-blue-500/30 text-slate-900 dark:text-white" />
                </div>
            </div>

            <!-- Table (Consistent with Book Management) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-16 text-center">ID</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên {{ activeTab === 'category' ? 'thể loại' : 'ngôn ngữ' }}</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mô tả chi tiết</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Số lượng sách</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="item in filtered" :key="item.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all">
                                <td class="p-4 text-center font-mono text-xs text-slate-400">#{{ String(item.id).padStart(3, '0') }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                                            <Icon :icon="activeTab === 'category' ? 'lucide:tags' : 'lucide:languages'" class="w-4 h-4" />
                                        </div>
                                        <div class="font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors text-[13px] tracking-tight">{{ item.name }}</div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <p class="text-[12px] text-slate-500 line-clamp-1 max-w-xs xl:max-w-md">{{ item.description }}</p>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded text-[11px] font-bold">
                                        {{ item.count }} cuốn
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="editItem(item)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all" title="Chỉnh sửa">
                                            <Icon icon="lucide:edit-3" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-all" title="Xóa">
                                            <Icon icon="lucide:trash-2" class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal (Standard) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-lg overflow-hidden shadow-xl border border-slate-200 dark:border-slate-800">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-blue-600">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                            {{ isEditing ? 'Cập nhật' : 'Thêm mới' }} {{ form.type === 'category' ? 'Thể loại' : 'Ngôn ngữ' }}
                        </h3>
                        <button @click="showModal = false" class="text-white/80 hover:text-white">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tên gọi tài liệu</label>
                            <Input v-model="form.name" :placeholder="'Ví dụ: ' + (form.type === 'category' ? 'Công nghệ thông tin' : 'Tiếng Việt')" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mô tả ngắn</label>
                            <textarea v-model="form.description" class="w-full h-24 p-3 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white outline-none focus:ring-1 focus:ring-blue-500/50 transition-all resize-none" placeholder="Mô tả phạm vi hoặc ý nghĩa..."></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                        <Button variant="outline" size="sm" @click="showModal = false" class="h-8 px-4 font-bold text-xs rounded-md">Bỏ qua</Button>
                        <Button size="sm" @click="save" class="h-8 px-6 font-bold text-xs rounded-md bg-blue-600 hover:bg-blue-700 text-white">Lưu thay đổi</Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
