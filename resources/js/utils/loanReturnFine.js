const LATE_MODE_PERCENT_DAILY = 'percent_book_price_daily';

function daysBetween(startDate, endDate) {
    if (!startDate || !endDate) {
        return 0;
    }
    const start = new Date(startDate);
    const end = new Date(endDate);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return 0;
    }
    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);
    const ms = end.getTime() - start.getTime();
    return Math.max(0, Math.floor(ms / 86400000));
}

function overdueDays(dueDate, returnDate) {
    if (!dueDate || !returnDate) {
        return 0;
    }
    const due = new Date(dueDate);
    const returned = new Date(returnDate);
    if (Number.isNaN(due.getTime()) || Number.isNaN(returned.getTime())) {
        return 0;
    }
    due.setHours(0, 0, 0, 0);
    returned.setHours(0, 0, 0, 0);
    if (returned <= due) {
        return 0;
    }
    return daysBetween(dueDate, returnDate);
}

function normalizePolicy(finePolicy = {}) {
    return {
        damage_fine_percent: Math.max(0, Number(finePolicy.damage_fine_percent ?? 0.1)),
        loss_fine_multiplier: Math.max(1, Number(finePolicy.loss_fine_multiplier ?? 2)),
        replacement_processing_fee: Math.max(0, Number(finePolicy.replacement_processing_fee ?? 10000)),
        overdue_fine_per_day: Math.max(0, Number(finePolicy.overdue_fine_per_day ?? 0)),
        late_return_fine_mode: finePolicy.late_return_fine_mode ?? 'fixed_per_day',
        late_return_fine_percent_of_book: Math.max(0, Number(finePolicy.late_return_fine_percent_of_book ?? 0)),
    };
}

function resolveDamageSeverityPercent(conditionOnReturn, damagePercent) {
    if (conditionOnReturn === 'mat') {
        return 100;
    }
    if (conditionOnReturn !== 'hong') {
        return null;
    }
    const n = Number(damagePercent);
    if (!Number.isFinite(n) || n < 1 || n > 100) {
        return null;
    }
    return Math.round(n);
}

export function calculateOverdueFine({ dueDate, returnDate, bookPrice, quantity, finePolicy }) {
    const policy = normalizePolicy(finePolicy);
    const days = overdueDays(dueDate, returnDate);
    const price = Math.max(0, Number(bookPrice ?? 0));
    const qty = Math.max(1, Number(quantity ?? 1));

    if (policy.late_return_fine_mode === LATE_MODE_PERCENT_DAILY) {
        const pct = policy.late_return_fine_percent_of_book / 100;
        return days * (price * pct) * qty;
    }

    return days * policy.overdue_fine_per_day * qty;
}

export function calculateConditionFine({ conditionOnReturn, damagePercent, bookPrice, quantity, finePolicy }) {
    const policy = normalizePolicy(finePolicy);
    const price = Math.max(0, Number(bookPrice ?? 0));
    const qty = Math.max(1, Number(quantity ?? 1));

    if (conditionOnReturn === 'hong') {
        const severity = resolveDamageSeverityPercent(conditionOnReturn, damagePercent);
        if (severity === null || price <= 0) {
            return 0;
        }
        return price * (severity / 100) * qty;
    }
    if (conditionOnReturn === 'mat') {
        if (price <= 0) {
            return 0;
        }
        return (price * policy.loss_fine_multiplier + policy.replacement_processing_fee) * qty;
    }

    return 0;
}

export function calculateReturnLineFine({
    dueDate,
    returnDate,
    conditionOnReturn,
    damagePercent,
    bookPrice,
    quantity,
    finePolicy,
}) {
    const overdue = calculateOverdueFine({ dueDate, returnDate, bookPrice, quantity, finePolicy });
    const condition = calculateConditionFine({
        conditionOnReturn,
        damagePercent,
        bookPrice,
        quantity,
        finePolicy,
    });

    return Math.max(0, Math.round((overdue + condition) * 100) / 100);
}

export function damagePercentRequired(conditionOnReturn) {
    return conditionOnReturn === 'hong';
}

/** Chuẩn hóa % hư khi nhập — không cho vượt 100 hoặc dưới 1. */
export function sanitizeDamagePercentInput(value) {
    if (value === '' || value === null || value === undefined) {
        return { value: '', exceededMax: false, belowMin: false };
    }
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return { value: '', exceededMax: false, belowMin: false };
    }
    if (n > 100) {
        return { value: 100, exceededMax: true, belowMin: false };
    }
    if (n < 1) {
        return { value: n, exceededMax: false, belowMin: true };
    }
    return { value: n, exceededMax: false, belowMin: false };
}

/** Làm tròn % hư khi blur — đảm bảo trong khoảng 1–100. */
export function finalizeDamagePercent(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }
    const n = Math.round(Number(value));
    if (!Number.isFinite(n)) {
        return null;
    }
    return Math.min(100, Math.max(1, n));
}

export function formatDamageFineRule() {
    return 'Phạt hư hỏng = giá bìa × (% mức hư ÷ 100) / cuốn; nhập % mức hư thực tế khi trả sách.';
}
