<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import ImportExcelModal from '@/Components/Admin/Books/ImportExcelModal.vue';

const props = defineProps({
    publishers: { type: Array, default: () => [
        { id: 1, name: 'NXB Giao Thông Vận Tải', address: '80A Trần Hưng Đạo, Hà Nội', email: 'nxbgtvt@utc.edu.vn', phone: '024 3942 2167', books_count: 156 },
        { id: 2, name: 'NXB Bách Khoa', address: 'Số 1 Đại Cồ Việt, Hà Nội', email: 'bkpress@hust.edu.vn', phone: '024 3869 2242', books_count: 89 },
        { id: 3, name: 'NXB Giáo Dục', address: '81 Trần Hưng Đạo, Hà Nội', email: 'nxbgd@moet.gov.vn', phone: '024 3822 0801', books_count: 412 },
    ]}
});

const searchQuery = ref('');
const showModal = ref(false);
const showDeleteModal = ref(false);
const showImportModal = ref(false);
const importLoading = ref(false);
const isEditing = ref(false);
const selectedPublisher = ref(null);

const filtered = computed(() => {
    if (!searchQuery.value) return props.publishers;
    const q = searchQuery.value.toLowerCase();
    return props.publishers.filter(p =>
        p.name.toLowerCase().includes(q) ||
        (p.email || '').toLowerCase().includes(q) ||
        (p.phone || '').toLowerCase().includes(q) ||
        (p.address || '').toLowerCase().includes(q)
    );
});

