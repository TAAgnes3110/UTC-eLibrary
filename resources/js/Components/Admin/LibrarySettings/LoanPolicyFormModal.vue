<script setup>
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';

const props = defineProps({
    show: { type: Boolean, required: true },
    form: { type: Object, required: true },
    fieldErrors: { type: Object, default: () => ({}) },
    clearFieldError: { type: Function, default: () => () => {} },
    saving: { type: Boolean, default: false },
    isExternalPolicy: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save']);

function errClass(key) {
    return props.fieldErrors[key]
        ? 'border border-red-500 dark:border-red-500'
        : 'border border-slate-200 dark:border-slate-700';
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="emit('close')" />
            <div
                class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800"
                role="dialog"
                aria-modal="true"
                aria-labelledby="loan-policy-modal-title"
            >
                <div
                    class="sticky top-0 px-4 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 z-10"
                >
                    <h3 id="loan-policy-modal-title" class="text-base font-bold text-slate-900 dark:text-white pr-2">
                        Chỉnh sửa quy định mượn
                    </h3>
                    <button
                        type="button"
                        class="min-h-11 min-w-11 inline-flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        @click="emit('close')"
                    >
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <p v-if="fieldErrors.general" class="sm:col-span-2 text-xs text-red-500 font-medium">
                        {{ fieldErrors.general }}
                    </p>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã cấu hình</label>
                        <Input
                            v-model="form.code"
                            class="h-11 rounded-lg font-mono dark:bg-slate-800"
                            :class="errClass('code')"
                            @update:model-value="clearFieldError('code')"
                        />
                        <p v-if="fieldErrors.code" class="text-xs text-red-500 font-medium">{{ fieldErrors.code }}</p>
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tên hiển thị</label>
                        <Input
                            v-model="form.name"
                            class="h-11 rounded-lg dark:bg-slate-800"
                            :class="errClass('name')"
                            @update:model-value="clearFieldError('name')"
                        />
                        <p v-if="fieldErrors.name" class="text-xs text-red-500 font-medium">{{ fieldErrors.name }}</p>
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Đối tượng (user_type)</label>
                        <Input
                            v-model="form.user_type"
                            disabled
                            class="h-11 rounded-lg font-mono opacity-80 dark:bg-slate-800"
                            :class="errClass('user_type')"
                        />
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Không đổi mã đối tượng tại đây — liên kết với thẻ và hệ thống.
                        </p>
                        <p v-if="fieldErrors.user_type" class="text-xs text-red-500 font-medium">{{ fieldErrors.user_type }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tối đa sách (tổng / trần an toàn)</label>
                        <Input
                            v-model.number="form.max_books"
                            type="number"
                            min="0"
                            class="h-11 rounded-lg dark:bg-slate-800"
                            :class="errClass('max_books')"
                            @update:model-value="clearFieldError('max_books')"
                        />
                        <p v-if="fieldErrors.max_books" class="text-xs text-red-500 font-medium">{{ fieldErrors.max_books }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Hạn mượn (ngày)</label>
                        <Input
                            v-model.number="form.max_days"
                            type="number"
                            min="0"
                            class="h-11 rounded-lg dark:bg-slate-800"
                            :class="errClass('max_days')"
                            @update:model-value="clearFieldError('max_days')"
                        />
                        <p v-if="fieldErrors.max_days" class="text-xs text-red-500 font-medium">{{ fieldErrors.max_days }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Gia hạn tối đa (lần)</label>
                        <Input
                            v-model.number="form.max_renewals"
                            type="number"
                            min="0"
                            class="h-11 rounded-lg dark:bg-slate-800"
                            :class="errClass('max_renewals')"
                            @update:model-value="clearFieldError('max_renewals')"
                        />
                        <p v-if="fieldErrors.max_renewals" class="text-xs text-red-500 font-medium">{{ fieldErrors.max_renewals }}</p>
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Phạt trễ hạn (đồng / ngày / cuốn)
                        </label>
                        <Input
                            v-model="form.overdue_fine_per_day"
                            type="text"
                            inputmode="decimal"
                            class="h-11 rounded-lg max-w-xs dark:bg-slate-800"
                            :class="errClass('overdue_fine_per_day')"
                            @update:model-value="clearFieldError('overdue_fine_per_day')"
                        />
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-prose">
                            Số tiền cho <span class="font-semibold text-slate-600 dark:text-slate-300">mỗi cuốn</span> mượn, tính theo
                            <span class="font-semibold text-slate-600 dark:text-slate-300">mỗi ngày</span> quá hạn, khi thư viện áp dụng phạt «theo đồng/ngày» (không áp dụng nếu hệ thống dùng cách tính theo % giá bìa). Ví dụ thường gặp: khoảng
                            <span class="font-semibold text-slate-700 dark:text-slate-200">1.000 đ</span>/ngày/cuốn; có thể đặt cao hơn (vd. 5.000 đ) tùy nhóm thẻ.
                        </p>
                        <p v-if="fieldErrors.overdue_fine_per_day" class="text-xs text-red-500 font-medium">
                            {{ fieldErrors.overdue_fine_per_day }}
                        </p>
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Phạt hư hỏng (% giá bìa / cuốn — mức 100% hư)
                        </label>
                        <div class="flex items-center gap-2 max-w-xs">
                            <Input
                                v-model="form.damage_fine_percent_pct"
                                type="text"
                                inputmode="decimal"
                                class="h-11 rounded-lg flex-1 dark:bg-slate-800"
                                :class="errClass('params.damage_fine_percent')"
                                @update:model-value="clearFieldError('params.damage_fine_percent')"
                            />
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-300 shrink-0">%</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-prose">
                            Phạt = giá bìa × % quy định × (% mức hư ÷ 100) / cuốn khi trả sách hư hỏng (vd. 10%, hư 50% → 5% giá bìa).
                        </p>
                    </div>

                    <div v-if="!isExternalPolicy" class="space-y-1.5 sm:col-span-2">
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-400">
                            Theo Nội quy Điều 5.2 — tối đa đồng thời theo loại (giáo trình / tài liệu tham khảo)
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tối đa giáo trình</label>
                                <Input
                                    v-model="form.max_textbooks"
                                    type="number"
                                    min="0"
                                    class="h-11 rounded-lg dark:bg-slate-800"
                                    @update:model-value="clearFieldError('params')"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tối đa tài liệu tham khảo</label>
                                <Input
                                    v-model="form.max_reference"
                                    type="number"
                                    min="0"
                                    class="h-11 rounded-lg dark:bg-slate-800"
                                    @update:model-value="clearFieldError('params')"
                                />
                            </div>
                        </div>
                        <p v-if="fieldErrors.params" class="text-xs text-red-500 font-medium">{{ fieldErrors.params }}</p>
                    </div>

                    <div class="sm:col-span-2 flex flex-col sm:flex-row gap-4 sm:gap-8">
                        <label
                            class="flex items-center gap-3 min-h-11 cursor-pointer select-none"
                            :class="isExternalPolicy ? 'opacity-70 cursor-not-allowed' : ''"
                        >
                            <Checkbox v-model="form.allow_home" :disabled="isExternalPolicy" class="h-5 w-5" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">Cho mượn về nhà</span>
                        </label>
                        <label class="flex items-center gap-3 min-h-11 cursor-pointer select-none">
                            <Checkbox v-model="form.allow_onsite" class="h-5 w-5" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">Đọc / mượn tại chỗ</span>
                        </label>
                    </div>
                    <p v-if="isExternalPolicy" class="sm:col-span-2 text-xs text-amber-700 dark:text-amber-400">
                        Bạn đọc ngoài: hệ thống không cho mượn về nhà theo quy định thư viện.
                    </p>
                </div>
                <div class="px-4 sm:px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex flex-col-reverse sm:flex-row justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                    <Button variant="outline" class="min-h-11 w-full sm:w-auto" @click="emit('close')">Hủy</Button>
                    <Button
                        class="min-h-11 w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white"
                        :disabled="saving"
                        @click="emit('save')"
                    >
                        {{ saving ? 'Đang lưu…' : 'Lưu' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
