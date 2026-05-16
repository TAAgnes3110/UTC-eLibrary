<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import BooksTable from '@/Components/Admin/Books/BooksTable.vue';
import { ADMIN_ICONS } from '@/config/adminIcons';
const BookFormModal = defineAsyncComponent(() => import('@/Components/Admin/Books/BookFormModal.vue'));
import { useBooksAdminPage, SEARCH_IN_OPTIONS, BOOK_SORT_OPTIONS, PRINT_TYPE_OPTIONS } from '@/composables/admin/useBooksAdminPage';
import { ref, computed, defineAsyncComponent } from 'vue';
import { booksApi } from '@/api/books';
import { useImageFallback } from '@/composables/useImageFallback';
import {
    downloadAdminDigitalAsset,
    hasAdminDigitalAttachment,
    digitalAttachmentFileName,
    digitalPosterLabel,
} from '@/utils/adminDigitalAsset';
import { toast } from '@/store/toast';
import RichHtmlContent from '@/Components/Shared/RichHtmlContent.vue';

const {
    pageKind,
    pageLabel,
    booksPagination,
    goBooksPage,
    searchBooks,
    loading,
    warehouses,
    saveBookLoading,
    classifications,
    cabinetOptions,
    storageSuggestionLoading,
    storageSuggestionMessage,
    createCoverPreviewUrl,
    setCreateCoverFile,
    clearCreateCoverFile,
    editExistingCoverUrl,
    editExistingDigitalFileName,
    clearEditExistingMedia,
    clearEditExistingCover,
    clearEditExistingDigitalFileName,
    setCreateDigitalFile,
    clearCreateDigitalFile,
    filterValues,
    showFilterPanel,
    filteredBooks,
    showModal,
    isEditing,
    form,
    bookFormErrors,
    clearBookFieldError,
    selectedBook,
    showDeleteConfirm,
    deleteLoading,
    selectedIds,
    hasSelection,
    isAllSelected,
    trashedBooks,
    showTrashDrawer,
    showCoverModal,
    coverBulkMode,
    coverUploadLoading,
    showImportModal,
    importLoading,
    toggleSelectAll,
    toggleSelect,
    deselectAll,
    openAddModal,
    openEditModal,
    saveBook,
    openDeleteOne,
    openDeleteMultiple,
    confirmDelete,
    exportExcel,
    openImportModal,
    downloadBooksTemplate,
    importBooksExcel,
    restoreBook,
    restoreManyBooks,
    forceDeleteBook,
    forceDeleteManyBooks,
    openCoverModal,
    closeCoverModal,
    uploadCover,
    markBookCodeTouched,
    markRegistrationTouched,
} = useBooksAdminPage();

const showDetailModal = ref(false);
const detailBook = ref(null);
const detailLoading = ref(false);
const { withFallback } = useImageFallback();

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '—';
    return new Intl.DateTimeFormat('vi-VN').format(d);
}

function digitalDetailSubmitterLabel(book) {
    const label = digitalPosterLabel(book);
    return label || '—';
}

/** Ưu tiên thời điểm gửi bản đăng ký; không có thì thời điểm tạo đầu mục trên hệ thống. */
function digitalDetailTimeLabel(book) {
    const iso = book?.digital_submission?.submitted_at ?? book?.created_at;
    return formatDate(iso);
}

async function openDetailModal(book) {
    detailBook.value = book ?? null;
    showDetailModal.value = true;
    if (!book?.id) return;

    detailLoading.value = true;
    try {
        const payload = await booksApi.get(book.id);
        const full = payload?.data ?? payload;
        if (full && showDetailModal.value) {
            detailBook.value = full;
        }
    } catch (_e) {
        // fallback dữ liệu hiện có từ danh sách
    } finally {
        detailLoading.value = false;
    }
}

/** Chi tiết modal: tách hẳn đồ án, luận văn khỏi bố cục sách in. */
const isDigitalDetailBook = computed(() => String(detailBook.value?.resource_type || '') === 'digital');

function detailDigitalPrimaryName(b) {
    return digitalAttachmentFileName(b);
}

const detailDownloading = ref(false);

function onDetailDownloadPdf() {
    if (!detailBook.value || detailDownloading.value) return;
    if (!hasAdminDigitalAttachment(detailBook.value)) return;
    detailDownloading.value = true;
    try {
        downloadAdminDigitalAsset(detailBook.value);
    } catch {
        toast.error('Không có file đính kèm để tải.', { title: 'Tải file' });
    } finally {
        window.setTimeout(() => {
            detailDownloading.value = false;
        }, 1500);
    }
}

