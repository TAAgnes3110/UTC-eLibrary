<script setup>
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

// Props: backend có thể truyền loans, stats, filters. Không truyền thì dùng mock/empty.
const props = defineProps({
    loans: { type: Array, default: () => [
        { id: 1, reader_name: 'Lê Văn Tùng', reader_code: '2021601234', book_title: 'Giáo trình Cấu trúc dữ liệu', book_code: 'CNTT-0012', loan_date: '2024-02-15', due_date: '2024-03-01', status: 'active', display_status: 'active' },
        { id: 2, reader_name: 'Nguyễn Thị Mai', reader_code: '2022605678', book_title: 'Lập trình Java cho người mới', book_code: 'CNTT-0567', loan_date: '2024-02-10', due_date: '2024-02-24', status: 'active', display_status: 'overdue' },
        { id: 3, reader_name: 'Trần Minh Quân', reader_code: 'GV0012', book_title: 'Xác suất thống kê ứng dụng', book_code: 'TOAN-0001', loan_date: '2024-01-20', due_date: '2024-02-03', status: 'returned', display_status: 'returned', return_date: '2024-02-02' },
    ] },
    stats: { type: Object, default: () => null },
    filters: { type: Object, default: () => ({}) },
});

const SEARCH_IN_OPTIONS = [
    { key: 'reader_name', label: 'Tên bạn đọc' },
    { key: 'reader_code', label: 'Mã bạn đọc' },
    { key: 'book_title', label: 'Tên sách' },
    { key: 'book_code', label: 'Mã sách' },
];

const filterValues = ref({
    status: props.filters?.status ?? '',
    searchKeyword: props.filters?.q ?? '',
    searchIn: { reader_name: true, reader_code: true, book_title: true, book_code: true },
});
const showFilterPanel = ref(false);
const showGuide = ref(false);
const showCreateModal = ref(false);
const showReturnModal = ref(false);

// Form Cho mượn sách (chỉ frontend – backend bạn tự gọi)
const loanForm = ref({
    reader_code: '',
    barcode: '',
    condition_on_loan: 'good',
});
const loanFormErrors = ref({});

// Form Trả sách (chỉ frontend – backend bạn tự gọi)
const returnForm = ref({
    loan_id: '',
    barcode: '',
    condition_on_return: 'good',
});
const returnFormErrors = ref({});

watch(() => props.filters, (f) => {
    if (f?.q !== undefined) filterValues.value.searchKeyword = f.q ?? '';
    if (f?.status !== undefined) filterValues.value.status = f.status ?? '';
}, { deep: true });

const filtered = computed(() => {
    return props.loans.filter(l => {
        const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
        const sin = filterValues.value.searchIn || {};
        let matchesSearch = true;
        if (kw) {
            const anyChecked = Object.values(sin).some(Boolean);
            if (anyChecked) {
                const m = [];
                if (sin.reader_name) m.push((l.reader_name || '').toLowerCase().includes(kw));
                if (sin.reader_code) m.push((l.reader_code || '').toLowerCase().includes(kw));
                if (sin.book_title) m.push((l.book_title || '').toLowerCase().includes(kw));
                if (sin.book_code) m.push((l.book_code || '').toLowerCase().includes(kw));
                matchesSearch = m.some(Boolean);
            }
        }
        const matchesStatus = filterValues.value.status ? (l.display_status || l.status) === filterValues.value.status : true;
        return matchesSearch && matchesStatus;
    });
});

const statsComputed = computed(() => {
    if (props.stats) return props.stats;
    return {
        borrowed: props.loans.filter(l => (l.display_status || l.status) === 'active').length,
        overdue: props.loans.filter(l => (l.display_status || l.status) === 'overdue').length,
        returned: props.loans.filter(l => (l.display_status || l.status) === 'returned').length,
    };
});

const getStatusStyle = (status) => {
    const s = status || '';
    switch (s) {
        case 'active': return 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/40 dark:text-blue-400 dark:border-slate-800';
        case 'overdue': return 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800';
        case 'returned': return 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800';
        case 'lost': return 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800';
        default: return 'bg-slate-50 text-slate-700 border-slate-100 dark:bg-slate-800 dark:text-slate-400';
    }
};

const getStatusLabel = (status) => {
    const s = status || '';
    switch (s) {
        case 'active': return 'Đang mượn';
        case 'overdue': return 'Quá hạn';
        case 'returned': return 'Đã trả';
        case 'lost': return 'Mất';
        default: return s || '—';
    }
};

const displayStatus = (loan) => loan.display_status ?? loan.status;

