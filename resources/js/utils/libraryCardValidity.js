/** Định dạng khoảng hiệu lực thẻ — khớp issue_date / expiry_date (backend). */

function formatDateOnly(value) {
    if (!value) return '—'
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('vi-VN')
}

function addOneYearIso(isoDate) {
    const d = new Date(isoDate)
    if (Number.isNaN(d.getTime())) return null
    const end = new Date(d)
    end.setFullYear(end.getFullYear() + 1)
    return end.toISOString()
}

/**
 * @param {{ workflow_status?: string, issue_date?: string|null, expiry_date?: string|null, reviewed_at?: string|null, created_at?: string|null }|null|undefined} card
 */
export function libraryCardValidityText(card) {
    if (!card) return '—'

    const ws = card.workflow_status
    const issue = card.issue_date
    const expiry = card.expiry_date

    if (issue && expiry) {
        return `${formatDateOnly(issue)} — ${formatDateOnly(expiry)}`
    }
    if (issue || expiry) {
        return `${formatDateOnly(issue)} — ${formatDateOnly(expiry)}`
    }

    if (ws === 'active') {
        const anchor = card.reviewed_at || card.created_at
        if (anchor) {
            const endIso = addOneYearIso(anchor)
            return `${formatDateOnly(anchor)} — ${formatDateOnly(endIso)}`
        }
    }

    if (ws === 'pending_pickup') return 'Chờ nhận thẻ tại quầy'
    if (ws === 'pending_payment') return 'Chờ thanh toán lệ phí'
    if (ws === 'pending_review') return 'Chờ duyệt hồ sơ'

    return '—'
}

/**
 * Cột « Hiệu lực » admin: thẻ active → khoảng ngày; còn lại → nhãn quy trình.
 */
export function libraryCardWorkflowOrValidityCell(card, workflowLabelFn) {
    if (!card) return '—'
    if (card.workflow_status === 'active') {
        const range = libraryCardValidityText(card)
        if (range !== '—') return range
    }
    return workflowLabelFn(card.workflow_status)
}
