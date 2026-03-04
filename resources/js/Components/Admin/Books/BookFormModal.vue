<script setup>
import { ref, watch, computed, nextTick, onUnmounted } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Icon } from '@iconify/vue';
import { RESOURCE_GROUPS } from '@/config/enums';

function useDebounce(fn, ms) {
    let timeout = null;
    return (...args) => {
        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), ms);
    };
}

const props = defineProps({
    show: Boolean,
    form: Object,
    isEditing: Boolean,
    categories: { type: Array, default: () => [] },
    faculties: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    cohorts: { type: Array, default: () => [] },
    bookTypes: { type: Array, default: () => [] },
});

const groupOptions = computed(() =>
    Object.entries(RESOURCE_GROUPS).map(([value, label]) => ({ value, label }))
);

const departmentsByFaculty = computed(() => {
    const fid = props.form?.faculty_id;
    if (!fid) return [];
    return props.departments.filter((d) => Number(d.faculty_id) === Number(fid));
});

const emit = defineEmits(['close', 'submit']);

const showConfirm = ref(false);
const validationError = ref('');
const coverPreview = ref(null);

// ——— Upload file tài liệu số ———
const documentUploading = ref(false);
const uploadedFileName = ref('');
const documentFileInput = ref(null);

function clearDocumentFile() {
    props.form.file_url = '';
    uploadedFileName.value = '';
    if (documentFileInput.value) documentFileInput.value.value = '';
}

