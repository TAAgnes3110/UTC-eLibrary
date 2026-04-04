<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminTrashDrawer from '@/Components/Admin/Shared/AdminTrashDrawer.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import LibraryCardsTable from '@/Components/Admin/LibraryCards/LibraryCardsTable.vue';
import LibraryCardWorkflowModal from '@/Components/Admin/LibraryCards/LibraryCardWorkflowModal.vue';
import { useLibraryCardsAdminPage } from '@/composables/admin/useLibraryCardsAdminPage';


const props = defineProps({
    section: { type: String, default: 'manage' },
    pageTitle: { type: String, default: 'Quản lý thẻ thư viện' },
    periods: { type: Array, default: () => [] },
});

const {
    cards,
    loading,
    filterValues,
    filteredCards,
    showModal,
    selectedCard,
    saveLoading,
    fieldErrors,
    openEditModal,
    closeModal,
    saveCard,
    loadCards,
    approveCard,
} = useLibraryCardsAdminPage();

const selectedIds = ref([]);
const clearSelectionToken = ref(0);

const modalOperation = ref('manage'); // manage | approve

const showTrashDrawer = ref(false);
const trashedCards = ref([]);

const showDeleteConfirm = ref(false);
const deleteLoading = ref(false);
const deleteTarget = ref(null);

const showPhotoModal = ref(false);
const photoUploadLoading = ref(false);
const photoTargetIds = ref([]);

const workflowOptions = [
    { value: '', label: 'Tất cả' },
    { value: 'draft', label: 'Nháp' },
    { value: 'pending_payment', label: 'Chờ thanh toán' },
    { value: 'pending_review', label: 'Chờ duyệt' },
    { value: 'active', label: 'Đang hoạt động' },
];

const activeSection = computed(() => props.section);

watch(
    activeSection,
    (v) => {
        // Set filter workflow_status theo nhánh sidebar để table tự tải đúng dữ liệu.
        if (v === 'manage') filterValues.value.workflow_status = '';
        if (v === 'approve') filterValues.value.workflow_status = 'pending_review';
    },
    { immediate: true },
);

const resolvedPageTitle = computed(() => props.pageTitle || 'Quản lý thẻ thư viện');

const adminPageTitle = 'Quản lý thẻ thư viện';

const headerLabel = computed(() => {
    if (activeSection.value === 'manage') return 'Danh sách thẻ thư viện';
    return 'Duyệt yêu cầu cấp thẻ';
});

const hasSelection = computed(() => selectedIds.value.length > 0);

const onTableSelectionChange = (ids) => {
    selectedIds.value = Array.isArray(ids) ? ids : [];
};

const clearSelection = () => {
    selectedIds.value = [];
    clearSelectionToken.value++;
};

const openBulkDelete = () => {
    deleteTarget.value = null;
    showDeleteConfirm.value = true;
};

const openSingleDelete = (card) => {
    deleteTarget.value = card
        ? {
              ...card,
              title: card.card_number || card.full_name || `#${card.id}`,
          }
        : null;
    showDeleteConfirm.value = true;
};

const confirmSoftDelete = async () => {
    if (deleteLoading.value) return;
    deleteLoading.value = true;

    const idsToDelete = deleteTarget.value?.id ? [deleteTarget.value.id] : [...selectedIds.value];
    const nowIso = new Date().toISOString();

    const toDelete = cards.value.filter((c) => idsToDelete.includes(c.id));
    cards.value = cards.value.filter((c) => !idsToDelete.includes(c.id));

    trashedCards.value = [
        ...trashedCards.value,
        ...toDelete.map((c) => ({
            ...c,
            deleted_at: nowIso,
            is_active: false,
        })),
    ];

    deleteLoading.value = false;
    showDeleteConfirm.value = false;
    deleteTarget.value = null;
    clearSelection();
};

const restoreCard = (id) => {
    const idx = trashedCards.value.findIndex((c) => c.id === id);
    if (idx < 0) return;
    const card = trashedCards.value[idx];
    trashedCards.value.splice(idx, 1);
    cards.value.unshift({ ...card, is_active: true });
};

const restoreManyCards = (ids) => {
    if (!Array.isArray(ids)) return;
    ids.forEach((id) => restoreCard(id));
};

const forceDeleteCard = (id) => {
    trashedCards.value = trashedCards.value.filter((c) => c.id !== id);
};

const forceDeleteManyCards = (ids) => {
    if (!Array.isArray(ids)) return;
    ids.forEach((id) => forceDeleteCard(id));
};

const openPhotoModalForSelected = () => {
    const fallbackIds = filteredCards.value?.map((c) => c.id) ?? [];
    photoTargetIds.value = selectedIds.value.length > 0 ? [...selectedIds.value] : fallbackIds;
    if (photoTargetIds.value.length === 0) return;
    showPhotoModal.value = true;
};

const openPhotoModalForCard = (card) => {
    photoTargetIds.value = card?.id ? [card.id] : [];
    if (photoTargetIds.value.length === 0) return;
    showPhotoModal.value = true;
};

const uploadPhoto = async (file) => {
    if (!file) return;
    photoUploadLoading.value = true;

    const url = URL.createObjectURL(file);
    const ids = new Set(photoTargetIds.value);

    cards.value = cards.value.map((c) => (ids.has(c.id) ? { ...c, photo_path: url } : c));
    trashedCards.value = trashedCards.value.map((c) => (ids.has(c.id) ? { ...c, photo_path: url } : c));

    photoUploadLoading.value = false;
    showPhotoModal.value = false;
};

