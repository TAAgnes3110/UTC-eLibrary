<script setup>
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    show: { type: Boolean, required: true },
    isEditing: { type: Boolean, required: true },
    form: { type: Object, required: true },
    classifications: { type: Array, default: () => [] },
    classificationDetails: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    saveLoading: { type: Boolean, default: false },
    fieldErrors: { type: Object, default: () => ({}) },
    clearFieldError: { type: Function, default: () => () => {} },
});

const emit = defineEmits(['close', 'save']);

function errClass(key) {
    return props.fieldErrors[key] ? 'border-red-500 dark:border-red-500' : 'border-slate-200 dark:border-slate-700';
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
                <div class="px-6 pb-6 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <p v-if="fieldErrors.general" class="sm:col-span-2 text-xs text-red-500 font-medium">
                        {{ fieldErrors.general }}
                    </p>
                    <div class="sm:col-span-2 space-y-1.5">
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
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã sách</label>
                        <Input
                            v-model="form.book_code"
                            class="h-10 rounded-lg font-mono dark:bg-slate-800"
                            :class="errClass('book_code')"
                            placeholder="Mã sách trong hệ thống"
                            @update:model-value="clearFieldError('book_code')"
                        />
                        <p v-if="fieldErrors.book_code" class="text-xs text-red-500 font-medium">{{ fieldErrors.book_code }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số đăng ký cá biệt (DKCB)</label>
                        <Input
                            v-model="form.registration_number"
                            class="h-10 rounded-lg font-mono dark:bg-slate-800"
                            :class="errClass('registration_number')"
                            placeholder="Mã DKCB"
                            @update:model-value="clearFieldError('registration_number')"
                        />
                        <p v-if="fieldErrors.registration_number" class="text-xs text-red-500 font-medium">
                            {{ fieldErrors.registration_number }}
                        </p>
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
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Nhà xuất bản</label>
                        <Input
                            v-model="form.publisher"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('publisher')"
                            placeholder="NXB"
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
                            max="2100"
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
                            Phân loại chi tiết <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.classification_detail"
                            list="book-classification-detail-options"
                            class="w-full h-10 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            :class="errClass('classification_detail')"
                            placeholder="Gõ mã / tên phân loại chi tiết"
                            @input="clearFieldError('classification_detail')"
                        />
                        <datalist id="book-classification-detail-options">
                            <option
                                v-for="d in classificationDetails"
                                :key="d.id"
                                :value="d.code && d.name ? `${d.code} – ${d.name}` : (d.name || d.code || '')"
                            />
                        </datalist>
                        <p v-if="fieldErrors.classification_detail" class="text-xs text-red-500 font-medium">
                            {{ fieldErrors.classification_detail }}
                        </p>
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
                            placeholder="Gõ mã / tên kho, ví dụ: Thư viện Trung tâm UTC"
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
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Số lượng bản in <span class="text-rose-500">*</span>
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
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Giá tiền (đ)</label>
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
                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mô tả / tóm tắt</label>
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
                    <p v-if="fieldErrors.resource_kind" class="sm:col-span-2 text-xs text-red-500 font-medium">
                        {{ fieldErrors.resource_kind }}
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
