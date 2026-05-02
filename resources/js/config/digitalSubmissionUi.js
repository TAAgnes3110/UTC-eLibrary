/**
 * Giao diện thống nhất: duyệt tài liệu số (Reader + Admin).
 * Badge trạng thái + nút neutral / danger / success cùng họ (viền + nền trong).
 */

/** Pill trạng thái: pending / approved / rejected */
export function submissionStatusBadgeClass(status) {
    if (status === 'approved') {
        return 'bg-emerald-100 text-emerald-900 shadow-sm dark:bg-emerald-900/40 dark:text-emerald-200 dark:shadow-none dark:ring-1 dark:ring-inset dark:ring-emerald-600/45'
    }
    if (status === 'rejected') {
        return 'bg-rose-100 text-rose-900 shadow-sm dark:bg-rose-900/45 dark:text-rose-100 dark:shadow-none dark:ring-1 dark:ring-inset dark:ring-rose-600/50'
    }
    return 'bg-amber-100 text-amber-950 shadow-sm dark:bg-amber-900/50 dark:text-amber-50 dark:shadow-none dark:ring-1 dark:ring-inset dark:ring-amber-500/50'
}

/** Link mở file PDF đính kèm */
export const LINK_SUBMISSION_FILE =
    'font-medium text-sky-700 underline-offset-2 hover:underline dark:text-sky-400/95 break-all line-clamp-1'

const BTN_NEUTRAL_CORE =
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-300 bg-white font-medium text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400/40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-700 dark:focus-visible:ring-slate-400/50 disabled:pointer-events-none disabled:opacity-50'

const BTN_DANGER_CORE =
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-rose-500/75 bg-rose-600/10 font-semibold text-rose-800 shadow-sm transition hover:bg-rose-600/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-rose-500/55 dark:bg-rose-950/35 dark:text-rose-100 dark:hover:bg-rose-950/55 dark:focus-visible:ring-offset-slate-900 disabled:pointer-events-none disabled:opacity-50'

const BTN_SUCCESS_CORE =
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-emerald-500/80 bg-emerald-600/12 font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-600/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/45 disabled:pointer-events-none disabled:opacity-45 dark:border-emerald-500/55 dark:bg-emerald-950/40 dark:text-emerald-100 dark:hover:bg-emerald-950/55'

/** Chi tiết / quan sát — hàng reader (touch 44px) */
export const BTN_SUBMISSION_NEUTRAL_ROW = `${BTN_NEUTRAL_CORE} min-h-[44px] px-3.5 py-2 text-sm`

/** Chi tiết — ô thao tác gọn (admin bảng) */
export const BTN_SUBMISSION_NEUTRAL_COMPACT = `${BTN_NEUTRAL_CORE} min-h-[32px] shrink-0 rounded-md px-2 py-1 text-xs`

/** Thu hồi / Xóa — reader */
export const BTN_SUBMISSION_DANGER_ROW = `${BTN_DANGER_CORE} min-h-[44px] px-3.5 py-2 text-sm`

/** Từ chối — admin hàng + modal */
export const BTN_SUBMISSION_DANGER_COMPACT = `${BTN_DANGER_CORE} min-h-[32px] shrink-0 rounded-md px-2 py-1 text-xs`

export const BTN_SUBMISSION_DANGER_MODAL = `${BTN_DANGER_CORE} min-h-[44px] px-4 py-2 text-sm`

/** Đồng ý duyệt — admin */
export const BTN_SUBMISSION_SUCCESS_COMPACT = `${BTN_SUCCESS_CORE} min-h-[32px] shrink-0 rounded-md px-2 py-1 text-xs`

export const BTN_SUBMISSION_SUCCESS_MODAL = `${BTN_SUCCESS_CORE} min-h-[44px] px-4 py-2 text-sm`

/** Thanh chọn nhiều — cùng họ màu, cỡ chữ sm */
export const BTN_SUBMISSION_SUCCESS_BAR = `${BTN_SUCCESS_CORE} min-h-[44px] gap-1.5 px-4 py-2.5 text-sm`

export const BTN_SUBMISSION_DANGER_BAR = `${BTN_DANGER_CORE} min-h-[44px] gap-1.5 px-4 py-2.5 text-sm`

/** Hàng bảng admin (Chi tiết / Đồng ý / Từ chối, cao h-9) */
const BTN_ROW_ADMIN = 'h-9 min-h-[36px] gap-1.5 px-3 py-1.5 text-xs font-semibold'

export const BTN_SUBMISSION_NEUTRAL_INLINE = `${BTN_NEUTRAL_CORE} ${BTN_ROW_ADMIN}`

export const BTN_SUBMISSION_SUCCESS_INLINE = `${BTN_SUCCESS_CORE} ${BTN_ROW_ADMIN}`

export const BTN_SUBMISSION_DANGER_INLINE = `${BTN_DANGER_CORE} ${BTN_ROW_ADMIN}`
