<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import BookshelfCellsTable from '@/Components/Admin/Warehouses/BookshelfCellsTable.vue';
import BookshelfCellFormModal from '@/Components/Admin/Warehouses/BookshelfCellFormModal.vue';
import { Button } from '@/Components/ui/button';
import { warehousesApi } from '@/api/warehouses';
import { classificationsApi } from '@/api/classifications';
import { classificationDetailsApi } from '@/api/classificationDetails';
import { bookshelfCellsApi } from '@/api/bookshelfCells';
import { toast } from '@/store/toast';

const BOOKSHELF_PER_PAGE = 20;
const BOOKSHELF_SEARCH_IN_OPTIONS = [
    { key: 'position', label: 'Vị trí' },
    { key: 'label', label: 'Nhãn' },
    { key: 'classification', label: 'Phân loại' },
    { key: 'classification_detail', label: 'Phân loại chi tiết' },
];

const warehouses = ref([]);
const classifications = ref([]);
const details = ref([]);
const cells = ref([]);

const selectedWarehouseId = ref('');
const loading = ref(false);
const generating = ref(false);
const savingCellId = ref(null);

const maxRows = ref(20);
const maxColumns = ref(20);
const pageNum = ref(1);
const cellsPagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: BOOKSHELF_PER_PAGE,
    total: 0,
    overview: {
        usedShelves: 0,
        emptyShelves: 0,
        totalShelves: 0,
    },
});
const selectedIds = ref([]);
const showDeleteModal = ref(false);
const deleting = ref(false);
const creating = ref(false);
const selectedCell = ref(null);
const showCreateModal = ref(false);
const showCellModal = ref(false);
const cellModalMode = ref('view');
const activeSeatCellId = ref(null);
const masterDataLoaded = ref(false);
const masterDataLoading = ref(false);
const createForm = ref({
    warehouse_id: '',
    row_index: 0,
    column_index: 0,
    classification_id: '',
    classification_detail_id: '',
});
const editorForm = ref({
    id: null,
    warehouse_id: '',
    row_index: 0,
    column_index: 0,
    label: '',
    classification_id: '',
    classification_detail_id: '',
    is_active: true,
    book_stats: {},
});

const filterValues = ref({
    searchKeyword: '',
    searchIn: {
        position: true,
        label: true,
        classification: true,
        classification_detail: true,
    },
    status: '',
    sort: '',
});
const showFilterPanel = ref(false);
const warehouseCellsCache = ref({});

const currentWarehouse = computed(() => {
    return warehouses.value.find((w) => String(w.id) === String(selectedWarehouseId.value)) || null;
});

const filteredCells = computed(() => {
    return cells.value;
});
const hasSelection = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed(
    () => filteredCells.value.length > 0 && filteredCells.value.every((c) => selectedIds.value.includes(c.id))
);

const overviewStats = computed(() => {
    const usedShelves = Number(cellsPagination.value.overview?.usedShelves || 0);
    const emptyShelves = Number(cellsPagination.value.overview?.emptyShelves || 0);
    const totalShelves = Number(cellsPagination.value.overview?.totalShelves || 0);
    return { usedShelves, emptyShelves, totalShelves };
});

const selectedWarehouseLabel = computed(() => {
    const warehouse = warehouses.value.find((w) => String(w.id) === String(selectedWarehouseId.value));
    return warehouse ? `${warehouse.code} - ${warehouse.name}` : 'Tất cả kho';
});

const warehouseSelectStyle = computed(() => {
    const chars = Math.max(16, Math.min(64, selectedWarehouseLabel.value.length + 4));
    return { width: `${chars}ch` };
});

const createWarehouseCells = computed(() => {
    const warehouseId = String(createForm.value.warehouse_id || '');
    if (!warehouseId) return [];
    return warehouseCellsCache.value[warehouseId] || [];
});

