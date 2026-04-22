<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';
import { Button } from '@/Components/ui/button';
import { warehousesApi } from '@/api/warehouses';
import { classificationsApi } from '@/api/classifications';
import { classificationDetailsApi } from '@/api/classificationDetails';
import { storageCabinetsApi } from '@/api/storageCabinets';
import { toast } from '@/store/toast';

const loading = ref(false);
const savingCabinet = ref(false);
const savingSlot = ref(false);
const warehouses = ref([]);
const classifications = ref([]);
const details = ref([]);
const detailsLoaded = ref(false);
const cabinets = ref([]);
const selectedCabinetId = ref(null);
const selectedIds = ref([]);
const pageNum = ref(1);
const cabinetsPagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
});
const showDeleteModal = ref(false);
const cabinetToDelete = ref(null);

const filters = ref({
    keyword: '',
    warehouse_id: '',
    classification_id: '',
    status: '',
    sort: '',
});

function resetFilters() {
    filters.value = { keyword: '', warehouse_id: '', classification_id: '', status: '', sort: '' };
}

const cabinetForm = ref({
    id: null,
    warehouse_id: '',
    classification_id: '',
    code: '',
    name: '',
    is_active: true,
});

const slotForm = ref({
    id: null,
    classification_detail_id: '',
    slot_code: '',
    slot_name: '',
    capacity: 30,
    current_quantity: 0,
    is_active: true,
});

const selectedCabinet = computed(() =>
    cabinets.value.find((c) => String(c.id) === String(selectedCabinetId.value)) || null
);
const hasSelection = computed(() => selectedIds.value.length > 0);
const selectedSlotKeys = ref([]);
const isAllSelected = computed(
    () => cabinets.value.length > 0 && cabinets.value.every((c) => selectedIds.value.includes(c.id))
);

const overview = computed(() => {
    const cabinetCount = cabinets.value.length;
    const slotCount = cabinets.value.reduce((sum, c) => sum + (c.slots?.length || 0), 0);
    const capacityTotal = cabinets.value.reduce((sum, c) => sum + Number(c.capacity_total || 0), 0);
    const quantityTotal = cabinets.value.reduce((sum, c) => sum + Number(c.current_quantity || 0), 0);
    return { cabinetCount, slotCount, capacityTotal, quantityTotal };
});

const detailOptions = computed(() => {
    const cls = selectedCabinet.value?.classification_id || cabinetForm.value.classification_id;
    if (!cls) return [];
    return details.value.filter((d) => String(d.classification_id) === String(cls));
});

const cabinetsWithRate = computed(() =>
    cabinets.value.map((cabinet) => {
        const capacity = Number(cabinet.capacity_total || 0);
        const quantity = Number(cabinet.current_quantity || 0);
        const usageRate = capacity > 0 ? Math.min(100, Math.round((quantity / capacity) * 100)) : 0;
        return {
            ...cabinet,
            usageRate,
        };
    })
);

const selectedCabinetUsageRate = computed(() => {
    if (!selectedCabinet.value) return 0;
    const capacity = Number(selectedCabinet.value.capacity_total || 0);
    const quantity = Number(selectedCabinet.value.current_quantity || 0);
    if (capacity <= 0) return 0;
    return Math.min(100, Math.round((quantity / capacity) * 100));
});

const slotManagementRows = computed(() => {
    const rows = [];
    for (const cabinet of cabinetsWithRate.value) {
        const cabinetLabel = cabinet.code ? `${cabinet.code} - ${cabinet.name}` : cabinet.name;
        const classificationLabel = cabinet.classification
            ? `${cabinet.classification.code} - ${cabinet.classification.name}`
            : '—';
        const warehouseLabel = cabinet.warehouse
            ? `${cabinet.warehouse.code} - ${cabinet.warehouse.name}`
            : '—';
        for (const slot of cabinet.slots || []) {
            rows.push({
                cabinetId: cabinet.id,
                cabinetLabel,
                classificationLabel,
                warehouseLabel,
                slot,
            });
        }
    }
    return rows;
});
const hasSlotSelection = computed(() => selectedSlotKeys.value.length > 0);
const isAllSlotsSelected = computed(
    () => slotManagementRows.value.length > 0 && selectedSlotKeys.value.length === slotManagementRows.value.length
);

