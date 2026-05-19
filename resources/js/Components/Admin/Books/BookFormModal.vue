<script setup>
import { computed, defineAsyncComponent, onBeforeUnmount, ref, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { useImageFallback } from '@/composables/useImageFallback';
import { resetFileInput } from '@/utils/resetFileInput';
const RichTextEditor = defineAsyncComponent(() => import('@/Components/Shared/RichTextEditor.vue'));

const props = defineProps({
    show: { type: Boolean, required: true },
    isEditing: { type: Boolean, required: true },
    pageKind: { type: String, default: 'printed' },
    form: { type: Object, required: true },
    classifications: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    cabinetOptions: { type: Array, default: () => [] },
    storageSuggestionLoading: { type: Boolean, default: false },
    storageSuggestionMessage: { type: String, default: '' },
    createCoverPreviewUrl: { type: String, default: '' },
    setCreateCoverFile: { type: Function, default: () => () => {} },
    clearCreateCoverFile: { type: Function, default: () => () => {} },
    editExistingCoverUrl: { type: String, default: '' },
    editExistingDigitalFileName: { type: String, default: '' },
    clearEditExistingCover: { type: Function, default: () => () => {} },
    clearEditExistingDigitalFileName: { type: Function, default: () => () => {} },
    setCreateDigitalFile: { type: Function, default: () => () => {} },
    clearCreateDigitalFile: { type: Function, default: () => () => {} },
    saveLoading: { type: Boolean, default: false },
    saveBlocked: { type: Boolean, default: false },
    fieldErrors: { type: Object, default: () => ({}) },
    clearFieldError: { type: Function, default: () => () => {} },
});

const emit = defineEmits(['close', 'save', 'book-code-touched', 'registration-touched']);
const currentYear = new Date().getFullYear();
const isDigitalPage = computed(() => props.pageKind === 'digital');
const { withFallback } = useImageFallback();

const coverPreviewUrl = computed(
    () => props.createCoverPreviewUrl || (props.isEditing ? props.editExistingCoverUrl : '')
);

const digitalFileLabel = computed(() => {
    if (createDigitalFileName.value) return createDigitalFileName.value;
    if (props.isEditing && props.editExistingDigitalFileName) {
        return props.editExistingDigitalFileName;
    }
    return '';
});

function errClass(key) {
    return props.fieldErrors[key] ? 'border-red-500 dark:border-red-500' : 'border-slate-200 dark:border-slate-700';
}

function matchLookupId(list, text) {
    const raw = String(text || '').trim().toLowerCase();
    if (!raw || !Array.isArray(list)) return null;

    for (const item of list) {
        const code = String(item?.code || '').trim().toLowerCase();
        const name = String(item?.name || '').trim().toLowerCase();
        const label = code && name ? `${code} – ${name}`.toLowerCase() : '';
        if (raw === code || raw === name || (label && raw === label)) return item?.id ?? null;
    }

    for (const item of list) {
        const code = String(item?.code || '').trim().toLowerCase();
        const name = String(item?.name || '').trim().toLowerCase();
        if ((code && raw.includes(code)) || (name && raw.includes(name))) return item?.id ?? null;
    }

    return null;
}

function onCoverFileChange(event) {
    const file = event?.target?.files?.[0] ?? null;
    if (!file) return;
    createCoverFileName.value = file ? String(file.name || '') : '';
    props.setCreateCoverFile(file);
}

const createCoverFileName = ref('');
const createCoverFileInput = ref(null);
function revokeDigitalPdfPreview() {
    if (digitalPdfPreviewUrl.value) {
        URL.revokeObjectURL(digitalPdfPreviewUrl.value);
        digitalPdfPreviewUrl.value = '';
    }
}

const createDigitalFileName = ref('');
const createDigitalFileInput = ref(null);
const digitalPdfPreviewUrl = ref('');
const digitalLocalError = ref('');

function resetCreateFileUi() {
    createCoverFileName.value = '';
    createDigitalFileName.value = '';
    revokeDigitalPdfPreview();
    resetFileInput(createCoverFileInput.value);
    resetFileInput(createDigitalFileInput.value);
}

watch(
    () => props.show,
    (visible) => {
        if (!visible || !props.isEditing) {
            resetCreateFileUi();
        }
    }
);

function removeCreateCover() {
    if (props.createCoverPreviewUrl) {
        createCoverFileName.value = '';
        resetFileInput(createCoverFileInput.value);
        props.clearCreateCoverFile();
        return;
    }
    if (props.isEditing && props.editExistingCoverUrl) {
        props.clearEditExistingCover();
    }
}

function removeExistingDigitalLabel() {
    createDigitalFileName.value = '';
    resetFileInput(createDigitalFileInput.value);
    revokeDigitalPdfPreview();
    props.clearCreateDigitalFile();
    if (props.isEditing && props.editExistingDigitalFileName) {
        props.clearEditExistingDigitalFileName();
    }
}

function onDigitalFileChange(event) {
    const file = event?.target?.files?.[0] ?? null;
    revokeDigitalPdfPreview();
    digitalLocalError.value = '';
    if (!file) return;
    const name = String(file.name || '');
    if (!name.toLowerCase().endsWith('.pdf')) {
        digitalLocalError.value = 'Chỉ chấp nhận file PDF (.pdf).';
        resetFileInput(createDigitalFileInput.value);
        return;
    }
    createDigitalFileName.value = name;
    props.setCreateDigitalFile(file);
    props.clearFieldError('digital_file');
    digitalPdfPreviewUrl.value = URL.createObjectURL(file);
}

function removeCreateDigitalFile() {
    createDigitalFileName.value = '';
    resetFileInput(createDigitalFileInput.value);
    revokeDigitalPdfPreview();
    props.clearCreateDigitalFile();
}

function handleEscapeKey(event) {
    if (event.key !== 'Escape' || !props.show) {
        return;
    }
    if (props.saveLoading) {
        event.preventDefault();
        event.stopPropagation();
        return;
    }
    if (props.saveBlocked) {
        event.preventDefault();
        event.stopPropagation();
    }
}

watch(
    () => props.show && (props.saveBlocked || props.saveLoading),
    (active) => {
        if (typeof window === 'undefined') {
            return;
        }
        if (active) {
            window.addEventListener('keydown', handleEscapeKey, true);
        } else {
            window.removeEventListener('keydown', handleEscapeKey, true);
        }
    },
    { immediate: true }
);

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleEscapeKey, true);
    }
    revokeDigitalPdfPreview();
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div
                class="absolute inset-0 bg-slate-900/50"
                @click="!saveLoading && !saveBlocked && emit('close')"
            />
            <div
                class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800"
            >
                <div
                    class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50"
                >
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{
                            isEditing
                                ? (isDigitalPage ? 'Chỉnh sửa đồ án, luận văn' : 'Chỉnh sửa sách')
                                : (isDigitalPage ? 'Thêm đồ án, luận văn mới' : 'Thêm sách mới')
                        }}
                    </h3>
                    <button
                        type="button"
                        class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 disabled:opacity-40"
                        :disabled="saveLoading"
                        @click="emit('close')"
                    >
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="px-6 pb-6 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div
                        v-if="saveBlocked"
                        class="sm:col-span-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2.5 text-xs text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100"
                    >
                        <strong>Lưu/upload chưa xong.</strong>
                        Form sẽ giữ mở — không chuyển trang / reload cho đến khi bạn sửa lỗi và bấm Lưu lại,
                        hoặc xác nhận đóng form.
                    </div>
                    <p v-if="fieldErrors.general" class="sm:col-span-2 text-xs text-red-500 font-medium">
                        {{ fieldErrors.general }}
                    </p>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Phân loại sách <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.classification"
                            list="book-classification-options"
                            class="w-full h-10 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            :class="errClass('classification')"
                            placeholder="Mã hoặc tên đầu phân loại gốc (000 … 900), ví dụ: 600"
                            @input="clearFieldError('classification')"
                        />
                        <datalist id="book-classification-options">
                            <option
                                v-for="c in classifications"
                                :key="c.id"
                                :value="c.code && c.name ? `${c.code} – ${c.name}` : (c.name || c.code || '')"
                            />
                        </datalist>
                        <p v-if="fieldErrors.classification" class="text-xs text-red-500 font-medium">
                            {{ fieldErrors.classification }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ isDigitalPage ? 'Tên đồ án/luận văn' : 'Tên sách' }} <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.title"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('title')"
                            :placeholder="isDigitalPage ? 'Nhập tên đồ án hoặc luận văn' : 'Nhập tên sách'"
                            @update:model-value="clearFieldError('title')"
                        />
                        <p v-if="fieldErrors.title" class="text-xs text-red-500 font-medium">{{ fieldErrors.title }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Loại sách <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.resource_type"
                            list="book-resource-type-options"
                            class="w-full h-10 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            :class="errClass('resource_type')"
                            placeholder="Gõ hoặc chọn loại sách"
                            @input="clearFieldError('resource_type')"
                        />
                        <datalist id="book-resource-type-options">
                            <option value="Sách giáo trình" />
                            <option value="Sách tham khảo" />
                            <option value="Đồ án, luận văn" />
                        </datalist>
                        <p v-if="fieldErrors.resource_type" class="text-xs text-red-500 font-medium">
                            {{ fieldErrors.resource_type }}
                        </p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tên sách phụ</label>
                        <Input
                            v-model="form.sub_title"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('sub_title')"
                            placeholder="Nhập tên sách phụ"
                            @update:model-value="clearFieldError('sub_title')"
                        />
                        <p v-if="fieldErrors.sub_title" class="text-xs text-red-500 font-medium">{{ fieldErrors.sub_title }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Kho sách <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.warehouse"
                            list="book-warehouse-options"
                            class="w-full h-10 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            :class="errClass('warehouse')"
                            placeholder="Gõ mã / tên kho"
                            @input="clearFieldError('warehouse')"
                        />
                        <datalist id="book-warehouse-options">
                            <option
                                v-for="w in warehouses"
                                :key="w.id"
                                :value="w.code && w.name ? `${w.code} – ${w.name}` : (w.name || w.code || '')"
                            />
                        </datalist>
                        <p v-if="fieldErrors.warehouse" class="text-xs text-red-500 font-medium">{{ fieldErrors.warehouse }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tủ lưu trữ</label>
                        <input
                            v-model="form.cabinet"
                            list="book-cabinet-options"
                            class="w-full h-10 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            :class="errClass('cabinet')"
                            :placeholder="storageSuggestionLoading ? 'Đang gợi ý tủ...' : 'Gợi ý tự động, có thể chỉnh tay'"
                            @input="clearFieldError('cabinet')"
                        />
                        <datalist id="book-cabinet-options">
                            <option v-for="name in cabinetOptions" :key="name" :value="name" />
                        </datalist>
                        <p v-if="fieldErrors.cabinet" class="text-xs text-red-500 font-medium">{{ fieldErrors.cabinet }}</p>
                    </div>
                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tác giả</label>
                        <Input
                            v-model="form.authors"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('authors')"
                            placeholder="Tên tác giả, phân tách bởi dấu phẩy"
                            @update:model-value="clearFieldError('authors')"
                        />
                        <p v-if="fieldErrors.authors" class="text-xs text-red-500 font-medium">{{ fieldErrors.authors }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Nhà xuất bản</label>
                        <Input
                            v-model="form.publisher"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('publisher')"
                            placeholder="Tên nhà xuất bản, phân tách bởi dấu phẩy"
                            @update:model-value="clearFieldError('publisher')"
                        />
                        <p v-if="fieldErrors.publisher" class="text-xs text-red-500 font-medium">{{ fieldErrors.publisher }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Năm xuất bản</label>
                        <Input
                            v-model="form.published_year"
                            type="number"
                            min="1900"
                            :max="currentYear"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('published_year')"
                            placeholder="YYYY"
                            @update:model-value="clearFieldError('published_year')"
                        />
                        <p v-if="fieldErrors.published_year" class="text-xs text-red-500 font-medium">
                            {{ fieldErrors.published_year }}
                        </p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số trang</label>
                        <Input
                            v-model="form.pages"
                            type="number"
                            min="0"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('pages')"
                            placeholder="Ví dụ: 350"
                            @update:model-value="clearFieldError('pages')"
                        />
                        <p v-if="fieldErrors.pages" class="text-xs text-red-500 font-medium">{{ fieldErrors.pages }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Khổ sách</label>
                        <Input
                            v-model="form.book_size"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('book_size')"
                            placeholder="Ví dụ: 12 * 27 cm"
                            @update:model-value="clearFieldError('book_size')"
                        />
                        <p v-if="fieldErrors.book_size" class="text-xs text-red-500 font-medium">{{ fieldErrors.book_size }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Giá bìa (đ)</label>
                        <Input
                            v-model="form.price"
                            type="number"
                            min="0"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('price')"
                            placeholder="Ví dụ: 98000"
                            @update:model-value="clearFieldError('price')"
                        />
                        <p v-if="fieldErrors.price" class="text-xs text-red-500 font-medium">{{ fieldErrors.price }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã sách</label>
                        <Input
                            v-model="form.book_code"
                            class="h-10 rounded-lg font-mono dark:bg-slate-800"
                            :class="errClass('book_code')"
                            placeholder="Mã sách"
                            @update:model-value="
                                () => {
                                    clearFieldError('book_code');
                                    emit('book-code-touched');
                                }
                            "
                        />
                        <p v-if="fieldErrors.book_code" class="text-xs text-red-500 font-medium">{{ fieldErrors.book_code }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số đăng ký cá biệt (DKCB)</label>
                        <Input
                            v-model="form.registration_number"
                            class="h-10 rounded-lg font-mono dark:bg-slate-800"
                            :class="errClass('registration_number')"
                            placeholder="DKCB"
                            @update:model-value="
                                () => {
                                    clearFieldError('registration_number');
                                    emit('registration-touched');
                                }
                            "
                        />
                        <p v-if="fieldErrors.registration_number" class="text-xs text-red-500 font-medium">
                            {{ fieldErrors.registration_number }}
                        </p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngôn ngữ</label>
                        <Input
                            v-model="form.language"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('language')"
                            placeholder="Ví dụ: Tiếng Việt"
                            @update:model-value="clearFieldError('language')"
                        />
                        <p v-if="fieldErrors.language" class="text-xs text-red-500 font-medium">{{ fieldErrors.language }}</p>
                    </div>
                    <div v-if="!isDigitalPage" class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Số lượng <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.quantity"
                            type="number"
                            min="0"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('quantity')"
                            placeholder="Ví dụ: 10"
                            @update:model-value="clearFieldError('quantity')"
                        />
                        <p v-if="fieldErrors.quantity" class="text-xs text-red-500 font-medium">{{ fieldErrors.quantity }}</p>
                    </div>
                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ isDigitalPage ? 'Ảnh minh họa (tùy chọn)' : 'Avatar sách (tùy chọn)' }}
                        </label>
                        <div class="rounded-lg border border-dashed border-slate-300 dark:border-slate-700 p-3 bg-slate-50/60 dark:bg-slate-800/40">
                            <div class="flex flex-wrap items-center gap-2">
                                <input
                                    id="book-create-cover-upload"
                                    ref="createCoverFileInput"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.gif,.webp"
                                    class="sr-only"
                                    @change="onCoverFileChange"
                                />
                                <label
                                    for="book-create-cover-upload"
                                    class="inline-flex h-9 items-center rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    Chọn ảnh bìa
                                </label>
                                <span class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[360px]">
                                    {{
                                        createCoverFileName
                                            || (isEditing && editExistingCoverUrl ? 'Ảnh bìa hiện tại' : 'Chưa chọn ảnh')
                                    }}
                                </span>
                            </div>
                            <div v-if="coverPreviewUrl" class="mt-3 flex items-center gap-3 rounded-md border border-slate-200 dark:border-slate-700 bg-white/70 dark:bg-slate-900/40 p-2">
                                <img :src="coverPreviewUrl" alt="Ảnh bìa" class="h-16 w-12 rounded object-cover ring-1 ring-slate-200 dark:ring-slate-700" @error="withFallback('/images/default-book-cover.png')($event)" />
                                <Button type="button" variant="outline" size="sm" @click="removeCreateCover">Bỏ ảnh</Button>
                            </div>
                        </div>
                    </div>
                    <div v-if="isDigitalPage" class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Tệp đồ án/luận văn (PDF)
                            <span v-if="!isEditing" class="text-rose-500">*</span>
                            <span v-else class="text-slate-400 font-normal text-xs">(để trống giữ file hiện tại)</span>
                        </label>
                        <div class="rounded-lg border border-dashed border-slate-300 dark:border-slate-700 p-3 bg-slate-50/60 dark:bg-slate-800/40">
                            <div class="flex flex-wrap items-center gap-2">
                                <input
                                    id="book-create-digital-upload"
                                    ref="createDigitalFileInput"
                                    type="file"
                                    accept=".pdf,application/pdf"
                                    class="sr-only"
                                    @change="onDigitalFileChange"
                                />
                                <label
                                    for="book-create-digital-upload"
                                    class="inline-flex h-9 items-center rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    Chọn file PDF
                                </label>
                                <span class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[360px]">
                                    {{ digitalFileLabel || 'Chưa chọn file PDF' }}
                                </span>
                                <Button
                                    v-if="digitalFileLabel"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="removeExistingDigitalLabel"
                                >
                                    Bỏ file
                                </Button>
                            </div>
                            <p v-if="digitalLocalError || fieldErrors.digital_file" class="mt-2 text-xs text-red-500 font-medium">
                                {{ digitalLocalError || fieldErrors.digital_file }}
                            </p>
                            <div
                                v-if="digitalPdfPreviewUrl"
                                class="mt-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden"
                            >
                                <p class="px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-700">
                                    Xem trước PDF (trên máy bạn — chưa gửi lên server)
                                </p>
                                <iframe
                                    :src="digitalPdfPreviewUrl"
                                    class="w-full h-[min(50vh,420px)] bg-slate-100 dark:bg-slate-950"
                                    title="Xem trước PDF"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mô tả</label>
                        <div :class="fieldErrors.description ? 'rounded-xl ring-2 ring-red-500' : ''">
                            <RichTextEditor
                                v-if="show"
                                v-model="form.description"
                                :active="show"
                                :placeholder="isDigitalPage ? 'Nhập mô tả về đồ án/luận văn…' : 'Nhập mô tả về nội dung sách…'"
                                min-height="220px"
                                @update:model-value="clearFieldError('description')"
                            />
                        </div>
                        <p v-if="fieldErrors.description" class="text-xs text-red-500 font-medium">{{ fieldErrors.description }}</p>
                    </div>
                    <p v-if="!isDigitalPage && storageSuggestionMessage" class="sm:col-span-2 text-xs font-medium text-amber-600 dark:text-amber-400">
                        {{ storageSuggestionMessage }}
                    </p>
                    <p v-else-if="!isDigitalPage && (form.cabinet || form.warehouse)" class="sm:col-span-2 text-xs text-slate-500 dark:text-slate-400">
                        Vị trí đang chọn:
                        <template v-if="form.warehouse">{{ form.warehouse }} · </template>
                        {{ form.cabinet || '—' }}
                    </p>
                </div>
                <div
                    class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30"
                >
                    <Button type="button" variant="outline" :disabled="saveLoading" @click="emit('close')">Hủy bỏ</Button>
                    <Button
                        type="button"
                        class="bg-blue-600 hover:bg-blue-700 text-white"
                        :disabled="saveLoading"
                        @click="emit('save')"
                    >
                        {{ saveLoading ? 'Đang lưu…' : isEditing ? 'Cập nhật' : 'Lưu' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
