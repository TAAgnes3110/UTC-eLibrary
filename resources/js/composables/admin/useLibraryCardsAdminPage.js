import { ref, computed, onBeforeUnmount, onMounted, watch, reactive } from 'vue';
import apiClient from '@/api/axios';
import { libraryCardsApi } from '@/api/libraryCards';
import { toast } from '@/store/toast';
import { useApiFieldErrors } from '@/composables/useApiFieldErrors';
import { LIBRARY_CARD_SEARCH_IN_OPTIONS } from '@/config/libraryCardUi';
import { extractApiPaginator } from '@/utils/adminPagination';

const FIELD_MAP = {
    full_name: 'full_name',
    email: 'email',
    phone: 'phone',
    address: 'address',
    date_of_birth: 'date_of_birth',
    status: 'status',
    workflow_status: 'workflow_status',
    holder_type: 'holder_type',
    faculty_id: 'faculty_id',
    period_id: 'period_id',
    class_code: 'class_code',
    external_organization: 'external_organization',
    notes: 'notes',
};

/**
 * @param {object} props — faculties, periods từ Inertia
 * @param {{ screen?: 'manage'|'requests' }} options
 */
export function useLibraryCardsAdminPage(props, options = {}) {
    const screen = options.screen ?? 'manage';

    const cards = ref([]);
    const loadingFallback = ref(false);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });
    const pageNum = ref(1);

    const defaultSearchIn = () => ({
        card_number: true,
        code: true,
        full_name: true,
        email: true,
        phone: true,
    });

    const filterValues = ref({
        searchKeyword: '',
        searchIn: defaultSearchIn(),
        holderType: '',
        sortBy: 'newest',
        ...(screen === 'manage' ? { status: '' } : {}),
    });

    const showFilterPanel = ref(false);
    let cardsReloadDebounce = null;
    let cardsRequestSerial = 0;

    const scheduleCardsReload = ({ resetPage = false, delayMs = 180 } = {}) => {
        if (resetPage) {
            pageNum.value = 1;
        }
        if (cardsReloadDebounce) clearTimeout(cardsReloadDebounce);
        cardsReloadDebounce = setTimeout(() => {
            loadCards();
        }, delayMs);
    };

    function buildSearchInQueryParam() {
        const sin = filterValues.value.searchIn || {};
        const keys = LIBRARY_CARD_SEARCH_IN_OPTIONS.map((o) => o.key);
        const active = keys.filter((k) => sin[k]);
        if (active.length === 0 || active.length === keys.length) {
            return undefined;
        }
        return active.join(',');
    }

    const facultiesList = computed(() => props.faculties ?? []);
    const periodsList = computed(() => props.periods ?? []);

    const loadCards = async () => {
        const requestSerial = ++cardsRequestSerial;
        loadingFallback.value = true;
        try {
            const params = {
                per_page: 50,
                page: pageNum.value,
                keyword: filterValues.value.searchKeyword?.trim() || undefined,
            };
            const fv = filterValues.value;
            const searchIn = buildSearchInQueryParam();
            if (screen === 'manage') {
                params.management = 1;
                if (fv.holderType) {
                    params.holder_type = fv.holderType;
                }
                if (fv.status !== '' && fv.status != null) {
                    params.status = fv.status;
                }
                if (fv.sortBy) {
                    params.sort_by = fv.sortBy;
                }
                if (searchIn) {
                    params.search_in = searchIn;
                }
            } else if (screen === 'requests') {
                params.workflow_status = 'pending_review';
                if (fv.holderType) {
                    params.holder_type = fv.holderType;
                }
                if (fv.sortBy) {
                    params.sort_by = fv.sortBy;
                }
                if (searchIn) {
                    params.search_in = searchIn;
                }
            }
            const response = await apiClient.get('/library-cards', {
                params,
            });
            const payload = response?.data;
            const { items, meta: m } = extractApiPaginator(payload, 50);
            if (requestSerial !== cardsRequestSerial) return;
            cards.value = items;
            meta.value = {
                current_page: m.current_page,
                last_page: m.last_page,
                total: m.total,
            };
            pageNum.value = m.current_page;
        } catch (e) {
            if (requestSerial !== cardsRequestSerial) return;
            console.error('loadCards', e);
            cards.value = [];
            toast.error('Không tải được danh sách thẻ.', { title: 'Lỗi' });
        } finally {
            if (requestSerial === cardsRequestSerial) {
                loadingFallback.value = false;
            }
        }
    };

    function searchCards() {
        scheduleCardsReload({ resetPage: true, delayMs: 0 });
    }

    onMounted(() => {
        loadCards();
    });

    const reloadListFilters = () => {
        scheduleCardsReload({ resetPage: true, delayMs: 150 });
    };
    watch(() => filterValues.value.holderType, reloadListFilters);
    watch(() => filterValues.value.searchIn, reloadListFilters, { deep: true });
    watch(() => filterValues.value.sortBy, reloadListFilters);
    if (screen === 'manage') {
        watch(() => filterValues.value.status, reloadListFilters);
    }
    watch(
        () => filterValues.value.searchKeyword,
        () => {
            scheduleCardsReload({ resetPage: true, delayMs: 350 });
        }
    );

    onBeforeUnmount(() => {
        if (cardsReloadDebounce) clearTimeout(cardsReloadDebounce);
        cardsRequestSerial++;
    });

    const selectedIds = ref([]);
    const hasSelection = computed(() => selectedIds.value.length > 0);
    const isAllSelected = computed(
        () => cards.value.length > 0 && selectedIds.value.length === cards.value.length
    );

    function toggleSelect(id) {
        const i = selectedIds.value.indexOf(id);
        if (i === -1) selectedIds.value = [...selectedIds.value, id];
        else selectedIds.value = selectedIds.value.filter((x) => x !== id);
    }

    function toggleSelectAll() {
        if (isAllSelected.value) selectedIds.value = [];
        else selectedIds.value = cards.value.map((c) => c.id);
    }

    function deselectAll() {
        selectedIds.value = [];
    }

    const showModal = ref(false);
    const saveLoading = ref(false);
    const form = ref({
        id: null,
        full_name: '',
        email: '',
        phone: '',
        address: '',
        date_of_birth: '',
        holder_type: 'student',
        status: 1,
        workflow_status: 'active',
        faculty_id: null,
        period_id: null,
        class_code: '',
        external_organization: '',
        notes: '',
    });

    const {
        fieldErrors: formErrors,
        clearField: clearFormFieldError,
        applyAxios422: applyFormErrors,
        clearAll: clearFormErrors,
    } = useApiFieldErrors(FIELD_MAP);

    function openEditModal(row) {
        clearFormErrors();
        form.value = {
            id: row.id,
            full_name: row.full_name ?? '',
            email: row.email ?? '',
            phone: row.phone ?? '',
            address: row.address ?? '',
            date_of_birth: row.date_of_birth ? String(row.date_of_birth).slice(0, 10) : '',
            holder_type: row.holder_type ?? 'student',
            status: Number(row.status) || 1,
            workflow_status: row.workflow_status ?? 'active',
            faculty_id: row.faculty_id ?? null,
            period_id: row.period_id ?? null,
            class_code: row.class_code ?? '',
            external_organization: row.external_organization ?? '',
            notes: row.notes ?? '',
        };
        showModal.value = true;
    }

    async function saveCard() {
        if (!form.value.id) return;
        saveLoading.value = true;
        clearFormErrors();
        try {
            const payload = {
                full_name: form.value.full_name,
                email: form.value.email,
                phone: form.value.phone,
                address: form.value.address,
                date_of_birth: form.value.date_of_birth || null,
                holder_type: form.value.holder_type,
                status: form.value.status,
                workflow_status: form.value.workflow_status,
                faculty_id: form.value.faculty_id,
                period_id: form.value.period_id,
                class_code: form.value.class_code || null,
                external_organization: form.value.external_organization?.trim() || null,
                notes: form.value.notes || null,
            };
            await libraryCardsApi.update(form.value.id, payload);
            toast.success('Đã cập nhật thẻ.', { title: 'Thành công' });
            showModal.value = false;
            await loadCards();
        } catch (e) {
            if (e?.response?.status === 422) {
                applyFormErrors(e?.response?.data);
            } else {
                toast.error(e?.response?.data?.messages || 'Không lưu được.', { title: 'Lỗi' });
            }
        } finally {
            saveLoading.value = false;
        }
    }

    const showDeleteModal = ref(false);
    const cardToDelete = ref(null);

    function openDeleteOne(row) {
        cardToDelete.value = row;
        showDeleteModal.value = true;
    }

    function openDeleteMultiple() {
        if (!selectedIds.value.length) return;
        cardToDelete.value = null;
        showDeleteModal.value = true;
    }

    async function confirmDelete() {
        try {
            if (cardToDelete.value) {
                await libraryCardsApi.remove(cardToDelete.value.id);
                toast.success('Đã xóa mềm thẻ.', { title: 'Thành công' });
            } else {
                await Promise.all(selectedIds.value.map((id) => libraryCardsApi.remove(id)));
                toast.success(`Đã xóa ${selectedIds.value.length} thẻ.`, { title: 'Thành công' });
            }
            showDeleteModal.value = false;
            cardToDelete.value = null;
            deselectAll();
            await loadCards();
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không xóa được.', { title: 'Lỗi' });
        }
    }

    const showTrashDrawer = ref(false);
    const trashedCards = ref([]);
    const loadingTrash = ref(false);

    async function openTrashDrawer() {
        showTrashDrawer.value = true;
        loadingTrash.value = true;
        try {
            const payload = await libraryCardsApi.trash({ per_page: 100 });
            const { items } = extractApiPaginator(payload, 100);
            trashedCards.value = items;
        } catch (e) {
            trashedCards.value = [];
        } finally {
            loadingTrash.value = false;
        }
    }

    async function restoreCard(row) {
        try {
            await libraryCardsApi.restore(row.id);
            toast.success('Đã khôi phục.', { title: 'Thành công' });
            await openTrashDrawer();
            await loadCards();
        } catch (e) {
            toast.error('Không khôi phục được.', { title: 'Lỗi' });
        }
    }

    async function restoreManyCards(ids) {
        try {
            await libraryCardsApi.restoreMany(ids);
            toast.success('Đã khôi phục.', { title: 'Thành công' });
            await openTrashDrawer();
            await loadCards();
        } catch (e) {
            toast.error('Không khôi phục được.', { title: 'Lỗi' });
        }
    }

    async function forceDeleteCard(row) {
        try {
            await libraryCardsApi.forceDelete(row.id);
            toast.success('Đã xóa vĩnh viễn.', { title: 'Thành công' });
            await openTrashDrawer();
            await loadCards();
        } catch (e) {
            toast.error('Không xóa được.', { title: 'Lỗi' });
        }
    }

    async function forceDeleteManyCards(ids) {
        try {
            await libraryCardsApi.forceDeleteMany(ids);
            toast.success('Đã xóa vĩnh viễn.', { title: 'Thành công' });
            await openTrashDrawer();
            await loadCards();
        } catch (e) {
            toast.error('Không xóa được.', { title: 'Lỗi' });
        }
    }

    const showPhotoModal = ref(false);
    const photoTarget = ref(null);
    const photoBulkMode = ref(false);
    const photoUploadLoading = ref(false);

    function openPhotoModal(row) {
        photoTarget.value = row;
        photoBulkMode.value = false;
        showPhotoModal.value = true;
    }

    function openPhotoBulkModal() {
        photoTarget.value = null;
        photoBulkMode.value = true;
        showPhotoModal.value = true;
    }

    function closePhotoModal() {
        showPhotoModal.value = false;
    }

    async function uploadPhoto(file) {
        photoUploadLoading.value = true;
        try {
            if (photoBulkMode.value) {
                toast.info('Cập nhật ảnh hàng loạt theo thẻ cần API riêng (zip). Tạm thời chỉ cập nhật từng thẻ.', {
                    title: 'Thông báo',
                });
                closePhotoModal();
                return;
            }
            if (!photoTarget.value || !file) return;
            const fd = new FormData();
            fd.append('photo', file);
            await libraryCardsApi.updatePhoto(photoTarget.value.id, fd);
            toast.success('Đã cập nhật ảnh thẻ.', { title: 'Thành công' });
            closePhotoModal();
            await loadCards();
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không tải ảnh lên được.', { title: 'Lỗi' });
        } finally {
            photoUploadLoading.value = false;
        }
    }

    const showLockModal = ref(false);
    const cardToLock = ref(null);

    function openLockModal(row) {
        cardToLock.value = row;
        showLockModal.value = true;
    }

    function closeLockModal() {
        showLockModal.value = false;
        cardToLock.value = null;
    }

    const isLockAction = computed(() => {
        const c = cardToLock.value;
        if (!c) return true;
        return Number(c.status) !== 3;
    });

    async function confirmLockStatus() {
        const c = cardToLock.value;
        if (!c) return;
        try {
            const nextStatus = Number(c.status) === 3 ? 1 : 3;
            const payload = {
                status: nextStatus,
                holder_type: c.holder_type,
            };
            if (c.holder_type === 'student' || c.holder_type === 'teacher') {
                payload.faculty_id = c.faculty_id;
            }
            if (c.holder_type === 'student') {
                payload.period_id = c.period_id;
                payload.class_code = c.class_code;
            }
            await libraryCardsApi.update(c.id, payload);
            toast.success(nextStatus === 3 ? 'Đã khóa thẻ.' : 'Đã mở khóa thẻ.', { title: 'Thành công' });
            closeLockModal();
            await loadCards();
        } catch (e) {
            toast.error(e?.response?.data?.messages || 'Không cập nhật được trạng thái.', { title: 'Lỗi' });
        }
    }

    function pendingReviewRowsFromSelection() {
        const ids = new Set(selectedIds.value);
        return cards.value.filter(
            (r) => ids.has(r.id) && r.workflow_status === 'pending_review'
        );
    }

    async function onApprove(row) {
        try {
            await libraryCardsApi.approveReview(row.id);
            toast.success('Đã duyệt và kích hoạt thẻ.', { title: 'Thành công' });
            await loadCards();
        } catch (e) {
            const msg = e?.response?.data?.messages || e?.response?.data?.message || 'Không duyệt được.';
            toast.error(msg, { title: 'Lỗi' });
        }
    }

    async function onApproveSelected() {
        if (screen !== 'requests') {
            return;
        }
        const rows = pendingReviewRowsFromSelection();
        if (rows.length === 0) {
            toast.warn('Chọn ít nhất một hồ sơ đang « Chờ duyệt ».', { title: 'Thông báo' });
            return;
        }
        if (typeof window === 'undefined') {
            return;
        }
        if (!window.confirm(`Đồng ý và kích hoạt ${rows.length} thẻ đã chọn?`)) {
            return;
        }
        try {
            const results = await Promise.allSettled(
                rows.map((row) => libraryCardsApi.approveReview(row.id))
            );
            const ok = results.filter((r) => r.status === 'fulfilled').length;
            const fail = results.length - ok;
            selectedIds.value = [];
            await loadCards();
            if (fail === 0) {
                toast.success(`Đã duyệt và kích hoạt ${ok} thẻ.`, { title: 'Thành công' });
            } else {
                toast.warn(`Thành công ${ok}, lỗi ${fail}.`, { title: 'Hoàn tất một phần' });
            }
        } catch (e) {
            toast.error('Không xử lý hàng loạt được.', { title: 'Lỗi' });
        }
    }

    async function onRejectSelected() {
        if (screen !== 'requests') {
            return;
        }
        const rows = pendingReviewRowsFromSelection();
        if (rows.length === 0) {
            toast.warn('Chọn ít nhất một hồ sơ đang « Chờ duyệt ».', { title: 'Thông báo' });
            return;
        }
        if (typeof window === 'undefined') {
            return;
        }
        if (!window.confirm(`Từ chối ${rows.length} hồ sơ đã chọn?`)) {
            return;
        }
        const input = window.prompt('Lý do từ chối (tuỳ chọn, áp dụng cho các hồ sơ đã chọn):');
        if (input === null) {
            return;
        }
        const notes = String(input).trim();
        const body = notes ? { notes } : {};
        try {
            const results = await Promise.allSettled(
                rows.map((row) => libraryCardsApi.rejectReview(row.id, body))
            );
            const ok = results.filter((r) => r.status === 'fulfilled').length;
            const fail = results.length - ok;
            selectedIds.value = [];
            await loadCards();
            if (fail === 0) {
                toast.success(`Đã từ chối ${ok} hồ sơ.`, { title: 'Thành công' });
            } else {
                toast.warn(`Thành công ${ok}, lỗi ${fail}.`, { title: 'Hoàn tất một phần' });
            }
        } catch (e) {
            toast.error('Không xử lý hàng loạt được.', { title: 'Lỗi' });
        }
    }

    async function onReject(row) {
        if (typeof window === 'undefined') {
            return;
        }
        if (!window.confirm('Từ chối hồ sơ này?')) {
            return;
        }
        const input = window.prompt('Lý do từ chối (tuỳ chọn):');
        if (input === null) {
            return;
        }
        const notes = String(input).trim();
        try {
            await libraryCardsApi.rejectReview(row.id, notes ? { notes } : {});
            toast.success('Đã từ chối hồ sơ.', { title: 'Thành công' });
            await loadCards();
        } catch (e) {
            const msg = e?.response?.data?.messages || e?.response?.data?.message || 'Không từ chối được.';
            toast.error(msg, { title: 'Lỗi' });
        }
    }

    async function exportExcel() {
        try {
            const params = {};
            if (selectedIds.value.length > 0) {
                params.ids = selectedIds.value;
            }
            const response = await libraryCardsApi.export(params);
            const blob = new Blob([response.data], {
                type:
                    response.headers['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'DanhSachTheThuVien.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            toast.success('Đã xuất Excel.', { title: 'Xuất Excel' });
        } catch (e) {
            toast.error('Không xuất được Excel.', { title: 'Lỗi' });
        }
    }

    function goPage(p) {
        pageNum.value = p;
        loadCards();
    }

    return reactive({
        cards,
        loadingFallback,
        meta,
        pageNum,
        filterValues,
        showFilterPanel,
        LIBRARY_CARD_SEARCH_IN_OPTIONS,
        loadCards,
        searchCards,
        selectedIds,
        hasSelection,
        isAllSelected,
        toggleSelect,
        toggleSelectAll,
        deselectAll,
        showModal,
        form,
        formErrors,
        clearFormFieldError,
        openEditModal,
        saveCard,
        saveLoading,
        showDeleteModal,
        openDeleteOne,
        openDeleteMultiple,
        confirmDelete,
        cardToDelete,
        showTrashDrawer,
        trashedCards,
        loadingTrash,
        openTrashDrawer,
        restoreCard,
        restoreManyCards,
        forceDeleteCard,
        forceDeleteManyCards,
        showPhotoModal,
        photoBulkMode,
        photoUploadLoading,
        openPhotoModal,
        openPhotoBulkModal,
        closePhotoModal,
        uploadPhoto,
        showLockModal,
        cardToLock,
        isLockAction,
        openLockModal,
        closeLockModal,
        confirmLockStatus,
        onApprove,
        onApproveSelected,
        onReject,
        onRejectSelected,
        exportExcel,
        goPage,
        facultiesList,
        periodsList,
        screen,
    });
}
