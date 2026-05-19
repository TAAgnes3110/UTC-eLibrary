import { ref, computed, onBeforeUnmount, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import apiClient from '@/api/axios';
import { booksApi } from '@/api/books';
import { warehousesApi } from '@/api/warehouses';
import { toast } from '@/store/toast';
import {
    prepareAdminApiAuthOnce,
    callWithSessionFallback,
    sessionApiPost,
    uploadDigitalAssetViaSession,
    uploadBookCoverViaSession,
} from '@/utils/adminApiAuth';
import { BOOK_FORM_FIELD_MAP, getFieldErrorsFromAxiosError, getLaravelErrorMessage } from '@/utils/laravelApiError';
import { useApiFieldErrors } from '@/composables/useApiFieldErrors';
import { toastShort, bookFormClientError } from '@/constants/adminUiMessages';
import { extractApiPaginator } from '@/utils/adminPagination';
import { primaryDigitalAsset } from '@/utils/adminDigitalAsset';

const BOOKS_PER_PAGE = 20;
const CURRENT_YEAR = new Date().getFullYear();

function matchLookupId(list, text) {
    const raw = (text || '').trim();
    if (!raw || !Array.isArray(list)) return null;
    const t = raw.toLowerCase();
    for (const item of list) {
        const code = String(item.code ?? '').trim().toLowerCase();
        const name = String(item.name ?? '').trim().toLowerCase();
        const label = code && name ? `${code} – ${name}`.toLowerCase() : '';
        if (code && t === code) return item.id;
        if (name && t === name) return item.id;
        if (label && t === label) return item.id;
    }
    for (const item of list) {
        const code = String(item.code ?? '').trim().toLowerCase();
        const name = String(item.name ?? '').trim().toLowerCase();
        if (code && t.includes(code)) return item.id;
        if (name && t.includes(name)) return item.id;
    }
    return null;
}

function toLookupLabel(item) {
    if (!item) return '';
    const code = String(item.code ?? '').trim();
    const name = String(item.name ?? '').trim();
    if (code && name) return `${code} – ${name}`;
    return name || code;
}

export const SEARCH_IN_OPTIONS = [
    { key: 'code', label: 'Mã sách' },
    { key: 'title', label: 'Tên sách' },
    { key: 'author', label: 'Tác giả' },
    { key: 'publisher', label: 'Nhà xuất bản' },
    { key: 'place', label: 'Nơi xuất bản' },
    { key: 'year', label: 'Năm xuất bản' },
    { key: 'classification', label: 'Phân loại' },
];

export const BOOK_SORT_OPTIONS = [
    { value: 'newest', label: 'Mới nhất' },
    { value: 'oldest', label: 'Cũ nhất' },
];

export const PRINT_TYPE_OPTIONS = [
    { value: 'textbook', label: 'Sách giáo trình' },
    { value: 'reference', label: 'Sách tham khảo' },
    { value: 'all', label: 'Tất cả' },
];

const RESOURCE_TYPE_OPTIONS = [
    { value: 'textbook', label: 'Sách giáo trình' },
    { value: 'reference', label: 'Sách tham khảo' },
    { value: 'digital', label: 'Đồ án, luận văn' },
];

function normalizeResourceTypeInput(rawValue) {
    const raw = String(rawValue || '').trim().toLowerCase();
    if (!raw) return '';

    const direct = RESOURCE_TYPE_OPTIONS.find((o) => raw === o.value || raw === o.label.toLowerCase());
    if (direct) return direct.value;

    if (raw.includes('giao trinh') || raw.includes('giáo trình') || raw === 'textbook') return 'textbook';
    if (raw.includes('tham khao') || raw.includes('tham khảo') || raw === 'reference') return 'reference';
    if (
        raw.includes('tai lieu so')
        || raw.includes('tài liệu số')
        || raw.includes('đồ án')
        || raw.includes('luận văn')
        || raw.includes('do an')
        || raw.includes('đồ án')
        || raw.includes('luan van')
        || raw.includes('luận văn')
        || raw.includes('digital')
    ) return 'digital';

    return '';
}

export function useBooksAdminPage() {
    const page = usePage();
    const pageKind = computed(() => page.props.pageKind ?? 'printed');
    const resourceTypeFilter = computed(() => page.props.resourceTypeFilter ?? '');
    const pageLabel = computed(() => {
        if (pageKind.value === 'printed') return 'Sách in';
        if (pageKind.value === 'digital') return 'Đồ án, luận văn';
        return 'Sách in';
    });

    const books = ref([]);
    const booksPageNum = ref(1);
    const booksListMeta = ref({
        current_page: 1,
        last_page: 1,
        per_page: BOOKS_PER_PAGE,
        total: 0,
    });
    const warehouses = ref([]);
    const saveBookLoading = ref(false);
    /** Khóa rời trang khi lưu/upload lỗi — giữ modal mở để xử lý. */
    const saveBlockedByError = ref(false);

    const classifications = ref([]);
    let booksReloadDebounce = null;
    let identifiersPreviewDebounce = null;
    let storageSuggestionDebounce = null;
    let loadBooksAbortController = null;
    let identifiersRequestSerial = 0;
    let storageSuggestionRequestSerial = 0;
    const selectedClassificationId = ref('');
    const matrixClassificationId = ref('');
    const loading = ref(false);

    const trashedBooks = ref([]);

    const filterValues = ref({
        searchKeyword: '',
        status: '',
        printType: 'all',
        priceSort: 'newest',
        searchIn: {
            code: true,
            title: true,
            author: true,
            publisher: true,
            place: true,
            year: true,
            classification: true,
        },
    });

    const showFilterPanel = ref(false);
    function buildSearchInParam() {
        const sin = filterValues.value.searchIn || {};
        const active = SEARCH_IN_OPTIONS.map((o) => o.key).filter((k) => !!sin[k]);
        if (active.length === 0 || active.length === SEARCH_IN_OPTIONS.length) {
            return undefined;
        }
        return active.join(',');
    }


    const booksPagination = computed(() => ({
        current_page: booksListMeta.value.current_page,
        last_page: booksListMeta.value.last_page,
        per_page: booksListMeta.value.per_page,
        total: booksListMeta.value.total,
    }));

    const goBooksPage = (page) => {
        if (warnIfSaveErrorLocked('chuyển trang danh sách')) {
            return;
        }
        const p = Number(page);
        if (!Number.isFinite(p) || p < 1 || p > booksListMeta.value.last_page) {
            return;
        }
        booksPageNum.value = p;
        loadBooks();
    };

    const searchBooks = () => {
        if (warnIfSaveErrorLocked('tìm kiếm lại')) {
            return;
        }
        booksPageNum.value = 1;
        loadBooks();
    };

    const scheduleLoadBooks = ({ resetPage = false, delayMs = 220 } = {}) => {
        if (resetPage) {
            booksPageNum.value = 1;
        }
        if (booksReloadDebounce) clearTimeout(booksReloadDebounce);
        booksReloadDebounce = setTimeout(() => {
            loadBooks();
        }, delayMs);
    };

    function setMatrixFilter({ classificationId }) {
        matrixClassificationId.value = classificationId != null ? String(classificationId) : '';
    }

    function clearMatrixFilter() {
        matrixClassificationId.value = '';
    }

    const showModal = ref(false);
    const isEditing = ref(false);
    const selectedBook = ref(null);
    const showDeleteConfirm = ref(false);
    const deleteLoading = ref(false);
    const selectedIds = ref(new Set());

    const showTrashDrawer = ref(false);
    const showCoverModal = ref(false);
    const coverBulkMode = ref(false);
    const coverUploadLoading = ref(false);
    const coverTargetBookId = ref(null);

    const showImportModal = ref(false);
    const importLoading = ref(false);
    const bookCodeTouched = ref(false);
    const registrationTouched = ref(false);
    const warehouseTouched = ref(false);
    const settingWarehouseSuggestion = ref(false);
    const storageOptions = ref([]);
    const storageSuggestionLoading = ref(false);
    const storageSuggestionMessage = ref('');
    const createCoverFile = ref(null);
    const createCoverPreviewUrl = ref('');
    const createDigitalFile = ref(null);
    /** Khi sửa: ảnh bìa / tên PDF đang có trên server (không phải file mới chọn). */
    const editExistingCoverUrl = ref('');
    const editExistingDigitalFileName = ref('');

    const {
        fieldErrors: bookFormErrors,
        clearField: clearBookFieldError,
        clearAll: clearBookFormErrors,
        applyAxios422: applyBookApiErrors,
        setClientErrors: setBookClientErrors,
    } = useApiFieldErrors(BOOK_FORM_FIELD_MAP);

    const DIGITAL_UPLOAD_FIELD_MAP = { ...BOOK_FORM_FIELD_MAP, file: 'digital_file' };

    function formatSaveStepError(stepLabel, error) {
        const custom = typeof error?.message === 'string' ? error.message.trim() : '';
        const detail = custom && custom !== 'Network Error'
            ? custom
            : getLaravelErrorMessage(error, 'Không rõ nguyên nhân');
        const status = error?.response?.status;
        const suffix = status ? ` (HTTP ${status})` : '';
        return `${stepLabel}: ${detail}${suffix}`;
    }

    function applySaveStepApiErrors(error, fieldMap = BOOK_FORM_FIELD_MAP) {
        return getFieldErrorsFromAxiosError(error, fieldMap);
    }

    function activateSaveErrorLock() {
        saveBlockedByError.value = true;
    }

    function clearSaveErrorLock() {
        saveBlockedByError.value = false;
    }

    function requestCloseBookModal() {
        if (saveBookLoading.value) {
            toast.warn('Đang lưu/upload — vui lòng đợi hoàn tất.', { title: 'Chưa thể đóng' });
            return;
        }
        if (saveBlockedByError.value) {
            const ok = window.confirm(
                'Lưu/upload chưa hoàn tất (PDF có thể chưa lên server).\n\n'
                + 'Đóng form? Bạn có thể mở «Sửa» từ danh sách để thử upload lại.'
            );
            if (!ok) {
                return;
            }
            clearSaveErrorLock();
        }
        showModal.value = false;
    }

    function warnIfSaveErrorLocked(context = 'thao tác này') {
        if (!showModal.value || !saveBlockedByError.value) {
            return false;
        }
        toast.warn(
            `Upload PDF chưa xong. Xem lỗi trong form và bấm Lưu lại trước khi ${context}.`,
            { title: 'Chưa thể tiếp tục' }
        );
        return true;
    }

    let removeInertiaGuard = null;

    function bindNavigationGuard() {
        if (removeInertiaGuard || typeof window === 'undefined') {
            return;
        }
        removeInertiaGuard = router.on('before', (event) => {
            if (!showModal.value) {
                return;
            }
            if (saveBookLoading.value) {
                event.preventDefault();
                toast.warn('Đang lưu/upload — vui lòng đợi hoàn tất.', { title: 'Chưa thể rời trang' });
                return;
            }
            if (saveBlockedByError.value) {
                event.preventDefault();
                toast.warn(
                    'Upload PDF chưa xong. Xem lỗi trong form và bấm Lưu lại trước khi chuyển trang.',
                    { title: 'Chưa thể rời trang' }
                );
            }
        });
    }

    function unbindNavigationGuard() {
        if (removeInertiaGuard) {
            removeInertiaGuard();
            removeInertiaGuard = null;
        }
    }

    function handleBeforeUnload(event) {
        if (!showModal.value) {
            return;
        }
        if (saveBookLoading.value || saveBlockedByError.value) {
            event.preventDefault();
            event.returnValue = '';
        }
    }

    watch([showModal, saveBlockedByError, saveBookLoading], ([modal, blocked, loading]) => {
        if (modal && (blocked || loading)) {
            bindNavigationGuard();
        } else {
            unbindNavigationGuard();
        }
    });

    onMounted(() => {
        if (typeof window !== 'undefined') {
            window.addEventListener('beforeunload', handleBeforeUnload);
        }
    });

    onBeforeUnmount(() => {
        unbindNavigationGuard();
        if (typeof window !== 'undefined') {
            window.removeEventListener('beforeunload', handleBeforeUnload);
        }
    });

    const filteredBooks = computed(() => {
        let list = [...books.value];
        const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
        if (kw) {
            const sin = filterValues.value.searchIn || {};
            const anyChecked = Object.values(sin).some(Boolean);
            if (anyChecked) {
                list = list.filter((b) => {
                    const checks = [];
                    if (sin.code) checks.push((b.book_code || '').toLowerCase().includes(kw));
                    if (sin.title) checks.push((b.title || '').toLowerCase().includes(kw));
                    if (sin.author) checks.push((b.authors_label || '').toLowerCase().includes(kw));
                    if (sin.publisher) checks.push((b.publishers_label || '').toLowerCase().includes(kw));
                    if (sin.place) checks.push((b.publisher_place || '').toLowerCase().includes(kw));
                    if (sin.year) checks.push(String(b.published_year || '').toLowerCase().includes(kw));
                    if (sin.classification) {
                        checks.push((b.classification?.code || '').toLowerCase().includes(kw));
                        checks.push((b.classification?.name || '').toLowerCase().includes(kw));
                    }
                    return checks.some(Boolean);
                });
            }
        }
        if (filterValues.value.status) {
            if (filterValues.value.status === 'in_stock') {
                list = list.filter((b) => (b.real_quantity ?? 0) > 0);
            } else if (filterValues.value.status === 'out_of_stock') {
                list = list.filter((b) => (b.real_quantity ?? 0) <= 0);
            }
        }
        if (selectedClassificationId.value) {
            list = list.filter(
                (b) =>
                    String(b.classification_id) === String(selectedClassificationId.value) ||
                    String(b.classification?.id ?? '') === String(selectedClassificationId.value),
            );
        }
        if (matrixClassificationId.value) {
            list = list.filter((b) => {
                const rowId = b.classification?.id ?? b.classification_id;
                return String(rowId ?? '') === String(matrixClassificationId.value);
            });
        }
        return list;
    });

    const cabinetOptions = computed(() => {
        const seen = new Set();
        const out = [];
        for (const row of storageOptions.value || []) {
            const name = String(row?.cabinet || '').trim();
            if (!name) continue;
            const key = name.toLowerCase();
            if (seen.has(key)) continue;
            seen.add(key);
            out.push(name);
        }
        return out;
    });

    async function previewIdentifiers() {
        const resourceType = normalizeResourceTypeInput(form.value.resource_type);
        if (resourceType === 'digital') {
            form.value.book_code = '';
            form.value.registration_number = '';
            return;
        }
        const warehouseId = matchLookupId(warehouses.value, form.value.warehouse);
        if (!warehouseId) return;
        const requestSerial = ++identifiersRequestSerial;

        let classificationId = matchLookupId(classifications.value, form.value.classification);
        if (!classificationId) classificationId = null;
        try {
            const payload = await booksApi.previewIdentifiers({
                warehouse_id: warehouseId,
            });
            if (requestSerial !== identifiersRequestSerial) return;
            const data = payload?.data ?? payload ?? {};
            if ((!bookCodeTouched.value || !String(form.value.book_code || '').trim()) && data.book_code) {
                form.value.book_code = String(data.book_code);
            }
            if ((!registrationTouched.value || !String(form.value.registration_number || '').trim()) && data.registration_number) {
                form.value.registration_number = String(data.registration_number);
            }
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to preview identifiers', e);
        }
    }

    const previewWarehouseId = computed(() => matchLookupId(warehouses.value, form.value.warehouse));
    const previewClassificationId = computed(() => matchLookupId(classifications.value, form.value.classification));
    async function loadStorageSuggestions() {
        const warehouseId = previewWarehouseId.value;
        const requestSerial = ++storageSuggestionRequestSerial;

        storageSuggestionMessage.value = '';
        if (!warehouseId) {
            storageOptions.value = [];
            return;
        }

        const resourceType = normalizeResourceTypeInput(form.value.resource_type);
        if (!resourceType || resourceType === 'digital') {
            storageOptions.value = [];
            if (resourceType === 'digital') {
                form.value.cabinet = '';
            }
            return;
        }

        storageSuggestionLoading.value = true;
        try {
            const payload = await booksApi.storageSuggestions({
                warehouse_id: warehouseId,
            });
            if (requestSerial !== storageSuggestionRequestSerial) return;
            const data = payload?.data ?? payload ?? {};
            const items = Array.isArray(data.items) ? data.items : [];
            storageOptions.value = items;
            storageSuggestionMessage.value = String(data.message || '').trim();

            if (items.length === 0) {
                form.value.cabinet = '';
                return;
            }

            const currentCabinet = String(form.value.cabinet || '').trim();
            const hasCurrent = items.some((row) => row.cabinet === currentCabinet);
            if (!hasCurrent) {
                form.value.cabinet = String(items[0].cabinet || '');
            }
        } catch (e) {
            storageOptions.value = [];
            storageSuggestionMessage.value = 'Không thể tải gợi ý tủ sách.';
            // eslint-disable-next-line no-console
            console.error('Failed to load storage suggestions', e);
        } finally {
            storageSuggestionLoading.value = false;
        }
    }

    const loadWarehouses = async () => {
        try {
            const payload = await warehousesApi.list({ keyword: '', page: 1 });
            const data = payload?.data ?? payload;
            const items = Array.isArray(data) ? data : (data?.data ?? []);
            warehouses.value = items;
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to load warehouses', e);
            warehouses.value = [];
        }
    };

    const loadBooks = async () => {
        if (loadBooksAbortController) {
            loadBooksAbortController.abort();
        }
        loadBooksAbortController = new AbortController();
        loading.value = true;
        try {
            const effectiveResourceType = (() => {
                if (pageKind.value === 'printed') {
                    if (filterValues.value.printType === 'textbook') return 'textbook';
                    if (filterValues.value.printType === 'reference') return 'reference';
                    return 'textbook,reference';
                }
                return resourceTypeFilter.value || undefined;
            })();
            const response = await apiClient.get('/books', {
                signal: loadBooksAbortController.signal,
                params: {
                    per_page: BOOKS_PER_PAGE,
                    page: booksPageNum.value,
                    keyword: filterValues.value.searchKeyword || undefined,
                    search_in: buildSearchInParam(),
                    sort: filterValues.value.priceSort || 'newest',
                    ...(effectiveResourceType ? { resource_type: effectiveResourceType } : {}),
                },
            });
            const payload = response?.data;
            const { items, meta } = extractApiPaginator(payload, BOOKS_PER_PAGE);
            books.value = items;
            booksListMeta.value = {
                current_page: meta.current_page,
                last_page: meta.last_page,
                per_page: meta.per_page,
                total: meta.total,
            };
            booksPageNum.value = meta.current_page;
        } catch (e) {
            if (e?.name === 'CanceledError' || e?.code === 'ERR_CANCELED') {
                return;
            }
            // eslint-disable-next-line no-console
            console.error('Failed to load books', e);
            books.value = [];
            booksListMeta.value = {
                current_page: 1,
                last_page: 1,
                per_page: BOOKS_PER_PAGE,
                total: 0,
            };
        } finally {
            if (loadBooksAbortController?.signal?.aborted !== true) {
                loadBooksAbortController = null;
            }
            loading.value = false;
        }
    };

    const loadClassifications = async () => {
        try {
            const response = await apiClient.get('/classifications/list');
            const payload = response?.data;
            classifications.value = Array.isArray(payload?.data) ? payload.data : [];
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to load classifications', e);
            classifications.value = [];
        }
    };

    onMounted(async () => {
        await loadClassifications();
    });

    onBeforeUnmount(() => {
        if (booksReloadDebounce) clearTimeout(booksReloadDebounce);
        if (identifiersPreviewDebounce) clearTimeout(identifiersPreviewDebounce);
        if (storageSuggestionDebounce) clearTimeout(storageSuggestionDebounce);
        if (loadBooksAbortController) {
            loadBooksAbortController.abort();
            loadBooksAbortController = null;
        }
    });

    watch(
        () => page.props.resourceTypeFilter ?? '',
        () => {
            scheduleLoadBooks({ resetPage: true, delayMs: 120 });
        },
        { immediate: true },
    );

    watch(
        () => filterValues.value.priceSort,
        () => {
            scheduleLoadBooks({ resetPage: true, delayMs: 120 });
        },
    );
    watch(
        () => filterValues.value.printType,
        () => {
            if (pageKind.value !== 'printed') return;
            scheduleLoadBooks({ resetPage: true, delayMs: 120 });
        },
    );
    watch(
        () => filterValues.value.searchIn,
        () => {
            scheduleLoadBooks({ resetPage: true, delayMs: 180 });
        },
        { deep: true }
    );
    const hasSelection = computed(() => selectedIds.value.size > 0);
    const isAllSelected = computed(
        () => filteredBooks.value.length > 0 && selectedIds.value.size === filteredBooks.value.length,
    );

    function toggleSelectAll() {
        if (isAllSelected.value) {
            selectedIds.value.clear();
        } else {
            filteredBooks.value.forEach((b) => selectedIds.value.add(b.id));
        }
        selectedIds.value = new Set(selectedIds.value);
    }

    function toggleSelect(id) {
        if (selectedIds.value.has(id)) {
            selectedIds.value.delete(id);
        } else {
            selectedIds.value.add(id);
        }
        selectedIds.value = new Set(selectedIds.value);
    }

    function deselectAll() {
        selectedIds.value.clear();
        selectedIds.value = new Set(selectedIds.value);
    }

    const emptyForm = () => ({
        id: null,
        registration_number: '',
        book_code: '',
        title: '',
        sub_title: '',
        language: '',
        authors: '',
        publisher: '',
        published_year: '',
        pages: '',
        book_size: '',
        description: '',
        price: '',
        classification: '',
        warehouse: '',
        cabinet: '',
        quantity: 1,
        resource_type: '',
    });

    const form = ref(emptyForm());

    function clearCreateCoverFile() {
        if (createCoverPreviewUrl.value && createCoverPreviewUrl.value.startsWith('blob:')) {
            URL.revokeObjectURL(createCoverPreviewUrl.value);
        }
        createCoverFile.value = null;
        createCoverPreviewUrl.value = '';
    }

    function setCreateCoverFile(file) {
        if (!(file instanceof File)) {
            clearCreateCoverFile();
            return;
        }
        if (createCoverPreviewUrl.value && createCoverPreviewUrl.value.startsWith('blob:')) {
            URL.revokeObjectURL(createCoverPreviewUrl.value);
        }
        createCoverFile.value = file;
        createCoverPreviewUrl.value = URL.createObjectURL(file);
    }

    function clearCreateDigitalFile() {
        createDigitalFile.value = null;
    }

    function clearEditExistingMedia() {
        editExistingCoverUrl.value = '';
        editExistingDigitalFileName.value = '';
    }

    function clearEditExistingCover() {
        editExistingCoverUrl.value = '';
    }

    function clearEditExistingDigitalFileName() {
        editExistingDigitalFileName.value = '';
    }

    function fillFormFromBook(book) {
        const editResourceTypeRaw = String(book.resource_type || '').trim();
        const editResourceTypeNormalized = normalizeResourceTypeInput(editResourceTypeRaw);
        const editResourceTypeLabel = RESOURCE_TYPE_OPTIONS.find((o) => o.value === editResourceTypeNormalized)?.label
            || editResourceTypeRaw;
        form.value = {
            id: book.id ?? null,
            registration_number: book.registration_number || '',
            book_code: book.book_code || '',
            title: book.title || '',
            sub_title: book.sub_title || '',
            language: book.language || '',
            authors: book.authors_label || '',
            publisher: book.publishers_label || '',
            published_year: book.published_year || '',
            pages: book.pages ?? '',
            book_size: book.book_size || '',
            description: book.summary || '',
            price: book.price ?? '',
            classification: book.classification
                ? `${book.classification.code || ''} – ${book.classification.name || ''}`.trim()
                : '',
            warehouse: book.warehouse?.name || '',
            cabinet: book.cabinet || '',
            quantity: book.quantity ?? 1,
            resource_type: editResourceTypeLabel,
        };
    }

    function applyEditExistingMediaFromBook(book) {
        const cover = String(book?.cover_image || '').trim();
        editExistingCoverUrl.value = cover && !cover.includes('default-book-cover') ? cover : '';
        const asset = primaryDigitalAsset(book);
        editExistingDigitalFileName.value = String(asset?.original_name || '').trim();
    }

    function setCreateDigitalFile(file) {
        if (!(file instanceof File)) {
            clearCreateDigitalFile();
            return;
        }
        createDigitalFile.value = file;
    }

    function suggestWarehouseByResourceType(resourceType) {
        if (!Array.isArray(warehouses.value) || warehouses.value.length === 0) return null;
        const rt = String(resourceType || '').trim();
        if (!rt) return null;

        const normalizedRows = warehouses.value.map((w) => {
            const code = String(w?.code || '').trim();
            const name = String(w?.name || '').trim();
            return {
                row: w,
                code,
                name,
                codeLower: code.toLowerCase(),
                nameLower: name.toLowerCase(),
                combined: `${code} ${name}`.toLowerCase(),
            };
        });

        const byPriority = (...predicates) => {
            for (const predicate of predicates) {
                const found = normalizedRows.find(predicate);
                if (found) return found.row;
            }
            return null;
        };

        if (rt === 'textbook') {
            return byPriority(
                (x) => x.codeLower.includes('kho-gt'),
                (x) => x.combined.includes('giao trinh'),
                (x) => x.combined.includes('giáo trình')
            );
        }
        if (rt === 'reference') {
            return byPriority(
                (x) => x.codeLower.includes('kho-tk'),
                (x) => x.combined.includes('tham khao'),
                (x) => x.combined.includes('tham khảo')
            );
        }
        if (rt === 'digital') {
            return byPriority(
                (x) => x.codeLower.includes('kho-so'),
                (x) => x.combined.includes('tai lieu so'),
                (x) =>
                    x.combined.includes('tài liệu số')
                    || x.combined.includes('đồ án')
                    || x.combined.includes('luận văn'),
                (x) => x.combined.includes('digital')
            );
        }

        return null;
    }

    async function applyWarehouseSuggestionByResourceType() {
        if (warehouseTouched.value) return;
        const normalizedResourceType = normalizeResourceTypeInput(form.value.resource_type);
        const suggestion = suggestWarehouseByResourceType(normalizedResourceType);
        if (!suggestion) return;
        settingWarehouseSuggestion.value = true;
        form.value.warehouse = toLookupLabel(suggestion);
        clearBookFieldError('warehouse');
        settingWarehouseSuggestion.value = false;
    }

    async function collectBookClientErrors() {
        const errors = {};
        const allowedResourceTypes = ['textbook', 'reference', 'digital'];
        const resourceType = normalizeResourceTypeInput(form.value.resource_type);
        if (!allowedResourceTypes.includes(resourceType)) {
            errors.resource_type = 'Vui lòng chọn loại sách hợp lệ.';
        }
        const title = String(form.value.title || '').trim();
        if (!title) errors.title = bookFormClientError.titleRequired;

        await loadWarehouses();
        let warehouseId = null;
        if (pageKind.value !== 'digital') {
            const wh = String(form.value.warehouse || '').trim();
            if (!wh) {
                errors.warehouse = bookFormClientError.warehouseEmpty;
            } else {
                warehouseId = matchLookupId(warehouses.value, form.value.warehouse);
                if (!warehouseId) {
                    errors.warehouse = bookFormClientError.warehouseNoMatch;
                }
            }
        }

        const cls = String(form.value.classification || '').trim();
        let classificationId = null;
        if (pageKind.value !== 'digital') {
            if (!cls) {
                errors.classification = bookFormClientError.classificationEmpty;
            } else {
                classificationId = matchLookupId(classifications.value, form.value.classification);
                if (!classificationId) {
                    errors.classification = bookFormClientError.classificationNoMatch;
                }
            }
        }

        const qtyRaw = parseInt(String(form.value.quantity ?? 0), 10);
        if (pageKind.value !== 'digital' && (Number.isNaN(qtyRaw) || qtyRaw < 0)) {
            errors.quantity = bookFormClientError.quantityInvalid;
        }

        if (Object.keys(errors).length > 0) {
            return { ok: false, errors };
        }

        const cabinetName = String(form.value.cabinet || '').trim();
        if (Object.keys(errors).length > 0) {
            return { ok: false, errors };
        }

        return {
            ok: true,
            title,
            resourceType,
            warehouseId,
            classificationId,
            quantity: pageKind.value === 'digital' ? 0 : Math.max(0, qtyRaw),
            cabinetName,
        };
    }

    const openAddModal = async () => {
        await Promise.allSettled([loadWarehouses(), loadClassifications()]);
        isEditing.value = false;
        form.value = emptyForm();
        clearCreateCoverFile();
        clearCreateDigitalFile();
        clearEditExistingMedia();
        if (pageKind.value === 'digital') {
            form.value.resource_type = 'Đồ án, luận văn';
            form.value.quantity = 0;
            form.value.book_code = '';
            form.value.registration_number = '';
        }
        warehouseTouched.value = false;
        settingWarehouseSuggestion.value = false;
        bookCodeTouched.value = false;
        registrationTouched.value = false;
        storageOptions.value = [];
        storageSuggestionMessage.value = '';
        await applyWarehouseSuggestionByResourceType();
        clearBookFormErrors();
        clearSaveErrorLock();
        showModal.value = true;
    };

    const openEditModal = async (book) => {
        if (!book?.id) return;
        await Promise.allSettled([loadWarehouses()]);
        isEditing.value = true;
        clearBookFormErrors();
        clearSaveErrorLock();
        clearCreateCoverFile();
        clearCreateDigitalFile();
        clearEditExistingMedia();
        warehouseTouched.value = true;
        settingWarehouseSuggestion.value = false;
        bookCodeTouched.value = true;
        registrationTouched.value = true;
        storageSuggestionMessage.value = '';

        try {
            const res = await booksApi.get(book.id);
            const detail = res?.data ?? res;
            fillFormFromBook(detail);
            applyEditExistingMediaFromBook(detail);
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to load book detail', e);
            fillFormFromBook(book);
            applyEditExistingMediaFromBook(book);
        }

        showModal.value = true;
    };

    const saveBook = async () => {
        if (saveBookLoading.value) return;
        clearBookFormErrors();
        const client = await collectBookClientErrors();
        if (!client.ok) {
            setBookClientErrors(client.errors);
            activateSaveErrorLock();
            toast.error(toastShort.fail);
            return;
        }
        const {
            title,
            resourceType,
            warehouseId,
            classificationId,
            quantity: qty,
            cabinetName
        } = client;
        if (!isEditing.value && pageKind.value === 'digital' && !(createDigitalFile.value instanceof File)) {
            setBookClientErrors({ general: 'Vui lòng đính kèm file PDF cho đồ án/luận văn.' });
            activateSaveErrorLock();
            toast.error(toastShort.fail);
            return;
        }
        const payload = {
            title,
            resource_type: resourceType,
        };
        if (pageKind.value === 'digital') {
            payload.access_mode = 'online_only';
            payload.quantity = 0;
        }
        if (pageKind.value !== 'digital') payload.quantity = qty;
        if (pageKind.value !== 'digital' && warehouseId) payload.warehouse_id = warehouseId;
        if (pageKind.value !== 'digital') {
            const reg = String(form.value.registration_number || '').trim();
            if (reg) payload.registration_number = reg;
            const bookCode = String(form.value.book_code || '').trim();
            if (bookCode) payload.book_code = bookCode;
        }
        const authors = String(form.value.authors || '').trim();
        payload.authors = authors;
        const publisher = String(form.value.publisher || '').trim();
        payload.publisher = publisher;
        if (cabinetName) payload.cabinet = cabinetName;
        const summary = String(form.value.description || '').trim();
        if (summary) payload.summary = summary;
        const subTitle = String(form.value.sub_title || '').trim();
        if (subTitle) payload.sub_title = subTitle;
        const language = String(form.value.language || '').trim();
        if (language) payload.language = language;
        const py = parseInt(String(form.value.published_year || ''), 10);
        if (!Number.isNaN(py) && py >= 1900 && py <= CURRENT_YEAR) payload.published_year = py;
        const pages = parseInt(String(form.value.pages || ''), 10);
        if (!Number.isNaN(pages) && pages >= 0) payload.pages = pages;
        const bookSize = String(form.value.book_size || '').trim();
        if (bookSize) payload.book_size = bookSize;
        const priceNum = parseInt(String(form.value.price ?? ''), 10);
        if (!Number.isNaN(priceNum) && priceNum >= 0) payload.price = priceNum;
        if (pageKind.value !== 'digital' && classificationId) payload.classification_id = classificationId;

        saveBookLoading.value = true;
        clearSaveErrorLock();
        await prepareAdminApiAuthOnce();
        let savedBookId = isEditing.value && form.value.id != null ? Number(form.value.id) : null;

        try {
            if (isEditing.value && form.value.id != null) {
                await callWithSessionFallback(
                    () => booksApi.update(form.value.id, payload),
                    () => client.put(`/books/${form.value.id}`, payload, { skipBearerAuth: true }).then((r) => r.data)
                );
                savedBookId = Number(form.value.id);
            } else {
                const created = await callWithSessionFallback(
                    () => booksApi.create(payload),
                    () => sessionApiPost('/books', payload)
                );
                const createdBook = created?.data ?? created ?? {};
                const createdId = Number(createdBook?.id ?? 0);
                if (!Number.isInteger(createdId) || createdId <= 0) {
                    setBookClientErrors({
                        general: 'Bước 1 — Lưu thông tin: API không trả về ID sách. Mở Console (F12) hoặc bật localStorage api_debug=1 để xem chi tiết.',
                    });
                    activateSaveErrorLock();
                    toast.error('Tạo tài liệu số thất bại — không nhận được ID.', { title: 'Lưu' });
                    return;
                }
                savedBookId = createdId;
                if (pageKind.value === 'digital') {
                    isEditing.value = true;
                    form.value.id = createdId;
                    if (createdBook?.book_code) {
                        form.value.book_code = createdBook.book_code;
                    }
                }
            }

            if (createCoverFile.value instanceof File && savedBookId) {
                try {
                    await uploadBookCoverViaSession(savedBookId, createCoverFile.value);
                } catch (coverError) {
                    const msg = formatSaveStepError('Bước 2 — Ảnh bìa', coverError);
                    setBookClientErrors({ general: msg });
                    activateSaveErrorLock();
                    toast.error(msg, { title: 'Lưu' });
                    await loadBooks();
                    return;
                }
            }

            if (pageKind.value === 'digital' && createDigitalFile.value instanceof File && savedBookId) {
                try {
                    await uploadDigitalAssetViaSession(savedBookId, createDigitalFile.value);
                } catch (uploadError) {
                    const fieldErrors = applySaveStepApiErrors(uploadError, DIGITAL_UPLOAD_FIELD_MAP);
                    const stepMsg = formatSaveStepError('Bước 3 — Upload PDF', uploadError);
                    setBookClientErrors({
                        ...fieldErrors,
                        general: fieldErrors.general || stepMsg,
                        digital_file: fieldErrors.digital_file || stepMsg,
                    });
                    activateSaveErrorLock();
                    toast.error(stepMsg, { title: 'Upload PDF' });
                    await loadBooks();
                    return;
                }
            }

            toast.success(toastShort.ok);
            clearSaveErrorLock();
            showModal.value = false;
            clearCreateCoverFile();
            clearCreateDigitalFile();
            clearEditExistingMedia();
            await loadBooks();
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('[saveBook]', e);
            const fieldErrors = applySaveStepApiErrors(e);
            const stepMsg = formatSaveStepError('Bước 1 — Lưu thông tin', e);
            setBookClientErrors({
                ...fieldErrors,
                general: fieldErrors.general || stepMsg,
            });
            activateSaveErrorLock();
            toast.error(stepMsg, { title: 'Lưu' });
        } finally {
            saveBookLoading.value = false;
        }
    };

    const openDeleteOne = (book) => {
        selectedBook.value = book;
        showDeleteConfirm.value = true;
    };

    const openDeleteMultiple = () => {
        if (!hasSelection.value) return;
        selectedBook.value = null;
        showDeleteConfirm.value = true;
    };

    const confirmDelete = async () => {
        if (deleteLoading.value) return;
        deleteLoading.value = true;
        try {
            if (selectedBook.value?.id) {
                await booksApi.remove(selectedBook.value.id);
                toast.success('Đã đưa sách vào thùng rác.', { title: 'Xóa' });
            } else if (hasSelection.value) {
                const ids = Array.from(selectedIds.value);
                await Promise.all(ids.map((id) => booksApi.remove(id)));
                deselectAll();
                toast.success(`Đã đưa ${ids.length} sách vào thùng rác.`, { title: 'Xóa' });
            } else {
                showDeleteConfirm.value = false;
                selectedBook.value = null;
                return;
            }

            showDeleteConfirm.value = false;
            selectedBook.value = null;
            await loadBooks();
            if (showTrashDrawer.value) {
                await fetchTrash();
            }
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Lỗi khi xóa sách:', e);
            const status = e?.response?.status;
            if (status === 404) {
                toast.info('Sách không tồn tại hoặc đã bị xóa trước đó.', { title: 'Xóa sách' });
                await loadBooks();
                if (showTrashDrawer.value) {
                    await fetchTrash();
                }
            } else {
                const err = e?.response?.data || {};
                const msg = err?.message || err?.error || 'Không thể xóa sách. Vui lòng thử lại.';
                toast.error(msg, { title: 'Xóa sách' });
            }
        } finally {
            deleteLoading.value = false;
        }
    };

    const exportExcel = async () => {
        try {
            const params = {};
            if (selectedIds.value.size > 0) {
                params.ids = Array.from(selectedIds.value);
            } else if (filteredBooks.value.length > 0) {
                params.ids = filteredBooks.value.map((b) => b.id);
            }
            if (pageKind.value === 'digital') {
                params.resource_type = 'digital';
            }
            const response = await booksApi.export(params);
            const blob = new Blob([response.data], {
                type:
                    response.headers['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = pageKind.value === 'digital' ? 'Danh_sach_do_an_luan_van.xlsx' : 'Danh_sach_sach_in.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            toast.success('Đã xuất Excel.', { title: 'Xuất Excel' });
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error(e);
            toast.error('Không thể xuất Excel. Vui lòng thử lại sau.', { title: 'Xuất Excel' });
        }
    };

    const openImportModal = () => {
        showImportModal.value = true;
    };

    const downloadBooksTemplate = async () => {
        try {
            const response = await booksApi.downloadImportTemplate();
            const blob = new Blob([response.data], {
                type:
                    response.headers['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'Mau_nhap_sach.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            toast.success('Đã tải file mẫu.', { title: 'File mẫu' });
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error(e);
            toast.error('Không thể tải file mẫu. Vui lòng thử lại sau.', { title: 'File mẫu' });
        }
    };

    const importBooksExcel = async (file) => {
        if (!file) return;
        importLoading.value = true;
        try {
            const formData = new FormData();
            formData.append('file', file);
            const res = await booksApi.import(formData);
            await loadBooks();
            const payload = res?.data ?? res;
            const errors = payload?.errors || [];
            if (Array.isArray(errors) && errors.length > 0) {
                const preview = errors
                    .slice(0, 3)
                    .map((item) => {
                        const row = item?.row ? `Dòng ${item.row}: ` : '';
                        return `${row}${item?.message || 'Dữ liệu không hợp lệ.'}`;
                    })
                    .join(' | ');
                const suffix = errors.length > 3 ? ` (còn ${errors.length - 3} lỗi khác)` : '';
                toast.error(`${preview}${suffix}`, { title: `Import thất bại (${errors.length} lỗi)` });
            } else {
                toast.success(toastShort.ok);
            }
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error(e);
            const err = e?.response?.data || {};
            const apiErrors = Array.isArray(err?.errors) ? err.errors : [];
            if (apiErrors.length > 0) {
                const preview = apiErrors
                    .slice(0, 3)
                    .map((item) => {
                        const row = item?.row ? `Dòng ${item.row}: ` : '';
                        return `${row}${item?.message || 'Dữ liệu không hợp lệ.'}`;
                    })
                    .join(' | ');
                const suffix = apiErrors.length > 3 ? ` (còn ${apiErrors.length - 3} lỗi khác)` : '';
                toast.error(`${preview}${suffix}`, { title: `Import thất bại (${apiErrors.length} lỗi)` });
            } else {
                toast.error(err?.message || toastShort.fail);
            }
        } finally {
            importLoading.value = false;
        }
    };

    const fetchTrash = async () => {
        try {
            const payload = await booksApi.trash();
            const data = payload?.data ?? payload;
            trashedBooks.value = Array.isArray(data) ? data : (data?.data ?? []);
        } catch (e) {
            trashedBooks.value = [];
            // eslint-disable-next-line no-console
            console.error('Lỗi khi tải thùng rác sách:', e);
        }
    };

    watch(showTrashDrawer, (open) => {
        if (open) fetchTrash();
    });

    const restoreBook = async (id) => {
        try {
            await booksApi.restore(id);
            await loadBooks();
            await fetchTrash();
            toast.success('Đã khôi phục.', { title: 'Thùng rác' });
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Lỗi khi khôi phục sách:', e);
            toast.error('Không thể khôi phục. Vui lòng thử lại.', { title: 'Thùng rác' });
        }
    };

    const restoreManyBooks = async (ids) => {
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!confirm(`Khôi phục ${ids.length} mục?`)) return;
        try {
            if (typeof booksApi.restoreMany === 'function') {
                await booksApi.restoreMany(ids);
            } else {
                await Promise.all(ids.map((id) => booksApi.restore(id)));
            }
            await loadBooks();
            await fetchTrash();
            toast.success(`Đã khôi phục ${ids.length} mục.`, { title: 'Thùng rác' });
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Lỗi khi khôi phục nhiều sách:', e);
            toast.error('Không thể khôi phục các mục đã chọn.', { title: 'Thùng rác' });
        }
    };

    const forceDeleteBook = async (id) => {
        if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
        try {
            await booksApi.forceDelete(id);
            trashedBooks.value = (trashedBooks.value || []).filter((b) => b.id !== id);
            await loadBooks();
            await fetchTrash();
            toast.success('Đã xóa vĩnh viễn.', { title: 'Thùng rác' });
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Lỗi khi xóa vĩnh viễn sách:', e);
            toast.error('Không thể xóa vĩnh viễn. Vui lòng thử lại.', { title: 'Thùng rác' });
        }
    };

    const forceDeleteManyBooks = async (ids) => {
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!confirm(`Xóa vĩnh viễn ${ids.length} mục? Không thể khôi phục.`)) return;
        try {
            if (typeof booksApi.forceDeleteMany === 'function') {
                await booksApi.forceDeleteMany(ids);
            } else {
                await Promise.all(ids.map((id) => booksApi.forceDelete(id)));
            }
            trashedBooks.value = (trashedBooks.value || []).filter((b) => !ids.includes(b.id));
            await loadBooks();
            await fetchTrash();
            toast.success(`Đã xóa vĩnh viễn ${ids.length} mục.`, { title: 'Thùng rác' });
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Lỗi khi xóa vĩnh viễn nhiều sách:', e);
            toast.error('Không thể xóa vĩnh viễn các mục đã chọn.', { title: 'Thùng rác' });
        }
    };

    const openCoverModal = (book = null) => {
        if (book) {
            coverBulkMode.value = false;
            coverTargetBookId.value = book.id;
        } else {
            const ids = Array.from(selectedIds.value);
            coverBulkMode.value = ids.length !== 1;
            coverTargetBookId.value = ids.length === 1 ? ids[0] : null;
        }
        showCoverModal.value = true;
    };

    function markBookCodeTouched() {
        bookCodeTouched.value = true;
    }

    function markRegistrationTouched() {
        registrationTouched.value = true;
    }

    watch(
        () => [
            showModal.value,
            previewWarehouseId.value,
            normalizeResourceTypeInput(form.value.resource_type),
        ],
        ([open, warehouseId]) => {
            if (!open) {
                clearCreateCoverFile();
                identifiersRequestSerial++;
                storageSuggestionRequestSerial++;
                if (identifiersPreviewDebounce) clearTimeout(identifiersPreviewDebounce);
                if (storageSuggestionDebounce) clearTimeout(storageSuggestionDebounce);
                storageSuggestionLoading.value = false;
                return;
            }

            if (identifiersPreviewDebounce) clearTimeout(identifiersPreviewDebounce);
            identifiersPreviewDebounce = setTimeout(() => {
                if (!warehouseId) return;
                previewIdentifiers();
            }, 120);

            if (storageSuggestionDebounce) clearTimeout(storageSuggestionDebounce);
            storageSuggestionDebounce = setTimeout(() => {
                loadStorageSuggestions();
            }, 120);
        }
    );

    watch(
        () => form.value.resource_type,
        async () => {
            if (!showModal.value || isEditing.value) return;
            await Promise.allSettled([loadWarehouses()]);
            await applyWarehouseSuggestionByResourceType();
        }
    );

    watch(
        () => form.value.warehouse,
        () => {
            if (!showModal.value || isEditing.value) return;
            if (settingWarehouseSuggestion.value) return;
            warehouseTouched.value = true;
        }
    );

    const closeCoverModal = () => {
        showCoverModal.value = false;
        coverTargetBookId.value = null;
        coverBulkMode.value = false;
    };

    const uploadCover = async (file) => {
        if (!file) return;
        coverUploadLoading.value = true;
        try {
            const formData = new FormData();
            if (coverBulkMode.value) {
                formData.append('file', file);
                const idList = Array.from(selectedIds.value);
                if (idList.length > 0) {
                    formData.append('ids', JSON.stringify(idList));
                }
                const body = await booksApi.bulkUpdateCover(formData);
                const summary = body?.data ?? {};
                const updated = Number(summary.updated ?? 0);
                const skipped = Number(summary.skipped ?? 0);
                const selectedCount = summary.selected_count != null ? Number(summary.selected_count) : 0;
                const selectedMissing = summary.selected_missing != null ? Number(summary.selected_missing) : 0;
                const hadSelectionFilter = summary.selected_count != null;
                await loadBooks();
                if (updated > 0) {
                    if (hadSelectionFilter && selectedMissing > 0) {
                        toast.warn(
                            `Cập nhật ${updated}/${selectedCount} — thiếu ${selectedMissing} ảnh trong zip.` +
                                (skipped > 0 ? ` (+${skipped} file bỏ qua)` : ''),
                            { title: 'Ảnh bìa' },
                        );
                    } else if (hadSelectionFilter && selectedMissing === 0 && selectedCount > 0) {
                        toast.success(`Đủ ${updated}/${selectedCount} sách đã chọn.`, { title: 'Ảnh bìa' });
                    } else if (skipped > 0) {
                        toast.success(`${updated} ảnh · ${skipped} file bỏ qua`, { title: 'Ảnh bìa' });
                    } else {
                        toast.success(`${updated} ảnh bìa`, { title: 'Ảnh bìa' });
                    }
                } else {
                    const picked = idList.length > 0;
                    toast.warn(
                        skipped > 0
                            ? picked
                                ? `0 ảnh — không khớp mã với ${skipped} file.`
                                : `0 ảnh — ${skipped} file không khớp mã.`
                            : 'Zip trống hoặc không đọc được.',
                        { title: 'Ảnh bìa' },
                    );
                }
            } else {
                const ids = Array.from(selectedIds.value);
                const bookId = coverTargetBookId.value ?? ids[0];
                if (!bookId) {
                    toast.info('Vui lòng chọn đúng 1 sách để cập nhật ảnh bìa.', { title: 'Ảnh bìa' });
                    coverUploadLoading.value = false;
                    return;
                }
                formData.append('book_cover', file);
                await booksApi.updateCover(bookId, formData);
                await loadBooks();
                toast.success('Cập nhật ảnh bìa sách thành công.', { title: 'Ảnh bìa' });
            }
            closeCoverModal();
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Lỗi khi cập nhật ảnh bìa:', e);
            const res = e?.response?.data || {};
            const message =
                res.message ||
                res.messages ||
                res.error ||
                'Cập nhật ảnh bìa không thành công. Vui lòng kiểm tra lại file.';
            toast.error(message, { title: 'Ảnh bìa' });
        } finally {
            coverUploadLoading.value = false;
        }
    };

    return {
        pageKind,
        pageLabel,
        books,
        booksPagination,
        goBooksPage,
        searchBooks,
        matrixClassificationId,
        setMatrixFilter,
        clearMatrixFilter,
        warehouses,
        saveBookLoading,
        saveBlockedByError,
        loading,
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
        loadBooks,
        openAddModal,
        openEditModal,
        saveBook,
        requestCloseBookModal,
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
    };
}
