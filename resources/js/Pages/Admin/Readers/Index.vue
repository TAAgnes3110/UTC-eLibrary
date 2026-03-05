<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';

// Props: readers, faculties, cohorts, departments từ backend
const props = defineProps({
    readers: { type: Array, default: () => [] },
    faculties: { type: Array, default: () => [] },
    cohorts: { type: Array, default: () => ['K60', 'K61', 'K62', 'K63', 'K64', 'K65', 'K66'] },
    departments: { type: Array, default: () => [] },
});

const SEARCH_IN_OPTIONS = [
    { key: 'name', label: 'Họ tên' },
    { key: 'code', label: 'Mã định danh' },
    { key: 'card_number', label: 'Mã thẻ' },
    { key: 'class', label: 'Lớp' },
    { key: 'faculty', label: 'Đơn vị/Khoa' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Số điện thoại' },
];
const TYPE_FILTER_OPTIONS = { title: 'Loại bạn đọc', options: [
    { key: 'student', label: 'Học sinh / Sinh viên' },
    { key: 'teacher', label: 'Giáo viên / Cán bộ' },
    { key: 'other', label: 'Bạn đọc khác' },
] };
const filterValues = ref({
    status: '',
    searchKeyword: '',
    searchIn: { name: true, code: true, card_number: true, class: true, faculty: true, email: true, phone: true },
    typeFilter: { student: false, teacher: false, other: false },
});
const showFilterPanel = ref(false);
const showModal = ref(false);
const showRenewModal = ref(false);
const showDetailModal = ref(false);
const showDeleteConfirm = ref(false);
const showImportModal = ref(false);
const showPhotoModal = ref(false);
const showPrintModal = ref(false);
const showHistoryModal = ref(false);
const showTrashDrawer = ref(false);
const trashedReaders = ref([]);
const loadingTrash = ref(false);
const isEditing = ref(false);
const selectedIds = ref(new Set());
const deleteTarget = ref(null); 
const renewTarget = ref(null);
const detailReader = ref(null);
const historyReader = ref(null);

const listByType = computed(() => {
    let list = [...props.readers];
    const tf = filterValues.value.typeFilter || {};
    const anyChecked = tf.student || tf.teacher || tf.other;
    if (anyChecked) {
        const types = [];
        if (tf.student) types.push('student');
        if (tf.teacher) types.push('teacher');
        if (tf.other) types.push('other');
        list = list.filter(r => types.includes(r.type));
    }
    return list;
});

const filtered = computed(() => {
    let result = listByType.value;
    const sv = filterValues.value.status;
    if (sv) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        result = result.filter(r => {
            if (sv === 'active') return (r.status === 'active') && (!r.expiry_date || new Date(r.expiry_date) >= today);
            if (sv === 'blocked') return r.status === 'blocked' || r.status === 'inactive';
            if (sv === 'inactive') return r.status === 'inactive';
            return true;
        });
    }
    const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
    const sin = filterValues.value.searchIn || {};
    if (kw) {
        const anyChecked = Object.values(sin).some(Boolean);
        if (anyChecked) {
            result = result.filter((r) => {
                const m = [];
                if (sin.name) m.push((r.name || '').toLowerCase().includes(kw));
                if (sin.code) m.push((r.code || '').toLowerCase().includes(kw));
                if (sin.card_number) m.push((r.card_number || '').toLowerCase().includes(kw));
                if (sin.class) m.push((r.class || '').toLowerCase().includes(kw));
                if (sin.faculty) m.push((r.faculty || '').toLowerCase().includes(kw));
                if (sin.email) m.push((r.email || '').toLowerCase().includes(kw));
                if (sin.phone) m.push((r.phone || '').toLowerCase().includes(kw));
                return m.some(Boolean);
            });
        }
    }
    return result;
});

const selectedList = computed(() => filtered.value.filter(r => selectedIds.value.has(r.id)));
const hasSelection = computed(() => selectedIds.value.size > 0);
const isAllSelected = computed(() => filtered.value.length > 0 && selectedIds.value.size === filtered.value.length);

function toggleSelectAll() {
    if (isAllSelected.value) selectedIds.value.clear();
    else filtered.value.forEach(r => selectedIds.value.add(r.id));
    selectedIds.value = new Set(selectedIds.value);
}
function toggleSelect(id) {
    if (selectedIds.value.has(id)) selectedIds.value.delete(id);
    else selectedIds.value.add(id);
    selectedIds.value = new Set(selectedIds.value);
}

function deselectAll() {
    selectedIds.value.clear();
    selectedIds.value = new Set(selectedIds.value);
}

