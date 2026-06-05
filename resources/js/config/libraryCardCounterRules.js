import { LibraryCard } from '@/config/libraryCardConstants';
import { workflowLabel, workflowHint } from '@/config/libraryCardUi';

/** Lệ phí cấp thẻ tại quầy (VNĐ) — sinh viên / bạn đọc ngoài */
export const COUNTER_FEE_STUDENT_VND = 40_000;

export const COUNTER_FEE_EXTERNAL_VND = 40_000;

/** Giảng viên, cán bộ miễn lệ phí (quy định thư viện UTC) */
export const COUNTER_FEE_TEACHER_VND = 0;

export function isTeacherHolder(holderType) {
    return holderType === LibraryCard.HOLDER_TEACHER;
}

export function isExternalHolder(holderType) {
    return holderType === LibraryCard.HOLDER_EXTERNAL;
}

export function isInternalHolder(holderType) {
    return holderType === LibraryCard.HOLDER_STUDENT || isTeacherHolder(holderType);
}

export function counterFeeAmountForHolder(holderType) {
    if (isTeacherHolder(holderType)) {
        return COUNTER_FEE_TEACHER_VND;
    }
    if (isExternalHolder(holderType)) {
        return COUNTER_FEE_EXTERNAL_VND;
    }

    return COUNTER_FEE_STUDENT_VND;
}

/**
 * Mặc định « đã xử lý tại quầy » theo loại thẻ và luồng.
 * @param {'with_account'|'without_account'} flowMode
 */
export function defaultPaidAtCounter(holderType, flowMode) {
    if (isExternalHolder(holderType) || isTeacherHolder(holderType)) {
        return true;
    }
    if (flowMode === 'with_account') {
        return true;
    }

    return true;
}

/** Có cho phép bỏ tick « đã thu phí » (sinh viên tại quầy, chưa thu) */
export function canTogglePaidAtCounter(holderType) {
    return holderType === LibraryCard.HOLDER_STUDENT;
}

/**
 * Dự báo quy trình sau khi tạo hồ sơ tại quầy.
 * @param {'with_account'|'without_account'} flowMode
 */
export function resolveCounterWorkflowPreview(holderType, paidAtCounter) {
    if (isExternalHolder(holderType)) {
        return {
            key: 'active',
            label: workflowLabel('active'),
            hint: 'Thẻ bạn đọc ngoài được kích hoạt ngay (Đang hiệu lực) sau khi thu phí tại quầy.',
            tone: 'emerald',
        };
    }

    if (isTeacherHolder(holderType)) {
        return {
            key: 'pending_pickup',
            label: workflowLabel('pending_pickup'),
            hint: 'Giảng viên miễn lệ phí — hồ sơ chuyển thẳng sang chờ lấy thẻ; xác nhận giao thẻ khi bạn đọc đến quầy.',
            tone: 'blue',
        };
    }

    if (paidAtCounter) {
        return {
            key: 'pending_pickup',
            label: workflowLabel('pending_pickup'),
            hint: 'Đã thu phí tại quầy — chờ bạn đọc nhận thẻ vật lý; thủ thư xác nhận giao thẻ trên hệ thống.',
            tone: 'blue',
        };
    }

    return {
        key: 'pending_review',
        label: workflowLabel('pending_review'),
        hint: 'Chưa thu phí — hồ sơ vào hàng chờ duyệt; sau duyệt chuyển sang chờ thanh toán rồi chờ lấy thẻ.',
        tone: 'amber',
    };
}

export function paidAtCounterCheckboxLabel(holderType) {
    if (isTeacherHolder(holderType)) {
        return 'Đã xử lý thủ tục tại quầy';
    }

    return 'Đã thu phí tại quầy';
}

export function counterFlowIntro(flowMode) {
    if (flowMode === 'with_account') {
        return 'Gắn hồ sơ thẻ với tài khoản eLibrary đã có. Sinh viên/giảng viên thường chuyển sang « Chờ lấy thẻ » sau khi xử lý tại quầy.';
    }

    return 'Tạo hồ sơ cho người chưa có tài khoản. Bạn đọc ngoài được kích hoạt thẻ ngay; sinh viên/giảng viên cần bước nhận thẻ vật lý.';
}

/** Payload thanh toán gửi API — ép theo quy định, tránh gửi sai từ form */
export function buildCounterPaymentPayload(holderType, paidAtCounter, paymentAmount) {
    if (isTeacherHolder(holderType)) {
        return { paid_at_counter: true, payment_amount: COUNTER_FEE_TEACHER_VND };
    }
    if (isExternalHolder(holderType)) {
        return { paid_at_counter: true, payment_amount: COUNTER_FEE_EXTERNAL_VND };
    }

    return {
        paid_at_counter: Boolean(paidAtCounter),
        payment_amount: paidAtCounter ? Number(paymentAmount ?? COUNTER_FEE_STUDENT_VND) : 0,
    };
}
