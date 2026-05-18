<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch, reactive } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { meBorrowRequestsApi } from '@/api/meBorrowRequests'
import { toast } from '@/store/toast'
import { useImageFallback } from '@/composables/useImageFallback'
import { digitalAssetsApi } from '@/api/digitalAssets'
import { digitalPurchaseCartApi } from '@/api/digitalPurchaseCart'
import {
    READER_BORROW_CART_KEY as CART_KEY,
    READER_DIGITAL_PURCHASE_CART_KEY,
    clearDigitalBuyNowSession,
    readDigitalBuyNowRow,
    notifyReaderCartUpdated,
    READER_CART_UPDATED_EVENT,
} from '@/config/readerCartKeys'

const page = usePage()

const props = defineProps({
    borrow_permissions: { type: Object, default: null },
    cart_tab: { type: String, default: 'borrow' },
    /** SĐT trên thẻ thư viện (khi `users.phone` trống vẫn điền được form thanh toán). */
    library_card_phone: { type: String, default: '' },
    /** SSR giỏ thanh toán khi `?tab=purchase` — tránh chờ API lần đầu. */
    digital_purchase_cart_items: { type: Array, default: null },
    /** Trang `/dich-vu/thanh-toan` — chỉ luồng thanh toán, không hiện giỏ mượn / tab giỏ. */
    payment_checkout_only: { type: Boolean, default: false },
})

const activeTab = computed(() => {
    if (props.payment_checkout_only) return 'purchase'
    return props.cart_tab === 'purchase' ? 'purchase' : 'borrow'
})
/** Chỉ tải giỏ thanh toán DB khi đang tab «Thanh toán» — tránh gọi API / migrate khi chỉ xem tab mượn. */
function isDigitalPurchaseTabActive() {
    return props.payment_checkout_only || props.cart_tab === 'purchase'
}

const bookCartBaseUrl = () => {
    try { return route('reader.services.book-cart') } catch { return '/dich-vu/gio-sach' }
}
const digitalPaymentBaseUrl = () => {
    try { return route('reader.services.digital-payment') } catch { return '/dich-vu/thanh-toan' }
}
const catalogBrowseUrl = () => {
    try { return route('reader.catalog') } catch { return '/tra-cuu-sach' }
}
const pageTitle = computed(() => {
    if (props.payment_checkout_only || (activeTab.value === 'purchase' && purchaseCartPhase.value === 'checkout')) {
        return 'Thanh toán tài liệu số'
    }
    return activeTab.value === 'borrow' ? 'Giỏ mượn' : 'Giỏ sách'
})

const canBorrowHome = computed(() => {
    const p = props.borrow_permissions
    return !p || Boolean(p.allow_home)
})
const canBorrowOnsite = computed(() => {
    const p = props.borrow_permissions
    return !p || Boolean(p.allow_onsite)
})

function syncLoanTypeToPermissions() {
    const p = props.borrow_permissions
    if (!p) return
    if (!p.allow_home && p.allow_onsite) loanType.value = 'onsite'
    else if (p.allow_home && !p.allow_onsite) loanType.value = 'home'
}

watch(
    () => [props.borrow_permissions?.allow_home, props.borrow_permissions?.allow_onsite],
    () => syncLoanTypeToPermissions(),
    { immediate: true },
)

const loading = ref(false)
const submitting = ref(false)
const rows = ref([])
const selectedIds = ref([])
const loanType = ref('home')
const requestedDueDate = ref('')
const todayIso = computed(() => new Date().toISOString().slice(0, 10))
const maxDueIso = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() + 1)
    return d.toISOString().slice(0, 10)
})

function extractApiErrorMessage(error, fallback) {
    const data = error?.response?.data || {}
    const msg = typeof data?.messages === 'string' && data.messages.trim() ? data.messages.trim() : ''
    if (msg) return msg
    const message = typeof data?.message === 'string' && data.message.trim() ? data.message.trim() : ''
    const errors = data?.errors
    if (errors && typeof errors === 'object') {
        const firstFieldErrors = Object.values(errors).find((list) => Array.isArray(list) && list.length > 0)
        if (Array.isArray(firstFieldErrors) && typeof firstFieldErrors[0] === 'string') return firstFieldErrors[0].trim()
    }
    return message || fallback
}

function normalizeBorrowRequestErrorText(raw) {
    let msg = String(raw || '').trim()
    if (!msg) return 'Có lỗi xảy ra, vui lòng thử lại.'
    msg = msg.replace(/Trường\s+Ngày hẹn mượn\s+phải là thời gian bắt đầu sau hoặc đúng bằng\s+today\.?/gi, 'Ngày hẹn mượn không được nhỏ hơn ngày hiện tại')
    msg = msg.replace(/Trường\s+Ngày hẹn trả\s+phải là thời gian bắt đầu sau hoặc đúng bằng\s+today\.?/gi, 'Ngày hẹn trả không được nhỏ hơn ngày hiện tại')
    msg = msg.replace(/requested due date/gi, 'Ngày hẹn trả').replace(/requested loan date/gi, 'Ngày hẹn mượn')
    msg = msg.replace(/today/gi, 'ngày hiện tại').replace(/The\s+/gi, '')
    msg = msg.replace(/^Trường\s+/i, '').replace(/\.$/, '')
    return msg
}

// ── Borrow cart ──
function loadCartRaw() {
    try { const raw = JSON.parse(localStorage.getItem(CART_KEY) || '[]'); return Array.isArray(raw) ? raw : [] } catch { return [] }
}
/** `silent`: không dispatch `reader-cart-updated` — tránh vòng refreshPreview ↔ notify khiến số lượng nhảy / gọi API lặp */
function saveCartRaw(items, options = {}) {
    const silent = Boolean(options.silent)
    localStorage.setItem(CART_KEY, JSON.stringify(items))
    if (!silent) notifyReaderCartUpdated('borrow')
}
function syncQtyToStorage(options = {}) {
    saveCartRaw(
        rows.value.map((r) => ({ book_id: Number(r.id), quantity: Math.max(1, Number(r.quantity || 1)) })),
        options,
    )
}

const BORROW_CART_SYNC_DEBOUNCE_MS = 400
let borrowCartSyncDebounceTimer = null
function scheduleSyncBorrowCartDebounced() {
    if (borrowCartSyncDebounceTimer) clearTimeout(borrowCartSyncDebounceTimer)
    borrowCartSyncDebounceTimer = setTimeout(() => {
        borrowCartSyncDebounceTimer = null
        syncQtyToStorage()
    }, BORROW_CART_SYNC_DEBOUNCE_MS)
}
function flushBorrowCartSyncDebounced() {
    if (!borrowCartSyncDebounceTimer) return
    clearTimeout(borrowCartSyncDebounceTimer)
    borrowCartSyncDebounceTimer = null
    syncQtyToStorage()
}

let borrowPreviewRequestId = 0
async function refreshPreview() {
    flushBorrowCartSyncDebounced()
    const cart = loadCartRaw()
    const ids = cart.map((x) => Number(x.book_id)).filter((x) => Number.isInteger(x) && x > 0)
    if (ids.length === 0) {
        rows.value = []
        selectedIds.value = []
        loading.value = false
        return
    }
    const reqId = ++borrowPreviewRequestId
    loading.value = true
    try {
        const payload = await meBorrowRequestsApi.preview(ids)
        if (reqId !== borrowPreviewRequestId) return
        const byId = new Map(cart.map((x) => [Number(x.book_id), Math.max(1, Number(x.quantity || 1))]))
        const newRows = (payload?.data?.items || []).map((b) => ({ ...b, quantity: byId.get(Number(b.id)) || 1 }))
        rows.value = newRows
        const allowed = new Set(newRows.filter((r) => Number(r.available_for_borrow || 0) > 0).map((r) => Number(r.id)))
        const prev = new Set(selectedIds.value.map(Number))
        selectedIds.value = [...prev].filter((id) => allowed.has(id))
        syncQtyToStorage({ silent: true })
    } catch (e) {
        if (reqId === borrowPreviewRequestId) {
            toast.error(extractApiErrorMessage(e, 'Không tải được danh sách mượn.'), { title: 'Giỏ sách' })
        }
    } finally {
        if (reqId === borrowPreviewRequestId) loading.value = false
    }
}
function removeItem(id) {
    flushBorrowCartSyncDebounced()
    rows.value = rows.value.filter((r) => Number(r.id) !== Number(id))
    selectedIds.value = selectedIds.value.filter((x) => Number(x) !== Number(id))
    syncQtyToStorage()
}

function removeSelectedItems() {
    if (selectedIds.value.length === 0) { toast.warn('Vui lòng chọn ít nhất một sách để xóa.', { title: 'Giỏ sách' }); return }
    flushBorrowCartSyncDebounced()
    const selectedSet = new Set(selectedIds.value.map((x) => Number(x)))
    const removedCount = selectedSet.size
    rows.value = rows.value.filter((r) => !selectedSet.has(Number(r.id)))
    selectedIds.value = []
    syncQtyToStorage()
    toast.success(`Đã xóa ${removedCount} sách đã chọn khỏi mục mượn.`, { title: 'Giỏ sách' })
}
function setQty(id, value) {
    const row = rows.value.find((r) => Number(r.id) === Number(id))
    if (!row) return
    const max = Math.max(1, Number(row.available_for_borrow || 1))
    row.quantity = Math.max(1, Math.min(max, Number(value || 1)))
    scheduleSyncBorrowCartDebounced()
}
function setBorrowRowSelected(id, checked) {
    const n = Number(id)
    if (!Number.isFinite(n)) return
    if (checked) {
        if (!selectedIds.value.includes(n)) selectedIds.value = [...selectedIds.value, n]
    } else {
        selectedIds.value = selectedIds.value.filter((x) => Number(x) !== n)
    }
}
const selectedRows = computed(() => rows.value.filter((r) => selectedIds.value.includes(Number(r.id))))
const totalQty = computed(() => selectedRows.value.reduce((sum, r) => sum + Math.max(1, Number(r.quantity || 1)), 0))
const selectableIds = computed(() => rows.value.filter((r) => Number(r.available_for_borrow || 0) > 0).map((r) => Number(r.id)))
const selectedCount = computed(() => selectedIds.value.length)
/** Đúng trạng thái «chọn tất cả» — tránh :checked theo selectedCount>0 gây double-toggle / tick nhầm */
const allBorrowSelectableSelected = computed(
    () => selectableIds.value.length > 0 && selectableIds.value.every((sid) => selectedIds.value.includes(Number(sid))),
)
function setBorrowSelectAll(checked) {
    if (checked) selectedIds.value = [...selectableIds.value]
    else selectedIds.value = []
}
function adjustQty(id, delta) {
    flushBorrowCartSyncDebounced()
    const row = rows.value.find((r) => Number(r.id) === Number(id))
    if (!row) return
    const max = Math.max(1, Number(row.available_for_borrow || 1))
    const current = Math.max(1, Number(row.quantity || 1))
    row.quantity = Math.max(1, Math.min(max, current + delta))
    syncQtyToStorage()
}
function validateCartBorrowClient() {
    const p = props.borrow_permissions
    if (p && loanType.value === 'home' && !p.allow_home) return 'Theo quy định thư viện UTC, loại thẻ của bạn chỉ được đăng ký đọc tại chỗ.'
    if (p && loanType.value === 'onsite' && !p.allow_onsite) return 'Hình thức đọc tại chỗ không áp dụng với loại thẻ của bạn.'
    if (loanType.value === 'home' && !requestedDueDate.value) return 'Vui lòng chọn ngày trả dự kiến khi mượn về nhà.'
    if (loanType.value === 'home' && String(requestedDueDate.value) > maxDueIso.value) return 'Ngày trả dự kiến không được quá một năm.'
    return null
}
async function submitBorrowRequest() {
    if (submitting.value) return
    if (selectedRows.value.length === 0) {
        toast.warn('Vui lòng chọn ít nhất một sách trong danh sách để gửi yêu cầu.', { title: 'Giỏ sách' })
        return
    }
    const cartErr = validateCartBorrowClient()
    if (cartErr) {
        toast.warn(cartErr, { title: 'Giỏ sách' })
        return
    }
    const valid = selectedRows.value.filter((r) => Number(r.available_for_borrow || 0) > 0)
    if (valid.length === 0) {
        toast.warn('Không có sách khả dụng trong các mục đã chọn.', { title: 'Giỏ sách' })
        return
    }
    submitting.value = true
    try {
        flushBorrowCartSyncDebounced()
        await meBorrowRequestsApi.create({
            loan_type: loanType.value,
            book_ids: valid.map((x) => Number(x.id)),
            quantity: valid.map((x) => Math.max(1, Number(x.quantity || 1))),
            requested_due_date: loanType.value === 'home' ? requestedDueDate.value : undefined,
            notes: customer.note || undefined,
        })
        const usedIds = new Set(valid.map((x) => Number(x.id)))
        rows.value = rows.value.filter((r) => !usedIds.has(Number(r.id)))
        selectedIds.value = []
        syncQtyToStorage()
        toast.success('Đã gửi yêu cầu mượn từ giỏ sách.', { title: 'Giỏ sách' })
    } catch (e) {
        toast.error(
            normalizeBorrowRequestErrorText(extractApiErrorMessage(e, 'Không tạo được yêu cầu mượn.')),
            { title: 'Tạo phiếu không thành công' }
        )
    } finally {
        submitting.value = false
    }
}