const importTitleByPageKind = {
    printed: 'Nhập sách in từ Excel',
    textbook: 'Nhập sách giáo trình từ Excel',
    reference: 'Nhập sách tham khảo từ Excel',
    digital: 'Nhập đồ án, luận văn từ Excel',
};

const importDescriptionByPageKind = {
    printed: 'Tải file mẫu, điền danh sách sách in (giáo trình/tham khảo) rồi chọn file để nhập.',
    textbook: 'Tải file mẫu, điền danh sách sách giáo trình (một dòng một bản ghi), sau đó chọn file để nhập.',
    reference: 'Tải file mẫu, điền danh sách sách tham khảo (một dòng một bản ghi), sau đó chọn file để nhập.',
    digital: 'Tải file mẫu, điền danh sách đồ án/luận văn (một dòng một bản ghi), sau đó chọn file để nhập.',
};

const addLabel = computed(() => (pageKind.value === 'digital' ? 'Thêm đồ án, luận văn' : 'Thêm sách'));
const updateFileLabel = computed(() => 'Cập nhật ảnh bìa');
const searchPlaceholder = computed(() => (
    pageKind.value === 'digital'
        ? 'Mã tài liệu, tên đồ án/luận văn, tác giả, đơn vị...'
        : 'Mã sách, tên sách, tác giả, NXB, nơi XB, năm XB...'
));
</script>

