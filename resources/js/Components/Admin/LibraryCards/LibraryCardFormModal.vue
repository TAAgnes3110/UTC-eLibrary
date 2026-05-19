<script setup>
import { watch, ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { LibraryCard } from '@/config/libraryCardConstants';
import { HOLDER_LABELS, WORKFLOW_LABELS } from '@/config/libraryCardUi';
import { maxDateOfBirthForInput, minDateOfBirthForInput } from '@/utils/dateOfBirth';

const maxDateOfBirth = maxDateOfBirthForInput();
const minDateOfBirth = minDateOfBirthForInput();

const props = defineProps({
    show: { type: Boolean, required: true },
    form: { type: Object, required: true },
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
    saveLoading: { type: Boolean, default: false },
    fieldErrors: { type: Object, default: () => ({}) },
    clearFieldError: { type: Function, default: () => {} },
    /** Màn duyệt yêu cầu: không chỉnh trạng thái thẻ (Hoạt động/Khóa/…) */
    hideCardStatus: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save']);

const holderTypes = [
    { value: LibraryCard.HOLDER_STUDENT, label: HOLDER_LABELS.student },
    { value: LibraryCard.HOLDER_TEACHER, label: HOLDER_LABELS.teacher },
    { value: LibraryCard.HOLDER_EXTERNAL, label: HOLDER_LABELS.external },
];

/** Không chọn « Từ chối » / « Đã hủy » ở đây — backend sẽ xóa mềm; có thể thêm vào khi bản ghi đang ở trạng thái đó (legacy). */
const workflowOptionsAll = [
    { value: 'draft', label: WORKFLOW_LABELS.draft },
    { value: 'pending_payment', label: WORKFLOW_LABELS.pending_payment },
    { value: 'pending_review', label: WORKFLOW_LABELS.pending_review },
    { value: 'pending_pickup', label: WORKFLOW_LABELS.pending_pickup },
    { value: 'active', label: WORKFLOW_LABELS.active },
    { value: 'expired', label: WORKFLOW_LABELS.expired },
    { value: 'revoked', label: WORKFLOW_LABELS.revoked },
];

const statusOptions = [
    { value: 1, label: 'Hoạt động' },
    { value: 2, label: 'Hết hạn' },
    { value: 3, label: 'Khóa' },
    { value: 4, label: 'Chờ' },
];

const panelRef = ref(null);

const facultiesList = computed(() => (Array.isArray(props.faculties) ? props.faculties : []));
const periodsList = computed(() => (Array.isArray(props.periods) ? props.periods : []));

const isStudent = computed(() => props.form.holder_type === LibraryCard.HOLDER_STUDENT);
const isTeacher = computed(() => props.form.holder_type === LibraryCard.HOLDER_TEACHER);
const isExternal = computed(() => props.form.holder_type === LibraryCard.HOLDER_EXTERNAL);

/** Không đưa « pending_pickup » vào danh sách chọn mới; vẫn hiện nếu bản ghi đang ở trạng thái đó. Từ chối/đã hủy chỉ khi legacy. */
const workflowOptions = computed(() => {
    const list = workflowOptionsAll.filter((o) => o.value !== 'pending_pickup');
    if (props.form.workflow_status === 'pending_pickup') {
        list.push({ value: 'pending_pickup', label: WORKFLOW_LABELS.pending_pickup });
    }
    if (props.form.workflow_status === 'rejected') {
        list.push({ value: 'rejected', label: WORKFLOW_LABELS.rejected });
    }
    if (props.form.workflow_status === 'cancelled') {
        list.push({ value: 'cancelled', label: WORKFLOW_LABELS.cancelled });
    }
    return list;
});

watch(
    () => props.show,
    (v) => {
        if (v && typeof document !== 'undefined') {
            document.body.style.overflow = 'hidden';
        } else if (typeof document !== 'undefined') {
            document.body.style.overflow = '';
        }
    }
);

function onSubmit() {
    emit('save');
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
            @click.self="$emit('close')"
        >
            <div class="absolute inset-0 bg-slate-900/60" @click="$emit('close')" />
            <div
                ref="panelRef"
                class="relative w-full sm:max-w-xl max-h-[92vh] overflow-y-auto rounded-t-2xl sm:rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl"
            >
                <div class="sticky top-0 z-10 flex items-center justify-between gap-2 px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Chỉnh sửa thẻ thư viện</h3>
                    <button type="button" class="p-2 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="$emit('close')">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>

                <form class="p-4 space-y-4" @submit.prevent="onSubmit">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Thông tin cá nhân</p>
                        <div class="mt-2 space-y-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Họ tên</label>
                                <input v-model="form.full_name" type="text" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @input="clearFieldError('full_name')" />
                                <p v-if="fieldErrors.full_name" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.full_name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Email</label>
                                <input v-model="form.email" type="email" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @input="clearFieldError('email')" />
                                <p v-if="fieldErrors.email" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.email }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Số điện thoại</label>
                                <input v-model="form.phone" type="text" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @input="clearFieldError('phone')" />
                                <p v-if="fieldErrors.phone" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.phone }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Địa chỉ</label>
                                <textarea v-model="form.address" rows="2" class="admin-filter-input w-full mt-1 py-2 min-h-[80px]" @input="clearFieldError('address')" />
                                <p v-if="fieldErrors.address" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.address }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Ngày sinh</label>
                                <input
                                    v-model="form.date_of_birth"
                                    type="date"
                                    :max="maxDateOfBirth"
                                    :min="minDateOfBirth"
                                    class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9"
                                    @input="clearFieldError('date_of_birth')"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Loại thẻ</label>
                            <select v-model="form.holder_type" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @change="clearFieldError('holder_type')">
                                <option v-for="h in holderTypes" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
                            <p v-if="fieldErrors.holder_type" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.holder_type }}</p>
                        </div>
                        <div v-if="!hideCardStatus">
                            <label class="text-xs font-semibold text-slate-500">Trạng thái thẻ</label>
                            <select v-model.number="form.status" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9">
                                <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-500">Quy trình</label>
                        <select v-model="form.workflow_status" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9">
                            <option v-for="w in workflowOptions" :key="w.value" :value="w.value">{{ w.label }}</option>
                        </select>
                    </div>

                    <div v-if="isStudent" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/40 p-3 space-y-3">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Theo loại thẻ: sinh viên</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Bắt buộc: khoa, niên khóa, lớp.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Khoa *</label>
                                <select v-model.number="form.faculty_id" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @change="clearFieldError('faculty_id')">
                                    <option :value="null">—</option>
                                    <option v-for="f in facultiesList" :key="f.id" :value="f.id">{{ f.code }} — {{ f.name }}</option>
                                </select>
                                <p v-if="fieldErrors.faculty_id" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.faculty_id }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Niên khóa *</label>
                                <select v-model.number="form.period_id" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @change="clearFieldError('period_id')">
                                    <option :value="null">—</option>
                                    <option v-for="p in periodsList" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                                <p v-if="fieldErrors.period_id" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.period_id }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Lớp / mã lớp *</label>
                            <input v-model="form.class_code" type="text" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @input="clearFieldError('class_code')" />
                            <p v-if="fieldErrors.class_code" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.class_code }}</p>
                        </div>
                    </div>

                    <div v-else-if="isTeacher" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/40 p-3 space-y-3">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Theo loại thẻ: giảng viên</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Bắt buộc: khoa. Niên khóa / lớp ghi nếu có.</p>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Khoa *</label>
                            <select v-model.number="form.faculty_id" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @change="clearFieldError('faculty_id')">
                                <option :value="null">—</option>
                                <option v-for="f in facultiesList" :key="f.id" :value="f.id">{{ f.code }} — {{ f.name }}</option>
                            </select>
                            <p v-if="fieldErrors.faculty_id" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.faculty_id }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Niên khóa</label>
                                <select v-model.number="form.period_id" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9">
                                    <option :value="null">—</option>
                                    <option v-for="p in periodsList" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Lớp / mã lớp</label>
                                <input v-model="form.class_code" type="text" class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9" @input="clearFieldError('class_code')" />
                                <p v-if="fieldErrors.class_code" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.class_code }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="isExternal" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/40 p-3 space-y-3">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Theo loại thẻ: bạn đọc ngoài</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Không gắn khoa / niên khóa. Có thể ghi đơn vị, tổ chức (nếu có).</p>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Đơn vị / tổ chức</label>
                            <input
                                v-model="form.external_organization"
                                type="text"
                                class="admin-filter-input w-full mt-1 min-h-[44px] sm:min-h-0 sm:h-9"
                                @input="clearFieldError('external_organization')"
                            />
                            <p v-if="fieldErrors.external_organization" class="text-xs text-rose-600 mt-0.5">{{ fieldErrors.external_organization }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-500">Ghi chú</label>
                        <textarea v-model="form.notes" rows="2" class="admin-filter-input w-full mt-1 py-2 min-h-[72px]" />
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" class="admin-filter-btn min-h-[44px] !h-auto py-2.5 px-4 w-full sm:w-auto justify-center" @click="$emit('close')">
                            Hủy
                        </button>
                        <button
                            type="submit"
                            class="btn-admin-green min-h-[44px] !h-auto py-2.5 px-4 w-full sm:w-auto justify-center inline-flex items-center disabled:opacity-50 disabled:pointer-events-none"
                            :disabled="saveLoading"
                        >
                            {{ saveLoading ? 'Đang lưu…' : 'Lưu thay đổi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
