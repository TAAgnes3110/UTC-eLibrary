/** localStorage + CustomEvent để đồng bộ giỏ sách (mượn / thanh toán tài liệu số) giữa các trang (cùng tab không có sự kiện `storage`). */
export const READER_BORROW_CART_KEY = 'reader_borrow_cart_v1'
export const READER_DIGITAL_PURCHASE_CART_KEY = 'reader_digital_purchase_cart_v1'
/** sessionStorage: dòng thanh toán «mua ngay» từ trang chi tiết sách (một lần). */
export const READER_DIGITAL_BUY_NOW_SESSION_KEY = 'reader_digital_buy_now_v1'

export const READER_CART_UPDATED_EVENT = 'reader-cart-updated'

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

export function stashDigitalBuyNowRow(row) {
    if (typeof sessionStorage === 'undefined' || !row?.digital_asset_id) return
    try {
        sessionStorage.setItem(READER_DIGITAL_BUY_NOW_SESSION_KEY, JSON.stringify(row))
    } catch {
        /* ignore */
    }
}

/** Đọc dòng «mua ngay» — chỉ dùng trên `/dich-vu/thanh-toan`, không khôi phục trên giỏ hàng. */
export function readDigitalBuyNowRow(expectedAssetId = null) {
    if (typeof sessionStorage === 'undefined') return null
    try {
        const raw = sessionStorage.getItem(READER_DIGITAL_BUY_NOW_SESSION_KEY)
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

export function clearDigitalBuyNowSession() {
    if (typeof sessionStorage === 'undefined') return
    try {
        sessionStorage.removeItem(READER_DIGITAL_BUY_NOW_SESSION_KEY)
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