// ── Digital purchase cart ──
const { withFallback } = useImageFallback()
const digitalRows = ref([])
const digitalLoading = ref(false)
const digitalLoadError = ref('')
/** Một lần: đưa giỏ tài liệu số cũ (localStorage) lên server rồi xóa local. */
const LEGACY_DIGITAL_CART_MIGRATED_KEY = 'reader_digital_purchase_cart_migrated_db_v1'
/** Chọn tài liệu trong giỏ trước khi vào màn thanh toán đầy đủ */
const purchaseCartPhase = ref(props.payment_checkout_only ? 'checkout' : 'pick')
const selectedDigitalAssetIds = ref([])
/** Thanh toán trực tiếp một tài liệu (nút «Mua» trên trang sách) — không qua bước chọn trong giỏ. */
const digitalBuyNowMode = ref(false)
const digitalBuyNowRow = ref(null)
/** Các digital_asset_id đã gửi lên API khi tạo đơn — dùng để xóa khỏi giỏ DB khi thanh toán xong */
const lastPaidDigitalAssetIds = ref([])
const checkoutStep = ref(1)
const paymentLoading = ref(false)
const paymentError = ref('')
const paymentOrder = ref(null)
/** Snapshot đơn đã thanh toán — giữ sau khi xóa giỏ để hiển thị bước 4. */
const paymentPolling = ref(false)
const paymentPollError = ref('')
let paymentPollTimer = null
const manualCheckLoading = ref(false)
const cancelOrderLoading = ref(false)
const digitalCancelOrderDialogOpen = ref(false)
const customer = reactive({ name: '', email: '', phone: '', note: '' })
const canProceedCheckoutStep1 = computed(() => customer.name.trim().length > 0 && customer.email.trim().length > 0)
const selectedDigitalIdSet = computed(() => new Set(selectedDigitalAssetIds.value.map(Number)))
const selectedDigitalRows = computed(() => {
    if (digitalBuyNowMode.value && digitalBuyNowRow.value) {
        return [digitalBuyNowRow.value]
    }
    return digitalRows.value.filter((r) => selectedDigitalIdSet.value.has(Number(r.digital_asset_id)))
})
const digitalSelectableIds = computed(() => digitalRows.value.map((r) => Number(r.digital_asset_id)))
const digitalSelectedCount = computed(() => selectedDigitalAssetIds.value.length)
const allDigitalSelectableSelected = computed(
    () =>
        digitalSelectableIds.value.length > 0
        && digitalSelectableIds.value.every((id) => selectedDigitalIdSet.value.has(Number(id))),
)
const digitalSelectedTotalPrice = computed(() =>
    selectedDigitalRows.value.reduce((sum, r) => sum + (Number(r.price_vnd) > 0 ? Number(r.price_vnd) : 0), 0)
)
function trimStr(v) {
    return String(v ?? '').trim()
}
/** SĐT ưu tiên từ tài khoản, fallback thẻ thư viện (cùng nguồn hồ sơ độc giả). */
const customerPhoneFilledFromProfile = computed(() => {
    const c = trimStr(customer.phone)
    if (!c) return false
    const fromUser = trimStr(page.props.auth?.user?.phone)
    const fromCard = trimStr(props.library_card_phone)
    if (fromUser) return c === fromUser
    return Boolean(fromCard) && c === fromCard
})
function syncCustomerFromUser() {
    const u = page.props.auth?.user
    if (u && typeof u === 'object') {
        customer.name = typeof u.name === 'string' ? u.name : ''
        customer.email = typeof u.email === 'string' ? u.email : ''
        const rawPhone = u.phone != null ? String(u.phone) : ''
        const userPhone = rawPhone.trim()
        customer.phone = userPhone || trimStr(props.library_card_phone)
    }
}
function normalizeDigitalRowFromApi(x) {
    return {
        digital_asset_id: Number(x.digital_asset_id),
        book_id: Number(x.book_id) > 0 ? Number(x.book_id) : null,
        book_title: typeof x.book_title === 'string' ? x.book_title.trim() : '',
        file_name: typeof x.file_name === 'string' ? x.file_name.trim() : '',
        cover_image: typeof x.cover_image === 'string' ? x.cover_image.trim() : '',
        price_vnd: Number.isFinite(Number(x.price_vnd)) ? Number(x.price_vnd) : 0,
        paywall_enabled: x.paywall_enabled === true ? true : x.paywall_enabled === false ? false : null,
    }
}
function applyDigitalCartItemsFromPayload(items) {
    digitalRows.value = Array.isArray(items) ? items.map(normalizeDigitalRowFromApi) : []
    applyDigitalCartSelectionAndPhaseGuards()
}
function hydrateDigitalCartFromProps() {
    if (!Array.isArray(props.digital_purchase_cart_items)) return false
    applyDigitalCartItemsFromPayload(props.digital_purchase_cart_items)
    return true
}
function clearDigitalBuyNowMode() {
    digitalBuyNowMode.value = false
    digitalBuyNowRow.value = null
    clearDigitalBuyNowSession()
}
function activateDigitalBuyNowRow(row) {
    if (!row?.digital_asset_id) return false
    digitalBuyNowMode.value = true
    digitalBuyNowRow.value = normalizeDigitalRowFromApi(row)
    selectedDigitalAssetIds.value = [Number(row.digital_asset_id)]
    return true
}
function restoreDigitalBuyNowCheckoutSession() {
    const row = readDigitalBuyNowRow()
    if (!row) return false
    activateDigitalBuyNowRow(row)
    purchaseCartPhase.value = 'checkout'
    return true
}
/** Giỏ hàng thường: không khôi phục «mua ngay» — chỉ xóa session còn sót. */
function discardStaleBuyNowOnRegularCart() {
    if (props.payment_checkout_only) return
    const hadBuyNow = digitalBuyNowMode.value || readDigitalBuyNowRow()
    if (!hadBuyNow) return
    clearDigitalBuyNowMode()
    if (!paymentOrder.value?.public_id && checkoutStep.value <= 1) {
        purchaseCartPhase.value = 'pick'
        checkoutStep.value = 1
        paymentError.value = ''
        paymentOrder.value = null
        stopPaymentPolling()
    }
}
/** Không đá người dùng ra khỏi checkout khi đang có đơn QR / bước thanh toán / mua ngay. */
function shouldPreserveDigitalCheckoutScreen() {
    return (
        purchaseCartPhase.value === 'checkout'
        && (
            Boolean(paymentOrder.value?.public_id)
            || checkoutStep.value > 1
            || (digitalBuyNowMode.value && digitalBuyNowRow.value)
        )
    )
}
function applyDigitalCartSelectionAndPhaseGuards() {
    if (digitalBuyNowMode.value && digitalBuyNowRow.value) {
        selectedDigitalAssetIds.value = [Number(digitalBuyNowRow.value.digital_asset_id)]
        return
    }
    const valid = new Set(digitalRows.value.map((r) => Number(r.digital_asset_id)))
    selectedDigitalAssetIds.value = selectedDigitalAssetIds.value.filter((id) => valid.has(Number(id)))
    if (purchaseCartPhase.value === 'checkout' && selectedDigitalRows.value.length === 0) {
        if (shouldPreserveDigitalCheckoutScreen()) return
        purchaseCartPhase.value = 'pick'
        checkoutStep.value = 1
        paymentOrder.value = null
        stopPaymentPolling()
    }
}
async function tryApplyDigitalBuyNowCheckout() {
    if (!isDigitalPurchaseTabActive()) return false
    let wantCheckout = props.payment_checkout_only
    let buyAssetId = NaN
    try {
        const u = new URL(page.url, typeof window !== 'undefined' ? window.location.origin : 'http://localhost')
        if (!wantCheckout) {
            wantCheckout = u.searchParams.get('checkout') === '1'
        }
        buyAssetId = Number(u.searchParams.get('buy_asset'))
    } catch {
        return false
    }
    if (!wantCheckout) return false
    if (!Number.isInteger(buyAssetId) || buyAssetId <= 0) {
        if (!props.payment_checkout_only) return false
        const fallback = readDigitalBuyNowRow()
        if (!fallback?.digital_asset_id) return false
        buyAssetId = Number(fallback.digital_asset_id)
    }

    const rawRow = readDigitalBuyNowRow(buyAssetId)
    if (!rawRow) {
        toast.warn('Không mở được thanh toán. Vui lòng thử lại từ trang tra cứu sách.', { title: 'Thanh toán' })
        return false
    }
    if (!activateDigitalBuyNowRow(rawRow)) {
        return false
    }
    purchaseCartPhase.value = 'checkout'

    const hadInCart = digitalRows.value.some((r) => Number(r.digital_asset_id) === buyAssetId)
    if (hadInCart) {
        try {
            await digitalPurchaseCartApi.removeItem(buyAssetId)
        } catch {
            /* best-effort */
        }
        digitalRows.value = digitalRows.value.filter((r) => Number(r.digital_asset_id) !== buyAssetId)
        notifyReaderCartUpdated('digital')
    }

    checkoutStep.value = 1
    paymentError.value = ''
    paymentOrder.value = null
    stopPaymentPolling()
    syncCustomerFromUser()

    if (typeof window !== 'undefined' && window.history?.replaceState) {
        try {
            const cleanUrl = props.payment_checkout_only
                ? `${digitalPaymentBaseUrl()}?buy_asset=${buyAssetId}`
                : `${bookCartBaseUrl()}?tab=purchase`
            window.history.replaceState({}, '', cleanUrl)
        } catch {
            /* ignore */
        }
    }
    return true
}

