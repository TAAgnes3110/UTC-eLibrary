<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeading from '@/Components/Admin/Shared/AdminPageHeading.vue';
import AdminImportExportBar from '@/Components/Admin/Shared/AdminImportExportBar.vue';
import AdminFilterSearch from '@/Components/Admin/Shared/AdminFilterSearch.vue';
import AdminFilterPanel from '@/Components/Admin/Shared/AdminFilterPanel.vue';
import AdminPaginationBar from '@/Components/Admin/Shared/AdminPaginationBar.vue';
import AdminDeleteConfirmModal from '@/Components/Admin/Shared/AdminDeleteConfirmModal.vue';
import AdminFileModal from '@/Components/Admin/Shared/AdminFileModal.vue';
import AdminTableActionIcon from '@/Components/Admin/Shared/AdminTableActionIcon.vue';
import { Button } from '@/Components/ui/button';
import { toast } from '@/store/toast';
import { newsPostsApi } from '@/api/newsPosts';
import { extractApiPaginator } from '@/utils/adminPagination';
import { useImageFallback } from '@/composables/useImageFallback';
import { resetFileInput } from '@/utils/resetFileInput';

const rows = ref([]);
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const showDetailModal = ref(false);
const showCoverModal = ref(false);
const coverBulkMode = ref(false);
const coverUploadLoading = ref(false);
const coverTargetPostId = ref(null);
const showRowThumbnailPreviewModal = ref(false);
const rowThumbnailPreview = ref(null);
const rowToDelete = ref(null);
const detailRow = ref(null);
const selectedIds = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const showFilterPanel = ref(false);
const filters = ref({
    keyword: '',
    type: '',
    sort: 'newest',
    searchIn: {
        title: true,
        content: true,
    },
});
const SEARCH_IN_OPTIONS = [
    { key: 'title', label: 'Tiêu đề' },
    { key: 'content', label: 'Nội dung' },
];
const form = ref({
    id: null,
    title: '',
    type: 'news',
    content: '',
    thumbnail: null,
    attachments: [],
    remove_thumbnail: false,
    remove_attachment_ids: [],
    existing_attachments: [],
    current_thumbnail_url: '',
});
const MAX_ATTACHMENTS = 10;
const editorRef = ref(null);
const editorImageInputRef = ref(null);
const thumbnailFileInputRef = ref(null);
const attachmentsFileInputRef = ref(null);
const thumbnailPreviewUrl = ref('');
const uploadingInlineImage = ref(false);
const quill = ref(null);
const showThumbnailPreviewModal = ref(false);
const DEFAULT_NEWS_COVER = '/images/default-news-cover.jpg';
const updateFileLabel = 'Cập nhật ảnh bìa';
const { withFallback } = useImageFallback();
let rowsReloadDebounce = null;
let rowsRequestSerial = 0;


const hasSelection = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed(() => rows.value.length > 0 && selectedIds.value.length === rows.value.length);
const isEditing = computed(() => !!form.value.id);
function resetFormFields() {
    form.value = {
        id: null,
        title: '',
        type: 'news',
        content: '',
        thumbnail: null,
        attachments: [],
        remove_thumbnail: false,
        remove_attachment_ids: [],
        existing_attachments: [],
        current_thumbnail_url: '',
    };
    if (thumbnailPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(thumbnailPreviewUrl.value);
    }
    thumbnailPreviewUrl.value = '';
    resetFileInput(thumbnailFileInputRef.value);
    resetFileInput(attachmentsFileInputRef.value);
    resetFileInput(editorImageInputRef.value);
}

function closeFormModal() {
    showFormModal.value = false;
    resetFormFields();
}

async function loadRows(page = 1) {
    const requestSerial = ++rowsRequestSerial;
    loading.value = true;
    try {
        const activeSearchIn = Object.entries(filters.value.searchIn)
            .filter(([, enabled]) => !!enabled)
            .map(([key]) => key);
        const payload = await newsPostsApi.list({
            page,
            per_page: pagination.value.per_page || 15,
            keyword: filters.value.keyword || undefined,
            type: filters.value.type || undefined,
            sort: filters.value.sort || 'newest',
            search_in: activeSearchIn.length > 0 ? activeSearchIn : undefined,
        });
        const { items, meta } = extractApiPaginator(payload, 15);
        if (requestSerial !== rowsRequestSerial) return;
        rows.value = items;
        pagination.value = {
            current_page: Number(meta.current_page || 1),
            last_page: Number(meta.last_page || 1),
            per_page: Number(meta.per_page || 15),
            total: Number(meta.total || items.length),
        };
        selectedIds.value = selectedIds.value.filter((id) => items.some((r) => r.id === id));
    } catch (e) {
        if (requestSerial !== rowsRequestSerial) return;
        toast.error(e?.response?.data?.messages || 'Không thể tải danh sách bài viết.');
    } finally {
        if (requestSerial === rowsRequestSerial) {
            loading.value = false;
        }
    }
}

