/** Tài sản số chính của đầu mục (admin). */
export function primaryDigitalAsset(book) {
    const arr = book?.digital_assets;
    if (!Array.isArray(arr) || arr.length === 0) {
        return null;
    }
    return arr.find((a) => a?.is_primary) || arr[0];
}

/** URL tải PDF qua session web (admin) — không dùng /api/v1 (tránh thiếu JWT). */
export function adminDigitalDownloadUrl(book) {
    const asset = primaryDigitalAsset(book);
    const bookId = Number(book?.id);
    const assetId = Number(asset?.id);
    if (!bookId || !assetId) {
        return null;
    }

    const fromApi = book.primary_digital_asset_download_url || asset?.admin_download_url || '';
    if (typeof fromApi === 'string' && fromApi.includes('/digital-assets/')) {
        return fromApi;
    }

    return `/admin/books/${bookId}/digital-assets/${assetId}/download`;
}

export function digitalAttachmentFileName(book) {
    const asset = primaryDigitalAsset(book);
    const name = String(asset?.original_name || '').trim();
    return name || 'tai-lieu.pdf';
}

export function hasAdminDigitalAttachment(book) {
    if (book?.has_digital_attachment === true) {
        return true;
    }
    const asset = primaryDigitalAsset(book);
    return Boolean(asset?.id);
}

/** Người đăng: độc giả gửi duyệt hoặc thủ thư tạo trên admin. */
export function digitalPosterLabel(book) {
    const sub = book?.digital_submission?.submitter;
    if (sub) {
        const name = String(sub.name || '').trim();
        const email = String(sub.email || '').trim();
        if (name && email) {
            return `${name} · ${email}`;
        }
        return name || email || '';
    }
    const creator = book?.created_by_user;
    if (creator) {
        const name = String(creator.name || '').trim();
        const email = String(creator.email || '').trim();
        if (name && email) {
            return `${name} · ${email}`;
        }
        return name || email || '';
    }
    return '';
}

/**
 * Tải PDF — GET thường + cookie session (giống IDM/trình duyệt).
 * Không dùng fetch/XHR: stream PDF + X-Requested-With dễ lỗi dù URL vẫn hợp lệ.
 */
export function downloadAdminDigitalAsset(book) {
    const url = adminDigitalDownloadUrl(book);
    if (!url) {
        throw new Error('Không có file đính kèm.');
    }

    const link = document.createElement('a');
    link.href = url;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
}