const emptySlotOptions = computed(() => {
    const occupied = new Set(
        createWarehouseCells.value.map((c) => `${Number(c.row_index)}-${Number(c.column_index)}`)
    );
    const options = [];
    for (let row = 1; row <= 20; row++) {
        for (let col = 1; col <= 20; col++) {
            const key = `${row}-${col}`;
            if (occupied.has(key)) continue;
            options.push({
                value: key,
                label: `R${String(row).padStart(2, '0')}-C${String(col).padStart(2, '0')}`,
            });
        }
    }
    return options;
});

function detailsByClassification(classificationId) {
    if (!classificationId) return [];
    return details.value.filter((d) => String(d.classification_id) === String(classificationId));
}

function searchCells() {
    pageNum.value = 1;
    loadCells();
}

function goPage(page) {
    const p = Number(page);
    if (!Number.isFinite(p) || p < 1 || p > Number(cellsPagination.value.last_page || 1)) return;
    pageNum.value = p;
    loadCells();
}

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
}

function toggleAll() {
    if (isAllSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !filteredCells.value.some((c) => c.id === id));
        return;
    }
    const merged = new Set([...selectedIds.value, ...filteredCells.value.map((c) => c.id)]);
    selectedIds.value = Array.from(merged);
}

function deselectAll() {
    selectedIds.value = [];
}

function resetCreateForm() {
    createForm.value = {
        warehouse_id: '',
        row_index: 0,
        column_index: 0,
        label: '',
        classification_id: '',
        classification_detail_id: '',
    };
}

async function loadWarehouses() {
    const payload = await warehousesApi.list({ per_page: 200 });
    const data = payload?.data ?? payload;
    warehouses.value = Array.isArray(data) ? data : (data?.data ?? []);
}

async function loadMasterData() {
    if (masterDataLoaded.value || masterDataLoading.value) return;
    masterDataLoading.value = true;
    try {
        const clsPayload = await classificationsApi.list({ per_page: 500 });
        const clsData = clsPayload?.data ?? clsPayload;
        classifications.value = Array.isArray(clsData) ? clsData : (clsData?.data ?? []);

        const detailPayload = await classificationDetailsApi.list({ per_page: 1000 });
        const detailData = detailPayload?.data ?? detailPayload;
        details.value = Array.isArray(detailData) ? detailData : (detailData?.data ?? []);
        masterDataLoaded.value = true;
    } finally {
        masterDataLoading.value = false;
    }
}

async function loadCells() {
    loading.value = true;
    try {
        const params = {
            page: pageNum.value,
            per_page: BOOKSHELF_PER_PAGE,
            keyword: String(filterValues.value.searchKeyword || '').trim() || undefined,
            status: filterValues.value.status || undefined,
            sort: filterValues.value.sort || undefined,
        };
        if (!selectedWarehouseId.value) {
            const payload = await bookshelfCellsApi.list(params);
            const data = payload?.data ?? payload;
            const rows = Array.isArray(data) ? data : (data?.data ?? []);
            const meta = data?.meta ?? payload?.meta ?? {};
            cells.value = rows;
            cellsPagination.value = {
                current_page: Number(meta.current_page || pageNum.value),
                last_page: Number(meta.last_page || 1),
                per_page: Number(meta.per_page || BOOKSHELF_PER_PAGE),
                total: Number(meta.total || rows.length),
                overview: {
                    usedShelves: Number(meta?.overview?.usedShelves || 0),
                    emptyShelves: Number(meta?.overview?.emptyShelves || 0),
                    totalShelves: Number(meta?.overview?.totalShelves || 0),
                },
            };
        } else {
            const payload = await bookshelfCellsApi.listByWarehouse(selectedWarehouseId.value, params);
            const data = payload?.data ?? payload;
            const rows = Array.isArray(data) ? data : (data?.data ?? []);
            const meta = data?.meta ?? payload?.meta ?? {};
            cells.value = rows;
            cellsPagination.value = {
                current_page: Number(meta.current_page || pageNum.value),
                last_page: Number(meta.last_page || 1),
                per_page: Number(meta.per_page || BOOKSHELF_PER_PAGE),
                total: Number(meta.total || rows.length),
                overview: {
                    usedShelves: Number(meta?.overview?.usedShelves || 0),
                    emptyShelves: Number(meta?.overview?.emptyShelves || 0),
                    totalShelves: Number(meta?.overview?.totalShelves || 0),
                },
            };
        }
        pageNum.value = cellsPagination.value.current_page;
    } catch (e) {
        cells.value = [];
        toast.error('Không thể tải dữ liệu kệ sách.');
    } finally {
        loading.value = false;
    }
    selectedIds.value = selectedIds.value.filter((id) => cells.value.some((c) => c.id === id));
    if (activeSeatCellId.value && !cells.value.some((c) => String(c.id) === String(activeSeatCellId.value))) {
        activeSeatCellId.value = null;
    }
}

