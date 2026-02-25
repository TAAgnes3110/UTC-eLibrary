<script setup>
import { ref, watch, computed } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Icon } from '@iconify/vue';

const props = defineProps({
    show: Boolean,
    form: Object,
    isEditing: Boolean,
    categories: { type: Array, default: () => [] },
    bookTypes: { type: Array, default: () => [
        { value: 'book', label: 'Sách' },
        { value: 'thesis', label: 'Khóa luận/Đồ án' },
        { value: 'dissertation', label: 'Luận văn/Luận án' },
        { value: 'research', label: 'Nghiên cứu khoa học' },
        { value: 'magazine', label: 'Tạp chí' },
        { value: 'other', label: 'Tài liệu khác' },
    ]},
});

const emit = defineEmits(['close', 'submit']);

const showConfirm = ref(false);
const validationError = ref('');

// Field labels for error messages
const fieldLabels = {
    published_year: 'Năm xuất bản',
    total_pages: 'Số trang',
    volume_number: 'Tập số',
    quantity: 'Số lượng',
    price: 'Giá',
};

watch(() => props.show, (val) => {
    if (val) {
        coverPreview.value = null;
        showConfirm.value = false;
        validationError.value = '';
    }
});

const coverPreview = ref(null);

const onCoverChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        props.form.image = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

// Validate non-negative on blur — show error if negative
const validateNonNegative = (field) => {
    const val = parseFloat(props.form[field]);
    if (!isNaN(val) && val < 0) {
        validationError.value = `${fieldLabels[field] || field} không được nhập giá trị âm!`;
    } else {
        // Clear error if this field is now valid
        if (validationError.value.includes(fieldLabels[field] || field)) {
            validationError.value = '';
        }
    }
};

// Check all numeric fields for negative values
const hasNegativeValues = computed(() => {
    const numericFields = ['published_year', 'total_pages', 'volume_number', 'quantity', 'price'];
    return numericFields.some(f => {
        const val = parseFloat(props.form[f]);
        return !isNaN(val) && val < 0;
    });
});

const inputClass = 'h-9 rounded-lg text-sm border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none';

// Handle submit with validation + confirmation
const handleSubmit = () => {
    if (hasNegativeValues.value) {
        validationError.value = 'Vui lòng kiểm tra lại! Các trường số không được nhập giá trị âm.';
        return;
    }
    validationError.value = '';
    showConfirm.value = true;
};

const confirmSubmit = () => {
    showConfirm.value = false;
    emit('submit');
};

