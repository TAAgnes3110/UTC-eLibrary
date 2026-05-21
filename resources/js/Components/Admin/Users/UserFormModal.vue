<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { facultyDisplayLabel, periodDisplayLabel } from '@/utils/lookupMatch';
import { maxDateOfBirthForInput, minDateOfBirthForInput } from '@/utils/dateOfBirth';

const maxDateOfBirth = maxDateOfBirthForInput();
const minDateOfBirth = minDateOfBirthForInput();

const GENDER_OPTIONS = [
    { value: 'male', label: 'Nam' },
    { value: 'female', label: 'Nữ' },
    { value: 'other', label: 'Khác' },
];

const props = defineProps({
    show: { type: Boolean, required: true },
    isEditing: { type: Boolean, required: true },
    form: { type: Object, required: true },
    roleOptions: { type: Array, default: () => [] },
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
    fieldErrors: { type: Object, default: () => ({}) },
    clearFieldError: { type: Function, default: () => () => {} },
    saveLoading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save']);

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const title = computed(() => (props.isEditing ? 'Chỉnh sửa tài khoản' : 'Thêm tài khoản'));

const isStudent = computed(() => props.form.role === 'STUDENT');
const isTeacher = computed(() => props.form.role === 'TEACHER');

function errClass(key) {
    return props.fieldErrors[key] ? 'border-red-500 dark:border-red-500' : 'border-slate-200 dark:border-slate-700';
}

function onRoleChange() {
    props.clearFieldError('role');
    const r = props.form.role;
    if (r === 'TEACHER') {
        props.form.period_id = null;
        props.form.period_lookup = '';
        props.form.class_code = '';
    } else if (r !== 'STUDENT') {
        props.form.faculty_id = null;
        props.form.faculty_lookup = '';
        props.form.period_id = null;
        props.form.period_lookup = '';
        props.form.class_code = '';
    }
}

const selectBaseClass =
    'w-full min-h-11 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm';

const lookupInputClass =
    'w-full min-h-11 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder:text-slate-400 dark:placeholder:text-slate-500';
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
                        {{ title }}
                    </h3>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="emit('close')">
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <p v-if="fieldErrors.general" class="sm:col-span-2 text-xs text-red-500 font-medium">
                        {{ fieldErrors.general }}
                    </p>
                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Họ và tên <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.name"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('name')"
                            placeholder="Nhập họ và tên"
                            @update:model-value="clearFieldError('name')"
                        />
                        <p v-if="fieldErrors.name" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Email <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.email"
                            type="email"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('email')"
                            placeholder="email@utc.edu.vn"
                            @update:model-value="clearFieldError('email')"
                        />
                        <p v-if="fieldErrors.email" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.email }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số điện thoại</label>
                        <Input
                            v-model="form.phone"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('phone')"
                            placeholder="09xxxxx..."
                            @update:model-value="clearFieldError('phone')"
                        />
                        <p v-if="fieldErrors.phone" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.phone }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Giới tính</label>
                        <select
                            v-model="form.gender"
                            :class="[selectBaseClass, errClass('gender')]"
                            @change="clearFieldError('gender')"
                        >
                            <option value="">— Chọn —</option>
                            <option v-for="g in GENDER_OPTIONS" :key="g.value" :value="g.value">{{ g.label }}</option>
                        </select>
                        <p v-if="fieldErrors.gender" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.gender }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày sinh</label>
                        <Input
                            v-model="form.date_of_birth"
                            type="date"
                            :max="maxDateOfBirth"
                            :min="minDateOfBirth"
                            class="h-10 rounded-lg dark:bg-slate-800"
                            :class="errClass('date_of_birth')"
                            @update:model-value="clearFieldError('date_of_birth')"
                        />
                        <p v-if="fieldErrors.date_of_birth" class="text-xs text-red-500 font-medium mt-1">
                            {{ fieldErrors.date_of_birth }}
                        </p>
                    </div>

                    <div class="sm:col-span-2 space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Địa chỉ</label>
                        <textarea
                            v-model="form.address"
                            rows="2"
                            class="w-full min-h-[80px] px-3 py-2 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder:text-slate-400 dark:placeholder:text-slate-500 resize-y"
                            :class="errClass('address')"
                            placeholder="Số nhà, phường/xã, quận/huyện, tỉnh/thành phố"
                            @input="clearFieldError('address')"
                        />
                        <p v-if="fieldErrors.address" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.address }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Mã định danh <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.code"
                            class="h-10 rounded-lg font-mono dark:bg-slate-800"
                            :class="errClass('code')"
                            placeholder="MSV, CCCD..."
                            @update:model-value="clearFieldError('code')"
                        />
                        <p v-if="fieldErrors.code" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.code }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Phân quyền</label>
                        <select
                            v-model="form.role"
                            :class="[selectBaseClass, errClass('role')]"
                            @change="onRoleChange"
                        >
                            <option v-for="r in roleOptions" :key="r.id ?? r.value ?? r.role" :value="r.id ?? r.value ?? r.role">
                                {{ r.text ?? r.label ?? r.name ?? r.id ?? r.value }}
                            </option>
                        </select>
                        <p v-if="fieldErrors.role" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.role }}</p>
                    </div>

                    <template v-if="isStudent || isTeacher">
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Khoa <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.faculty_lookup"
                                type="text"
                                list="user-account-faculty-datalist"
                                autocomplete="off"
                                :class="[lookupInputClass, errClass('faculty_id')]"
                                placeholder="Gõ mã / tên khoa hoặc chọn gợi ý"
                                @input="clearFieldError('faculty_id')"
                            />
                            <datalist id="user-account-faculty-datalist">
                                <option v-for="f in faculties" :key="f.id" :value="facultyDisplayLabel(f)" />
                            </datalist>
                            <p v-if="fieldErrors.faculty_id" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.faculty_id }}
                            </p>
                        </div>
                    </template>

                    <template v-if="isStudent">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Niên khóa <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.period_lookup"
                                type="text"
                                list="user-account-period-datalist"
                                autocomplete="off"
                                :class="[lookupInputClass, errClass('period_id')]"
                                placeholder="Gõ K63 / 2022 hoặc chọn gợi ý (K… và khoảng năm)"
                                @input="clearFieldError('period_id')"
                            />
                            <datalist id="user-account-period-datalist">
                                <option v-for="p in periods" :key="p.id" :value="periodDisplayLabel(p)" />
                            </datalist>
                            <p v-if="fieldErrors.period_id" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.period_id }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Lớp</label>
                            <Input
                                v-model="form.class_code"
                                class="min-h-11 rounded-lg dark:bg-slate-800"
                                :class="errClass('class_code')"
                                placeholder="Ví dụ: D21CQCN01-N"
                                @update:model-value="clearFieldError('class_code')"
                            />
                            <p v-if="fieldErrors.class_code" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.class_code }}
                            </p>
                        </div>
                    </template>

                    <template v-if="!isEditing">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Mật khẩu <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <Input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="h-10 pr-10 rounded-lg dark:bg-slate-800"
                                    :class="errClass('password')"
                                    placeholder="••••••••"
                                    @update:model-value="clearFieldError('password')"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                    :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                                    @click="showPassword = !showPassword"
                                >
                                    <Icon :icon="showPassword ? 'lucide:eye-off' : 'lucide:eye'" class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="fieldErrors.password" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.password }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Xác nhận mật khẩu <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <Input
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    class="h-10 pr-10 rounded-lg dark:bg-slate-800"
                                    :class="errClass('password_confirmation')"
                                    placeholder="••••••••"
                                    @update:model-value="clearFieldError('password_confirmation')"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-200"
                                    :aria-label="showPasswordConfirmation ? 'Ẩn xác nhận mật khẩu' : 'Hiện xác nhận mật khẩu'"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                >
                                    <Icon :icon="showPasswordConfirmation ? 'lucide:eye-off' : 'lucide:eye'" class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="fieldErrors.password_confirmation" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.password_confirmation }}
                            </p>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                    <Button variant="outline" :disabled="saveLoading" @click="emit('close')">Hủy bỏ</Button>
                    <Button :disabled="saveLoading" class="bg-blue-600 hover:bg-blue-700 text-white" @click="emit('save')">
                        {{ isEditing ? 'Cập nhật' : 'Lưu' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
