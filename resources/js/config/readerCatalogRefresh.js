/** Đánh dấu cần tải lại `books` trên danh mục sau khi xem chi tiết (tránh số lượt xem cũ). */
export const READER_CATALOG_REFRESH_BOOKS_KEY = 'reader_catalog_refresh_books_v1'

export function markReaderCatalogBooksStale() {
    if (typeof sessionStorage === 'undefined') {
        return
    }
    try {
        sessionStorage.setItem(READER_CATALOG_REFRESH_BOOKS_KEY, '1')
    } catch {
        /* ignore */
    }
}

export function consumeReaderCatalogBooksStale() {
    if (typeof sessionStorage === 'undefined') {
        return false
    }
    try {
        const stale = sessionStorage.getItem(READER_CATALOG_REFRESH_BOOKS_KEY) === '1'
        if (stale) {
            sessionStorage.removeItem(READER_CATALOG_REFRESH_BOOKS_KEY)
        }
        return stale
    } catch {
        return false
    }
}
