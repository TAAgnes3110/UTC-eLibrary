/** Mặc định UTC: 10% giá bìa khi hư hỏng 100% */
export const DEFAULT_DAMAGE_FINE_PERCENT_DISPLAY = '10';

/** Hệ số 0.1 → hiển thị «10» (%) */
export function damageFineRateToPercentDisplay(rate) {
    const r = Number(rate ?? 0.1);
    if (!Number.isFinite(r) || r <= 0) {
        return '0';
    }

    return String(Math.round(r * 1000) / 10);
}

/** Nhập «10» (%) → hệ số 0.1 */
export function damageFinePercentDisplayToRate(display) {
    const n = parseFloat(String(display ?? '0').replace(',', '.'));
    if (!Number.isFinite(n) || n < 0) {
        return 0;
    }

    return Math.min(1, Math.max(0, n / 100));
}

export function clampDamageFinePercentDisplay(raw) {
    const n = parseFloat(String(raw ?? '0').replace(',', '.'));
    if (!Number.isFinite(n)) {
        return '0';
    }

    return String(Math.min(100, Math.max(0, Math.round(n * 10) / 10)));
}

/** Nhãn ngắn cho bảng quy định */
export function formatDamageFinePolicyShort(rate) {
    const pct = damageFineRateToPercentDisplay(rate);
    if (pct === '0') {
        return null;
    }

    return `${pct}% giá bìa/cuốn`;
}
