<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue'
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue'
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue'
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue'
import { digitalDocumentsApi } from '@/api/digitalDocuments'
import {
    BTN_SUBMISSION_DANGER_ROW,
    BTN_SUBMISSION_NEUTRAL_ROW,
    LINK_SUBMISSION_FILE,
    submissionStatusBadgeClass,
} from '@/config/digitalSubmissionUi'
import { toast } from '@/store/toast'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)

const loading = ref(false)
const submitting = ref(false)
const removingId = ref(null)
const rows = ref([])
const loadError = ref('')

const selectedIds = ref(new Set())
const showDetailModal = ref(false)
const detailItem = ref(null)
const showUploadModal = ref(false)
const showCoverPreviewModal = ref(false)
const coverPreviewItem = ref(null)

const filterKeyword = ref('')
/** Lọc theo trạng thái bản ghi: '' = tất cả */
const statusFilter = ref('')
const sortBy = ref('newest')
const currentPage = ref(1)
const perPage = 10

const form = ref({
    title: '',
    authorNames: '',
    description: '',
    file: null,
    coverFile: null,
})

const createCoverFileName = ref('')
const coverPreviewUrl = ref('')
const createDigitalFileName = ref('')
const pdfFileRef = ref(null)
const coverFileRef = ref(null)

const statusLabel = {
    pending: 'Chờ duyệt',
    approved: 'Đã duyệt',
    rejected: 'Từ chối',
}

/** Chuỗi hiển thị: trim, gộp khoảng trắng; rỗng → null (để template dùng || '—'). */
function cleanDisplayText(value) {
    if (value == null || value === '') return null
    const s = String(value).replace(/\s+/g, ' ').trim()
    return s || null
}

/**
 * Chuẩn hóa một dòng API — tránh khoảng trắng thừa, dữ liệu lồng nhau không đồng nhất.
 * @param {Record<string, unknown>} row
 */
function normalizeSubmissionRow(row) {
    if (!row || typeof row !== 'object') return row
    const ab = row.approved_book
    const approvedBook =
        ab && typeof ab === 'object'
            ? {
                  ...ab,
                  book_code: cleanDisplayText(ab.book_code) ?? '',
                  title: cleanDisplayText(ab.title) ?? '',
                  summary: cleanDisplayText(ab.summary) ?? '',
                  authors_label: cleanDisplayText(ab.authors_label) ?? '',
              }
            : ab

    const sub = row.submitter
    const submitter =
        sub && typeof sub === 'object'
            ? {
                  ...sub,
                  name: cleanDisplayText(sub.name) ?? '',
                  email: cleanDisplayText(sub.email) ?? '',
              }
            : sub

    return {
        ...row,
        title: cleanDisplayText(row.title) ?? '',
        author_names: cleanDisplayText(row.author_names) ?? '',
        description: cleanDisplayText(row.description) ?? '',
        original_name: cleanDisplayText(row.original_name) ?? '',
        review_note: row.review_note != null ? cleanDisplayText(row.review_note) ?? '' : row.review_note,
        approved_book: approvedBook,
        submitter,
    }
}

/** Lấy thông báo lỗi từ ApiResponse / Laravel validation (422). */
function readerApiErrorMessage(error, fallback) {
    const d = error?.response?.data
    if (!d || typeof d !== 'object') return fallback
    if (typeof d.messages === 'string' && d.messages.trim()) return d.messages
    if (typeof d.message === 'string' && d.message.trim()) return d.message
    const errs = d.errors
    if (errs && typeof errs === 'object') {
        for (const key of Object.keys(errs)) {
            const arr = errs[key]
            if (Array.isArray(arr) && arr[0]) return String(arr[0])
            if (typeof arr === 'string') return arr
        }
    }
    return fallback
}

