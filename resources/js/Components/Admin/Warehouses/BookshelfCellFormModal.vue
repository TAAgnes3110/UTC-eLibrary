<script setup>
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    show: { type: Boolean, required: true },
    mode: { type: String, default: 'create' }, // create|edit|view
    form: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    classifications: { type: Array, default: () => [] },
    details: { type: Array, default: () => [] },
    emptySlots: { type: Array, default: () => [] },
    saveLoading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save', 'classification-change']);

const isView = computed(() => props.mode === 'view');
const isEdit = computed(() => props.mode === 'edit');
const isCreate = computed(() => props.mode === 'create');
const lastAutoLabel = ref('');

const filteredDetails = computed(() => {
    if (!props.form.classification_id) return [];
    return props.details.filter((d) => String(d.classification_id) === String(props.form.classification_id));
});

function formatLabelByPosition(rowIndex, columnIndex) {
    const row = Number(rowIndex || 0);
    const col = Number(columnIndex || 0);
    if (row < 1 || col < 1) return '';
    return `R${String(row).padStart(2, '0')}-C${String(col).padStart(2, '0')}`;
}

function onSlotChange(event) {
    const value = String(event?.target?.value || '');
    const [r, c] = value.split('-');
    const row = Number(r || 0);
    const col = Number(c || 0);
    props.form.row_index = row;
    props.form.column_index = col;

    const suggested = formatLabelByPosition(row, col);
    const currentLabel = String(props.form.label || '').trim();

    if (!currentLabel || currentLabel === lastAutoLabel.value) {
        props.form.label = suggested;
    }
    lastAutoLabel.value = suggested;
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="emit('close')" />
            <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-800">
                <div class="sticky top-0 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 z-10">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ isView ? 'Xem ô kệ sách' : (isEdit ? 'Chỉnh sửa ô kệ sách' : 'Thêm ô kệ sách') }}
                    </h3>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="emit('close')">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-if="isView">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Vị trí</label>
                        <input
                            :value="form.row_index ? `R${String(form.row_index).padStart(2, '0')}-C${String(form.column_index).padStart(2, '0')}` : '—'"
                            disabled
                            class="mt-1 w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm dark:bg-slate-800 disabled:bg-slate-100 dark:disabled:bg-slate-800/70"
                        >
                    </div>

                    <div v-if="isView">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số lượng sách</label>
                        <input
                            :value="`${form.book_stats?.quantity_total ?? 0} bản`"
                            disabled
                            class="mt-1 w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm dark:bg-slate-800 disabled:bg-slate-100 dark:disabled:bg-slate-800/70"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Kho sách <span class="text-rose-500">*</span></label>
                        <select
                            v-model="form.warehouse_id"
                            :disabled="isView || isEdit"
                            class="mt-1 w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm dark:bg-slate-800 disabled:bg-slate-100 dark:disabled:bg-slate-800/70"
                            @change="emit('classification-change')"
                        >
                            <option value="">-- Chọn kho --</option>
                            <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                                {{ w.code }} - {{ w.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="isCreate">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tủ/Kệ trống <span class="text-rose-500">*</span></label>
                        <select
                            :value="form.row_index && form.column_index ? `${form.row_index}-${form.column_index}` : ''"
                            class="mt-1 w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm dark:bg-slate-800"
                            @change="onSlotChange"
                        >
                            <option value="">-- Chọn vị trí trống --</option>
                            <option v-for="slot in emptySlots" :key="slot.value" :value="slot.value">
                                {{ slot.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Nhãn <span v-if="isCreate" class="text-rose-500">*</span></label>
                        <input
                            v-model="form.label"
                            :disabled="isView"
                            class="mt-1 w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm dark:bg-slate-800 disabled:bg-slate-100 dark:disabled:bg-slate-800/70"
                            placeholder="Nhãn sẽ gợi ý theo vị trí đã chọn"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Phân loại <span class="text-rose-500">*</span></label>
                        <select
                            v-model="form.classification_id"
                            :disabled="isView"
                            class="mt-1 w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm dark:bg-slate-800 disabled:bg-slate-100 dark:disabled:bg-slate-800/70"
                            @change="emit('classification-change')"
                        >
                            <option value="">-- Chọn phân loại --</option>
                            <option v-for="c in classifications" :key="c.id" :value="String(c.id)">
                                {{ c.code }} - {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Phân loại chi tiết <span class="text-rose-500">*</span></label>
                        <select
                            v-model="form.classification_detail_id"
                            :disabled="isView"
                            class="mt-1 w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm dark:bg-slate-800 disabled:bg-slate-100 dark:disabled:bg-slate-800/70"
                        >
                            <option value="">-- Chọn chi tiết --</option>
                            <option v-for="d in filteredDetails" :key="d.id" :value="String(d.id)">
                                {{ d.code }} - {{ d.name }}
                            </option>
                        </select>
                    </div>

                </div>

                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                    <Button variant="outline" @click="emit('close')">Đóng</Button>
                    <Button v-if="!isView" :disabled="saveLoading" class="bg-blue-600 hover:bg-blue-700 text-white" @click="emit('save')">
                        {{ saveLoading ? 'Đang lưu...' : (isEdit ? 'Cập nhật' : 'Lưu') }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
