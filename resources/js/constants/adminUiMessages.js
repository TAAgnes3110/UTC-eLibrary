export const toastShort = {
    ok: 'Thành công',
    fail: 'Không thành công',
};

export const bookFormClientError = {
    titleRequired: 'Vui lòng nhập tên sách.',
    warehouseEmpty: 'Vui lòng chọn hoặc nhập kho sách.',
    warehouseNoMatch: 'Không tìm thấy kho sách khớp. Chọn mã hoặc tên kho từ gợi ý.',
    classificationEmpty: 'Vui lòng nhập phân loại sách.',
    classificationNoMatch: 'Chọn phân loại từ danh sách gợi ý.',
    classificationDetailEmpty: 'Vui lòng nhập phân loại chi tiết.',
    classificationDetailNoMatch: 'Chọn phân loại chi tiết từ danh sách gợi ý.',
    quantityInvalid: 'Số lượng phải là số nguyên ≥ 0.',
};

/** Lỗi validation client — form tài khoản (khớp tone UserRequest.messages). */
export const userFormClientError = {
    nameRequired: 'Tên không được để trống',
    emailRequired: 'Email không được để trống',
    codeRequired: 'Mã không được để trống',
    phoneInvalid: 'Số điện thoại không đúng định dạng (bắt đầu bằng 0, 10–11 số).',
    passwordRequired: 'Mật khẩu không được để trống',
    passwordConfirmRequired: 'Vui lòng nhập lại mật khẩu xác nhận.',
    passwordConfirmMismatch: 'Xác nhận mật khẩu không khớp',
    passwordMin: 'Mật khẩu tối thiểu 6 ký tự',
};

export const apiGenericFallback = 'Có lỗi xảy ra.';
