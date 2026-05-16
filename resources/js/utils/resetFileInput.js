/**
 * Xóa giá trị <input type="file"> để lần chọn sau vẫn kích hoạt @change
 * và không giữ tên/file từ lần upload trước.
 * @param {HTMLInputElement | null | undefined} el
 */
export function resetFileInput(el) {
    if (el && typeof el.value === 'string') {
        el.value = '';
    }
}