function scheduleRowsReload({ resetPage = false, delayMs = 180 } = {}) {
    if (rowsReloadDebounce) clearTimeout(rowsReloadDebounce);
    rowsReloadDebounce = setTimeout(() => {
        loadRows(resetPage ? 1 : pagination.value.current_page || 1);
    }, delayMs);
}

function openCreateForm() {
    resetFormFields();
    showFormModal.value = true;
}

async function openEditForm(item) {
    try {
        const payload = await newsPostsApi.get(item.id);
        const data = payload?.data ?? payload;
        form.value = {
            id: data.id,
            title: data.title || '',
            type: data.type || 'news',
            content: data.content || '',
            thumbnail: null,
            attachments: [],
            remove_thumbnail: false,
            remove_attachment_ids: [],
            existing_attachments: Array.isArray(data.attachments) ? data.attachments : [],
            current_thumbnail_url: data.thumbnail_url || '',
        };
        thumbnailPreviewUrl.value = data.thumbnail_url || '';
        showFormModal.value = true;
        await prepareEditor();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể tải chi tiết bài viết.');
    }
}

async function openCreateWithEditorReady() {
    openCreateForm();
    await prepareEditor();
}

function toggleSelectAll() {
    selectedIds.value = isAllSelected.value ? [] : rows.value.map((r) => r.id);
}

function toggleSelect(id) {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
        return;
    }
    selectedIds.value = [...selectedIds.value, id];
}

function openDeleteOne(item) {
    rowToDelete.value = item;
    showDeleteModal.value = true;
}

function openDeleteSelected() {
    if (!hasSelection.value) return;
    rowToDelete.value = null;
    showDeleteModal.value = true;
}

async function openDetail(item) {
    detailRow.value = item;
    showDetailModal.value = true;
    try {
        const payload = await newsPostsApi.get(item.id);
        const full = payload?.data ?? payload;
        if (showDetailModal.value && full?.id === item.id) {
            detailRow.value = full;
        }
    } catch (_e) {
        // giữ dữ liệu list nếu fetch chi tiết lỗi
    }
}

function openRowThumbnailPreview(item) {
    rowThumbnailPreview.value = item;
    showRowThumbnailPreviewModal.value = true;
}

function closeRowThumbnailPreview() {
    showRowThumbnailPreviewModal.value = false;
    rowThumbnailPreview.value = null;
}

function openCoverModal(item = null) {
    if (item?.id) {
        coverBulkMode.value = false;
        coverTargetPostId.value = item.id;
    } else {
        const ids = [...selectedIds.value];
        coverBulkMode.value = ids.length !== 1;
        coverTargetPostId.value = ids.length === 1 ? ids[0] : null;
    }
    showCoverModal.value = true;
}

function closeCoverModal() {
    showCoverModal.value = false;
    coverBulkMode.value = false;
    coverTargetPostId.value = null;
}

async function uploadCover(file) {
    if (!file) return;
    coverUploadLoading.value = true;
    try {
        const fd = new FormData();
        if (coverBulkMode.value) {
            fd.append('file', file);
            if (selectedIds.value.length > 0) {
                fd.append('ids', JSON.stringify(selectedIds.value));
            }
            const payload = await newsPostsApi.bulkUpdateThumbnail(fd);
            const summary = payload?.data ?? {};
            const updated = Number(summary.updated ?? 0);
            const skipped = Number(summary.skipped ?? 0);
            const selectedCount = summary.selected_count != null ? Number(summary.selected_count) : 0;
            const selectedMissing = summary.selected_missing != null ? Number(summary.selected_missing) : 0;
            const hadSelectionFilter = summary.selected_count != null;
            await loadRows();
            if (updated > 0) {
                if (hadSelectionFilter && selectedMissing > 0) {
                    toast.warn(
                        `Cập nhật ${updated}/${selectedCount} — thiếu ${selectedMissing} ảnh trong zip.` +
                            (skipped > 0 ? ` (+${skipped} file bỏ qua)` : ''),
                        { title: 'Ảnh bìa bài viết' },
                    );
                } else if (hadSelectionFilter && selectedMissing === 0 && selectedCount > 0) {
                    toast.success(`Đủ ${updated}/${selectedCount} bài viết đã chọn.`, { title: 'Ảnh bìa bài viết' });
                } else if (skipped > 0) {
                    toast.success(`${updated} ảnh · ${skipped} file bỏ qua`, { title: 'Ảnh bìa bài viết' });
                } else {
                    toast.success(`${updated} ảnh bìa`, { title: 'Ảnh bìa bài viết' });
                }
            } else {
                const picked = selectedIds.value.length > 0;
                toast.warn(
                    skipped > 0
                        ? picked
                            ? `0 ảnh — không khớp mã bài viết với ${skipped} file.`
                            : `0 ảnh — ${skipped} file không khớp mã bài viết.`
                        : 'Zip trống hoặc không đọc được.',
                    { title: 'Ảnh bìa bài viết' },
                );
            }
        } else {
            const postId = coverTargetPostId.value ?? selectedIds.value[0];
            if (!postId) {
                toast.info('Vui lòng chọn đúng 1 bài viết để cập nhật ảnh bìa.', { title: 'Ảnh bìa bài viết' });
                coverUploadLoading.value = false;
                return;
            }
            fd.append('thumbnail', file);
            await newsPostsApi.updateThumbnail(postId, fd);
            await loadRows();
            toast.success('Cập nhật ảnh bìa bài viết thành công.', { title: 'Ảnh bìa bài viết' });
        }
        closeCoverModal();
    } catch (e) {
        const data = e?.response?.data || {};
        const message = data?.messages || data?.message || 'Cập nhật ảnh bìa không thành công. Vui lòng kiểm tra lại file.';
        toast.error(message, { title: 'Ảnh bìa bài viết' });
    } finally {
        coverUploadLoading.value = false;
    }
}