const form = useForm({
    id: null,
    name: '',
    address: '',
    email: '',
    phone: '',
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const editPublisher = (p) => {
    isEditing.value = true;
    form.id = p.id;
    form.name = p.name;
    form.address = p.address || '';
    form.email = p.email || '';
    form.phone = p.phone || '';
    showModal.value = true;
};

const downloadTemplate = () => {
    window.location.href = '/templates/03-nha-xuat-ban/Mau_nhap_nha_xuat_ban.csv';
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

const confirmDelete = (p) => {
    selectedPublisher.value = p;
    showDeleteModal.value = true;
};

const deletePublisher = () => {
    showDeleteModal.value = false;
    selectedPublisher.value = null;
};

const exportExcel = () => {
    const headers = ['ID', 'Tên Nhà Xuất Bản', 'Địa Chỉ', 'Email', 'Điện Thoại', 'Số Lượng Sách'];

    let csvContent = headers.join(',') + '\n';

    props.publishers.forEach(p => {
        const row = [
            p.id,
            `"${p.name || ''}"`,
            `"${(p.address || '').replace(/"/g, '""')}"`,
            `"${p.email || ''}"`,
            `"${p.phone || ''}"`,
            p.books_count || 0
        ];
        csvContent += row.join(',') + '\n';
    });

    const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'Danh_Sach_Nha_Xuat_Ban.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="Quản lý Nhà xuất bản - Admin" />
    <AdminLayout
        title="Quản lý Nhà xuất bản"
        :breadcrumbs="[
            { label: 'Dữ liệu thư viện' },
            { label: 'Quản lý Nhà xuất bản' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Quản lý Nhà xuất bản</h2>

            <!-- Bộ lọc + Tìm kiếm thống nhất -->
            <AdminFilterSearch
                v-model="searchQuery"
                search-placeholder="Nhập tên NXB, địa chỉ, email..."
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
                        <Icon icon="lucide:plus" class="w-3.5 h-3.5" />
                        Thêm Nhà xuất bản
                    </button>
                </template>
            </AdminFilterSearch>

            <!-- Table (Split Columns for DB Readiness) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-16 text-center">ID</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên Nhà xuất bản</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Địa chỉ trụ sở</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Số điện thoại</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Email</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Số sách</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="p in filtered" :key="p.id" class="admin-table-row">
                                <td class="p-4 text-center font-mono text-xs text-slate-400">#{{ String(p.id).padStart(3, '0') }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-blue-900/40 flex items-center justify-center text-indigo-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                                            <Icon icon="lucide:building-2" class="w-4 h-4" />
                                        </div>
                                        <div class="font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 transition-colors text-[13px] tracking-tight">{{ p.name }}</div>
                                    </div>
                                </td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-400 truncate max-w-[200px] xl:max-w-xs">{{ p.address }}</td>
                                <td class="p-4 text-[12px] font-medium text-slate-600 dark:text-slate-300">{{ p.phone }}</td>
                                <td class="p-4 text-[12px] text-blue-600 dark:text-blue-400 underline underline-offset-4 decoration-blue-500/30">{{ p.email }}</td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 bg-indigo-50 dark:bg-blue-900/40 text-indigo-600 dark:text-blue-400 rounded text-[11px] font-bold">
                                        {{ p.books_count }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="editPublisher(p)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all" title="Chỉnh sửa">
                                            <Icon icon="lucide:edit-3" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button @click="confirmDelete(p)" class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-all" title="Xóa">
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
                                    <Icon :icon="isEditing ? 'lucide:file-edit' : 'lucide:building-2'" class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-white leading-tight tracking-tight">
                                        {{ isEditing ? 'Cập nhật đối tác NXB / Chi nhánh' : 'Thêm Nhà Xuất Bản Mới' }}
                                    </h3>
                                    <p class="text-[12px] text-slate-400 font-semibold uppercase tracking-widest mt-0.5">Hệ thống quản lý thư viện số</p>
                                </div>
                            </div>
                            <button @click="showModal = false" class="w-10 h-10 hover:bg-slate-100 dark:hover:bg-white/10 rounded-full flex items-center justify-center transition-all group active:scale-90">
                                <Icon icon="lucide:x" class="w-5 h-5 text-slate-400 group-hover:text-rose-500 transition-colors" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-slate-50/50 dark:bg-slate-900/50">
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="col-span-2 space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Tên Nhà xuất bản <span class="text-rose-500">*</span></label>
                                        <Input v-model="form.name" placeholder="Ví dụ: NXB Giao Thông Vận Tải" class="h-14 rounded-[20px] text-[15px] border-slate-200 dark:border-slate-800 dark:bg-white/50 dark:bg-slate-800/80 shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all font-bold placeholder:text-slate-300 dark:placeholder:text-slate-600" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Số điện thoại liên hệ</label>
                                        <div class="relative group">
                                            <Icon icon="lucide:phone" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                                            <Input v-model="form.phone" placeholder="024 3xxx..." class="h-14 pl-12 rounded-[20px] text-[15px] border-slate-200 dark:border-slate-800 dark:bg-white/50 dark:bg-slate-800/80 shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all font-bold" />
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Email</label>
                                        <div class="relative group">
                                            <Icon icon="lucide:mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                                            <Input v-model="form.email" placeholder="contact@publisher.com" class="h-14 pl-12 rounded-[20px] text-[15px] border-slate-200 dark:border-slate-800 dark:bg-white/50 dark:bg-slate-800/80 shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all font-bold" />
                                        </div>
                                    </div>
                                    <div class="col-span-2 space-y-2">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Địa chỉ trụ sở</label>
                                        <textarea v-model="form.address" placeholder="Nhập địa chỉ đầy đủ..." class="w-full h-32 p-4 rounded-[20px] border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all text-[14px] font-medium resize-none focus:outline-none placeholder:text-slate-300 dark:placeholder:text-slate-600"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-8 py-6 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center bg-white dark:bg-slate-900 shrink-0 gap-4">
                            <Button @click="showModal = false" variant="ghost" class="h-12 rounded-[20px] px-8 text-[14px] font-extrabold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10 transition-all">
                                Hủy bỏ
                            </Button>
                            <Button @click="save" class="h-12 rounded-[20px] px-10 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-[14px] font-extrabold shadow-xl shadow-blue-500/30 transition-all hover:scale-[1.03] active:scale-95 flex items-center justify-center gap-2">
                                <Icon :icon="isEditing ? 'lucide:save-all' : 'lucide:check-circle'" class="w-5 h-5" />
                                {{ isEditing ? 'Lưu cập nhật' : 'Xác nhận thêm' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Premium Delete Modal -->
        <Teleport to="body">
            <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showDeleteModal = false">
                    <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-[0_20px_50px_rgba(0,0,0,0.2)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] w-full max-w-[400px] overflow-hidden animate-in zoom-in-95 fade-in duration-300 border border-slate-100 dark:border-slate-800/60 relative">
                        <!-- Decorative Top Border -->
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 via-rose-600 to-rose-500"></div>

                        <!-- Close Button -->
                        <button @click="showDeleteModal = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800/50 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-all z-10">
                            <Icon icon="lucide:x" class="w-4 h-4" />
                        </button>

                        <!-- Body Content -->
                        <div class="px-8 pt-10 pb-8 text-center">
                            <!-- Warning Icon with Pulsing Effect -->
                            <div class="relative w-20 h-20 mx-auto mb-6">
                                <div class="absolute inset-0 bg-rose-500/20 dark:bg-rose-500/10 rounded-full animate-ping duration-[2000ms]"></div>
                                <div class="relative w-full h-full rounded-full bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center ring-4 ring-white dark:ring-slate-900">
                                    <Icon icon="lucide:trash-2" class="w-10 h-10 text-rose-600 dark:text-rose-500" />
                                </div>
                            </div>

                            <!-- Texts -->
                            <div class="space-y-3">
                                <h3 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                    Xác nhận xóa?
                                </h3>
                                <div class="px-2">
                                     <p class="text-[14px] font-medium text-slate-600 dark:text-slate-300 leading-relaxed">
                                        Bạn đang thực hiện xóa nhà xuất bản: <br/>
                                        <span class="font-bold text-slate-900 dark:text-white mt-1 block">"{{ selectedPublisher?.name }}"</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="px-8 py-5 border-t border-slate-100 dark:border-slate-800/60 flex items-center gap-3 bg-slate-50 dark:bg-slate-900/50">
                            <Button variant="outline" @click="showDeleteModal = false" class="flex-1 h-11 rounded-[16px] text-[13px] font-extrabold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white border-slate-200 hover:bg-white dark:border-slate-800 dark:hover:bg-white/10 transition-all">
                                Quay lại
                            </Button>
                            <Button @click="deletePublisher" class="flex-1 h-11 rounded-[16px] bg-rose-500 hover:bg-rose-600 text-white text-[13px] font-extrabold shadow-lg shadow-rose-500/25 transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2">
                                Xóa dữ liệu
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

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
