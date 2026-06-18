<script setup>
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';

const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: 'internal' },
    fieldErrors: { type: Object, default: () => ({}) },
    sectionKey: { type: String, default: '' },
});

function err(suffix) {
    const k = props.sectionKey ? `${props.sectionKey}_${suffix}` : suffix;
    return props.fieldErrors[k] || props.fieldErrors[suffix] || '';
}

function errClass(suffix) {
    return err(suffix) ? 'border border-red-500 dark:border-red-500' : 'border border-slate-200 dark:border-slate-700';
}

function clampIntField(key) {
    const v = props.form[key];
    if (v === '' || v === null || v === undefined) return;
    const n = Number(v);
    if (Number.isNaN(n)) {
        props.form[key] = 0;
        return;
    }
    props.form[key] = Math.max(0, Math.trunc(n));
}
</script>

<template>
    <div class="space-y-5 max-w-xl">
        <template v-if="mode === 'internal'">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-800 dark:text-slate-200 leading-snug">
                    Số đầu sách mượn tối đa (cùng lúc)
                </label>
                <Input
                    v-model.number="form.max_books"
                    type="number"
                    min="0"
                    step="1"
                    class="h-11 rounded-lg bg-white dark:bg-slate-950"
                    :class="errClass('max_books')"
                    @blur="clampIntField('max_books')"
                />
                <p v-if="err('max_books')" class="text-xs text-red-600 dark:text-red-400">{{ err('max_books') }}</p>
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-800 dark:text-slate-200">Thời hạn mượn (ngày)</label>
                <Input
                    v-model.number="form.max_days"
                    type="number"
                    min="0"
                    step="1"
                    class="h-11 rounded-lg bg-white dark:bg-slate-950"
                    :class="errClass('max_days')"
                    @blur="clampIntField('max_days')"
                />
                <p v-if="err('max_days')" class="text-xs text-red-600 dark:text-red-400">{{ err('max_days') }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tối đa số giáo trình</label>
                    <Input
                        v-model.number="form.max_textbooks"
                        type="number"
                        min="0"
                        step="1"
                        class="h-11 rounded-lg bg-white dark:bg-slate-950"
                        :class="errClass('max_textbooks')"
                        @blur="clampIntField('max_textbooks')"
                    />
                    <p v-if="err('params.max_textbooks') || err('max_textbooks')" class="text-xs text-red-600 dark:text-red-400">
                        {{ err('params.max_textbooks') || err('max_textbooks') }}
                    </p>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tối đa tài liệu tham khảo</label>
                    <Input
                        v-model.number="form.max_reference"
                        type="number"
                        min="0"
                        step="1"
                        class="h-11 rounded-lg bg-white dark:bg-slate-950"
                        :class="errClass('max_reference')"
                        @blur="clampIntField('max_reference')"
                    />
                    <p v-if="err('params.max_reference') || err('max_reference')" class="text-xs text-red-600 dark:text-red-400">
                        {{ err('params.max_reference') || err('max_reference') }}
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Số lần gia hạn tối đa</label>
                    <Input
                        v-model.number="form.max_renewals"
                        type="number"
                        min="0"
                        step="1"
                        class="h-11 rounded-lg max-w-xs bg-white dark:bg-slate-950"
                        :class="errClass('max_renewals')"
                        @blur="clampIntField('max_renewals')"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Phạt trễ hạn (đồng / ngày / cuốn)
                    </label>
                    <Input
                        v-model="form.overdue_fine_per_day"
                        type="text"
                        inputmode="decimal"
                        class="h-11 rounded-lg max-w-xs bg-white dark:bg-slate-950"
                        :class="errClass('overdue_fine_per_day')"
                    />
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-prose">
                        Số tiền cho <span class="font-semibold text-slate-600 dark:text-slate-300">mỗi cuốn</span> mượn, tính theo
                        <span class="font-semibold text-slate-600 dark:text-slate-300">mỗi ngày</span> quá hạn, khi thư viện áp dụng phạt «theo đồng/ngày» (không áp dụng nếu hệ thống dùng cách tính theo % giá bìa). Ví dụ thường gặp: khoảng
                        <span class="font-semibold text-slate-700 dark:text-slate-200">1.000 đ</span>/ngày/cuốn; có thể đặt cao hơn (vd. 5.000 đ) tùy nhóm thẻ.
                    </p>
                    <p v-if="err('overdue_fine_per_day')" class="text-xs text-red-600 dark:text-red-400">{{ err('overdue_fine_per_day') }}</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 sm:gap-8 pt-1">
                <label class="flex items-center gap-3 min-h-11 cursor-pointer">
                    <Checkbox v-model="form.allow_home" class="h-5 w-5" />
                    <span class="text-sm text-slate-700 dark:text-slate-300">Cho mượn về nhà</span>
                </label>
                <label class="flex items-center gap-3 min-h-11 cursor-pointer">
                    <Checkbox v-model="form.allow_onsite" class="h-5 w-5" />
                    <span class="text-sm text-slate-700 dark:text-slate-300">Đọc / mượn tại chỗ</span>
                </label>
            </div>
        </template>

        <template v-else>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Bạn đọc ngoài thường chỉ dùng dịch vụ tại chỗ; có thể đặt số đầu tối đa bằng 0 nếu không áp dụng mượn.
            </p>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-800 dark:text-slate-200">Số đầu sách tối đa</label>
                <Input
                    v-model.number="form.max_books"
                    type="number"
                    min="0"
                    step="1"
                    class="h-11 rounded-lg bg-white dark:bg-slate-950"
                    @blur="clampIntField('max_books')"
                />
            </div>
            <div class="flex items-center gap-3 min-h-11 pt-2 opacity-70">
                <Checkbox :model-value="false" disabled class="h-5 w-5" />
                <span class="text-sm text-slate-600 dark:text-slate-400">Không cho mượn về nhà</span>
            </div>
            <label class="flex items-center gap-3 min-h-11 cursor-pointer">
                <Checkbox v-model="form.allow_onsite" class="h-5 w-5" />
                <span class="text-sm text-slate-700 dark:text-slate-300">Đọc / mượn tại chỗ</span>
            </label>
        </template>
    </div>
</template>