function resetCabinetForm() {
    cabinetForm.value = {
        id: null,
        warehouse_id: '',
        classification_id: '',
        code: '',
        name: '',
        is_active: true,
    };
}

function resetSlotForm() {
    slotForm.value = {
        id: null,
        classification_detail_id: '',
        slot_code: '',
        slot_name: '',
        capacity: 30,
        current_quantity: 0,
        is_active: true,
    };
}

function editCabinet(cabinet) {
    cabinetForm.value = {
        id: cabinet.id,
        warehouse_id: String(cabinet.warehouse_id),
        classification_id: String(cabinet.classification_id || ''),
        code: cabinet.code || '',
        name: cabinet.name || '',
        is_active: !!cabinet.is_active,
    };
}

function openViewCabinet(cabinet) {
    if (!cabinet) return;
    selectCabinet(cabinet);
}

function getCabinetById(id) {
    return cabinets.value.find((c) => c.id === id) || null;
}

function selectCabinet(cabinet) {
    if (!cabinet) return;
    selectedCabinetId.value = cabinet.id;
    resetSlotForm();
    ensureDetailsLoaded().catch(() => {
        toast.error('Không thể tải danh sách phân loại chi tiết.');
    });
}

async function editSlot(slot) {
    try {
        await ensureDetailsLoaded();
    } catch {
        toast.error('Không thể tải danh sách phân loại chi tiết.');
        return;
    }
    slotForm.value = {
        id: slot.id,
        classification_detail_id: String(slot.classification_detail_id || ''),
        slot_code: slot.slot_code || '',
        slot_name: slot.slot_name || '',
        capacity: Number(slot.capacity || 30),
        current_quantity: Number(slot.current_quantity || 0),
        is_active: !!slot.is_active,
    };
}

async function loadMasterData() {
    const [wh, cls] = await Promise.all([
        warehousesApi.list({ per_page: 200 }),
        classificationsApi.list({ per_page: 500 }),
    ]);
    warehouses.value = Array.isArray(wh?.data?.data) ? wh.data.data : (wh?.data || []);
    classifications.value = Array.isArray(cls?.data?.data) ? cls.data.data : (cls?.data || []);
}

async function ensureDetailsLoaded() {
    if (detailsLoaded.value) return;
    const det = await classificationDetailsApi.list({ per_page: 1000 });
    details.value = Array.isArray(det?.data?.data) ? det.data.data : (det?.data || []);
    detailsLoaded.value = true;
}

async function loadCabinets() {
    loading.value = true;
    try {
        const payload = await storageCabinetsApi.list({
            page: pageNum.value,
            keyword: filters.value.keyword || undefined,
            warehouse_id: filters.value.warehouse_id || undefined,
            classification_id: filters.value.classification_id || undefined,
            status: filters.value.status || undefined,
            sort: filters.value.sort || undefined,
            with_slots: true,
            per_page: 20,
        });
        const rows = payload?.data?.data || [];
        const meta = payload?.data?.meta || {};
        cabinets.value = rows;
        cabinetsPagination.value = {
            current_page: Number(meta.current_page || pageNum.value),
            last_page: Number(meta.last_page || 1),
            per_page: Number(meta.per_page || 20),
            total: Number(meta.total || rows.length),
        };
        pageNum.value = cabinetsPagination.value.current_page;
        if (selectedCabinetId.value && !cabinets.value.some((c) => String(c.id) === String(selectedCabinetId.value))) {
            selectedCabinetId.value = null;
        }
        selectedIds.value = selectedIds.value.filter((id) => rows.some((r) => r.id === id));
        selectedSlotKeys.value = selectedSlotKeys.value.filter((key) => {
            const [cabinetId, slotId] = key.split(':');
            return rows.some((cabinet) =>
                String(cabinet.id) === cabinetId
                && Array.isArray(cabinet.slots)
                && cabinet.slots.some((slot) => String(slot.id) === slotId)
            );
        });
    } catch {
        toast.error('Không thể tải tủ lưu trữ.');
    } finally {
        loading.value = false;
    }
}

