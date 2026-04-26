<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { computed, onMounted, ref, watch } from 'vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue'
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue'
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue'
import { ADMIN_ICONS } from '@/config/adminIcons'
import { profileApi } from '@/api/profile'

const PER_PAGE = 10

const rows = ref([])
const loading = ref(false)
const loadError = ref('')
const searchKeyword = ref('')
const sortBy = ref('newest')
const statusFilter = ref('')
const expandedRowId = ref(null)
const pageNum = ref(1)

const statusOptions = [
    { key: '', label: 'Trạng thái: Tất cả' },
    { key: 'pending', label: 'Chờ duyệt' },
    { key: 'approved', label: 'Đã duyệt' },
    { key: 'rejected', label: 'Đã từ chối' },
]

function statusLabel(s) {
    if (s === 'approved') return 'Đã duyệt'
    if (s === 'rejected') return 'Đã từ chối'
    return 'Chờ duyệt'
}

function statusClass(s) {
    if (s === 'approved') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
    if (s === 'rejected') return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
}

const formatDateTime = (iso) => {
    if (!iso) return '—'
    const date = new Date(iso)
    if (Number.isNaN(date.getTime())) return '—'
    return date.toLocaleString('vi-VN')
}

const hasText = (v) => v != null && String(v).trim() !== ''

const renderField = (value) => (value === null || value === undefined || value === '' ? '—' : value)

/**
 * Chỉ các trường có gửi trong yêu cầu (giống admin Duyệt yêu cầu).
 * @returns {Array<{ key: string, label: string, from: unknown, to: unknown }>}
 */
function requestChangeLines(item) {
    const lines = []
    if (hasText(item?.requested_code)) {
        lines.push({
            key: 'code',
            label: 'Mã',
            from: item.user?.code,
            to: item.requested_code,
        })
    }
    const facId = item?.requested_faculty_id
    if (facId != null && Number(facId) > 0) {
        lines.push({
            key: 'faculty',
            label: 'Khoa',
            from: item.user?.faculty_name,
            to: item.requested_faculty?.name,
        })
    }
    const perId = item?.requested_period_id
    if (perId != null && Number(perId) > 0) {
        lines.push({
            key: 'period',
            label: 'Niên khóa',
            from: item.user?.period_name,
            to: item.requested_period?.name,
        })
    }
    if (hasText(item?.requested_class_code)) {
        lines.push({
            key: 'class',
            label: 'Lớp',
            from: item.user?.class_code,
            to: item.requested_class_code,
        })
    }
    return lines
}

function toggleDetails(id) {
    expandedRowId.value = expandedRowId.value === id ? null : id
}

function runSearch() {
    expandedRowId.value = null
}

const filteredRows = computed(() => {
    let list = rows.value

    if (statusFilter.value) {
        list = list.filter((item) => item.status === statusFilter.value)
    }

    const keyword = searchKeyword.value.trim().toLowerCase()
    if (keyword !== '') {
        list = list.filter((item) => {
            const parts = [
                String(item?.id ?? ''),
                item?.user?.code ?? '',
                item?.user?.name ?? '',
                item?.user?.email ?? '',
                ...requestChangeLines(item).map((l) => `${l.label} ${renderField(l.from)} ${renderField(l.to)}`),
                item?.review_note ?? '',
                item?.reason ?? '',
                statusLabel(item?.status ?? ''),
            ]
            return parts.join(' ').toLowerCase().includes(keyword)
        })
    }

    const sorted = [...list]
    sorted.sort((a, b) => {
        if (sortBy.value === 'oldest') {
            return Number(a?.id ?? 0) - Number(b?.id ?? 0)
        }
        if (sortBy.value === 'status') {
            const order = { pending: 0, approved: 1, rejected: 2 }
            const aOrder = order[a?.status] ?? 9
            const bOrder = order[b?.status] ?? 9
            if (aOrder !== bOrder) return aOrder - bOrder
            return Number(b?.id ?? 0) - Number(a?.id ?? 0)
        }
        return Number(b?.id ?? 0) - Number(a?.id ?? 0)
    })

    return sorted
})

const lastPage = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / PER_PAGE)))

const pagedRows = computed(() => {
    const start = (pageNum.value - 1) * PER_PAGE
    return filteredRows.value.slice(start, start + PER_PAGE)
})

const rangeLabel = computed(() => {
    const total = filteredRows.value.length
    if (total === 0) return '0 / 0'
    const start = (pageNum.value - 1) * PER_PAGE + 1
    const end = Math.min(pageNum.value * PER_PAGE, total)
    return `${start}–${end} / ${total}`
})

watch([statusFilter, sortBy], () => {
    pageNum.value = 1
    expandedRowId.value = null
})

watch(searchKeyword, () => {
    pageNum.value = 1
    expandedRowId.value = null
})

watch(
    () => lastPage.value,
    (lp) => {
        if (pageNum.value > lp) {
            pageNum.value = lp
        }
    },
)

