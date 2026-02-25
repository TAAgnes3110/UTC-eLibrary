<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    authors: { type: Array, default: () => [
        { id: 1, name: 'Hồ Ngọc Đại', bio: 'GS.TSKH Hồ Ngọc Đại là người sáng lập Trung tâm Thực nghiệm Giáo dục phổ thông.', birth_date: '1936-04-05', books_count: 12, nationality: 'Việt Nam' },
        { id: 2, name: 'Nguyễn Nhật Ánh', bio: 'Nhà văn chuyên viết cho tuổi mới lớn với nhiều tác phẩm nổi tiếng như Mắt Biếc.', birth_date: '1955-05-07', books_count: 45, nationality: 'Việt Nam' },
        { id: 3, name: 'Tô Hoài', bio: 'Tác giả của tác phẩm thiếu nhi kinh điển Dế Mèn Phiêu Lưu Ký.', birth_date: '1920-09-27', books_count: 20, nationality: 'Việt Nam' },
        { id: 4, name: 'Agatha Christie', bio: 'Nữ nhà văn trinh thám người Anh, nổi tiếng với các tiểu thuyết hình sự.', birth_date: '1890-09-15', books_count: 85, nationality: 'Anh' },
    ]}
});

const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);

const filtered = computed(() => {
    if (!searchQuery.value) return props.authors;
    const q = searchQuery.value.toLowerCase();
    return props.authors.filter(a =>
        a.name.toLowerCase().includes(q) ||
        (a.bio || '').toLowerCase().includes(q) ||
        (a.nationality || '').toLowerCase().includes(q)
    );
});

const form = useForm({
    id: null,
    name: '',
    bio: '',
    birth_date: '',
    nationality: 'Việt Nam',
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const editAuthor = (author) => {
    isEditing.value = true;
    form.id = author.id;
    form.name = author.name;
    form.bio = author.bio || '';
    form.birth_date = author.birth_date || '';
    form.nationality = author.nationality || 'Việt Nam';
    showModal.value = true;
};

const downloadTemplate = () => {
    window.location.href = '/templates/mau_nhap_tac_gia.csv';
};

const save = () => {
    showModal.value = false;
};
</script>

<template>
    <Head title="Quản lý Tác giả - Admin" />
    <AdminLayout
        title="Quản lý Tác giả"
        :breadcrumbs="[
            { label: 'Dữ liệu Thư viện' },
            { label: 'Quản lý Tác giả' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Action Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Tác giả</h2>
                <div class="flex items-center gap-1.5">
                    <button @click="downloadTemplate" class="btn-excel-import">
                        <Icon icon="lucide:file-spreadsheet" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Nhập excel</span>
                    </button>
                    <button class="btn-excel-export">
                        <Icon icon="lucide:file-down" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Xuất excel</span>
                    </button>
                    <button @click="openAddModal" class="btn-action-primary">
                        <Icon icon="lucide:user-plus" class="w-[18px] h-[18px]" />
                        <span>Thêm tác giả</span>
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                    <Input v-model="searchQuery" placeholder="Tìm tên tác giả, quốc tịch, tiểu sử..." class="pl-10 h-10 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm focus:ring-1 focus:ring-blue-500/30" />
                </div>
            </div>

            <!-- Table (Split Columns for DB Readiness) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-16 text-center">Mã</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Họ và tên</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Quốc tịch</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ngày sinh</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tiểu sử</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Tác phẩm</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="a in filtered" :key="a.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all">
                                <td class="p-4 text-center font-mono text-xs text-slate-400">#{{ String(a.id).padStart(3, '0') }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform font-bold text-xs uppercase">
                                            {{ a.name.charAt(0) }}
                                        </div>
                                        <div class="font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors text-[13px] tracking-tight">{{ a.name }}</div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded text-[11px] font-bold uppercase">
                                        {{ a.nationality }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2 text-[12px] text-slate-500 font-medium">
                                        {{ a.birth_date }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <p class="text-[12px] text-slate-500 line-clamp-1 max-w-[200px] xl:max-w-xs">{{ a.bio }}</p>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded text-[11px] font-bold">
                                        {{ a.books_count }} tài liệu
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-1">
                                        <button @click="editAuthor(a)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all">
                                            <Icon icon="lucide:edit-3" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-all">
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
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-xl overflow-hidden shadow-xl border border-slate-200 dark:border-slate-800">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-blue-600">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">{{ isEditing ? 'Cập nhật' : 'Thêm mới' }} tác giả</h3>
                        <button @click="showModal = false" class="text-white/80 hover:text-white">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div class="col-span-2 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Họ và tên tác giả</label>
                            <Input v-model="form.name" placeholder="Ví dụ: Nguyễn Nhật Ánh" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ngày sinh</label>
                            <Input v-model="form.birth_date" type="date" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark]" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quốc tịch</label>
                            <Input v-model="form.nationality" placeholder="Việt Nam" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-2 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tiểu sử / Giới thiệu</label>
                            <textarea v-model="form.bio" placeholder="Mô tả tóm tắt về tác giả..." class="w-full h-24 p-3 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white outline-none focus:ring-1 focus:ring-blue-500/50 transition-all resize-none"></textarea>
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
