/**
 * Nhãn loại tài liệu — khớp App\Http\Resources\ReaderBookCardResource::resourceTypeLabel.
 *
 * @param {string|null|undefined} value
 * @returns {string}
 */
const LABEL = Object.freeze({
    textbook: 'Sách giáo trình',
    digital: 'Đồ án, luận văn',
});

/** reference + legacy thesis/journal → một nhãn (đồng bộ PHP). */
const REFERENCE_GROUP = Object.freeze(new Set(['reference', 'thesis', 'journal']));
const LABEL_REFERENCE = 'Sách tham khảo';

export function bookResourceTypeLabel(value) {
    const v = value == null ? '' : String(value);
    if (v === '') {
        return '—';
    }
    if (Object.prototype.hasOwnProperty.call(LABEL, v)) {
        return LABEL[v];
    }
    if (REFERENCE_GROUP.has(v)) {
        return LABEL_REFERENCE;
    }
    return v;
}
