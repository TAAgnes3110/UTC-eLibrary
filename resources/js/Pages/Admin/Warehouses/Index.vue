<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';

const warehousesData = ref({ data: [] });
const loading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const selectedWarehouse = ref(null);
const form = ref({ id: null, code: '', name: '', is_active: true });

const SEARCH_IN_OPTIONS = [
    { key: 'code', label: 'Mã kho' },
    { key: 'name', label: 'Tên kho' },
];

const filterValues = ref({
    searchKeyword: '',
    searchIn: { code: true, name: true },
    status: '',
});

const fetchWarehouses = async () => {
    loading.value = true;
    try {
        const { data } = await window.axios.get('/warehouses', {
            params: { keyword: filterValues.value.searchKeyword || '' },
        });
        const payload = data?.data ?? data;
        const items = Array.isArray(payload) ? payload : (payload?.data ?? []);
        warehousesData.value = { data: items };
    } catch {
        warehousesData.value = { data: [] };
    }
    loading.value = false;
};

onMounted(() => {
    fetchWarehouses();
});

const warehousesList = computed(() => warehousesData.value?.data ?? []);

const filteredWarehouses = computed(() => {
    let result = warehousesList.value;
    const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
    const sin = filterValues.value.searchIn || {};
    if (kw) {
        const anyChecked = Object.values(sin).some(Boolean);
        if (anyChecked) {
            result = result.filter((w) => {
                const m = [];
                if (sin.code) m.push((w.code || '').toLowerCase().includes(kw));
                if (sin.name) m.push((w.name || '').toLowerCase().includes(kw));
                return m.some(Boolean);
            });
        }
    }
    if (filterValues.value.status !== '') {
        const active = filterValues.value.status === 'active';
        result = result.filter((w) => !!w.is_active === active);
    }
    return result;
});

const selectedIds = ref([]);
const hasSelection = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed(
    () => filteredWarehouses.value.length > 0 && selectedIds.value.length === filteredWarehouses.value.length,
);

const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
};
const toggleAll = () => {
    if (isAllSelected.value) selectedIds.value = [];
    else selectedIds.value = filteredWarehouses.value.map((w) => w.id);
};
const deselectAll = () => {
    selectedIds.value = [];
};

const openAddModal = () => {
    isEditing.value = false;
    form.value = { id: null, code: '', name: '', is_active: true };
    showModal.value = true;
};

const openEditModal = (w) => {
    isEditing.value = true;
    selectedWarehouse.value = w;
    form.value = {
        id: w.id,
        code: w.code || '',
        name: w.name || '',
        is_active: !!w.is_active,
    };
    showModal.value = true;
};

const confirmDelete = (w) => {
    selectedWarehouse.value = w;
    showDeleteModal.value = true;
};

const confirmBulkDelete = () => {
    selectedWarehouse.value = null;
    showDeleteModal.value = true;
};

const saveWarehouse = async () => {
    const payload = {
        code: form.value.code,
        name: form.value.name,
        is_active: form.value.is_active,
    };
    try {
        if (isEditing.value && form.value.id) {
            await window.axios.put(`/warehouses/${form.value.id}`, payload);
        } else {
            await window.axios.post('/warehouses', payload);
        }
        showModal.value = false;
        await fetchWarehouses();
    } catch (e) {
        console.error(e);
    }
};

const deleteWarehouse = async () => {
    try {
        if (selectedWarehouse.value) {
            await window.axios.delete(`/warehouses/${selectedWarehouse.value.id}`);
        } else if (selectedIds.value.length > 0) {
            for (const id of selectedIds.value) {
                await window.axios.delete(`/warehouses/${id}`);
            }
        }
        router.reload();
    } catch (_) {
        router.reload();
    }
    selectedWarehouse.value = null;
    selectedIds.value = [];
    showDeleteModal.value = false;
};

function statusLabel(isActive) {
    return isActive ? 'Đang dùng' : 'Ngừng dùng';
}
function statusClass(isActive) {
    return isActive
        ? 'bg-emerald-500 dark:bg-emerald-600 text-white'
        : 'bg-slate-500 dark:bg-slate-600 text-white';
}
</script>

<template>
    <Head title="Quản lý Kho sách - Admin" />
    <AdminLayout
        title="Quản lý kho sách"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý kho sách' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Danh sách kho sách</h2>
            </div>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                add-label="Thêm kho"
                :show-export="false"
                :show-import="false"
                :show-update-file="false"
                @add="openAddModal"
                @delete-selected="confirmBulkDelete"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Nhập từ khóa để tìm..."
                :show-filter-button="false"
                @search="() => {}"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="active">Đang dùng</option>
                            <option value="inactive">Ngừng dùng</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th class="p-4 w-12">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        :indeterminate="hasSelection && !isAllSelected"
                                        @change="toggleAll"
                                        class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                    />
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã kho</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên kho</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="w in filteredWarehouses"
                                :key="w.id"
                                :class="[selectedIds.includes(w.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                            >
                                <td class="p-4">
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.includes(w.id)"
                                        @change="toggleSelect(w.id)"
                                        class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <td class="p-4">
                                    <p class="font-mono text-[12px] text-slate-700 dark:text-slate-300">{{ w.code }}</p>
                                </td>
                                <td class="p-4">
                                    <p class="font-semibold text-sm text-slate-900 dark:text-white">{{ w.name }}</p>
                                </td>
                                <td class="p-4">
                                    <span :class="[statusClass(w.is_active), 'px-2 py-0.5 rounded text-[11px] font-semibold']">
                                        {{ statusLabel(w.is_active) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-0.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(w)"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                            title="Chỉnh sửa"
                                        >
                                            <Icon icon="lucide:pencil" class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(w)"
                                            class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                            title="Xóa"
                                        >
                                            <Icon icon="lucide:trash-2" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="loading" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Đang tải...</p>
                <p v-else-if="filteredWarehouses.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Không có kho sách nào.</p>
            </div>
        </div>

        <!-- Modal Thêm / Sửa kho sách -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800">
                    <div class="sticky top-0 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 z-10">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ isEditing ? 'Chỉnh sửa kho sách' : 'Thêm kho sách' }}</h3>
                        <button type="button" @click="showModal = false" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã kho <span class="text-rose-500">*</span></label>
                            <Input
                                v-model="form.code"
                                class="h-10 rounded-lg font-mono border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Ví dụ: TV-TT-UTC"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tên kho <span class="text-rose-500">*</span></label>
                            <Input
                                v-model="form.name"
                                class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800"
                                placeholder="Ví dụ: Thư viện Trung tâm UTC"
                            />
                        </div>
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                />
                                Đang sử dụng
                            </label>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                        <Button variant="outline" @click="showModal = false">Hủy bỏ</Button>
                        <Button @click="saveWarehouse" class="bg-blue-600 hover:bg-blue-700 text-white">{{ isEditing ? 'Cập nhật' : 'Lưu' }}</Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <AdminDeleteConfirmModal
            :show="showDeleteModal"
            title="Xác nhận xóa kho sách"
            item-label="kho sách"
            :item="selectedWarehouse"
            :selected-count="selectedWarehouse ? 0 : selectedIds.length"
            @close="showDeleteModal = false"
            @confirm="deleteWarehouse"
        />
    </AdminLayout>
</template>