async function savePost() {
    if (saving.value) return;
    syncFormContentFromEditor();
    const hasTitle = String(form.value.title || '').trim().length > 0;
    const hasContent = hasContentValue(form.value.content);

    if (!hasTitle && !hasContent) {
        toast.error('Vui lòng nhập tiêu đề và nội dung bài viết.');
        return;
    }
    if (!hasTitle) {
        toast.error('Vui lòng nhập tiêu đề bài viết.');
        return;
    }
    if (!hasContent) {
        toast.error('Vui lòng nhập nội dung bài viết.');
        return;
    }
    saving.value = true;
    try {
        const fd = new FormData();
        fd.append('title', String(form.value.title || '').trim());
        fd.append('type', String(form.value.type || 'news'));
        fd.append('content', String(form.value.content || '').trim());
        if (form.value.thumbnail instanceof File) {
            fd.append('thumbnail', form.value.thumbnail);
        }
        if (form.value.remove_thumbnail) {
            fd.append('remove_thumbnail', '1');
        }
        form.value.attachments.forEach((file) => fd.append('attachments[]', file));
        form.value.remove_attachment_ids.forEach((id) => fd.append('remove_attachment_ids[]', String(id)));

        if (isEditing.value) {
            await newsPostsApi.update(form.value.id, fd);
            toast.success('Đã cập nhật bài viết.');
        } else {
            await newsPostsApi.create(fd);
            toast.success('Đã tạo bài viết.');
        }
        closeFormModal();
        await loadRows(1);
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể lưu bài viết.');
    } finally {
        saving.value = false;
    }
}

async function confirmDelete() {
    deleting.value = true;
    try {
        if (rowToDelete.value?.id) {
            await newsPostsApi.remove(rowToDelete.value.id);
            toast.success('Đã xóa bài viết.');
        } else {
            await Promise.all(selectedIds.value.map((id) => newsPostsApi.remove(id)));
            toast.success('Đã xóa các bài viết đã chọn.');
            selectedIds.value = [];
        }
        showDeleteModal.value = false;
        rowToDelete.value = null;
        await loadRows();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể xóa bài viết.');
    } finally {
        deleting.value = false;
    }
}

onMounted(async () => {
    await loadRows(1);
});

watch(() => filters.value.type, () => {
    scheduleRowsReload({ resetPage: true, delayMs: 120 });
});
watch(() => filters.value.sort, () => {
    scheduleRowsReload({ resetPage: true, delayMs: 120 });
});
watch(() => filters.value.searchIn, () => {
    scheduleRowsReload({ resetPage: true, delayMs: 180 });
}, { deep: true });

onBeforeUnmount(() => {
    if (rowsReloadDebounce) clearTimeout(rowsReloadDebounce);
    rowsRequestSerial++;
});

function syncEditorFromForm() {
    if (!quill.value) return;
    quill.value.root.innerHTML = String(form.value.content || '').trim() || '<p><br></p>';
}

function normalizeEditorHtml(html) {
    let normalized = String(html || '');
    normalized = normalized.replace(/<p><br><\/p>/gi, '');
    normalized = normalized.replace(/<p>\s*<\/p>/gi, '');

    return normalized.trim();
}

function syncFormContentFromEditor() {
    if (!quill.value) return;
    form.value.content = normalizeEditorHtml(quill.value.root.innerHTML);
}

function hasContentValue(html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = String(html || '');
    const text = (wrapper.textContent || '').trim();
    return text.length > 0 || wrapper.querySelector('img') !== null;
}

function onThumbnailChange(event) {
    const file = event?.target?.files?.[0];
    form.value.thumbnail = file || null;
    if (thumbnailPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(thumbnailPreviewUrl.value);
    }
    if (file) {
        form.value.remove_thumbnail = false;
        thumbnailPreviewUrl.value = URL.createObjectURL(file);
        return;
    }
    thumbnailPreviewUrl.value = form.value.remove_thumbnail ? '' : (form.value.current_thumbnail_url || '');
}

