<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue'
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue'
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue'
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue'
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue'
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue'
import AdminAvailabilityBadge from '@/Components/Admin/Shared/AdminAvailabilityBadge.vue'
import { readerSavedBooksPageStrings as P } from '@/config/readerStrings'
import { toast } from '@/store/toast'

const page = usePage()
const SEARCH_IN_OPTIONS = [
    { key: 'title', label: 'Tên sách' },
    { key: 'code', label: 'Mã sách' },
    { key: 'author', label: 'Tác giả' },
    { key: 'publisher', label: 'Nhà xuất bản' },
    { key: 'classification', label: 'Phân loại' },
]

const q = reactive({
    keyword: '',
    search_in: ['title', 'code', 'author', 'publisher', 'classification'],
    status: '',
    sort: 'newest',
})
const showFilterPanel = ref(false)
const selectedBookIds = ref([])
const bulkRemoving = ref(false)
const rowRemovingIds = ref([])
const searchInMap = computed({
    get() {
        const active = Array.isArray(q.search_in) ? q.search_in : []
        return SEARCH_IN_OPTIONS.reduce((acc, opt) => {
            acc[opt.key] = active.includes(opt.key)
            return acc
        }, {})
    },
    set(next) {
        const picked = SEARCH_IN_OPTIONS
            .map((opt) => opt.key)
            .filter((key) => next?.[key] === true)
        q.search_in = picked.length > 0 ? picked : ['title']
    },
})

const saved = computed(() => page.props.saved ?? { data: [], last_page: 1, current_page: 1 })
const rows = computed(() => (Array.isArray(saved.value?.data) ? saved.value.data : []))
const filteredRows = computed(() => {
    let list = [...rows.value]

    if (q.status === 'in_stock') {
        list = list.filter((row) => row?.book?.is_available === true)
    } else if (q.status === 'out_of_stock') {
        list = list.filter((row) => row?.book?.is_available !== true)
    }

    const kw = q.keyword.trim().toLowerCase()
    if (kw) {
        list = list.filter((row) => {
            const b = row.book || {}
            const fields = {
                title: b.title,
                code: b.book_code,
                author: b.authors_label,
                publisher: b.publishers_label,
                classification: b.classification_name,
            }
            const scopes = Array.isArray(q.search_in) && q.search_in.length > 0 ? q.search_in : ['title']
            return scopes
                .map((scope) => fields[scope])
                .filter(Boolean)
                .some((v) => String(v).toLowerCase().includes(kw))
        })
    }

    list.sort((a, b) => {
        if (q.sort === 'oldest') {
            return new Date(a.saved_at || 0).getTime() - new Date(b.saved_at || 0).getTime()
        }
        if (q.sort === 'title_asc') {
            return String(a?.book?.title || '').localeCompare(String(b?.book?.title || ''), 'vi')
        }
        if (q.sort === 'title_desc') {
            return String(b?.book?.title || '').localeCompare(String(a?.book?.title || ''), 'vi')
        }
        return new Date(b.saved_at || 0).getTime() - new Date(a.saved_at || 0).getTime()
    })

    return list
})
const hasSelection = computed(() => selectedBookIds.value.length > 0)
const isAllSelected = computed(() => filteredRows.value.length > 0 && selectedBookIds.value.length === filteredRows.value.length)

watch(
    filteredRows,
    (nextRows) => {
        const valid = new Set(
            nextRows
                .map((row) => Number(row?.book?.id))
                .filter((id) => Number.isInteger(id) && id > 0)
        )
        selectedBookIds.value = selectedBookIds.value.filter((id) => valid.has(id))
    },
    { immediate: true }
)

function buildQuery(pageOverride) {
    const payload = {}
    if (pageOverride != null) payload.page = pageOverride
    return payload
}

function goPage(pageNum) {
    if (pageNum < 1) return
    router.get(route('reader.saved-books'), buildQuery(pageNum), {
        preserveState: true,
        preserveScroll: true,
    })
}

function toggleAll() {
    if (isAllSelected.value) {
        selectedBookIds.value = []
        return
    }
    selectedBookIds.value = filteredRows.value
        .map((row) => Number(row?.book?.id))
        .filter((id) => Number.isInteger(id) && id > 0)
}

function toggleOne(bookId) {
    const id = Number(bookId)
    if (!Number.isInteger(id) || id <= 0) return
    if (selectedBookIds.value.includes(id)) {
        selectedBookIds.value = selectedBookIds.value.filter((x) => x !== id)
        return
    }
    selectedBookIds.value.push(id)
}

function isRowRemoving(bookId) {
    return rowRemovingIds.value.includes(Number(bookId))
}

