<script setup>
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    email: String,
    status: String,
})

const form = useForm({
    email: props.email ?? '',
    otp: '',
})

const submit = () => {
    form.post(route('verify-otp'), {
        preserveScroll: true,
    })
}

const resendOtp = () => {
    router.post(route('verify-otp.resend'), { email: form.email }, {
        preserveScroll: true,
        onSuccess: () => form.reset('otp'),
    })
}
</script>

<template>
    <AuthLayout>
        <Head title="Xác thực OTP" />

        <div class="flex flex-col items-center justify-center space-y-2 mb-6 text-center animate-in slide-in-from-top-5 duration-500">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-full p-1 shadow-lg">
                    <img src="/Image/logoUTC.png" alt="UTC Logo" class="h-16 w-16 object-contain" />
                </div>
                <div class="flex flex-col items-start text-white">
                    <h2 class="text-sm font-bold uppercase tracking-wider opacity-90">Trường Đại Học</h2>
                    <h1 class="text-xl font-extrabold uppercase tracking-wide text-yellow-400 drop-shadow-sm">
                        Giao Thông Vận Tải
                    </h1>
                </div>
            </div>
        </div>

        <div class="w-full max-w-[420px] bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl shadow-2xl rounded-2xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden animate-in zoom-in-95 duration-500">
            <div class="relative pt-10 pb-6 px-10 flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-xl bg-[#1e3a8a]/10 dark:bg-blue-500/20 flex items-center justify-center mb-4 text-[#1e3a8a] dark:text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                </div>
                <h3 class="text-xl font-bold text-[#1e3a8a] dark:text-white tracking-tight">Xác thực OTP</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                    Nhập mã 6 số đã gửi đến <strong class="text-[#1e3a8a] dark:text-blue-400">{{ email }}</strong>
                </p>
            </div>

            <div class="px-10 pb-10">
                <p v-if="status" class="mb-5 text-sm text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900/30 p-4 rounded-xl border border-green-200 dark:border-green-800/50">
                    {{ status }}
                </p>

                <form @submit.prevent="submit" class="space-y-5">
                    <input type="hidden" v-model="form.email" />

                    <div class="space-y-2">
                        <Input
                            id="otp"
                            type="text"
                            placeholder="Nhập mã OTP"
                            v-model="form.otp"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            class="h-14 text-center text-2xl tracking-[0.4em] font-mono rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-800/50 focus-visible:ring-2 focus-visible:ring-[#1e3a8a]/20"
                            :class="{ 'border-red-500 ring-2 ring-red-500/20': form.errors.otp }"
                            required
                            autofocus
                        />
                        <p v-if="form.errors.otp" class="text-xs text-red-600 dark:text-red-400 pl-1 text-center">{{ form.errors.otp }}</p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full h-12 text-base font-semibold bg-[#1e3a8a] hover:bg-[#172554] text-white shadow-lg shadow-[#1e3a8a]/25 rounded-xl transition-all duration-200"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Đang xác thực...</span>
                        <span v-else>Xác nhận & hoàn tất</span>
                    </Button>

                    <p class="text-center text-sm text-slate-600 dark:text-slate-400">
                        Chưa nhận được mã?
                        <button
                            type="button"
                            @click="resendOtp"
                            :disabled="form.processing"
                            class="text-[#1e3a8a] dark:text-blue-400 font-semibold hover:underline ml-1"
                        >
                            Gửi lại OTP
                        </button>
                    </p>
                </form>

                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-slate-200 dark:border-slate-700" /></div>
                    <div class="relative flex justify-center">
                        <span class="bg-white dark:bg-slate-900 px-4 text-sm text-slate-500 dark:text-slate-400">Quay lại</span>
                    </div>
                </div>

                <Link
                    :href="route('register')"
                    class="flex justify-center w-full h-12 items-center rounded-xl font-semibold text-[#1e3a8a] dark:text-blue-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-600"
                >
                    Đăng ký lại
                </Link>
            </div>
        </div>

        <div class="mt-8 text-center text-white/60 text-xs">
            &copy; {{ new Date().getFullYear() }} UTC eLibrary System.
        </div>
    </AuthLayout>
</template>