// Cho mượn: validate + đóng modal. Khi có backend: gửi POST route('admin.loans.store') với payload tương ứng.
const submitCreateLoan = () => {
    loanFormErrors.value = {};
    if (!(loanForm.value.reader_code || '').trim()) loanFormErrors.value.reader_code = 'Vui lòng nhập mã bạn đọc.';
    if (!(loanForm.value.barcode || '').trim()) loanFormErrors.value.barcode = 'Vui lòng nhập mã vạch sách.';
    if (Object.keys(loanFormErrors.value).length) return;
    // TODO: gọi backend, ví dụ: router.post(route('admin.loans.store'), { reader_code: loanForm.value.reader_code, barcode: loanForm.value.barcode, condition_on_loan: loanForm.value.condition_on_loan });
    loanForm.value = { reader_code: '', barcode: '', condition_on_loan: 'good' };
    showCreateModal.value = false;
};

const openCreateModal = () => {
    showCreateModal.value = true;
    loanForm.value = { reader_code: '', barcode: '', condition_on_loan: 'good' };
    loanFormErrors.value = {};
};
const openReturnModal = () => {
    showReturnModal.value = true;
    returnForm.value = { loan_id: '', barcode: '', condition_on_return: 'good' };
    returnFormErrors.value = {};
};

// Trả sách: validate + đóng modal. Khi có backend: gửi POST route('admin.loans.return') với payload tương ứng.
const submitReturn = () => {
    returnFormErrors.value = {};
    const lid = (returnForm.value.loan_id || '').trim();
    const bar = (returnForm.value.barcode || '').trim();
    if (!lid && !bar) returnFormErrors.value.barcode = 'Vui lòng nhập mã phiếu mượn hoặc mã vạch sách.';
    if (Object.keys(returnFormErrors.value).length) return;
    // TODO: gọi backend, ví dụ: router.post(route('admin.loans.return'), { loan_id: lid || undefined, barcode: bar || undefined, condition_on_return: returnForm.value.condition_on_return });
    returnForm.value = { loan_id: '', barcode: '', condition_on_return: 'good' };
    showReturnModal.value = false;
};

// Trả sách nhanh từ dòng bảng. Khi có backend: gửi POST với loan_id.
const returnBook = (loan) => {
    if (!confirm('Xác nhận trả sách cho phiếu #' + loan.id + '?')) return;
    // TODO: gọi backend, ví dụ: router.post(route('admin.loans.return'), { loan_id: loan.id, condition_on_return: loan.condition_on_loan || 'good' });
};

const exportExcel = () => {
    alert('Chức năng xuất Excel đang được xây dựng.');
};
const viewLoan = (loan) => {
    alert('Xem chi tiết phiếu: ' + loan.id);
};
const printLoan = (loan) => {
    alert('In phiếu: ' + loan.id);
};

const conditionOptions = [
    { value: 'new', label: 'Mới' },
    { value: 'good', label: 'Tốt' },
    { value: 'fair', label: 'Bình thường' },
    { value: 'poor', label: 'Kém' },
    { value: 'damaged', label: 'Hỏng' },
];
</script>

