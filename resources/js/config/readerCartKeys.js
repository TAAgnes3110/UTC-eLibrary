/** localStorage + CustomEvent để đồng bộ giỏ sách (mượn / thanh toán tài liệu số) giữa các trang (cùng tab không có sự kiện `storage`). */
export const READER_BORROW_CART_KEY = 'reader_borrow_cart_v1'
export const READER_DIGITAL_PURCHASE_CART_KEY = 'reader_digital_purchase_cart_v1'
/** sessionStorage: dòng thanh toán «mua ngay» từ trang chi tiết sách (một lần). */
export const READER_DIGITAL_BUY_NOW_SESSION_KEY = 'reader_digital_buy_now_v1'
const READER_BORROW_CART_KEY_PREFIX = `${READER_BORROW_CART_KEY}_u_`
const READER_DIGITAL_BUY_NOW_SESSION_KEY_PREFIX = `${READER_DIGITAL_BUY_NOW_SESSION_KEY}_u_`

export const READER_CART_UPDATED_EVENT = 'reader-cart-updated'

/** Giỏ mượn phải tách theo user để tránh lẫn dữ liệu giữa các tài khoản trên cùng trình duyệt. */
export function buildReaderBorrowCartStorageKey(userId) {
    const id = Number(userId)
    if (!Number.isInteger(id) || id <= 0) return null
    return `${READER_BORROW_CART_KEY_PREFIX}${id}`
}

export function isReaderBorrowCartStorageKey(key) {
    return typeof key === 'string' && key.startsWith(READER_BORROW_CART_KEY_PREFIX)
}

/** Dọn key giỏ mượn cũ (không theo user) để tránh rò rỉ dữ liệu giữa tài khoản. */
export function clearLegacyReaderBorrowCartStorage() {
    if (typeof localStorage === 'undefined') return
    try {
        localStorage.removeItem(READER_BORROW_CART_KEY)
    } catch {
        /* ignore */
    }
}

function buildReaderDigitalBuyNowSessionKey(userId) {
    const id = Number(userId)
    if (!Number.isInteger(id) || id <= 0) return READER_DIGITAL_BUY_NOW_SESSION_KEY
    return `${READER_DIGITAL_BUY_NOW_SESSION_KEY_PREFIX}${id}`
}

/** Chuẩn hóa payload thanh toán trực tiếp từ asset + sách trên BookShow. */
export function buildDigitalBuyNowRow(asset, bookRow) {
    if (!asset?.id) return null
    const paywall = asset?.paywall && typeof asset.paywall === 'object' ? asset.paywall : {}
    const price = Number(paywall?.pdf_download_price_vnd ?? 0)
    const bid = Number(bookRow?.id ?? 0)
    return {
        digital_asset_id: Number(asset.id),
        book_id: Number.isFinite(bid) && bid > 0 ? bid : null,
        book_title: String(bookRow?.title || '').trim() || null,
        file_name: String(asset?.original_name || '').trim() || null,
        cover_image: String(bookRow?.cover_image || '').trim() || null,
        price_vnd: Number.isFinite(price) && price >= 0 ? price : 0,
        paywall_enabled: paywall?.is_enabled === true ? true : paywall?.is_enabled === false ? false : null,
    }
}

export function stashDigitalBuyNowRow(row, userId = null) {
    if (typeof sessionStorage === 'undefined' || !row?.digital_asset_id) return
    try {
        sessionStorage.setItem(buildReaderDigitalBuyNowSessionKey(userId), JSON.stringify(row))
    } catch {
        /* ignore */
    }
}

/** Đọc dòng «mua ngay» — chỉ dùng trên `/dich-vu/thanh-toan`, không khôi phục trên giỏ hàng. */
export function readDigitalBuyNowRow(expectedAssetId = null, userId = null) {
    if (typeof sessionStorage === 'undefined') return null
    try {
        const raw = sessionStorage.getItem(buildReaderDigitalBuyNowSessionKey(userId))
        if (!raw) return null
        const parsed = JSON.parse(raw)
        if (expectedAssetId != null && Number(parsed?.digital_asset_id) !== Number(expectedAssetId)) {
            return null
        }
        return parsed
    } catch {
        return null
    }
}

export function clearDigitalBuyNowSession(userId = null) {
    if (typeof sessionStorage === 'undefined') return
    try {
        sessionStorage.removeItem(buildReaderDigitalBuyNowSessionKey(userId))
    } catch {
        /* ignore */
    }
}

/**
 * @param {'borrow'|'digital'|undefined} source — `undefined`: đồng bộ cả hai giỏ (tương thích gọi cũ).
 */
export function notifyReaderCartUpdated(source) {
    if (typeof window === 'undefined') {
        return
    }
    const detail = source ? { source } : {}
    window.dispatchEvent(new CustomEvent(READER_CART_UPDATED_EVENT, { detail }))
}
