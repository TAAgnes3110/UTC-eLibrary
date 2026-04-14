/** Khớp `App\Enums\RoleType::staffRoles()` — tài khoản vào khu vực quản trị / thủ thư. */
export const LIBRARY_STAFF_USER_TYPES = ['SUPER_ADMIN', 'ADMIN', 'LIBRARIAN']

/**
 * @param {string|null|undefined} userType
 * @returns {boolean}
 */
export function isLibraryStaffUserType(userType) {
    if (userType == null || userType === '') {
        return false
    }
    return LIBRARY_STAFF_USER_TYPES.includes(String(userType))
}