async function deleteSavedBook(bookId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    await axios.delete(`${route('reader.catalog.show', { book: bookId })}/luu`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            Accept: 'application/json',
        },
    })
}

async function removeRow(bookId) {
    const id = Number(bookId)
    if (!Number.isInteger(id) || id <= 0 || bulkRemoving.value || isRowRemoving(id)) return

    rowRemovingIds.value.push(id)
    try {
        await deleteSavedBook(id)
        selectedBookIds.value = selectedBookIds.value.filter((x) => x !== id)
        toast.success('Đã bỏ sách khỏi danh sách lưu.', { title: 'Sách đã lưu' })
        router.reload({ only: ['saved', 'filters'], preserveScroll: true })
    } catch {
        toast.error('Không thể bỏ sách đã lưu. Vui lòng thử lại.', { title: 'Sách đã lưu' })
    } finally {
        rowRemovingIds.value = rowRemovingIds.value.filter((x) => x !== id)
    }
}

async function removeSelected() {
    if (!hasSelection.value || bulkRemoving.value) return

    const targets = [...selectedBookIds.value]
    bulkRemoving.value = true

    const results = await Promise.allSettled(targets.map((id) => deleteSavedBook(id)))
    bulkRemoving.value = false
    selectedBookIds.value = []

    const successCount = results.filter((r) => r.status === 'fulfilled').length
    const failedCount = results.length - successCount

    router.reload({ only: ['saved', 'filters'], preserveScroll: true })
    if (successCount > 0) {
        toast.success(`Đã bỏ ${successCount} sách khỏi danh sách lưu.`, { title: 'Sách đã lưu' })
    }
    if (failedCount > 0) {
        toast.warn(`${failedCount} sách chưa bỏ lưu được. Vui lòng thử lại.`, { title: 'Sách đã lưu' })
    }
}

function applyFilters() {
    // Search/filter is client-side for current page; keep API query only for per_page/pagination.
}
</script>