async function ensureWarehouseCells(warehouseId) {
    const key = String(warehouseId || '');
    if (!key) return [];
    if (Array.isArray(warehouseCellsCache.value[key])) {
        return warehouseCellsCache.value[key];
    }
    const payload = await bookshelfCellsApi.listByWarehouse(key, { page: 1, per_page: 500 });
    const data = payload?.data ?? payload;
    const rows = Array.isArray(data) ? data : (data?.data ?? []);
    warehouseCellsCache.value[key] = rows;
    return rows;
}

async function generateSample() {
    if (!selectedWarehouseId.value) return;
    generating.value = true;
    try {
        await bookshelfCellsApi.generateByWarehouse(selectedWarehouseId.value, {
            reset: true,
            max_rows: Number(maxRows.value || 20),
            max_columns: Number(maxColumns.value || 20),
        });
        await loadCells();
        pageNum.value = 1;
        toast.success('Đã tạo dữ liệu mẫu kệ mặc định 20x20 (tối đa 400 vị trí).');
    } catch (e) {
        toast.error('Không thể tạo dữ liệu mẫu kệ sách.');
    } finally {
        generating.value = false;
    }
}

function openCreateModal() {
    loadMasterData();
    resetCreateForm();
    ensureWarehouseCells(createForm.value.warehouse_id);
    showCreateModal.value = true;
}

async function onCreateClassificationChange() {
    await ensureWarehouseCells(createForm.value.warehouse_id);
    createForm.value.row_index = 0;
    createForm.value.column_index = 0;
    createForm.value.classification_detail_id = '';
}

async function submitCreate() {
    const warehouseId = String(createForm.value.warehouse_id || '').trim();
    const rowIndex = Number(createForm.value.row_index || 0);
    const columnIndex = Number(createForm.value.column_index || 0);
    const label = String(createForm.value.label || '').trim();
    const classificationId = String(createForm.value.classification_id || '').trim();
    const detailId = String(createForm.value.classification_detail_id || '').trim();
    if (!warehouseId || !classificationId || !detailId || !label || rowIndex < 1 || columnIndex < 1) {
        toast.error('Vui lòng nhập đầy đủ thông tin: kho, vị trí, nhãn, phân loại và phân loại chi tiết.');
        return;
    }
    creating.value = true;
    try {
        await bookshelfCellsApi.create({
            warehouse_id: Number(warehouseId),
            row_index: rowIndex,
            column_index: columnIndex,
            label,
            classification_id: Number(classificationId),
            classification_detail_id: Number(detailId),
            is_active: true,
        });
        warehouseCellsCache.value = {};
        showCreateModal.value = false;
        if (selectedWarehouseId.value !== warehouseId) {
            selectedWarehouseId.value = warehouseId;
        } else {
            await loadCells();
        }
        toast.success('Đã tạo ô kệ mới.');
    } catch (e) {
        const msg = e?.response?.data?.errors
            ? Object.values(e.response.data.errors).flat()?.[0]
            : null;
        toast.error(msg || 'Không thể tạo ô kệ sách.');
    } finally {
        creating.value = false;
    }
}

