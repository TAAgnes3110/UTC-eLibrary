import { ref, computed, onMounted, watch } from 'vue';
import { warehousesApi } from '@/api/warehouses';
import { extractApiPaginator } from '@/utils/adminPagination';
import { toast } from '@/store/toast';
import { WAREHOUSE_FORM_FIELD_MAP, WAREHOUSE_ERROR_DISPLAY_KEYS } from '@/utils/laravelApiError';
import { useApiFieldErrors } from '@/composables/useApiFieldErrors';
import { toastShort } from '@/constants/adminUiMessages';

const WAREHOUSES_PER_PAGE = 50;

export const WAREHOUSES_SEARCH_IN_OPTIONS = [
    { key: 'code', label: 'Mã kho' },
    { key: 'name', label: 'Tên kho' },
];

export function useWarehousesAdminPage() {
    const warehousesData = ref({ data: [] });
    const warehousesPageNum = ref(1);
    const warehousesListMeta = ref({
        current_page: 1,
        last_page: 1,
        per_page: WAREHOUSES_PER_PAGE,
        total: 0,
    });
    const loading = ref(false);
    const showModal = ref(false);
    const showDeleteModal = ref(false);
    const showImportModal = ref(false);
    const importLoading = ref(false);
    const isEditing = ref(false);
    const selectedWarehouse = ref(null);
    const form = ref({ id: null, code: '', name: '', parent_id: null, is_active: true });

    const {
        fieldErrors: warehouseFormErrors,
        clearField: clearWarehouseFieldError,
        clearAll: clearWarehouseFormErrors,
        applyAxios422: applyWarehouseApiErrors,
    } = useApiFieldErrors(WAREHOUSE_FORM_FIELD_MAP, { displayKeys: WAREHOUSE_ERROR_DISPLAY_KEYS });

    const showTrashDrawer = ref(false);
    const trashedWarehouses = ref([]);
    const loadingTrash = ref(false);

    const filterValues = ref({
        searchKeyword: '',
        searchIn: { code: true, name: true },
        status: '',
    });

    const showFilterPanel = ref(false);

    const warehousesPagination = computed(() => ({
        current_page: warehousesListMeta.value.current_page,
        last_page: warehousesListMeta.value.last_page,
        per_page: warehousesListMeta.value.per_page,
        total: warehousesListMeta.value.total,
    }));

    const goWarehousesPage = (page) => {
        const p = Number(page);
        if (!Number.isFinite(p) || p < 1 || p > warehousesListMeta.value.last_page) {
            return;
        }
        warehousesPageNum.value = p;
        fetchWarehouses();
    };

    const fetchWarehouses = async () => {
        loading.value = true;
        try {
            const payload = await warehousesApi.list({
                keyword: filterValues.value.searchKeyword?.trim() || '',
                page: warehousesPageNum.value,
                per_page: WAREHOUSES_PER_PAGE,
            });
            const { items, meta } = extractApiPaginator(payload, WAREHOUSES_PER_PAGE);
            warehousesData.value = { data: items };
            warehousesListMeta.value = {
                current_page: meta.current_page,
                last_page: meta.last_page,
                per_page: meta.per_page,
                total: meta.total,
            };
            warehousesPageNum.value = meta.current_page;
        } catch (e) {
            console.error('Lỗi khi tải danh sách kho sách:', e);
            warehousesData.value = { data: [] };
            warehousesListMeta.value = {
                current_page: 1,
                last_page: 1,
                per_page: WAREHOUSES_PER_PAGE,
                total: 0,
            };
        }
        loading.value = false;
    };

    let warehousesSearchDebounce = null;
    watch(
        () => filterValues.value.searchKeyword,
        () => {
            if (warehousesSearchDebounce) clearTimeout(warehousesSearchDebounce);
            warehousesSearchDebounce = setTimeout(() => {
                warehousesPageNum.value = 1;
                fetchWarehouses();
            }, 350);
        },
    );

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

    const formatDateTime = (value) => {
        if (!value) return '—';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return '—';
        return d.toLocaleString('vi-VN');
    };

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

    const openImportModal = () => {
        showImportModal.value = true;
    };

    const exportExcel = async () => {
        try {
            const params = {};
            if (selectedIds.value.length > 0) {
                params.ids = selectedIds.value;
            } else if (
                filterValues.value.searchKeyword ||
                filterValues.value.status ||
                Object.values(filterValues.value.searchIn || {}).some(Boolean)
            ) {
                params.ids = filteredWarehouses.value.map((w) => w.id);
            }

            const response = await warehousesApi.export(params);
            const blob = new Blob([response.data], {
                type:
                    response.headers['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'Danh_sach_kho_sach.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            toast.success('Đã xuất Excel.', { title: 'Xuất Excel' });
        } catch (e) {
            console.error(e);
            toast.error('Không thể xuất Excel. Vui lòng thử lại sau.', { title: 'Xuất Excel' });
        }
    };

    const downloadTemplate = async () => {
        try {
            const response = await warehousesApi.downloadImportTemplate();
            const blob = new Blob([response.data], {
                type:
                    response.headers['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'Mau_nhap_kho_sach.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            toast.success('Đã tải file mẫu.', { title: 'File mẫu' });
        } catch (e) {
            console.error(e);
            toast.error('Không thể tải file mẫu. Vui lòng thử lại sau.', { title: 'File mẫu' });
        }
    };

    const importExcel = async (file) => {
        if (!file) return;
        importLoading.value = true;
        try {
            const formData = new FormData();
            formData.append('file', file);
            await warehousesApi.import(formData);
            await fetchWarehouses();
            toast.success('Nhập kho từ Excel thành công.', { title: 'Import Excel' });
        } catch (e) {
            console.error(e);
            toast.error('Không thể nhập kho từ Excel. Vui lòng kiểm tra file và thử lại.', { title: 'Import Excel' });
        } finally {
            importLoading.value = false;
        }
    };

    const openAddModal = () => {
        isEditing.value = false;
        form.value = { id: null, code: '', name: '', parent_id: null, is_active: true };
        clearWarehouseFormErrors();
        showModal.value = true;
    };

    const openEditModal = (w) => {
        isEditing.value = true;
        clearWarehouseFormErrors();
        selectedWarehouse.value = w;
        form.value = {
            id: w.id,
            code: w.code || '',
            name: w.name || '',
            parent_id: w.parent_id ?? null,
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
        clearWarehouseFormErrors();
        const code = String(form.value.code ?? '').trim();
        const name = String(form.value.name ?? '').trim();
        const payload = {
            code,
            name,
            is_active: !!form.value.is_active,
        };
        const pid = form.value.parent_id;
        if (pid != null && pid !== '' && Number.isFinite(Number(pid))) {
            payload.parent_id = Number(pid);
        }
        try {
            if (isEditing.value && form.value.id) {
                await warehousesApi.update(form.value.id, payload);
                toast.success(toastShort.ok);
            } else {
                await warehousesApi.create(payload);
                toast.success(toastShort.ok);
            }
            showModal.value = false;
            await fetchWarehouses();
        } catch (e) {
            console.error(e);
            applyWarehouseApiErrors(e);
            toast.error(toastShort.fail);
        }
    };

    const deleteWarehouse = async () => {
        try {
            if (selectedWarehouse.value) {
                await warehousesApi.remove(selectedWarehouse.value.id);
            } else if (selectedIds.value.length > 0) {
                for (const id of selectedIds.value) {
                    await warehousesApi.remove(id);
                }
            }
            await fetchWarehouses();
            if (showTrashDrawer.value) {
                await fetchTrash();
            }
        } catch (_) {
            await fetchWarehouses();
        }
        selectedWarehouse.value = null;
        selectedIds.value = [];
        showDeleteModal.value = false;
    };

    const openTrashDrawer = () => {
        showTrashDrawer.value = true;
        fetchTrash();
    };

    const fetchTrash = async () => {
        loadingTrash.value = true;
        try {
            const payload = await warehousesApi.trash();
            const data = payload?.data ?? payload;
            trashedWarehouses.value = Array.isArray(data) ? data : (data?.data ?? []);
        } catch (e) {
            trashedWarehouses.value = [];
            console.error('Lỗi khi tải thùng rác kho sách:', e);
        }
        loadingTrash.value = false;
    };

    const onRestoreWarehouse = async (id) => {
        try {
            await warehousesApi.restore(id);
            await fetchTrash();
            await fetchWarehouses();
            toast.success('Đã khôi phục.', { title: 'Thùng rác' });
        } catch (_) {}
    };

    const onRestoreManyWarehouses = async (ids) => {
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!confirm(`Khôi phục ${ids.length} mục?`)) return;
        try {
            if (typeof warehousesApi.restoreMany === 'function') {
                await warehousesApi.restoreMany(ids);
            } else {
                await Promise.all(ids.map((id) => warehousesApi.restore(id)));
            }
            await fetchTrash();
            await fetchWarehouses();
            toast.success(`Đã khôi phục ${ids.length} mục.`, { title: 'Thùng rác' });
        } catch (_) {}
    };

    const onForceDeleteWarehouse = async (id) => {
        if (!confirm('Xóa vĩnh viễn kho này? Hành động không thể hoàn tác.')) return;
        try {
            await warehousesApi.forceDelete(id);
            trashedWarehouses.value = (trashedWarehouses.value || []).filter((w) => w.id !== id);
            await fetchTrash();
            await fetchWarehouses();
            toast.success('Đã xóa vĩnh viễn.', { title: 'Thùng rác' });
        } catch (e) {
            console.error('Lỗi khi xóa vĩnh viễn kho:', e);
            toast.error('Không thể xóa vĩnh viễn kho. Vui lòng thử lại.', { title: 'Thùng rác' });
        }
    };

    const onForceDeleteManyWarehouses = async (ids) => {
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!confirm(`Xóa vĩnh viễn ${ids.length} mục? Hành động không thể hoàn tác.`)) return;
        try {
            if (typeof warehousesApi.forceDeleteMany === 'function') {
                await warehousesApi.forceDeleteMany(ids);
            } else {
                await Promise.all(ids.map((id) => warehousesApi.forceDelete(id)));
            }
            trashedWarehouses.value = (trashedWarehouses.value || []).filter((w) => !ids.includes(w.id));
            await fetchTrash();
            await fetchWarehouses();
            toast.success(`Đã xóa vĩnh viễn ${ids.length} mục.`, { title: 'Thùng rác' });
        } catch (e) {
            console.error('Lỗi khi xóa vĩnh viễn nhiều kho:', e);
            toast.error('Không thể xóa vĩnh viễn các kho đã chọn. Vui lòng thử lại.', { title: 'Thùng rác' });
        }
    };

    function statusLabel(isActive) {
        return isActive ? 'Hoạt động' : 'Không hoạt động';
    }
    function statusClass(isActive) {
        return isActive
            ? 'bg-emerald-500 dark:bg-emerald-600 text-white'
            : 'bg-slate-500 dark:bg-slate-600 text-white';
    }

    return {
        warehousesData,
        warehousesPagination,
        goWarehousesPage,
        loading,
        showModal,
        showDeleteModal,
        showImportModal,
        importLoading,
        isEditing,
        selectedWarehouse,
        form,
        warehouseFormErrors,
        clearWarehouseFieldError,
        showTrashDrawer,
        trashedWarehouses,
        loadingTrash,
        filterValues,
        showFilterPanel,
        filteredWarehouses,
        formatDateTime,
        selectedIds,
        hasSelection,
        isAllSelected,
        toggleSelect,
        toggleAll,
        deselectAll,
        openImportModal,
        exportExcel,
        downloadTemplate,
        importExcel,
        openAddModal,
        openEditModal,
        confirmDelete,
        confirmBulkDelete,
        saveWarehouse,
        deleteWarehouse,
        openTrashDrawer,
        onRestoreWarehouse,
        onRestoreManyWarehouses,
        onForceDeleteWarehouse,
        onForceDeleteManyWarehouses,
        statusLabel,
        statusClass,
    };
}
