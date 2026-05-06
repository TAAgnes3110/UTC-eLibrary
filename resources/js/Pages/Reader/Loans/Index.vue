<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue'
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue'
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue'
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue'
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue'
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue'
import { meLoansApi } from '@/api/meLoans'
import { extractApiPaginator } from '@/utils/adminPagination'
import { toast } from '@/store/toast'

const PER_PAGE = 20
const SEARCH_IN_OPTIONS = [
    { key: 'loan_code', label: 'Mã phiếu' },
    { key: 'card_number', label: 'Mã thẻ thư viện' },
    { key: 'created_by_name', label: 'Người tạo' },
]

const loading = ref(false)
const rows = ref([])
const pageNum = ref(1)
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: PER_PAGE })
const selectedIds = ref([])
const showSingleDeleteModal = ref(false)
const singleDeleteLoading = ref(false)
const deletingLoan = ref(null)
const showFilterPanel = ref(false)
const borrowSummary = ref({
    borrowed_textbooks: 0,
    borrowed_references: 0,
    borrowed_total: 0,
})
const filters = reactive({
    searchKeyword: '',
    searchIn: {
        loan_code: true,
        card_number: true,
        created_by_name: true,
    },
    status: '',
    sort: '',
})

function buildSearchInParam() {
    const active = Object.keys(filters.searchIn).filter((k) => filters.searchIn[k])
    if (active.length === 0 || active.length === SEARCH_IN_OPTIONS.length) return undefined
    return active.join(',')
}

function formatDate(value) {
    if (!value) return '—'
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('vi-VN')
}

async function loadLoans(resetPage = false) {
    if (resetPage) {
        pageNum.value = 1
        selectedIds.value = []
    }
    loading.value = true
    try {
        const payload = await meLoansApi.list({
            page: pageNum.value,
            per_page: PER_PAGE,
            search: filters.searchKeyword.trim() || undefined,
            search_in: buildSearchInParam(),
            status: filters.status || undefined,
            sort: filters.sort || undefined,
        })
        const { items, meta: m } = extractApiPaginator(payload, PER_PAGE)
        rows.value = items
        meta.value = { current_page: m.current_page, last_page: m.last_page, total: m.total, per_page: m.per_page }
        pageNum.value = m.current_page
        const allowed = new Set(items.map((x) => x.id))
        selectedIds.value = selectedIds.value.filter((id) => allowed.has(id))
    } catch (e) {
        rows.value = []
        toast.error(e?.response?.data?.messages || 'Không tải được danh sách phiếu mượn.', { title: 'Phiếu mượn' })
    } finally {
        loading.value = false
    }
}

/** Ô tìm kiếm: chỉ load qua @search (AdminFilterSearch debounce + emit), không watch keyword để tránh gọi API hai lần. */
watch(() => filters.searchIn, () => loadLoans(true), { deep: true })
watch(() => [filters.status, filters.sort], () => loadLoans(true))

const hasSelection = computed(() => selectedIds.value.length > 0)
const isAllSelected = computed(() => rows.value.length > 0 && rows.value.every((r) => selectedIds.value.includes(r.id)))

function toggleSelect(id) {
    const n = Number(id)
    if (!Number.isInteger(n)) return
    if (selectedIds.value.includes(n)) {
        selectedIds.value = selectedIds.value.filter((x) => x !== n)
    } else {
        selectedIds.value = [...selectedIds.value, n]
    }
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = []
        return
    }
    selectedIds.value = rows.value.map((r) => r.id)
}

function deselectAll() {
    selectedIds.value = []
}

function goPage(p) {
    const n = Number(p)
    if (!Number.isFinite(n) || n < 1 || n > meta.value.last_page) return
    pageNum.value = n
    loadLoans(false)
}

function goShow(id) {
    router.visit(route('reader.services.loan-requests.show', { loan: id }))
}

function canRenew(row) {
    const status = String(row?.status || '').toLowerCase().trim()
    const statusLabel = String(row?.status_label || '').toLowerCase().trim()
    const raw = `${status} ${statusLabel}`
    return (
        raw.includes('da_muon')
        || raw.includes('dang_muon')
        || raw.includes('borrowed')
        || raw.includes('qua_han')
        || raw.includes('overdue')
        || raw.includes('đang mượn')
        || raw.includes('qua han')
        || raw.includes('quá hạn')
    )
}

function isEligibleToRenew(row) {
    return row?.renewal_eligibility?.eligible === true
}

function canDelete(row) {
    return row?.status === 'da_tra'
}

function isReturnedLoan(row) {
    const status = String(row?.status || '').toLowerCase().trim()
    const statusLabel = String(row?.status_label || '').toLowerCase().trim()
    return status === 'da_tra' || status === 'returned' || statusLabel === 'đã trả'
}