async function onDocumentFileChange(e) {
    const file = e.target?.files?.[0];
    if (!file) return;
    documentUploading.value = true;
    validationError.value = '';
    try {
        const formData = new FormData();
        formData.append('file', file);
        const { data } = await window.axios.post('/books/upload-document', formData, {
            headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
        });
        const resData = data?.data ?? data;
        if (resData?.url) {
            props.form.file_url = resData.url;
            uploadedFileName.value = file.name;
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.errors?.file?.[0] || 'Không thể tải file.';
        validationError.value = msg;
    }
    documentUploading.value = false;
}

// ——— Ảnh bìa ———
const onCoverChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        props.form.image = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

const fieldLabels = { published_year: 'Năm xuất bản', total_pages: 'Số trang', volume_number: 'Tập số', quantity: 'Số lượng', price: 'Giá' };
const validateNonNegative = (field) => {
    const val = parseFloat(props.form[field]);
    if (!isNaN(val) && val < 0) validationError.value = `${fieldLabels[field] || field} không được âm.`;
    else if (validationError.value?.includes(fieldLabels[field])) validationError.value = '';
};

const hasNegativeValues = computed(() => {
    return ['published_year', 'total_pages', 'volume_number', 'quantity', 'price'].some(f => {
        const val = parseFloat(props.form[f]);
        return !isNaN(val) && val < 0;
    });
});

const modalPanelRef = ref(null);
let savedActiveElement = null;

function onEscape(e) {
    if (e.key !== 'Escape' || !props.show || showConfirm.value) return;
    e.preventDefault();
    emit('close');
}

watch(() => props.show, (val) => {
    if (val) {
        coverPreview.value = null;
        showConfirm.value = false;
        validationError.value = '';
        uploadedFileName.value = '';
        savedActiveElement = document.activeElement;
        document.body.classList.add('overflow-hidden');
        nextTick(() => modalPanelRef.value?.focus());
        document.addEventListener('keydown', onEscape);
    } else {
        document.body.classList.remove('overflow-hidden');
        document.removeEventListener('keydown', onEscape);
        if (savedActiveElement && typeof savedActiveElement.focus === 'function') {
            savedActiveElement.focus();
        }
        savedActiveElement = null;
    }
});

const handleSubmit = () => {
    if (hasNegativeValues.value) {
        validationError.value = 'Các trường số không được nhập giá trị âm.';
        return;
    }
    validationError.value = '';
    showConfirm.value = true;
};

const confirmSubmit = () => { showConfirm.value = false; emit('submit'); };
const cancelConfirm = () => { showConfirm.value = false; };

onUnmounted(() => {
    document.removeEventListener('keydown', onEscape);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div @click="emit('close')" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

            <div
                ref="modalPanelRef"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                aria-labelledby="book-form-title"
                class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200 border border-slate-200 dark:border-slate-700 outline-none"
            >
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center shrink-0 bg-white dark:bg-slate-900 sticky top-0 z-20">
                    <h3 id="book-form-title" class="text-base font-semibold text-slate-800 dark:text-white">
                        {{ isEditing ? 'Cập nhật tài liệu' : 'Thêm tài liệu mới' }}
                    </h3>
                    <button type="button" @click="emit('close')" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 min-touch" aria-label="Đóng">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto p-5 space-y-5">

                    <!-- ⚠️ Validation Error Banner -->
                    <Transition name="error-slide">
                        <div v-if="validationError" class="flex items-center gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <Icon icon="lucide:alert-circle" class="w-4 h-4 text-red-500 shrink-0" />
                            <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ validationError }}</p>
                            <button @click="validationError = ''" class="ml-auto shrink-0 p-0.5 hover:bg-red-100 dark:hover:bg-red-800/30 rounded transition-colors">
                                <Icon icon="lucide:x" class="w-3 h-3 text-red-400" />
                            </button>
                        </div>
                    </Transition>

                    <!-- Section 1: Thông tin cơ bản -->
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 p-5">
                        <h4 class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-4">Thông tin định danh</h4>
                        <div class="relative">

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                                <!-- Cover upload -->
                                <div class="lg:col-span-4 flex flex-col items-center lg:items-start">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Ảnh bìa</label>
                                    <div class="relative w-full max-w-[200px]">
                                        <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-2 text-center hover:border-slate-400 dark:hover:border-slate-500 cursor-pointer relative aspect-[3/4] flex flex-col items-center justify-center bg-white dark:bg-slate-800/50 overflow-hidden">
                                            <input type="file" @change="onCoverChange" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*" />
                                            <img v-if="coverPreview || form.image_url" :src="coverPreview || form.image_url" class="absolute inset-0 w-full h-full object-cover rounded-lg" />
                                            <template v-else>
                                                <Icon icon="lucide:image-plus" class="w-10 h-10 text-slate-400 mb-2" />
                                                <p class="text-xs text-slate-500">Ảnh bìa</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Basic fields -->
                                <div class="lg:col-span-8 space-y-7">
                                    <!-- Title -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Nhan đề <span class="text-rose-500">*</span></label>
                                        <Input v-model="form.title" placeholder="Nhập tên tài liệu / sách..." class="h-10 rounded-lg text-sm border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
                                    </div>

                                    <!-- Tác giả (nhập tay, có thể nhiều tên) -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Tác giả chính <span class="text-rose-500">*</span></label>
                                        <Input
                                            v-model="form.author"
                                            placeholder="Nhập tên tác giả chính..."
                                            class="h-10 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"
                                        />
                                        <div class="flex gap-2 mt-1">
                                            <Input v-model="form.co_authors" placeholder="Đồng tác giả (cách nhau bởi dấu phẩy)" class="h-9 flex-1 rounded-lg text-sm border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Danh mục</label>
                                            <select v-model="form.category_id" class="w-full h-10 pl-3 pr-9 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white outline-none">
                                                <option :value="null">-- Chọn --</option>
                                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Mã phân loại</label>
                                            <Input v-model="form.classification_code" placeholder="VD: VH-001" class="h-10 rounded-lg text-sm" />
                                        </div>
                                    </div>
                                    <!-- Nhóm + Loại (chỉ khi thêm mới) -->
                                    <div v-if="!isEditing" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Nhóm tài liệu <span class="text-rose-500">*</span></label>
                                            <select v-model="form.group" class="w-full h-10 pl-3 pr-9 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white outline-none">
                                                <option v-for="opt in groupOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Loại <span class="text-rose-500">*</span></label>
                                            <select v-model="form.type" class="w-full h-10 pl-3 pr-9 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white outline-none">
                                                <option v-for="t in bookTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Tài liệu số: upload file -->
                                    <div v-if="form.group === 'digital'" class="space-y-1.5">
                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">File tài liệu <span class="text-rose-500">*</span></label>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <input
                                                ref="documentFileInput"
                                                type="file"
                                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.epub"
                                                class="hidden"
                                                @change="onDocumentFileChange"
                                            />
                                            <Button type="button" variant="outline" size="sm" class="rounded-lg" :disabled="documentUploading" @click="documentFileInput?.click()">
                                                <Icon v-if="documentUploading" icon="lucide:loader-2" class="w-4 h-4 animate-spin mr-1.5" />
                                                <Icon v-else icon="lucide:upload" class="w-4 h-4 mr-1.5" />
                                                {{ documentUploading ? 'Đang tải lên...' : 'Chọn file tải lên' }}
                                            </Button>
                                            <span v-if="uploadedFileName" class="text-sm text-slate-600 dark:text-slate-300 truncate max-w-[200px]">{{ uploadedFileName }}</span>
                                            <Button v-if="form.file_url || uploadedFileName" type="button" variant="ghost" size="sm" class="text-rose-500 hover:text-rose-600" @click="clearDocumentFile">
                                                <Icon icon="lucide:x" class="w-4 h-4" />
                                            </Button>
                                        </div>
                                        <p class="text-[11px] text-slate-400">PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, EPUB — tối đa 50MB</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Luận văn: Khóa, Khoa, Lớp -->
                    <div v-if="form.group === 'thesis'" class="bg-amber-50/50 dark:bg-amber-900/10 p-6 rounded-[24px] border border-amber-200/50 dark:border-amber-800/30">
                        <div class="flex items-center gap-2 mb-4">
                            <Icon icon="lucide:graduation-cap" class="w-5 h-5 text-amber-600 shrink-0" />
                            <h4 class="text-[12px] font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Khóa · Khoa · Lớp</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Khóa <span class="text-rose-500">*</span></label>
                                <select v-model="form.cohort" class="w-full h-11 px-4 rounded-xl text-[14px] border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-amber-500/20">
                                    <option value="">-- Chọn khóa --</option>
                                    <option v-for="c in cohorts" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Khoa <span class="text-rose-500">*</span></label>
                                <select v-model="form.faculty_id" class="w-full h-11 px-4 rounded-xl text-[14px] border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-amber-500/20" @change="form.department_id = null">
                                    <option :value="null">-- Chọn khoa --</option>
                                    <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Lớp <span class="text-rose-500">*</span></label>
                                <select v-model="form.department_id" class="w-full h-11 px-4 rounded-xl text-[14px] border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-amber-500/20">
                                    <option :value="null">-- Chọn lớp --</option>
                                    <option v-for="d in departmentsByFaculty" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 dark:border-slate-800"></div>

                    <!-- Nhà xuất bản + Năm (nơi XB gộp: nhà xuất bản đã bao gồm thông tin địa điểm) -->
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 p-5">
                        <h4 class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-3">Nhà xuất bản</h4>
                        <div class="relative mb-3">
                            <Input
                                v-model="form.publisher"
                                placeholder="Nhập tên nhà xuất bản..."
                                class="h-10 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] text-slate-500 dark:text-slate-400 mb-1">Năm xuất bản</label>
                                <Input v-model="form.published_year" type="number" min="0" placeholder="2024" @blur="validateNonNegative('published_year')" class="h-9 rounded-lg text-sm [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-500 dark:text-slate-400 mb-1">Nơi XB (tùy chọn)</label>
                                <Input v-model="form.publication_place" placeholder="VD: Hà Nội" class="h-9 rounded-lg text-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 dark:border-slate-800"></div>

                    <!-- Đặc điểm vật lý & Giá -->
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 p-5">
                        <h4 class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-3">Số trang · Khổ · Tập · Số lượng · Giá</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            <div><label class="block text-[11px] text-slate-500 mb-1">Số trang</label><Input v-model="form.total_pages" type="number" min="0" placeholder="0" @blur="validateNonNegative('total_pages')" class="h-9 rounded-lg text-sm w-full [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" /></div>
                            <div><label class="block text-[11px] text-slate-500 mb-1">Khổ</label><Input v-model="form.book_size" placeholder="24cm" class="h-9 rounded-lg text-sm w-full" /></div>
                            <div><label class="block text-[11px] text-slate-500 mb-1">Tập</label><Input v-model="form.volume_number" type="number" min="0" placeholder="0" @blur="validateNonNegative('volume_number')" class="h-9 rounded-lg text-sm w-full [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" /></div>
                            <div><label class="block text-[11px] text-slate-500 mb-1">Số lượng <span class="text-rose-500">*</span></label><Input v-model="form.quantity" type="number" min="0" placeholder="0" @blur="validateNonNegative('quantity')" class="h-9 rounded-lg text-sm w-full [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" /></div>
                            <div><label class="block text-[11px] text-slate-500 mb-1">Giá (VNĐ)</label><Input v-model="form.price" type="number" min="0" placeholder="0" @blur="validateNonNegative('price')" class="h-9 rounded-lg text-sm w-full [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" /></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                            <div class="space-y-1.5">
                                <label class="block text-[11px] text-slate-500 dark:text-slate-400">Kho sách</label>
                                <select v-model="form.warehouse_id" class="w-full h-10 pl-3 pr-9 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white outline-none">
                                    <option :value="null">-- Chọn kho --</option>
                                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.code }} - {{ w.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[11px] text-slate-500 dark:text-slate-400">Vị trí kệ (VD: K1-A1)</label>
                                <Input v-model="form.shelf" placeholder="VD: K1-A1" class="h-10 rounded-lg text-sm border-slate-200 dark:border-slate-700 dark:bg-slate-800" />
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 dark:border-slate-800"></div>

                    <!-- Section 4: Ghi chú -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ghi chú</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:ring-1 focus:ring-blue-500/30 dark:text-white resize-none"
                            placeholder="Ghi chú thêm..."
                        ></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-white dark:bg-slate-900 shrink-0">
                    <Button @click="emit('close')" variant="outline" size="sm" class="rounded-lg">Hủy</Button>
                    <Button @click="handleSubmit" size="sm" class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
                        {{ isEditing ? 'Lưu thay đổi' : 'Thêm tài liệu' }}
                    </Button>
                </div>
            </div>

            <!-- Confirmation Dialog -->
            <Transition name="confirm-fade">
                <div v-if="showConfirm" class="absolute inset-0 z-[110] flex items-center justify-center p-4">
                    <div @click="cancelConfirm" class="absolute inset-0 bg-black/30"></div>
                    <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl w-full max-w-sm p-5 animate-in zoom-in-95 duration-150">
                        <div class="text-center space-y-3">
                            <!-- Icon -->
                            <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center" :class="isEditing ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-blue-100 dark:bg-blue-900/30'">
                                <Icon :icon="isEditing ? 'lucide:save' : 'lucide:book-plus'" class="w-6 h-6" :class="isEditing ? 'text-amber-600' : 'text-blue-600'" />
                            </div>

                            <!-- Title -->
                            <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                                {{ isEditing ? 'Xác nhận lưu thay đổi?' : 'Xác nhận thêm sách mới?' }}
                            </h4>

                            <!-- Description -->
                            <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                                <template v-if="form.title">
                                    {{ isEditing ? 'Bạn có muốn lưu thay đổi cho' : 'Bạn có muốn thêm sách' }}
                                    "<strong class="text-gray-700 dark:text-slate-300">{{ form.title }}</strong>"
                                    {{ isEditing ? 'không?' : 'vào hệ thống không?' }}
                                </template>
                                <template v-else>
                                    {{ isEditing ? 'Bạn có muốn lưu thay đổi không?' : 'Bạn có muốn thêm sách mới vào hệ thống không?' }}
                                </template>
                            </p>

                            <!-- Buttons -->
                            <div class="flex gap-2 pt-1">
                                <Button variant="outline" @click="cancelConfirm" class="flex-1 h-8 rounded-lg text-xs font-medium">
                                    Hủy
                                </Button>
                                <Button @click="confirmSubmit" class="flex-1 h-8 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">
                                    <Icon icon="lucide:check" class="w-3.5 h-3.5 mr-1" /> Xác nhận
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<style scoped>
.error-slide-enter-active,
.error-slide-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.error-slide-enter-from,
.error-slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
.confirm-fade-enter-active,
.confirm-fade-leave-active {
    transition: opacity 0.15s ease;
}
.confirm-fade-enter-from,
.confirm-fade-leave-to {
    opacity: 0;
}
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.2s ease-out;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
