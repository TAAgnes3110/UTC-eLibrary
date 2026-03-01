/**
 * Utils: cn (className merge), format helpers. Hằng số/enums dùng từ config/enums.js hoặc utils/constants.
 */
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function formatDate(value, format = 'DD/MM/YYYY') {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '—';
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return format.replace('DD', day).replace('MM', month).replace('YYYY', year);
}
