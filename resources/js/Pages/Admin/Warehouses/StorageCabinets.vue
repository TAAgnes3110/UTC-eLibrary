<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';
import { Button } from '@/Components/ui/button';
import { warehousesApi } from '@/api/warehouses';
import { classificationsApi } from '@/api/classifications';
import { storageCabinetsApi } from '@/api/storageCabinets';
import { toast } from '@/store/toast';
import { extractApiPaginator } from '@/utils/adminPagination';

const loading = ref(false);
const savingCabinet = ref(false);
const warehouses = ref([]);
const classifications = ref([]);
const classificationsLoaded = ref(false);
const cabinets = ref([]);
const selectedIds = ref([]);
const pageNum = ref(1);
const cabinetsPagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const showDeleteModal = ref(false);
const cabinetToDelete = ref(null);
const showDetailModal = ref(false);
const cabinetDetail = ref(null);

const filters = ref({
    keyword: '',
    warehouse_id: '',
    sort: '',
    searchIn: {
        code: true,
        name: true,
        warehouse: true,
        classification: true,
    },
});
const showFilterPanel = ref(false);
const STORAGE_CABINET_SEARCH_IN_OPTIONS = [
    { key: 'code', label: 'Mã tủ' },
    { key: 'name', label: 'Tên tủ' },
    { key: 'warehouse', label: 'Kho' },
    { key: 'classification', label: 'Phân loại' },
];

const cabinetForm = ref({
    id: null,
    warehouse_id: '',
    classification_id: '',
    code: '',
    name: '',
    is_active: true,
});
const showCabinetForm = ref(false);

const hasSelection = computed(() => selectedIds.value.length > 0);

const cabinetsWithRate = computed(() => cabinets.value);
const isAllSelected = computed(
    () => cabinetsWithRate.value.length > 0 && selectedIds.value.length === cabinetsWithRate.value.length
);

function warehouseDisplayName(warehouse) {
    const rawName = String(warehouse?.name || '').trim();
    const idx = rawName.indexOf('(');
    if (idx > 0) return rawName.slice(0, idx).trim();
    return rawName;
}

function resetCabinetForm() {
    cabinetForm.value = {
        id: null,
        warehouse_id: '',
        classification_id: '',
        code: '',
        name: '',
        is_active: true,
    };
    showCabinetForm.value = false;
}

function editCabinet(cabinet) {
    if (!classificationsLoaded.value) {
        ensureClassificationsLoaded().catch(() => {
            toast.error('Không thể tải danh sách phân loại.');
        });
    }
    cabinetForm.value = {
        id: cabinet.id,
        warehouse_id: String(cabinet.warehouse_id),
        classification_id: String(cabinet.classification_id || ''),
        code: cabinet.code || '',
        name: cabinet.name || '',
        is_active: !!cabinet.is_active,
    };
    showCabinetForm.value = true;
}

async function loadMasterData() {
    const wh = await warehousesApi.list({ per_page: 200 });
    warehouses.value = extractApiPaginator(wh, 200).items;
}

async function ensureClassificationsLoaded() {
    if (classificationsLoaded.value) return;
    const cls = await classificationsApi.list({ per_page: 500 });
    classifications.value = extractApiPaginator(cls, 500).items;
    classificationsLoaded.value = true;
}

async function loadCabinets() {
    loading.value = true;
    try {
        const payload = await storageCabinetsApi.list({
            page: pageNum.value,
            keyword: filters.value.keyword || undefined,
            warehouse_id: filters.value.warehouse_id || undefined,
            sort: filters.value.sort || undefined,
            search_in: buildSearchInParam(),
            per_page: 20,
        });
        const { items: rows, meta } = extractApiPaginator(payload, 20);
        cabinets.value = rows;
        cabinetsPagination.value = {
            current_page: Number(meta.current_page || pageNum.value),
            last_page: Number(meta.last_page || 1),
            per_page: Number(meta.per_page || 20),
            total: Number(meta.total || rows.length),
        };
        pageNum.value = cabinetsPagination.value.current_page;
        selectedIds.value = selectedIds.value.filter((id) => rows.some((r) => r.id === id));
    } catch {
        toast.error('Không thể tải tủ lưu trữ.');
    } finally {
        loading.value = false;
    }
}

function buildSearchInParam() {
    const searchIn = filters.value.searchIn || {};
    const keys = STORAGE_CABINET_SEARCH_IN_OPTIONS.map((opt) => opt.key);
    const active = keys.filter((k) => !!searchIn[k]);
    if (active.length === 0 || active.length === keys.length) return undefined;
    return active.join(',');
}