const filteredRows = computed(() => {
    let list = rows.value
    const keyword = String(filterKeyword.value || '').trim().toLowerCase()
    if (keyword) {
        list = list.filter((item) => {
            const st = String(item?.status || '')
            const stLabel = String(statusLabel[item.status] || '').toLowerCase()
            const fields = [
                item?.approved_book?.book_code,
                item?.title,
                item?.author_names,
                item?.approved_book?.authors_label,
                item?.description,
                item?.original_name,
                item?.submitted_at,
                formatDetailDate(item?.submitted_at),
                st,
                stLabel,
            ]
            return fields.some((v) => String(v || '').toLowerCase().includes(keyword))
        })
    }
    if (statusFilter.value !== '') {
        list = list.filter((item) => item.status === statusFilter.value)
    }
    const sorted = [...list].sort((a, b) => (sortBy.value === 'oldest' ? a.id - b.id : b.id - a.id))
    return sorted
})

const lastPage = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / perPage)))
const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filteredRows.value.slice(start, start + perPage)
})

const hasSelection = computed(() => selectedIds.value.size > 0)
const isAllSelected = computed(() => paginatedRows.value.length > 0 && paginatedRows.value.every((row) => selectedIds.value.has(row.id)))

watch(lastPage, (lp) => {
    if (currentPage.value > lp) currentPage.value = lp
})

watch([statusFilter, sortBy], () => {
    currentPage.value = 1
})

watch(paginatedRows, (list) => {
    const valid = new Set(list.map((item) => item.id))
    let changed = false
    const next = new Set()
    for (const id of selectedIds.value) {
        if (valid.has(id)) next.add(id)
        else changed = true
    }
    if (changed) selectedIds.value = next
})

async function loadRows() {
    if (!isAuthed.value) {
        rows.value = []
        return
    }
    loading.value = true
    loadError.value = ''
    try {
        const payload = await digitalDocumentsApi.list()
        const raw = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : []
        rows.value = raw.map((row) => normalizeSubmissionRow(row))
    } catch (error) {
        rows.value = []
        loadError.value = readerApiErrorMessage(error, 'Không tải được danh sách tài liệu số.')
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    loadRows()
})

onUnmounted(() => {
    if (coverPreviewUrl.value) URL.revokeObjectURL(coverPreviewUrl.value)
})

function openUploadModal() {
    if (!isAuthed.value) return
    resetUploadForm()
    showUploadModal.value = true
}

function searchRows() {
    currentPage.value = 1
}

function onDigitalFileChange(event) {
    const picked = event?.target?.files?.[0] ?? null
    if (!picked) return
    if (picked.type !== 'application/pdf') {
        toast.error('Chỉ hỗ trợ file PDF.')
        event.target.value = ''
        form.value.file = null
        createDigitalFileName.value = ''
        return
    }
    form.value.file = picked
    createDigitalFileName.value = picked.name || ''
}

function onCoverFileChange(event) {
    const file = event?.target?.files?.[0] ?? null
    if (!file) return
    const ok = /^image\/(jpeg|png|webp)$/i.test(file.type || '')
    if (!ok) {
        toast.error('Ảnh bìa chỉ nhận JPEG, PNG hoặc WebP.')
        event.target.value = ''
        return
    }
    if (coverPreviewUrl.value) URL.revokeObjectURL(coverPreviewUrl.value)
    createCoverFileName.value = String(file.name || '')
    form.value.coverFile = file
    coverPreviewUrl.value = URL.createObjectURL(file)
}

function removeCreateCover() {
    createCoverFileName.value = ''
    form.value.coverFile = null
    if (coverPreviewUrl.value) URL.revokeObjectURL(coverPreviewUrl.value)
    coverPreviewUrl.value = ''
    if (coverFileRef.value) coverFileRef.value.value = ''
}

function removeCreateDigitalFile() {
    createDigitalFileName.value = ''
    form.value.file = null
    if (pdfFileRef.value) pdfFileRef.value.value = ''
}

function resetUploadForm() {
    if (coverPreviewUrl.value) URL.revokeObjectURL(coverPreviewUrl.value)
    coverPreviewUrl.value = ''
    createCoverFileName.value = ''
    createDigitalFileName.value = ''
    form.value = { title: '', authorNames: '', description: '', file: null, coverFile: null }
    if (pdfFileRef.value) pdfFileRef.value.value = ''
    if (coverFileRef.value) coverFileRef.value.value = ''
}