function toggleRemoveThumbnail() {
    form.value.remove_thumbnail = !form.value.remove_thumbnail;
    if (form.value.remove_thumbnail && !(form.value.thumbnail instanceof File)) {
        thumbnailPreviewUrl.value = '';
        return;
    }
    if (!(form.value.thumbnail instanceof File)) {
        thumbnailPreviewUrl.value = form.value.current_thumbnail_url || '';
    }
}

function openThumbnailPreview() {
    if (!thumbnailPreviewUrl.value) return;
    showThumbnailPreviewModal.value = true;
}

function onAttachmentsChange(event) {
    const files = Array.from(event?.target?.files || []);
    if (files.length === 0) return;

    const existingCount = form.value.existing_attachments.length;
    const merged = [...form.value.attachments, ...files];
    const deduped = [];
    const seen = new Set();

    for (const file of merged) {
        const key = `${file.name}-${file.size}-${file.lastModified}`;
        if (seen.has(key)) continue;
        seen.add(key);
        deduped.push(file);
    }

    const allowedNewCount = Math.max(0, MAX_ATTACHMENTS - existingCount);
    if (deduped.length > allowedNewCount) {
        toast.info(`Tối đa ${MAX_ATTACHMENTS} tệp. Hệ thống giữ ${allowedNewCount} tệp mới.`);
    }

    form.value.attachments = deduped.slice(0, allowedNewCount);
    if (event?.target) event.target.value = '';
}

function removeNewAttachment(index) {
    form.value.attachments.splice(index, 1);
}

function removeExistingAttachment(id) {
    if (!form.value.remove_attachment_ids.includes(id)) {
        form.value.remove_attachment_ids.push(id);
    }
    form.value.existing_attachments = form.value.existing_attachments.filter((x) => x.id !== id);
}

async function prepareEditor() {
    await nextTick();
    if (!editorRef.value) return;

    const shouldReinitialize =
        !quill.value ||
        !quill.value.root ||
        !quill.value.root.isConnected ||
        quill.value.root.closest('.news-quill-editor') !== editorRef.value;

    if (shouldReinitialize) {
        editorRef.value.innerHTML = '';
        quill.value = new Quill(editorRef.value, {
            theme: 'snow',
            placeholder: 'Nhập nội dung bài viết...',
            modules: {
                toolbar: {
                    container: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }, { background: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ align: [] }],
                        ['blockquote', 'link', 'image'],
                        ['clean'],
                    ],
                    handlers: {
                        image: () => editorImageInputRef.value?.click(),
                    },
                },
            },
        });

        quill.value.on('text-change', () => {
            syncFormContentFromEditor();
        });
    }

    forceWordToolbarStyle();
    syncEditorFromForm();
}

function forceWordToolbarStyle() {
    if (!quill.value) return;

    const toolbar = quill.value.getModule('toolbar')?.container;
    if (!toolbar) return;

    toolbar.style.background = '#e5e7eb';
    toolbar.style.borderBottom = '1px solid #cbd5e1';
    toolbar.style.padding = '10px 12px';
    toolbar.style.color = '#1f2937';

    toolbar.querySelectorAll('button, .ql-picker-label').forEach((el) => {
        el.style.color = '#1f2937';
        el.style.opacity = '1';
    });

    toolbar.querySelectorAll('svg').forEach((svg) => {
        svg.style.filter = 'none';
    });

    toolbar.querySelectorAll('.ql-stroke').forEach((el) => {
        el.style.stroke = '#1f2937';
    });
    toolbar.querySelectorAll('.ql-fill').forEach((el) => {
        el.style.fill = '#1f2937';
    });
}

function extractApiErrorMessage(error, fallback) {
    const data = error?.response?.data;
    if (typeof data?.messages === 'string' && data.messages.trim() !== '') return data.messages;
    if (typeof data?.message === 'string' && data.message.trim() !== '') return data.message;
    const firstError = data?.errors && typeof data.errors === 'object'
        ? Object.values(data.errors).flat().find((v) => typeof v === 'string' && v.trim() !== '')
        : null;
    if (typeof firstError === 'string' && firstError.trim() !== '') return firstError;
    if (typeof error?.message === 'string' && error.message.trim() !== '') return error.message;
    return fallback;
}

function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('Không thể đọc tệp ảnh.'));
        reader.readAsDataURL(file);
    });
}

function escapeHtmlAttr(value) {
    return String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('"', '&quot;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;');
}

