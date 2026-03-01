<script setup>
import { computed, ref } from 'vue';
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    user: { type: Object, required: true },
    faculties: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    cohorts: { type: Array, default: () => [] },
    myRequests: { type: Array, default: () => [] },
});

const form = useForm({
    field: 'cohort',
    value_new: '',
    proof: null,
});
const submitting = ref(false);
const statusMessage = ref('');

const departmentsByFaculty = computed(() => {
    if (!props.user.faculty_id) return props.departments;
    return props.departments.filter((d) => Number(d.faculty_id) === Number(props.user.faculty_id));
});

const submit = async () => {
    const valueNew = (form.field === 'faculty_id' || form.field === 'department_id') ? String(form.value_new) : form.value_new;
    const fd = new FormData();
    fd.append('field', form.field);
    fd.append('value_new', valueNew);
    if (form.proof) fd.append('proof', form.proof);
    submitting.value = true;
    form.clearErrors();
    statusMessage.value = '';
    try {
        const { data } = await window.axios.post('/me/profile-change-requests', fd, {
            headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
        });
        statusMessage.value = data?.messages || 'Đã gửi yêu cầu.';
        form.reset('value_new', 'proof');
        router.reload();
    } catch (e) {
        const err = e.response?.data;
        const errData = err?.data;
        if (errData && typeof errData === 'object') form.setErrors(errData);
        else if (err?.messages) form.setErrors({ value_new: [err.messages] });
    }
    submitting.value = false;
};

const statusLabel = (s) => ({ pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Đã từ chối' }[s] || s);
const fieldLabel = (f) => ({ code: 'Mã SV/CCCD', cohort: 'Khóa học', faculty_id: 'Khoa', department_id: 'Lớp' }[f] || f);
</script>

<template>
    <Head title="Yêu cầu chỉnh sửa thông tin - Thư viện số" />
    <ReaderDashboardLayout title="Yêu cầu chỉnh sửa thông tin">
        <div class="max-w-2xl space-y-8">
            <p class="text-slate-600 dark:text-slate-400">
                Mã sinh viên/căn cước, khoa, lớp và khóa chỉ được cập nhật sau khi có sự chấp nhận của quản trị viên hoặc thủ thư. Gửi yêu cầu kèm minh chứng (ảnh thẻ sinh viên) nếu cần thay đổi.
                Họ tên, email, SĐT, mật khẩu có thể
                <a :href="route('library.profile.edit')" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">cập nhật trực tiếp (không cần duyệt)</a>.
            </p>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Thông tin hiện tại</h2>
                <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                    <li><span class="font-medium text-slate-700 dark:text-slate-300">Mã SV/CCCD:</span> {{ user.code || '—' }}</li>
                    <li><span class="font-medium text-slate-700 dark:text-slate-300">Khóa:</span> {{ user.cohort || '—' }}</li>
                    <li><span class="font-medium text-slate-700 dark:text-slate-300">Khoa:</span> {{ user.faculty || '—' }}</li>
                    <li><span class="font-medium text-slate-700 dark:text-slate-300">Lớp:</span> {{ user.department || '—' }}</li>
                </ul>
            </div>

            <form class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4" @submit.prevent="submit">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Gửi yêu cầu chỉnh sửa</h2>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Loại thông tin</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.field" type="radio" value="code" class="rounded border-slate-300" />
                            <span class="text-slate-700 dark:text-slate-300">Mã SV/CCCD</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.field" type="radio" value="cohort" class="rounded border-slate-300" />
                            <span class="text-slate-700 dark:text-slate-300">Khóa học</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.field" type="radio" value="faculty_id" class="rounded border-slate-300" />
                            <span class="text-slate-700 dark:text-slate-300">Khoa</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.field" type="radio" value="department_id" class="rounded border-slate-300" />
                            <span class="text-slate-700 dark:text-slate-300">Lớp</span>
                        </label>
                    </div>
                </div>
                <div v-if="form.field === 'code'" class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã SV/CCCD mới</label>
                    <Input v-model="form.value_new" placeholder="Nhập mã sinh viên hoặc số căn cước" class="w-full" />
                    <p v-if="form.errors.value_new" class="text-sm text-rose-600">{{ form.errors.value_new }}</p>
                </div>
                <div v-else-if="form.field === 'cohort'" class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Khóa học mới</label>
                    <select v-model="form.value_new" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Chọn khóa --</option>
                        <option v-for="c in cohorts" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <p v-if="form.errors.value_new" class="text-sm text-rose-600">{{ form.errors.value_new }}</p>
                </div>
                <div v-else-if="form.field === 'faculty_id'" class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Khoa mới</label>
                    <select v-model="form.value_new" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Chọn khoa --</option>
                        <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
                    </select>
                    <p v-if="form.errors.value_new" class="text-sm text-rose-600">{{ form.errors.value_new }}</p>
                </div>
                <div v-else-if="form.field === 'department_id'" class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Lớp mới</label>
                    <select v-model="form.value_new" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Chọn lớp (theo khoa hiện tại) --</option>
                        <option v-for="d in departmentsByFaculty" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                    <p v-if="!user.faculty_id" class="text-xs text-amber-600 dark:text-amber-400">Vui lòng chọn khoa trước (gửi yêu cầu đổi khoa nếu cần).</p>
                    <p v-if="form.errors.value_new" class="text-sm text-rose-600">{{ form.errors.value_new }}</p>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Minh chứng (ảnh thẻ sinh viên, tùy chọn)</label>
                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-slate-600 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300" @change="form.proof = $event.target.files?.[0] || null" />
                    <p class="text-xs text-slate-500">Định dạng JPG, PNG hoặc PDF, tối đa 5MB.</p>
                </div>
                <div class="flex gap-2">
                    <p v-if="statusMessage" class="text-sm text-emerald-600 dark:text-emerald-400">{{ statusMessage }}</p>
                <Button type="submit" :disabled="submitting" class="bg-blue-600 hover:bg-blue-700 text-white">
                        {{ submitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}
                    </Button>
                </div>
            </form>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Lịch sử yêu cầu</h2>
                <ul v-if="myRequests.length" class="space-y-3">
                    <li v-for="r in myRequests" :key="r.id" class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ fieldLabel(r.field) }}: {{ r.value_old || '—' }} → {{ r.value_new }}</span>
                        <span
                            :class="{
                                'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400': r.status === 'pending',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400': r.status === 'approved',
                                'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400': r.status === 'rejected',
                            }"
                            class="rounded-lg px-2 py-0.5 text-xs font-medium"
                        >
                            {{ statusLabel(r.status) }}
                        </span>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-500 dark:text-slate-400">Chưa có yêu cầu nào.</p>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>
