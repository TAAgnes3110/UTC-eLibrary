import { computed, onMounted, ref } from 'vue';
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
        sort_due_date: 'asc',
    });

    const showFilterPanel = ref(false);

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
        }
        loading.value = true;
        try {
            const kw = filterValues.value.searchKeyword?.trim() || '';
            const res = await loansApi.list({
                search: kw || undefined,
                search_in: buildSearchInParam(),
                status: filterValues.value.status || undefined,
                sort_due_date: filterValues.value.sort_due_date || undefined,
                page: loansPageNum.value,
                per_page: LOANS_PER_PAGE,
            });
            const { items, meta } = extractApiPaginator(res, LOANS_PER_PAGE);
            rows.value = items;
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
            sort_due_date: 'asc',
        };
        loadLoans(true);
    }

    const emptyText = computed(() => (loading.value ? 'Đang tải dữ liệu...' : 'Chưa có phiếu mượn nào.'));

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

    async function removeLoan(id) {
        if (!window.confirm('Xóa phiếu mượn này?')) return;
        try {
            await loansApi.remove(id);
            toast.success('Đã xóa phiếu mượn.', { title: 'Thành công' });
            await loadLoans(false);
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không xóa được phiếu mượn.', { title: 'Lỗi' });
        }
    }

    async function exportExcel() {
        try {
            const kw = filterValues.value.searchKeyword?.trim() || '';
            const response = await loansApi.export({
                search: kw || undefined,
                search_in: buildSearchInParam(),
                status: filterValues.value.status || undefined,
                sort_due_date: filterValues.value.sort_due_date || undefined,
            });
            const blob = new Blob([response.data], {
                type:
                    response.headers?.['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'danh_sach_phieu_muon.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            toast.success('Đã xuất Excel.', { title: 'Xuất Excel' });
        } catch (e) {
            toast.error('Không thể xuất Excel.', { title: 'Xuất Excel' });
        }
    }

    onMounted(() => loadLoans(false));

    return {
        loading,
        rows,
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
    };
}
