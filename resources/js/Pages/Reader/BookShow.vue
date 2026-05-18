<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerBookShowStrings as S } from '@/config/readerStrings'
import { toast } from '@/store/toast'
import { meBorrowRequestsApi } from '@/api/meBorrowRequests'
import { useImageFallback } from '@/composables/useImageFallback'
import { digitalPurchaseCartApi } from '@/api/digitalPurchaseCart'
import {
    READER_BORROW_CART_KEY as CART_KEY,
    buildDigitalBuyNowRow,
    notifyReaderCartUpdated,
    stashDigitalBuyNowRow,
} from '@/config/readerCartKeys'
import { markReaderCatalogBooksStale } from '@/config/readerCatalogRefresh'
import { startBrowserDownload } from '@/utils/downloadAuthenticatedFile'
import RichHtmlContent from '@/Components/Shared/RichHtmlContent.vue'
import ReaderRelatedBooksSection from '@/Components/Reader/ReaderRelatedBooksSection.vue'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)
const isDigitalBook = computed(() => String(props.book?.resource_type || '') === 'digital')

const digitalStats = computed(() => (props.digital_stats && typeof props.digital_stats === 'object' ? props.digital_stats : null))

function normalizeDigitalAssetsPayload(raw) {
    if (Array.isArray(raw)) {
        return raw
    }
    if (raw && typeof raw === 'object' && Array.isArray(raw.data)) {
        return raw.data
    }
    return []
}

const digitalAssetsList = computed(() => normalizeDigitalAssetsPayload(props.book?.digital_assets))

const hasReaderDigitalFile = computed(() => {
    if (digitalAssetsList.value.length > 0) {
        return true
    }
    if (props.book?.has_digital_attachment === true) {
        return true
    }
    const sid = props.digital_stats?.digital_asset_id
    return Number.isFinite(Number(sid)) && Number(sid) > 0
})

const primaryDigitalAsset = computed(() => {
    if (digitalAssetsList.value.length) {
        return digitalAssetsList.value[0]
    }
    const fromStats = props.digital_stats?.digital_asset_id
    if (Number.isFinite(Number(fromStats)) && Number(fromStats) > 0) {
        return { id: Number(fromStats) }
    }
    const pid = props.book?.primary_digital_asset_id
    if (Number.isFinite(Number(pid)) && Number(pid) > 0) {
        return { id: Number(pid) }
    }
    return null
})

/** Danh sách hiển thị khối «Đồ án, luận văn» đính kèm sách in (fallback khi prop digital_assets lỗi định dạng nhưng vẫn có id). */
const digitalAssetsListForSection = computed(() => {
    if (digitalAssetsList.value.length) {
        return digitalAssetsList.value
    }
    const one = primaryDigitalAsset.value
    return one?.id ? [one] : []
})

const digitalAttachmentName = computed(() => {
    if (!isDigitalBook.value) return ''
    const first = digitalAssetsListForSection.value?.[0]
    const name = String(first?.original_name || '').trim()
    return name || 'PDF đồ án, luận văn'
})
const creatingBorrowRequest = ref(false)
const buyingDigitalAssetId = ref(null)
const borrowQty = ref(1)
const showBorrowPreview = ref(false)
const loanType = ref('home')
const requestedDueDate = ref('')
const borrowSubmitError = ref('')
const todayIso = computed(() => new Date().toISOString().slice(0, 10))
const { withFallback } = useImageFallback()

function formatReaderStatCount(value) {
    const n = Number(value ?? 0)
    return Number.isFinite(n) && n >= 0 ? n.toLocaleString('vi-VN') : '0'
}


const maxDueIso = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() + 1)
    return d.toISOString().slice(0, 10)
})

function extractBorrowRequestApiError(error, fallback) {
    const data = error?.response?.data || {}

    const errors = data?.errors
    if (errors && typeof errors === 'object') {
        const lists = Object.values(errors).filter((x) => Array.isArray(x))
        const first = lists.flat().find((x) => typeof x === 'string' && x.trim())
        if (first) return normalizeBorrowValidationText(first.trim())
    }

    const top = typeof data.messages === 'string' && data.messages.trim() ? data.messages.trim() : ''
    if (top) return normalizeBorrowValidationText(top)

    const msg = typeof data.message === 'string' && data.message.trim() ? data.message.trim() : ''
    if (msg) return normalizeBorrowValidationText(msg)

    return fallback
}

function normalizeBorrowValidationText(raw) {
    let msg = String(raw || '').trim()
    if (!msg) return 'Không gửi được yêu cầu. Vui lòng kiểm tra lại thông tin.'
    msg = msg.replace(/The\s+requested\s+due\s+date\s+field\s+must\s+be\s+a\s+date\s+after\s+or\s+equal\s+to\s+today\.?/gi, 'Ngày trả dự kiến phải từ hôm nay trở đi.')
    msg = msg.replace(/requested_due_date/gi, 'Ngày trả dự kiến')
    return msg
}

onMounted(() => {
    toast.clearAll()
    try {
        const u = new URL(page.url, typeof window !== 'undefined' ? window.location.origin : 'http://localhost')
        if (u.searchParams.get('checkout') === '1') {
            goDigitalCheckout()
        }
    } catch {
        //
    }
})

onBeforeUnmount(() => {
    markReaderCatalogBooksStale()
})

function downloadDigitalPdf(asset) {
    if (!asset?.id || downloadingPdfAssetId.value != null) {
        return
    }
    const url = resolveDigitalAssetDownloadPdfUrl(asset.id)
    if (!startBrowserDownload(url)) {
        return
    }

    downloadingPdfAssetId.value = Number(asset.id)
    toast.info('Đã gửi yêu cầu tải — theo dõi ở thanh tải của trình duyệt.', {
        title: 'Tải tài liệu',
        duration: 4500,
    })

    window.setTimeout(() => {
        if (Number(downloadingPdfAssetId.value) === Number(asset.id)) {
            downloadingPdfAssetId.value = null
        }
    }, 2500)
}

