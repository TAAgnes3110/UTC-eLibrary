<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/Components/ui/button';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';
import { classificationsApi } from '@/api/classifications';
import { extractApiPaginator } from '@/utils/adminPagination';
import { toast } from '@/store/toast';

const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const rows = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const selectedIds = ref([]);
const showDeleteModal = ref(false);
const rowToDelete = ref(null);
const showFormModal = ref(false);
const form = ref({ id: null, name: '' });
const showFilterPanel = ref(false);
const filters = ref({
    keyword: '',
    sort: '',
    searchIn: { code: true, name: true },
});
const SEARCH_IN_OPTIONS = [
    { key: 'code', label: 'Mã phân loại' },
    { key: 'name', label: 'Tên phân loại' },
];

const hasSelection = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed(() => rows.value.length > 0 && selectedIds.value.length === rows.value.length);
const displayRows = computed(() => {
    const sorted = [...rows.value];
    const sortKey = String(filters.value.sort || '');
    if (sortKey === 'name_asc') sorted.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
    else if (sortKey === 'name_desc') sorted.sort((a, b) => String(b.name || '').localeCompare(String(a.name || '')));
    else if (sortKey === 'oldest') sorted.sort((a, b) => Number(a.id || 0) - Number(b.id || 0));
    else sorted.sort((a, b) => Number(b.id || 0) - Number(a.id || 0));
    return sorted;
});

function resetForm() {
    form.value = { id: null, name: '' };
    showFormModal.value = false;
}

function openAddForm() {
    form.value = { id: null, name: '' };
    showFormModal.value = true;
}

function editItem(item) {
    form.value = { id: item.id, name: item.name || '' };
    showFormModal.value = true;
}

function toggleSelectAll() {
    selectedIds.value = isAllSelected.value ? [] : rows.value.map((r) => r.id);
}

function toggleSelect(id) {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
        return;
    }
    selectedIds.value = [...selectedIds.value, id];
}

async function loadRows(page = pagination.value.current_page || 1) {
    loading.value = true;
    try {
        const payload = await classificationsApi.list({
            page,
            per_page: pagination.value.per_page || 20,
            keyword: filters.value.keyword || undefined,
        });
        const { items, meta } = extractApiPaginator(payload, 20);
        rows.value = items;
        pagination.value = {
            current_page: Number(meta.current_page || 1),
            last_page: Number(meta.last_page || 1),
            per_page: Number(meta.per_page || 20),
            total: Number(meta.total || items.length),
        };
        selectedIds.value = selectedIds.value.filter((id) => items.some((r) => r.id === id));
    } catch {
        toast.error('Không thể tải danh sách phân loại.');
    } finally {
        loading.value = false;
    }
}

async function saveItem() {
    const payload = {
        name: String(form.value.name || '').trim(),
    };
    if (!payload.name) {
        toast.error('Vui lòng nhập tên phân loại.');
        return;
    }
    saving.value = true;
    try {
        if (form.value.id) {
            await classificationsApi.update(form.value.id, payload);
            toast.success('Đã cập nhật phân loại.');
        } else {
            await classificationsApi.create(payload);
            toast.success('Đã tạo phân loại.');
        }
        showFormModal.value = false;
        resetForm();
        await loadRows(1);
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể lưu phân loại.');
    } finally {
        saving.value = false;
    }
}

function openDeleteOne(item) {
    rowToDelete.value = item;
    showDeleteModal.value = true;
}

function openDeleteSelected() {
    if (!hasSelection.value) return;
    rowToDelete.value = null;
    showDeleteModal.value = true;
}

async function confirmDelete() {
    deleting.value = true;
    try {
        if (rowToDelete.value?.id) {
            await classificationsApi.remove(rowToDelete.value.id);
            toast.success('Đã xóa phân loại.');
        } else if (selectedIds.value.length > 0) {
            await Promise.all(selectedIds.value.map((id) => classificationsApi.remove(id)));
            toast.success('Đã xóa các phân loại đã chọn.');
            selectedIds.value = [];
        }
        showDeleteModal.value = false;
        rowToDelete.value = null;
        await loadRows();
    } catch {
        toast.error('Không thể xóa phân loại.');
    } finally {
        deleting.value = false;
    }
}