const cancelConfirm = () => {
    showConfirm.value = false;
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div @click="emit('close')" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="px-8 py-5 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center shrink-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-20">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-[18px] bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-xl shadow-blue-500/20">
                            <Icon :icon="isEditing ? 'lucide:file-text' : 'lucide:book-plus'" class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900 dark:text-white leading-tight tracking-tight">
                                {{ isEditing ? 'Cấu hình tài liệu' : 'Thêm tài liệu mới' }}
                            </h3>
                            <p class="text-[12px] text-slate-400 font-semibold uppercase tracking-widest mt-0.5">Hệ thống quản lý thư viện số</p>
                        </div>
                    </div>
                    <button @click="emit('close')" class="w-10 h-10 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full flex items-center justify-center transition-all group active:scale-90">
                        <Icon icon="lucide:x" class="w-5 h-5 text-slate-400 group-hover:text-rose-500 transition-colors" />
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
                    <div class="bg-white dark:bg-slate-900/40 relative group/section">
                        <!-- Subtle Background Glow -->
                        <div class="absolute -inset-4 bg-blue-500/5 rounded-[40px] opacity-0 group-hover/section:opacity-100 transition-opacity duration-700 pointer-events-none"></div>

                        <div class="relative">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-1.5 h-6 bg-gradient-to-b from-blue-400 to-blue-600 rounded-full shadow-[0_0_15px_rgba(37,99,235,0.4)]"></div>
                                <h4 class="text-[14px] font-black text-slate-800 dark:text-white uppercase tracking-[0.15em]">Thông tin định danh</h4>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                                <!-- Cover upload -->
                                <div class="lg:col-span-4 flex flex-col items-center lg:items-start">
                                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4 ml-1">Ảnh đại diện tài liệu</label>
                                    <div class="relative group/upload w-full max-w-[240px]">
                                        <div class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[32px] p-2 text-center hover:border-blue-500 hover:bg-blue-50/20 dark:hover:bg-blue-900/10 transition-all cursor-pointer relative aspect-[3/4.2] flex flex-col items-center justify-center bg-slate-50/50 dark:bg-slate-800/20 group-hover/upload:shadow-[0_20px_40px_-15px_rgba(59,130,246,0.3)] transition-all duration-700 overflow-hidden group-hover/upload:-translate-y-2">
                                             <input type="file" @change="onCoverChange" class="absolute inset-0 opacity-0 cursor-pointer z-20" accept="image/*" />
                                             <img v-if="coverPreview || form.image_url" :src="coverPreview || form.image_url" class="absolute inset-0 w-full h-full rounded-[28px] object-cover shadow-2xl transition-transform duration-1000 group-hover/upload:scale-110" />
                                             <template v-else>
                                                 <div class="w-20 h-20 rounded-[28px] bg-white dark:bg-slate-800 shadow-2xl shadow-blue-500/10 flex items-center justify-center mb-5 transition-all duration-500 group-hover/upload:bg-blue-600 group-hover/upload:text-white group-hover/upload:scale-110 group-hover/upload:rotate-3">
                                                     <Icon icon="lucide:cloud-upload" class="w-8 h-8 text-blue-500 group-hover/upload:text-white" />
                                                 </div>
                                                 <p class="text-[12px] font-black uppercase text-slate-600 dark:text-slate-400 tracking-widest">Tải ảnh lên</p>
                                                 <p class="text-[10px] text-slate-400 mt-2 font-bold px-4 leading-tight">Click hoặc kéo thả file vào đây</p>
                                             </template>

                                             <!-- Glassmorphism Overlay -->
                                             <div v-if="coverPreview || form.image_url" class="absolute inset-0 bg-gradient-to-t from-blue-900/90 via-blue-900/20 to-transparent opacity-0 group-hover/upload:opacity-100 transition-all duration-500 flex flex-col items-center justify-end pb-10 z-10">
                                                <div class="w-14 h-14 rounded-full bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center mb-4 transform translate-y-4 group-hover/upload:translate-y-0 transition-transform duration-500">
                                                     <Icon icon="lucide:refresh-cw" class="w-7 h-7 text-white" />
                                                </div>
                                                <span class="text-[11px] font-black text-white uppercase tracking-widest transform translate-y-4 group-hover/upload:translate-y-0 transition-transform duration-500">Thay đổi hình ảnh</span>
                                             </div>
                                         </div>
                                    </div>
                                </div>

                                <!-- Basic fields -->
                                <div class="lg:col-span-8 space-y-7">
                                    <!-- Title -->
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-end px-1">
                                            <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Nhan đề tài liệu / Sách <span class="text-rose-500">*</span></label>
                                            <span v-if="form.title.length > 0" class="text-[10px] font-bold text-blue-500 uppercase">{{ form.title.length }} ký tự</span>
                                        </div>
                                        <Input v-model="form.title" placeholder="Nhập tên chính xác của tài liệu..." class="h-14 rounded-[22px] text-[16px] border-slate-200 dark:border-slate-800 dark:bg-slate-900/80 shadow-sm focus:ring-8 focus:ring-blue-500/10 transition-all font-bold placeholder:text-slate-300 dark:placeholder:text-slate-600" />
                                    </div>

                                    <!-- Author -->
                                    <div class="space-y-3">
                                        <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Tác giả biên soạn chính</label>
                                        <div class="relative group">
                                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-[16px] bg-slate-100/80 dark:bg-slate-800 flex items-center justify-center transition-all group-focus-within:bg-blue-600 group-focus-within:text-white group-focus-within:rotate-3 shadow-sm">
                                                <Icon icon="lucide:user-check" class="w-6 h-6 text-slate-500 group-focus-within:text-white" />
                                            </div>
                                            <Input v-model="form.author" placeholder="Họ tên (Ví dụ: Nguyễn Nhật Ánh)" class="h-14 pl-18 rounded-[22px] text-[15px] border-slate-200 dark:border-slate-800 dark:bg-slate-900/80 shadow-sm font-bold transition-all focus:ring-8 focus:ring-blue-500/10" />
                                        </div>
                                    </div>

                                    <!-- Type + Category -->
                                    <div class="grid grid-cols-2 gap-6">
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Hình thức</label>
                                            <div class="relative group">
                                                <select v-model="form.type" class="w-full h-14 pl-6 pr-12 text-[15px] font-bold bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-[22px] focus:ring-8 focus:ring-blue-500/10 dark:text-white outline-none appearance-none transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800">
                                                    <option v-for="t in bookTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                                </select>
                                                <Icon icon="lucide:layers" class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none group-focus-within:text-blue-500 transition-colors" />
                                            </div>
                                        </div>
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Lĩnh vực quản lý</label>
                                            <div class="relative group">
                                                <select v-model="form.category_id" class="w-full h-14 pl-6 pr-12 text-[15px] font-bold bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-[22px] focus:ring-8 focus:ring-blue-500/10 dark:text-white outline-none appearance-none transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800">
                                                    <option value="">-- Chọn lĩnh vực --</option>
                                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                </select>
                                                <Icon icon="lucide:tag" class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none group-focus-within:text-blue-500 transition-colors" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 dark:border-slate-800"></div>

                    <!-- Section 2: Xuất bản -->
                    <div class="bg-slate-50/50 dark:bg-slate-800/20 p-6 rounded-[24px] border border-slate-100 dark:border-slate-800/60">
                         <div class="flex items-center gap-2 mb-5">
                            <Icon icon="lucide:building-2" class="w-5 h-5 text-indigo-500" />
                            <h4 class="text-[12px] font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Xuất bản & Phân phối</h4>
                        </div>
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-5">
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Nhà xuất bản</label>
                                <Input v-model="form.publisher" placeholder="Ví dụ: NXB Giáo dục..." class="h-11 rounded-[16px] text-[14px] border-slate-200 dark:border-slate-800 dark:bg-slate-900 font-medium" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Nơi xuất bản</label>
                                <Input v-model="form.publication_place" placeholder="Ví dụ: Hà Nội..." class="h-11 rounded-[16px] text-[14px] border-slate-200 dark:border-slate-800 dark:bg-slate-900 font-medium" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Năm xuất bản</label>
                                <Input v-model="form.published_year" type="number" min="0" placeholder="2024" @blur="validateNonNegative('published_year')" class="h-11 rounded-[16px] text-[14px] border-slate-200 dark:border-slate-800 dark:bg-slate-900 font-bold [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 dark:border-slate-800"></div>

                    <!-- Section 3: Mô tả vật lý & Giá -->
                    <div class="bg-indigo-50/20 dark:bg-indigo-900/5 p-6 rounded-[24px] border border-indigo-100/50 dark:border-indigo-900/10">
                        <div class="flex items-center gap-2 mb-5">
                            <Icon icon="lucide:file-text" class="w-5 h-5 text-blue-500" />
                            <h4 class="text-[12px] font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Đặc điểm vật lý & Giá thành</h4>
                        </div>
                        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Số trang</label>
                                <Input v-model="form.total_pages" type="number" min="0" placeholder="0" @blur="validateNonNegative('total_pages')" class="h-11 rounded-[16px] text-[14px] border-slate-200 dark:border-slate-800 dark:bg-slate-900 font-bold" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Khổ sách</label>
                                <Input v-model="form.book_size" placeholder="Ví dụ: 24cm" class="h-11 rounded-[16px] text-[14px] border-slate-200 dark:border-slate-800 dark:bg-slate-900 font-medium" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Tập số</label>
                                <Input v-model="form.volume_number" type="number" min="0" placeholder="0" @blur="validateNonNegative('volume_number')" class="h-11 rounded-[16px] text-[14px] border-slate-200 dark:border-slate-800 dark:bg-slate-900 font-bold" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Số lượng</label>
                                <Input v-model="form.quantity" type="number" min="0" placeholder="0" @blur="validateNonNegative('quantity')" class="h-11 rounded-[16px] text-[14px] border-slate-200 dark:border-slate-800 dark:bg-slate-900 font-bold text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Giá tài liệu</label>
                                <div class="relative group">
                                    <Input v-model="form.price" type="number" min="0" placeholder="0" @blur="validateNonNegative('price')" class="h-11 pr-12 rounded-[16px] text-[14px] border-slate-200 dark:border-slate-800 dark:bg-slate-900 font-bold text-emerald-600 dark:text-emerald-400" />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[11px] font-extrabold text-slate-400 uppercase">vnđ</span>
                                </div>
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
                <div class="px-8 py-6 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 shrink-0">
                    <div class="hidden sm:flex items-center gap-2 opacity-50">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                        <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-[0.2em]">Live Editor Sync</p>
                    </div>
                    <div class="flex gap-4 ml-auto w-full sm:w-auto">
                        <Button @click="emit('close')" variant="ghost" class="flex-1 sm:flex-none h-12 rounded-[20px] px-8 text-[14px] font-extrabold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                            Hủy bỏ
                        </Button>
                        <Button @click="handleSubmit" class="flex-1 sm:flex-none h-12 rounded-[20px] px-10 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-[14px] font-extrabold shadow-xl shadow-blue-500/30 transition-all hover:scale-[1.03] active:scale-95 flex items-center justify-center gap-2">
                            <Icon :icon="isEditing ? 'lucide:save-all' : 'lucide:plus-circle'" class="w-5 h-5" />
                            {{ isEditing ? 'Cập nhật tài liệu' : 'Xác nhận thêm mới' }}
                        </Button>
                    </div>
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
.confirm-fade-enter-active,
.confirm-fade-leave-active {
    transition: opacity 0.15s ease;
}
.confirm-fade-enter-from,
.confirm-fade-leave-to {
    opacity: 0;
}
</style>