<template>
    <Head :title="`Danh mục – ${pageLabel}`" />
    <AdminLayout
        title="Danh mục tài liệu"
        :breadcrumbs="[
            { label: 'Danh mục tài liệu' },
            { label: pageLabel },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading :title="`${pageLabel} theo danh mục`">
                <template #actions>
                    <Button variant="outline" size="sm" class="gap-1.5" @click="showTrashDrawer = true">
                        <Icon :icon="ADMIN_ICONS.trash" class="w-4 h-4" />
                        Thùng rác
                    </Button>
                </template>
            </AdminPageHeading>

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.size"
                :update-file-label="updateFileLabel"
                :show-update-file="true"
                :show-import="pageKind !== 'digital'"
                :add-label="addLabel"
                @add="openAddModal"
                @export-excel="exportExcel"
                @import-excel="openImportModal"
                @update-file="() => openCoverModal()"
                @delete-selected="openDeleteMultiple"
                @deselect-all="deselectAll"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                :search-placeholder="searchPlaceholder"
                :show-filter-button="false"
                @search="searchBooks"
            >
                <template #filters>
                    <div class="flex items-center gap-3 flex-wrap">
                        <AdminFilterPanel
                            :options="SEARCH_IN_OPTIONS"
                            v-model:model-value="filterValues.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select v-model="filterValues.status" class="admin-filter-select admin-filter-select-centered">
                            <option value="">Trạng thái</option>
                            <option value="in_stock">Còn lưu hành</option>
                            <option value="out_of_stock">Không lưu hành</option>
                        </select>
                        <select
                            v-if="pageKind === 'printed'"
                            v-model="filterValues.printType"
                            class="admin-filter-select admin-filter-select-centered"
                        >
                            <option
                                v-for="option in PRINT_TYPE_OPTIONS"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <select v-model="filterValues.priceSort" class="admin-filter-select admin-filter-select-centered">
                            <option
                                v-for="option in BOOK_SORT_OPTIONS"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <BooksTable
                :page-kind="pageKind"
                :books="filteredBooks"
                :selected-ids="selectedIds"
                :is-all-selected="isAllSelected"
                :has-selection="hasSelection"
                @toggle-select-all="toggleSelectAll"
                @toggle-select="toggleSelect"
                @view="openDetailModal"
                @edit="openEditModal"
                @delete="openDeleteOne"
                @cover="openCoverModal"
            />

            <AdminPaginationBar
                always-show
                :current-page="booksPagination.current_page"
                :last-page="booksPagination.last_page"
                :disabled="loading"
                @go-page="goBooksPage"
            />
        </div>

        <BookFormModal
            v-if="showModal"
            :show="showModal"
            :is-editing="isEditing"
            :page-kind="pageKind"
            :form="form"
            :classifications="classifications"
            :warehouses="warehouses"
            :cabinet-options="cabinetOptions"
            :storage-suggestion-loading="storageSuggestionLoading"
            :storage-suggestion-message="storageSuggestionMessage"
            :create-cover-preview-url="createCoverPreviewUrl"
            :set-create-cover-file="setCreateCoverFile"
            :clear-create-cover-file="clearCreateCoverFile"
            :edit-existing-cover-url="editExistingCoverUrl"
            :edit-existing-digital-file-name="editExistingDigitalFileName"
            :clear-edit-existing-cover="clearEditExistingCover"
            :clear-edit-existing-digital-file-name="clearEditExistingDigitalFileName"
            :set-create-digital-file="setCreateDigitalFile"
            :clear-create-digital-file="clearCreateDigitalFile"
            :save-loading="saveBookLoading"
            :field-errors="bookFormErrors"
            :clear-field-error="clearBookFieldError"
            @close="showModal = false"
            @save="saveBook"
            @book-code-touched="markBookCodeTouched"
            @registration-touched="markRegistrationTouched"
        />

        <AdminFileModal
            :show="showCoverModal"
            :title="coverBulkMode ? 'Cập nhật ảnh bìa hàng loạt' : 'Cập nhật ảnh bìa sách'"
            :description="
                coverBulkMode
                    ? selectedIds.size > 0
                        ? `Đã chọn ${selectedIds.size} sách — chỉ cập nhật bìa cho các bản ghi đã chọn (tên file trong .zip = mã sách). Ảnh trong zip trùng mã nhưng không nằm trong lựa chọn sẽ bị bỏ qua.`
                        : 'File .zip: mỗi ảnh đặt tên đúng mã sách + đuôi (jpg, png...). Cập nhật mọi sách có mã khớp trong zip (không chọn dòng nào).'
                    : 'Kéo thả ảnh vào đây hoặc chọn file. Tên file không quan trọng, hệ thống tự đặt tên.'
            "
            :accept="coverBulkMode ? '.zip' : '.jpg,.jpeg,.png,.gif,.webp'"
            :max-size-mb="coverBulkMode ? 50 : 10"
            submit-label="Lưu"
            :loading="coverUploadLoading"
            @close="closeCoverModal"
            @submit="(file) => uploadCover(file)"
        />

        <AdminFileModal
            v-if="pageKind !== 'digital'"
            :show="showImportModal"
            :title="importTitleByPageKind[pageKind] || importTitleByPageKind.textbook"
            :description="importDescriptionByPageKind[pageKind] || importDescriptionByPageKind.textbook"
            accept=".xls,.xlsx,.csv"
            :max-size-mb="10"
            template-label="Tải file mẫu sách"
            submit-label="Nhập Excel"
            :loading="importLoading"
            @close="showImportModal = false"
            @submit="
                (file) => {
                    importBooksExcel(file);
                    showImportModal = false;
                }
            "
            @download-template="downloadBooksTemplate"
        />

        <AdminDeleteConfirmModal
            :show="showDeleteConfirm"
            title="Xác nhận xóa sách"
            item-label="sách"
            :item="selectedBook"
            :selected-count="selectedBook ? 0 : selectedIds.size"
            :loading="deleteLoading"
            @close="showDeleteConfirm = false"
            @confirm="confirmDelete"
        />

        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác"
            item-label-key="title"
            :items="trashedBooks"
            :loading="false"
            @close="showTrashDrawer = false"
            @restore="restoreBook"
            @restore-many="restoreManyBooks"
            @force-delete="forceDeleteBook"
            @force-delete-many="forceDeleteManyBooks"
        />

        <div v-if="showDetailModal && detailBook" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="showDetailModal = false" />
            <div class="relative w-full max-w-4xl max-h-[88vh] overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <template v-if="isDigitalDetailBook">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết đồ án, luận văn</h3>
                        </template>
                        <template v-else>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Chi tiết sách</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Thông tin nhanh của đầu sách</p>
                        </template>
                    </div>
                    <button
                        type="button"
                        class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        @click="showDetailModal = false"
                    >
                        <Icon icon="lucide:x" class="w-4 h-4" />
                    </button>
                </div>

                <template v-if="isDigitalDetailBook">
                    <div class="grid grid-cols-1 md:grid-cols-[140px,1fr] gap-6">
                        <div
                            class="mx-auto aspect-[3/4] w-full max-w-[140px] overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200/80 dark:bg-slate-800 dark:ring-slate-700/80 md:mx-0"
                        >
                            <img
                                :src="detailBook.cover_image || '/images/default-book-cover.png'"
                                :alt="detailBook.title || 'Ảnh bìa'"
                                class="h-full w-full object-cover"
                                @error="withFallback('/images/default-book-cover.png')($event)"
                            />
                        </div>
                        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div v-if="detailLoading" class="sm:col-span-2 text-xs text-slate-500 dark:text-slate-400">
                                Đang tải dữ liệu chi tiết...
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-slate-500 dark:text-slate-400">Tên tài liệu</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ detailBook.title || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Mã sách</p>
                                <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.book_code || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Tác giả</p>
                                <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.authors_label || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">Người đăng</p>
                                <p class="font-medium text-slate-800 dark:text-slate-200">
                                    {{ digitalDetailSubmitterLabel(detailBook) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-slate-500 dark:text-slate-400">
                                    {{ detailBook.digital_submission?.submitted_at ? 'Thời gian gửi' : 'Thời gian tạo đầu mục' }}
                                </p>
                                <p class="font-medium text-slate-800 dark:text-slate-200">{{ digitalDetailTimeLabel(detailBook) }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-slate-500 dark:text-slate-400 mb-1">Tóm tắt</p>
                                <RichHtmlContent :html="detailBook.summary" empty-text="Chưa có mô tả." />
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-slate-500 dark:text-slate-400">File đính kèm (PDF)</p>
                                <button
                                    v-if="hasAdminDigitalAttachment(detailBook)"
                                    type="button"
                                    class="font-semibold text-blue-700 hover:underline disabled:opacity-50 dark:text-blue-300"
                                    :disabled="detailDownloading"
                                    @click="onDetailDownloadPdf"
                                >
                                    {{ detailDownloading ? 'Đang tải…' : detailDigitalPrimaryName(detailBook) }}
                                </button>
                                <p v-else class="font-medium text-slate-500 dark:text-slate-400">—</p>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="grid grid-cols-1 md:grid-cols-[104px,1fr] gap-4">
                    <div class="h-28 w-20 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 ring-1 ring-slate-200/80 dark:ring-slate-700/80">
                        <img
                            :src="detailBook.cover_image || '/images/default-book-cover.png'"
                            :alt="detailBook.title"
                            class="h-full w-full object-cover"
                            @error="withFallback('/images/default-book-cover.png')($event)"
                        />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                        <div v-if="detailLoading" class="sm:col-span-2 lg:col-span-3 text-xs text-slate-500 dark:text-slate-400">
                            Đang tải dữ liệu chi tiết...
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Tên sách</p>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ detailBook.title || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Mã sách</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.book_code || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Sổ đăng ký cá biệt</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.registration_number || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Số lượng còn</p>
                            <p class="font-semibold text-emerald-700 dark:text-emerald-300">
                                {{ detailBook.real_quantity ?? detailBook.available_quantity ?? detailBook.quantity ?? 0 }}
                            </p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Trạng thái lưu hành</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">
                                {{ detailBook.circulation_status_label || 'Không lưu hành' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Tác giả</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.authors_label || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Nhà xuất bản</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.publishers_label || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Phân loại</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.classification?.name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Kho</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">
                                <template v-if="detailBook.warehouse">
                                    {{
                                        [detailBook.warehouse.code, detailBook.warehouse.name]
                                            .map((s) => String(s || '').trim())
                                            .filter(Boolean)
                                            .join(' – ') || '—'
                                    }}
                                </template>
                                <template v-else>—</template>
                            </p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Tủ lưu trữ</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.cabinet || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Ngôn ngữ</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.language || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Năm xuất bản</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.published_year || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Số trang</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.pages ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Khổ sách</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.book_size || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 dark:text-slate-400">Giá bìa</p>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ detailBook.price ?? '—' }}</p>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <p class="text-slate-500 dark:text-slate-400 mb-1">Tóm tắt nội dung</p>
                            <RichHtmlContent :html="detailBook.summary" empty-text="Chưa có mô tả." />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <p class="text-slate-500 dark:text-slate-400 mb-1">Lịch sử mượn sách (20 lượt gần nhất)</p>
                            <div class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <table class="w-full text-xs">
                                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                                        <tr>
                                            <th class="px-2 py-2 text-left">Mã phiếu</th>
                                            <th class="px-2 py-2 text-left">Bạn đọc</th>
                                            <th class="px-2 py-2 text-left">Mượn</th>
                                            <th class="px-2 py-2 text-left">Hạn trả</th>
                                            <th class="px-2 py-2 text-left">Đã trả</th>
                                            <th class="px-2 py-2 text-left">Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="item in (detailBook.loan_history || [])" :key="`${item.loan_id}-${item.loan_date}-${item.loan_code}`">
                                            <td class="px-2 py-2 font-medium text-slate-700 dark:text-slate-200">{{ item.loan_code || '—' }}</td>
                                            <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ item.reader_name || '—' }}</td>
                                            <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ formatDate(item.loan_date) }}</td>
                                            <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ formatDate(item.due_date) }}</td>
                                            <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ formatDate(item.return_date) }}</td>
                                            <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ item.loan_status || '—' }}</td>
                                        </tr>
                                        <tr v-if="!(detailBook.loan_history || []).length">
                                            <td colspan="6" class="px-2 py-3 text-center text-slate-500 dark:text-slate-400">
                                                Chưa có lịch sử mượn.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
