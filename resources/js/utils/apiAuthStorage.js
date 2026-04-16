/**
 * Xóa JWT / cache user phía trình duyệt (đăng xuất web) để API /me/* không còn gửi token cũ.
 */
export function clearClientApiCredentials() {
    try {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    } catch {
        //
    }
}