function isDownloadingPdf(asset) {
    return downloadingPdfAssetId.value != null && Number(downloadingPdfAssetId.value) === Number(asset?.id)
}

function goDigitalCheckout() {
    try {
        router.visit(`${route('reader.services.book-cart')}?tab=purchase`)
    } catch {
        router.visit('/dich-vu/gio-sach?tab=purchase')
    }
}

/** Mua ngay: xóa khỏi giỏ (nếu có) rồi mở thẳng bước thanh toán — không thêm vào giỏ. */
async function buyDigitalAssetNow(asset, bookRow) {
    if (!asset?.id) return
    if (!isAuthed.value) {
        toast.warn('Vui lòng đăng nhập để thanh toán tài liệu số.', { title: 'Thanh toán' })
        return
    }
    const assetId = Number(asset.id)
    buyingDigitalAssetId.value = assetId
    try {
        try {
            await digitalPurchaseCartApi.removeItem(assetId)
            notifyReaderCartUpdated('digital')
        } catch {
            /* Không có trong giỏ — bỏ qua. */
        }
        const row = buildDigitalBuyNowRow(asset, bookRow)
        if (!row) {
            toast.error('Không mở được thanh toán.', { title: 'Thanh toán' })
            return
        }
        stashDigitalBuyNowRow(row)
        const qs = new URLSearchParams({ buy_asset: String(assetId) })
        try {
            router.visit(`${route('reader.services.digital-payment')}?${qs}`)
        } catch {
            router.visit(`/dich-vu/thanh-toan?${qs}`)
        }
    } finally {
        buyingDigitalAssetId.value = null
    }
}

/** Trang xem trước PDF trong app (iframe + nút quay lại). */
function resolveDigitalAssetPreviewPageUrl(assetId, bookId = null) {
    if (!assetId) {
        return '#'
    }
    const bid = Number(bookId ?? props.book?.id ?? 0)
    if (!Number.isFinite(bid) || bid <= 0) {
        return '#'
    }
    try {
        return route('reader.catalog.digital-preview', { book: bid, digital_asset: assetId })
    } catch {
        return `/tra-cuu-sach/${bid}/tai-lieu/${assetId}/xem-truoc`
    }
}

function assetPreviewReady(asset) {
    if (!asset?.id) {
        return false
    }
    if (asset.preview_status === 'ready') {
        return true
    }
    return asset.preview_available === true && Boolean(asset?.preview_url)
}

function assetPreviewDisabled(asset) {
    return asset?.preview_status === 'disabled'
}

/** Có thể bấm xem trước (sẵn sàng hoặc đang chờ tạo — hiện thông báo). */
function assetPreviewActionVisible(asset) {
    if (!asset?.id || assetPreviewDisabled(asset)) {
        return false
    }
    return assetPreviewReady(asset) || asset.preview_status === 'pending' || asset.preview_status === 'processing' || asset.preview_status === 'failed' || Boolean(asset?.preview_url)
}

function openDigitalPreview(asset) {
    if (!asset?.id) {
        return
    }
    if (assetPreviewReady(asset)) {
        router.visit(resolveDigitalAssetPreviewPageUrl(asset.id))
        return
    }
    const status = String(asset.preview_status || '')
    if (status === 'pending' || status === 'processing') {
        toast.info(S.previewPdfProcessing, { title: S.previewPdf })
        return
    }
    toast.warn(S.previewPdfUnavailable, { title: S.previewPdf })
}

const guestDigitalPreviewReady = computed(
    () =>
        isDigitalBook.value &&
        hasReaderDigitalFile.value &&
        primaryDigitalAsset.value &&
        assetPreviewReady(primaryDigitalAsset.value)
)

const guestDigitalPreviewActionVisible = computed(
    () =>
        isDigitalBook.value &&
        hasReaderDigitalFile.value &&
        primaryDigitalAsset.value &&
        assetPreviewActionVisible(primaryDigitalAsset.value)
)

/** Tải file PDF gốc (session auth) — route web reader.catalog.digital-download-pdf. */
function resolveDigitalAssetDownloadPdfUrl(assetId, bookId = null) {
    if (!assetId) {
        return '#'
    }
    const bid = Number(bookId ?? props.book?.id ?? 0)
    if (!Number.isFinite(bid) || bid <= 0) {
        return '#'
    }
    try {
        return route('reader.catalog.digital-download-pdf', { book: bid, digital_asset: assetId })
    } catch {
        return `/tra-cuu-sach/${bid}/tai-lieu/${assetId}/tai-pdf`
    }
}

/**
 * Quyền tải PDF (API): tài liệu tự gửi đã duyệt, đã thanh toán, hoặc paywall tắt.
 * Không hiện giỏ / mua khi true.
 */
function assetCanDownloadPdf(asset) {
    if (!asset?.id) {
        return false
    }
    if (asset.user_can_download_pdf === true) {
        return true
    }
    if (isDigitalBook.value && Number(primaryDigitalAsset.value?.id) === Number(asset.id)) {
        return props.digital_stats?.user_can_download_pdf === true
    }
    return false
}

function assetIsOwnApprovedSubmission(asset) {
    if (asset?.is_own_approved_submission === true) {
        return true
    }
    if (isDigitalBook.value && Number(primaryDigitalAsset.value?.id) === Number(asset.id)) {
        return props.digital_stats?.is_own_approved_submission === true
    }
    return false
}