async function submitUpload() {
    if (!isAuthed.value) {
        toast.warn('Vui lòng đăng nhập để tải tài liệu số.')
        return
    }
    if (submitting.value) return
    if (!form.value.title.trim()) {
        toast.error('Vui lòng nhập tên sách.')
        return
    }
    if (!form.value.authorNames.trim()) {
        toast.error('Vui lòng nhập tác giả tài liệu.')
        return
    }
    if (!form.value.file) {
        toast.error('Vui lòng chọn file PDF.')
        return
    }

    const fd = new FormData()
    fd.append('title', form.value.title.trim())
    fd.append('author_names', form.value.authorNames.trim())
    fd.append('description', form.value.description.trim())
    fd.append('file', form.value.file)
    if (form.value.coverFile) {
        fd.append('cover_image', form.value.coverFile)
    }

    submitting.value = true
    try {
        const res = await digitalDocumentsApi.submit(fd)
        const created = res?.data
        if (created && typeof created === 'object' && created.id != null) {
            rows.value = [normalizeSubmissionRow(created), ...rows.value]
        } else {
            await loadRows()
        }
        toast.success('Đã gửi tài liệu số, vui lòng chờ duyệt.')
        showUploadModal.value = false
        resetUploadForm()
    } catch (error) {
        toast.error(readerApiErrorMessage(error, 'Gửi tài liệu số thất bại.'))
    } finally {
        submitting.value = false
    }
}

function goPage(pageNumber) {
    currentPage.value = pageNumber
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = new Set()
        return
    }
    selectedIds.value = new Set(paginatedRows.value.map((item) => item.id))
}

function toggleSelect(id) {
    const next = new Set(selectedIds.value)
    if (next.has(id)) next.delete(id)
    else next.add(id)
    selectedIds.value = next
}

function deselectAll() {
    selectedIds.value = new Set()
}

/** Chỉ cho phép ẩn/xóa khỏi danh sách khi chờ duyệt hoặc đã bị từ chối; đã duyệt chỉ xem chi tiết. */
function canRemoveSubmission(item) {
    const s = item?.status
    return s === 'pending' || s === 'rejected'
}

function removeConfirmMessage(item) {
    if (item?.status === 'pending') {
        return 'Thu hồi yêu cầu duyệt tài liệu này? Hệ thống vẫn lưu để thủ thư quản lý; chỉ ẩn trên trang của bạn.'
    }
    if (item?.status === 'rejected') {
        return 'Xóa bản ghi đã từ chối khỏi danh sách của bạn? Hệ thống vẫn lưu phía thủ thư; chỉ ẩn trên trang của bạn.'
    }
    return 'Xóa mục này khỏi danh sách của bạn?'
}

function removeSuccessMessage(item) {
    if (item?.status === 'pending') return 'Đã thu hồi yêu cầu duyệt khỏi danh sách của bạn.'
    return 'Đã xóa khỏi danh sách của bạn.'
}

async function removeFromMyList(item) {
    if (!item?.id || !isAuthed.value || !canRemoveSubmission(item)) return
    const ok = window.confirm(removeConfirmMessage(item))
    if (!ok) return
    const id = item.id
    const snapshot = rows.value
    rows.value = snapshot.filter((r) => r.id !== id)
    if (selectedIds.value.has(id)) {
        const next = new Set(selectedIds.value)
        next.delete(id)
        selectedIds.value = next
    }
    removingId.value = id
    try {
        await digitalDocumentsApi.hide(id)
        toast.success(removeSuccessMessage(item))
    } catch (error) {
        rows.value = snapshot
        toast.error(readerApiErrorMessage(error, 'Không xóa khỏi danh sách được.'))
    } finally {
        removingId.value = null
    }
}

function openDetail(item) {
    detailItem.value = item
    showDetailModal.value = true
}

function closeDetail() {
    detailItem.value = null
    showDetailModal.value = false
}

function itemBookCode(item) {
    const code = item?.approved_book?.book_code
    return cleanDisplayText(code) || '—'
}

function itemCover(item) {
    return item?.approved_book?.cover_image || item?.cover_image_url || '/images/default-book-cover.png'
}

/** Popup xem ảnh bìa — cùng pattern `BooksTable` / `LibraryCardsTable`. */
function openCoverPreview(item) {
    if (!item) return
    coverPreviewItem.value = item
    showCoverPreviewModal.value = true
}

function closeCoverPreview() {
    showCoverPreviewModal.value = false
    coverPreviewItem.value = null
}

