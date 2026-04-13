import { computed, onMounted, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from '../../../../vendor/tightenco/ziggy/dist/index.js';
import { loansApi } from '@/api/loans';
import { toast } from '@/store/toast';
import { extractApiPaginator } from '@/utils/adminPagination';

const LOANS_PER_PAGE = 20;
const LOANS_SEARCH_IN_KEYS = ['loan_code', 'card_number', 'reader_name', 'created_by_name'];

export const LOANS_SEARCH_IN_OPTIONS = [
    { key: 'loan_code', label: 'Mã phiếu' },
    { key: 'card_number', label: 'Mã thẻ thư viện' },
    { key: 'reader_name', label: 'Độc giả' },
    { key: 'created_by_name', label: 'Người tạo' },
];

export function useLoansAdminPage() {
    const loading = ref(false);
    const rows = ref([]);
    const selectedIds = ref([]);
    /** Trạng thái phiếu theo id (khi chọn từ bảng), dùng khi chọn qua nhiều trang phân trang. */
    const loanStatusById = reactive({});
    const loansPageNum = ref(1);
    const loansListMeta = ref({
        current_page: 1,
        last_page: 1,
        per_page: LOANS_PER_PAGE,
        total: 0,
    });

    const filterValues = ref({
        searchKeyword: '',
        searchIn: {
            loan_code: true,
            card_number: true,
            reader_name: true,
            created_by_name: true,
        },
        status: '',
        sort: '',
    });

    const showFilterPanel = ref(false);
    let loansSearchDebounce = null;

    const showBulkDeleteModal = ref(false);
    const bulkDeleteLoading = ref(false);
    const showSingleDeleteModal = ref(false);
    const singleDeleteLoading = ref(false);
    const deletingLoan = ref(null);
    const showBulkReturnModal = ref(false);
    const bulkReturnLoading = ref(false);

    function clearLoanStatusMap() {
        Object.keys(loanStatusById).forEach((k) => delete loanStatusById[k]);
    }

    function syncLoanStatusForSelectedRows() {
        rows.value.forEach((r) => {
            if (selectedIds.value.includes(r.id)) {
                loanStatusById[r.id] = r.status;
            }
        });
    }

    function buildSearchInParam() {
        const sin = filterValues.value.searchIn || {};
        const active = LOANS_SEARCH_IN_KEYS.filter((k) => !!sin[k]);
        if (active.length === 0 || active.length === LOANS_SEARCH_IN_KEYS.length) {
            return undefined;
        }
        return active.join(',');
    }

    const loansPagination = computed(() => ({
        current_page: loansListMeta.value.current_page,
        last_page: loansListMeta.value.last_page,
    }));

    async function loadLoans(resetPage = false) {
        if (resetPage) {
            loansPageNum.value = 1;
            selectedIds.value = [];
            clearLoanStatusMap();
        }
        loading.value = true;
        try {
            const kw = filterValues.value.searchKeyword?.trim() || '';
            const res = await loansApi.list({
                search: kw || undefined,
                search_in: buildSearchInParam(),
                status: filterValues.value.status || undefined,
                sort: filterValues.value.sort || undefined,
                page: loansPageNum.value,
                per_page: LOANS_PER_PAGE,
            });
            const { items, meta } = extractApiPaginator(res, LOANS_PER_PAGE);
            rows.value = items;
            syncLoanStatusForSelectedRows();
            loansListMeta.value = {
                current_page: meta.current_page,
                last_page: meta.last_page,
                per_page: meta.per_page,
                total: meta.total,
            };
            loansPageNum.value = meta.current_page;
        } catch (e) {
            rows.value = [];
            loansListMeta.value = {
                current_page: 1,
                last_page: 1,
                per_page: LOANS_PER_PAGE,
                total: 0,
            };
            toast.error(e?.response?.data?.messages || 'Không tải được danh sách phiếu mượn.', { title: 'Lỗi' });
        } finally {
            loading.value = false;
        }
    }

    function goLoansPage(p) {
        const n = Number(p);
        if (!Number.isFinite(n) || n < 1 || n > loansListMeta.value.last_page) {
            return;
        }
        loansPageNum.value = n;
        loadLoans(false);
    }

    function resetFilters() {
        filterValues.value = {
            searchKeyword: '',
            searchIn: {
                loan_code: true,
                card_number: true,
                reader_name: true,
                created_by_name: true,
            },
            status: '',
            sort: '',
        };
        loadLoans(true);
    }

    watch(
        () => filterValues.value.searchKeyword,
        () => {
            if (loansSearchDebounce) {
                clearTimeout(loansSearchDebounce);
            }
            loansSearchDebounce = setTimeout(() => {
                loadLoans(true);
            }, 350);
        }
    );

    watch(
        () => filterValues.value.searchIn,
        () => {
            loadLoans(true);
        },
        { deep: true }
    );

    const hasSelection = computed(() => selectedIds.value.length > 0);

    const isAllSelected = computed(
        () => rows.value.length > 0 && rows.value.every((r) => selectedIds.value.includes(r.id)),
    );

    function toggleSelect(id) {
        const idx = selectedIds.value.indexOf(id);
        if (idx >= 0) {
            selectedIds.value = selectedIds.value.filter((x) => x !== id);
            delete loanStatusById[id];
        } else {
            selectedIds.value = [...selectedIds.value, id];
            const row = rows.value.find((r) => r.id === id);
            if (row) {
                loanStatusById[id] = row.status;
            }
        }
    }

    function toggleSelectAll() {
        const pageIds = rows.value.map((r) => r.id);
        const allOnPage = pageIds.length > 0 && pageIds.every((id) => selectedIds.value.includes(id));
        if (allOnPage) {
            selectedIds.value = selectedIds.value.filter((id) => !pageIds.includes(id));
            pageIds.forEach((id) => delete loanStatusById[id]);
        } else {
            selectedIds.value = [...new Set([...selectedIds.value, ...pageIds])];
            rows.value.forEach((r) => {
                if (pageIds.includes(r.id)) {
                    loanStatusById[r.id] = r.status;
                }
            });
        }
    }

    function deselectAll() {
        selectedIds.value = [];
        clearLoanStatusMap();
    }

    /** Phiếu đang mượn / quá hạn — dùng cho trả hàng loạt. */
    const openLoanIdsForBulk = computed(() =>
        selectedIds.value.filter((id) => ['da_muon', 'qua_han'].includes(loanStatusById[id])),
    );

    /** Mọi trạng thái có thể xóa (gồm đã trả). */
    const bulkDeletableLoanIds = computed(() =>
        selectedIds.value.filter((id) => ['da_muon', 'qua_han', 'da_tra'].includes(loanStatusById[id])),
    );

    const skippedNonOpenBulkCount = computed(() => selectedIds.value.length - openLoanIdsForBulk.value.length);

    function openBulkDelete() {
        if (bulkDeletableLoanIds.value.length === 0) {
            toast.warn('Không có phiếu hợp lệ để xóa trong lựa chọn.', { title: 'Xóa phiếu' });
            return;
        }
        showBulkDeleteModal.value = true;
    }

    async function confirmBulkDelete() {
        const ids = [...bulkDeletableLoanIds.value];
        bulkDeleteLoading.value = true;
        try {
            await loansApi.bulkDelete({ ids });
            toast.success(`Đã chuyển ${ids.length} phiếu vào thùng rác.`, { title: 'Thành công' });
            showBulkDeleteModal.value = false;
            deselectAll();
            await loadLoans(false);
            if (showTrashDrawer.value) {
                await fetchTrashLoans();
            }
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không xóa được các phiếu đã chọn.', { title: 'Lỗi' });
        } finally {
            bulkDeleteLoading.value = false;
        }
    }

    function openBulkReturn() {
        if (openLoanIdsForBulk.value.length === 0) {
            toast.warn('Chỉ có thể trả phiếu đang mượn hoặc quá hạn.', { title: 'Trả sách' });
            return;
        }
        showBulkReturnModal.value = true;
    }

    async function confirmBulkReturn(payload) {
        const loanIds = [...openLoanIdsForBulk.value];
        bulkReturnLoading.value = true;
        try {
            await loansApi.bulkReturn({
                loan_ids: loanIds,
                return_date: payload.return_date,
                condition_on_return: payload.condition_on_return,
            });
            toast.success(`Đã trả ${loanIds.length} phiếu.`, { title: 'Thành công' });
            showBulkReturnModal.value = false;
            deselectAll();
            await loadLoans(false);
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không xử lý trả hàng loạt được.', { title: 'Lỗi' });
        } finally {
            bulkReturnLoading.value = false;
        }
    }

    const emptyText = computed(() => (loading.value ? 'Đang tải dữ liệu...' : 'Chưa có phiếu mượn nào.'));

    const trashedLoans = ref([]);
    const showTrashDrawer = ref(false);
    const loadingTrash = ref(false);

    async function fetchTrashLoans() {
        loadingTrash.value = true;
        try {
            const payload = await loansApi.trash({ per_page: 100 });
            const { items } = extractApiPaginator(payload, 100);
            trashedLoans.value = items;
        } catch (e) {
            trashedLoans.value = [];
            toast.error(e?.response?.data?.messages || 'Không tải được thùng rác.', { title: 'Thùng rác' });
        } finally {
            loadingTrash.value = false;
        }
    }

    watch(showTrashDrawer, (open) => {
        if (open) {
            fetchTrashLoans();
        }
    });

    async function restoreLoan(id) {
        try {
            await loansApi.restore(id);
            await loadLoans(false);
            await fetchTrashLoans();
            toast.success('Đã khôi phục phiếu mượn.', { title: 'Thùng rác' });
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không khôi phục được.', { title: 'Thùng rác' });
        }
    }

    async function restoreManyLoans(ids) {
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!window.confirm(`Khôi phục ${ids.length} phiếu?`)) return;
        try {
            await loansApi.restoreMany(ids);
            await loadLoans(false);
            await fetchTrashLoans();
            toast.success(`Đã khôi phục ${ids.length} phiếu.`, { title: 'Thùng rác' });
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không khôi phục được các phiếu đã chọn.', { title: 'Thùng rác' });
        }
    }

    async function forceDeleteLoan(id) {
        if (!window.confirm('Xóa vĩnh viễn phiếu này? Không thể khôi phục.')) return;
        try {
            await loansApi.forceDelete(id);
            trashedLoans.value = (trashedLoans.value || []).filter((x) => x.id !== id);
            await loadLoans(false);
            await fetchTrashLoans();
            toast.success('Đã xóa vĩnh viễn.', { title: 'Thùng rác' });
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không xóa vĩnh viễn được.', { title: 'Thùng rác' });
        }
    }

    async function forceDeleteManyLoans(ids) {
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!window.confirm(`Xóa vĩnh viễn ${ids.length} phiếu? Không thể khôi phục.`)) return;
        try {
            await loansApi.forceDeleteMany(ids);
            const set = new Set(ids);
            trashedLoans.value = (trashedLoans.value || []).filter((x) => !set.has(x.id));
            await loadLoans(false);
            await fetchTrashLoans();
            toast.success(`Đã xóa vĩnh viễn ${ids.length} phiếu.`, { title: 'Thùng rác' });
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không xóa vĩnh viễn được.', { title: 'Thùng rác' });
        }
    }

    function goCreate() {
        router.visit(route('admin.loans.create'));
    }
    function goShow(id) {
        router.visit(route('admin.loans.show', id));
    }
    function goEdit(id) {
        router.visit(route('admin.loans.edit', id));
    }
    function goReturn(id) {
        router.visit(route('admin.loans.return', id));
    }

    function removeLoan(id) {
        const row = rows.value.find((x) => x.id === id) || null;
        deletingLoan.value = row
            ? { id: row.id, code: row.loan_code || `#${row.id}` }
            : { id };
        showSingleDeleteModal.value = true;
    }

    function closeSingleDeleteModal() {
        showSingleDeleteModal.value = false;
        deletingLoan.value = null;
    }

    async function confirmSingleDelete() {
        const id = deletingLoan.value?.id;
        if (!id) {
            closeSingleDeleteModal();
            return;
        }
        singleDeleteLoading.value = true;
        try {
            await loansApi.remove(id);
            toast.success('Đã xóa phiếu mượn.', { title: 'Thành công' });
            selectedIds.value = selectedIds.value.filter((x) => x !== id);
            delete loanStatusById[id];
            closeSingleDeleteModal();
            await loadLoans(false);
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không xóa được phiếu mượn.', { title: 'Lỗi' });
        } finally {
            singleDeleteLoading.value = false;
        }
    }

    function buildExportParams() {
        const kw = filterValues.value.searchKeyword?.trim() || '';
        return {
            search: kw || undefined,
            search_in: buildSearchInParam(),
            status: filterValues.value.status || undefined,
            sort: filterValues.value.sort || undefined,
        };
    }

    async function exportExcel() {
        const bySelection = selectedIds.value.length > 0;
        const params = bySelection ? { ids: [...selectedIds.value] } : buildExportParams();
        try {
            const response = await loansApi.export(params);
            const blob = new Blob([response.data], {
                type:
                    response.headers?.['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = bySelection ? 'phieu_muon_da_chon.xlsx' : 'danh_sach_phieu_muon.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            if (bySelection) {
                toast.success(`Đã xuất ${selectedIds.value.length} phiếu đã chọn.`, { title: 'Xuất Excel' });
            } else {
                toast.success('Đã xuất Excel theo bộ lọc hiện tại.', { title: 'Xuất Excel' });
            }
        } catch (e) {
            toast.error('Không thể xuất Excel.', { title: 'Xuất Excel' });
        }
    }

    onMounted(() => loadLoans(false));

    return {
        loading,
        rows,
        selectedIds,
        hasSelection,
        isAllSelected,
        toggleSelect,
        toggleSelectAll,
        deselectAll,
        openLoanIdsForBulk,
        bulkDeletableLoanIds,
        skippedNonOpenBulkCount,
        showBulkDeleteModal,
        bulkDeleteLoading,
        showSingleDeleteModal,
        singleDeleteLoading,
        deletingLoan,
        showBulkReturnModal,
        bulkReturnLoading,
        openBulkDelete,
        confirmBulkDelete,
        closeSingleDeleteModal,
        confirmSingleDelete,
        openBulkReturn,
        confirmBulkReturn,
        filterValues,
        showFilterPanel,
        loansPagination,
        loadLoans,
        goLoansPage,
        resetFilters,
        emptyText,
        goCreate,
        goShow,
        goEdit,
        goReturn,
        removeLoan,
        exportExcel,
        trashedLoans,
        showTrashDrawer,
        loadingTrash,
        fetchTrashLoans,
        restoreLoan,
        restoreManyLoans,
        forceDeleteLoan,
        forceDeleteManyLoans,
    };
}