function assetPdfDownloadHint(asset) {
    if (assetIsOwnApprovedSubmission(asset)) {
        return 'Tài liệu do bạn gửi và đã được thủ thư duyệt — tải PDF miễn phí.'
    }
    return 'Bạn đã có quyền tải toàn bộ PDF.'
}

async function addToDigitalPurchaseCart(asset, bookRow) {
    if (!asset?.id) return
    if (!isAuthed.value) {
        toast.warn('Vui lòng đăng nhập để thêm vào giỏ thanh toán.', { title: 'Giỏ sách' })
        return
    }
    const bid = Number(bookRow?.id ?? 0)
    const title = String(bookRow?.title || '').trim()
    const fileLabel = String(asset?.original_name || '').trim()
    const cover = String(bookRow?.cover_image || '').trim()
    try {
        await digitalPurchaseCartApi.addItem({
            digital_asset_id: Number(asset.id),
            book_id: Number.isFinite(bid) && bid > 0 ? bid : undefined,
            book_title: title || undefined,
            file_name: fileLabel || undefined,
            cover_image: cover || undefined,
        })
        notifyReaderCartUpdated('digital')
        toast.success('Đã thêm vào giỏ sách (mục thanh toán tài liệu số).', { title: 'Giỏ sách' })
    } catch (e) {
        const msg = e?.response?.data?.messages || e?.response?.data?.message || 'Không thêm được vào giỏ.'
        toast.error(typeof msg === 'string' ? msg : 'Không thêm được vào giỏ.', { title: 'Giỏ sách' })
    }
}

function validateBorrowRequestClient() {
    const maxAvail = Math.max(0, Number(props.availability?.available || 0))
    if (maxAvail <= 0) {
        return 'Sách hiện đã hết khả dụng để tạo yêu cầu mượn.'
    }
    const qty = Math.max(1, Number(borrowQty.value || 1))
    if (qty > maxAvail) {
        return `Số lượng mượn không được vượt quá ${maxAvail} (theo số sách khả dụng).`
    }
    const perm = props.borrow_permissions
    if (perm && loanType.value === 'home' && !perm.allow_home) {
        return 'Theo quy định thư viện UTC, loại thẻ của bạn chỉ được đăng ký đọc tại chỗ, không mượn về nhà.'
    }
    if (perm && loanType.value === 'onsite' && !perm.allow_onsite) {
        return 'Hình thức đọc tại chỗ không áp dụng với loại thẻ của bạn. Vui lòng liên hệ thủ thư.'
    }
    if (loanType.value !== 'home') {
        return null
    }
    const raw = String(requestedDueDate.value || '').trim()
    if (!raw) {
        return 'Vui lòng chọn ngày trả dự kiến khi mượn về nhà.'
    }
    if (raw < todayIso.value) {
        return 'Ngày trả dự kiến không được trước ngày hiện tại.'
    }
    if (raw > maxDueIso.value) {
        return 'Ngày trả dự kiến không được quá một năm kể từ hôm nay.'
    }
    return null
}

const props = defineProps({
    book: { type: Object, required: true },
    availability: {
        type: Object,
        required: true,
        validator: (v) => v && typeof v.total === 'number',
    },
    digital_stats: { type: Object, default: null },
    /** Lượt xem trang chi tiết (sách in). */
    reader_view_count: { type: Number, default: null },
    has_active_library_card: { type: Boolean, default: false },
    /** @type {{ allow_home: boolean, allow_onsite: boolean, holder_type: string }|null} */
    borrow_permissions: { type: Object, default: null },
    related_books: { type: Array, default: () => [] },
})

const downloadingPdfAssetId = ref(null)
const localDigitalStats = ref(props.digital_stats ? { ...props.digital_stats } : null)
const localReaderViewCount = ref(props.reader_view_count ?? null)

watch(
    () => props.digital_stats,
    (stats) => {
        if (stats && typeof stats === 'object') {
            localDigitalStats.value = { ...stats }
        }
    },
    { deep: true },
)
watch(
    () => props.reader_view_count,
    (count) => {
        if (count != null) {
            localReaderViewCount.value = count
        }
    },
)

const hasActiveLibraryCard = computed(() => Boolean(props.has_active_library_card))

const canBorrowHome = computed(() => {
    const p = props.borrow_permissions
    if (!p) {
        return true
    }
    return Boolean(p.allow_home)
})

const canBorrowOnsite = computed(() => {
    const p = props.borrow_permissions
    if (!p) {
        return true
    }
    return Boolean(p.allow_onsite)
})

function syncLoanTypeToPermissions() {
    const p = props.borrow_permissions
    if (!p) {
        return
    }
    if (!p.allow_home && p.allow_onsite) {
        loanType.value = 'onsite'
    } else if (p.allow_home && !p.allow_onsite) {
        loanType.value = 'home'
    }
}

watch(
    () => props.borrow_permissions,
    () => syncLoanTypeToPermissions(),
    { immediate: true, deep: true }
)

async function createBorrowRequestSingle() {
    if (!isAuthed.value || creatingBorrowRequest.value) {
        return
    }
    if (!hasActiveLibraryCard.value) {
        const t = 'Bạn chưa có thẻ thư viện hoạt động. Vui lòng gửi yêu cầu cấp thẻ trước khi mượn sách.'
        toast.warn(t, { title: 'Không thể gửi yêu cầu' })
        return
    }
    if (Number(props.availability?.available || 0) <= 0) {
        const t = 'Sách hiện đã hết khả dụng để tạo yêu cầu mượn.'
        toast.warn(t, { title: 'Không thể gửi yêu cầu' })
        return
    }

    borrowSubmitError.value = ''
    const clientErr = validateBorrowRequestClient()
    if (clientErr) {
        borrowSubmitError.value = clientErr
        return
    }

    creatingBorrowRequest.value = true
    try {
        await meBorrowRequestsApi.create({
            loan_type: loanType.value,
            book_ids: [props.book.id],
            quantity: [Math.max(1, Number(borrowQty.value || 1))],
            requested_due_date: loanType.value === 'home' ? requestedDueDate.value : undefined,
        })
        borrowSubmitError.value = ''
        toast.success('Đã tạo yêu cầu mượn cho sách này.', { title: 'Yêu cầu mượn' })
        showBorrowPreview.value = false
    } catch (e) {
        borrowSubmitError.value = extractBorrowRequestApiError(e, 'Không tạo được yêu cầu mượn. Vui lòng thử lại.')
    } finally {
        creatingBorrowRequest.value = false
    }
}