function goRenew(row) {
    if (!canRenew(row)) {
        toast.warn('Chỉ phiếu đang mượn hoặc quá hạn mới có thể gia hạn.', { title: 'Gia hạn' })
        return
    }
    if (!isEligibleToRenew(row)) {
        const msg = row?.renewal_eligibility?.message || 'Phiếu này hiện không thể gia hạn.'
        toast.warn(msg, { title: 'Gia hạn' })
        return
    }
    goShow(row.id)
}

function removeLoan(row) {
    if (!canDelete(row)) {
        toast.warn('Chỉ có thể xóa phiếu ở trạng thái đã trả.', { title: 'Xóa phiếu' })
        return
    }
    deletingLoan.value = { id: row.id, code: row.loan_code || `#${row.id}` }
    showSingleDeleteModal.value = true
}

function closeSingleDeleteModal() {
    showSingleDeleteModal.value = false
    deletingLoan.value = null
}

async function confirmSingleDelete() {
    const id = deletingLoan.value?.id
    if (!id) {
        closeSingleDeleteModal()
        return
    }
    singleDeleteLoading.value = true
    try {
        await meLoansApi.remove(id)
        toast.success('Đã xóa phiếu khỏi danh sách.', { title: 'Xóa phiếu' })
        selectedIds.value = selectedIds.value.filter((x) => x !== id)
        closeSingleDeleteModal()
        await loadLoans(false)
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không xóa được phiếu mượn.', { title: 'Xóa phiếu' })
    } finally {
        singleDeleteLoading.value = false
    }
}

async function exportExcel() {
    try {
        const params = selectedIds.value.length > 0
            ? { ids: [...selectedIds.value] }
            : {
                search: filters.searchKeyword.trim() || undefined,
                search_in: buildSearchInParam(),
                status: filters.status || undefined,
                sort: filters.sort || undefined,
            }
        const response = await meLoansApi.export(params)
        const blob = new Blob([response.data], {
            type: response.headers?.['content-type'] || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        })
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = selectedIds.value.length > 0 ? 'phieu_muon_da_chon.xlsx' : 'phieu_muon_cua_toi.xlsx'
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(url)
        toast.success('Đã xuất Excel phiếu mượn.', { title: 'Phiếu mượn' })
    } catch {
        toast.error('Không thể xuất Excel.', { title: 'Phiếu mượn' })
    }
}

async function loadBorrowSummary() {
    try {
        const payload = await meLoansApi.summary()
        const data = payload?.data || {}
        borrowSummary.value = {
            borrowed_textbooks: Number(data.borrowed_textbooks || 0),
            borrowed_references: Number(data.borrowed_references || 0),
            borrowed_total: Number(data.borrowed_total || 0),
        }
    } catch {
        borrowSummary.value = {
            borrowed_textbooks: 0,
            borrowed_references: 0,
            borrowed_total: 0,
        }
    }
}

onMounted(() => {
    loadLoans(false)
    loadBorrowSummary()
})
</script>