const toggleLockCard = (card) => {
    if (!card?.id) return;
    const next = !Boolean(cards.value.find((c) => c.id === card.id)?.is_active);
    cards.value = cards.value.map((c) => (c.id === card.id ? { ...c, is_active: next } : c));
    // Nếu đang ở danh sách active thì chỉ cần cập nhật `cards`.
};

const exportExcel = () => {
    // UI-first: xuất CSV (Excel mở được) từ dữ liệu đang hiển thị.
    const rows = filteredCards.value ?? [];
    const headers = [
        'card_number',
        'full_name',
        'email',
        'phone',
        'holder_type',
        'workflow_status',
        'payment_status',
        'payment_amount',
        'faculty',
        'code',
        'period',
        'address',
    ];

    const escapeCell = (v) => {
        const s = v === null || v === undefined ? '' : String(v);
        const needsQuotes = /[",\n]/.test(s);
        const escaped = s.replace(/"/g, '""');
        return needsQuotes ? `"${escaped}"` : escaped;
    };

    const csv = [
        `\uFEFF${headers.join(',')}`,
        ...rows.map((c) =>
            [
                c.card_number,
                c.full_name || c.user?.name,
                c.email,
                c.phone,
                c.holder_type,
                c.workflow_status,
                c.payment_status,
                c.payment_amount,
                c.faculty?.name,
                c.code,
                c.period?.name || '',
                c.address,
            ]
                .map(escapeCell)
                .join(','),
        ),
    ].join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `library-cards-${activeSection.value}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
};

const openManageModal = (card) => {
    modalOperation.value = 'manage';
    openEditModal(card);
};

</script>

<template>
    <Head :title="`${headerLabel} - Admin`" />
    <AdminLayout
        :title="adminPageTitle"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý thẻ thư viện' },
            { label: resolvedPageTitle },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">{{ headerLabel }}</h2>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="activeSection === 'manage'"
                        variant="outline"
                        size="sm"
                        class="gap-1.5"
                        @click="showTrashDrawer = true"
                    >
                        <Icon icon="lucide:trash-2" class="w-4 h-4" />
                        Thùng rác
                    </Button>
                    <Button variant="outline" size="sm" class="gap-1.5" :disabled="loading" @click="loadCards">
                        <Icon icon="lucide:refresh-cw" class="w-4 h-4" />
                        Làm mới
                    </Button>
                </div>
            </div>

            <AdminImportExportBar
                :has-selection="activeSection === 'manage' ? hasSelection : false"
                :selected-count="selectedIds.length"
                :show-add="false"
                :show-import="false"
                :show-export="true"
                update-file-label="Cập nhật ảnh thẻ"
                :show-update-file="true"
                @export-excel="exportExcel"
                @update-file="openPhotoModalForSelected"
                @delete-selected="openBulkDelete"
                @deselect-all="clearSelection"
            />

            <AdminFilterSearch
                v-model="filterValues.searchKeyword"
                search-placeholder="Tìm theo mã thẻ, tên, email..."
                :show-filter-button="false"
                @search="() => {}"
            >
                <template #filters>
                    <div v-if="activeSection === 'manage'" class="flex items-center gap-3">
                        <select v-model="filterValues.workflow_status" class="admin-filter-select admin-filter-select-centered">
                            <option v-for="opt in workflowOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <LibraryCardsTable
                :cards="filteredCards"
                :loading="loading"
                :mode="activeSection"
                @edit="openManageModal"
                @approve="approveCard"
                @selection-change="onTableSelectionChange"
                :clear-selection-token="clearSelectionToken"
                @delete-soft="openSingleDelete"
                @update-photo="openPhotoModalForCard"
                @toggle-lock="toggleLockCard"
            />
        </div>

        <AdminDeleteConfirmModal
            :show="showDeleteConfirm"
            title="Xác nhận xóa thẻ thư viện"
            item-label="thẻ thư viện"
            :item="deleteTarget"
            :selected-count="deleteTarget ? 0 : selectedIds.length"
            :loading="deleteLoading"
            @close="showDeleteConfirm = false"
            @confirm="confirmSoftDelete"
        />

        <AdminTrashDrawer
            :show="showTrashDrawer"
            title="Thùng rác thẻ thư viện"
            item-label-key="card_number"
            :items="trashedCards"
            :loading="false"
            @close="showTrashDrawer = false"
            @restore="restoreCard"
            @restore-many="restoreManyCards"
            @force-delete="forceDeleteCard"
            @force-delete-many="forceDeleteManyCards"
        />

        <AdminFileModal
            :show="showPhotoModal"
            title="Cập nhật ảnh thẻ"
            description="UI-first: chọn file ảnh để cập nhật ảnh thẻ cho các dòng đang chọn (hoặc tất cả dòng đang hiển thị nếu chưa chọn)."
            accept=".jpg,.jpeg,.png,.gif,.webp"
            :max-size-mb="10"
            submit-label="Cập nhật"
            :loading="photoUploadLoading"
            @close="showPhotoModal = false"
            @submit="uploadPhoto"
        >
        </AdminFileModal>

        <LibraryCardWorkflowModal
            :show="showModal"
            :card="selectedCard"
            :operation="modalOperation"
            :loading="saveLoading"
            :field-errors="fieldErrors"
            :periods="props.periods"
            @close="closeModal"
            @save="saveCard"
        />
    </AdminLayout>
</template>