async function saveCabinet() {
    const payload = {
        warehouse_id: Number(cabinetForm.value.warehouse_id),
        classification_id: Number(cabinetForm.value.classification_id),
        name: String(cabinetForm.value.name || '').trim(),
        is_active: !!cabinetForm.value.is_active,
    };
    if (!payload.warehouse_id || !payload.classification_id || !payload.name) {
        toast.error('Vui lòng nhập đủ kho, phân loại và tên tủ.');
        return;
    }
    savingCabinet.value = true;
    try {
        if (cabinetForm.value.id) {
            await storageCabinetsApi.update(cabinetForm.value.id, payload);
            toast.success('Đã cập nhật tủ lưu trữ.');
        } else {
            await storageCabinetsApi.create(payload);
            toast.success('Đã tạo tủ lưu trữ.');
        }
        resetCabinetForm();
        await loadCabinets();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể lưu tủ lưu trữ.');
    } finally {
        savingCabinet.value = false;
    }
}

async function openAddCabinetForm() {
    try {
        await ensureClassificationsLoaded();
    } catch {
        toast.error('Không thể tải danh sách phân loại.');
        return;
    }
    cabinetForm.value = {
        id: null,
        warehouse_id: '',
        classification_id: '',
        code: '',
        name: '',
        is_active: true,
    };
    showCabinetForm.value = true;
}

function confirmDeleteOne(cabinet) {
    cabinetToDelete.value = cabinet;
    showDeleteModal.value = true;
}

function openDetail(cabinet) {
    cabinetDetail.value = cabinet;
    showDetailModal.value = true;
}

function confirmDeleteSelected() {
    if (!hasSelection.value) return;
    cabinetToDelete.value = null;
    showDeleteModal.value = true;
}

async function deleteCabinet() {
    try {
        if (cabinetToDelete.value?.id) {
            await storageCabinetsApi.remove(cabinetToDelete.value.id);
            toast.success('Đã xóa tủ lưu trữ.');
        } else {
            await Promise.all(selectedIds.value.map((id) => storageCabinetsApi.remove(id)));
            toast.success(`Đã xóa ${selectedIds.value.length} tủ lưu trữ.`);
            selectedIds.value = [];
        }
        showDeleteModal.value = false;
        cabinetToDelete.value = null;
        await loadCabinets();
    } catch {
        toast.error('Không thể xóa tủ lưu trữ.');
    }
}

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
}

function deselectAll() {
    selectedIds.value = [];
}

function toggleAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
        return;
    }
    selectedIds.value = cabinetsWithRate.value.map((c) => c.id);
}

function searchCabinets() {
    pageNum.value = 1;
    loadCabinets();
}


function goPage(page) {
    const p = Number(page);
    if (!Number.isFinite(p) || p < 1 || p > Number(cabinetsPagination.value.last_page || 1)) return;
    pageNum.value = p;
    loadCabinets();
}

onMounted(async () => {
    await Promise.allSettled([loadCabinets(), loadMasterData()]);
});

</script>