<template>
    <ReaderLayout>
        <Head title="Phiếu mượn của tôi" />
        <div class="mx-auto max-w-6xl space-y-4 animate-in fade-in-50 duration-500">
            <div>
                <Link
                    :href="route('reader.services')"
                    class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-400"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                    Quay lại dịch vụ
                </Link>
            </div>

            <AdminPageHeading title="Quản lý phiếu mượn của tôi">
                <template #description>
                    Theo dõi danh sách phiếu mượn và xem thông tin chi tiết từng phiếu.
                </template>
            </AdminPageHeading>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white/90 px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sách giáo trình đang mượn</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ borrowSummary.borrowed_textbooks }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white/90 px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sách tham khảo đang mượn</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ borrowSummary.borrowed_references }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white/90 px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tổng số sách đang mượn</p>
                    <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ borrowSummary.borrowed_total }}</p>
                </div>
            </div>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                :show-add="false"
                :show-import="false"
                :show-update-file="false"
                :show-delete-selected="false"
                @export-excel="exportExcel"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filters.searchKeyword"
                search-placeholder="Tìm mã phiếu, mã thẻ, người tạo..."
                :show-filter-button="false"
                @search="loadLoans(true)"
            >
                <template #filters>
                    <div class="flex flex-wrap items-center gap-2">
                        <AdminFilterPanel
                            :options="SEARCH_IN_OPTIONS"
                            v-model:model-value="filters.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filters.status" class="admin-filter-select admin-filter-select-centered min-w-0 w-full sm:w-auto sm:min-w-[148px]">
                            <option value="">Trạng thái</option>
                            <option value="da_muon">Đang mượn</option>
                            <option value="da_tra">Đã trả</option>
                            <option value="qua_han">Quá hạn</option>
                        </select>
                        <select v-model="filters.sort" class="admin-filter-select admin-filter-select-centered min-w-0 w-full sm:w-auto sm:min-w-[188px]">
                            <option value="">Sắp xếp</option>
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px] text-left border-collapse">
                        <thead class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="p-3 w-11 align-middle">
                                    <span class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            :checked="isAllSelected"
                                            :indeterminate="hasSelection && !isAllSelected"
                                            class="admin-table-checkbox"
                                            @change="toggleSelectAll"
                                        >
                                    </span>
                                </th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Mã phiếu</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Mã thẻ</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Ngày mượn</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Hạn trả</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Trạng thái</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 w-[220px]">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="row in rows" :key="row.id" :class="[selectedIds.includes(row.id) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']">
                                <td class="p-3">
                                    <span class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.includes(row.id)"
                                            class="admin-table-checkbox"
                                            @change="toggleSelect(row.id)"
                                        >
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-[12px] font-semibold text-slate-800 dark:text-slate-100">{{ row.loan_code || `#${row.id}` }}</td>
                                <td class="p-3 text-[12px] text-slate-700 dark:text-slate-300">{{ row.library_card_number || '—' }}</td>
                                <td class="p-3 text-[12px] text-slate-700 dark:text-slate-300">{{ formatDate(row.loan_date) }}</td>
                                <td class="p-3 text-[12px] text-slate-700 dark:text-slate-300">{{ formatDate(row.due_date) }}</td>
                                <td class="p-3">
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-sm text-[11px] font-semibold leading-tight"
                                        :class="row.status === 'da_tra'
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200'
                                            : row.status === 'qua_han'
                                                ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/45 dark:text-amber-100'
                                                : 'bg-sky-100 text-sky-900 dark:bg-sky-900/45 dark:text-sky-100'"
                                    >
                                        {{ row.status_label || row.status }}
                                    </span>
                                </td>
                                <td class="p-1.5 sm:p-2 align-middle">
                                    <div class="flex items-center gap-1 w-full max-w-[210px]" role="group" aria-label="Thao tác phiếu mượn">
                                        <button
                                            type="button"
                                            class="loan-action-btn border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                            title="Xem chi tiết"
                                            @click="goShow(row.id)"
                                        >
                                            <Icon icon="lucide:eye" class="w-4 h-4 shrink-0" />
                                            <span class="loan-action-btn__label">Xem</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="loan-action-btn border-blue-200 bg-blue-50/90 text-blue-800 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-200 dark:hover:bg-blue-900/40 disabled:opacity-40 disabled:pointer-events-none"
                                            title="Gửi yêu cầu gia hạn"
                                            :disabled="isReturnedLoan(row)"
                                            @click="goRenew(row)"
                                        >
                                            <Icon icon="lucide:calendar-clock" class="w-4 h-4 shrink-0" />
                                            <span class="loan-action-btn__label">Gia hạn</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="loan-action-btn border-rose-200 bg-rose-50/90 text-rose-800 hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/35 dark:text-rose-200 dark:hover:bg-rose-900/30 disabled:opacity-40 disabled:pointer-events-none"
                                            title="Xóa phiếu"
                                            :disabled="!canDelete(row)"
                                            @click="removeLoan(row)"
                                        >
                                            <Icon icon="lucide:trash-2" class="w-4 h-4 shrink-0" />
                                            <span class="loan-action-btn__label">Xóa</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="loading" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Đang tải...</p>
                <p v-else-if="rows.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Chưa có phiếu mượn nào.</p>
            </div>

            <AdminPaginationBar
                always-show
                :current-page="meta.current_page"
                :last-page="meta.last_page"
                :disabled="loading"
                @go-page="goPage"
            />

            <AdminDeleteConfirmModal
                :show="showSingleDeleteModal"
                title="Xóa phiếu khỏi danh sách"
                confirm-button-label="Xóa"
                item-label="phiếu mượn"
                :item="deletingLoan"
                :selected-count="0"
                :loading="singleDeleteLoading"
                @close="closeSingleDeleteModal"
                @confirm="confirmSingleDelete"
            />
        </div>
    </ReaderLayout>
</template>

<style scoped>
.loan-action-btn {
    display: inline-flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    border-width: 1px;
    border-radius: 0.125rem;
    padding: 0.375rem 0.375rem;
    min-height: 40px;
    width: 100%;
    font-size: 10px;
    line-height: 1.25;
    font-weight: 600;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}
.loan-action-btn__label {
    flex-shrink: 0;
}
</style>