<template>
    <ReaderLayout>
        <Head :title="P.headTitle" />

        <div class="mx-auto w-full max-w-6xl space-y-4 animate-in fade-in-50 duration-500">
            <div>
                <Link
                    :href="route('reader.catalog')"
                    class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-400"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ P.backCatalog }}
                </Link>
            </div>

            <AdminPageHeading :title="P.heroTitle">
                <template #description>
                    {{ P.lead }}
                </template>
            </AdminPageHeading>

            <div
                v-if="rows.length === 0"
                class="rounded-xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900"
            >
                <Icon icon="lucide:bookmark" class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
                <p class="mt-4 text-lg font-bold text-slate-800 dark:text-slate-100">{{ P.emptyTitle }}</p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ P.emptyHint }}</p>
                <Link
                    :href="route('reader.catalog')"
                    class="mt-6 inline-flex min-h-[44px] items-center justify-center rounded-xl bg-blue-900 px-6 text-sm font-bold text-white hover:bg-blue-800"
                >
                    {{ P.backCatalog }}
                </Link>
            </div>

            <template v-else>
                <AdminImportExportBar
                    :has-selection="hasSelection"
                    :selected-count="selectedBookIds.length"
                    :show-add="false"
                    :show-import="false"
                    :show-export="false"
                    :show-update-file="false"
                    @delete-selected="removeSelected"
                    @deselect-all="selectedBookIds = []"
                />

                <AdminFilterSearch
                    v-model="q.keyword"
                    search-placeholder="Tìm theo phạm vi đã chọn..."
                    :show-filter-button="false"
                    @search="applyFilters"
                >
                    <template #filters>
                        <div class="flex flex-nowrap items-center gap-2 overflow-x-auto">
                            <AdminFilterPanel
                                :options="SEARCH_IN_OPTIONS"
                                v-model:model-value="searchInMap"
                                :show="showFilterPanel"
                                @update:show="showFilterPanel = $event"
                            />
                            <button type="button" class="admin-filter-btn !h-9 !w-[124px] !px-3 !py-0 text-sm" @click="toggleAll">
                                <Icon :icon="isAllSelected ? 'lucide:check-square' : 'lucide:square'" class="h-4 w-4" />
                                {{ isAllSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả' }}
                            </button>
                            <select v-model="q.status" class="admin-filter-select admin-filter-select-centered !h-9 !w-[124px] !py-0 leading-9">
                                <option value="">Trạng thái</option>
                                <option value="in_stock">Còn</option>
                                <option value="out_of_stock">Hết</option>
                            </select>
                            <select v-model="q.sort" class="admin-filter-select admin-filter-select-centered !h-9 !w-[156px] !py-0 leading-9">
                                <option value="newest">Mới lưu nhất</option>
                                <option value="oldest">Lưu lâu nhất</option>
                                <option value="title_asc">Tên A-Z</option>
                                <option value="title_desc">Tên Z-A</option>
                            </select>
                        </div>
                    </template>
                </AdminFilterSearch>

                <div class="rounded-xl border border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] text-left border-collapse">
                            <thead class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-200 dark:border-slate-700">
                                <tr>
                                    <th class="py-2 pl-3 pr-1 w-10 align-middle">
                                        <span class="admin-table-checkbox-wrap--compact">
                                            <input
                                                type="checkbox"
                                                :checked="isAllSelected"
                                                :indeterminate="hasSelection && !isAllSelected"
                                                class="admin-table-checkbox"
                                                @change="toggleAll"
                                            />
                                        </span>
                                    </th>
                                    <th class="py-2 px-2 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Sách</th>
                                    <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Tác giả</th>
                                    <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">Nhà xuất bản</th>
                                    <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300 text-right w-[110px]">Trạng thái</th>
                                    <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300">{{ P.savedAt }}</th>
                                    <th class="p-4 align-middle whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-300 w-[130px]">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="row in filteredRows"
                                    :key="row.id"
                                    :class="[selectedBookIds.includes(Number(row.book.id)) ? 'bg-blue-50 dark:bg-blue-900/15' : 'admin-table-row']"
                                >
                                    <td class="py-2 pl-3 pr-1 align-middle">
                                        <span class="admin-table-checkbox-wrap--compact">
                                            <input
                                                type="checkbox"
                                                :checked="selectedBookIds.includes(Number(row.book.id))"
                                                class="admin-table-checkbox"
                                                @change="toggleOne(row.book.id)"
                                            />
                                        </span>
                                    </td>
                                    <td class="py-2 px-2 align-middle min-w-[280px] max-w-[360px]">
                                        <div class="flex items-start gap-3">
                                            <Link
                                                :href="route('reader.catalog.show', row.book.id)"
                                                class="w-12 h-16 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0"
                                            >
                                                <img v-if="row.book.cover_image" :src="row.book.cover_image" :alt="row.book.title" class="h-full w-full object-cover" />
                                                <div v-else class="h-full w-full flex items-center justify-center text-slate-400">
                                                    <Icon icon="lucide:book-open" class="w-5 h-5 opacity-60" />
                                                </div>
                                            </Link>
                                            <div class="min-w-0">
                                                <Link
                                                    :href="route('reader.catalog.show', row.book.id)"
                                                    class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-300"
                                                >
                                                    {{ row.book.title }}
                                                </Link>
                                                <p v-if="row.book.book_code" class="mt-1 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                                    {{ row.book.book_code }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle text-[12px] text-slate-700 dark:text-slate-300">
                                        {{ row.book.authors_label || '—' }}
                                    </td>
                                    <td class="p-4 align-middle text-[12px] text-slate-700 dark:text-slate-300">
                                        {{ row.book.publishers_label || '—' }}
                                    </td>
                                    <td class="p-4 text-right w-[110px]">
                                        <AdminAvailabilityBadge :available="row.book.is_available === true" />
                                    </td>
                                    <td class="p-4 align-middle text-[12px] text-slate-600 dark:text-slate-400 tabular-nums">
                                        {{ row.saved_at ? new Date(row.saved_at).toLocaleString('vi-VN') : '—' }}
                                    </td>
                                    <td class="p-4 align-middle whitespace-nowrap">
                                        <div class="flex flex-nowrap justify-start gap-0.5">
                                            <AdminTableActionIcon
                                                icon="lucide:eye"
                                                title="Chi tiết"
                                                :href="route('reader.catalog.show', row.book.id)"
                                            />
                                            <AdminTableActionIcon
                                                :icon="isRowRemoving(row.book.id) ? 'lucide:loader-2' : 'lucide:trash-2'"
                                                :spin="isRowRemoving(row.book.id)"
                                                tone="rose"
                                                :disabled="bulkRemoving || isRowRemoving(row.book.id)"
                                                title="Bỏ khỏi danh sách"
                                                @click="removeRow(row.book.id)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="filteredRows.length === 0" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">
                        Không tìm thấy đầu mục phù hợp bộ lọc hiện tại.
                    </p>
                </div>

                <AdminPaginationBar
                    :current-page="saved.current_page"
                    :last-page="saved.last_page"
                    :disabled="bulkRemoving"
                    @go-page="goPage"
                />
            </template>
        </div>
    </ReaderLayout>
</template>