const form = useForm({
    id: null,
    name: '',
    code: '',
    card_number: '',
    issue_date: '',
    expiry_date: '',
    faculty_id: null,
    cohort: '',
    department_id: null,
    type: 'student',
    gender: 'Nam',
    email: '',
    phone: '',
});

const departmentsFilteredByFaculty = computed(() => {
    if (!form.faculty_id) return props.departments;
    return props.departments.filter((d) => d.faculty_id === form.faculty_id);
});

const renewForm = useForm({
    reader_id: null,
    new_expiry_date: '',
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.type = 'student';
    form.issue_date = new Date().toISOString().slice(0, 10);
    showModal.value = true;
};

const editReader = (r) => {
    isEditing.value = true;
    form.id = r.id;
    form.name = r.name;
    form.code = r.code;
    form.card_number = r.card_number || r.code || '';
    form.issue_date = (r.issue_date || '').toString().slice(0, 10);
    form.expiry_date = (r.expiry_date || '').toString().slice(0, 10);
    form.faculty_id = r.faculty_id ?? null;
    form.cohort = r.cohort || '';
    form.department_id = r.department_id ?? null;
    form.type = r.type;
    form.gender = r.gender || 'Nam';
    form.email = r.email || '';
    form.phone = r.phone || '';
    showModal.value = true;
};

const openRenew = (r) => {
    renewTarget.value = r;
    renewForm.reader_id = r.id;
    renewForm.new_expiry_date = (r.expiry_date || '').toString().slice(0, 10) || new Date().toISOString().slice(0, 10);
    showRenewModal.value = true;
};

const genderToBackend = (v) => (v === 'Nữ' ? 'female' : v === 'Nam' ? 'male' : 'other');

const save = async () => {
    if (!form.id) return;
    const payload = {
        name: form.name,
        code: form.code,
        email: form.email,
        phone: form.phone || null,
        gender: genderToBackend(form.gender),
        faculty_id: form.faculty_id || null,
        cohort: form.cohort || null,
        department_id: form.department_id || null,
        card_number: form.card_number || null,
        issue_date: form.issue_date || null,
        expiry_date: form.expiry_date || null,
        user_type: 'MEMBER',
    };
    try {
        await window.axios.put(`/users/${form.id}`, payload);
        showModal.value = false;
        router.reload();
    } catch (e) {
        form.setErrors(e.response?.data?.errors || {});
    }
};

const saveRenew = () => {
    // TODO: renewForm.post(route('admin.readers.renew'))
    showRenewModal.value = false;
    renewTarget.value = null;
};

const doSearch = () => {
    // Đã reactive qua filtered, nút "Tìm kiếm" chỉ cần trigger có thể gửi request nếu backend filter
};

const openDeleteOne = (r) => {
    deleteTarget.value = r;
    showDeleteConfirm.value = true;
};

const openDeleteMultiple = () => {
    if (!hasSelection.value) return;
    deleteTarget.value = 'multiple';
    showDeleteConfirm.value = true;
};

const confirmDelete = async () => {
    try {
        if (deleteTarget.value === 'multiple') {
            for (const id of selectedIds.value) {
                await window.axios.delete(`/users/${id}`);
            }
            selectedIds.value.clear();
            selectedIds.value = new Set();
        } else if (deleteTarget.value && deleteTarget.value.id) {
            await window.axios.delete(`/users/${deleteTarget.value.id}`);
            router.reload();
        }
        if (deleteTarget.value === 'multiple') router.reload();
    } catch (_) {
        router.reload();
    }
    showDeleteConfirm.value = false;
    deleteTarget.value = null;
};

const openTrashDrawer = () => {
    showTrashDrawer.value = true;
    fetchTrash();
};
const fetchTrash = async () => {
    loadingTrash.value = true;
    try {
        const { data } = await window.axios.get('/users/trash');
        trashedReaders.value = data.data || [];
    } catch {
        trashedReaders.value = [];
    }
    loadingTrash.value = false;
};
const onRestoreReader = async (id) => {
    try {
        await window.axios.post(`/users/restore/${id}`);
        fetchTrash();
        router.reload();
    } catch (_) {}
};
const onForceDeleteReader = async (id) => {
    if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
    try {
        await window.axios.delete(`/users/force/${id}`);
        fetchTrash();
        router.reload();
    } catch (_) {}
};

const openDetail = (r) => {
    detailReader.value = r;
    showDetailModal.value = true;
};

const openHistory = (r) => {
    historyReader.value = r;
    showHistoryModal.value = true;
};

const downloadTemplate = () => {
    window.location.href = '/templates/06-ban-doc/Mau_nhap_ban_doc.csv';
};

const openImportModal = () => {
    showImportModal.value = true;
};

const importExcel = (file) => {
    // TODO: gửi file lên API nhập excel bạn đọc
};

const updatePhoto = () => {
    alert('Chức năng đang được phát triển. API upload ảnh thẻ sẽ có trong bản cập nhật sau.');
};

const openPhotoModal = () => {
    showPhotoModal.value = true;
};

const openPrintModal = () => {
    if (!hasSelection.value) return;
    showPrintModal.value = true;
};

const exportExcel = () => {
    window.location.href = route('admin.readers.export');
};

// Mock lịch sử gia hạn (backend có thể trả từ library_cards.metadata hoặc bảng renewal_histories)
const renewalHistory = computed(() => {
    if (!historyReader.value) return [];
    return [
        { date: historyReader.value.issue_date, note: 'Cấp thẻ lần đầu' },
        { date: historyReader.value.expiry_date, note: 'Hạn hiện tại' },
    ];
});

function formatDate(d) {
    if (!d) return '—';
    const s = String(d).slice(0, 10);
    if (s.length === 10) return s.split('-').reverse().join('/');
    return s;
}

function readerStatusLabel(r) {
    if (r.status === 'blocked' || r.status === 'inactive') return 'Đã khóa';
    if (r.expiry_date && new Date(r.expiry_date) < new Date()) return 'Hết hạn';
    return 'Hoạt động';
}

function readerStatusClass(r) {
    if (r.status === 'blocked' || r.status === 'inactive') return 'bg-slate-500 dark:bg-slate-600';
    if (r.expiry_date && new Date(r.expiry_date) < new Date()) return 'bg-rose-500 dark:bg-rose-600';
    return 'bg-emerald-500 dark:bg-emerald-600';
}
</script>

<template>
    <Head title="Quản lý Bạn đọc - Admin" />
    <AdminLayout
        title="Quản lý người dùng"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý người dùng' },
            { label: 'Bạn đọc' },
        ]"
    >
        <!-- Cùng kiểu với Quản lý tác giả: không viền đen, nền slate/trắng -->
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Quản lý người dùng</h2>

            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Danh sách bạn đọc</h3>
                <Button variant="outline" size="sm" class="gap-1.5" @click="openTrashDrawer">
                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                    Thùng rác
                </Button>
            </div>

            <!-- Nút thao tác: cùng kiểu Quản lý tác giả -->
            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.size"
                update-file-label="Cập nhật ảnh thẻ"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @update-file="openPhotoModal"
                @delete-selected="openDeleteMultiple"
                @deselect-all="deselectAll"
            >
                <template v-if="hasSelection" #extra>
                    <button type="button" @click="openPrintModal" class="btn-admin-green">
                        <Icon icon="lucide:credit-card" class="w-3.5 h-3.5" /> In thẻ
                    </button>
                </template>
            </AdminImportExportBar>

            <!-- Bộ lọc + Tìm kiếm -->
            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Nhập từ khóa để tìm..."
                :show-filter-button="false"
                @search="doSearch"
            >
                <template #filters>
                    <div class="flex items-center gap-3">
                        <AdminFilterPanel
                            :options="SEARCH_IN_OPTIONS"
                            v-model:model-value="filterValues.searchIn"
                            :filter-group="TYPE_FILTER_OPTIONS"
                            v-model:filter-value="filterValues.typeFilter"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="active">Đang hoạt động</option>
                            <option value="blocked">Đã khóa</option>
                            <option value="inactive">Chưa kích hoạt</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <!-- Bảng: cùng kiểu Quản lý tác giả (border slate, hover #2C3A4F) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 w-12">
                                    <input type="checkbox" :checked="isAllSelected" :indeterminate="hasSelection && !isAllSelected" @change="toggleSelectAll" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500" />
                                </th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Họ và tên</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã thẻ</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ngày cấp</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ngày hết hạn</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Lớp / Đơn vị</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="r in filtered" :key="r.id" class="admin-table-row">
                                <td class="p-4">
                                    <input type="checkbox" :checked="selectedIds.has(r.id)" @change="toggleSelect(r.id)" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500" />
                                </td>
                                <td class="p-4">
                                    <button type="button" @click="openDetail(r)" class="font-semibold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 text-left">
                                        {{ r.name }}
                                    </button>
                                </td>
                                <td class="p-4 font-mono text-[12px] text-slate-600 dark:text-slate-300">{{ r.card_number || r.code }}</td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">{{ formatDate(r.issue_date) }}</td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">{{ formatDate(r.expiry_date) || '—' }}</td>
                                <td class="p-4 text-[12px] text-slate-600 dark:text-slate-300">{{ r.class || r.faculty || '—' }}</td>
                                <td class="p-4">
                                    <span :class="['px-2.5 py-1 rounded-full text-[10px] font-bold uppercase text-white', readerStatusClass(r)]">
                                        {{ readerStatusLabel(r) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-1">
                                        <button type="button" @click="editReader(r)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all" title="Chỉnh sửa">
                                            <Icon icon="lucide:edit-3" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button type="button" @click="openRenew(r)" class="p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-all" title="Gia hạn thẻ">
                                            <Icon icon="lucide:credit-card" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button type="button" @click="openHistory(r)" class="p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-all" title="Lịch sử gia hạn">
                                            <Icon icon="lucide:history" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button type="button" @click="openDeleteOne(r)" class="p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-all" title="Xóa">
                                            <Icon icon="lucide:trash-2" class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="filtered.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Chưa có bạn đọc nào.</p>
            </div>
        </div>

        <!-- Modal Thêm mới / Chỉnh sửa thẻ bạn đọc (các trường bắt buộc có dấu *) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800">
                    <div class="sticky top-0 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 z-10">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ isEditing ? 'Chỉnh sửa thẻ bạn đọc' : 'Thêm mới thẻ bạn đọc' }}</h3>
                        <button type="button" @click="showModal = false" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Họ và tên <span class="text-rose-500">*</span></label>
                            <Input v-model="form.name" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="Nhập họ và tên" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã thẻ / Mã định danh <span class="text-rose-500">*</span></label>
                            <Input v-model="form.code" class="h-10 rounded-lg font-mono border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="Mã thẻ duy nhất" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày cấp thẻ <span class="text-rose-500">*</span></label>
                            <Input v-model="form.issue_date" type="date" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 [color-scheme:light] dark:[color-scheme:dark]" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày hết hạn <span class="text-rose-500">*</span></label>
                            <Input v-model="form.expiry_date" type="date" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 [color-scheme:light] dark:[color-scheme:dark]" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Khoa</label>
                            <select v-model="form.faculty_id" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark]" @change="form.department_id = null">
                                <option :value="null">-- Chọn khoa --</option>
                                <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Khóa học</label>
                            <select v-model="form.cohort" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark]">
                                <option value="">-- Chọn khóa --</option>
                                <option v-for="c in cohorts" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Lớp</label>
                            <select v-model="form.department_id" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark]">
                                <option :value="null">-- Chọn lớp --</option>
                                <option v-for="d in departmentsFilteredByFaculty" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Giới tính</label>
                            <select v-model="form.gender" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark]">
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số điện thoại</label>
                            <Input v-model="form.phone" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="Số điện thoại" />
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                            <Input v-model="form.email" type="email" class="h-10 rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800" placeholder="Email" />
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                        <Button variant="outline" @click="showModal = false">Hủy bỏ</Button>
                        <Button @click="save" class="bg-blue-600 hover:bg-blue-700 text-white">Lưu</Button>
                    </div>
                </div>
            </div>

            <!-- Modal Gia hạn thẻ -->
            <div v-if="showRenewModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showRenewModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-md shadow-xl border border-slate-200 dark:border-slate-800 p-6">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Gia hạn thẻ bạn đọc</h3>
                    <p v-if="renewTarget" class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ renewTarget.name }} – Mã thẻ: {{ renewTarget.card_number || renewTarget.code }}</p>
                    <div class="space-y-2 mb-6">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày hết hạn mới <span class="text-rose-500">*</span></label>
                        <Input v-model="renewForm.new_expiry_date" type="date" class="h-10 rounded-lg w-full [color-scheme:light] dark:[color-scheme:dark]" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showRenewModal = false">Hủy bỏ</Button>
                        <Button @click="saveRenew" class="bg-blue-600 hover:bg-blue-700 text-white">Lưu</Button>
                    </div>
                </div>
            </div>

            <AdminDeleteConfirmModal
            :show="showDeleteConfirm"
            title="Xác nhận xóa thẻ bạn đọc"
            item-label="bạn đọc"
            :item="deleteTarget && deleteTarget !== 'multiple' ? deleteTarget : null"
            :selected-count="deleteTarget === 'multiple' ? selectedIds.size : 0"
            @close="showDeleteConfirm = false"
            @confirm="confirmDelete"
        />
        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác – Bạn đọc"
            item-label-key="name"
            :items="trashedReaders"
            :loading="loadingTrash"
            @close="showTrashDrawer = false"
            @restore="onRestoreReader"
            @force-delete="onForceDeleteReader"
        />

            <!-- Modal Xem chi tiết bạn đọc -->
            <div v-if="showDetailModal && detailReader" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showDetailModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-lg shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Chi tiết thẻ bạn đọc</h3>
                        <button type="button" @click="showDetailModal = false" class="p-1 text-slate-500 hover:text-slate-700"><Icon icon="lucide:x" class="w-5 h-5" /></button>
                    </div>
                    <div class="p-6 space-y-3 text-sm">
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">Họ tên:</span> {{ detailReader.name }}</p>
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">Mã thẻ:</span> {{ detailReader.card_number || detailReader.code }}</p>
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">Ngày cấp:</span> {{ formatDate(detailReader.issue_date) }}</p>
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">Ngày hết hạn:</span> {{ formatDate(detailReader.expiry_date) }}</p>
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">Khoa / Lớp:</span> {{ detailReader.faculty || '—' }} / {{ detailReader.class || '—' }}</p>
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">Giới tính:</span> {{ detailReader.gender || '—' }}</p>
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">Email:</span> {{ detailReader.email || '—' }}</p>
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">SĐT:</span> {{ detailReader.phone || '—' }}</p>
                        <p><span class="font-medium text-slate-500 dark:text-slate-400 w-28 inline-block">Trạng thái:</span> {{ detailReader.status === 'active' ? 'Hoạt động' : 'Đã khóa' }}</p>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <Button @click="editReader(detailReader); showDetailModal = false" class="bg-blue-600 hover:bg-blue-700 text-white">Chỉnh sửa</Button>
                    </div>
                </div>
            </div>

            <!-- Modal Lịch sử gia hạn -->
            <div v-if="showHistoryModal && historyReader" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showHistoryModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-md shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Lịch sử gia hạn – {{ historyReader.name }}</h3>
                        <button type="button" @click="showHistoryModal = false" class="p-1 text-slate-500 hover:text-slate-700"><Icon icon="lucide:x" class="w-5 h-5" /></button>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-2 text-sm">
                            <li v-for="(item, i) in renewalHistory" :key="i" class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                <span class="text-slate-600 dark:text-slate-300">{{ item.note }}</span>
                                <span class="font-mono text-slate-500 dark:text-slate-400">{{ formatDate(item.date) }}</span>
                            </li>
                        </ul>
                        <p v-if="renewalHistory.length === 0" class="text-slate-500 dark:text-slate-400 text-sm">Chưa có lịch sử gia hạn.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Nhập excel / Cập nhật ảnh thẻ: định dạng chung AdminFileModal -->
            <AdminFileModal
                :show="showImportModal"
                title="Nhập excel"
                description="Tải file mẫu, điền đầy đủ thông tin bắt buộc, sau đó chọn file tải lên."
                accept=".xls,.xlsx"
                :max-size-mb="10"
                template-label="Tải file mẫu"
                submit-label="Nhập excel"
                :loading="false"
                @close="showImportModal = false"
                @submit="(file) => { importExcel(file); showImportModal = false; }"
                @download-template="downloadTemplate"
            />
            <AdminFileModal
                :show="showPhotoModal"
                title="Cập nhật ảnh thẻ"
                description="Chọn một file .zip chứa các ảnh thẻ. Mỗi ảnh đặt tên đúng mã thẻ + .jpg hoặc .png."
                accept=".zip"
                :max-size-mb="50"
                submit-label="Lưu"
                @close="showPhotoModal = false"
                @submit="() => { updatePhoto(); showPhotoModal = false; }"
            >
                <template #hint>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Ví dụ tên file trong zip: 034204009633.jpg hoặc 034204009633.png (tên = mã thẻ).</p>
                </template>
            </AdminFileModal>

            <!-- Modal In thẻ bạn đọc -->
            <div v-if="showPrintModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showPrintModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-md shadow-xl border border-slate-200 dark:border-slate-800 p-6">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">In thẻ bạn đọc</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Đã chọn {{ selectedIds.size }} bạn đọc. Nhập/chọn thông tin bắt buộc (các trường có *) rồi bấm In.</p>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showPrintModal = false">Hủy bỏ</Button>
                        <Button class="gap-2 bg-blue-600 hover:bg-blue-700 text-white"><Icon icon="lucide:printer" class="w-4 h-4" /> In</Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
