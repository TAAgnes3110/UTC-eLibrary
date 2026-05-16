<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue'
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue'
import { meDigitalOrdersApi } from '@/api/meDigitalOrders'
import { digitalAssetsApi } from '@/api/digitalAssets'
import { extractApiPaginator } from '@/utils/adminPagination'
import { toast } from '@/store/toast'

const PER_PAGE = 10

const loading = ref(false)
const summaryLoading = ref(false)
const rows = ref([])
const pageNum = ref(1)
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: PER_PAGE })
const summary = ref({
    total_orders: 0,
    paid_count: 0,
    pending_count: 0,
    total_spent_vnd: 0,
})

const filters = reactive({
    searchKeyword: '',
    status: '',
})

const detailOpen = ref(false)
const detailLoading = ref(false)
const detailRow = ref(null)
const detailData = ref(null)
const cancelDialogOpen = ref(false)
const cancelTarget = ref(null)
const cancelLoading = ref(false)
const pendingMaxAgeDays = ref(3)

const statusOptions = [
    { value: '', label: 'Tất cả trạng thái' },
    { value: 'pending', label: 'Chờ thanh toán' },
    { value: 'paid', label: 'Đã thanh toán' },
    { value: 'cancelled', label: 'Đã hủy' },
    { value: 'failed', label: 'Thanh toán thất bại' },
    { value: 'expired', label: 'Hết hạn' },
]

function formatDate(value) {
    if (!value) return '—'
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('vi-VN')
}

function formatDateTime(value) {
    if (!value) return '—'
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleString('vi-VN')
}

function formatVnd(n) {
    return `${Number(n || 0).toLocaleString('vi-VN')} đ`
}