async function tryResumePendingDigitalOrder() {
    let publicId = ''
    try {
        const u = new URL(page.url, typeof window !== 'undefined' ? window.location.origin : 'http://localhost')
        publicId = String(u.searchParams.get('order') || '').trim()
    } catch {
        return false
    }
    if (!publicId) return false

    try {
        const payload = await digitalAssetsApi.orderStatus(publicId, { sync: false })
        const data = payload?.data ?? payload
        const order = data?.order
        if (!order?.public_id) {
            toast.warn('Không tìm thấy đơn hàng.', { title: 'Thanh toán' })
            return false
        }
        if (order.status === 'paid') {
            paymentOrder.value = order
            await resetAfterDigitalPaymentSuccess({ showToast: true, redirectToOrders: true })
            return true
        }
        if (order.status !== 'pending') {
            toast.info('Đơn này không còn chờ thanh toán. Xem trong mục Đơn hàng của tôi.', { title: 'Thanh toán', duration: 8000 })
            try {
                router.visit(route('reader.services.digital-orders'))
            } catch {
                router.visit('/dich-vu/don-hang-cua-toi')
            }
            return true
        }
        purchaseCartPhase.value = 'checkout'
        checkoutStep.value = 3
        paymentOrder.value = order
        paymentError.value = order.qr_image_url ? '' : 'Không tải được mã QR thanh toán.'
        if (order.qr_image_url) startPaymentPolling()
        syncCustomerFromUser()
        toast.info('Tiếp tục thanh toán đơn đang chờ.', { title: 'Thanh toán' })
        return true
    } catch {
        toast.error('Không tải được đơn hàng.', { title: 'Thanh toán' })
        return false
    }
}

const LEGACY_DIGITAL_MIGRATE_CONCURRENCY = 6

async function migrateLegacyLocalDigitalCartOnce() {
    if (typeof localStorage === 'undefined') return
    if (localStorage.getItem(LEGACY_DIGITAL_CART_MIGRATED_KEY)) return
    let rawItems = []
    try {
        const raw = localStorage.getItem(READER_DIGITAL_PURCHASE_CART_KEY)
        const parsed = raw ? JSON.parse(raw) : null
        rawItems = Array.isArray(parsed?.items) ? parsed.items : []
    } catch {
        rawItems = []
    }
    if (rawItems.length === 0) {
        localStorage.setItem(LEGACY_DIGITAL_CART_MIGRATED_KEY, '1')
        return
    }
    const payloads = []
    for (const row of rawItems) {
        const id = Number(row?.digital_asset_id)
        if (!Number.isInteger(id) || id <= 0) continue
        payloads.push({
            digital_asset_id: id,
            book_id: Number(row?.book_id) > 0 ? Number(row.book_id) : undefined,
            book_title: typeof row?.book_title === 'string' ? row.book_title : undefined,
            file_name: typeof row?.file_name === 'string' ? row.file_name : (typeof row?.original_name === 'string' ? row.original_name : undefined),
            cover_image: typeof row?.cover_image === 'string' ? row.cover_image : undefined,
        })
    }
    if (payloads.length === 0) {
        localStorage.setItem(LEGACY_DIGITAL_CART_MIGRATED_KEY, '1')
        return
    }
    try {
        for (let i = 0; i < payloads.length; i += LEGACY_DIGITAL_MIGRATE_CONCURRENCY) {
            const chunk = payloads.slice(i, i + LEGACY_DIGITAL_MIGRATE_CONCURRENCY)
            await Promise.all(chunk.map((body) => digitalPurchaseCartApi.addItem(body)))
        }
        try {
            localStorage.removeItem(READER_DIGITAL_PURCHASE_CART_KEY)
        } catch {
            /* ignore */
        }
        localStorage.setItem(LEGACY_DIGITAL_CART_MIGRATED_KEY, '1')
    } catch {
        /* Giữ localStorage nếu API lỗi — thử lại khi tải giỏ sau. */
    }
}
async function fetchDigitalCart(options = {}) {
    const { silent = false, preserveCheckout = false } = options
    digitalLoadError.value = ''
    if (!page.props.auth?.user) {
        digitalRows.value = []
        if (!preserveCheckout) applyDigitalCartSelectionAndPhaseGuards()
        return
    }
    if (!silent) digitalLoading.value = true
    try {
        await migrateLegacyLocalDigitalCartOnce()
        const payload = await digitalPurchaseCartApi.list()
        const items = payload?.data?.items ?? payload?.items ?? []
        digitalRows.value = Array.isArray(items) ? items.map(normalizeDigitalRowFromApi) : []
        if (!preserveCheckout) applyDigitalCartSelectionAndPhaseGuards()
    } catch (e) {
        if (!silent || digitalRows.value.length === 0) {
            digitalRows.value = []
            digitalLoadError.value = extractApiErrorMessage(e, 'Không tải được giỏ thanh toán tài liệu.')
            toast.error(digitalLoadError.value, { title: 'Giỏ sách' })
        }
    } finally {
        if (!silent) digitalLoading.value = false
    }
}
async function removeDigitalItem(digitalAssetId) {
    const id = Number(digitalAssetId)
    try {
        await digitalPurchaseCartApi.removeItem(id)
        await fetchDigitalCart()
        notifyReaderCartUpdated('digital')
        toast.success('Đã xóa khỏi danh sách thanh toán.', { title: 'Giỏ sách' })
    } catch (e) {
        toast.error(extractApiErrorMessage(e, 'Không xóa được mục.'), { title: 'Giỏ sách' })
    }
}
function setDigitalRowSelected(assetId, checked) {
    const n = Number(assetId)
    if (!Number.isFinite(n)) return
    if (checked) {
        if (!selectedDigitalIdSet.value.has(n)) {
            selectedDigitalAssetIds.value = [...selectedDigitalAssetIds.value, n]
        }
    } else {
        selectedDigitalAssetIds.value = selectedDigitalAssetIds.value.filter((x) => Number(x) !== n)
    }
}
function setDigitalSelectAll(checked) {
    if (checked) selectedDigitalAssetIds.value = [...digitalSelectableIds.value]
    else selectedDigitalAssetIds.value = []
}
async function removeSelectedDigitalItems() {
    if (selectedDigitalAssetIds.value.length === 0) {
        toast.warn('Vui lòng chọn ít nhất một mục để xóa.', { title: 'Giỏ sách' })
        return
    }
    const idSet = new Set(selectedDigitalAssetIds.value.map(Number))
    try {
        await digitalPurchaseCartApi.bulkRemove([...idSet])
        await fetchDigitalCart()
        notifyReaderCartUpdated('digital')
        toast.success(`Đã xóa ${idSet.size} mục đã chọn khỏi giỏ.`, { title: 'Giỏ sách' })
    } catch (e) {
        toast.error(extractApiErrorMessage(e, 'Không xóa được các mục đã chọn.'), { title: 'Giỏ sách' })
    }
}
async function removePaidDigitalAssetsFromCart(assetIds) {
    const ids = (assetIds || []).map((x) => Number(x)).filter((x) => Number.isInteger(x) && x > 0)
    if (ids.length === 0) return
    try {
        await digitalPurchaseCartApi.bulkRemove(ids)
    } catch {
        /* best-effort */
    }
    await fetchDigitalCart()
    notifyReaderCartUpdated('digital')
}
function goPurchaseCheckout() {
    if (selectedDigitalRows.value.length === 0) {
        toast.warn('Vui lòng chọn ít nhất một tài liệu cần thanh toán.', { title: 'Giỏ sách' })
        return
    }
    purchaseCartPhase.value = 'checkout'
    checkoutStep.value = 1
    syncCustomerFromUser()
}
function backToPurchasePick() {
    if (props.payment_checkout_only) {
        const bookId = Number(digitalBuyNowRow.value?.book_id ?? 0)
        clearDigitalBuyNowMode()
        try {
            if (Number.isFinite(bookId) && bookId > 0) {
                router.visit(route('reader.catalog.show', { book: bookId }))
            } else {
                router.visit(route('reader.catalog'))
            }
        } catch {
            router.visit('/tra-cuu-sach')
        }
        return
    }
    clearDigitalBuyNowMode()
    purchaseCartPhase.value = 'pick'
    checkoutStep.value = 1
    paymentError.value = ''
    paymentOrder.value = null
    stopPaymentPolling()
}
function digitalRowTitle(row) {
    return row.book_title || row.file_name || `Tài liệu #${row.digital_asset_id}`
}
function digitalItemBookHref(row) {
    if (!row.book_id) return null
    try {
        return route('reader.catalog.show', { book: row.book_id })
    } catch {
        return null
    }
}
function digitalItemCheckoutHref() {
    try {
        return `${route('reader.services.book-cart')}?tab=purchase`
    } catch {
        return '/dich-vu/gio-sach?tab=purchase'
    }
}
function digitalPriceLine(row) {
    if (row.paywall_enabled === false) return 'Không thu phí theo cấu hình hiện tại.'
    if (row.price_vnd != null && row.price_vnd > 0) return `Giá tải PDF: ${row.price_vnd.toLocaleString('vi-VN')}₫`
    if (row.paywall_enabled === true) return 'Giá: cập nhật tại bước thanh toán QR.'
    return 'Tạo QR thanh toán tại bước thanh toán nếu thiếu thông tin giá.'
}
async function createPaymentOrderForDigitalCart() {
    const ids = selectedDigitalRows.value.map((r) => r.digital_asset_id)
    if (ids.length === 0) return
    paymentLoading.value = true; paymentError.value = ''; paymentOrder.value = null
    try {
        const payload = await digitalAssetsApi.createPaymentOrder(ids)
        const data = payload?.data ?? payload
        paymentOrder.value = data?.order ?? null
        lastPaidDigitalAssetIds.value = ids.map(Number)
        if (!paymentOrder.value?.qr_image_url) { paymentError.value = 'Không tạo được QR thanh toán.' }
        else { checkoutStep.value = 3; startPaymentPolling() }
    } catch (e) { paymentError.value = e?.response?.data?.messages || 'Không tạo được giao dịch.' }
    finally { paymentLoading.value = false }
}
async function copyText(text) {
    const t = String(text || '').trim(); if (!t) return
    try { await navigator.clipboard.writeText(t); toast.success('Đã sao chép.', { title: 'Thanh toán' }) }
    catch { toast.warn('Trình duyệt không cho phép sao chép.', { title: 'Thanh toán' }) }
}
function parseDigitalOrderStatusPayload(payload) {
    const data = payload?.data ?? payload
    return {
        status: String(data?.order?.status ?? '').trim(),
        entitlementsGranted: data?.entitlements_granted === true,
        sepaySyncAvailable: data?.sepay_sync_available === true,
    }
}
async function applyDigitalOrderStatusFromApi(payload, options = {}) {
    const { showToast = false } = options
    const info = parseDigitalOrderStatusPayload(payload)
    if (info.status && paymentOrder.value) {
        paymentOrder.value.status = info.status
    }
    if (info.status === 'paid' && info.entitlementsGranted) {
        await resetAfterDigitalPaymentSuccess({ showToast })
        return 'paid'
    }
    if (info.status === 'paid' && !info.entitlementsGranted) {
        paymentPollError.value = 'Đơn đã thanh toán nhưng quyền tải chưa được cấp. Vui lòng thử «Kiểm tra thanh toán» lại sau vài giây.'
        if (showToast) {
            toast.warn(paymentPollError.value, { title: 'Thanh toán', duration: 8000 })
        }
        return 'paid_pending_entitlement'
    }
    if (info.status === 'failed') {
        paymentPollError.value = 'Số tiền chuyển khoản chưa đủ so với đơn hàng. Vui lòng chuyển đủ số tiền và đúng nội dung CK.'
        if (showToast) {
            toast.warn(paymentPollError.value, { title: 'Thanh toán', duration: 8000 })
        }
        return 'failed'
    }
    if (showToast) {
        toast.info(
            info.sepaySyncAvailable
                ? 'Chưa thấy thanh toán khớp đơn trên SePay. Kiểm tra đúng số tiền và nội dung CK, rồi thử lại sau vài giây.'
                : 'Chưa xác nhận thanh toán. Cấu hình SEPAY_API_TOKEN trên server để hệ thống đối soát tự động, hoặc đợi webhook SePay.',
            { title: 'Thanh toán', duration: 8000 }
        )
    }
    return 'pending'
}
async function manualCheckPayment() {
    if (!paymentOrder.value?.public_id || manualCheckLoading.value) return
    manualCheckLoading.value = true
    paymentPollError.value = ''
    try {
        const payload = await digitalAssetsApi.orderStatus(paymentOrder.value.public_id, { sync: true })
        await applyDigitalOrderStatusFromApi(payload, { showToast: true })
    } catch {
        paymentPollError.value = 'Không kiểm tra được trạng thái thanh toán.'
    } finally {
        manualCheckLoading.value = false
    }
}
function openDigitalCancelOrderDialog() {
    digitalCancelOrderDialogOpen.value = true
}
function closeDigitalCancelOrderDialog() {
    digitalCancelOrderDialogOpen.value = false
}
function redirectAfterDigitalOrderCancelled() {
    stopPaymentPolling()
    paymentOrder.value = null
    paymentError.value = ''
    checkoutStep.value = 1
    purchaseCartPhase.value = 'pick'
    clearDigitalBuyNowMode()
    clearDigitalBuyNowSession()
    closeDigitalCancelOrderDialog()
    router.visit(catalogBrowseUrl())
}
async function confirmCancelDigitalPaymentOrder() {
    if (!paymentOrder.value?.public_id || cancelOrderLoading.value) return
    cancelOrderLoading.value = true
    try {
        await digitalAssetsApi.cancelOrder(paymentOrder.value.public_id)
        toast.success('Đã hủy đơn hàng.', { title: 'Thanh toán' })
        redirectAfterDigitalOrderCancelled()
    } catch (e) {
        toast.error(extractApiErrorMessage(e, 'Không hủy được đơn hàng.'), { title: 'Thanh toán' })
    } finally {
        cancelOrderLoading.value = false
    }
}
function stopPaymentPolling() {
    paymentPolling.value = false
    paymentPollError.value = ''
    if (paymentPollTimer) {
        clearInterval(paymentPollTimer)
        paymentPollTimer = null
    }
}

