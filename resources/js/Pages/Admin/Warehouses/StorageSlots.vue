<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';
import { Button } from '@/Components/ui/button';
import { warehousesApi } from '@/api/warehouses';
import { classificationDetailsApi } from '@/api/classificationDetails';
import { storageCabinetsApi } from '@/api/storageCabinets';
import { toast } from '@/store/toast';
import { extractApiPaginator } from '@/utils/adminPagination';

const loading = ref(false);
const savingSlot = ref(false);
const warehouses = ref([]);
const details = ref([]);
const detailsLoaded = ref(false);
const cabinets = ref([]);
const slots = ref([]);
const selectedSlotIds = ref([]);
const pageNum = ref(1);
const cabinetsPagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });

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
const STORAGE_SLOT_SEARCH_IN_OPTIONS = [
    { key: 'code', label: 'Mã tủ' },
    { key: 'name', label: 'Tên tủ' },
    { key: 'warehouse', label: 'Kho' },
    { key: 'classification', label: 'Phân loại' },
];

const slotForm = ref({
    cabinet_id: '',
    id: null,
    classification_detail_id: '',
    slot_name: '',
    is_active: true,
});
const showSlotForm = ref(false);
const showDetailModal = ref(false);
const detailRow = ref(null);

const detailOptions = computed(() => {
    const cabinet = cabinets.value.find((c) => String(c.id) === String(slotForm.value.cabinet_id));
    if (!cabinet?.classification_id) return [];
    return details.value.filter((d) => String(d.classification_id) === String(cabinet.classification_id));
});

const slotRows = computed(() => {
    return slots.value.map((slot) => ({
        id: slot.id,
        cabinetId: slot.storage_cabinet_id,
        cabinetLabel: slot.cabinet?.name || '—',
        warehouseLabel: slot.cabinet?.warehouse?.name || '—',
        slot,
    }));
});
const hasSelection = computed(() => selectedSlotIds.value.length > 0);
const isAllSelected = computed(
    () => slotRows.value.length > 0 && selectedSlotIds.value.length === slotRows.value.length
);

function resetSlotForm() {
    slotForm.value = {
        cabinet_id: '',
        id: null,
        classification_detail_id: '',
        slot_name: '',
        is_active: true,
    };
    showSlotForm.value = false;
}

function openDetail(row) {
    detailRow.value = row;
    showDetailModal.value = true;
}

function editSlot(row) {
    if (!detailsLoaded.value) {
        ensureDetailsLoaded().catch(() => {
            toast.error('Không thể tải danh sách phân loại chi tiết.');
        });
    }
    slotForm.value = {
        cabinet_id: String(row.cabinetId),
        id: row.slot.id,
        classification_detail_id: String(row.slot.classification_detail_id || ''),
        slot_name: row.slot.slot_name || '',
        is_active: !!row.slot.is_active,
    };
    showSlotForm.value = true;
}

async function openAddSlotForm() {
    try {
        await ensureDetailsLoaded();
    } catch {
        toast.error('Không thể tải danh sách phân loại chi tiết.');
        return;
    }
    slotForm.value = {
        cabinet_id: '',
        id: null,
        classification_detail_id: '',
        slot_name: '',
        is_active: true,
    };
    showSlotForm.value = true;
}

async function loadMasterData() {
    const [wh, cabinetPayload] = await Promise.all([
        warehousesApi.list({ per_page: 200 }),
        storageCabinetsApi.list({ per_page: 500, with_slots: false }),
    ]);
    warehouses.value = extractApiPaginator(wh, 200).items;
    cabinets.value = extractApiPaginator(cabinetPayload, 500).items;
}

async function ensureDetailsLoaded() {
    if (detailsLoaded.value) return;
    const det = await classificationDetailsApi.list({ per_page: 1000 });
    details.value = extractApiPaginator(det, 1000).items;
    detailsLoaded.value = true;
}

async function loadCabinets() {
    loading.value = true;
    try {
        const payload = await storageCabinetsApi.listSlots({
            page: pageNum.value,
            keyword: filters.value.keyword || undefined,
            warehouse_id: filters.value.warehouse_id || undefined,
            sort: filters.value.sort || undefined,
            search_in: buildSearchInParam(),
            per_page: 20,
        });
        const { items: rows, meta } = extractApiPaginator(payload, 20);
        slots.value = rows;
        cabinetsPagination.value = {
            current_page: Number(meta.current_page || pageNum.value),
            last_page: Number(meta.last_page || 1),
            per_page: Number(meta.per_page || 20),
            total: Number(meta.total || rows.length),
        };
        pageNum.value = cabinetsPagination.value.current_page;
        selectedSlotIds.value = selectedSlotIds.value.filter((id) =>
            slotRows.value.some((row) => Number(row.id) === Number(id))
        );
    } catch {
        toast.error('Không thể tải danh sách ngăn lưu trữ.');
    } finally {
        loading.value = false;
    }
}