function itemAuthors(item) {
    const fromBook = cleanDisplayText(item?.approved_book?.authors_label)
    const fromRow = cleanDisplayText(item?.author_names)
    return fromBook || fromRow || '—'
}

/** Giống modal chi tiết tài liệu số trên admin (`Index.vue`). */
function formatDetailDate(iso) {
    if (!iso) return '—'
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return '—'
    return new Intl.DateTimeFormat('vi-VN').format(d)
}

function detailSubmitterLabel(item) {
    const sub = item?.submitter
    if (!sub) return '—'
    const name = String(sub.name || '').trim()
    const email = String(sub.email || '').trim()
    if (name && email) return `${name} · ${email}`
    return name || email || '—'
}

function detailTimeLabel(item) {
    return formatDetailDate(item?.submitted_at)
}

</script>

<template>
    <ReaderLayout>
        <Head title="Quản lý tài liệu số" />

        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading title="Nộp đồ án, luận văn theo danh mục">
                <template #description>
                    Gửi PDF kèm ảnh bìa (tuỳ chọn); sau duyệt, đầu mục xuất hiện trong danh mục tài liệu số của thư viện.
                </template>
            </AdminPageHeading>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.size"
                add-label="Thêm tài liệu số"
                :show-export="false"
                :show-import="false"
                :show-update-file="false"
                :show-delete-selected="false"
                @add="openUploadModal"
                @deselect-all="deselectAll"
            >
                <template #extra>
                    <Link
                        v-if="!isAuthed"
                        :href="route('login')"
                        class="btn-admin-green"
                    >
                        Đăng nhập để tải lên
                    </Link>
                </template>
            </AdminImportExportBar>

            <AdminFilterSearch
                v-model="filterKeyword"
                search-placeholder="Mã sách, tên sách, tác giả, ngày gửi, trạng thái, file đính kèm..."
                :show-filter-button="false"
                @search="searchRows"
            >
                <template #filters>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <select v-model="statusFilter" class="admin-filter-select !h-9 !py-0 leading-9 w-full sm:w-[118px] max-w-full pr-9">
                                <option value="">Tất cả</option>
                                <option value="pending">Chờ duyệt</option>
                                <option value="approved">Đã duyệt</option>
                                <option value="rejected">Từ chối</option>
                            </select>
                            <Icon
                                icon="lucide:chevron-down"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                            />
                        </div>
                        <div class="relative">
                            <select v-model="sortBy" class="admin-filter-select !h-9 !py-0 leading-9 w-full sm:w-[112px] max-w-full pr-9">
                                <option value="newest">Mới nhất</option>
                                <option value="oldest">Cũ nhất</option>
                            </select>
                            <Icon
                                icon="lucide:chevron-down"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                            />
                        </div>
                    </div>
                </template>
            </AdminFilterSearch>

            <p
                v-if="loadError"
                class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300"
            >
                {{ loadError }}
            </p>

            <div v-if="!isAuthed" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                Vui lòng đăng nhập để xem và quản lý tài liệu số đã tải lên.
            </div>

            <div
                v-if="isAuthed && hasSelection"
                class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200/90 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40"
            >
                <span class="text-sm text-slate-600 dark:text-slate-300">
                    Đã chọn <strong>{{ selectedIds.size }}</strong> dòng
                </span>
                <button
                    type="button"
                    class="min-h-[44px] px-2 text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                    @click="deselectAll"
                >
                    Bỏ chọn
                </button>
            </div>

            <div v-if="isAuthed" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1220px] border-collapse text-left text-sm text-slate-800 dark:text-slate-200">
                        <thead class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/80">
                            <tr>
                                <th class="w-11 px-3 py-3.5 align-middle">
                                    <span class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            :checked="isAllSelected"
                                            :indeterminate="hasSelection && !isAllSelected"
                                            class="admin-table-checkbox"
                                            :disabled="loading || !paginatedRows.length"
                                            @change="toggleSelectAll"
                                        />
                                    </span>
                                </th>
                                <th class="px-3 py-3.5 align-middle whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Mã sách
                                </th>
                                <th class="px-3 py-3.5 align-middle whitespace-nowrap text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    <span class="sr-only">Ảnh bìa — bấm để xem trong cửa sổ</span>
                                </th>
                                <th class="px-3 py-3.5 align-middle whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Tên sách
                                </th>
                                <th class="px-3 py-3.5 align-middle whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Tác giả
                                </th>
                                <th class="px-3 py-3.5 align-middle whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Mô tả
                                </th>
                                <th class="w-[1%] whitespace-nowrap px-3 py-3.5 align-middle text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Ngày gửi
                                </th>
                                <th class="min-w-[140px] px-3 py-3.5 align-middle whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    File đính kèm
                                </th>
                                <th class="px-3 py-3.5 align-middle text-center whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Trạng thái
                                </th>
                                <th class="min-w-[220px] px-3 py-3.5 align-middle text-left whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                            <tr v-if="loading">
                                <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Đang tải...</td>
                            </tr>
                            <template v-else>
                            <tr v-for="item in paginatedRows" :key="item.id" class="admin-table-row hover:bg-gray-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-3 py-3.5 align-middle">
                                    <span class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.has(item.id)"
                                            class="admin-table-checkbox"
                                            @change="toggleSelect(item.id)"
                                        />
                                    </span>
                                </td>
                                <td class="px-3 py-3.5 align-middle">
                                    <p class="text-sm font-medium tabular-nums tracking-wide font-mono whitespace-nowrap truncate text-slate-600 dark:text-slate-300">
                                        {{ itemBookCode(item) }}
                                    </p>
                                </td>
                                <td class="px-3 py-3.5 align-middle">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg p-1 text-left ring-1 ring-transparent transition hover:bg-slate-100 hover:ring-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:hover:bg-slate-800 dark:hover:ring-slate-600 dark:focus:ring-blue-400"
                                        title="Xem ảnh bìa"
                                        @click="openCoverPreview(item)"
                                    >
                                        <span class="sr-only">Xem ảnh bìa</span>
                                        <span
                                            class="block h-10 w-8 overflow-hidden rounded-md bg-slate-100 ring-1 ring-slate-200/80 dark:bg-slate-800 dark:ring-slate-700/80"
                                        >
                                            <img
                                                :src="itemCover(item)"
                                                :alt="item.title || 'Ảnh bìa'"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                                decoding="async"
                                            />
                                        </span>
                                    </button>
                                </td>
                                <td class="px-3 py-3.5 align-middle">
                                    <p class="text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 line-clamp-2 break-words">
                                        {{ item.title || '—' }}
                                    </p>
                                </td>
                                <td class="px-3 py-3.5 align-middle">
                                    <p class="text-sm font-normal leading-snug text-slate-600 dark:text-slate-300 line-clamp-2 break-words text-left">
                                        {{ itemAuthors(item) }}
                                    </p>
                                </td>
                                <td class="px-3 py-3.5 align-middle">
                                    <p class="text-sm font-normal leading-snug text-slate-600 dark:text-slate-300 line-clamp-2 break-words text-left">
                                        {{ item.description || '—' }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 align-middle tabular-nums text-slate-600 dark:text-slate-300">
                                    {{ formatDetailDate(item.submitted_at) }}
                                </td>
                                <td class="px-3 py-3.5 align-middle">
                                    <a
                                        v-if="item.file_url"
                                        :href="item.file_url"
                                        target="_blank"
                                        :class="['text-sm', LINK_SUBMISSION_FILE]"
                                    >
                                        {{ item.original_name || 'Mở file đính kèm' }}
                                    </a>
                                    <span v-else class="text-sm text-slate-500 dark:text-slate-500">—</span>
                                </td>
                                <td class="px-3 py-3.5 align-middle text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex min-w-[5.5rem] items-center justify-center rounded-md px-2.5 py-1.5 text-xs font-semibold leading-tight"
                                        :class="submissionStatusBadgeClass(item.status)"
                                    >
                                        {{ statusLabel[item.status] || item.status }}
                                    </span>
                                </td>
                                <td class="px-2 py-3.5 align-middle text-left">
                                    <div
                                        class="flex flex-wrap items-center justify-start gap-2"
                                        role="group"
                                        :aria-label="'Thao tác với tài liệu: ' + (item.title || '')"
                                    >
                                        <button
                                            type="button"
                                            :class="BTN_SUBMISSION_NEUTRAL_ROW"
                                            title="Xem chi tiết — mở cửa sổ thông tin đầy đủ"
                                            @click="openDetail(item)"
                                        >
                                            <Icon icon="lucide:eye" class="h-4 w-4 shrink-0 opacity-90" aria-hidden="true" />
                                            Chi tiết
                                        </button>
                                        <button
                                            v-if="canRemoveSubmission(item)"
                                            type="button"
                                            :class="BTN_SUBMISSION_DANGER_ROW"
                                            :title="
                                                item.status === 'pending'
                                                    ? 'Thu hồi yêu cầu duyệt (ẩn khỏi danh sách của bạn)'
                                                    : 'Xóa bản ghi đã từ chối khỏi danh sách của bạn'
                                            "
                                            :disabled="removingId === item.id"
                                            @click="removeFromMyList(item)"
                                        >
                                            <Icon icon="lucide:trash-2" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                            {{ item.status === 'pending' ? 'Thu hồi' : 'Xóa' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!paginatedRows.length">
                                <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    Chưa có tài liệu số trong danh sách.
                                </td>
                            </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <AdminPaginationBar
                v-if="isAuthed"
                always-show
                :current-page="currentPage"
                :last-page="lastPage"
                :disabled="loading"
                @go-page="goPage"
            />
        </div>

        <Teleport to="body">
            <div v-if="showUploadModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50" @click="showUploadModal = false" />
                <div
                    class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800"
                >
                    <div
                        class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50"
                    >
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Thêm tài liệu số mới</h3>
                        <button
                            type="button"
                            class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                            @click="showUploadModal = false"
                        >
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="px-6 pb-6 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Tên sách <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="form.title"
                                class="h-10 rounded-lg dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                                placeholder="Nhập tên sách"
                            />
                        </div>
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Tác giả <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="form.authorNames"
                                class="h-10 rounded-lg dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                                placeholder="Tên tác giả, phân tách bởi dấu phẩy"
                            />
                        </div>
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Avatar sách (tùy chọn)</label>
                            <div class="rounded-lg border border-dashed border-slate-300 dark:border-slate-700 p-3 bg-slate-50/60 dark:bg-slate-800/40">
                                <div class="flex flex-wrap items-center gap-2">
                                    <input
                                        ref="coverFileRef"
                                        id="reader-digital-cover-upload"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        class="sr-only"
                                        @change="onCoverFileChange"
                                    />
                                    <label
                                        for="reader-digital-cover-upload"
                                        class="inline-flex h-9 min-h-[36px] items-center rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                                    >
                                        Chọn ảnh bìa
                                    </label>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[360px]">
                                        {{ createCoverFileName || 'Chưa chọn ảnh' }}
                                    </span>
                                </div>
                                <div
                                    v-if="coverPreviewUrl"
                                    class="mt-3 flex items-center gap-3 rounded-md border border-slate-200 dark:border-slate-700 bg-white/70 dark:bg-slate-900/40 p-2"
                                >
                                    <img
                                        :src="coverPreviewUrl"
                                        alt="Ảnh bìa xem trước"
                                        class="h-16 w-12 rounded object-cover ring-1 ring-slate-200 dark:ring-slate-700"
                                    />
                                    <Button type="button" variant="outline" size="sm" class="min-h-[36px]" @click="removeCreateCover">Bỏ ảnh</Button>
                                </div>
                            </div>
                        </div>
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                File đính kèm (PDF) <span class="text-rose-500">*</span>
                            </label>
                            <div class="rounded-lg border border-dashed border-slate-300 dark:border-slate-700 p-3 bg-slate-50/60 dark:bg-slate-800/40">
                                <div class="flex flex-wrap items-center gap-2">
                                    <input
                                        ref="pdfFileRef"
                                        id="reader-digital-upload-file"
                                        type="file"
                                        accept=".pdf,application/pdf"
                                        class="sr-only"
                                        @change="onDigitalFileChange"
                                    />
                                    <label
                                        for="reader-digital-upload-file"
                                        class="inline-flex h-9 min-h-[36px] items-center rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                                    >
                                        Chọn file PDF
                                    </label>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[360px]">
                                        {{ createDigitalFileName || 'Chưa chọn file PDF' }}
                                    </span>
                                    <Button
                                        v-if="createDigitalFileName"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="min-h-[36px]"
                                        @click="removeCreateDigitalFile"
                                    >
                                        Bỏ file
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mô tả</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white px-3 py-2 resize-y"
                                placeholder="Nhập mô tả ngắn về nội dung sách"
                            />
                        </div>
                    </div>
                    <div
                        class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30"
                    >
                        <Button variant="outline" class="min-h-[44px] px-4" :disabled="submitting" @click="showUploadModal = false">
                            Hủy bỏ
                        </Button>
                        <Button class="min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white px-4" :disabled="submitting" @click="submitUpload">
                            {{ submitting ? 'Đang gửi…' : 'Gửi tài liệu số' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Xem ảnh bìa (popup giống BooksTable / LibraryCardsTable) -->
        <div v-if="showCoverPreviewModal && coverPreviewItem" class="fixed inset-0 z-[121] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeCoverPreview" />
            <div
                class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                role="dialog"
                aria-modal="true"
                aria-labelledby="digital-cover-preview-title"
            >
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h4 id="digital-cover-preview-title" class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                        Ảnh bìa
                    </h4>
                    <button
                        type="button"
                        class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        @click="closeCoverPreview"
                    >
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                </div>
                <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                    <img
                        :src="itemCover(coverPreviewItem)"
                        :alt="coverPreviewItem.title || 'Ảnh bìa'"
                        class="h-[360px] w-full object-contain bg-slate-50 dark:bg-slate-800"
                    />
                </div>
                <div class="mt-3 flex justify-end">
                    <a
                        :href="itemCover(coverPreviewItem)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-[36px] items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        Mở ảnh tab mới
                    </a>
                </div>
            </div>
        </div>

        <div v-if="showDetailModal && detailItem" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeDetail" />
            <div
                class="relative w-full max-w-4xl max-h-[88vh] overflow-y-auto rounded-xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            >
                <div class="mb-4 flex items-start justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết tài liệu số</h3>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeDetail">
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                </div>

                <!-- Bố cục giống admin: cột ảnh bìa cố định + lưới thông tin -->
                <div class="grid grid-cols-1 md:grid-cols-[140px,1fr] gap-6">
                    <div
                        class="mx-auto aspect-[3/4] w-full max-w-[140px] overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200/80 dark:bg-slate-800 dark:ring-slate-700/80 md:mx-0"
                    >
                        <img
                            :src="itemCover(detailItem)"
                            :alt="detailItem.title || 'Ảnh bìa'"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            decoding="async"
                        />
                    </div>
                    <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <p class="text-slate-500 dark:text-slate-400">Tên tài liệu</p>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ detailItem.title || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Mã sách</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ itemBookCode(detailItem) }}</p>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Tác giả</p>
                                <p class="font-medium text-slate-800 dark:text-slate-200">{{ itemAuthors(detailItem) }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Trạng thái</p>
                                <span
                                    class="mt-0.5 inline-flex min-w-[6rem] items-center justify-center rounded-md px-2.5 py-1 text-xs font-semibold leading-none tracking-wide"
                                    :class="submissionStatusBadgeClass(detailItem.status)"
                                >
                                    {{ statusLabel[detailItem.status] || detailItem.status }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Người đăng</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailSubmitterLabel(detailItem) }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">
                                {{ detailItem.submitted_at ? 'Thời gian gửi' : 'Thời gian' }}
                            </p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailTimeLabel(detailItem) }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-slate-500 dark:text-slate-400">Mô tả</p>
                            <p class="font-medium whitespace-pre-line text-slate-800 dark:text-slate-200">
                                {{ detailItem.description || '—' }}
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-slate-500 dark:text-slate-400">File đính kèm (PDF)</p>
                            <a
                                v-if="detailItem.file_url"
                                :href="detailItem.file_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                :class="['text-sm font-semibold', LINK_SUBMISSION_FILE]"
                            >
                                {{ detailItem.original_name || 'Mở file PDF' }}
                            </a>
                            <p v-else class="font-medium text-slate-500 dark:text-slate-400">—</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ReaderLayout>
</template>
