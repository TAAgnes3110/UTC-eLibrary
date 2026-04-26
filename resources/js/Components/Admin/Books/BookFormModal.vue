<script setup>
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    show: { type: Boolean, required: true },
    isEditing: { type: Boolean, required: true },
    form: { type: Object, required: true },
    classifications: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    cabinetOptions: { type: Array, default: () => [] },
    storageSuggestionLoading: { type: Boolean, default: false },
    storageSuggestionMessage: { type: String, default: '' },
    createCoverPreviewUrl: { type: String, default: '' },
    setCreateCoverFile: { type: Function, default: () => () => {} },
    clearCreateCoverFile: { type: Function, default: () => () => {} },
    saveLoading: { type: Boolean, default: false },
    fieldErrors: { type: Object, default: () => ({}) },
    clearFieldError: { type: Function, default: () => () => {} },
});

const emit = defineEmits(['close', 'save', 'book-code-touched', 'registration-touched']);
const currentYear = new Date().getFullYear();

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
    createCoverFileName.value = file ? String(file.name || '') : '';
    props.setCreateCoverFile(file);
}

const createCoverFileName = ref('');

function removeCreateCover() {
    createCoverFileName.value = '';
    props.clearCreateCoverFile();
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="emit('close')" />
            <div
                class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800"
            >
                <div
                    class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50"
                >
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ isEditing ? 'Chỉnh sửa sách' : 'Thêm sách mới' }}
                    </h3>
                    <button
                        type="button"
                        class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        @click="emit('close')"
                    >
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="px-6 pb-6 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <p v-if="fieldErrors.general" class="sm:col-span-2 text-xs text-red-500 font-medium">
                        {{ fieldErrors.general }}
                    </p>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Phân loại sách <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.classification"
                            list="book-classification-options"
                            class="w-full h-10 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            :class="errClass('classification')"
                            placeholder="Gõ mã / tên phân loại, ví dụ: 624 / 624.2"
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
                            Tên sách <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.title"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('title')"
                            placeholder="Nhập tên sách"
                            @update:model-value="clearFieldError('title')"
                        />
                        <p v-if="fieldErrors.title" class="text-xs text-red-500 font-medium">{{ fieldErrors.title }}</p>
                    </div>
                    <div class="space-y-1.5">
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
                            <option value="Tài liệu số" />
                        </datalist>
                        <p v-if="fieldErrors.resource_type" class="text-xs text-red-500 font-medium">
                            {{ fieldErrors.resource_type }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="sm:col-span-2 space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                    <div class="space-y-1.5">
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
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ảnh bìa (tùy chọn)</label>
                        <div class="rounded-lg border border-dashed border-slate-300 dark:border-slate-700 p-3 bg-slate-50/60 dark:bg-slate-800/40">
                            <div class="flex flex-wrap items-center gap-2">
                                <input
                                    id="book-create-cover-upload"
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
                                    {{ createCoverFileName || 'Chưa chọn ảnh' }}
                                </span>
                            </div>
                            <div v-if="createCoverPreviewUrl" class="mt-3 flex items-center gap-3 rounded-md border border-slate-200 dark:border-slate-700 bg-white/70 dark:bg-slate-900/40 p-2">
                                <img :src="createCoverPreviewUrl" alt="Ảnh bìa xem trước" class="h-16 w-12 rounded object-cover ring-1 ring-slate-200 dark:ring-slate-700" />
                                <Button type="button" variant="outline" size="sm" @click="removeCreateCover">Bỏ ảnh</Button>
                            </div>
                        </div>
                    </div>
                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tóm tắt nội dung</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg border bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white px-3 py-2 resize-y"
                            :class="errClass('description')"
                            placeholder="Nhập mô tả ngắn về nội dung sách"
                            @input="clearFieldError('description')"
                        />
                        <p v-if="fieldErrors.description" class="text-xs text-red-500 font-medium">{{ fieldErrors.description }}</p>
                    </div>
                    <p v-if="storageSuggestionMessage" class="sm:col-span-2 text-xs font-medium text-amber-600 dark:text-amber-400">
                        {{ storageSuggestionMessage }}
                    </p>
                    <p v-else-if="form.cabinet || form.warehouse" class="sm:col-span-2 text-xs text-slate-500 dark:text-slate-400">
                        Vị trí đang chọn:
                        <template v-if="form.warehouse">{{ form.warehouse }} · </template>
                        {{ form.cabinet || '—' }}
                    </p>
                </div>
                <div
                    class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30"
                >
                    <Button variant="outline" :disabled="saveLoading" @click="emit('close')">Hủy bỏ</Button>
                    <Button class="bg-blue-600 hover:bg-blue-700 text-white" :disabled="saveLoading" @click="emit('save')">
                        {{ saveLoading ? 'Đang lưu…' : isEditing ? 'Cập nhật' : 'Lưu' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