<template>
    <Head title="Quản lý tủ sách - Admin" />
    <AdminLayout
        title="Quản lý tủ sách"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Quản lý kho sách' }, { label: 'Quản lý tủ sách' }]"
    >
        <div class="space-y-4">
            <AdminPageHeading title="Danh sách tủ sách">
            </AdminPageHeading>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                add-label="Thêm tủ sách"
                :show-export="false"
                :show-import="false"
                :show-update-file="false"
                @add="openAddCabinetForm"
                @delete-selected="confirmDeleteSelected"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filters.keyword"
                search-placeholder="Tìm theo tên/mã tủ, kho..."
                :show-filter-button="false"
                @search="searchCabinets"
            >
                <template #filters>
                    <div class="flex items-center gap-3 flex-wrap">
                        <AdminFilterPanel
                            :options="STORAGE_CABINET_SEARCH_IN_OPTIONS"
                            v-model:model-value="filters.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select
                            v-model="filters.warehouse_id"
                            class="admin-filter-select admin-filter-select-centered !h-11 !rounded-xl px-4 shadow-sm max-w-[340px] overflow-hidden text-ellipsis whitespace-nowrap"
                            @change="searchCabinets"
                        >
                            <option value="">Tất cả kho</option>
                            <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">{{ warehouseDisplayName(w) }}</option>
                        </select>
                        <select
                            v-model="filters.sort"
                            class="admin-filter-select admin-filter-select-centered !h-11 !rounded-xl px-4 shadow-sm min-w-[170px]"
                            @change="searchCabinets"
                        >
                            <option value="">Sắp xếp</option>
                            <option value="name_asc">Tên A → Z</option>
                            <option value="name_desc">Tên Z → A</option>
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <div class="rounded-xl border bg-white dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b dark:border-slate-700">
                            <tr>
                                <th class="p-3 text-left w-12">
                                    <input
                                        type="checkbox"
                                        class="admin-table-checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleAll"
                                    />
                                </th>
                                <th class="p-3 text-left whitespace-nowrap">Mã tủ</th>
                                <th class="p-3 text-left whitespace-nowrap">Tên tủ</th>
                                <th class="p-3 text-left whitespace-nowrap">Kho</th>
                                <th class="p-3 text-left whitespace-nowrap">Phân loại</th>
                                <th class="p-3 text-left whitespace-nowrap">Trạng thái</th>
                                <th class="p-3 text-left whitespace-nowrap">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cabinet in cabinetsWithRate" :key="cabinet.id" class="border-b dark:border-slate-800">
                                <td class="p-3">
                                    <input type="checkbox" :checked="selectedIds.includes(cabinet.id)" class="admin-table-checkbox" @change="toggleSelect(cabinet.id)" />
                                </td>
                                <td class="p-3 whitespace-nowrap">{{ cabinet.code || '—' }}</td>
                                <td class="p-3 font-medium">
                                    <div class="max-w-[220px] truncate" :title="cabinet.name">{{ cabinet.name }}</div>
                                </td>
                                <td class="p-3">
                                    <div class="max-w-[320px] truncate" :title="warehouseDisplayName(cabinet.warehouse) || '—'">
                                        {{ warehouseDisplayName(cabinet.warehouse) || '—' }}
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="max-w-[260px] truncate" :title="cabinet.classification?.name || '—'">{{ cabinet.classification?.name || '—' }}</div>
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        :class="cabinet.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'"
                                    >
                                        {{ cabinet.is_active ? 'Hoạt động' : 'Không hoạt động' }}
                                    </span>
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <AdminTableActionIcon icon="lucide:eye" title="Xem chi tiết" @click="openDetail(cabinet)" />
                                        <AdminTableActionIcon icon="lucide:pencil" title="Sửa tủ" tone="slate" @click="editCabinet(cabinet)" />
                                        <AdminTableActionIcon icon="lucide:trash-2" title="Xóa tủ" tone="rose" @click="confirmDeleteOne(cabinet)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!loading && cabinetsWithRate.length === 0">
                                <td colspan="7" class="p-4 text-center text-slate-500">Không có dữ liệu tủ sách.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AdminPaginationBar
                always-show
                :current-page="cabinetsPagination.current_page"
                :last-page="cabinetsPagination.last_page"
                :disabled="loading"
                @go-page="goPage"
            />

            <AdminDeleteConfirmModal
                :show="showDeleteModal"
                title="Xác nhận xóa tủ sách"
                item-label="tủ sách"
                :item="cabinetToDelete"
                :selected-count="cabinetToDelete ? 0 : selectedIds.length"
                @close="showDeleteModal = false"
                @confirm="deleteCabinet"
            />

            <Teleport to="body">
                <div v-if="showDetailModal && cabinetDetail" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/50" @click="showDetailModal = false" />
                    <div class="relative w-full max-w-2xl rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Chi tiết tủ lưu trữ</h3>
                            <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="showDetailModal = false">
                                <Icon icon="lucide:x" class="h-5 w-5" />
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-6 md:grid-cols-2 text-sm">
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Mã tủ</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ cabinetDetail.code || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Tên tủ</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ cabinetDetail.name }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Kho</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ cabinetDetail.warehouse?.name || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Phân loại</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ cabinetDetail.classification?.name || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Số lượng</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ cabinetDetail.current_quantity }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Trạng thái</p>
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    {{ cabinetDetail.is_active ? 'Hoạt động' : 'Không hoạt động' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
                <div v-if="showCabinetForm" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/50" @click="resetCabinetForm" />
                    <div class="relative w-full max-w-3xl rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ cabinetForm.id ? 'Sửa tủ lưu trữ' : 'Thêm tủ lưu trữ' }}
                            </h3>
                            <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="resetCabinetForm">
                                <Icon icon="lucide:x" class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-3 p-6 md:grid-cols-2">
                            <select v-model="cabinetForm.warehouse_id" class="admin-filter-select">
                                <option value="">Chọn kho</option>
                                <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">{{ warehouseDisplayName(w) }}</option>
                            </select>
                            <select v-model="cabinetForm.classification_id" class="admin-filter-select">
                                <option value="">Chọn phân loại</option>
                                <option v-for="c in classifications" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                            </select>
                            <input
                                v-model="cabinetForm.name"
                                class="admin-filter-select md:col-span-2"
                                placeholder="Tên tủ"
                            />
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                            <Button variant="outline" @click="resetCabinetForm">Hủy</Button>
                            <Button class="!bg-blue-600 hover:!bg-blue-700 !text-white" :disabled="savingCabinet" @click="saveCabinet">
                                {{ savingCabinet ? 'Đang lưu...' : cabinetForm.id ? 'Cập nhật' : 'Lưu tủ' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AdminLayout>
</template>
