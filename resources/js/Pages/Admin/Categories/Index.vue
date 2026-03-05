<script setup>
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import ImportExcelModal from '@/Components/Admin/Books/ImportExcelModal.vue';

const props = defineProps({
    tab: { type: String, default: 'category' },
    categories: { type: Array, default: () => [] },
});

const activeTab = ref(props.tab || 'category');
watch(() => props.tab, (t) => { if (t) activeTab.value = t; });

function setTab(tab) {
    activeTab.value = tab;
    router.get(route('admin.categories.index'), { tab }, { preserveState: true });
}
const searchQuery = ref('');
const showFilterPanel = ref(false);
const SEARCH_IN_OPTIONS = [
    { key: 'name', label: 'Tên' },
    { key: 'description', label: 'Mô tả' },
];
const filterSearchIn = ref({ name: true, description: true });
const showModal = ref(false);
const showImportModal = ref(false);
const showDeleteModal = ref(false);
const importLoading = ref(false);
const isEditing = ref(false);
const selectedItemToDelete = ref(null);

const filtered = computed(() => {
    let list = props.categories.filter(item => item.type === activeTab.value);
    const kw = (searchQuery.value || '').trim().toLowerCase();
    const sin = filterSearchIn.value || {};
    if (kw && (sin.name || sin.description)) {
        list = list.filter(item => {
            const m = [];
            if (sin.name) m.push((item.name || '').toLowerCase().includes(kw));
            if (sin.description) m.push((item.description || '').toLowerCase().includes(kw));
            return m.some(Boolean);
        });
    }
    return list;
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

const deleteItem = (item) => {
    selectedItemToDelete.value = item;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    showDeleteModal.value = false;
};

const downloadTemplate = () => {
    const path = activeTab.value === 'category'
        ? '/templates/04-the-loai/Mau_nhap_the_loai.csv'
        : '/templates/05-ngon-ngu/Mau_nhap_ngon_ngu.csv';
    window.location.href = path;
};

const importExcel = async (file) => {
    importLoading.value = true;
    setTimeout(() => {
        importLoading.value = false;
        showImportModal.value = false;
    }, 1500);
};

const exportExcel = () => {
    let csvContent = '\uFEFF';
    csvContent += `"ID","Tên","Mô tả chi tiết","Số lượng"\n`;
    filtered.value.forEach(item => {
        const row = [
            item.id,
            `"${item.name}"`,
            `"${item.description || ''}"`,
            item.count
        ];
        csvContent += row.join(',') + '\n';
    });

    const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Danh_Sach_${activeTab.value === 'category' ? 'The_Loai' : 'Ngon_Ngu'}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head :title="(activeTab === 'category' ? 'Thể loại' : 'Ngôn ngữ') + ' - Admin'" />
    <AdminLayout
        title="Danh mục"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Danh mục' },
            { label: activeTab === 'category' ? 'Thể loại' : 'Ngôn ngữ' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Tab Thể loại / Ngôn ngữ (đồng bộ với URL ?tab=) -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-800 w-fit">
                <button
                    @click="setTab('category')"
                    :class="[
                        'px-4 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-tight transition-all',
                        activeTab === 'category' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white'
                    ]"
                >
                    Thể loại
                </button>
                <button
                    @click="setTab('language')"
                    :class="[
                        'px-4 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-tight transition-all',
                        activeTab === 'language' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white'
                    ]"
                >
                    Ngôn ngữ
                </button>
            </div>

            <AdminImportExportBar
                :add-label="'Thêm ' + (activeTab === 'category' ? 'thể loại' : 'ngôn ngữ')"
                :show-update-file="false"
                :has-selection="false"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="showImportModal = true"
            />

            <AdminFilterSearch
                v-model="searchQuery"
                :search-placeholder="'Nhập tên ' + (activeTab === 'category' ? 'thể loại' : 'ngôn ngữ') + '...'"
                :show-filter-button="false"
                @search="() => {}"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <AdminFilterPanel
                            :options="SEARCH_IN_OPTIONS"
                            v-model:model-value="filterSearchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                    </div>
                </template>
            </AdminFilterSearch>

            <!-- Table (Consistent with Book Management) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div v-if="filtered.length > 0" class="overflow-x-auto text-nowrap">
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
                            <tr v-for="item in filtered" :key="item.id" class="admin-table-row">
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
                                        <button @click="deleteItem(item)" class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-all" title="Xóa">
                                            <Icon icon="lucide:trash-2" class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="py-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                    Chưa có {{ activeTab === 'category' ? 'thể loại' : 'ngôn ngữ' }} nào. Bấm "Thêm" để tạo.
                </p>
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
                                        Bạn đang thực hiện xóa dữ liệu này: <br/>
                                        <span class="font-bold text-slate-900 dark:text-white mt-1 block">"{{ selectedItemToDelete?.name }}"</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="px-8 py-5 border-t border-slate-100 dark:border-slate-800/60 flex items-center gap-3 bg-slate-50 dark:bg-slate-900/50">
                            <Button variant="outline" @click="showDeleteModal = false" class="flex-1 h-11 rounded-[16px] text-[13px] font-extrabold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white border-slate-200 hover:bg-white dark:border-slate-700 dark:hover:bg-slate-800 transition-all">
                                Quay lại
                            </Button>
                            <Button @click="confirmDelete" class="flex-1 h-11 rounded-[16px] bg-rose-500 hover:bg-rose-600 text-white text-[13px] font-extrabold shadow-lg shadow-rose-500/25 transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2">
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
