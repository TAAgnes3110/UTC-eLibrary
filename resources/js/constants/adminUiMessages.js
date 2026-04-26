export const toastShort = {
    ok: 'Thành công',
    fail: 'Không thành công',
};

export const bookFormClientError = {
    titleRequired: 'Vui lòng nhập tên sách.',
    warehouseEmpty: 'Vui lòng chọn kho sách từ danh sách.',
    warehouseNoMatch: 'Kho sách không tồn tại trong hệ thống. Vui lòng chọn từ danh sách.',
    classificationEmpty: 'Vui lòng chọn phân loại sách từ danh sách.',
    classificationNoMatch: 'Phân loại sách không tồn tại trong hệ thống. Vui lòng chọn từ danh sách.',
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
    facultyRequiredStudent: 'Sinh viên cần chọn khoa.',
    facultyRequiredTeacher: 'Giảng viên cần chọn khoa.',
    periodRequiredStudent: 'Sinh viên cần chọn niên khóa.',
    facultyNoMatch: 'Không tìm thấy khoa khớp — gõ mã hoặc tên hoặc chọn từ gợi ý.',
    periodNoMatch: 'Không tìm thấy niên khóa khớp — gõ mã hoặc tên hoặc chọn từ gợi ý.',
};

export const apiGenericFallback = 'Có lỗi xảy ra.';