function openViewModal(cell) {
    activeSeatCellId.value = cell.id;
    cellModalMode.value = 'view';
    editorForm.value = {
        id: cell.id,
        warehouse_id: cell.warehouse_id ? String(cell.warehouse_id) : '',
        row_index: cell.row_index,
        column_index: cell.column_index,
        label: cell.label || '',
        classification_id: cell.classification_id ? String(cell.classification_id) : '',
        classification_detail_id: cell.classification_detail_id ? String(cell.classification_detail_id) : '',
        is_active: !!cell.is_active,
        book_stats: cell.book_stats || {},
    };
    showCellModal.value = true;
}

function pickSeatCell(cell) {
    activeSeatCellId.value = cell.id;
    openViewModal(cell);
}

function openEditModal(cell) {
    loadMasterData();
    cellModalMode.value = 'edit';
    openViewModal(cell);
    cellModalMode.value = 'edit';
}

function onEditorClassificationChange() {
    editorForm.value.classification_detail_id = '';
}

async function saveEditor() {
    if (cellModalMode.value !== 'edit' || !editorForm.value.id) return;
    savingCellId.value = editorForm.value.id;
    try {
        await bookshelfCellsApi.update(editorForm.value.id, {
            label: editorForm.value.label || null,
            classification_id: editorForm.value.classification_id ? Number(editorForm.value.classification_id) : null,
            classification_detail_id: editorForm.value.classification_detail_id ? Number(editorForm.value.classification_detail_id) : null,
            is_active: !!editorForm.value.is_active,
        });
        warehouseCellsCache.value = {};
        toast.success('Đã cập nhật ô kệ sách.');
        showCellModal.value = false;
        await loadCells();
    } catch (e) {
        toast.error('Không thể cập nhật ô kệ sách.');
    } finally {
        savingCellId.value = null;
    }
}

function openDeleteOne(cell) {
    selectedCell.value = cell;
    showDeleteModal.value = true;
}

function openDeleteMany() {
    if (!hasSelection.value) return;
    selectedCell.value = null;
    showDeleteModal.value = true;
}

async function confirmDelete() {
    deleting.value = true;
    try {
        if (selectedCell.value?.id) {
            await bookshelfCellsApi.remove(selectedCell.value.id);
            toast.success('Đã xóa ô kệ sách.');
        } else if (selectedIds.value.length > 0) {
            await Promise.all(selectedIds.value.map((id) => bookshelfCellsApi.remove(id)));
            toast.success(`Đã xóa ${selectedIds.value.length} ô kệ sách.`);
            deselectAll();
        }
        warehouseCellsCache.value = {};
        showDeleteModal.value = false;
        selectedCell.value = null;
        await loadCells();
    } catch (e) {
        toast.error('Không thể xóa ô kệ sách.');
    } finally {
        deleting.value = false;
    }
}