async function saveCabinet() {
    const payload = {
        warehouse_id: Number(cabinetForm.value.warehouse_id),
        classification_id: Number(cabinetForm.value.classification_id),
        code: cabinetForm.value.code || null,
        name: cabinetForm.value.name,
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

async function removeCabinet(cabinet) {
    if (!confirm(`Xóa tủ "${cabinet.name}"?`)) return;
    try {
        await storageCabinetsApi.remove(cabinet.id);
        toast.success('Đã xóa tủ lưu trữ.');
        if (String(selectedCabinetId.value) === String(cabinet.id)) {
            selectedCabinetId.value = null;
        }
        await loadCabinets();
    } catch {
        toast.error('Không thể xóa tủ lưu trữ.');
    }
}

function confirmDeleteOne(cabinet) {
    cabinetToDelete.value = cabinet;
    showDeleteModal.value = true;
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

async function saveSlot() {
    if (!selectedCabinet.value) {
        toast.error('Vui lòng chọn tủ trước khi quản lý ngăn.');
        return;
    }
    try {
        await ensureDetailsLoaded();
    } catch {
        toast.error('Không thể tải danh sách phân loại chi tiết.');
        return;
    }
    const payload = {
        classification_detail_id: Number(slotForm.value.classification_detail_id),
        slot_code: slotForm.value.slot_code || null,
        slot_name: slotForm.value.slot_name,
        capacity: Number(slotForm.value.capacity || 30),
        current_quantity: Number(slotForm.value.current_quantity || 0),
        is_active: !!slotForm.value.is_active,
    };
    if (!payload.classification_detail_id || !payload.slot_name) {
        toast.error('Vui lòng nhập phân loại chi tiết và tên ngăn.');
        return;
    }
    savingSlot.value = true;
    try {
        if (slotForm.value.id) {
            await storageCabinetsApi.updateSlot(selectedCabinet.value.id, slotForm.value.id, payload);
            toast.success('Đã cập nhật ngăn lưu trữ.');
        } else {
            await storageCabinetsApi.createSlot(selectedCabinet.value.id, payload);
            toast.success('Đã tạo ngăn lưu trữ.');
        }
        resetSlotForm();
        await loadCabinets();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể lưu ngăn lưu trữ.');
    } finally {
        savingSlot.value = false;
    }
}

async function removeSlot(slot) {
    if (!selectedCabinet.value) return;
    if (!confirm(`Xóa ngăn "${slot.slot_name}"?`)) return;
    try {
        await storageCabinetsApi.removeSlot(selectedCabinet.value.id, slot.id);
        toast.success('Đã xóa ngăn lưu trữ.');
        await loadCabinets();
    } catch {
        toast.error('Không thể xóa ngăn lưu trữ.');
    }
}

function getSlotKey(cabinetId, slotId) {
    return `${cabinetId}:${slotId}`;
}

function toggleSlotSelect(cabinetId, slotId) {
    const key = getSlotKey(cabinetId, slotId);
    const idx = selectedSlotKeys.value.indexOf(key);
    if (idx >= 0) selectedSlotKeys.value.splice(idx, 1);
    else selectedSlotKeys.value.push(key);
}

function toggleAllSlots() {
    if (isAllSlotsSelected.value) {
        selectedSlotKeys.value = [];
        return;
    }
    selectedSlotKeys.value = slotManagementRows.value.map((row) => getSlotKey(row.cabinetId, row.slot.id));
}

async function deleteSelectedSlots() {
    if (!hasSlotSelection.value) return;
    if (!confirm(`Xóa nhanh ${selectedSlotKeys.value.length} ngăn đã chọn?`)) return;

    try {
        const payload = selectedSlotKeys.value.map((key) => {
            const [cabinetId, slotId] = key.split(':');
            return { cabinetId: Number(cabinetId), slotId: Number(slotId) };
        });
        await Promise.all(payload.map((item) => storageCabinetsApi.removeSlot(item.cabinetId, item.slotId)));
        toast.success(`Đã xóa ${payload.length} ngăn lưu trữ.`);
        selectedSlotKeys.value = [];
        await loadCabinets();
    } catch {
        toast.error('Không thể xóa nhanh các ngăn đã chọn.');
    }
}

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
}

function toggleAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
        return;
    }
    selectedIds.value = cabinets.value.map((c) => c.id);
}

function deselectAll() {
    selectedIds.value = [];
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
    await loadMasterData();
    await loadCabinets();
});

</script>