async function onInlineImageSelected(event) {
    const file = event?.target?.files?.[0];
    if (!file) return;

    if (!String(file.type || '').startsWith('image/')) {
        toast.error('Vui lòng chọn đúng tệp ảnh (jpg, png, webp, gif).');
        if (event?.target) event.target.value = '';
        return;
    }
    if ((file.size || 0) > 10 * 1024 * 1024) {
        toast.error('Ảnh chèn nội dung không vượt quá 10MB.');
        if (event?.target) event.target.value = '';
        return;
    }

    uploadingInlineImage.value = true;
    try {
        if (!quill.value) {
            throw new Error('Trình soạn thảo chưa khởi tạo xong. Vui lòng đóng/mở lại popup rồi thử lại.');
        }

        const dataUrl = await readFileAsDataUrl(file);
        if (!dataUrl) {
            throw new Error('Không đọc được dữ liệu ảnh (data URL rỗng).');
        }

        const safeSrc = escapeHtmlAttr(dataUrl);
        const safeAlt = escapeHtmlAttr(file.name || 'image');
        const root = quill.value.root;
        if (!root) {
            throw new Error('Không truy cập được vùng nội dung editor.');
        }

        // Safe mode: chèn trực tiếp vào DOM để tránh crash selection của Quill.
        const wrapper = document.createElement('p');
        const image = document.createElement('img');
        image.setAttribute('src', safeSrc);
        image.setAttribute('alt', safeAlt);
        image.style.maxWidth = '100%';
        image.style.height = 'auto';
        wrapper.appendChild(image);
        root.appendChild(wrapper);
        syncFormContentFromEditor();
    } catch (e) {
        const message = extractApiErrorMessage(e, 'Không thể chèn ảnh vào nội dung.');
        console.error('[NewsEditor][InlineImage] Insert failed:', {
            message,
            rawError: e,
            fileName: file?.name,
            fileType: file?.type,
            fileSize: file?.size,
        });
        toast.error(`Lỗi chèn ảnh: ${message}`);
    } finally {
        uploadingInlineImage.value = false;
        if (event?.target) event.target.value = '';
    }
}
</script>

