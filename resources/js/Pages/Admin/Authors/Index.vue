<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import ImportExcelModal from '@/Components/Admin/Books/ImportExcelModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';

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
const showDeleteModal = ref(false);
const showImportModal = ref(false);
const showTrashDrawer = ref(false);
const trashedAuthors = ref([]);
const loadingTrash = ref(false);
const importLoading = ref(false);
const isEditing = ref(false);
const selectedAuthor = ref(null);

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
    window.location.href = '/templates/02-tac-gia/Mau_nhap_tac_gia.csv';
};

const importExcel = async (file) => {
    importLoading.value = true;
    setTimeout(() => {
        importLoading.value = false;
        showImportModal.value = false;
    }, 1500);
};

const save = () => {
    showModal.value = false;
};

const confirmDelete = (author) => {
    selectedAuthor.value = author;
    showDeleteModal.value = true;
};

const deleteAuthor = async () => {
    if (!selectedAuthor.value) {
        showDeleteModal.value = false;
        return;
    }
    try {
        await window.axios.delete(`/authors/${selectedAuthor.value.id}`);
        router.reload();
    } catch (_) {
        router.reload();
    }
    showDeleteModal.value = false;
    selectedAuthor.value = null;
};

const openTrashDrawer = () => {
    showTrashDrawer.value = true;
    fetchTrash();
};
const fetchTrash = async () => {
    loadingTrash.value = true;
    try {
        const { data } = await window.axios.get('/authors/trash');
        trashedAuthors.value = data.data || [];
    } catch {
        trashedAuthors.value = [];
    }
    loadingTrash.value = false;
};
const onRestoreAuthor = async (id) => {
    try {
        await window.axios.post(`/authors/restore/${id}`);
        fetchTrash();
        router.reload();
    } catch (_) {}
};
const onForceDeleteAuthor = async (id) => {
    if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
    try {
        await window.axios.delete(`/authors/force/${id}`);
        fetchTrash();
        router.reload();
    } catch (_) {}
};

