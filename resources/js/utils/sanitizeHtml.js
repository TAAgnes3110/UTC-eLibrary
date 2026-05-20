import DOMPurify from 'dompurify';

const ALLOWED_TAGS = [
    'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'ul', 'ol', 'li', 'a', 'img', 'blockquote', 'span', 'div', 'table', 'thead',
    'tbody', 'tr', 'td', 'th', 'hr', 'sub', 'sup',
];

const ALLOWED_ATTR = ['href', 'src', 'alt', 'title', 'class', 'target', 'rel', 'width', 'height'];

/**
 * Lọc HTML trước v-html — chống XSS (tin tức Quill).
 */
export function sanitizeHtml(html) {
    const raw = String(html ?? '').trim();
    if (!raw) {
        return '';
    }

    return DOMPurify.sanitize(raw, {
        ALLOWED_TAGS,
        ALLOWED_ATTR,
        ALLOW_DATA_ATTR: false,
        ADD_ATTR: ['target'],
        FORBID_TAGS: ['script', 'iframe', 'object', 'embed', 'form', 'style'],
        FORBID_ATTR: ['onerror', 'onload', 'onclick', 'style'],
    });
}
