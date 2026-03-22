<script setup>
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    show: { type: Boolean, required: true },
    isEditing: { type: Boolean, required: true },
    form: { type: Object, required: true },
    fieldErrors: { type: Object, default: () => ({}) },
    clearFieldError: { type: Function, default: () => () => {} },
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
            >
                <div
                    class="sticky top-0 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 z-10"
                >
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ isEditing ? 'Chỉnh sửa kho sách' : 'Thêm kho sách' }}
                    </h3>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="emit('close')">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <p v-if="fieldErrors.general" class="sm:col-span-2 text-xs text-red-500 font-medium">
                        {{ fieldErrors.general }}
                    </p>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã kho <span class="text-rose-500">*</span></label>
                        <Input
                            v-model="form.code"
                            class="h-10 rounded-lg font-mono dark:bg-slate-800"
                            :class="errClass('code')"
                            placeholder="Ví dụ: TV-TT-UTC"
                            @update:model-value="clearFieldError('code')"
                        />
                        <p v-if="fieldErrors.code" class="text-xs text-red-500 font-medium">{{ fieldErrors.code }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tên kho <span class="text-rose-500">*</span></label>
                        <Input
                            v-model="form.name"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('name')"
                            placeholder="Ví dụ: Thư viện Trung tâm UTC"
                            @update:model-value="clearFieldError('name')"
                        />
                        <p v-if="fieldErrors.name" class="text-xs text-red-500 font-medium">{{ fieldErrors.name }}</p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                    <Button variant="outline" @click="emit('close')">Hủy bỏ</Button>
                    <Button class="bg-blue-600 hover:bg-blue-700 text-white" @click="emit('save')">
                        {{ isEditing ? 'Cập nhật' : 'Lưu' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