<template>
    <Head title="Quản lý bài viết - Admin" />
    <AdminLayout
        title="Quản lý tin tức & thông báo"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Tin tức & thông báo' }, { label: 'Tin tức, thông báo' }]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <AdminPageHeading title="Danh sách tin tức, thông báo" />

            <AdminImportExportBar
                :has-selection="hasSelection"
                :selected-count="selectedIds.length"
                add-label="Tạo bài viết"
                :update-file-label="updateFileLabel"
                :show-import="false"
                :show-export="false"
                :show-update-file="true"
                @add="openCreateWithEditorReady"
                @update-file="() => openCoverModal()"
                @delete-selected="openDeleteSelected"
                @deselect-all="selectedIds = []"
            />

            <AdminFilterSearch
                v-model="filters.keyword"
                search-placeholder="Tìm theo tiêu đề hoặc nội dung..."
                :show-filter-button="false"
                @search="scheduleRowsReload({ resetPage: true, delayMs: 0 })"
            >
                <template #filters>
                    <div class="flex w-full min-w-0 items-center gap-2.5 flex-wrap">
                        <AdminFilterPanel
                            :options="SEARCH_IN_OPTIONS"
                            v-model:model-value="filters.searchIn"
                            :show="showFilterPanel"
                            @update:show="showFilterPanel = $event"
                        />
                        <select
                            v-model="filters.type"
                            class="admin-filter-select admin-filter-select-centered !h-10 !rounded-xl px-2.5 shadow-sm min-w-[132px] text-sm"
                            @change="scheduleRowsReload({ resetPage: true, delayMs: 0 })"
                        >
                            <option value="">Tất cả loại</option>
                            <option value="news">Tin tức</option>
                            <option value="notice">Thông báo</option>
                        </select>
                        <select
                            v-model="filters.sort"
                            class="admin-filter-select admin-filter-select-centered !h-10 !rounded-xl px-3 shadow-sm min-w-[120px] text-sm"
                            @change="scheduleRowsReload({ resetPage: true, delayMs: 0 })"
                        >
                            <option value="newest">Mới nhất</option>
                            <option value="oldest">Cũ nhất</option>
                        </select>
                    </div>
                </template>
            </AdminFilterSearch>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1160px] table-auto text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="px-3 py-3.5 w-12 align-middle">
                                    <span class="admin-table-checkbox-wrap">
                                        <input
                                            type="checkbox"
                                            :checked="isAllSelected"
                                            :indeterminate="hasSelection && !isAllSelected"
                                            class="admin-table-checkbox"
                                            @change="toggleSelectAll"
                                        />
                                    </span>
                                </th>
                                <th class="px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Mã bài viết</th>
                                <th class="px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Ảnh bài viết</th>
                                <th class="px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Tiêu đề</th>
                                <th class="px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Loại</th>
                                <th class="px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Người viết</th>
                                <th class="px-3 py-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Ngày đăng</th>
                                <th class="px-3 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="item in rows" :key="item.id" class="admin-table-row">
                                <td class="px-3 py-3 align-middle">
                                    <span class="admin-table-checkbox-wrap">
                                        <input type="checkbox" :checked="selectedIds.includes(item.id)" class="admin-table-checkbox" @change="toggleSelect(item.id)" />
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">#{{ item.id }}</span>
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md p-1 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                                        :title="`Xem ảnh bìa: ${item.title || 'Bài viết'}`"
                                        @click="openRowThumbnailPreview(item)"
                                    >
                                        <img
                                            :src="item.thumbnail_url || DEFAULT_NEWS_COVER"
                                            alt="Ảnh đại diện bài viết"
                                            @error="withFallback(DEFAULT_NEWS_COVER)($event)"
                                            class="h-12 w-16 rounded-md object-cover border border-slate-200 dark:border-slate-700"
                                        />
                                    </button>
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-semibold text-slate-900 dark:text-white truncate">{{ item.title }}</p>
                                    </div>
                                </td>
                                <td class="px-3 py-3 align-middle text-[12px]">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs font-semibold"
                                        :class="item.type === 'notice'
                                            ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'
                                            : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200'"
                                    >
                                        {{ item.type === 'notice' ? 'Thông báo' : 'Tin tức' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-middle text-[13px] text-slate-700 dark:text-slate-200">
                                    {{ item.posted_by?.name || '—' }}
                                </td>
                                <td class="px-3 py-3 align-middle text-[13px] text-slate-700 dark:text-slate-200">
                                    {{ item.published_at ? new Date(item.published_at).toLocaleString('vi-VN') : '—' }}
                                </td>
                                <td class="px-3 py-3 align-middle text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-0.5">
                                        <AdminTableActionIcon icon="lucide:eye" tone="slate" title="Xem chi tiết" icon-class="w-4 h-4" @click="openDetail(item)" />
                                        <AdminTableActionIcon icon="lucide:image-plus" tone="slate" title="Cập nhật ảnh bìa" icon-class="w-4 h-4" @click="openCoverModal(item)" />
                                        <AdminTableActionIcon icon="lucide:pen-square" tone="slate" title="Sửa" icon-class="w-4 h-4" @click="openEditForm(item)" />
                                        <AdminTableActionIcon icon="lucide:trash-2" tone="rose" title="Xóa" icon-class="w-4 h-4" @click="openDeleteOne(item)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!loading && rows.length === 0">
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">Chưa có bài viết nào.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AdminPaginationBar
                always-show
                :current-page="pagination.current_page"
                :last-page="pagination.last_page"
                :disabled="loading"
                @go-page="loadRows"
            />
        </div>

        <AdminDeleteConfirmModal
            :show="showDeleteModal"
            title="Xác nhận xóa bài viết"
            item-label="bài viết"
            :item="rowToDelete"
            :selected-count="rowToDelete ? 0 : selectedIds.length"
            :loading="deleting"
            @close="showDeleteModal = false"
            @confirm="confirmDelete"
        />

        <AdminFileModal
            :show="showCoverModal"
            :title="coverBulkMode ? 'Cập nhật ảnh bìa hàng loạt' : 'Cập nhật ảnh bìa bài viết'"
            :description="
                coverBulkMode
                    ? selectedIds.length > 0
                        ? `Đã chọn ${selectedIds.length} bài viết — chỉ cập nhật ảnh cho các bản ghi đã chọn (tên file trong .zip = mã bài viết/ID).`
                        : 'File .zip: mỗi ảnh đặt tên đúng mã bài viết (ID) + đuôi (jpg, png...). Cập nhật mọi bài viết có mã khớp trong zip.'
                    : 'Kéo thả ảnh vào đây hoặc chọn file. Tên file không quan trọng, hệ thống tự đặt tên.'
            "
            :accept="coverBulkMode ? '.zip' : '.jpg,.jpeg,.png,.gif,.webp'"
            :max-size-mb="coverBulkMode ? 50 : 10"
            submit-label="Lưu"
            :loading="coverUploadLoading"
            @close="closeCoverModal"
            @submit="(file) => uploadCover(file)"
        />

        <Teleport to="body">
            <div v-if="showRowThumbnailPreviewModal && rowThumbnailPreview" class="fixed inset-0 z-[112] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60" @click="closeRowThumbnailPreview" />
                <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <div class="mb-3 flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ảnh bìa bài viết</h4>
                        <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeRowThumbnailPreview">
                            <Icon icon="lucide:x" class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                        <img
                            :src="rowThumbnailPreview.thumbnail_url || DEFAULT_NEWS_COVER"
                            :alt="rowThumbnailPreview.title || 'Ảnh bìa'"
                            @error="withFallback(DEFAULT_NEWS_COVER)($event)"
                            class="h-[320px] w-full object-contain bg-slate-50 dark:bg-slate-800"
                        />
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button
                            type="button"
                            class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/35 dark:text-blue-300 dark:hover:bg-blue-900/50"
                            @click="closeRowThumbnailPreview(); openCoverModal(rowThumbnailPreview)"
                        >
                            <Icon icon="lucide:camera" class="h-3.5 w-3.5" />
                            Đổi ảnh
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showDetailModal && detailRow" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60" @click="showDetailModal = false" />
                <div class="relative w-full max-w-3xl rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3 dark:border-slate-700">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Chi tiết bài viết #{{ detailRow.id }}</h3>
                        <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="showDetailModal = false">
                            <Icon icon="lucide:x" class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="max-h-[70vh] overflow-y-auto p-5 space-y-4">
                        <img
                            :src="detailRow.thumbnail_url || DEFAULT_NEWS_COVER"
                            alt="Ảnh bài viết"
                            @error="withFallback(DEFAULT_NEWS_COVER)($event)"
                            class="h-40 w-full rounded-lg object-cover border border-slate-200 dark:border-slate-700"
                        />
                        <div>
                            <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ detailRow.title }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Người viết: {{ detailRow.posted_by?.name || '—' }} ·
                                Ngày đăng: {{ detailRow.published_at ? new Date(detailRow.published_at).toLocaleString('vi-VN') : '—' }}
                            </p>
                        </div>
                        <div class="prose prose-sm max-w-none dark:prose-invert prose-img:rounded-md" v-html="detailRow.content || '<p>Không có nội dung.</p>'"></div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showFormModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60" @click="closeFormModal" />
                <div class="relative flex max-h-[90vh] w-full max-w-4xl flex-col rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Sửa bài viết' : 'Tạo bài viết mới' }}
                        </h3>
                        <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeFormModal">
                            <Icon icon="lucide:x" class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="grid flex-1 grid-cols-1 gap-4 overflow-y-auto p-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Tiêu đề</label>
                            <input
                                v-model="form.title"
                                class="mt-1 w-full h-11 rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:ring-blue-900/40"
                                placeholder="Nhập tiêu đề bài viết"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Loại bài viết</label>
                            <select
                                v-model="form.type"
                                class="mt-1 w-full h-11 rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:ring-blue-900/40"
                            >
                                <option value="news">Tin tức</option>
                                <option value="notice">Thông báo</option>
                            </select>
                        </div>
                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="rounded-xl border border-slate-300/70 dark:border-slate-700 p-4 bg-slate-50/70 dark:bg-slate-800/40">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Ảnh bài viết</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ảnh đại diện hiển thị ở danh sách và trang chi tiết.</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <label class="inline-flex h-10 cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        <input ref="thumbnailFileInputRef" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onThumbnailChange" />
                                        Chọn ảnh đại diện
                                    </label>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 items-center rounded-lg border px-3 text-sm font-medium transition"
                                        :class="form.remove_thumbnail
                                            ? 'border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-200'
                                            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'"
                                        @click="toggleRemoveThumbnail"
                                    >
                                        {{ form.remove_thumbnail ? 'Đang xóa ảnh hiện tại' : 'Xóa ảnh hiện tại' }}
                                    </button>
                                </div>

                                <div class="mt-3 rounded-lg border border-dashed border-slate-300 bg-white/70 p-2 dark:border-slate-600 dark:bg-slate-900/40">
                                    <img
                                        v-if="thumbnailPreviewUrl"
                                        :src="thumbnailPreviewUrl"
                                        alt="Xem trước ảnh đại diện"
                                        class="h-28 w-full cursor-zoom-in rounded-md object-cover"
                                        @error="withFallback(DEFAULT_NEWS_COVER)($event)"
                                        @click="openThumbnailPreview"
                                    />
                                    <div v-else class="flex h-28 items-center justify-center rounded-md bg-slate-100 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        Chưa có ảnh đại diện
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-300/70 dark:border-slate-700 p-4 bg-slate-50/70 dark:bg-slate-800/40">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">File đính kèm (nhiều tệp)</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Bấm chọn để tải lên PDF/Word/Excel/Zip... tối đa {{ MAX_ATTACHMENTS }} tệp.</p>
                                <label class="mt-3 flex min-h-[92px] cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3 text-center text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                                    <input ref="attachmentsFileInputRef" type="file" multiple class="hidden" @change="onAttachmentsChange" />
                                    <Icon icon="lucide:upload-cloud" class="h-5 w-5 mb-1" />
                                    Chọn nhiều file đính kèm
                                </label>
                                <div v-if="form.attachments.length > 0" class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        v-for="(file, idx) in form.attachments"
                                        :key="file.name + file.size + idx"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-1 text-xs text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800"
                                        @click="removeNewAttachment(idx)"
                                    >
                                        {{ file.name }}
                                        <Icon icon="lucide:x" class="w-3 h-3" />
                                    </button>
                                </div>
                                <div v-if="form.existing_attachments.length > 0" class="mt-3 space-y-1.5">
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Tệp đã có:</p>
                                    <div v-for="file in form.existing_attachments" :key="file.id" class="flex items-center justify-between rounded-md border border-slate-200 px-2 py-1.5 dark:border-slate-700">
                                        <span class="text-xs text-slate-700 dark:text-slate-200 truncate pr-2">{{ file.original_name }}</span>
                                        <button type="button" class="text-xs text-rose-600 hover:text-rose-700" @click="removeExistingAttachment(file.id)">Gỡ</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Nội dung</label>
                            <div class="mt-1 overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
                                <input ref="editorImageInputRef" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" class="hidden" @change="onInlineImageSelected" />
                                <div ref="editorRef" class="news-quill-editor"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                        <Button variant="outline" :disabled="saving" @click="closeFormModal">Hủy</Button>
                        <Button
                            class="bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-60"
                            :disabled="saving"
                            @click="savePost"
                        >
                            {{ saving ? 'Đang lưu...' : (isEditing ? 'Lưu thay đổi' : 'Tạo bài viết') }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>
        <Teleport to="body">
            <div v-if="showThumbnailPreviewModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/70" @click="showThumbnailPreviewModal = false" />
                <div class="relative w-full max-w-2xl rounded-xl border border-slate-200 bg-white p-3 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                    <button
                        type="button"
                        class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        @click="showThumbnailPreviewModal = false"
                    >
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                    <img :src="thumbnailPreviewUrl" alt="Ảnh đại diện hiện tại" class="mx-auto max-h-[70vh] w-auto rounded-lg object-contain" @error="withFallback(DEFAULT_NEWS_COVER)($event)" />
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.news-quill-editor :deep(.ql-toolbar.ql-snow) {
    border: 0;
    border-bottom: 1px solid #cbd5e1 !important;
    background: #e5e7eb;
    padding: 10px 12px;
}

.news-quill-editor :deep(.ql-container.ql-snow) {
    border: 0;
    min-height: 300px;
    font-size: 14px;
    background: #ffffff;
}

.news-quill-editor :deep(.ql-editor) {
    min-height: 300px;
    color: #111827;
    line-height: 1.7;
}

.news-quill-editor :deep(.ql-editor.ql-blank::before) {
    color: #6b7280;
    font-style: normal;
}

/* Force content area always in light mode (both light/night app theme) */
.news-quill-editor :deep(.ql-container.ql-snow) {
    background: #ffffff !important;
}

.news-quill-editor :deep(.ql-editor),
.news-quill-editor :deep(.ql-editor *) {
    color: #111827 !important;
    caret-color: #111827 !important;
    text-shadow: none !important;
}

.news-quill-editor :deep(.ql-editor a) {
    color: #2563eb !important;
}

.news-quill-editor :deep(.ql-editor ::selection) {
    background: rgba(37, 99, 235, 0.25);
    color: #111827;
}

.news-quill-editor :deep(.ql-toolbar button),
.news-quill-editor :deep(.ql-toolbar .ql-picker-label),
.news-quill-editor :deep(.ql-toolbar .ql-picker-label::before) {
    color: #1f2937 !important;
    opacity: 1 !important;
}

.news-quill-editor :deep(.ql-toolbar button svg .ql-stroke),
.news-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-stroke) {
    stroke: #1f2937 !important;
}

.news-quill-editor :deep(.ql-toolbar button svg .ql-fill),
.news-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-fill) {
    fill: #1f2937 !important;
}

