/** Ngày chọn tối đa: 31/12 năm trước (khớp rule backend). */
export function maxDateOfBirthForInput() {
    const year = new Date().getFullYear() - 1;
    return `${year}-12-31`;
}

/** Giới hạn dưới hợp lý cho input date (mặc định 100 năm). */
export function minDateOfBirthForInput(yearsBack = 100) {
    const year = new Date().getFullYear() - yearsBack;
    return `${year}-01-01`;
}