<template>
    <Head title="Tủ và ngăn lưu trữ - Admin" />
    <AdminLayout
        title="Tủ và ngăn lưu trữ"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Quản lý kho sách' }, { label: 'Tủ và ngăn lưu trữ' }]"
    >
        <div class="space-y-4">
            <AdminPageHeading title="Quản lý tủ sách và ngăn sách">
                <template #actions>
                    <Button variant="outline" size="sm" class="gap-1.5" @click="loadCabinets">
                        <Icon icon="lucide:refresh-cw" class="w-4 h-4" />
                        Tải lại
                    </Button>
                </template>
            </AdminPageHeading>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                add-label="Thêm tủ sách"
                :show-export="false"
                :show-import="false"
                :show-update-file="false"
                @add="resetCabinetForm"
                @delete-selected="confirmDeleteSelected"
                @deselect-all="deselectAll"
            />

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Tổng tủ lưu trữ</p>
                    <p class="text-2xl font-semibold text-blue-600">{{ overview.cabinetCount }}</p>
                </div>
                <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Tổng ngăn lưu trữ</p>
                    <p class="text-2xl font-semibold text-indigo-600">{{ overview.slotCount }}</p>
                </div>
                <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Tồn hiện tại</p>
                    <p class="text-2xl font-semibold text-emerald-600">{{ overview.quantityTotal }}</p>
                </div>
                <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Sức chứa tổng</p>
                    <p class="text-2xl font-semibold text-slate-700 dark:text-slate-200">{{ overview.capacityTotal }}</p>
                </div>
            </div>

            <AdminFilterSearch
                v-model="filters.keyword"
                search-placeholder="Tìm theo tên/mã tủ, kho, phân loại..."
                :show-filter-button="false"
                @search="searchCabinets"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <select v-model="filters.warehouse_id" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Tất cả kho</option>
                            <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">{{ w.code }} - {{ w.name }}</option>
                        </select>
                        <select v-model="filters.classification_id" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Tất cả phân loại</option>
                            <option v-for="c in classifications" :key="c.id" :value="String(c.id)">{{ c.code }} - {{ c.name }}</option>
                        </select>
                        <select v-model="filters.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="active">Đang hoạt động</option>
                            <option value="inactive">Ngưng hoạt động</option>
                            <option value="empty">Tủ trống</option>
                            <option value="full">Tủ đầy</option>
                        </select>
                        <select v-model="filters.sort" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Sắp xếp</option>
                            <option value="name_asc">Tên A → Z</option>
                            <option value="name_desc">Tên Z → A</option>
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                            <option value="stock_desc">Tồn giảm dần</option>
                            <option value="stock_asc">Tồn tăng dần</option>
                            <option value="usage_desc">% lấp đầy giảm dần</option>
                            <option value="usage_asc">% lấp đầy tăng dần</option>
                        </select>
                        <Button variant="outline" size="sm" @click="resetFilters(); searchCabinets();">Làm mới</Button>
                    </div>
                </template>
            </AdminFilterSearch>

            <div class="rounded-xl border bg-white dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
                <div class="px-4 py-3 border-b dark:border-slate-800 flex items-center justify-between gap-3">
                    <p class="font-semibold">Bảng quản lý tủ/ngăn</p>
                    <Button variant="destructive" size="sm" :disabled="!hasSlotSelection" @click="deleteSelectedSlots">
                        Xóa nhanh ngăn đã chọn ({{ selectedSlotKeys.length }})
                    </Button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1400px] text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b dark:border-slate-700">
                            <tr>
                                <th class="p-3 text-left w-12">
                                    <input
                                        type="checkbox"
                                        class="admin-table-checkbox"
                                        :checked="isAllSlotsSelected"
                                        @change="toggleAllSlots"
                                    />
                                </th>
                                <th class="p-3 text-left">Tủ</th>
                                <th class="p-3 text-left">Tên ngăn</th>
                                <th class="p-3 text-left">Kho</th>
                                <th class="p-3 text-left">Phân loại</th>
                                <th class="p-3 text-left">Số lượng</th>
                                <th class="p-3 text-left">Trạng thái</th>
                                <th class="p-3 text-left">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in slotManagementRows" :key="row.slot.id" class="border-b dark:border-slate-800">
                                <td class="p-3">
                                    <input
                                        type="checkbox"
                                        class="admin-table-checkbox"
                                        :checked="selectedSlotKeys.includes(getSlotKey(row.cabinetId, row.slot.id))"
                                        @change="toggleSlotSelect(row.cabinetId, row.slot.id)"
                                    />
                                </td>
                                <td class="p-3">{{ row.cabinetLabel }}</td>
                                <td class="p-3">
                                    {{ row.slot.slot_code ? `${row.slot.slot_code} - ` : '' }}{{ row.slot.slot_name }}
                                </td>
                                <td class="p-3">{{ row.warehouseLabel }}</td>
                                <td class="p-3">{{ row.classificationLabel }}</td>
                                <td class="p-3 font-medium">
                                    {{ row.slot.current_quantity }}/{{ row.slot.capacity }}
                                </td>
                                <td class="p-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        :class="row.slot.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'"
                                    >
                                        {{ row.slot.is_active ? 'Hoạt động' : 'Ngưng hoạt động' }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-1.5">
                                        <AdminTableActionIcon icon="lucide:eye" title="Xem tủ/ngăn" @click="openViewCabinet(getCabinetById(row.cabinetId))" />
                                        <AdminTableActionIcon icon="lucide:pencil" title="Sửa ngăn" tone="slate" @click="selectCabinet(getCabinetById(row.cabinetId)); editSlot(row.slot)" />
                                        <AdminTableActionIcon icon="lucide:trash-2" title="Xóa ngăn" tone="rose" @click="selectCabinet(getCabinetById(row.cabinetId)); removeSlot(row.slot)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!loading && slotManagementRows.length === 0">
                                <td colspan="8" class="p-4 text-center text-slate-500">Không có dữ liệu tủ/ngăn.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <div class="xl:col-span-5 space-y-4">
                    <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                        <p class="font-semibold mb-3">{{ cabinetForm.id ? 'Sửa tủ lưu trữ' : 'Tạo tủ lưu trữ' }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <select v-model="cabinetForm.warehouse_id" class="admin-filter-select">
                                <option value="">Chọn kho</option>
                                <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">{{ w.code }} - {{ w.name }}</option>
                            </select>
                            <select v-model="cabinetForm.classification_id" class="admin-filter-select">
                                <option value="">Chọn phân loại</option>
                                <option v-for="c in classifications" :key="c.id" :value="String(c.id)">{{ c.code }} - {{ c.name }}</option>
                            </select>
                            <input v-model="cabinetForm.code" class="admin-filter-select" placeholder="Mã tủ (VD: TU-01)" />
                            <input v-model="cabinetForm.name" class="admin-filter-select" placeholder="Tên tủ" />
                        </div>
                        <div class="flex items-center gap-2 mt-3">
                            <Button :disabled="savingCabinet" @click="saveCabinet">{{ savingCabinet ? 'Đang lưu...' : 'Lưu tủ' }}</Button>
                            <Button variant="outline" @click="resetCabinetForm">Hủy</Button>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-white dark:bg-slate-900 dark:border-slate-800 overflow-hidden">
                        <div class="px-4 py-3 border-b dark:border-slate-800 flex items-center justify-between">
                            <p class="font-semibold">Danh sách tủ</p>
                            <span class="text-xs text-slate-500">{{ cabinetsPagination.total }} tủ</span>
                        </div>
                        <div class="max-h-[560px] overflow-auto">
                            <div
                                v-for="cabinet in cabinetsWithRate"
                                :key="cabinet.id"
                                class="w-full text-left px-4 py-3 border-b last:border-0 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40"
                                :class="String(selectedCabinetId) === String(cabinet.id) ? 'bg-blue-50 dark:bg-blue-900/20' : ''"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <span class="admin-table-checkbox-wrap mt-0.5">
                                            <input
                                                type="checkbox"
                                                :checked="selectedIds.includes(cabinet.id)"
                                                class="admin-table-checkbox"
                                                @change="toggleSelect(cabinet.id)"
                                            />
                                        </span>
                                        <div>
                                        <p class="font-medium text-sm">{{ cabinet.code ? `${cabinet.code} - ` : '' }}{{ cabinet.name }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ cabinet.warehouse?.code }} - {{ cabinet.warehouse?.name }}</p>
                                        <p class="text-xs text-slate-500">{{ cabinet.classification?.code }} - {{ cabinet.classification?.name }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-800">
                                        {{ cabinet.current_quantity }}/{{ cabinet.capacity_total }}
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-2 bg-blue-500 rounded-full" :style="{ width: `${cabinet.usageRate}%` }" />
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">Lấp đầy: {{ cabinet.usageRate }}% - {{ cabinet.slots?.length || 0 }} ngăn</p>
                                </div>
                                <div class="flex gap-1.5 mt-2">
                                    <AdminTableActionIcon icon="lucide:eye" title="Xem chi tiết" @click="openViewCabinet(cabinet)" />
                                    <AdminTableActionIcon icon="lucide:pencil" title="Sửa" tone="slate" @click="editCabinet(cabinet)" />
                                    <AdminTableActionIcon icon="lucide:trash-2" title="Xóa" tone="rose" @click="confirmDeleteOne(cabinet)" />
                                </div>
                            </div>
                            <p v-if="!loading && cabinetsWithRate.length === 0" class="p-4 text-sm text-slate-500">Không có tủ lưu trữ.</p>
                            <p v-if="loading" class="p-4 text-sm text-slate-500">Đang tải...</p>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-7 space-y-4">
                    <div v-if="selectedCabinet" class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <div>
                                <p class="font-semibold">Chi tiết tủ: {{ selectedCabinet.name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ selectedCabinet.classification?.code }} - {{ selectedCabinet.classification?.name }}
                                </p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                Lấp đầy {{ selectedCabinetUsageRate }}%
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-4">
                            <select v-model="slotForm.classification_detail_id" class="admin-filter-select md:col-span-2">
                                <option value="">Phân loại chi tiết</option>
                                <option v-for="d in detailOptions" :key="d.id" :value="String(d.id)">{{ d.code }} - {{ d.name }}</option>
                            </select>
                            <input v-model="slotForm.slot_code" class="admin-filter-select" placeholder="Mã ngăn" />
                            <input v-model="slotForm.slot_name" class="admin-filter-select md:col-span-2" placeholder="Tên ngăn" />
                            <input v-model.number="slotForm.capacity" type="number" min="1" class="admin-filter-select" placeholder="Sức chứa" />
                            <input v-model.number="slotForm.current_quantity" type="number" min="0" class="admin-filter-select" placeholder="Tồn hiện tại" />
                            <div class="flex gap-2 md:col-span-6">
                                <Button :disabled="savingSlot" @click="saveSlot">{{ savingSlot ? 'Đang lưu...' : 'Lưu ngăn' }}</Button>
                                <Button variant="outline" @click="resetSlotForm">Hủy</Button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg border dark:border-slate-800">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/60">
                                    <tr class="text-left border-b dark:border-slate-700">
                                        <th class="py-2 px-3">Ngăn</th>
                                        <th class="py-2 px-3">Phân loại chi tiết</th>
                                        <th class="py-2 px-3">Sức chứa/Tồn</th>
                                        <th class="py-2 px-3">Lấp đầy</th>
                                        <th class="py-2 px-3">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="slot in selectedCabinet.slots || []" :key="slot.id" class="border-b dark:border-slate-800">
                                        <td class="py-2 px-3">{{ slot.slot_code ? `${slot.slot_code} - ` : '' }}{{ slot.slot_name }}</td>
                                        <td class="py-2 px-3">{{ slot.classification_detail?.code }} - {{ slot.classification_detail?.name }}</td>
                                        <td class="py-2 px-3">{{ slot.current_quantity }}/{{ slot.capacity }}</td>
                                        <td class="py-2 px-3">
                                            {{ slot.capacity > 0 ? Math.round((slot.current_quantity / slot.capacity) * 100) : 0 }}%
                                        </td>
                                        <td class="py-2 px-3 flex gap-2">
                                            <Button variant="outline" size="sm" @click="editSlot(slot)">Sửa</Button>
                                            <Button variant="destructive" size="sm" @click="removeSlot(slot)">Xóa</Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="rounded-xl border p-8 bg-white dark:bg-slate-900 dark:border-slate-800 text-center text-slate-500">
                        Chọn một tủ ở danh sách bên trái để xem và quản lý các ngăn.
                    </div>
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
        </div>
    </AdminLayout>
</template>