.news-quill-editor :deep(.ql-toolbar button:hover),
.news-quill-editor :deep(.ql-toolbar button.ql-active),
.news-quill-editor :deep(.ql-toolbar .ql-picker-label:hover),
.news-quill-editor :deep(.ql-toolbar .ql-picker-label.ql-active) {
    background: #e5e7eb !important;
    border-radius: 6px;
}

.news-quill-editor :deep(.ql-toolbar .ql-picker-options) {
    background: #ffffff;
    border-color: #d1d5db;
}

/* Keep ONLY toolbar fixed in both light/night */
:global(html.dark) .news-quill-editor :deep(.ql-toolbar.ql-snow),
:global(body.dark) .news-quill-editor :deep(.ql-toolbar.ql-snow),
:global(.dark) .news-quill-editor :deep(.ql-toolbar.ql-snow) {
    background: #e5e7eb !important;
    border-bottom: 1px solid #cbd5e1 !important;
}

:global(html.dark) .news-quill-editor :deep(.ql-toolbar button),
:global(html.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label),
:global(html.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label::before),
:global(body.dark) .news-quill-editor :deep(.ql-toolbar button),
:global(body.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label),
:global(body.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label::before),
:global(.dark) .news-quill-editor :deep(.ql-toolbar button),
:global(.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label),
:global(.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label::before) {
    color: #1f2937 !important;
}

:global(html.dark) .news-quill-editor :deep(.ql-toolbar button svg .ql-stroke),
:global(html.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-stroke),
:global(body.dark) .news-quill-editor :deep(.ql-toolbar button svg .ql-stroke),
:global(body.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-stroke),
:global(.dark) .news-quill-editor :deep(.ql-toolbar button svg .ql-stroke),
:global(.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-stroke) {
    stroke: #1f2937 !important;
}

:global(html.dark) .news-quill-editor :deep(.ql-toolbar button svg .ql-fill),
:global(html.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-fill),
:global(body.dark) .news-quill-editor :deep(.ql-toolbar button svg .ql-fill),
:global(body.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-fill),
:global(.dark) .news-quill-editor :deep(.ql-toolbar button svg .ql-fill),
:global(.dark) .news-quill-editor :deep(.ql-toolbar .ql-picker-label svg .ql-fill) {
    fill: #1f2937 !important;
}
</style>