/** Sau khi backend xác nhận paid: xóa giỏ DB, chuyển sang trang Đơn hàng của tôi. */
async function resetAfterDigitalPaymentSuccess(options = {}) {
    const { showToast = false, redirectToOrders = true } = options
    stopPaymentPolling()
    const ids = [...lastPaidDigitalAssetIds.value]
    lastPaidDigitalAssetIds.value = []
    const publicId = paymentOrder.value?.public_id ? String(paymentOrder.value.public_id) : ''

    await removePaidDigitalAssetsFromCart(ids)
    clearDigitalBuyNowMode()
    paymentOrder.value = null
    checkoutStep.value = 1
    purchaseCartPhase.value = 'pick'
    selectedDigitalAssetIds.value = []

    if (redirectToOrders && publicId) {
        if (showToast) {
            toast.success('Thanh toán thành công.', { title: 'Thanh toán', duration: 6000 })
        }
        try {
            router.visit(`${route('reader.services.digital-orders')}?order=${encodeURIComponent(publicId)}&paid=1`)
        } catch {
            router.visit(`/dich-vu/don-hang-cua-toi?order=${encodeURIComponent(publicId)}&paid=1`)
        }
        return
    }

    if (showToast) {
        toast.success('Thanh toán thành công.', { title: 'Thanh toán', duration: 6000 })
    }
}
function startPaymentPolling() {
    stopPaymentPolling()
    if (!paymentOrder.value?.public_id) return
    paymentPolling.value = true
    const publicId = paymentOrder.value.public_id
    let ticks = 0
    paymentPollTimer = setInterval(async () => {
        ticks += 1
        try {
            const payload = await digitalAssetsApi.orderStatus(publicId, { sync: true })
            const outcome = await applyDigitalOrderStatusFromApi(payload, { showToast: false })
            if (outcome === 'paid') stopPaymentPolling()
        } catch {
            paymentPollError.value = 'Không kiểm tra được trạng thái thanh toán.'
        }
        if (ticks >= 60) stopPaymentPolling()
    }, 2000)
}

function onWindowStorage(event) {
    if (!event.key || event.key === CART_KEY) refreshPreview()
}
function onReaderCartUpdated(e) {
    const src = e?.detail?.source
    if (src == null || src === 'borrow') refreshPreview()
    if ((src == null || src === 'digital') && isDigitalPurchaseTabActive()) {
        if (shouldPreserveDigitalCheckoutScreen()) return
        void fetchDigitalCart()
    }
}

watch(
    () => props.cart_tab,
    async (tab) => {
        if (tab === 'purchase') {
            discardStaleBuyNowOnRegularCart()
            const preserve = shouldPreserveDigitalCheckoutScreen()
            if (!hydrateDigitalCartFromProps()) {
                await fetchDigitalCart({ preserveCheckout: preserve })
            } else {
                void migrateLegacyLocalDigitalCartOnce().then(() => {
                    if (!shouldPreserveDigitalCheckoutScreen()) {
                        void fetchDigitalCart({ silent: true, preserveCheckout: true })
                    }
                })
            }
            if (props.payment_checkout_only) {
                const appliedFromUrl = await tryApplyDigitalBuyNowCheckout()
                if (!appliedFromUrl && !preserve) restoreDigitalBuyNowCheckoutSession()
            }
        } else {
            clearDigitalBuyNowMode()
            refreshPreview()
        }
    },
)

watch(
    () => [
        trimStr(page.props.auth?.user?.phone),
        trimStr(page.props.auth?.user?.name),
        trimStr(page.props.auth?.user?.email),
        trimStr(props.library_card_phone),
    ],
    () => {
        if (purchaseCartPhase.value !== 'checkout' || checkoutStep.value !== 1) return
        if (trimStr(customer.phone) !== '') return
        syncCustomerFromUser()
    },
)

onMounted(async () => {
    syncCustomerFromUser()
    refreshPreview()
    if (isDigitalPurchaseTabActive()) {
        discardStaleBuyNowOnRegularCart()
        const preserve = shouldPreserveDigitalCheckoutScreen()
        if (!hydrateDigitalCartFromProps()) {
            await fetchDigitalCart({ preserveCheckout: preserve })
        } else {
            void migrateLegacyLocalDigitalCartOnce().then(() => {
                if (!shouldPreserveDigitalCheckoutScreen()) {
                    void fetchDigitalCart({ silent: true, preserveCheckout: true })
                }
            })
        }
        if (props.payment_checkout_only) {
            const resumedOrder = await tryResumePendingDigitalOrder()
            if (!resumedOrder) {
            const appliedFromUrl = await tryApplyDigitalBuyNowCheckout()
            if (!appliedFromUrl && !preserve) {
                const restored = restoreDigitalBuyNowCheckoutSession()
                if (!restored) {
                    toast.warn('Không mở được thanh toán. Vui lòng thử lại từ trang tra cứu sách.', { title: 'Thanh toán' })
                    try {
                        router.visit(route('reader.catalog'))
                    } catch {
                        router.visit('/tra-cuu-sach')
                    }
                }
            }
            }
        }
    }
    window.addEventListener('storage', onWindowStorage)
    window.addEventListener(READER_CART_UPDATED_EVENT, onReaderCartUpdated)
})
onBeforeUnmount(() => {
    window.removeEventListener('storage', onWindowStorage)
    window.removeEventListener(READER_CART_UPDATED_EVENT, onReaderCartUpdated)
    flushBorrowCartSyncDebounced()
    stopPaymentPolling()
})
</script>