function openBorrowPreview() {
    if (!isAuthed.value) {
        toast.warn('Vui lòng đăng nhập để tạo yêu cầu mượn.', { title: 'Yêu cầu mượn' })
        return
    }
    if (!hasActiveLibraryCard.value) {
        toast.warn('Bạn chưa có thẻ thư viện hoạt động. Vui lòng gửi yêu cầu cấp thẻ trước khi mượn sách.', { title: 'Yêu cầu mượn' })
        return
    }
    if (Number(props.availability?.available || 0) <= 0) {
        toast.warn('Sách hiện đã hết khả dụng để tạo yêu cầu mượn.', { title: 'Yêu cầu mượn' })
        return
    }
    borrowSubmitError.value = ''
    syncLoanTypeToPermissions()
    borrowQty.value = Math.max(1, Math.min(Number(props.availability?.available || 1), Number(borrowQty.value || 1)))
    showBorrowPreview.value = true
}

function closeBorrowPreview() {
    showBorrowPreview.value = false
    borrowSubmitError.value = ''
}

function adjustBorrowQty(delta) {
    const max = Math.max(1, Number(props.availability?.available || 1))
    const current = Math.max(1, Number(borrowQty.value || 1))
    borrowQty.value = Math.max(1, Math.min(max, current + delta))
}

function onBorrowQtyInput(event) {
    const max = Math.max(1, Number(props.availability?.available || 1))
    const next = Math.max(1, Math.min(max, Number(event?.target?.value || 1)))
    borrowQty.value = next
}

function addToBorrowCart() {
    if (!isAuthed.value) {
        toast.warn('Vui lòng đăng nhập để dùng giỏ sách.', { title: 'Giỏ sách' })
        return
    }
    const qty = Math.max(1, Math.min(Number(props.availability?.available || 1), Number(borrowQty.value || 1)))
    const maxAvailable = Math.max(1, Number(props.availability?.available || 1))
    try {
        const current = JSON.parse(localStorage.getItem(CART_KEY) || '[]')
        const items = Array.isArray(current) ? current : []
        const idx = items.findIndex((x) => Number(x.book_id) === Number(props.book.id))
        if (idx >= 0) {
            const currentQty = Math.max(1, Number(items[idx].quantity || 1))
            const nextQty = Math.min(maxAvailable, currentQty + 1)
            if (nextQty === currentQty) {
                toast.warn('Số lượng trong giỏ đã đạt tối đa theo số sách khả dụng.', { title: 'Giỏ sách' })
                return
            }
            items[idx].quantity = nextQty
            localStorage.setItem(CART_KEY, JSON.stringify(items))
            notifyReaderCartUpdated('borrow')
            toast.success('Đã thêm sách vào mục mượn trong giỏ sách.', { title: 'Giỏ sách' })
            return
        } else {
            items.push({ book_id: Number(props.book.id), quantity: qty })
        }
        localStorage.setItem(CART_KEY, JSON.stringify(items))
        notifyReaderCartUpdated('borrow')
        toast.success('Đã thêm sách vào mục mượn trong giỏ sách.', { title: 'Giỏ sách' })
    } catch {
        toast.error('Không thể thêm vào giỏ sách.', { title: 'Giỏ sách' })
    }
}

const publicationLine = computed(() => {
    const b = props.book
    const parts = []
    if (b.publishers_label) {
        parts.push(b.publishers_label)
    }
    if (b.published_year) {
        parts.push(String(b.published_year))
    }
    if (b.publisher_place) {
        parts.push(b.publisher_place)
    }
    return parts.length ? parts.join(' — ') : '—'
})

const physicalLine = computed(() => {
    const b = props.book
    const parts = []
    if (b.pages) {
        parts.push(`${b.pages} tr.`)
    }
    if (b.book_size) {
        parts.push(b.book_size)
    }
    return parts.length ? parts.join(' · ') : '—'
})

const priceFmt = computed(() => {
    const n = Number(props.book.price)
    if (!Number.isFinite(n)) {
        return '—'
    }
    return new Intl.NumberFormat('vi-VN').format(n) + ' đ'
})

const subjectLine = computed(() => {
    const b = props.book
    const c = b.classification?.name
    return c || '—'
})

const digitalDescription = computed(() => {
    const summary = String(props.book?.summary || '').trim()
    if (summary) return summary
    const paramsDesc = String(props.book?.params?.description || '').trim()
    if (paramsDesc) return paramsDesc
    const abs = String(props.book?.thesis_metadata?.abstract_text || '').trim()
    if (abs) return abs
    return ''
})

const headTitle = computed(() => `${props.book.title} — ${S.headTitleSuffix}`)

const detailPanel = ref('description')

const digitalPriceLabel = computed(() => {
    const asset = primaryDigitalAsset.value
    if (!asset?.paywall) {
        return null
    }
    const price = Number(asset.paywall.pdf_download_price_vnd ?? 0)
    if (asset.paywall.is_enabled === false || price <= 0) {
        return 'Miễn phí'
    }
    return `${price.toLocaleString('vi-VN')} ₫`
})

