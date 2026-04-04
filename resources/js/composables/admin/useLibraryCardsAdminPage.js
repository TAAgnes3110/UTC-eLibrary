import { ref, computed, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import apiClient from '@/api/axios';

function mapWorkflowStatusLabel(v) {
    switch (v) {
        case 'draft':
            return 'Nháp';
        case 'pending_payment':
            return 'Chờ thanh toán';
        case 'pending_review':
            return 'Chờ duyệt';
        case 'active':
            return 'Đang hoạt động';
        case 'rejected':
            return 'Từ chối';
        case 'cancelled':
            return 'Đã hủy';
        case 'expired':
            return 'Hết hạn';
        case 'revoked':
            return 'Bị thu hồi';
        default:
            return v || '—';
    }
}

/**
 * @param {{ skipInitialLoad?: boolean }} [options]
 */
export function useLibraryCardsAdminPage(options = {}) {
    const skipInitialLoad = options.skipInitialLoad === true;
    const page = usePage();
    const currentUserId = computed(() => page.props?.auth?.user?.id ?? null);

    const cards = ref([]);
    const loading = ref(false);
    const didMount = ref(false);

    const filterValues = ref({
        searchKeyword: '',
        workflow_status: '',
    });

    const showModal = ref(false);
    const selectedCard = ref(null);
    const saveLoading = ref(false);
    const fieldErrors = ref({});

    const filteredCards = computed(() => {
        const kw = String(filterValues.value.searchKeyword || '').trim().toLowerCase();
        if (!kw) return cards.value;
        return cards.value.filter((c) => {
            const haystack = [
                c.card_number,
                c.full_name,
                c.code,
                c.period?.name,
                c.period?.code,
                c.phone,
                c.email,
                c.address,
                c.workflow_status,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            return haystack.includes(kw);
        });
    });

    const loadCards = async () => {
        loading.value = true;
        fieldErrors.value = {};
        try {
            const response = await apiClient.get('/library-cards', {
                params: {
                    per_page: 30,
                    workflow_status: filterValues.value.workflow_status || undefined,
                    keyword: filterValues.value.searchKeyword || undefined,
                },
            });
            const payload = response?.data;
            const paginator = payload?.data;
            const items = Array.isArray(paginator?.data)
                ? paginator.data
                : Array.isArray(paginator)
                  ? paginator
                  : [];
            cards.value = items;
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to load library cards', e);
            cards.value = [];
        } finally {
            loading.value = false;
        }
    };

    onMounted(async () => {
        didMount.value = true;
        await loadCards();
    });

    watch(
        () => [filterValues.value.workflow_status, filterValues.value.searchKeyword],
        () => {
            if (!didMount.value) return;
            // debounce keyword reload
            const kw = String(filterValues.value.searchKeyword || '');
            const isKeyword = kw.trim().length > 0;
            const delay = isKeyword ? 350 : 0;
            window.clearTimeout(loadCards._t);
            loadCards._t = window.setTimeout(loadCards, delay);
        },
    );

    const openEditModal = (card) => {
        selectedCard.value = card ?? null;
        fieldErrors.value = {};
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        selectedCard.value = null;
        fieldErrors.value = {};
    };

    /**
     * @param {object} payload
     * @param {{ afterCreate?: 'close' | 'redirect' }} [saveOptions]
     */
    const saveCard = async (payload, saveOptions = {}) => {
        const afterCreate = saveOptions.afterCreate ?? 'close';
        if (!selectedCard.value?.id) {
            // UI-first: cho phép "Thêm cấp thẻ nhanh" khi chưa kết nối API create.
            const now = new Date();
            cards.value.unshift({
                id: `quick-${now.getTime()}`,
                card_number: `TMP-${now.getTime()}`,
                holder_type: payload?.holder_type ?? 'student',
                workflow_status: payload?.workflow_status || 'active',
                payment_status: payload?.payment_status || '',
                payment_amount: payload?.payment_amount ?? null,
                paid_at: payload?.paid_at || null,
                payment_method: payload?.payment_method || null,
                receipt_number: payload?.receipt_number || null,
                full_name: payload?.full_name || 'Thẻ mới tại quầy',
                code: payload?.code || null,
                period_id: payload?.period_id ?? null,
                email: payload?.email || null,
                phone: null,
                address: payload?.address || null,
                date_of_birth: payload?.date_of_birth || null,
                is_active: payload?.is_active ?? true,
                issue_date: payload?.issue_date || now.toISOString().slice(0, 10),
                expiry_date: payload?.expiry_date || null,
                photo_path: null,
                faculty: null,
                external_organization: null,
                params: payload?.params || {},
            });
            if (afterCreate === 'redirect') {
                router.visit(route('admin.library-cards.manage'));
            } else {
                showModal.value = false;
            }
            return;
        }
        saveLoading.value = true;
        fieldErrors.value = {};
        try {
            const data = {
                ...payload,
            };
            // Tự động gán người xử lý nếu payload chưa có
            if (data.reviewed_by == null && currentUserId.value != null) {
                data.reviewed_by = currentUserId.value;
            }
            if (data.payment_collected_by == null && currentUserId.value != null) {
                data.payment_collected_by = currentUserId.value;
            }

            await apiClient.patch(`/library-cards/${selectedCard.value.id}`, data);
            showModal.value = false;
            await loadCards();
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to update library card', e);
            fieldErrors.value = e?.response?.data?.errors ?? {};
        } finally {
            saveLoading.value = false;
        }
    };

    const patchCard = async (cardId, payload) => {
        if (!cardId) return;
        saveLoading.value = true;
        fieldErrors.value = {};
        try {
            const data = {
                ...payload,
            };

            if (data.reviewed_by == null && currentUserId.value != null) {
                data.reviewed_by = currentUserId.value;
            }
            if (data.payment_collected_by == null && currentUserId.value != null) {
                data.payment_collected_by = currentUserId.value;
            }

            await apiClient.patch(`/library-cards/${cardId}`, data);
            await loadCards();
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Failed to update library card', e);
            fieldErrors.value = e?.response?.data?.errors ?? {};
        } finally {
            saveLoading.value = false;
        }
    };

    const approveCard = async (card) => {
        if (!card?.id) return;

        const hasPaid = card.payment_status === 'paid' || Number(card.payment_amount || 0) > 0;
        const hasPhoto = Boolean(card.photo_path);
        const canActivateNow = hasPaid && hasPhoto;

        await patchCard(card.id, {
            workflow_status: canActivateNow ? 'active' : 'pending_payment',
            payment_status: canActivateNow ? 'paid' : (card.payment_status || 'pending'),
            is_active: true,
            reviewed_at: new Date().toISOString(),
        });
    };

    const quickIssueCard = async (card) => {
        if (!card?.id) return;
        const now = new Date();
        await patchCard(card.id, {
            workflow_status: 'active',
            payment_status: 'paid',
            paid_at: now.toISOString(),
            issue_date: now.toISOString().slice(0, 10),
            is_active: true,
            reviewed_at: now.toISOString(),
        });
    };

    return {
        cards,
        loading,
        filterValues,
        filteredCards,
        loadCards,
        showModal,
        selectedCard,
        saveLoading,
        fieldErrors,
        openEditModal,
        closeModal,
        saveCard,
        approveCard,
        quickIssueCard,
        mapWorkflowStatusLabel,
    };
}