const exportExcel = () => {
    // Generate a simple CSV for mock purposes
    const headers = ['ID', 'Tên Tác Giả', 'Ngày Sinh', 'Quốc Tịch', 'Số Lượng Tác Phẩm', 'Tiểu Sử'];

    let csvContent = headers.join(',') + '\n';

    props.authors.forEach(a => {
        const row = [
            a.id,
            `"${a.name || ''}"`,
            `"${a.birth_date || ''}"`,
            `"${a.nationality || ''}"`,
            a.books_count || 0,
            `"${(a.bio || '').replace(/"/g, '""')}"`
        ];
        csvContent += row.join(',') + '\n';
    });

    const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'Danh_Sach_Tac_Gia.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="Quản lý Tác giả - Admin" />
    <AdminLayout
        title="Quản lý Tác giả"
        :breadcrumbs="[
            { label: 'Dữ liệu thư viện' },
            { label: 'Quản lý Tác giả' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Quản lý Tác giả</h2>
                <Button variant="outline" size="sm" class="gap-1.5" @click="openTrashDrawer">
                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                    Thùng rác
                </Button>
            </div>

            <!-- Bộ lọc + Tìm kiếm thống nhất -->
            <AdminFilterSearch
                v-model="searchQuery"
                search-placeholder="Nhập tên tác giả, quốc tịch, tiểu sử..."
                @search="() => {}"
            >
                <template #actions>
                    <button @click="showImportModal = true" class="btn-excel-import">
                        <Icon icon="lucide:file-spreadsheet" class="w-3.5 h-3.5" />
                        Nhập excel
                    </button>
                    <button @click="exportExcel" class="btn-excel-export">
                        <Icon icon="lucide:file-down" class="w-3.5 h-3.5" />
                        Xuất excel
                    </button>
                    <button @click="openAddModal" class="btn-action-primary">
                        <Icon icon="lucide:user-plus" class="w-3.5 h-3.5" />
                        Thêm tác giả
                    </button>
                </template>
            </AdminFilterSearch>

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
                            <tr v-for="a in filtered" :key="a.id" class="admin-table-row">
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
                                        <button @click="confirmDelete(a)" class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-all" title="Xóa">
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

        <!-- Premium Add/Edit Modal -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" @click.self="showModal = false">
                    <div class="relative bg-white dark:bg-slate-900 rounded-[24px] shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200 border border-slate-100 dark:border-slate-800">

                        <!-- Header -->
                        <div class="px-8 py-5 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center shrink-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-20">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-[18px] bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-xl shadow-blue-500/20">
                                    <Icon :icon="isEditing ? 'lucide:user-cog' : 'lucide:user-plus'" class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-white leading-tight tracking-tight">
                                        {{ isEditing ? 'Cập nhật tác giả' : 'Thêm tác giả mới' }}
                                    </h3>
                                    <p class="text-[12px] text-slate-400 font-semibold uppercase tracking-widest mt-0.5">Hệ thống quản lý thư viện số</p>
                                </div>
                            </div>
                            <button @click="showModal = false" class="w-10 h-10 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full flex items-center justify-center transition-all group active:scale-90">
                                <Icon icon="lucide:x" class="w-5 h-5 text-slate-400 group-hover:text-rose-500 transition-colors" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-slate-50/50 dark:bg-slate-900/50">
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="col-span-2 space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Họ và tên tác giả <span class="text-rose-500">*</span></label>
                                        <Input v-model="form.name" placeholder="Ví dụ: Nguyễn Nhật Ánh" class="h-14 rounded-[20px] text-[15px] border-slate-200 dark:border-slate-800 dark:bg-white/50 dark:dark:bg-slate-900/80 shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all font-bold placeholder:text-slate-300 dark:placeholder:text-slate-600" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Ngày sinh</label>
                                        <Input v-model="form.birth_date" type="date" class="h-14 rounded-[20px] text-[15px] border-slate-200 dark:border-slate-800 dark:bg-white/50 dark:dark:bg-slate-900/80 shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all font-bold text-slate-600 dark:text-slate-300 [color-scheme:light] dark:[color-scheme:dark]" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Quốc tịch</label>
                                        <Input v-model="form.nationality" placeholder="Ví dụ: Việt Nam" class="h-14 rounded-[20px] text-[15px] border-slate-200 dark:border-slate-800 dark:bg-white/50 dark:dark:bg-slate-900/80 shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all font-bold" />
                                    </div>
                                    <div class="col-span-2 space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Tiểu sử / Giới thiệu</label>
                                        <textarea v-model="form.bio" placeholder="Mô tả tóm tắt về tác giả..." class="w-full h-32 p-4 rounded-[20px] border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all text-[14px] font-medium resize-none focus:outline-none placeholder:text-slate-300 dark:placeholder:text-slate-600"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-8 py-6 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center bg-white dark:bg-slate-900 shrink-0 gap-4">
                            <Button @click="showModal = false" variant="ghost" class="h-12 rounded-[20px] px-8 text-[14px] font-extrabold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                                Hủy bỏ
                            </Button>
                            <Button @click="save" class="h-12 rounded-[20px] px-10 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-[14px] font-extrabold shadow-xl shadow-blue-500/30 transition-all hover:scale-[1.03] active:scale-95 flex items-center justify-center gap-2">
                                <Icon :icon="isEditing ? 'lucide:save-all' : 'lucide:check-circle'" class="w-5 h-5" />
                                {{ isEditing ? 'Lưu thay đổi' : 'Xác nhận thêm' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <AdminDeleteConfirmModal
            :show="showDeleteModal"
            title="Xác nhận xóa tác giả"
            item-label="tác giả"
            :item="selectedAuthor"
            @close="showDeleteModal = false"
            @confirm="deleteAuthor"
        />
        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác – Tác giả"
            item-label-key="name"
            :items="trashedAuthors"
            :loading="loadingTrash"
            @close="showTrashDrawer = false"
            @restore="onRestoreAuthor"
            @force-delete="onForceDeleteAuthor"
        />

        <!-- Import Modal -->
        <ImportExcelModal
            :show="showImportModal"
            :loading="importLoading"
            @close="showImportModal = false"
            @import="importExcel"
            @download-template="downloadTemplate"
        />
    </AdminLayout>
</template>