async function exportExcel() {
    try {
        const params = {};
        if (selectedIds.value.length > 0) {
            params.ids = selectedIds.value;
        } else if (displayRows.value.length > 0) {
            params.ids = displayRows.value.map((r) => r.id);
        }
        const response = await classificationsApi.export(params);
        const blob = new Blob([response.data], {
            type: response.headers['content-type'] || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'Danh_muc_phan_loai.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        toast.success('Đã xuất Excel phân loại.');
    } catch (e) {
        console.error(e);
        toast.error('Không thể xuất Excel phân loại.');
    }
}

onMounted(async () => {
    await loadRows(1);
});
</script>

<template>
    <Head title="Quản lý phân loại sách - Admin" />
    <AdminLayout
        title="Quản lý phân loại"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Quản lý phân loại' }, { label: 'Phân loại sách' }]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading title="Danh sách phân loại sách" />

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                add-label="Thêm phân loại"
                :show-import="false"
                :show-export="true"
                :show-update-file="false"
                @add="openAddForm"
                @export-excel="exportExcel"
                @delete-selected="openDeleteSelected"
                @deselect-all="selectedIds = []"
            />

            <AdminFilterSearch
                v-model="filters.keyword"
                search-placeholder="Tìm theo mã hoặc tên phân loại..."
                :show-filter-button="false"
                @search="loadRows(1)"
            >
                <template #filters>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <AdminFilterPanel
                            :options="SEARCH_IN_OPTIONS"
                            v-model:model-value="filters.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filters.sort" class="admin-filter-select admin-filter-select-centered !h-10 !rounded-xl px-3.5 shadow-sm min-w-[145px] text-sm">
                            <option value="">Sắp xếp</option>
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                            <option value="name_asc">Tên A → Z</option>
                            <option value="name_desc">Tên Z → A</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] table-auto text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="px-3 py-3.5 w-12 align-middle">
                                    <span class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            :checked="isAllSelected"
                                            :indeterminate="hasSelection && !isAllSelected"
                                            class="admin-table-checkbox"
                                            @change="toggleSelectAll"
                                        />
                                    </span>
                                </th>
                                <th class="px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Mã</th>
                                <th class="px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Tên phân loại</th>
                                <th class="px-3 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="item in displayRows" :key="item.id" class="admin-table-row">
                                <td class="px-3 py-3 align-middle">
                                    <span class="admin-table-checkbox-wrap">
                                        <input type="checkbox" :checked="selectedIds.includes(item.id)" class="admin-table-checkbox" @change="toggleSelect(item.id)" />
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-middle text-[12px] font-semibold font-mono text-slate-700 dark:text-slate-200">{{ item.code }}</td>
                                <td class="px-3 py-3 align-middle text-[13px] font-medium text-slate-800 dark:text-slate-100">{{ item.name }}</td>
                                <td class="px-3 py-3 align-middle text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-0.5">
                                        <AdminTableActionIcon icon="lucide:pen-square" tone="slate" title="Sửa" icon-class="w-4 h-4" @click="editItem(item)" />
                                        <AdminTableActionIcon icon="lucide:trash-2" tone="rose" title="Xóa" icon-class="w-4 h-4" @click="openDeleteOne(item)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!loading && rows.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">Không có dữ liệu phân loại.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AdminPaginationBar
                always-show
                :current-page="pagination.current_page"
                :last-page="pagination.last_page"
                :disabled="loading"
                @go-page="loadRows"
            />
        </div>

        <AdminDeleteConfirmModal
            :show="showDeleteModal"
            title="Xác nhận xóa phân loại"
            item-label="phân loại"
            :item="rowToDelete"
            :selected-count="rowToDelete ? 0 : selectedIds.length"
            :loading="deleting"
            @close="showDeleteModal = false"
            @confirm="confirmDelete"
        />

        <Teleport to="body">
            <div v-if="showFormModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60" @click="showFormModal = false" />
                <div class="relative w-full max-w-xl rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ form.id ? 'Sửa phân loại' : 'Thêm phân loại' }}</h3>
                        <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="showFormModal = false">
                            <Icon icon="lucide:x" class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-6">
                        <input v-model="form.name" class="admin-filter-select" placeholder="Tên phân loại" />
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                        <Button variant="outline" :disabled="saving" @click="showFormModal = false">Hủy</Button>
                        <Button
                            class="bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-60"
                            :disabled="saving"
                            @click="saveItem"
                        >
                            {{ saving ? 'Đang lưu...' : 'Lưu phân loại' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