function buildSearchInParam() {
    const searchIn = filters.value.searchIn || {};
    const keys = STORAGE_SLOT_SEARCH_IN_OPTIONS.map((opt) => opt.key);
    const active = keys.filter((k) => !!searchIn[k]);
    if (active.length === 0 || active.length === keys.length) return undefined;
    return active.join(',');
}

async function saveSlot() {
    const cabinetId = Number(slotForm.value.cabinet_id);
    if (!cabinetId) {
        toast.error('Vui lòng chọn tủ sách.');
        return;
    }
    const payload = {
        classification_detail_id: Number(slotForm.value.classification_detail_id),
        slot_name: String(slotForm.value.slot_name || '').trim(),
        is_active: !!slotForm.value.is_active,
    };
    if (!payload.classification_detail_id) {
        toast.error('Vui lòng chọn phân loại chi tiết.');
        return;
    }
    if (!payload.slot_name) {
        toast.error('Vui lòng nhập tên ngăn.');
        return;
    }

    savingSlot.value = true;
    try {
        if (slotForm.value.id) {
            await storageCabinetsApi.updateSlot(cabinetId, slotForm.value.id, payload);
            toast.success('Đã cập nhật ngăn lưu trữ.');
        } else {
            await storageCabinetsApi.createSlot(cabinetId, payload);
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

async function removeSlot(row) {
    if (!confirm(`Xóa ngăn "${row.slot.slot_name}"?`)) return;
    try {
        await storageCabinetsApi.removeSlot(row.cabinetId, row.slot.id);
        toast.success('Đã xóa ngăn lưu trữ.');
        await loadCabinets();
    } catch {
        toast.error('Không thể xóa ngăn lưu trữ.');
    }
}

function toggleSelect(id) {
    const idx = selectedSlotIds.value.indexOf(id);
    if (idx >= 0) selectedSlotIds.value.splice(idx, 1);
    else selectedSlotIds.value.push(id);
}

function toggleAll() {
    if (isAllSelected.value) {
        selectedSlotIds.value = [];
        return;
    }
    selectedSlotIds.value = slotRows.value.map((row) => row.id);
}

function deselectAll() {
    selectedSlotIds.value = [];
}

async function deleteSelectedSlots() {
    if (!hasSelection.value) return;
    if (!confirm(`Xóa nhanh ${selectedSlotIds.value.length} ngăn đã chọn?`)) return;

    try {
        const selectedRows = slotRows.value.filter((row) => selectedSlotIds.value.includes(row.id));
        await Promise.all(selectedRows.map((row) => storageCabinetsApi.removeSlot(row.cabinetId, row.slot.id)));
        toast.success(`Đã xóa ${selectedRows.length} ngăn lưu trữ.`);
        selectedSlotIds.value = [];
        await loadCabinets();
    } catch {
        toast.error('Không thể xóa nhanh các ngăn đã chọn.');
    }
}

function searchSlots() {
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
    <Head title="Quản lý ngăn sách - Admin" />
    <AdminLayout
        title="Quản lý ngăn sách"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Quản lý kho sách' }, { label: 'Quản lý ngăn sách' }]"
    >
        <div class="space-y-4">
            <AdminPageHeading title="Danh sách ngăn sách" />

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedSlotIds.length"
                add-label="Thêm ngăn sách"
                :show-export="false"
                :show-import="false"
                :show-update-file="false"
                :show-delete-selected="true"
                @add="openAddSlotForm"
                @delete-selected="deleteSelectedSlots"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filters.keyword"
                search-placeholder="Tìm theo tên ngăn, tủ, kho..."
                :show-filter-button="false"
                @search="searchSlots"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <AdminFilterPanel
                            :options="STORAGE_SLOT_SEARCH_IN_OPTIONS"
                            v-model:model-value="filters.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filters.warehouse_id" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Tất cả kho</option>
                            <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">{{ w.code }} - {{ w.name }}</option>
                        </select>
                        <select v-model="filters.sort" class="admin-filter-select admin-filter-select-centered">
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
                    <table class="w-full min-w-[980px] table-fixed text-sm">
                        <colgroup>
                            <col class="w-10" />
                            <col class="w-[18%]" />
                            <col class="w-[16%]" />
                            <col class="w-[14%]" />
                            <col class="w-[20%]" />
                            <col class="w-[10%]" />
                            <col class="w-[10%]" />
                            <col class="w-[12%]" />
                        </colgroup>
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
                                <th class="p-3 text-left">Tên tủ</th>
                                <th class="p-3 text-left">Tên ngăn</th>
                                <th class="p-3 text-left">Kho</th>
                                <th class="p-3 text-left">Phân loại</th>
                                <th class="p-3 text-center whitespace-nowrap">Số lượng</th>
                                <th class="p-3 text-center whitespace-nowrap">Trạng thái</th>
                                <th class="p-3 text-center whitespace-nowrap">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in slotRows" :key="row.id" class="border-b dark:border-slate-800">
                                <td class="p-3">
                                    <input
                                        type="checkbox"
                                        class="admin-table-checkbox"
                                        :checked="selectedSlotIds.includes(row.id)"
                                        @change="toggleSelect(row.id)"
                                    />
                                </td>
                                <td class="p-3">
                                    <div class="truncate" :title="row.cabinetLabel">{{ row.cabinetLabel }}</div>
                                </td>
                                <td class="p-3">
                                    <div class="truncate" :title="row.slot.slot_name">{{ row.slot.slot_name }}</div>
                                </td>
                                <td class="p-3">
                                    <div class="truncate" :title="row.warehouseLabel">{{ row.warehouseLabel }}</div>
                                </td>
                                <td class="p-3">
                                    <div class="truncate" :title="row.slot.classification_detail?.name || '—'">
                                        {{ row.slot.classification_detail?.name || '—' }}
                                    </div>
                                </td>
                                <td class="p-3 text-center font-semibold whitespace-nowrap">{{ row.slot.current_quantity }}/{{ row.slot.capacity }}</td>
                                <td class="p-3 text-center">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        :class="Number(row.slot.capacity || 0) > 0 && Number(row.slot.current_quantity || 0) >= Number(row.slot.capacity || 0)
                                            ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300'
                                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'"
                                    >
                                        {{ Number(row.slot.capacity || 0) > 0 && Number(row.slot.current_quantity || 0) >= Number(row.slot.capacity || 0) ? 'Đầy' : 'Còn trống' }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <AdminTableActionIcon icon="lucide:eye" title="Xem chi tiết" @click="openDetail(row)" />
                                        <AdminTableActionIcon icon="lucide:pencil" title="Sửa ngăn" tone="slate" @click="editSlot(row)" />
                                        <AdminTableActionIcon icon="lucide:trash-2" title="Xóa ngăn" tone="rose" @click="removeSlot(row)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!loading && slotRows.length === 0">
                                <td colspan="8" class="p-4 text-center text-slate-500">Không có dữ liệu ngăn sách.</td>
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

            <Teleport to="body">
                <div v-if="showDetailModal && detailRow" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/50" @click="showDetailModal = false" />
                    <div class="relative w-full max-w-2xl rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Chi tiết ngăn sách</h3>
                            <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="showDetailModal = false">
                                <Icon icon="lucide:x" class="h-5 w-5" />
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-6 text-sm md:grid-cols-2">
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Tủ</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ detailRow.cabinetLabel }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Tên ngăn</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ detailRow.slot.slot_name }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Kho</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ detailRow.warehouseLabel }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Phân loại chi tiết</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ detailRow.slot.classification_detail?.name || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Số lượng</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ detailRow.slot.current_quantity }}/{{ detailRow.slot.capacity }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Trạng thái</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ detailRow.slot.is_active ? 'Hoạt động' : 'Ngưng' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
                <div v-if="showSlotForm" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/50" @click="resetSlotForm" />
                    <div class="relative w-full max-w-3xl rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ slotForm.id ? 'Sửa ngăn lưu trữ' : 'Thêm ngăn lưu trữ' }}
                            </h3>
                            <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="resetSlotForm">
                                <Icon icon="lucide:x" class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-3 p-6 md:grid-cols-2">
                            <select v-model="slotForm.cabinet_id" class="admin-filter-select">
                                <option value="">Chọn tủ sách</option>
                                <option v-for="cabinet in cabinets" :key="cabinet.id" :value="String(cabinet.id)">
                                    {{ cabinet.name }}
                                </option>
                            </select>
                            <select v-model="slotForm.classification_detail_id" class="admin-filter-select">
                                <option value="">Phân loại chi tiết</option>
                                <option v-for="d in detailOptions" :key="d.id" :value="String(d.id)">{{ d.code }} - {{ d.name }}</option>
                            </select>
                            <input v-model="slotForm.slot_name" class="admin-filter-select md:col-span-2" placeholder="Tên ngăn" />
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                            <Button variant="outline" @click="resetSlotForm">Hủy</Button>
                            <Button class="!bg-blue-600 hover:!bg-blue-700 !text-white" :disabled="savingSlot" @click="saveSlot">
                                {{ savingSlot ? 'Đang lưu...' : slotForm.id ? 'Cập nhật' : 'Lưu ngăn' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AdminLayout>
</template>
