import { ref, computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import apiClient from '@/api/axios';
import { booksApi } from '@/api/books';
import { warehousesApi } from '@/api/warehouses';
import { toast } from '@/store/toast';
import { BOOK_FORM_FIELD_MAP } from '@/utils/laravelApiError';
import { useApiFieldErrors } from '@/composables/useApiFieldErrors';
import { toastShort, bookFormClientError } from '@/constants/adminUiMessages';

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

export const SEARCH_IN_OPTIONS = [
    { key: 'code', label: 'Mã sách' },
    { key: 'title', label: 'Tên sách' },
    { key: 'author', label: 'Tác giả' },
    { key: 'publisher', label: 'Nhà xuất bản' },
    { key: 'place', label: 'Nơi xuất bản' },
    { key: 'year', label: 'Năm xuất bản' },
    { key: 'classification', label: 'Phân loại' },
];

export function useBooksAdminPage() {
    const page = usePage();
    const pageKind = computed(() => page.props.pageKind ?? 'print');
    const resourceKindFilter = computed(() => page.props.resourceKindFilter ?? '');
    const pageLabel = computed(() => (pageKind.value === 'digital' ? 'Tài liệu số' : 'Sách in'));

    const books = ref([]);
    const warehouses = ref([]);
    const saveBookLoading = ref(false);

    const classifications = ref([]);
    const classificationDetails = ref([]);
    const classificationDetailsLoaded = ref(false);
    let booksSearchDebounce = null;
    const selectedClassificationId = ref('');
    const loading = ref(false);

    const trashedBooks = ref([]);

    const filterValues = ref({
        searchKeyword: '',
        status: '',
        priceSort: '',
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

    const {
        fieldErrors: bookFormErrors,
        clearField: clearBookFieldError,
        clearAll: clearBookFormErrors,
        applyAxios422: applyBookApiErrors,
        setClientErrors: setBookClientErrors,
    } = useApiFieldErrors(BOOK_FORM_FIELD_MAP);

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
                list = list.filter((b) => (b.quantity ?? 0) > 0);
            } else if (filterValues.value.status === 'out_of_stock') {
                list = list.filter((b) => (b.quantity ?? 0) <= 0);
            }
        }
        if (selectedClassificationId.value) {
            list = list.filter(
                (b) =>
                    String(b.classification_id) === String(selectedClassificationId.value) ||
                    String(b.classification?.id ?? '') === String(selectedClassificationId.value),
            );
        }
        if (filterValues.value.priceSort) {
            const dir = filterValues.value.priceSort === 'asc' ? 1 : -1;
            list = [...list].sort((a, b) => {
                const pa = Number(a.price ?? 0);
                const pb = Number(b.price ?? 0);
                if (Number.isNaN(pa) && Number.isNaN(pb)) return 0;
                if (Number.isNaN(pa)) return 1;
                if (Number.isNaN(pb)) return -1;
                if (pa === pb) return 0;
                return pa < pb ? -dir : dir;
            });
        }
        return list;
    });

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
        loading.value = true;
        try {
            const response = await apiClient.get('/books', {
                params: {
                    per_page: 50,
                    keyword: filterValues.value.searchKeyword || undefined,
                    ...(resourceKindFilter.value ? { resource_kind: resourceKindFilter.value } : {}),
                },
            });
            const payload = response?.data;
            const paginator = payload?.data;
            const items = Array.isArray(paginator?.data)
                ? paginator.data
                : Array.isArray(paginator)
                  ? paginator
                  : [];
            books.value = items;
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to load books', e);
            books.value = [];
        } finally {
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

    const loadClassificationDetails = async () => {
        if (classificationDetailsLoaded.value) return;
        try {
            const response = await apiClient.get('/classification-details', {
                params: {
                    per_page: 500,
                },
            });
            const payload = response?.data;
            const paginator = payload?.data;
            const items = Array.isArray(paginator?.data)
                ? paginator.data
                : Array.isArray(paginator)
                  ? paginator
                  : [];
            classificationDetails.value = items;
            classificationDetailsLoaded.value = true;
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to load classification details', e);
            classificationDetails.value = [];
            classificationDetailsLoaded.value = false;
        }
    };

    onMounted(async () => {
        await loadClassifications();
    });

    watch(
        () => page.props.resourceKindFilter ?? '',
        () => {
            loadBooks();
        },
        { immediate: true },
    );

    watch(
        () => filterValues.value.searchKeyword,
        () => {
            if (booksSearchDebounce) clearTimeout(booksSearchDebounce);
            booksSearchDebounce = setTimeout(() => {
                loadBooks();
            }, 350);
        },
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
        authors: '',
        publisher: '',
        published_year: '',
        description: '',
        price: '',
        classification: '',
        classification_detail: '',
        warehouse: '',
        quantity: 1,
    });

    const form = ref(emptyForm());

    /**
     * Kiểm tra toàn bộ ô bắt buộc một lần — hiển thị đồng thời mọi lỗi (không dừng ở field đầu tiên).
     */
    async function collectBookClientErrors() {
        const errors = {};
        const title = String(form.value.title || '').trim();
        if (!title) errors.title = bookFormClientError.titleRequired;

        await loadWarehouses();
        let warehouseId = null;
        const wh = String(form.value.warehouse || '').trim();
        if (!wh) {
            errors.warehouse = bookFormClientError.warehouseEmpty;
        } else {
            warehouseId = matchLookupId(warehouses.value, form.value.warehouse);
            if (!warehouseId) {
                errors.warehouse = bookFormClientError.warehouseNoMatch;
            }
        }

        const cls = String(form.value.classification || '').trim();
        let classificationId = null;
        if (!cls) {
            errors.classification = bookFormClientError.classificationEmpty;
        } else {
            classificationId = matchLookupId(classifications.value, form.value.classification);
            if (!classificationId) {
                errors.classification = bookFormClientError.classificationNoMatch;
            }
        }

        const clsDet = String(form.value.classification_detail || '').trim();
        if (!clsDet) {
            errors.classification_detail = bookFormClientError.classificationDetailEmpty;
        } else {
            const pool =
                classificationId != null
                    ? classificationDetails.value.filter((d) => String(d.classification_id) === String(classificationId))
                    : classificationDetails.value;
            const detailId = matchLookupId(pool, form.value.classification_detail);
            if (!detailId) {
                errors.classification_detail = bookFormClientError.classificationDetailNoMatch;
            }
        }

        const qtyRaw = parseInt(String(form.value.quantity ?? 0), 10);
        if (Number.isNaN(qtyRaw) || qtyRaw < 0) {
            errors.quantity = bookFormClientError.quantityInvalid;
        }

        if (Object.keys(errors).length > 0) {
            return { ok: false, errors };
        }

        const classificationDetailId = (() => {
            const pool =
                classificationId != null
                    ? classificationDetails.value.filter((d) => String(d.classification_id) === String(classificationId))
                    : classificationDetails.value;
            return matchLookupId(pool, form.value.classification_detail);
        })();

        return {
            ok: true,
            title,
            warehouseId,
            classificationId,
            classificationDetailId,
            quantity: Math.max(0, qtyRaw),
        };
    }

    const openAddModal = async () => {
        await loadClassificationDetails();
        await loadWarehouses();
        isEditing.value = false;
        form.value = emptyForm();
        clearBookFormErrors();
        showModal.value = true;
    };

    const openEditModal = async (book) => {
        await loadClassificationDetails();
        await loadWarehouses();
        isEditing.value = true;
        clearBookFormErrors();
        form.value = {
            id: book.id ?? null,
            registration_number: book.registration_number || '',
            book_code: book.book_code || '',
            title: book.title || '',
            authors: book.authors_label || '',
            publisher: book.publishers_label || '',
            published_year: book.published_year || '',
            description: book.summary || '',
            price: book.price ?? '',
            classification: book.classification
                ? `${book.classification.code || ''} – ${book.classification.name || ''}`.trim()
                : '',
            classification_detail: book.classification_detail
                ? `${book.classification_detail.code || ''} – ${book.classification_detail.name || ''}`.trim()
                : '',
            warehouse: book.warehouse?.name || '',
            quantity: book.quantity ?? 1,
        };
        showModal.value = true;
    };

    const saveBook = async () => {
        if (saveBookLoading.value) return;
        clearBookFormErrors();
        const client = await collectBookClientErrors();
        if (!client.ok) {
            setBookClientErrors(client.errors);
            toast.error(toastShort.fail);
            return;
        }
        const { title, warehouseId, classificationId, classificationDetailId, quantity: qty } = client;
        const payload = {
            title,
            warehouse_id: warehouseId,
            quantity: qty,
            resource_kind: pageKind.value === 'digital' ? 'digital' : 'print',
        };
        const reg = String(form.value.registration_number || '').trim();
        if (reg) payload.registration_number = reg;
        const bookCode = String(form.value.book_code || '').trim();
        if (bookCode) payload.book_code = bookCode;
        const summary = String(form.value.description || '').trim();
        if (summary) payload.summary = summary;
        const py = parseInt(String(form.value.published_year || ''), 10);
        if (!Number.isNaN(py) && py >= 1900 && py <= 2100) payload.published_year = py;
        const priceNum = parseInt(String(form.value.price ?? ''), 10);
        if (!Number.isNaN(priceNum) && priceNum >= 0) payload.price = priceNum;
        if (classificationId) payload.classification_id = classificationId;
        if (classificationDetailId) payload.classification_detail_id = classificationDetailId;

        saveBookLoading.value = true;
        try {
            if (isEditing.value && form.value.id != null) {
                await booksApi.update(form.value.id, payload);
                toast.success(toastShort.ok);
            } else {
                await booksApi.create(payload);
                toast.success(toastShort.ok);
            }
            showModal.value = false;
            await loadBooks();
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error(e);
            applyBookApiErrors(e);
            toast.error(toastShort.fail);
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
            const response = await booksApi.export(params);
            const blob = new Blob([response.data], {
                type:
                    response.headers['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'Danh_sach_sach_in.xlsx';
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
                toast.error(toastShort.fail);
            } else {
                toast.success(toastShort.ok);
            }
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error(e);
            toast.error(toastShort.fail);
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
        warehouses,
        saveBookLoading,
        loading,
        classifications,
        classificationDetails,
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
    };
}