function fulfillmentBadgeClass(row) {
    if (row?.status === 'paid') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
    if (row?.status === 'pending') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200'
    return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function paymentBadgeClass(row) {
    if (row?.status === 'paid') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
    if (row?.status === 'pending') return 'bg-orange-100 text-orange-900 dark:bg-orange-950/50 dark:text-orange-200'
    return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
}

async function loadSummary() {
    summaryLoading.value = true
    try {
        const payload = await meDigitalOrdersApi.summary()
        const data = payload?.data ?? payload
        summary.value = {
            total_orders: Number(data?.total_orders || 0),
            paid_count: Number(data?.paid_count || 0),
            pending_count: Number(data?.pending_count || 0),
            total_spent_vnd: Number(data?.total_spent_vnd || 0),
        }
    } catch {
        summary.value = { total_orders: 0, paid_count: 0, pending_count: 0, total_spent_vnd: 0 }
    } finally {
        summaryLoading.value = false
    }
}

async function loadOrders(resetPage = false) {
    if (resetPage) pageNum.value = 1
    loading.value = true
    try {
        const payload = await meDigitalOrdersApi.list({
            page: pageNum.value,
            per_page: PER_PAGE,
            search: filters.searchKeyword.trim() || undefined,
            status: filters.status || undefined,
            sort: 'newest',
        })
        const { items, meta: m } = extractApiPaginator(payload, PER_PAGE)
        rows.value = items
        meta.value = { current_page: m.current_page, last_page: m.last_page, total: m.total, per_page: m.per_page }
        pageNum.value = m.current_page
        const days = Number(items?.[0]?.pending_max_age_days)
        if (Number.isFinite(days) && days > 0) {
            pendingMaxAgeDays.value = days
        }
    } catch (e) {
        rows.value = []
        toast.error(e?.response?.data?.messages || 'Không tải được danh sách đơn hàng.', { title: 'Đơn hàng' })
    } finally {
        loading.value = false
    }
}

async function refreshAll() {
    await Promise.all([loadSummary(), loadOrders(true)])
}

watch(() => filters.status, () => {
    void loadOrders(true)
})

function goPage(p) {
    const n = Number(p)
    if (!Number.isFinite(n) || n < 1 || n > meta.value.last_page) return
    pageNum.value = n
    loadOrders(false)
}

function payOrderUrl(publicId) {
    try {
        return `${route('reader.services.digital-payment')}?order=${encodeURIComponent(publicId)}`
    } catch {
        return `/dich-vu/thanh-toan?order=${encodeURIComponent(publicId)}`
    }
}

function goPay(row) {
    if (!row?.can_pay || !row?.public_id) return
    router.visit(payOrderUrl(row.public_id))
}

function openCancelDialog(row) {
    if (!row?.can_cancel || !row?.public_id) return
    cancelTarget.value = row
    cancelDialogOpen.value = true
}

function closeCancelDialog() {
    cancelDialogOpen.value = false
    cancelTarget.value = null
}

async function confirmCancelOrder() {
    const publicId = cancelTarget.value?.public_id
    if (!publicId || cancelLoading.value) return
    cancelLoading.value = true
    try {
        await digitalAssetsApi.cancelOrder(publicId)
        toast.success('Đã hủy đơn hàng.', { title: 'Đơn hàng' })
        if (detailRow.value?.public_id === publicId) {
            closeDetail()
        }
        closeCancelDialog()
        await refreshAll()
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không hủy được đơn hàng.', { title: 'Đơn hàng' })
    } finally {
        cancelLoading.value = false
    }
}

function formatAutoRemoveHint(row) {
    if (!row?.auto_remove_at) return ''
    const d = new Date(row.auto_remove_at)
    if (Number.isNaN(d.getTime())) return ''
    return `Tự xóa nếu chưa thanh toán trước ${d.toLocaleString('vi-VN')}`
}

async function openDetail(row) {
    if (!row?.public_id) return
    detailRow.value = row
    detailOpen.value = true
    detailLoading.value = true
    detailData.value = null
    try {
        const payload = await digitalAssetsApi.orderStatus(row.public_id, { sync: row.status === 'pending' })
        detailData.value = payload?.data ?? payload
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không tải được chi tiết đơn.', { title: 'Đơn hàng' })
        detailOpen.value = false
    } finally {
        detailLoading.value = false
    }
}

function closeDetail() {
    detailOpen.value = false
    detailRow.value = null
    detailData.value = null
}

const detailItems = computed(() => {
    const items = detailData.value?.items
    return Array.isArray(items) ? items : []
})

const detailOrder = computed(() => detailData.value?.order ?? null)

onMounted(async () => {
    await refreshAll()
    try {
        const u = new URL(window.location.href)
        const openId = u.searchParams.get('order')?.trim()
        const justPaid = u.searchParams.get('paid') === '1'
        if (openId) {
            const row =
                rows.value.find((r) => String(r.public_id) === openId) ||
                { public_id: openId, status: justPaid ? 'paid' : 'pending', can_pay: !justPaid }
            await openDetail(row)
            if (justPaid) {
                toast.success('Thanh toán thành công. Xem chi tiết đơn bên dưới.', { title: 'Đơn hàng', duration: 8000 })
            }
            if (window.history?.replaceState) {
                const clean = route('reader.services.digital-orders')
                window.history.replaceState({}, '', clean)
            }
        }
    } catch {
        /* ignore */
    }
})
</script>

<template>
    <ReaderLayout>
        <Head title="Đơn hàng của tôi" />
        <div class="mx-auto max-w-6xl space-y-5 animate-in fade-in-50 duration-500">
            <div>
                <Link
                    :href="route('reader.profile')"
                    class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-400"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                    Quay lại tài khoản
                </Link>
            </div>

            <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg dark:border-slate-700/80 dark:bg-slate-900">
                <header class="border-b border-slate-100 bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 px-5 py-8 text-white sm:px-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Đơn hàng của tôi</h1>
                            <p class="mt-2 max-w-xl text-sm text-blue-100/90">
                                Theo dõi đơn mua tài liệu số (tải PDF) và xem lại tài liệu đã thanh toán.
                                Đơn chưa thanh toán quá {{ pendingMaxAgeDays }} ngày sẽ tự động bị xóa; bạn có thể hủy đơn bất cứ lúc nào.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 text-sm font-semibold backdrop-blur-sm transition hover:bg-white/20"
                            :disabled="loading || summaryLoading"
                            @click="refreshAll"
                        >
                            <Icon icon="lucide:refresh-cw" class="h-4 w-4" :class="{ 'animate-spin': loading || summaryLoading }" />
                            Làm mới
                        </button>
                    </div>
                </header>

                <div class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-800 sm:p-6">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/90 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tổng đơn</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ summary.total_orders }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/80 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800/80 dark:text-emerald-300/80">Đã thanh toán</p>
                        <p class="mt-1 text-2xl font-black text-emerald-800 dark:text-emerald-300">{{ summary.paid_count }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200/80 bg-amber-50/80 p-4 dark:border-amber-900/40 dark:bg-amber-950/30">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-900/70 dark:text-amber-200/80">Chờ thanh toán</p>
                        <p class="mt-1 text-2xl font-black text-amber-900 dark:text-amber-200">{{ summary.pending_count }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-200/80 bg-blue-50/80 p-4 dark:border-blue-900/40 dark:bg-blue-950/30">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-800/80 dark:text-blue-300/80">Tổng chi tiêu</p>
                        <p class="mt-1 text-xl font-black text-blue-900 dark:text-blue-200">{{ formatVnd(summary.total_spent_vnd) }}</p>
                    </div>
                </div>

                <div class="space-y-4 p-4 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div class="min-w-0 flex-1 sm:max-w-md">
                            <AdminFilterSearch
                                v-model="filters.searchKeyword"
                                placeholder="Tìm mã đơn, nội dung CK…"
                                @search="() => loadOrders(true)"
                            />
                        </div>
                        <label class="block shrink-0">
                            <span class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Trạng thái</span>
                            <select
                                v-model="filters.status"
                                class="min-h-[44px] w-full min-w-[12rem] rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                            >
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200/90 dark:border-slate-700">
                        <table class="min-w-[720px] w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">Mã đơn</th>
                                    <th class="px-4 py-3">Tài liệu</th>
                                    <th class="px-4 py-3 text-right">Tổng tiền</th>
                                    <th class="px-4 py-3">Trạng thái</th>
                                    <th class="px-4 py-3">Thanh toán</th>
                                    <th class="px-4 py-3">Ngày đặt</th>
                                    <th class="px-4 py-3 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="loading">
                                    <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                        <Icon icon="lucide:loader-2" class="mx-auto h-8 w-8 animate-spin" />
                                        <p class="mt-2">Đang tải…</p>
                                    </td>
                                </tr>
                                <tr v-else-if="rows.length === 0">
                                    <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                        Chưa có đơn hàng tài liệu số nào.
                                    </td>
                                </tr>
                                <tr
                                    v-for="row in rows"
                                    :key="row.public_id"
                                    class="bg-white transition hover:bg-slate-50/80 dark:bg-slate-900 dark:hover:bg-slate-800/50"
                                >
                                    <td class="max-w-[10rem] px-4 py-3">
                                        <p class="truncate font-mono text-xs font-bold text-slate-900 dark:text-white" :title="row.public_id">
                                            #{{ row.public_id }}
                                        </p>
                                    </td>
                                    <td class="max-w-[14rem] px-4 py-3">
                                        <p class="line-clamp-2 font-medium text-slate-900 dark:text-white">{{ row.product_summary }}</p>
                                        <p v-if="row.item_count > 1" class="mt-0.5 text-xs text-slate-500">{{ row.item_count }} tài liệu</p>
                                        <p v-if="row.status === 'pending' && formatAutoRemoveHint(row)" class="mt-1 text-[11px] text-amber-700 dark:text-amber-300">
                                            {{ formatAutoRemoveHint(row) }}
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-blue-800 dark:text-blue-300">
                                        {{ formatVnd(row.total_vnd) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold" :class="fulfillmentBadgeClass(row)">
                                            {{ row.fulfillment_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold" :class="paymentBadgeClass(row)">
                                            {{ row.payment_label }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-400">{{ formatDate(row.created_at) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[40px] items-center gap-1 rounded-lg px-2 text-xs font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                                                @click="openDetail(row)"
                                            >
                                                <Icon icon="lucide:eye" class="h-4 w-4" />
                                                Chi tiết
                                            </button>
                                            <button
                                                v-if="row.can_pay"
                                                type="button"
                                                class="inline-flex min-h-[40px] items-center gap-1 rounded-lg bg-blue-700 px-3 text-xs font-bold text-white hover:bg-blue-800 dark:bg-blue-600"
                                                @click="goPay(row)"
                                            >
                                                Thanh toán
                                                <Icon icon="lucide:arrow-right" class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                v-if="row.can_cancel"
                                                type="button"
                                                class="inline-flex min-h-[40px] items-center gap-1 rounded-lg border border-rose-200 px-2 text-xs font-bold text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-950/40"
                                                @click="openCancelDialog(row)"
                                            >
                                                <Icon icon="lucide:trash-2" class="h-4 w-4" />
                                                Hủy
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <AdminPaginationBar
                        v-if="meta.total > 0 || loading"
                        always-show
                        :current-page="meta.current_page"
                        :last-page="meta.last_page"
                        :disabled="loading"
                        @go-page="goPage"
                    />
                </div>
            </article>
        </div>

        <Teleport to="body">
            <div
                v-if="detailOpen"
                class="fixed inset-0 z-[120] flex items-end justify-center bg-black/50 p-4 sm:items-center"
                role="dialog"
                aria-modal="true"
                aria-labelledby="order-detail-title"
                @click.self="closeDetail"
            >
                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900" @click.stop>
                    <div class="flex items-start justify-between gap-3">
                        <h2 id="order-detail-title" class="text-lg font-bold text-slate-900 dark:text-white">Chi tiết đơn hàng</h2>
                        <button
                            type="button"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                            aria-label="Đóng"
                            @click="closeDetail"
                        >
                            <Icon icon="lucide:x" class="h-5 w-5" />
                        </button>
                    </div>

                    <div v-if="detailLoading" class="py-12 text-center text-slate-500">
                        <Icon icon="lucide:loader-2" class="mx-auto h-8 w-8 animate-spin" />
                    </div>

                    <template v-else-if="detailOrder">
                        <dl class="mt-4 space-y-2 text-sm">
                            <div>
                                <dt class="text-slate-500 dark:text-slate-400">Mã đơn</dt>
                                <dd class="font-mono font-bold text-slate-900 dark:text-white">#{{ detailOrder.public_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500 dark:text-slate-400">Trạng thái</dt>
                                <dd class="font-semibold">{{ detailRow?.payment_label || detailOrder.status }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500 dark:text-slate-400">Ngày đặt</dt>
                                <dd>{{ formatDateTime(detailOrder.created_at) }}</dd>
                            </div>
                            <div v-if="detailOrder.paid_at">
                                <dt class="text-slate-500 dark:text-slate-400">Thanh toán lúc</dt>
                                <dd>{{ formatDateTime(detailOrder.paid_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500 dark:text-slate-400">Tổng</dt>
                                <dd class="text-lg font-black text-blue-800 dark:text-blue-300">{{ formatVnd(detailOrder.amount_vnd) }}</dd>
                            </div>
                            <div v-if="detailOrder.merchant_reference">
                                <dt class="text-slate-500 dark:text-slate-400">Nội dung CK</dt>
                                <dd class="break-all font-mono text-xs">{{ detailOrder.merchant_reference }}</dd>
                            </div>
                        </dl>

                        <h3 class="mt-6 text-sm font-bold text-slate-900 dark:text-white">Tài liệu ({{ detailItems.length }})</h3>
                        <ul class="mt-2 space-y-2">
                            <li
                                v-for="item in detailItems"
                                :key="item.digital_asset_id"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/50"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ item.title }}</p>
                                    <p class="text-xs text-slate-500">{{ formatVnd(item.line_total_vnd) }}</p>
                                </div>
                                <Link
                                    v-if="item.book_id && detailOrder.status === 'paid'"
                                    :href="route('reader.catalog.show', { book: item.book_id })"
                                    class="inline-flex min-h-[40px] shrink-0 items-center rounded-lg bg-emerald-700 px-3 text-xs font-bold text-white hover:bg-emerald-800"
                                >
                                    Mở sách
                                </Link>
                            </li>
                        </ul>

                        <p
                            v-if="detailRow?.status === 'pending' && formatAutoRemoveHint(detailRow)"
                            class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
                        >
                            {{ formatAutoRemoveHint(detailRow) }}
                        </p>

                        <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                            <button
                                v-if="detailRow?.can_pay"
                                type="button"
                                class="inline-flex min-h-[48px] flex-1 items-center justify-center rounded-xl bg-blue-700 text-sm font-bold text-white hover:bg-blue-800"
                                @click="goPay(detailRow); closeDetail()"
                            >
                                Tiếp tục thanh toán
                            </button>
                            <button
                                v-if="detailRow?.can_cancel"
                                type="button"
                                class="inline-flex min-h-[48px] flex-1 items-center justify-center rounded-xl border border-rose-300 text-sm font-bold text-rose-800 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-200 dark:hover:bg-rose-950/40"
                                @click="openCancelDialog(detailRow)"
                            >
                                Hủy đơn hàng
                            </button>
                            <Link
                                v-if="detailOrder.status === 'paid'"
                                :href="route('reader.catalog')"
                                class="inline-flex min-h-[48px] flex-1 items-center justify-center rounded-xl border border-slate-300 text-sm font-bold text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-100"
                                @click="closeDetail"
                            >
                                Tra cứu sách
                            </Link>
                            <Link
                                :href="route('reader.services.digital-orders')"
                                class="inline-flex min-h-[48px] items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 dark:border-slate-600 dark:text-slate-200"
                                @click="closeDetail"
                            >
                                Đóng
                            </Link>
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="cancelDialogOpen"
                class="fixed inset-0 z-[130] flex items-end justify-center bg-black/50 p-4 sm:items-center"
                role="dialog"
                aria-modal="true"
                aria-labelledby="cancel-order-title"
                @click.self="closeCancelDialog"
            >
                <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900" @click.stop>
                    <h2 id="cancel-order-title" class="text-lg font-bold text-slate-900 dark:text-white">Hủy đơn hàng?</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        Đơn chờ thanh toán sẽ bị hủy. Bạn có thể tạo đơn mới sau nếu vẫn muốn mua tài liệu.
                    </p>
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="inline-flex min-h-[48px] w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 sm:w-auto"
                            :disabled="cancelLoading"
                            @click="closeCancelDialog"
                        >
                            Đóng
                        </button>
                        <button
                            type="button"
                            class="inline-flex min-h-[48px] w-full items-center justify-center rounded-xl bg-rose-700 px-4 text-sm font-bold text-white hover:bg-rose-800 disabled:opacity-60 dark:bg-rose-600 sm:w-auto"
                            :disabled="cancelLoading"
                            @click="confirmCancelOrder"
                        >
                            <Icon v-if="cancelLoading" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                            Xác nhận hủy
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </ReaderLayout>
</template>
