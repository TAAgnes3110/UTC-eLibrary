/** Gợi ý form tài khoản: chỉ tên khoa / năm; khớp vẫn nhận mã, tên, định dạng cũ. */

export function facultyDisplayLabel(f) {
    if (!f) return '';
    return String(f.name ?? '').trim();
}

/** Niên khóa: như DB — vd. K63 (2022 - 2026); fallback K = start_year − 1959. */
export function periodDisplayLabel(p) {
    if (!p) return '';
    const n = String(p.name ?? '').trim();
    if (n) return n;
    if (p.start_year != null && p.end_year != null) {
        const start = Number(p.start_year);
        const end = Number(p.end_year);
        const k = start - 1959;
        return `K${k} (${start} - ${end})`;
    }
    if (p.start_year != null) return String(p.start_year);
    return '';
}

function facultyLegacyLabel(f) {
    const code = String(f.code ?? '').trim();
    const name = String(f.name ?? '').trim();
    if (code && name) return `${code} – ${name}`;
    return name || code || '';
}

function periodLegacyLabel(p) {
    return facultyLegacyLabel(p);
}

function facultyAliases(item) {
    const name = String(item.name ?? '').trim();
    const code = String(item.code ?? '').trim();
    const legacy = facultyLegacyLabel(item);
    return [...new Set([name, code, legacy].filter(Boolean))];
}

function periodAliases(item) {
    const name = String(item.name ?? '').trim();
    const code = String(item.code ?? '').trim();
    const sy = item.start_year != null ? String(item.start_year) : '';
    const ey = item.end_year != null ? String(item.end_year) : '';
    const spaced = sy && ey ? `${sy} - ${ey}` : '';
    const compact = sy && ey ? `${sy}-${ey}` : '';
    const kLabel =
        item.start_year != null && !Number.isNaN(Number(item.start_year))
            ? `K${Number(item.start_year) - 1959}`
            : '';
    const legacy = periodLegacyLabel(item);
    return [...new Set([name, code, kLabel, sy, ey, spaced, compact, legacy].filter(Boolean))];
}

/**
 * @param {Array<{ id: number|string }>} list
 * @param {string} text
 * @param {(item: object) => string[]} getAliases
 */
function matchByAliases(list, text, getAliases) {
    const raw = (text || '').trim();
    if (!raw || !Array.isArray(list)) return null;
    const t = raw.toLowerCase();

    if (/^\d+$/.test(t)) {
        const num = parseInt(t, 10);
        const byId = list.find((item) => Number(item.id) === num);
        if (byId) return byId.id;
        const byYear = list.find((item) => {
            const sy = item.start_year != null ? String(item.start_year) : '';
            const ey = item.end_year != null ? String(item.end_year) : '';
            return raw === sy || raw === ey;
        });
        if (byYear) return byYear.id;
    }

    for (const item of list) {
        const aliases = getAliases(item).map((s) => String(s).trim().toLowerCase()).filter(Boolean);
        for (const a of aliases) {
            if (t === a) return item.id;
        }
    }

    for (const item of list) {
        const aliases = getAliases(item).map((s) => String(s).trim().toLowerCase()).filter(Boolean);
        for (const a of aliases) {
            if (a.length >= 2 && (t.includes(a) || a.includes(t))) return item.id;
        }
    }

    return null;
}

export function matchFacultyId(list, text) {
    return matchByAliases(list, text, facultyAliases);
}

export function matchPeriodId(list, text) {
    return matchByAliases(list, text, periodAliases);
}