<template>
    <ReaderLayout full-width>
        <Head :title="pageTitle" />

        <div class="w-full max-w-full animate-in fade-in-50 pb-10 duration-500 sm:pb-12">
            <div class="w-full">
                <div class="px-4 pt-2 pb-2 sm:px-8 sm:pt-3 sm:pb-3 lg:px-12 xl:px-16">
                    <nav class="mb-1.5 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                        <Link :href="route('reader.home')" class="text-blue-800 hover:underline dark:text-blue-300">Trang chủ</Link>
                        <span aria-hidden="true">/</span>
                        <span class="text-slate-700 dark:text-slate-200">{{ payment_checkout_only ? 'Thanh toán' : 'Giỏ sách' }}</span>
                    </nav>

                    <header class="space-y-3">
                        <!-- Hàng 1: chỉ tiêu đề + meta (không gộp tab lên cùng hàng) -->
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                            <div class="min-w-0">
                                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                                    {{ pageTitle }}
                                </h1>
                                <p v-if="payment_checkout_only" class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    Hoàn tất thanh toán để tải PDF tài liệu đã chọn. Không yêu cầu thẻ thư viện.
                                </p>
                            </div>
                            <div
                                v-if="payment_checkout_only || (activeTab === 'borrow' && loading) || (activeTab === 'purchase' && digitalLoading) || (activeTab === 'purchase' && purchaseCartPhase === 'checkout')"
                                class="flex shrink-0 flex-col gap-1 text-xs text-slate-500 sm:max-w-xs sm:text-right dark:text-slate-400"
                            >
                                <template v-if="activeTab === 'borrow'">
                                    <template v-if="loading">
                                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-600 dark:text-slate-300">
                                            <Icon icon="lucide:loader-2" class="h-3.5 w-3.5 shrink-0 animate-spin" aria-hidden="true" />
                                            Đang tải giỏ…
                                        </span>
                                    </template>
                                </template>
                                <template v-else-if="activeTab === 'purchase' && digitalLoading">
                                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-600 dark:text-slate-300">
                                        <Icon icon="lucide:loader-2" class="h-3.5 w-3.5 shrink-0 animate-spin" aria-hidden="true" />
                                        Đang tải giỏ…
                                    </span>
                                </template>
                                <template v-else-if="activeTab === 'purchase' && purchaseCartPhase === 'checkout'">
                                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ selectedDigitalRows.length }} mục trong đơn</span>
                                    <span class="block text-slate-500 dark:text-slate-400">Bước {{ Math.min(checkoutStep, 3) }}/3</span>
                                </template>
                            </div>
                        </div>

                        <!-- Hàng 2: tab giỏ (ẩn khi mua ngay / trang thanh toán riêng) -->
                        <div
                            v-if="!payment_checkout_only"
                            class="flex gap-4 border-b border-slate-200 dark:border-slate-700"
                            role="tablist"
                            aria-label="Chế độ giỏ sách"
                        >
                            <Link
                                :href="bookCartBaseUrl()"
                                class="inline-flex min-h-[44px] items-center gap-2 border-b-2 px-1 pb-3 text-sm font-bold transition-colors"
                                :class="activeTab === 'borrow' ? 'border-blue-700 text-blue-800 dark:border-blue-400 dark:text-blue-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                            >
                                <Icon icon="lucide:library" class="h-4 w-4" />
                                Mượn sách in
                            </Link>
                            <Link
                                :href="`${bookCartBaseUrl()}?tab=purchase`"
                                class="inline-flex min-h-[44px] items-center gap-2 border-b-2 px-1 pb-3 text-sm font-bold transition-colors"
                                :class="activeTab === 'purchase' ? 'border-blue-700 text-blue-800 dark:border-blue-400 dark:text-blue-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                            >
                                <Icon icon="lucide:qr-code" class="h-4 w-4" />
                                Thanh toán tài liệu
                            </Link>
                        </div>
                        <p
                            v-if="!payment_checkout_only && activeTab === 'purchase' && purchaseCartPhase === 'pick'"
                            class="text-xs text-slate-500 dark:text-slate-400"
                        >
                            Mua tài liệu số chỉ cần đăng nhập — không yêu cầu thẻ thư viện.
                        </p>

                        <nav
                            v-if="payment_checkout_only || (activeTab === 'purchase' && purchaseCartPhase === 'checkout')"
                            class="min-w-0 overflow-x-auto pb-1 [-webkit-overflow-scrolling:touch]"
                            aria-label="Tiến trình thanh toán"
                        >
                            <ol class="flex min-w-max gap-2 sm:min-w-0 sm:w-full sm:gap-3">
                                <li
                                    v-for="s in [{ n: 1, label: 'Thông tin' }, { n: 2, label: 'Thanh toán' }, { n: 3, label: 'Hoàn tất' }]"
                                    :key="s.n"
                                    class="flex min-w-[6.75rem] flex-1 items-center gap-2 rounded-xl border px-2.5 py-2 sm:min-w-0 sm:px-3"
                                    :class="
                                        checkoutStep > s.n
                                            ? 'border-emerald-200 bg-emerald-50/90 dark:border-emerald-900/50 dark:bg-emerald-950/30'
                                            : checkoutStep === s.n
                                              ? 'border-blue-300 bg-blue-50/90 ring-1 ring-blue-200/80 dark:border-blue-800 dark:bg-blue-950/40 dark:ring-blue-900/50'
                                              : 'border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/50'
                                    "
                                >
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black"
                                        :class="
                                            checkoutStep > s.n
                                                ? 'bg-emerald-600 text-white'
                                                : checkoutStep === s.n
                                                  ? 'bg-blue-700 text-white dark:bg-blue-600'
                                                  : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                                        "
                                    >
                                        <Icon v-if="checkoutStep > s.n" icon="lucide:check" class="h-3.5 w-3.5" />
                                        <span v-else>{{ s.n }}</span>
                                    </span>
                                    <span
                                        class="min-w-0 truncate text-xs font-bold sm:text-sm"
                                        :class="checkoutStep === s.n ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'"
                                    >{{ s.label }}</span>
                                </li>
                            </ol>
                        </nav>
                    </header>
                </div>

                <div class="min-w-0 overflow-x-hidden px-4 pb-8 pt-0 dark:border-slate-800 sm:px-8 sm:pb-10 lg:px-12 xl:px-16">

                <!-- ================= BORROW TAB CONTENT (layout theo BorrowCart.vue — af47536) ================= -->
                <div v-if="activeTab === 'borrow' && !payment_checkout_only">
                    <div v-if="!loading && rows.length > 0" class="mb-3 flex flex-row flex-wrap items-center justify-between gap-2">
                        <button
                            type="button"
                            class="inline-flex min-h-[44px] w-fit max-w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm ring-1 ring-rose-100 transition hover:bg-rose-50 disabled:pointer-events-none disabled:opacity-45 dark:border-rose-900/60 dark:bg-slate-900 dark:ring-rose-900/40 dark:text-rose-200 dark:hover:bg-rose-950/40"
                            :disabled="selectedIds.length === 0"
                            @click="removeSelectedItems"
                        >
                            <Icon icon="lucide:trash-2" class="h-4 w-4" />
                            Xóa mục đã chọn ({{ selectedIds.length }})
                        </button>
                        <Link
                            :href="route('reader.catalog')"
                            class="inline-flex min-h-[44px] w-fit shrink-0 items-center gap-2 text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300"
                        >
                            <Icon icon="lucide:arrow-up-right" class="h-4 w-4" />
                            Tra cứu sách
                        </Link>
                    </div>

                    <div v-if="!loading && rows.length === 0" class="mx-auto mt-2 w-full max-w-md rounded-2xl bg-slate-100/90 p-3 sm:p-4 dark:bg-slate-950/50">
                        <div
                            class="rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700/80"
                            role="status"
                        >
                            <div
                                class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800/90"
                                aria-hidden="true"
                            >
                                <Icon icon="lucide:library" class="h-10 w-10 text-slate-400 dark:text-slate-500" />
                            </div>
                            <p class="mt-8 text-base font-semibold text-slate-800 dark:text-slate-100">Chưa có sách trong giỏ mượn</p>
                            <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                                Thêm sách từ tra cứu để gửi yêu cầu mượn in tại thư viện.
                            </p>
                            <Link
                                :href="route('reader.catalog')"
                                class="mt-8 inline-flex min-h-[48px] min-w-[200px] items-center justify-center rounded-lg bg-blue-700 px-8 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
                            >
                                Đi tới tra cứu sách
                            </Link>
                        </div>
                    </div>

                    <div v-else class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start lg:gap-6">
                        <section class="min-w-0">
                            <div class="rounded-2xl bg-slate-100/90 p-4 dark:bg-slate-950/40">
                                <!-- Desktop: flex + items-center — căn giữa dọc ổn định hơn grid với ô checkbox min-h 44px -->
                                <div
                                    class="mb-3 hidden rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200/80 lg:flex lg:min-w-0 lg:items-center lg:gap-3 dark:bg-slate-900 dark:ring-slate-700/80"
                                >
                                    <span class="admin-table-checkbox-wrap shrink-0">
                                        <input
                                            id="borrow-cart-select-all"
                                            type="checkbox"
                                            :checked="allBorrowSelectableSelected"
                                            class="admin-table-checkbox"
                                            @change="(e) => setBorrowSelectAll(e.target.checked)"
                                        />
                                    </span>
                                    <span class="w-14 shrink-0" aria-hidden="true"></span>
                                    <label
                                        class="min-w-0 flex-1 cursor-pointer text-sm font-semibold text-slate-700 dark:text-slate-200"
                                        for="borrow-cart-select-all"
                                    >Đã chọn {{ selectedCount }} sách</label>
                                    <span class="w-[220px] shrink-0 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Vị trí kho/tủ</span>
                                    <span class="w-[170px] shrink-0 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Số lượng</span>
                                    <span class="w-11 shrink-0 text-right"><span class="sr-only">Xóa</span></span>
                                </div>

                                <!-- Mobile: chọn tất cả -->
                                <div class="mb-3 rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200/80 lg:hidden dark:bg-slate-900 dark:ring-slate-700/80">
                                    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        <span class="admin-table-checkbox-wrap">
                                            <input
                                                type="checkbox"
                                                :checked="allBorrowSelectableSelected"
                                                class="admin-table-checkbox"
                                                @change="(e) => setBorrowSelectAll(e.target.checked)"
                                            />
                                        </span>
                                        Đã chọn {{ selectedCount }} sách
                                    </label>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="row in rows"
                                        :key="row.id"
                                        class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700/80"
                                    >
                                        <!-- Mobile -->
                                        <div class="flex flex-col gap-4 lg:hidden">
                                            <div class="flex min-w-0 items-start gap-3">
                                                <span class="admin-table-checkbox-wrap">
                                                    <input
                                                        type="checkbox"
                                                        :checked="selectedIds.includes(Number(row.id))"
                                                        :disabled="Number(row.available_for_borrow || 0) <= 0"
                                                        class="admin-table-checkbox"
                                                        @change="(e) => setBorrowRowSelected(row.id, e.target.checked)"
                                                    />
                                                </span>
                                                <Link
                                                    :href="route('reader.catalog.show', { book: row.id })"
                                                    class="flex min-w-0 flex-1 items-start gap-3 rounded-lg transition hover:bg-slate-50 dark:hover:bg-slate-800/80"
                                                >
                                                    <div class="h-20 w-14 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-100 dark:border-slate-600 dark:bg-slate-800">
                                                        <img
                                                            v-if="row.cover_image"
                                                            :src="row.cover_image"
                                                            :alt="row.title"
                                                            loading="lazy"
                                                            class="h-full w-full object-cover"
                                                            @error="withFallback('/images/default-book-cover.png')($event)"
                                                        />
                                                        <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                                            <Icon icon="lucide:book-open" class="h-5 w-5" />
                                                        </div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="line-clamp-2 text-sm font-bold text-slate-900 hover:text-blue-700 dark:text-slate-100 dark:hover:text-blue-300">{{ row.title }}</p>
                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                            Số lượng sách khả dụng: {{ Number(row.available_for_borrow || 0) > 0 ? row.available_for_borrow : 'Hết sách' }}
                                                        </p>
                                                    </div>
                                                </Link>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Vị trí kho/tủ</p>
                                                <p class="truncate text-xs font-medium text-slate-700 dark:text-slate-300">
                                                    Kho: {{ row.warehouse_name || row.warehouse_code || '—' }}
                                                </p>
                                                <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">Tủ: {{ row.cabinet || '—' }}</p>
                                            </div>
                                            <div class="flex flex-col items-stretch gap-2">
                                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Số lượng</span>
                                                <div
                                                    class="inline-flex h-12 max-w-[200px] items-center justify-center overflow-hidden rounded-lg border border-slate-200/90 bg-slate-50 dark:border-slate-600 dark:bg-slate-800/80"
                                                >
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-full w-12 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-700"
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
                                                        class="inline-flex h-full w-12 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-700"
                                                        :disabled="Number(row.quantity || 1) >= Math.max(1, Number(row.available_for_borrow || 1))"
                                                        @click="adjustQty(row.id, 1)"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex justify-end">
                                                <button
                                                    type="button"
                                                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30"
                                                    :aria-label="'Xóa ' + (row.title || 'sách')"
                                                    @click="removeItem(row.id)"
                                                >
                                                    <Icon icon="lucide:trash-2" class="h-5 w-5" />
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Desktop: flex đồng bộ hàng tiêu đề — items-center căn giữa theo chiều dọc -->
                                        <div class="hidden min-w-0 lg:flex lg:items-center lg:gap-3">
                                            <span class="admin-table-checkbox-wrap shrink-0">
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedIds.includes(Number(row.id))"
                                                    :disabled="Number(row.available_for_borrow || 0) <= 0"
                                                    class="admin-table-checkbox"
                                                    @change="(e) => setBorrowRowSelected(row.id, e.target.checked)"
                                                />
                                            </span>
                                            <div class="h-20 w-14 shrink-0 self-center overflow-hidden rounded-md border border-slate-200 bg-slate-100 dark:border-slate-600 dark:bg-slate-800">
                                                <img
                                                    v-if="row.cover_image"
                                                    :src="row.cover_image"
                                                    :alt="row.title"
                                                    loading="lazy"
                                                    class="h-full w-full object-cover"
                                                    @error="withFallback('/images/default-book-cover.png')($event)"
                                                />
                                                <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                                    <Icon icon="lucide:book-open" class="h-5 w-5" />
                                                </div>
                                            </div>
                                            <Link
                                                :href="route('reader.catalog.show', { book: row.id })"
                                                class="block min-w-0 flex-1 self-center rounded-lg py-0.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/80"
                                            >
                                                <p class="line-clamp-2 text-sm font-bold text-slate-900 hover:text-blue-700 dark:text-slate-100 dark:hover:text-blue-300">{{ row.title }}</p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    Số lượng sách khả dụng: {{ Number(row.available_for_borrow || 0) > 0 ? row.available_for_borrow : 'Hết sách' }}
                                                </p>
                                            </Link>
                                            <div class="w-[220px] shrink-0 self-center lg:text-left">
                                                <p class="truncate text-xs font-medium text-slate-700 dark:text-slate-300">
                                                    Kho: {{ row.warehouse_name || row.warehouse_code || '—' }}
                                                </p>
                                                <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">Tủ: {{ row.cabinet || '—' }}</p>
                                            </div>
                                            <div class="flex w-[170px] shrink-0 flex-col items-center justify-center self-center">
                                                <div
                                                    class="inline-flex h-12 w-full max-w-[200px] items-center justify-center overflow-hidden rounded-lg border border-slate-200/90 bg-slate-50 dark:border-slate-600 dark:bg-slate-800/80 lg:max-w-none"
                                                >
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-full w-12 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-700"
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
                                                        class="inline-flex h-full w-12 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-700"
                                                        :disabled="Number(row.quantity || 1) >= Math.max(1, Number(row.available_for_borrow || 1))"
                                                        @click="adjustQty(row.id, 1)"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex w-11 shrink-0 items-center justify-end self-center">
                                                <button
                                                    type="button"
                                                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30"
                                                    :aria-label="'Xóa ' + (row.title || 'sách')"
                                                    @click="removeItem(row.id)"
                                                >
                                                    <Icon icon="lucide:trash-2" class="h-5 w-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <aside class="min-w-0 space-y-3 lg:sticky lg:top-24">
                            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700/80">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Tóm tắt yêu cầu mượn</h3>
                                <p
                                    v-if="selectedCount === 0"
                                    class="mt-2 rounded-lg bg-white/80 px-3 py-2 text-xs leading-relaxed text-slate-600 dark:bg-slate-800/80 dark:text-slate-400"
                                >
                                    Chọn ít nhất một sách trong bảng bên trái, chỉnh số lượng, rồi bấm «Gửi yêu cầu mượn».
                                </p>
                                <div class="mt-3 rounded-md bg-white/80 p-3 dark:bg-slate-800/60">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Hình thức mượn</p>
                                    <div class="mt-2 grid grid-cols-1 gap-2">
                                        <label
                                            class="inline-flex min-h-[44px] items-center gap-2 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:bg-slate-900/80 dark:text-slate-200"
                                            :class="{ 'pointer-events-none opacity-50': !canBorrowHome }"
                                        >
                                            <input v-model="loanType" type="radio" value="home" :disabled="!canBorrowHome" />
                                            Mượn về nhà
                                        </label>
                                        <label
                                            class="inline-flex min-h-[44px] items-center gap-2 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:bg-slate-900/80 dark:text-slate-200"
                                            :class="{ 'pointer-events-none opacity-50': !canBorrowOnsite }"
                                        >
                                            <input v-model="loanType" type="radio" value="onsite" :disabled="!canBorrowOnsite" />
                                            Đọc tại chỗ
                                        </label>
                                    </div>
                                    <div v-if="loanType === 'home'" class="mt-3">
                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-200">Ngày trả dự kiến</label>
                                        <input
                                            v-model="requestedDueDate"
                                            type="date"
                                            :min="todayIso"
                                            :max="maxDueIso"
                                            class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-100 dark:[color-scheme:dark] dark:focus:border-emerald-400 dark:focus:ring-emerald-900/50"
                                        />
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Ghi chú cho thủ thư (tùy chọn)</label>
                                    <textarea
                                        v-model="customer.note"
                                        rows="2"
                                        class="w-full resize-y rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-emerald-400"
                                        placeholder="Ví dụ: hẹn lấy sách cuối tuần…"
                                    ></textarea>
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
                                    class="mt-4 inline-flex min-h-[48px] w-full items-center justify-center rounded-lg px-4 text-sm font-bold text-white shadow-sm transition"
                                    :class="
                                        selectedRows.length > 0 && !submitting
                                            ? 'bg-emerald-600 hover:bg-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-500'
                                            : 'cursor-not-allowed bg-slate-300 text-slate-500 dark:bg-slate-700 dark:text-slate-400'
                                    "
                                    :disabled="selectedRows.length === 0 || submitting"
                                    @click="submitBorrowRequest"
                                >
                                    {{ submitting ? 'Đang gửi...' : 'Gửi yêu cầu mượn' }}
                                </button>
                            </div>

                            <div
                                class="rounded-xl border border-slate-200/80 bg-slate-50/90 p-4 text-xs text-slate-600 ring-1 ring-slate-200/60 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400 dark:ring-slate-700/60"
                            >
                                Thủ thư sẽ duyệt yêu cầu theo số lượng khả dụng thực tế tại thời điểm xử lý.
                            </div>
                        </aside>
                    </div>
                </div>

                <!-- ================= DIGITAL TAB CONTENT ================= -->
                <div v-else>
                    <!-- Bước 1: chỉ danh sách giỏ — chọn xong mới hiện cột phải; bấm «Tiếp tục thanh toán» mới vào form như cũ -->
                    <template v-if="purchaseCartPhase === 'pick' && !payment_checkout_only">
                        <div v-if="!digitalLoading && digitalRows.length > 0" class="mb-3 flex flex-row flex-wrap items-center justify-between gap-2">
                            <button
                                type="button"
                                class="inline-flex min-h-[44px] w-fit max-w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm ring-1 ring-rose-100 transition hover:bg-rose-50 disabled:pointer-events-none disabled:opacity-45 dark:border-rose-900/60 dark:bg-slate-900 dark:ring-rose-900/40 dark:text-rose-200 dark:hover:bg-rose-950/40"
                                :disabled="digitalSelectedCount === 0"
                                @click="removeSelectedDigitalItems"
                            >
                                <Icon icon="lucide:trash-2" class="h-4 w-4" />
                                Xóa mục đã chọn ({{ digitalSelectedCount }})
                            </button>
                            <Link
                                :href="route('reader.catalog')"
                                class="inline-flex min-h-[44px] w-fit shrink-0 items-center gap-2 text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300"
                            >
                                <Icon icon="lucide:arrow-up-right" class="h-4 w-4" />
                                Tra cứu sách
                            </Link>
                        </div>

                        <div v-if="digitalLoading && digitalRows.length === 0" class="mx-auto mt-2 w-full max-w-md rounded-2xl bg-slate-100/90 p-3 sm:p-4 dark:bg-slate-950/50">
                            <div class="flex flex-col items-center justify-center gap-3 rounded-xl bg-white px-6 py-12 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700/80">
                                <Icon icon="lucide:loader-2" class="h-10 w-10 animate-spin text-slate-400 dark:text-slate-500" aria-hidden="true" />
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Đang tải giỏ thanh toán…</p>
                            </div>
                        </div>

                        <div v-else-if="!digitalLoading && digitalRows.length === 0" class="mx-auto mt-2 w-full max-w-md rounded-2xl bg-slate-100/90 p-3 sm:p-4 dark:bg-slate-950/50">
                            <div
                                class="rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700/80"
                                role="status"
                            >
                                <div
                                    class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800/90"
                                    aria-hidden="true"
                                >
                                    <Icon icon="lucide:file-text" class="h-10 w-10 text-slate-400 dark:text-slate-500" />
                                </div>
                                <p class="mt-8 text-base font-semibold text-slate-800 dark:text-slate-100">Chưa có tài liệu trong giỏ thanh toán</p>
                                <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                                    Thêm tài liệu từ tra cứu sách, rồi quay lại đây để thanh toán.
                                </p>
                                <Link
                                    :href="route('reader.catalog')"
                                    class="mt-8 inline-flex min-h-[48px] min-w-[200px] items-center justify-center rounded-lg bg-blue-700 px-8 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
                                >
                                    Đi tới tra cứu sách
                                </Link>
                            </div>
                        </div>

                        <div v-else class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start lg:gap-6">
                            <!-- Cột trái: danh sách (nền xám + thẻ trắng từng dòng — kiểu giỏ hàng 2 cột) -->
                            <section class="min-w-0">
                                <div class="rounded-2xl bg-slate-100/90 p-4 dark:bg-slate-950/40">
                                    <div
                                        class="mb-3 hidden rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200/80 lg:flex lg:min-w-0 lg:items-center lg:gap-3 dark:bg-slate-900 dark:ring-slate-700/80"
                                    >
                                        <span class="admin-table-checkbox-wrap shrink-0">
                                            <input
                                                id="digital-cart-select-all"
                                                type="checkbox"
                                                :checked="allDigitalSelectableSelected"
                                                class="admin-table-checkbox"
                                                @change="(e) => setDigitalSelectAll(e.target.checked)"
                                            />
                                        </span>
                                        <span class="w-14 shrink-0" aria-hidden="true"></span>
                                        <label
                                            class="min-w-0 flex-1 cursor-pointer text-sm font-semibold text-slate-700 dark:text-slate-200"
                                            for="digital-cart-select-all"
                                        >Đã chọn {{ digitalSelectedCount }} tài liệu</label>
                                        <span class="w-[120px] shrink-0 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Giá</span>
                                        <span class="w-11 shrink-0 text-right"><span class="sr-only">Xóa</span></span>
                                    </div>

                                    <div class="mb-3 rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200/80 lg:hidden dark:bg-slate-900 dark:ring-slate-700/80">
                                        <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                            <span class="admin-table-checkbox-wrap">
                                                <input
                                                    type="checkbox"
                                                    :checked="allDigitalSelectableSelected"
                                                    class="admin-table-checkbox"
                                                    @change="(e) => setDigitalSelectAll(e.target.checked)"
                                                />
                                            </span>
                                            Đã chọn {{ digitalSelectedCount }} tài liệu
                                        </label>
                                    </div>

                                    <div class="space-y-3">
                                        <div
                                            v-for="row in digitalRows"
                                            :key="row.digital_asset_id"
                                            class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700/80"
                                        >
                                            <!-- Mobile -->
                                            <div class="flex flex-col gap-4 lg:hidden">
                                                <div class="flex min-w-0 items-start gap-3">
                                                    <span class="admin-table-checkbox-wrap">
                                                        <input
                                                            type="checkbox"
                                                            :checked="selectedDigitalAssetIds.includes(Number(row.digital_asset_id))"
                                                            class="admin-table-checkbox"
                                                            @change="(e) => setDigitalRowSelected(row.digital_asset_id, e.target.checked)"
                                                        />
                                                    </span>
                                                    <Link
                                                        v-if="digitalItemBookHref(row)"
                                                        :href="digitalItemBookHref(row)"
                                                        class="flex min-w-0 flex-1 items-start gap-3 rounded-lg transition hover:bg-slate-50 dark:hover:bg-slate-800/80"
                                                    >
                                                        <div class="h-20 w-14 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-100 dark:border-slate-600 dark:bg-slate-800">
                                                            <img
                                                                v-if="row.cover_image"
                                                                :src="row.cover_image"
                                                                alt=""
                                                                loading="lazy"
                                                                class="h-full w-full object-cover"
                                                                @error="withFallback('/images/default-book-cover.png')($event)"
                                                            />
                                                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                                                <Icon icon="lucide:file-text" class="h-5 w-5" />
                                                            </div>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="line-clamp-2 text-sm font-bold text-slate-900 hover:text-blue-700 dark:text-slate-100 dark:hover:text-blue-300">
                                                                {{ row.book_title || row.file_name }}
                                                            </p>
                                                            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ row.file_name || 'Tài liệu số' }}</p>
                                                        </div>
                                                    </Link>
                                                    <div v-else class="flex min-w-0 flex-1 items-center gap-3 rounded-lg">
                                                        <div class="h-20 w-14 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-100 dark:border-slate-600 dark:bg-slate-800">
                                                            <img
                                                                v-if="row.cover_image"
                                                                :src="row.cover_image"
                                                                alt=""
                                                                loading="lazy"
                                                                class="h-full w-full object-cover"
                                                                @error="withFallback('/images/default-book-cover.png')($event)"
                                                            />
                                                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                                                <Icon icon="lucide:file-text" class="h-5 w-5" />
                                                            </div>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="line-clamp-2 text-sm font-bold text-slate-900 hover:text-blue-700 dark:text-slate-100 dark:hover:text-blue-300">
                                                                {{ row.book_title || row.file_name }}
                                                            </p>
                                                            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ row.file_name || 'Tài liệu số' }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Giá</span>
                                                    <p class="text-base font-bold tabular-nums text-slate-900 dark:text-white">
                                                        {{ (row.price_vnd || 0).toLocaleString('vi-VN') }} đ
                                                    </p>
                                                </div>
                                                <div class="flex justify-end">
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30"
                                                        :aria-label="'Xóa ' + (row.book_title || row.file_name || 'tài liệu')"
                                                        @click="removeDigitalItem(row.digital_asset_id)"
                                                    >
                                                        <Icon icon="lucide:trash-2" class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Desktop -->
                                            <div class="hidden min-w-0 lg:flex lg:items-center lg:gap-3">
                                                <span class="admin-table-checkbox-wrap shrink-0">
                                                    <input
                                                        type="checkbox"
                                                        :checked="selectedDigitalAssetIds.includes(Number(row.digital_asset_id))"
                                                        class="admin-table-checkbox"
                                                        @change="(e) => setDigitalRowSelected(row.digital_asset_id, e.target.checked)"
                                                    />
                                                </span>
                                                <div class="h-20 w-14 shrink-0 self-center overflow-hidden rounded-md border border-slate-200 bg-slate-100 dark:border-slate-600 dark:bg-slate-800">
                                                    <img
                                                        v-if="row.cover_image"
                                                        :src="row.cover_image"
                                                        alt=""
                                                        loading="lazy"
                                                        class="h-full w-full object-cover"
                                                        @error="withFallback('/images/default-book-cover.png')($event)"
                                                    />
                                                    <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                                        <Icon icon="lucide:file-text" class="h-5 w-5" />
                                                    </div>
                                                </div>
                                                <Link
                                                    v-if="digitalItemBookHref(row)"
                                                    :href="digitalItemBookHref(row)"
                                                    class="block min-w-0 flex-1 self-center rounded-lg py-0.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/80"
                                                >
                                                    <p class="line-clamp-2 text-sm font-bold text-slate-900 hover:text-blue-700 dark:text-slate-100 dark:hover:text-blue-300">{{ row.book_title || row.file_name }}</p>
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ row.file_name || 'Tài liệu số' }}</p>
                                                </Link>
                                                <div v-else class="min-w-0 flex-1 self-center">
                                                    <p class="line-clamp-2 text-sm font-bold text-slate-900 dark:text-slate-100">{{ row.book_title || row.file_name }}</p>
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ row.file_name || 'Tài liệu số' }}</p>
                                                </div>
                                                <div class="w-[120px] shrink-0 self-center text-center">
                                                    <p class="text-sm font-bold tabular-nums text-slate-900 dark:text-white">{{ (row.price_vnd || 0).toLocaleString('vi-VN') }} đ</p>
                                                </div>
                                                <div class="flex w-11 shrink-0 items-center justify-end self-center">
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30"
                                                        :aria-label="'Xóa ' + (row.book_title || row.file_name || 'tài liệu')"
                                                        @click="removeDigitalItem(row.digital_asset_id)"
                                                    >
                                                        <Icon icon="lucide:trash-2" class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Cột phải: tóm tắt luôn hiện (Fahasa — nút chính khi chưa chọn thì disabled) -->
                            <aside class="min-w-0 space-y-3 lg:sticky lg:top-24">
                                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700/80">
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Tóm tắt thanh toán</h3>
                                    <p
                                        v-if="digitalSelectedCount === 0"
                                        class="mt-2 rounded-lg bg-white/80 px-3 py-2 text-xs leading-relaxed text-slate-600 dark:bg-slate-800/80 dark:text-slate-400"
                                    >
                                        Chọn ít nhất một tài liệu trong bảng bên trái, rồi bấm «Tiếp tục thanh toán».
                                    </p>
                                    <div class="mt-3 space-y-2 text-sm">
                                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                            <span>Mục đã chọn</span>
                                            <span class="font-semibold">{{ digitalSelectedCount }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                                            <span>Tạm tính</span>
                                            <span class="font-semibold tabular-nums">{{ digitalSelectedTotalPrice.toLocaleString('vi-VN') }} đ</span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-4 flex min-h-[48px] w-full items-center justify-center rounded-lg px-4 text-sm font-bold text-white transition"
                                        :class="
                                            digitalSelectedCount > 0
                                                ? 'bg-blue-700 shadow-sm hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500'
                                                : 'cursor-not-allowed bg-slate-300 text-slate-500 dark:bg-slate-700 dark:text-slate-400'
                                        "
                                        :disabled="digitalSelectedCount === 0"
                                        @click="goPurchaseCheckout"
                                    >
                                        Tiếp tục thanh toán
                                    </button>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-200/80 bg-slate-50/90 p-4 text-xs text-slate-600 ring-1 ring-slate-200/60 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400 dark:ring-slate-700/60"
                                >
                                    Sau khi thanh toán thành công, mở trang tra cứu sách để tải PDF các tài liệu đã mua.
                                </div>
                            </aside>
                        </div>
                    </template>

                    <template v-else>
                        <section
                            class="min-w-0 overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-200/70 sm:p-6 dark:border-slate-700/90 dark:bg-slate-900/85 dark:shadow-none dark:ring-slate-600/80"
                            aria-label="Thanh toán tài liệu số"
                        >
                    <!-- BƯỚC 1 checkout: thông tin + đơn hàng (chỉ mục đã chọn) -->
                    <div v-if="checkoutStep === 1" class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <section class="rounded-xl bg-slate-100/90 p-5 sm:p-6 dark:bg-slate-900/50">
                            <button
                                type="button"
                                class="mb-4 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-300"
                                @click="backToPurchasePick"
                            >
                                ← {{ payment_checkout_only ? 'Quay lại tra cứu sách' : 'Quay lại chọn tài liệu' }}
                            </button>
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                                <Icon icon="lucide:user" class="h-5 w-5 text-blue-700 dark:text-blue-400" />
                                Thông tin khách hàng
                            </h2>
                            <div class="mt-5 space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Họ và tên *</label>
                                    <input
                                        v-model="customer.name"
                                        type="text"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                    />
                                    <p v-if="page.props.auth?.user?.name && customer.name === page.props.auth.user.name" class="mt-1 flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-400">
                                        <Icon icon="lucide:check-circle-2" class="h-3.5 w-3.5" /> Đã điền từ tài khoản của bạn
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email *</label>
                                    <input
                                        v-model="customer.email"
                                        type="email"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                    />
                                    <p v-if="page.props.auth?.user?.email && customer.email === page.props.auth.user.email" class="mt-1 flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-400">
                                        <Icon icon="lucide:check-circle-2" class="h-3.5 w-3.5" /> Đã điền từ tài khoản của bạn
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Số điện thoại</label>
                                    <input
                                        v-model="customer.phone"
                                        type="tel"
                                        class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                    />
                                    <p
                                        v-if="customerPhoneFilledFromProfile"
                                        class="mt-1 flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-400"
                                    >
                                        <Icon icon="lucide:check-circle-2" class="h-3.5 w-3.5" /> Đã điền từ tài khoản của bạn
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ghi chú đơn hàng</label>
                                    <textarea
                                        v-model="customer.note"
                                        rows="3"
                                        class="mt-1.5 w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                        placeholder="Ghi chú cho đơn hàng (tùy chọn)"
                                    ></textarea>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl bg-slate-100/90 p-5 sm:p-6 dark:bg-slate-900/50">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Đơn hàng ({{ selectedDigitalRows.length }} mục)</h2>

                            <div v-if="selectedDigitalRows.length === 0" class="mt-3 max-w-lg text-left text-sm text-slate-500 dark:text-slate-400">
                                Không có mục nào được chọn. Hãy quay lại bước chọn tài liệu.
                            </div>
                            <div v-else class="mt-4 max-h-[300px] space-y-3 overflow-y-auto pr-2">
                                <div v-for="row in selectedDigitalRows" :key="row.digital_asset_id" class="flex gap-4 rounded-lg bg-slate-100/70 p-3 dark:bg-slate-800/40">
                                    <div class="h-16 w-12 shrink-0 overflow-hidden rounded bg-slate-200 dark:bg-slate-700">
                                        <img v-if="row.cover_image" :src="row.cover_image" alt="" class="h-full w-full object-cover" @error="withFallback('/images/default-book-cover.png')($event)" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-bold text-slate-900 dark:text-white">{{ row.book_title || row.file_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ row.file_name || 'Tài liệu số' }} × 1</p>
                                        <p class="mt-1 text-sm font-bold text-blue-800 dark:text-blue-300">{{ (row.price_vnd || 0).toLocaleString('vi-VN') }} đ</p>
                                    </div>
                                </div>
                            </div>

                            <dl class="mt-6 space-y-2 border-t border-slate-200 pt-4 text-sm dark:border-slate-700">
                                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                    <dt>Tạm tính</dt>
                                    <dd class="font-semibold text-slate-900 dark:text-slate-200">{{ digitalSelectedTotalPrice.toLocaleString('vi-VN') }} đ</dd>
                                </div>
                                <div class="flex justify-between border-t border-slate-200 pt-3 text-base dark:border-slate-700">
                                    <dt class="font-bold text-slate-900 dark:text-white">Tổng cộng</dt>
                                    <dd class="font-black text-slate-900 dark:text-white">{{ digitalSelectedTotalPrice.toLocaleString('vi-VN') }} đ</dd>
                                </div>
                            </dl>
                            <button
                                type="button"
                                @click="() => { if (canProceedCheckoutStep1 && selectedDigitalRows.length > 0) checkoutStep = 2 }"
                                class="mt-6 flex min-h-[48px] w-full items-center justify-center rounded-xl bg-blue-700 px-4 text-base font-bold text-white transition hover:bg-blue-800 disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-500"
                                :disabled="selectedDigitalRows.length === 0 || !canProceedCheckoutStep1"
                            >
                                Tiếp tục
                            </button>
                        </section>
                    </div>

                    <!-- BƯỚC 2: Chọn phương thức & Đặt hàng -->
                    <div v-else-if="checkoutStep === 2" class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <section class="rounded-xl bg-slate-100/90 p-5 sm:p-6 dark:bg-slate-900/50">
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                                <Icon icon="lucide:credit-card" class="h-5 w-5 text-blue-700 dark:text-blue-400" />
                                Phương thức thanh toán
                            </h2>
                            <div class="mt-4 rounded-xl bg-blue-50/90 p-4 dark:bg-blue-950/25">
                                <label class="flex cursor-pointer gap-3">
                                    <input type="radio" checked class="mt-1 h-4 w-4 accent-blue-700" />
                                    <span>
                                        <span class="block font-bold text-slate-900 dark:text-white">Chuyển khoản ngân hàng</span>
                                        <span class="mt-0.5 block text-sm text-slate-600 dark:text-slate-400">Thanh toán qua QR Code — tự động xác nhận</span>
                                    </span>
                                </label>
                            </div>
                            <button type="button" @click="checkoutStep = 1" class="mt-4 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-300">
                                ← Quay lại thông tin đơn hàng
                            </button>
                        </section>

                        <section class="rounded-xl bg-slate-100/90 p-5 sm:p-6 dark:bg-slate-900/50">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Tóm tắt</h2>
                            <dl class="mt-4 space-y-2 text-sm">
                                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                    <dt>Tạm tính</dt>
                                    <dd class="font-semibold text-slate-900 dark:text-slate-200">{{ digitalSelectedTotalPrice.toLocaleString('vi-VN') }} đ</dd>
                                </div>
                                <div class="flex justify-between border-t border-slate-200 pt-3 text-lg dark:border-slate-700">
                                    <dt class="font-bold text-slate-900 dark:text-white">Tổng cộng</dt>
                                    <dd class="font-black text-slate-900 dark:text-white">{{ digitalSelectedTotalPrice.toLocaleString('vi-VN') }} đ</dd>
                                </div>
                            </dl>
                            <p v-if="paymentError" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200">
                                {{ paymentError }}
                            </p>
                            <button
                                type="button"
                                @click="createPaymentOrderForDigitalCart"
                                class="mt-6 flex min-h-[48px] w-full items-center justify-center rounded-xl bg-blue-700 px-4 text-base font-bold text-white transition hover:bg-blue-800 disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-500"
                                :disabled="paymentLoading"
                            >
                                {{ paymentLoading ? 'Đang tạo đơn…' : 'Đặt Hàng Ngay' }}
                            </button>
                            <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400">Bằng cách đặt hàng, bạn đồng ý với điều khoản dịch vụ.</p>
                        </section>
                    </div>

                    <!-- BƯỚC 3: QR Code — khung gọn, căn giữa -->
                    <div v-else-if="checkoutStep === 3" class="mx-auto w-full min-w-0 max-w-3xl">
                            <div class="grid min-w-0 gap-4 lg:grid-cols-2 lg:items-stretch">
                                <section class="flex min-w-0 flex-col rounded-xl border border-slate-200/80 bg-slate-100/90 p-4 sm:p-5 dark:border-slate-700/80 dark:bg-slate-900/50">
                                    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white sm:text-base">
                                        <Icon icon="lucide:qr-code" class="h-5 w-5 shrink-0 text-blue-700 dark:text-blue-400" />
                                        Thông tin chuyển khoản
                                    </h2>
                                    <div v-if="paymentOrder?.qr_image_url" class="mt-3 flex flex-col items-center rounded-lg bg-white/90 p-3 dark:bg-slate-800/50">
                                        <img
                                            :src="paymentOrder.qr_image_url"
                                            alt="QR VietQR"
                                            class="h-auto max-h-[200px] w-full max-w-[200px] object-contain"
                                        />
                                        <p class="mt-2 text-center text-[11px] font-medium text-slate-600 dark:text-slate-400">Quét mã QR để chuyển khoản</p>
                                    </div>
                                    <div class="mt-4 space-y-2 text-sm">
                                        <div class="flex justify-between gap-3 rounded-lg bg-slate-100/80 px-3 py-2.5 dark:bg-slate-800/50">
                                            <span class="text-slate-500 dark:text-slate-400">Số tiền</span>
                                            <span class="font-bold text-slate-900 dark:text-white">{{ Number(paymentOrder?.amount_vnd || 0).toLocaleString('vi-VN') }} đ</span>
                                        </div>
                                        <div class="rounded-lg bg-slate-100/80 px-3 py-2.5 dark:bg-slate-800/50">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nội dung chuyển khoản</p>
                                            <p class="mt-1 break-all font-mono text-xs font-bold text-blue-800 sm:text-sm dark:text-blue-300">{{ paymentOrder?.merchant_reference }}</p>
                                            <button
                                                type="button"
                                                class="mt-2 inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                                @click="copyText(paymentOrder?.merchant_reference)"
                                            >
                                                <Icon icon="lucide:copy" class="h-4 w-4 shrink-0" />
                                                Sao chép nội dung CK
                                            </button>
                                        </div>
                                    </div>
                                </section>

                                <section class="flex min-w-0 flex-col rounded-xl border border-slate-200/80 bg-slate-100/90 p-4 sm:p-5 dark:border-slate-700/80 dark:bg-slate-900/50">
                                    <h2 class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">Chi tiết đơn hàng</h2>
                                    <div
                                        v-if="paymentOrder?.public_id"
                                        class="mt-3 rounded-lg border border-slate-200/80 bg-slate-100/80 px-3 py-2.5 dark:border-slate-600/80 dark:bg-slate-800/50"
                                    >
                                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Đơn hàng</p>
                                        <p class="mt-1 break-all font-mono text-sm font-bold text-slate-900 dark:text-white">#{{ paymentOrder.public_id }}</p>
                                        <p v-if="paymentOrder?.status === 'paid'" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                            <Icon icon="lucide:check-circle-2" class="h-4 w-4" /> Đã thanh toán
                                        </p>
                                        <p v-else class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-amber-700 dark:text-amber-400">
                                            <Icon icon="lucide:clock" class="h-4 w-4" /> Chờ thanh toán
                                        </p>
                                    </div>
                                    <div class="mt-3 rounded-lg bg-slate-100/80 p-3 dark:bg-slate-800/50">
                                        <dl class="space-y-2 text-sm">
                                            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3">
                                                <dt class="shrink-0 text-slate-500 dark:text-slate-400">Tên</dt>
                                                <dd class="min-w-0 break-words text-left font-medium text-slate-900 sm:text-right dark:text-white">{{ customer.name }}</dd>
                                            </div>
                                            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3">
                                                <dt class="shrink-0 text-slate-500 dark:text-slate-400">Email</dt>
                                                <dd class="min-w-0 break-all text-left font-medium text-slate-900 sm:text-right dark:text-white">{{ customer.email }}</dd>
                                            </div>
                                        </dl>
                                        <div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-600">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-sm font-bold text-slate-900 dark:text-white">Tổng cộng</span>
                                                <span class="text-lg font-black text-blue-800 dark:text-blue-300">{{ Number(paymentOrder?.amount_vnd || 0).toLocaleString('vi-VN') }} đ</span>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="mt-4 min-w-0 overflow-hidden rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-200/70 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-600/80">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Xác nhận thanh toán</h3>
                                    <span
                                        v-if="paymentPolling"
                                        class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-900 dark:bg-blue-950/60 dark:text-blue-200"
                                    >
                                        <Icon icon="lucide:loader-2" class="h-3.5 w-3.5 shrink-0 animate-spin" />
                                        <span class="truncate">Đang đối soát tự động</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-900 dark:bg-amber-950/50 dark:text-amber-200"
                                    >
                                        <Icon icon="lucide:clock" class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">Chờ xác nhận</span>
                                    </span>
                                </div>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                    Sau khi chuyển khoản đúng <strong class="font-semibold text-slate-800 dark:text-slate-200">số tiền</strong> và
                                    <strong class="font-semibold text-slate-800 dark:text-slate-200">nội dung CK</strong>, hệ thống tự kiểm tra mỗi 2 giây. Bạn cũng có thể bấm kiểm tra ngay bên dưới.
                                </p>
                                <p
                                    v-if="paymentPollError"
                                    class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-left text-xs leading-relaxed text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-100"
                                    role="alert"
                                >
                                    {{ paymentPollError }}
                                </p>
                                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
                                        :disabled="manualCheckLoading || cancelOrderLoading"
                                        @click="manualCheckPayment"
                                    >
                                        <Icon icon="lucide:refresh-cw" class="h-5 w-5 shrink-0" :class="{ 'animate-spin': manualCheckLoading }" />
                                        {{ manualCheckLoading ? 'Đang kiểm tra…' : 'Kiểm tra ngay' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center rounded-xl border border-rose-300 bg-white px-4 text-sm font-bold text-rose-800 transition hover:bg-rose-50 disabled:opacity-50 dark:border-rose-800/60 dark:bg-slate-900 dark:text-rose-200 dark:hover:bg-rose-950/40"
                                        :disabled="cancelOrderLoading || manualCheckLoading"
                                        @click="openDigitalCancelOrderDialog"
                                    >
                                        Hủy đơn hàng
                                    </button>
                                </div>
                            </div>
                    </div>


                        </section>
                    </template>
                </div>

            </div>
            </div>
        </div>
        <Teleport to="body">
            <div
                v-if="digitalCancelOrderDialogOpen"
                class="fixed inset-0 z-[120] flex items-end justify-center bg-black/50 p-4 sm:items-center"
                role="dialog"
                aria-modal="true"
                aria-labelledby="digital-cancel-order-title"
                @click.self="closeDigitalCancelOrderDialog"
            >
                <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900" @click.stop>
                    <h2 id="digital-cancel-order-title" class="text-lg font-bold text-slate-900 dark:text-white">Hủy đơn hàng?</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        Đơn chờ thanh toán sẽ bị hủy trên hệ thống. Bạn có thể tạo đơn thanh toán mới ở bước trước.
                    </p>
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="inline-flex min-h-[48px] w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-800 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 sm:w-auto"
                            :disabled="cancelOrderLoading"
                            @click="closeDigitalCancelOrderDialog"
                        >
                            Đóng
                        </button>
                        <button
                            type="button"
                            class="inline-flex min-h-[48px] w-full items-center justify-center rounded-xl bg-rose-700 px-4 text-sm font-bold text-white transition hover:bg-rose-800 disabled:opacity-60 dark:bg-rose-600 dark:hover:bg-rose-500 sm:w-auto"
                            :disabled="cancelOrderLoading"
                            @click="confirmCancelDigitalPaymentOrder"
                        >
                            <Icon v-if="cancelOrderLoading" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                            Xác nhận hủy
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </ReaderLayout>
</template>