async function exportExcel() {
    try {
        const params = {};
        if (selectedIds.value.length > 0) {
            params.ids = [...selectedIds.value];
        } else if (filteredCells.value.length > 0) {
            params.ids = filteredCells.value.map((c) => c.id);
        }
        const response = await bookshelfCellsApi.export(params);
        const blob = new Blob([response.data], {
            type:
                response.headers['content-type'] ||
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'Danh_sach_ke_sach.xlsx';
        link.click();
        window.URL.revokeObjectURL(url);
        toast.success('Đã xuất danh sách kệ sách.');
    } catch (e) {
        toast.error('Không thể xuất Excel kệ sách.');
    }
}

watch(selectedWarehouseId, () => {
    pageNum.value = 1;
    loadCells();
});

watch(
    () => [filterValues.value.searchKeyword, filterValues.value.status, filterValues.value.sort],
    () => {
        pageNum.value = 1;
        loadCells();
    }
);
watch(
    () => filterValues.value.searchIn,
    () => {
        pageNum.value = 1;
        loadCells();
    },
    { deep: true }
);

onMounted(async () => {
    await loadWarehouses();
    await loadCells();
});
</script>

<template>
    <Head title="Quản lý kệ sách - Admin" />
    <AdminLayout
        title="Quản lý kệ sách"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý kho sách' },
            { label: 'Kệ sách' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading title="Kệ sách theo ma trận">
                <template #actions>
                    <Link :href="route('admin.warehouses.index')">
                        <Button variant="outline" size="sm" class="gap-1.5">
                            <Icon icon="lucide:arrow-left" class="w-4 h-4" />
                            Về danh sách kho
                        </Button>
                    </Link>
                </template>
            </AdminPageHeading>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                add-label="Thêm mới"
                :show-export="true"
                :show-import="false"
                :show-update-file="false"
                :show-delete-selected="true"
                @add="openCreateModal"
                @export-excel="exportExcel"
                @delete-selected="openDeleteMany"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Tìm theo vị trí, nhãn, phân loại..."
                :show-filter-button="false"
                @search="searchCells"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <AdminFilterPanel
                            :options="BOOKSHELF_SEARCH_IN_OPTIONS"
                            v-model:model-value="filterValues.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select
                            v-model="selectedWarehouseId"
                            class="admin-filter-select admin-filter-select-centered min-w-[16ch] max-w-[64ch] w-auto"
                            :style="warehouseSelectStyle"
                        >
                            <option value="">Tất cả kho</option>
                            <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                                {{ w.code }} - {{ w.name }}
                            </option>
                        </select>
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered rounded-xl">
                            <option value="">Trạng thái</option>
                            <option value="in_stock">Còn sách</option>
                            <option value="out_of_stock">Trống</option>
                        </select>
                        <select v-model="filterValues.sort" class="admin-filter-select admin-filter-select-centered rounded-xl">
                            <option value="">Sắp xếp</option>
                            <option value="label_asc">A → Z</option>
                            <option value="label_desc">Z → A</option>
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                    <p class="text-xs text-slate-500">Số kệ đã dùng</p>
                    <p class="text-2xl font-semibold text-emerald-600">{{ overviewStats.usedShelves }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                    <p class="text-xs text-slate-500">Số kệ chưa dùng</p>
                    <p class="text-2xl font-semibold text-slate-700 dark:text-slate-200">{{ overviewStats.emptyShelves }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                    <p class="text-xs text-slate-500">Tổng số kệ</p>
                    <p class="text-2xl font-semibold text-blue-600">{{ overviewStats.totalShelves }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                <BookshelfCellsTable
                    :rows="filteredCells"
                    :selected-ids="selectedIds"
                    :loading="loading"
                    :is-all-selected="isAllSelected"
                    :has-selection="hasSelection"
                    @toggle-all="toggleAll"
                    @toggle="toggleSelect"
                    @view="openViewModal"
                    @edit="openEditModal"
                    @delete="openDeleteOne"
                />
            </div>

            <AdminPaginationBar
                always-show
                :current-page="cellsPagination.current_page"
                :last-page="cellsPagination.last_page"
                :disabled="loading"
                @go-page="goPage"
            />

            <AdminDeleteConfirmModal
                :show="showDeleteModal"
                title="Xác nhận xóa ô kệ sách"
                item-label="ô kệ"
                :item="selectedCell"
                :selected-count="selectedCell ? 0 : selectedIds.length"
                :loading="deleting"
                @close="showDeleteModal = false"
                @confirm="confirmDelete"
            />

            <BookshelfCellFormModal
                :show="showCreateModal"
                mode="create"
                :form="createForm"
                :warehouses="warehouses"
                :classifications="classifications"
                :details="details"
                :empty-slots="emptySlotOptions"
                :save-loading="creating"
                @close="showCreateModal = false"
                @classification-change="onCreateClassificationChange"
                @save="submitCreate"
            />

            <BookshelfCellFormModal
                :show="showCellModal"
                :mode="cellModalMode"
                :form="editorForm"
                :warehouses="warehouses"
                :classifications="classifications"
                :details="details"
                :save-loading="savingCellId === editorForm.id"
                @close="showCellModal = false"
                @classification-change="onEditorClassificationChange"
                @save="saveEditor"
            />
        </div>
    </AdminLayout>
</template>
