<script setup>
import { ref } from 'vue';
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    phone: props.user.phone ?? '',
    password: '',
    gender: props.user.gender ?? 'Nam',
});
const submitting = ref(false);

const submit = async () => {
    submitting.value = true;
    form.clearErrors();
    const payload = { name: form.name, email: form.email, phone: form.phone || null, gender: form.gender };
    if (form.password) payload.password = form.password;
    try {
        await window.axios.put('/me/profile', payload);
        form.reset('password');
        router.visit(route('library.profile.change-request'), { preserveState: false });
    } catch (e) {
        const err = e.response?.data;
        if (err?.data) form.setErrors(err.data);
    }
    submitting.value = false;
};
</script>

<template>
    <Head title="Cập nhật thông tin cá nhân - Thư viện số" />
    <ReaderDashboardLayout title="Cập nhật thông tin cá nhân">
        <div class="max-w-2xl space-y-6">
            <p class="text-slate-600 dark:text-slate-400">
                Các trường bên dưới có thể cập nhật trực tiếp, không cần chờ duyệt. Mã SV/CCCD, khoa, lớp, khóa cần gửi yêu cầu tại
                <Link :href="route('library.profile.change-request')" class="text-blue-600 dark:text-blue-400 hover:underline">Yêu cầu chỉnh sửa thông tin</Link>.
            </p>

            <form class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Họ và tên <span class="text-rose-500">*</span></label>
                    <Input v-model="form.name" type="text" class="w-full" required />
                    <p v-if="form.errors.name" class="text-sm text-rose-600">{{ form.errors.name }}</p>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Email <span class="text-rose-500">*</span></label>
                    <Input v-model="form.email" type="email" class="w-full" required />
                    <p v-if="form.errors.email" class="text-sm text-rose-600">{{ form.errors.email }}</p>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số điện thoại</label>
                    <Input v-model="form.phone" type="text" class="w-full" />
                    <p v-if="form.errors.phone" class="text-sm text-rose-600">{{ form.errors.phone }}</p>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Giới tính</label>
                    <select v-model="form.gender" class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mật khẩu mới (để trống nếu không đổi)</label>
                    <Input v-model="form.password" type="password" class="w-full" autocomplete="new-password" />
                    <p v-if="form.errors.password" class="text-sm text-rose-600">{{ form.errors.password }}</p>
                </div>
                <div class="flex gap-2">
                    <Button type="submit" :disabled="submitting">
                        {{ submitting ? 'Đang lưu...' : 'Lưu thay đổi' }}
                    </Button>
                    <Link :href="route('library.profile.change-request')" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                        Quay lại
                    </Link>
                </div>
            </form>
        </div>
    </ReaderDashboardLayout>
</template>