const specRows = computed(() => {
    const rows = []
    const b = props.book
    if (b.authors_label) {
        rows.push({ label: S.authors, value: b.authors_label })
    }
    if (!isDigitalBook.value && b.publishers_label) {
        rows.push({ label: S.publisherLabel, value: b.publishers_label })
    }
    if (subjectLine.value && subjectLine.value !== '—') {
        rows.push({ label: S.subject, value: subjectLine.value })
    }
    rows.push({ label: S.resourceType, value: b.resource_type_label || '—' })
    if (!isDigitalBook.value) {
        rows.push({ label: S.price, value: priceFmt.value })
        if (b.warehouse?.name) {
            rows.push({ label: S.warehouseLabel, value: b.warehouse.name })
        }
        if (b.cabinet) {
            rows.push({ label: S.cabinetLabel, value: b.cabinet })
        }
    }
    if (!isDigitalBook.value && publicationLine.value !== '—') {
        rows.push({ label: S.publicationInfo, value: publicationLine.value })
    }
    if (!isDigitalBook.value && physicalLine.value !== '—') {
        rows.push({ label: S.physicalDesc, value: physicalLine.value })
    }
    return rows
})

const hasDescriptionPanel = computed(() => {
    if (isDigitalBook.value) {
        return Boolean(digitalDescription.value)
    }
    return Boolean(props.book?.summary)
})

watch(hasDescriptionPanel, (has) => {
    if (!has && detailPanel.value === 'description') {
        detailPanel.value = 'details'
    }
}, { immediate: true })

const displayBookCode = computed(() => String(props.book?.book_code || '').trim())
</script>

