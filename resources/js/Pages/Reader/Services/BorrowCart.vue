<script setup>
import { computed, onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { meBorrowRequestsApi } from '@/api/meBorrowRequests'
import { toast } from '@/store/toast'

const CART_KEY = 'reader_borrow_cart_v1'
const loading = ref(false)
const submitting = ref(false)
const rows = ref([])
const selectedIds = ref([])
const loanType = ref('home')
const requestedDueDate = ref('')
const todayIso = computed(() => new Date().toISOString().slice(0, 10))

function extractApiErrorMessage(error, fallback) {
    const data = error?.response?.data || {}
    const msg = typeof data?.messages === 'string' && data.messages.trim() ? data.messages.trim() : ''
    if (msg) return msg

    const message = typeof data?.message === 'string' && data.message.trim() ? data.message.trim() : ''
    const errors = data?.errors
    if (errors && typeof errors === 'object') {
        const firstFieldErrors = Object.values(errors).find((list) => Array.isArray(list) && list.length > 0)
        if (Array.isArray(firstFieldErrors) && typeof firstFieldErrors[0] === 'string' && firstFieldErrors[0].trim()) {
            return firstFieldErrors[0].trim()
        }
    }

    if (message) return message
    return fallback
}

function normalizeBorrowRequestErrorText(raw) {
    let msg = String(raw || '').trim()
    if (!msg) return 'Có lỗi xảy ra, vui lòng thử lại.'

    msg = msg.replace(
        /Trường\s+Ngày hẹn mượn\s+phải là thời gian bắt đầu sau hoặc đúng bằng\s+today\.?/gi,
        'Ngày hẹn mượn không được nhỏ hơn ngày hiện tại'
    )
    msg = msg.replace(
        /Trường\s+Ngày hẹn mượn\s+phải là thời gian bắt đầu sau hoặc đúng bằng\s+hôm nay\.?/gi,
        'Ngày hẹn mượn không được nhỏ hơn ngày hiện tại'
    )
    msg = msg.replace(
        /Trường\s+Ngày hẹn trả\s+phải là thời gian bắt đầu sau hoặc đúng bằng\s+today\.?/gi,
        'Ngày hẹn trả không được nhỏ hơn ngày hiện tại'
    )
    msg = msg.replace(
        /Trường\s+Ngày hẹn trả\s+phải là thời gian bắt đầu sau hoặc đúng bằng\s+hôm nay\.?/gi,
        'Ngày hẹn trả không được nhỏ hơn ngày hiện tại'
    )
    msg = msg.replace(/requested due date/gi, 'Ngày hẹn trả')
    msg = msg.replace(/requested loan date/gi, 'Ngày hẹn mượn')
    msg = msg.replace(/due date/gi, 'Ngày hẹn trả')
    msg = msg.replace(/loan date/gi, 'Ngày hẹn mượn')
    msg = msg.replace(/today/gi, 'ngày hiện tại')
    msg = msg.replace(/field/gi, 'trường thông tin')
    msg = msg.replace(/must be/gi, 'phải')
    msg = msg.replace(/must/gi, 'phải')
    msg = msg.replace(/greater than or equal to/gi, 'không được nhỏ hơn')
    msg = msg.replace(/less than or equal to/gi, 'không được lớn hơn')
    msg = msg.replace(/after or equal to today/gi, 'không được nhỏ hơn ngày hiện tại')
    msg = msg.replace(/after or equal to requested loan date/gi, 'không được nhỏ hơn ngày hẹn mượn')
    msg = msg.replace(/before or equal to/gi, 'không được lớn hơn')
    msg = msg.replace(/The\s+/gi, '')
    msg = msg.replace(/^Trường\s+/i, '')
    msg = msg.replace(/\.$/, '')

    if (/\b(requested|loan|due|today|after|before|must|field)\b/i.test(msg)) {
        return 'Dữ liệu chưa hợp lệ. Vui lòng kiểm tra lại thông tin phiếu mượn.'
    }

    return msg
}

function loadCartRaw() {
    try {
        const raw = JSON.parse(localStorage.getItem(CART_KEY) || '[]')
        return Array.isArray(raw) ? raw : []
    } catch {
        return []
    }
}

function saveCartRaw(items) {
    localStorage.setItem(CART_KEY, JSON.stringify(items))
}

function syncQtyToStorage() {
    const items = rows.value.map((r) => ({
        book_id: Number(r.id),
        quantity: Math.max(1, Number(r.quantity || 1)),
    }))
    saveCartRaw(items)
}

async function refreshPreview() {
    const cart = loadCartRaw()
    const ids = cart.map((x) => Number(x.book_id)).filter((x) => Number.isInteger(x) && x > 0)
    if (ids.length === 0) {
        rows.value = []
        selectedIds.value = []
        return
    }

    loading.value = true
    try {
        const payload = await meBorrowRequestsApi.preview(ids)
        const byId = new Map(cart.map((x) => [Number(x.book_id), Math.max(1, Number(x.quantity || 1))]))
        rows.value = (payload?.data?.items || []).map((b) => ({
            ...b,
            quantity: byId.get(Number(b.id)) || 1,
        }))
        selectedIds.value = []
        syncQtyToStorage()
    } catch (e) {
        toast.error(extractApiErrorMessage(e, 'Không tải được giỏ mượn.'), { title: 'Giỏ mượn' })
    } finally {
        loading.value = false
    }
}

function removeItem(id) {
    rows.value = rows.value.filter((r) => Number(r.id) !== Number(id))
    selectedIds.value = selectedIds.value.filter((x) => Number(x) !== Number(id))
    syncQtyToStorage()
}

function removeSelectedItems() {
    if (selectedIds.value.length === 0) {
        toast.warn('Vui lòng chọn ít nhất một sách để xóa.', { title: 'Giỏ mượn' })
        return
    }
    const selectedSet = new Set(selectedIds.value.map((x) => Number(x)))
    const removedCount = selectedSet.size
    rows.value = rows.value.filter((r) => !selectedSet.has(Number(r.id)))
    selectedIds.value = []
    syncQtyToStorage()
    toast.success(`Đã xóa ${removedCount} sách đã chọn khỏi giỏ mượn.`, { title: 'Giỏ mượn' })
}

function setQty(id, value) {
    const row = rows.value.find((r) => Number(r.id) === Number(id))
    if (!row) return
    const max = Math.max(1, Number(row.available_for_borrow || 1))
    row.quantity = Math.max(1, Math.min(max, Number(value || 1)))
    syncQtyToStorage()
}

function toggleSelect(id) {
    const n = Number(id)
    if (selectedIds.value.includes(n)) {
        selectedIds.value = selectedIds.value.filter((x) => x !== n)
    } else {
        selectedIds.value.push(n)
    }
}

const selectedRows = computed(() => rows.value.filter((r) => selectedIds.value.includes(Number(r.id))))
const totalQty = computed(() => selectedRows.value.reduce((sum, r) => sum + Math.max(1, Number(r.quantity || 1)), 0))
const selectableIds = computed(() =>
    rows.value
        .filter((r) => Number(r.available_for_borrow || 0) > 0)
        .map((r) => Number(r.id))
)
const selectedCount = computed(() => selectedIds.value.length)

function toggleSelectAll() {
    if (selectedIds.value.length > 0) {
        selectedIds.value = []
        return
    }
    selectedIds.value = [...selectableIds.value]
}

function adjustQty(id, delta) {
    const row = rows.value.find((r) => Number(r.id) === Number(id))
    if (!row) return
    const max = Math.max(1, Number(row.available_for_borrow || 1))
    const current = Math.max(1, Number(row.quantity || 1))
    row.quantity = Math.max(1, Math.min(max, current + delta))
    syncQtyToStorage()
}

async function submitBorrowRequest() {
    if (selectedRows.value.length === 0 || submitting.value) return
    if (loanType.value === 'home' && !requestedDueDate.value) {
        toast.warn('Vui lòng chọn ngày trả dự kiến khi mượn về nhà.', { title: 'Giỏ mượn' })
        return
    }
    const valid = selectedRows.value.filter((r) => Number(r.available_for_borrow || 0) > 0)
    if (valid.length === 0) {
        toast.warn('Không có sách khả dụng để gửi yêu cầu.', { title: 'Giỏ mượn' })
        return
    }

    submitting.value = true
    try {
        await meBorrowRequestsApi.create({
            loan_type: loanType.value,
            book_ids: valid.map((x) => Number(x.id)),
            quantity: valid.map((x) => Math.max(1, Number(x.quantity || 1))),
            requested_due_date: loanType.value === 'home' ? requestedDueDate.value : undefined,
        })
        const usedIds = new Set(valid.map((x) => Number(x.id)))
        rows.value = rows.value.filter((r) => !usedIds.has(Number(r.id)))
        selectedIds.value = []
        syncQtyToStorage()
        toast.success('Đã gửi yêu cầu mượn từ giỏ mượn.', { title: 'Giỏ mượn' })
    } catch (e) {
        const detail = extractApiErrorMessage(e, 'Không tạo được yêu cầu mượn.')
        const normalized = detail.toLowerCase()
        if (normalized.includes('thẻ thư viện')) {
            toast.error(normalizeBorrowRequestErrorText(detail), {
                title: 'Tạo phiếu không thành công',
            })
            return
        }
        toast.error(normalizeBorrowRequestErrorText(detail), {
            title: 'Tạo phiếu không thành công',
        })
    } finally {
        submitting.value = false
    }
}

onMounted(refreshPreview)
</script>

<template>
    <ReaderLayout>
        <Head title="Giỏ mượn" />
        <div class="mx-auto w-full max-w-7xl space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100">Giỏ mượn</h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Quản lý sách đã chọn và gửi yêu cầu mượn nhanh.
                    </p>
                </div>
                <Link
                    :href="route('reader.catalog')"
                    class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300"
                >
                    <Icon icon="lucide:arrow-up-right" class="h-4 w-4" />
                    Tra cứu sách
                </Link>
            </div>

            <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">Đang tải giỏ mượn...</div>
            <div v-else-if="rows.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-600 dark:text-slate-300">Giỏ mượn đang trống.</p>
                <Link :href="route('reader.catalog')" class="mt-4 inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Đi tới tra cứu sách</Link>
            </div>

            <div v-else class="grid gap-4 lg:grid-cols-[1fr_320px]">
                <section class="space-y-2">
                    <div class="flex items-center justify-end">
                        <button
                            type="button"
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-50 disabled:pointer-events-none dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200 dark:hover:bg-rose-900/40"
                            :disabled="selectedIds.length === 0"
                            @click="removeSelectedItems"
                        >
                            <Icon icon="lucide:trash-2" class="h-4 w-4" />
                            Xóa mục đã chọn ({{ selectedIds.length }})
                        </button>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                        <div class="grid grid-cols-[1fr_220px_140px_44px] items-center gap-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <label class="flex items-center gap-2 normal-case text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <span class="admin-table-checkbox-wrap">
                                    <input type="checkbox" :checked="selectedCount > 0" class="admin-table-checkbox" @change="toggleSelectAll" />
                                </span>
                                Đã chọn {{ selectedCount }} sách
                            </label>
                            <span class="text-left normal-case">Vị trí kho/tủ</span>
                            <span class="text-center normal-case">Số lượng</span>
                            <span class="text-center normal-case">Xóa</span>
                        </div>
                    </div>

                    <div
                        v-for="row in rows"
                        :key="row.id"
                        class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div class="grid grid-cols-[1fr_220px_170px_44px] items-center gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="admin-table-checkbox-wrap">
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.includes(Number(row.id))"
                                        :disabled="Number(row.available_for_borrow || 0) <= 0"
                                        class="admin-table-checkbox"
                                        @change="toggleSelect(row.id)"
                                    />
                                </span>
                                <Link
                                    :href="route('reader.catalog.show', { book: row.id })"
                                    class="flex min-w-0 items-center gap-3 rounded-lg px-1 py-1 transition hover:bg-slate-50 dark:hover:bg-slate-800"
                                >
                                    <div class="h-16 w-12 shrink-0 overflow-hidden rounded border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
                                        <img
                                            v-if="row.cover_image"
                                            :src="row.cover_image"
                                            :alt="row.title"
                                            loading="lazy"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                            <Icon icon="lucide:book-open" class="h-4 w-4" />
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900 hover:text-blue-700 dark:text-slate-100 dark:hover:text-blue-300">{{ row.title }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Số lượng sách khả dụng: {{ Number(row.available_for_borrow || 0) > 0 ? row.available_for_borrow : 'Hết sách' }}</p>
                                    </div>
                                </Link>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Kho: {{ row.warehouse_name || row.warehouse_code || '—' }}
                                </p>
                                <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">
                                    Tủ: {{ row.cabinet || '—' }}
                                </p>
                            </div>

                            <div class="inline-flex h-12 items-center justify-center overflow-hidden rounded-2xl border border-slate-300/80 bg-white shadow-sm dark:border-slate-600 dark:bg-slate-900">
                                <button
                                    type="button"
                                    class="inline-flex h-full w-12 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                    :disabled="Number(row.quantity || 1) <= 1"
                                    @click="adjustQty(row.id, -1)"
                                >
                                    -
                                </button>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    :value="row.quantity"
                                    class="h-full w-14 border-x border-slate-200 bg-transparent text-center text-base font-bold text-slate-900 [appearance:textfield] dark:border-slate-700 dark:text-slate-100"
                                    @input="setQty(row.id, $event.target.value)"
                                />
                                <button
                                    type="button"
                                    class="inline-flex h-full w-12 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                    :disabled="Number(row.quantity || 1) >= Math.max(1, Number(row.available_for_borrow || 1))"
                                    @click="adjustQty(row.id, 1)"
                                >
                                    +
                                </button>
                            </div>

                            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30" @click="removeItem(row.id)">
                                <Icon icon="lucide:trash-2" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </section>

                <aside class="space-y-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Tóm tắt yêu cầu mượn</h3>
                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-600 dark:bg-slate-800/80">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                Hình thức mượn
                            </p>
                            <div class="mt-2 grid grid-cols-1 gap-2">
                                <label class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <input v-model="loanType" type="radio" value="home" />
                                    Mượn về nhà
                                </label>
                                <label class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <input v-model="loanType" type="radio" value="onsite" />
                                    Đọc tại chỗ
                                </label>
                            </div>
                            <div v-if="loanType === 'home'" class="mt-3">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-200">
                                    Ngày trả dự kiến
                                </label>
                                <input
                                    v-model="requestedDueDate"
                                    type="date"
                                    :min="todayIso"
                                    class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-100 dark:[color-scheme:dark] dark:focus:border-emerald-400 dark:focus:ring-emerald-900/50"
                                />
                            </div>
                        </div>
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                <span>Sách đã chọn</span>
                                <span class="font-semibold">{{ selectedRows.length }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                <span>Tổng số lượng</span>
                                <span class="font-semibold">{{ totalQty }}</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-500 disabled:opacity-60"
                            :disabled="selectedRows.length === 0 || submitting"
                            @click="submitBorrowRequest"
                        >
                            {{ submitting ? 'Đang gửi...' : 'Gửi yêu cầu mượn' }}
                        </button>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                        Thủ thư sẽ duyệt yêu cầu theo số lượng khả dụng thực tế tại thời điểm xử lý.
                    </div>
                </aside>
            </div>
        </div>
    </ReaderLayout>
</template>

