/**
 * Kích hoạt tải PDF qua GET + cookie session — để trình duyệt (hoặc trình tải hệ thống) xử lý.
 * Dùng iframe ẩn (không dùng thẻ `<a>`) để Inertia không chặn điều hướng tải file.
 *
 * @param {string} url URL route tải (cùng origin, đã đăng nhập)
 * @returns {boolean}
 */
export function startBrowserDownload(url) {
    if (!url || url === '#') {
        return false
    }

    const iframe = document.createElement('iframe')
    iframe.setAttribute('title', 'Tải PDF')
    iframe.setAttribute('aria-hidden', 'true')
    iframe.tabIndex = -1
    iframe.style.cssText = 'position:fixed;width:0;height:0;border:0;opacity:0;pointer-events:none'
    iframe.src = url
    document.body.appendChild(iframe)

    window.setTimeout(() => {
        try {
            iframe.remove()
        } catch {
            /* ignore */
        }
    }, 180_000)

    return true
}
