/**
 * Đọc cấu trúc ApiResponse + Laravel Resource paginator (`data.data` + `data.meta`).
 *
 * @param {unknown} payload — Thân JSON API (vd. axios `response.data`)
 * @param {number} [fallbackPerPage=20]
 * @returns {{ items: unknown[], meta: { current_page: number, last_page: number, per_page: number, total: number, from: number|null, to: number|null } }}
 */
export function extractApiPaginator(payload, fallbackPerPage = 20) {
    const inner = payload?.data;
    const emptyMeta = {
        current_page: 1,
        last_page: 1,
        per_page: fallbackPerPage,
        total: 0,
        from: null,
        to: null,
    };
    if (Array.isArray(inner)) {
        const n = inner.length;
        return {
            items: inner,
            meta: {
                ...emptyMeta,
                last_page: 1,
                per_page: n || fallbackPerPage,
                total: n,
                to: n || null,
            },
        };
    }
    if (inner && typeof inner === 'object' && Array.isArray(inner.data)) {
        const m = inner.meta || {};
        const items = inner.data;
        return {
            items,
            meta: {
                current_page: m.current_page ?? inner.current_page ?? 1,
                last_page: m.last_page ?? inner.last_page ?? 1,
                per_page: m.per_page ?? inner.per_page ?? fallbackPerPage,
                total: m.total ?? inner.total ?? items.length,
                from: m.from ?? inner.from ?? null,
                to: m.to ?? inner.to ?? null,
            },
        };
    }
    return { items: [], meta: emptyMeta };
}

/**
 * Sinh danh sách hiển thị phân trang admin: trang 1, cửa sổ quanh trang hiện tại (±neighbor),
 * và tail trang cuối; chèn ellipsis khi có khoảng trống.
 *
 * @param {number} currentPage - Trang hiện tại (1-based)
 * @param {number} lastPage - Trang cuối (1-based)
 * @param {{ neighbor?: number, tail?: number }} [options]
 * @returns {{ type: 'page', value: number } | { type: 'ellipsis' }}[]
 */
export function buildAdminPaginationItems(currentPage, lastPage, options = {}) {
    const last = Math.max(Number(lastPage) || 1, 1);
    const current = Math.min(Math.max(Number(currentPage) || 1, 1), last);
    if (last <= 1) {
        return [];
    }

    const neighborRaw = options.neighbor != null ? Number(options.neighbor) : 3;
    const tailRaw = options.tail != null ? Number(options.tail) : 3;
    const neighbor = Math.max(0, Number.isFinite(neighborRaw) ? neighborRaw : 3);
    const tail = Math.max(1, Number.isFinite(tailRaw) ? tailRaw : 3);

    const windowStart = Math.max(1, current - neighbor);
    const windowEnd = Math.min(last, current + neighbor);
    const tailStart = Math.max(1, last - tail + 1);

    const pages = new Set([1]);
    for (let p = windowStart; p <= windowEnd; p += 1) {
        pages.add(p);
    }
    for (let p = tailStart; p <= last; p += 1) {
        pages.add(p);
    }

    const sorted = [...pages].sort((a, b) => a - b);
    /** @type {{ type: 'page'; value: number } | { type: 'ellipsis' }}[] */
    const out = [];
    for (let i = 0; i < sorted.length; i += 1) {
        if (i > 0 && sorted[i] - sorted[i - 1] > 1) {
            out.push({ type: 'ellipsis' });
        }
        out.push({ type: 'page', value: sorted[i] });
    }
    return out;
}
