/** Cấu hình toolbar Quill — đồng bộ với trang Tin tức & thông báo (Word-like). */
export const QUILL_WORD_TOOLBAR = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ align: [] }],
    ['blockquote', 'link', 'image'],
    ['clean'],
];

export function normalizeEditorHtml(html) {
    let normalized = String(html || '');
    normalized = normalized.replace(/<p><br><\/p>/gi, '');
    normalized = normalized.replace(/<p>\s*<\/p>/gi, '');

    return normalized.trim();
}

export function stripHtmlToPlainText(html) {
    if (typeof document === 'undefined') {
        return String(html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    }
    const wrapper = document.createElement('div');
    wrapper.innerHTML = String(html || '');
    return (wrapper.textContent || '').replace(/\s+/g, ' ').trim();
}

export function hasRichTextContent(html) {
    const plain = stripHtmlToPlainText(html);
    if (plain.length > 0) return true;
    if (typeof document === 'undefined') return false;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = String(html || '');
    return wrapper.querySelector('img') !== null;
}

export function escapeHtmlAttr(value) {
    return String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('"', '&quot;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;');
}

export function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('Không thể đọc tệp ảnh.'));
        reader.readAsDataURL(file);
    });
}

/** Toolbar xám kiểu Word — khớp News/Index.vue */
export function forceQuillWordToolbarStyle(quillInstance) {
    if (!quillInstance) return;

    const toolbar = quillInstance.getModule('toolbar')?.container;
    if (!toolbar) return;

    toolbar.style.background = '#e5e7eb';
    toolbar.style.borderBottom = '1px solid #cbd5e1';
    toolbar.style.padding = '10px 12px';
    toolbar.style.color = '#1f2937';

    toolbar.querySelectorAll('button, .ql-picker-label').forEach((el) => {
        el.style.color = '#1f2937';
        el.style.opacity = '1';
    });

    toolbar.querySelectorAll('svg').forEach((svg) => {
        svg.style.filter = 'none';
    });

    toolbar.querySelectorAll('.ql-stroke').forEach((el) => {
        el.style.stroke = '#1f2937';
    });
    toolbar.querySelectorAll('.ql-fill').forEach((el) => {
        el.style.fill = '#1f2937';
    });
}