<template>
    <Head title="Quản lý mượn trả sách - Admin" />
    <AdminLayout
        title="Quản lý mượn trả sách"
        :breadcrumbs="[
            { label: 'Mượn – Trả sách' },
            { label: 'Quản lý mượn trả sách' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Quản lý mượn trả sách</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Quy trình cho mượn, trả sách, tra cứu phiếu mượn và xử lý quá hạn. Quy định thiết lập tại
                <Link :href="route('admin.settings.rules')" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">Quy định mượn trả</Link>.
                <button type="button" @click="showGuide = !showGuide" class="ml-2 text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors inline-flex items-center gap-1">
                    <Icon :icon="showGuide ? 'lucide:chevron-up' : 'lucide:help-circle'" class="w-3.5 h-3.5" />
                    {{ showGuide ? 'Ẩn' : 'Hướng dẫn nhanh' }}
                </button>
            </p>

            <div v-show="showGuide" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 animate-in slide-in-from-top-2 duration-200">
                <h3 class="text-sm font-bold text-blue-800 dark:text-blue-400 mb-3 flex items-center gap-2">
                    <Icon icon="lucide:list-checks" class="w-4 h-4" />
                    Quy trình mượn trả sách
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-700 dark:text-slate-400">
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-900/30 dark:bg-blue-900/40 text-blue-700 dark:text-white font-bold text-xs">1</span>
                        <div>
                            <span class="font-semibold text-slate-900 dark:text-white">Cho mượn sách:</span> Bấm "Cho mượn sách" → nhập hoặc quét thông tin bạn đọc và sách (mã cá biệt) → lưu phiếu mượn. Hệ thống kiểm tra quy định (số sách tối đa, hạn thẻ).
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-900/30 dark:bg-blue-900/40 text-blue-700 dark:text-white font-bold text-xs">2</span>
                        <div>
                            <span class="font-semibold text-slate-900 dark:text-white">Trả sách:</span> Bấm "Trả sách" → quét mã sách hoặc mã phiếu mượn → xác nhận trả. Hệ thống cập nhật trạng thái phiếu và giải phóng đầu sách.
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-900/30 dark:bg-blue-900/40 text-blue-700 dark:text-white font-bold text-xs">3</span>
                        <div>
                            <span class="font-semibold text-slate-900 dark:text-white">Tra cứu phiếu:</span> Dùng bảng "Danh sách phiếu mượn" bên dưới để tìm theo bạn đọc, sách, trạng thái (đang mượn / quá hạn / đã trả). Có thể in phiếu hoặc xử lý trả nhanh tại bảng.
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-900/30 dark:bg-blue-900/40 text-blue-700 dark:text-white font-bold text-xs">4</span>
                        <div>
                            <span class="font-semibold text-slate-900 dark:text-white">Gia hạn & Quá hạn:</span> Gia hạn theo quy định trong Cấu hình thư viện. Phiếu quá hạn xử lý tại mục "Quản lý Trả muộn & Phạt" (phạt, khóa thẻ nếu cần).
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button
                    type="button"
                    @click="openCreateModal"
                    class="group flex items-center gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-500/10 transition-all text-left shadow-sm"
                >
                    <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-105 transition-transform">
                        <Icon icon="lucide:book-plus" class="w-6 h-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Cho mượn sách</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tạo phiếu mượn mới cho bạn đọc</p>
                    </div>
                    <Icon icon="lucide:chevron-right" class="w-5 h-5 text-slate-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 shrink-0" />
                </button>
                <button
                    type="button"
                    @click="openReturnModal"
                    class="group flex items-center gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-500 dark:hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-500/10 transition-all text-left shadow-sm"
                >
                    <div class="w-12 h-12 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                        <Icon icon="lucide:book-check" class="w-6 h-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Trả sách</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Xử lý trả sách theo mã sách hoặc phiếu mượn</p>
                    </div>
                    <Icon icon="lucide:chevron-right" class="w-5 h-5 text-slate-400 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 shrink-0" />
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4 shadow-sm">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/40 dark:text-blue-400 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <Icon icon="lucide:book-up" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-slate-900 dark:text-white leading-tight">{{ statsComputed.borrowed }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đang mượn</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4 shadow-sm border-l-4 border-l-rose-500">
                    <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <Icon icon="lucide:alert-triangle" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-rose-600 dark:text-rose-400 leading-tight">{{ statsComputed.overdue }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Quá hạn</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4 shadow-sm">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <Icon icon="lucide:book-check" class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-slate-900 dark:text-white leading-tight">{{ statsComputed.returned }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đã trả (trong danh sách)</div>
                    </div>
                </div>
            </div>

            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Danh sách phiếu mượn</h3>

            <AdminImportExportBar
                add-label="Cho mượn sách"
                :show-import="false"
                :show-update-file="false"
                :has-selection="false"
                @add="openCreateModal"
                @export-excel="exportExcel"
            />

                <AdminFilterSearch
                    v-model="filterValues.searchKeyword"
                    search-placeholder="Nhập từ khóa để tìm..."
                    :show-filter-button="false"
                    @search="() => {}"
                >
                    <template #filters>
                        <div class="flex items-center gap-3">
                            <AdminFilterPanel
                                :options="SEARCH_IN_OPTIONS"
                                v-model:model-value="filterValues.searchIn"
                                :show="showFilterPanel"
                                @update:show="showFilterPanel = $event"
                            />
                            <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                                <option value="">Trạng thái</option>
                                <option value="active">Đang mượn</option>
                                <option value="overdue">Quá hạn</option>
                                <option value="returned">Đã trả</option>
                                <option value="lost">Mất</option>
                            </select>
                        </div>
                    </template>
                </AdminFilterSearch>

                <!-- Bảng -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto text-nowrap">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                    <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Bạn đọc</th>
                                    <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã bạn đọc</th>
                                    <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên sách</th>
                                    <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã cá biệt</th>
                                    <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Ngày mượn</th>
                                    <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Hạn trả</th>
                                    <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Trạng thái</th>
                                    <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="l in filtered" :key="l.id" class="admin-table-row">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white text-[13px] tracking-tight">{{ l.reader_name }}</div>
                                    </td>
                                    <td class="p-4">
                                        <span class="text-[12px] font-bold font-mono text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-800">{{ l.reader_code }}</span>
                                    </td>
                                    <td class="p-4">
                                        <div class="text-[13px] font-medium text-slate-600 dark:text-slate-400 max-w-[200px] truncate" :title="l.book_title">{{ l.book_title }}</div>
                                    </td>
                                    <td class="p-4">
                                        <span class="text-[12px] font-bold font-mono text-blue-600 bg-blue-50 dark:bg-blue-900/40 dark:text-blue-400 px-2 py-0.5 rounded border border-blue-100 dark:border-slate-800 uppercase">{{ l.book_code }}</span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="text-[12px] text-slate-500 font-medium">{{ l.loan_date }}</span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="text-[12px] font-bold" :class="displayStatus(l) === 'overdue' ? 'text-rose-600' : 'text-slate-700 dark:text-white'">{{ l.due_date }}</span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusStyle(displayStatus(l))]">
                                            {{ getStatusLabel(displayStatus(l)) }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex justify-end gap-1">
                                            <button v-if="l.status !== 'returned'" @click="returnBook(l)" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-700 transition-all">
                                                Trả sách
                                            </button>
                                            <button @click="viewLoan(l)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all" title="Xem chi tiết">
                                                <Icon icon="lucide:eye" class="w-[18px] h-[18px]" />
                                            </button>
                                            <button @click="printLoan(l)" class="p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg transition-all" title="In phiếu">
                                                <Icon icon="lucide:printer" class="w-[18px] h-[18px]" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="filtered.length === 0" class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">Chưa có phiếu mượn nào phù hợp.</p>
                </div>
        </div>

        <!-- Modal Cho mượn sách (giao diện thống nhất với Quản lý thẻ / Quản lý người dùng) -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="showCreateModal = false">
                <div class="absolute inset-0 bg-slate-900/50" @click="showCreateModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Cho mượn sách</h3>
                        <button type="button" @click="showCreateModal = false" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitCreateLoan" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Mã bạn đọc <span class="text-rose-500">*</span></label>
                            <Input v-model="loanForm.reader_code" type="text" placeholder="Nhập hoặc quét mã bạn đọc (SV, GV...)" class="w-full h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
                            <p v-if="loanFormErrors.reader_code" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ loanFormErrors.reader_code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Mã vạch sách (mã cá biệt) <span class="text-rose-500">*</span></label>
                            <Input v-model="loanForm.barcode" type="text" placeholder="Nhập hoặc quét mã vạch sách" class="w-full h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
                            <p v-if="loanFormErrors.barcode" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ loanFormErrors.barcode }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tình trạng sách khi mượn</label>
                            <select v-model="loanForm.condition_on_loan" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm">
                                <option v-for="opt in conditionOptions.filter(o => o.value !== 'damaged')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>
                        <div class="flex gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <Button type="button" variant="outline" class="flex-1" @click="showCreateModal = false">Hủy bỏ</Button>
                            <Button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white">Lưu phiếu mượn</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Trả sách (giao diện thống nhất) -->
        <Teleport to="body">
            <div v-if="showReturnModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="showReturnModal = false">
                <div class="absolute inset-0 bg-slate-900/50" @click="showReturnModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-200">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Trả sách</h3>
                        <button type="button" @click="showReturnModal = false" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitReturn" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Mã phiếu mượn</label>
                            <Input v-model="returnForm.loan_id" type="text" placeholder="ID phiếu (nếu biết)" class="w-full h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
                        </div>
                        <div class="text-center text-slate-500 dark:text-slate-400 text-sm">— hoặc —</div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Mã vạch sách</label>
                            <Input v-model="returnForm.barcode" type="text" placeholder="Quét mã vạch sách trả" class="w-full h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
                            <p v-if="returnFormErrors.loan_id || returnFormErrors.barcode" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ returnFormErrors.loan_id || returnFormErrors.barcode }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tình trạng sách khi trả</label>
                            <select v-model="returnForm.condition_on_return" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm">
                                <option v-for="opt in conditionOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>
                        <div class="flex gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <Button type="button" variant="outline" class="flex-1" @click="showReturnModal = false">Hủy bỏ</Button>
                            <Button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white">Xác nhận trả sách</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