<template>
    <ReaderLayout>
        <Head :title="headTitle" />
        <div class="mx-auto max-w-7xl animate-in fade-in-50 duration-500 px-1 sm:px-0">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 sm:mb-5">
                <nav
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm"
                    aria-label="Breadcrumb"
                >
                    <Link :href="route('reader.home')" class="hover:text-blue-800 dark:hover:text-blue-400">{{ S.breadcrumbHome }}</Link>
                    <Icon icon="lucide:chevron-right" class="h-3.5 w-3.5 shrink-0 opacity-60" aria-hidden="true" />
                    <Link :href="route('reader.catalog')" :preserve-state="false" class="hover:text-blue-800 dark:hover:text-blue-400">{{
                        S.breadcrumbCatalog
                    }}</Link>
                    <Icon icon="lucide:chevron-right" class="h-3.5 w-3.5 shrink-0 opacity-60" aria-hidden="true" />
                    <span class="line-clamp-1 font-medium text-slate-800 dark:text-slate-200">{{ book.title }}</span>
                </nav>
                <Link
                    :href="route('reader.catalog')"
                    :preserve-state="false"
                    class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-blue-600 dark:hover:bg-slate-700"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" aria-hidden="true" />
                    <span class="hidden sm:inline">{{ S.backCatalog }}</span>
                    <span class="sm:hidden">Tra cứu</span>
                </Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-12 lg:items-stretch lg:gap-8">
                <div class="flex lg:col-span-4 xl:col-span-3">
                    <div class="flex h-full w-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="flex min-h-[220px] flex-1 items-center justify-center bg-slate-50 p-3 dark:bg-slate-800/50 sm:min-h-[280px] sm:p-5">
                            <img
                                v-if="book.cover_image"
                                :src="book.cover_image"
                                :alt="book.title"
                                class="max-h-full w-full max-w-[300px] object-contain shadow-md"
                                @error="withFallback('/images/default-book-cover.png')($event)"
                            />
                            <div v-else class="flex h-full min-h-[200px] w-full max-w-[300px] items-center justify-center bg-slate-100 dark:bg-slate-800">
                                <Icon icon="lucide:book-open" class="h-20 w-20 opacity-40" aria-hidden="true" />
                                <span class="sr-only">{{ S.noCover }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex min-w-0 lg:col-span-8 xl:col-span-9">
                    <div class="flex h-full w-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div class="border-b border-slate-100 p-4 dark:border-slate-800 sm:p-6">
                            <dl class="space-y-3.5">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ S.bookTitleLabel }}</dt>
                                    <dd class="mt-1 text-xl font-bold leading-snug text-slate-900 dark:text-white sm:text-2xl">{{ book.title }}</dd>
                                </div>
                                <div v-if="book.sub_title">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ S.subTitleLabel }}</dt>
                                    <dd class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ book.sub_title }}</dd>
                                </div>
                                <div v-if="book.authors_label">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ S.authors }}</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ book.authors_label }}</dd>
                                </div>
                                <div v-if="book.publishers_label">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ S.publisherLabel }}</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ book.publishers_label }}</dd>
                                </div>
                            </dl>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1.5 text-xs font-bold text-blue-900 dark:bg-blue-950/60 dark:text-blue-100">
                                    <Icon icon="lucide:eye" class="h-3.5 w-3.5 shrink-0 opacity-90" aria-hidden="true" />
                                    {{ S.metaViews }}: {{ isDigitalBook ? formatReaderStatCount(localDigitalStats?.access_sessions) : formatReaderStatCount(localReaderViewCount) }}
                                </span>
                                <template v-if="isDigitalBook">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-900 dark:bg-rose-950/60 dark:text-rose-100">
                                        <Icon icon="lucide:download" class="h-3.5 w-3.5 shrink-0 opacity-90" aria-hidden="true" />
                                        Lượt tải: {{ formatReaderStatCount(localDigitalStats?.downloads) }}
                                    </span>
                                    <span v-if="digitalPriceLabel" class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-900 dark:bg-amber-950/60 dark:text-amber-100">
                                        <Icon icon="lucide:file-text" class="h-3.5 w-3.5 shrink-0 opacity-90" aria-hidden="true" />
                                        PDF: {{ digitalPriceLabel }}
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                                        <Icon icon="lucide:layers" class="h-3.5 w-3.5 shrink-0 opacity-90" aria-hidden="true" />
                                        {{ S.totalCopies }}: {{ availability.total }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-100">
                                        <Icon icon="lucide:book-check" class="h-3.5 w-3.5 shrink-0 opacity-90" aria-hidden="true" />
                                        {{ S.availableCopies }}: {{ availability.available }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-900 dark:bg-rose-950/60 dark:text-rose-100">
                                        <Icon icon="lucide:book-marked" class="h-3.5 w-3.5 shrink-0 opacity-90" aria-hidden="true" />
                                        {{ S.borrowedCopies }}: {{ availability.borrowed }}
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div class="border-b border-slate-100 bg-slate-50/90 p-4 dark:border-slate-800 dark:bg-slate-800/30 sm:p-6">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                {{ isDigitalBook ? S.buyBoxTitleDigital : S.buyBoxTitle }}
                            </h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ isDigitalBook ? S.buyBoxDigitalHint : S.buyBoxPhysicalHint }}</p>
                            <div class="mt-4">
                            <template v-if="!isAuthed">
                                <div v-if="guestDigitalPreviewActionVisible" class="space-y-3">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 text-sm font-bold text-blue-900 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-100 dark:hover:bg-blue-900/50"
                                        @click="openDigitalPreview(primaryDigitalAsset)"
                                    >
                                        <Icon icon="lucide:eye" class="h-5 w-5 shrink-0" aria-hidden="true" />
                                        {{ S.previewPdf }}
                                    </button>
                                    <Link
                                        :href="route('login')"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 text-sm font-bold text-white hover:bg-blue-800"
                                    >
                                        <Icon icon="lucide:log-in" class="h-5 w-5 shrink-0" aria-hidden="true" />
                                        {{ S.guestLoginDigital }}
                                    </Link>
                                </div>
                                <Link
                                    v-else
                                    :href="route('login')"
                                    class="inline-flex min-h-[48px] min-w-[48px] items-center justify-center gap-2 rounded-xl bg-blue-900 px-6 text-sm font-bold text-white hover:bg-blue-800"
                                >
                                    <Icon icon="lucide:bookmark" class="h-5 w-5 shrink-0" aria-hidden="true" />
                                    {{ isDigitalBook ? S.guestLoginDigital : S.guestLoginPhysical }}
                                </Link>
                            </template>
                            <template v-else>
                                <div v-if="isDigitalBook" class="space-y-3">
                                    <p
                                        v-if="hasReaderDigitalFile && primaryDigitalAsset && assetCanDownloadPdf(primaryDigitalAsset)"
                                        class="text-xs font-semibold text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{ assetPdfDownloadHint(primaryDigitalAsset) }}
                                    </p>
                                    <div
                                        v-if="hasReaderDigitalFile && primaryDigitalAsset && assetCanDownloadPdf(primaryDigitalAsset)"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
                                            :disabled="isDownloadingPdf(primaryDigitalAsset)"
                                            @click="downloadDigitalPdf(primaryDigitalAsset)"
                                        >
                                            <Icon
                                                :icon="isDownloadingPdf(primaryDigitalAsset) ? 'lucide:loader-2' : 'lucide:download'"
                                                :class="isDownloadingPdf(primaryDigitalAsset) ? 'h-5 w-5 animate-spin' : 'h-5 w-5'"
                                            />
                                            {{ isDownloadingPdf(primaryDigitalAsset) ? 'Đang tải…' : 'Tải PDF' }}
                                        </button>
                                    </div>
                                    <div
                                        v-else-if="hasReaderDigitalFile && primaryDigitalAsset"
                                        class="space-y-3"
                                    >
                                        <button
                                            v-if="assetPreviewActionVisible(primaryDigitalAsset)"
                                            type="button"
                                            class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 text-sm font-bold text-blue-900 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-100 dark:hover:bg-blue-900/50"
                                            @click="openDigitalPreview(primaryDigitalAsset)"
                                        >
                                            <Icon icon="lucide:eye" class="h-5 w-5" />
                                            {{ S.previewPdf }}
                                        </button>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-900 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                                @click="addToDigitalPurchaseCart(primaryDigitalAsset, book)"
                                            >
                                                <Icon icon="lucide:shopping-cart" class="h-5 w-5" />
                                                Thêm vào giỏ sách
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-600 disabled:opacity-60"
                                                :disabled="buyingDigitalAssetId === Number(primaryDigitalAsset?.id)"
                                                @click="buyDigitalAssetNow(primaryDigitalAsset, book)"
                                            >
                                                <Icon
                                                    :icon="buyingDigitalAssetId === Number(primaryDigitalAsset?.id) ? 'lucide:loader-2' : 'lucide:qr-code'"
                                                    :class="buyingDigitalAssetId === Number(primaryDigitalAsset?.id) ? 'h-5 w-5 animate-spin' : 'h-5 w-5'"
                                                />
                                                {{ buyingDigitalAssetId === Number(primaryDigitalAsset?.id) ? 'Đang chuyển…' : 'Mua' }}
                                            </button>
                                        </div>
                                    </div>
                                    <p v-else class="text-xs font-semibold text-rose-600 dark:text-rose-300">
                                        Đồ án, luận văn này chưa có file đính kèm.
                                    </p>
                                </div>
                                <template v-else>
                                <div class="mb-3 flex flex-wrap items-center gap-3">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Số lượng:</label>
                                    <div class="inline-flex h-11 items-center overflow-hidden rounded-xl border border-slate-300/80 bg-white shadow-sm dark:border-slate-600 dark:bg-slate-900">
                                        <button
                                            type="button"
                                            class="inline-flex h-full w-11 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                            :disabled="Number(borrowQty || 1) <= 1"
                                            @click="adjustBorrowQty(-1)"
                                        >
                                            -
                                        </button>
                                        <input
                                            :value="borrowQty"
                                            type="text"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            class="h-full w-14 border-x border-slate-200 bg-transparent text-center text-base font-bold text-slate-900 [appearance:textfield] focus:outline-none dark:border-slate-700 dark:text-slate-100"
                                            @input="onBorrowQtyInput($event)"
                                        />
                                        <button
                                            type="button"
                                            class="inline-flex h-full w-11 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                            :disabled="Number(borrowQty || 1) >= Math.max(1, Number(availability.available || 1))"
                                            @click="adjustBorrowQty(1)"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Tối đa {{ Math.max(0, Number(availability.available || 0)) }}</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border border-emerald-600 bg-white px-4 text-sm font-bold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-400 dark:bg-slate-900 dark:text-emerald-300 dark:hover:bg-slate-800"
                                    :disabled="Number(availability.available || 0) <= 0"
                                    @click="addToBorrowCart"
                                >
                                    <Icon icon="lucide:shopping-cart" class="h-5 w-5" />
                                    {{ Number(availability.available || 0) > 0 ? 'Thêm vào giỏ sách' : 'Hết sách' }}
                                </button>
                                <button
                                    type="button"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-600 disabled:opacity-60"
                                    :disabled="creatingBorrowRequest || Number(availability.available || 0) <= 0"
                                    @click="openBorrowPreview"
                                >
                                    <Icon :icon="creatingBorrowRequest ? 'lucide:loader-2' : 'lucide:shopping-cart'" :class="creatingBorrowRequest ? 'h-5 w-5 animate-spin' : 'h-5 w-5'" />
                                    {{ Number(availability.available || 0) > 0 ? 'Tạo yêu cầu mượn' : 'Hết sách' }}
                                </button>
                                </div>
                                </template>
                            </template>
                        </div>
                        <p v-if="!isAuthed" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{
                                isDigitalBook
                                    ? guestDigitalPreviewReady
                                        ? S.guestDigitalPreviewHint
                                        : S.guestDigitalNoPreviewHint
                                    : S.guestPhysicalHint
                            }}
                        </p>
                        <div v-if="isAuthed" class="mt-2">
                            <Link
                                :href="
                                    isDigitalBook
                                        ? `${route('reader.services.book-cart')}?tab=purchase`
                                        : route('reader.services.book-cart')
                                "
                                class="inline-flex text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-300"
                            >
                                {{ isDigitalBook ? S.digitalPurchaseCartLink : S.borrowCartLink }}
                            </Link>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 space-y-6">
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex border-b border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            class="min-h-[44px] flex-1 px-4 text-sm font-bold transition"
                            :class="detailPanel === 'description' ? 'border-b-2 border-blue-800 text-blue-900 dark:border-blue-400 dark:text-blue-100' : 'text-slate-600 dark:text-slate-400'"
                            @click="detailPanel = 'description'"
                        >{{ S.tabDescription }}</button>
                        <button
                            type="button"
                            class="min-h-[44px] flex-1 px-4 text-sm font-bold transition"
                            :class="detailPanel === 'details' ? 'border-b-2 border-blue-800 text-blue-900 dark:border-blue-400 dark:text-blue-100' : 'text-slate-600 dark:text-slate-400'"
                            @click="detailPanel = 'details'"
                        >{{ S.tabDetails }}</button>
                    </div>
                    <div v-show="detailPanel === 'description'" class="p-4 sm:p-6">
                        <RichHtmlContent
                            content-class=""
                            :html="isDigitalBook ? digitalDescription : book.summary"
                            :empty-text="isDigitalBook ? 'Chưa có mô tả cho đồ án, luận văn này.' : 'Chưa có mô tả.'"
                        />
                    </div>
                    <div v-show="detailPanel === 'details'" class="p-4 sm:p-6">
                        <table class="w-full text-sm">
                            <tbody>
                                <tr v-if="displayBookCode" class="border-b border-slate-100 dark:border-slate-800">
                                    <th class="w-36 py-2.5 pr-4 text-left align-top font-semibold text-slate-500 dark:text-slate-400">{{ S.bookCodeLabel }}</th>
                                    <td class="py-2.5 align-top text-slate-900 dark:text-slate-100">{{ displayBookCode }}</td>
                                </tr>
                                <tr v-for="row in specRows" :key="row.label" class="border-b border-slate-100 last:border-0 dark:border-slate-800">
                                    <th class="w-36 py-2.5 pr-4 text-left align-top font-semibold text-slate-500 dark:text-slate-400">{{ row.label }}</th>
                                    <td class="py-2.5 text-slate-900 dark:text-slate-100">{{ row.value }}</td>
                                </tr>
                                <tr v-if="isDigitalBook" class="border-b border-slate-100 dark:border-slate-800">
                                    <th class="w-36 py-2.5 pr-4 text-left font-semibold text-slate-500 dark:text-slate-400">File đính kèm</th>
                                    <td class="py-2.5 text-slate-900 dark:text-slate-100">{{ hasReaderDigitalFile ? digitalAttachmentName : '—' }}</td>
                                </tr>
                                <tr v-if="isDigitalBook" class="border-b border-slate-100 last:border-0 dark:border-slate-800">
                                    <th class="w-36 py-2.5 pr-4 text-left font-semibold text-slate-500 dark:text-slate-400">Lượt tải</th>
                                    <td class="py-2.5 text-slate-900 dark:text-slate-100">{{ formatReaderStatCount(localDigitalStats?.downloads) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="!isDigitalBook && hasReaderDigitalFile && digitalAssetsListForSection.length"
                    class="overflow-hidden rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6"
                >
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ S.digitalAssets }}
                    </h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="(asset, idx) in digitalAssetsListForSection"
                            :key="asset.id ?? idx"
                            class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/40 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                                    {{ asset.original_name || 'Đồ án, luận văn' }}
                                </p>
                                <p v-if="assetCanDownloadPdf(asset)" class="mt-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ assetPdfDownloadHint(asset) }}
                                </p>
                                <p v-else class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    <span v-if="asset?.paywall?.is_enabled">
                                        Tải PDF: {{ Number(asset?.paywall?.pdf_download_price_vnd ?? 0).toLocaleString('vi-VN') }}₫.
                                    </span>
                                    <span v-else>Thanh toán để tải PDF toàn bộ.</span>
                                </p>
                            </div>
                            <div v-if="assetCanDownloadPdf(asset)" class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 text-xs font-bold text-white hover:bg-blue-800 disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
                                    :disabled="isDownloadingPdf(asset)"
                                    @click="downloadDigitalPdf(asset)"
                                >
                                    <Icon
                                        :icon="isDownloadingPdf(asset) ? 'lucide:loader-2' : 'lucide:download'"
                                        :class="isDownloadingPdf(asset) ? 'h-4 w-4 animate-spin' : 'h-4 w-4'"
                                    />
                                    {{ isDownloadingPdf(asset) ? 'Đang tải…' : 'Tải PDF' }}
                                </button>
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-xs font-bold text-slate-900 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                    @click="addToDigitalPurchaseCart(asset, book)"
                                >
                                    Thêm vào giỏ sách
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 text-xs font-bold text-white hover:bg-emerald-600 disabled:opacity-60"
                                    :disabled="buyingDigitalAssetId === Number(asset?.id)"
                                    @click="buyDigitalAssetNow(asset, book)"
                                >
                                    <Icon
                                        v-if="buyingDigitalAssetId === Number(asset?.id)"
                                        icon="lucide:loader-2"
                                        class="h-4 w-4 animate-spin"
                                    />
                                    {{ buyingDigitalAssetId === Number(asset?.id) ? 'Đang chuyển…' : 'Mua' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <ReaderRelatedBooksSection :books="related_books" :source-book-id="book.id" />

            <div v-if="showBorrowPreview" class="fixed inset-0 z-50 flex items-end justify-center bg-black/55 p-3 backdrop-blur-[2px] sm:items-center">
                <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Xác nhận tạo yêu cầu mượn</h3>
                        <button type="button" class="rounded-md px-2 py-1 text-sm text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white" @click="closeBorrowPreview">Đóng</button>
                    </div>
                    <p class="text-sm text-slate-700 dark:text-slate-200">Sách: <span class="font-semibold">{{ book.title }}</span></p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Số lượng sách khả dụng: {{ availability.available }}</p>
                    <p
                        v-if="borrowSubmitError"
                        class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100"
                        role="alert"
                    >
                        {{ borrowSubmitError }}
                    </p>
                    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-600 dark:bg-slate-800/80">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Hình thức mượn</p>
                        <p
                            v-if="borrow_permissions && !borrow_permissions.allow_home && borrow_permissions.allow_onsite"
                            class="mt-2 text-xs text-slate-600 dark:text-slate-400"
                        >
                            Theo quy định UTC, loại thẻ của bạn chỉ đăng ký đọc tại chỗ (không mượn về nhà).
                        </p>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label
                                class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                :class="!canBorrowHome ? 'opacity-50' : ''"
                            >
                                <input v-model="loanType" type="radio" value="home" :disabled="!canBorrowHome" />
                                Mượn về nhà
                            </label>
                            <label
                                class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                :class="!canBorrowOnsite ? 'opacity-50' : ''"
                            >
                                <input v-model="loanType" type="radio" value="onsite" :disabled="!canBorrowOnsite" />
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
                                :max="maxDueIso"
                                class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-100 dark:[color-scheme:dark] dark:focus:border-emerald-400 dark:focus:ring-emerald-900/50"
                            />
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Số lượng mượn</label>
                        <div class="inline-flex h-11 items-center overflow-hidden rounded-xl border border-slate-300/80 bg-white shadow-sm dark:border-slate-600 dark:bg-slate-900">
                            <button
                                type="button"
                                class="inline-flex h-full w-11 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                :disabled="Number(borrowQty || 1) <= 1"
                                @click="adjustBorrowQty(-1)"
                            >
                                -
                            </button>
                            <input
                                :value="borrowQty"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                class="h-full w-14 border-x border-slate-200 bg-transparent text-center text-base font-bold text-slate-900 [appearance:textfield] focus:outline-none dark:border-slate-700 dark:text-slate-100"
                                @input="onBorrowQtyInput($event)"
                            />
                            <button
                                type="button"
                                class="inline-flex h-full w-11 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                :disabled="Number(borrowQty || 1) >= Math.max(1, Number(availability.available || 1))"
                                @click="adjustBorrowQty(1)"
                            >
                                +
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Tối đa: {{ Math.max(1, Number(availability.available || 1)) }} · Hình thức: {{ loanType === 'home' ? 'Mượn về nhà' : 'Đọc tại chỗ' }}
                        </p>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-700" @click="closeBorrowPreview">Hủy</button>
                        <button
                            type="button"
                            class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-60 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                            :disabled="creatingBorrowRequest"
                            @click="createBorrowRequestSingle"
                        >
                            {{ creatingBorrowRequest ? 'Đang gửi...' : 'Gửi yêu cầu' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </ReaderLayout>
</template>