function goPage(p) {
    const n = Number(p)
    if (!Number.isFinite(n) || n < 1 || n > lastPage.value) return
    pageNum.value = n
    expandedRowId.value = null
}

const loadRequests = async () => {
    loading.value = true
    loadError.value = ''
    try {
        const response = await profileApi.myProfileUpdateRequests()
        rows.value = Array.isArray(response?.data) ? response.data : []
    } catch {
        rows.value = []
        loadError.value = 'Không thể tải lịch sử yêu cầu. Vui lòng thử lại.'
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    loadRequests()
})
</script>

<template>
    <ReaderLayout>
        <Head title="Lịch sử yêu cầu cập nhật - Thư viện số UTC" />

        <div class="mx-auto flex min-h-0 w-full max-w-6xl flex-1 flex-col gap-4 animate-in fade-in-50 duration-500">
            <div>
                <Link
                    :href="route('reader.profile')"
                    class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-400"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                    Quay lại hồ sơ
                </Link>
            </div>

            <AdminPageHeading title="Lịch sử yêu cầu cập nhật hồ sơ">
                <template #description>
                    Theo dõi trạng thái các yêu cầu đổi mã định danh, khoa, niên khóa hoặc lớp. Bấm "Chi tiết" để xem đầy đủ minh chứng và ghi chú xử lý.
                </template>
            </AdminPageHeading>

            <div
                v-if="loadError"
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300"
            >
                {{ loadError }}
            </div>

            <AdminFilterSearch
                v-model="searchKeyword"
                search-placeholder="Mã yêu cầu, mã định danh, email, khoa, niên khóa, lớp, ghi chú..."
                :show-filter-button="false"
                @search="runSearch"
            >
                <template #filters>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <select v-model="statusFilter" class="admin-filter-select !h-9 !py-0 leading-9 min-w-[200px] max-w-full pr-9">
                                <option v-for="opt in statusOptions" :key="opt.key || 'all'" :value="opt.key">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <Icon
                                :icon="ADMIN_ICONS.chevronDown"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                            />
                        </div>
                        <div class="relative">
                            <select v-model="sortBy" class="admin-filter-select !h-9 !py-0 leading-9 min-w-[160px] max-w-full pr-9">
                                <option value="newest">Mới nhất</option>
                                <option value="oldest">Cũ nhất</option>
                                <option value="status">Theo trạng thái</option>
                            </select>
                            <Icon
                                :icon="ADMIN_ICONS.chevronDown"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                            />
                        </div>
                    </div>
                </template>
            </AdminFilterSearch>

            <p class="shrink-0 text-xs text-slate-500 dark:text-slate-400">
                Đang xem <span class="font-semibold text-slate-700 dark:text-slate-200">{{ rangeLabel }}</span> yêu cầu
                (tổng trong hệ thống: {{ rows.length }})
            </p>

            <!-- Khung bảng cố định theo viewport: dư nội dung cuộn trong khung, không kéo footer -->
            <div
                class="flex min-h-[12rem] max-h-[70dvh] flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:max-h-[32rem]"
            >
                <div class="min-h-0 min-w-0 flex-1 overflow-x-auto overflow-y-auto overscroll-y-contain">
                    <table class="w-full min-w-[1020px] border-collapse text-left">
                        <thead class="border-b border-slate-100 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/50">
                            <tr>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Thời gian</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Mã yêu cầu</th>
                                <th class="p-3 min-w-[220px] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                    Hiện tại → Yêu cầu
                                </th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Minh chứng</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Trạng thái</th>
                                <th class="p-3 min-w-[140px] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Ghi chú duyệt</th>
                                <th class="p-3 w-[120px] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template v-if="loading">
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Đang tải…</td>
                                </tr>
                            </template>
                            <template v-else-if="rows.length === 0">
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">Bạn chưa gửi yêu cầu nào.</td>
                                </tr>
                            </template>
                            <template v-else-if="filteredRows.length === 0">
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                        Không có bản ghi phù hợp bộ lọc / từ khóa.
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                            <template v-for="item in pagedRows" :key="item.id">
                                <tr class="align-top admin-table-row">
                                    <td class="p-3 text-[12px] text-slate-700 dark:text-slate-300">
                                        {{ formatDateTime(item.created_at) }}
                                    </td>
                                    <td class="p-3 font-mono text-[12px] font-semibold text-slate-800 dark:text-slate-100">
                                        #{{ item.id }}
                                    </td>
                                    <td class="p-3 text-[12px] text-slate-600 dark:text-slate-300">
                                        <div class="space-y-1">
                                            <template v-if="requestChangeLines(item).length">
                                                <p v-for="line in requestChangeLines(item)" :key="`${item.id}-${line.key}`">
                                                    <span class="text-slate-400 dark:text-slate-500">{{ line.label }}:</span>
                                                    {{ renderField(line.from) }} → {{ renderField(line.to) }}
                                                </p>
                                            </template>
                                            <p v-else class="text-slate-400 dark:text-slate-500">
                                                Không có mô tả trường (xem minh chứng / chi tiết).
                                            </p>
                                            <p
                                                v-if="item.reason"
                                                class="border-t border-slate-100 pt-1 text-slate-500 dark:border-slate-800 dark:text-slate-400"
                                            >
                                                Lý do: {{ item.reason }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="p-3 align-middle">
                                        <a
                                            v-if="item.proof_image_url"
                                            :href="item.proof_image_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex flex-col items-center gap-1 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Xem ảnh minh chứng"
                                        >
                                            <span class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                                                <img
                                                    :src="item.proof_image_url"
                                                    alt="Minh chứng"
                                                    class="h-11 w-11 object-cover"
                                                />
                                            </span>
                                            <span class="text-[10px] font-semibold leading-none">Nhấn để xem</span>
                                        </a>
                                        <span v-else class="text-[12px] text-slate-500">—</span>
                                    </td>
                                    <td class="p-3 align-middle whitespace-nowrap">
                                        <span
                                            :class="[
                                                statusClass(item.status),
                                                'inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap',
                                            ]"
                                        >
                                            {{ statusLabel(item.status) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-[12px] text-slate-700 dark:text-slate-300">
                                        <p class="line-clamp-3 max-w-[220px]" :title="item.review_note || ''">
                                            {{ item.review_note || '—' }}
                                        </p>
                                    </td>
                                    <td class="p-2 align-middle">
                                        <button
                                            type="button"
                                            class="inline-flex min-h-[40px] w-full max-w-[100px] items-center justify-center gap-1 rounded-lg border border-slate-300 bg-slate-50 px-2 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                            title="Xem chi tiết"
                                            @click="toggleDetails(item.id)"
                                        >
                                            <Icon icon="lucide:eye" class="h-3.5 w-3.5 shrink-0" />
                                            <span class="leading-none">Chi tiết</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expandedRowId === item.id" class="bg-slate-50/70 dark:bg-slate-800/40">
                                    <td colspan="7" class="px-4 py-4">
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    Thông tin hiện tại
                                                </p>
                                                <div class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                                                    <template v-if="requestChangeLines(item).length">
                                                        <p v-for="line in requestChangeLines(item)" :key="`cur-${item.id}-${line.key}`">
                                                            <span class="text-slate-400">{{ line.label }}:</span> {{ renderField(line.from) }}
                                                        </p>
                                                    </template>
                                                    <p v-else class="text-xs text-slate-500">Không có trường được liệt kê.</p>
                                                </div>
                                            </div>
                                            <div class="rounded-lg border border-blue-200 bg-blue-50/60 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">
                                                    Yêu cầu thay đổi
                                                </p>
                                                <div class="mt-2 space-y-1.5 text-sm text-slate-800 dark:text-slate-200">
                                                    <template v-if="requestChangeLines(item).length">
                                                        <p v-for="line in requestChangeLines(item)" :key="`req-${item.id}-${line.key}`">
                                                            <span class="text-slate-500 dark:text-slate-400">{{ line.label }}:</span>
                                                            {{ renderField(line.to) }}
                                                        </p>
                                                    </template>
                                                    <p v-else class="text-xs text-slate-500">Không có trường được liệt kê.</p>
                                                </div>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    Ảnh minh chứng
                                                </p>
                                                <a
                                                    v-if="item.proof_image_url"
                                                    :href="item.proof_image_url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="mt-2 inline-block max-h-64 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700"
                                                >
                                                    <img :src="item.proof_image_url" alt="Minh chứng" class="max-h-64 w-full object-contain" />
                                                </a>
                                                <p v-else class="mt-2 text-sm text-slate-500">—</p>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    Ghi chú duyệt
                                                </p>
                                                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ item.review_note || '—' }}</p>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    Người xử lý
                                                </p>
                                                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ item.reviewer?.name || '—' }}</p>
                                                <p v-if="item.reviewer?.email" class="mt-0.5 text-xs text-slate-500">{{ item.reviewer.email }}</p>
                                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                                    Duyệt lúc: {{ formatDateTime(item.reviewed_at) }}<br />
                                                    Áp dụng: {{ formatDateTime(item.applied_at) }}
                                                </p>
                                            </div>
                                            <div
                                                v-if="item.reason"
                                                class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900 md:col-span-2"
                                            >
                                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    Lý do gửi kèm
                                                </p>
                                                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ item.reason }}</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="shrink-0 border-t border-slate-100 bg-slate-50/80 px-2 py-2 dark:border-slate-800 dark:bg-slate-800/40">
                    <AdminPaginationBar
                        always-show
                        :current-page="Number(pageNum)"
                        :last-page="Number(lastPage)"
                        :disabled="loading"
                        @go-page="goPage"
                    />
                </div>
            </div>
        </div>
    </ReaderLayout>
</template>
