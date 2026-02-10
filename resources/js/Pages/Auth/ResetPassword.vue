<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Label } from '@/Components/ui/label'
import { Lock, KeyRound, ArrowRight, Eye, EyeOff, CheckCircle2, RefreshCw } from 'lucide-vue-next'
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const showPassword = ref(false)
const showConfirmPassword = ref(false)
const step = ref('otp')

const props = defineProps({
    email: String,
    status: String,
})

const form = useForm({
    email: props.email || '',
    otp: '',
    password: '',
    password_confirmation: '',
})

const otpDigits = ref(['', '', '', '', '', ''])
const inputRefs = ref([])
const countdown = ref(60)
const canResend = ref(false)
let timer = null

const startTimer = () => {
    canResend.value = false
    countdown.value = 60
    if (timer) clearInterval(timer)
    timer = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--
        } else {
            canResend.value = true
            clearInterval(timer)
        }
    }, 1000)
}

onMounted(() => {
    startTimer()
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const resendOtp = () => {
    if (!canResend.value) return

    router.post(route('password.email'), { email: form.email }, {
        preserveScroll: true,
        onSuccess: () => {
            otpDigits.value = ['', '', '', '', '', '']
            form.reset('otp')
            startTimer()
        },
    })
}

const handleInput = (index, event) => {
    const val = event.target.value.replace(/\D/g, '')
    otpDigits.value[index] = val
    form.otp = otpDigits.value.join('')

    if (val && index < 5) {
        inputRefs.value[index + 1]?.focus()
    }
}

const handlePaste = (index, event) => {
    event.preventDefault()
    const pasteData = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6 - index)

    if (!pasteData) return

    const digits = pasteData.split('')
    digits.forEach((digit, i) => {
        if (index + i < 6) {
            otpDigits.value[index + i] = digit
        }
    })

    form.otp = otpDigits.value.join('')

    const nextIndex = Math.min(index + digits.length, 5)
    inputRefs.value[nextIndex]?.focus()
}

const handleKeyDown = (index, event) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        inputRefs.value[index - 1]?.focus()
    }
}

const submit = () => {
    if (step.value === 'otp') {
        if (!form.otp || form.otp.length !== 6) {
            form.errors.otp = 'Vui lòng nhập mã OTP 6 chữ số.'
            return
        }
        step.value = 'password'
        return
    }

    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <AuthLayout>
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Đặt lại mật khẩu" />

            <!-- Brand Header -->
            <div class="mb-6 flex flex-col items-center shrink-0 z-20 animate-in fade-in slide-in-from-top-4 duration-700">
                 <div class="group relative flex items-center justify-center gap-4 transition-transform hover:scale-105">
                     <div class="absolute -inset-2 bg-yellow-400/20 rounded-full blur-xl group-hover:bg-yellow-400/30 transition-all duration-300 opacity-0 group-hover:opacity-100"></div>
                     <div class="relative bg-white/10 backdrop-blur-md rounded-xl p-1.5 border border-white/20 shadow-2xl">
                        <img src="/Image/logoUTC.png" alt="UTC Logo" class="h-12 w-12 object-contain" />
                     </div>
                     <div class="flex flex-col">
                        <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-white/70 leading-none mb-1">Trường Đại Học</span>
                        <h1 class="font-display text-lg font-black uppercase tracking-tight text-white leading-tight drop-shadow-lg">
                            Giao Thông <span class="text-yellow-400">Vận Tải</span>
                        </h1>
                    </div>
                 </div>
            </div>

            <!-- Reset Password Card -->
            <div class="w-full max-w-[450px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2rem] border border-white/10 overflow-hidden animate-in zoom-in-95 fade-in duration-700">
                <div class="px-5 py-5 sm:px-8">
                    <!-- Title Section -->
                    <div class="mb-4 relative flex items-center min-h-[50px]">
                        <!-- Icon on the Left -->
                        <div class="absolute left-0 group">
                            <div class="absolute -inset-4 bg-blue-500/20 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center border border-white/10 text-blue-400 shadow-2xl ring-1 ring-white/5">
                                <KeyRound v-if="step === 'otp'" :size="24" class="animate-pulse" />
                                <Lock v-else :size="24" class="animate-bounce" />
                            </div>
                        </div>

                        <!-- Centered Text -->
                        <div class="flex-1 text-center pl-10 space-y-1">
                            <h2 class="font-display text-2xl font-black text-white tracking-tight uppercase leading-loose">
                                {{ step === 'otp' ? 'XÁC THỰC OTP' : 'ĐẶT MẬT KHẨU' }}
                            </h2>
                            <div class="flex items-center justify-center gap-2">
                                <div class="h-px w-6 bg-blue-500/20"></div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.3em]">
                                    {{ step === 'otp' ? 'Bước 1: Xác minh' : 'Bước 2: Bảo mật' }}
                                </p>
                                <div class="h-px w-6 bg-blue-500/20"></div>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-[13px] text-slate-500 mb-4 leading-relaxed">
                        Thiết lập mật khẩu mới cho tài khoản <span class="text-slate-300 font-bold ml-1">{{ email }}</span>
                    </p>

                    <!-- Success Alert -->
                    <div v-if="status" class="mb-5 animate-in fade-in zoom-in-95 duration-500">
                        <div class="relative overflow-hidden rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-3.5 transition-all hover:bg-emerald-500/15">
                            <!-- Background Glow -->
                            <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-500/10 blur-2xl rounded-full"></div>

                            <div class="relative flex items-center gap-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                    <CheckCircle2 :size="18" class="animate-pulse" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-[11px] font-black text-emerald-400/80 tracking-widest leading-tight">
                                        Thành công
                                    </p>
                                    <p class="text-[12px] text-emerald-200/60 font-medium leading-relaxed mt-0.5">
                                        {{ status }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <input type="hidden" v-model="form.email" />

                        <!-- Step 1: OTP -->
                        <div v-if="step === 'otp'" class="space-y-6 animate-in slide-in-from-right-10 duration-500">
                            <div class="space-y-4">
                                <Label class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] ml-1 transition-colors block text-center">
                                    Mã xác thực 6 số
                                </Label>

                                <div class="flex justify-between gap-2 sm:gap-3 px-1">
                                    <template v-for="(digit, index) in otpDigits" :key="index">
                                        <input
                                            :ref="el => inputRefs[index] = el"
                                            type="text"
                                            v-model="otpDigits[index]"
                                            maxlength="1"
                                            inputmode="numeric"
                                            @input="handleInput(index, $event)"
                                            @keydown="handleKeyDown(index, $event)"
                                            @paste="handlePaste(index, $event)"
                                            class="w-full h-11 text-center text-lg font-bold rounded-xl border-2 border-white/5 bg-white/5 text-white focus:bg-blue-600/10 focus:border-blue-500/40 focus:ring-0 transition-all duration-200 outline-none shadow-lg"
                                            :class="{ 'border-red-500/40 bg-red-500/5': form.errors.otp }"
                                        />
                                    </template>
                                </div>
                                <p v-if="form.errors.otp" class="text-[11px] text-red-500 font-bold uppercase tracking-wider text-center animate-pulse mt-2">{{ form.errors.otp }}</p>

                                <!-- Resend OTP Section -->
                                <div class="flex items-center justify-center gap-2 mt-4 bg-white/5 py-2 px-4 rounded-xl border border-white/5">
                                    <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-none">Chưa nhận được mã?</p>
                                    <button
                                        type="button"
                                        @click="resendOtp"
                                        :disabled="!canResend || form.processing"
                                        class="flex items-center gap-1.5 font-black text-[10px] transition-all uppercase tracking-wider leading-none"
                                        :class="canResend ? 'text-blue-400 hover:text-blue-300' : 'text-slate-600 cursor-not-allowed'"
                                    >
                                        <RefreshCw :size="12" :class="{ 'animate-spin': form.processing }" />
                                        <span>{{ canResend ? 'Gửi lại ngay' : `Thử lại sau ${countdown}s` }}</span>
                                    </button>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="group relative w-full h-14 overflow-hidden rounded-2xl bg-blue-600 text-base font-black text-white shadow-[0_20px_40px_-12px_rgba(59,130,246,0.5)] transition-all hover:bg-blue-500 active:scale-[0.98]"
                            >
                                <span class="flex items-center justify-center gap-2 uppercase tracking-widest">
                                    Xác thực & Tiếp tục <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform" />
                                </span>
                            </button>
                        </div>

                        <!-- Step 2: Password -->
                        <div v-if="step === 'password'" class="space-y-5 animate-in slide-in-from-right-10 duration-500">
                            <!-- Password Section -->
                            <div class="space-y-2 group">
                                <Label for="password" class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                    Mật khẩu mới
                                </Label>
                                <div class="relative group">
                                    <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <input
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        v-model="form.password"
                                        class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                        required
                                        autofocus
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5"
                                    >
                                        <Eye v-if="!showPassword" :size="18" />
                                        <EyeOff v-else :size="18" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.password }}</p>
                            </div>

                            <!-- Confirm Password -->
                            <div class="space-y-2 group">
                                <Label for="password_confirmation" class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                    Xác nhận mật khẩu mới
                                </Label>
                                <div class="relative group">
                                    <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <input
                                        id="password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        v-model="form.password_confirmation"
                                        class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                        required
                                    />
                                    <button
                                        type="button"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5"
                                    >
                                        <Eye v-if="!showConfirmPassword" :size="18" />
                                        <EyeOff v-else :size="18" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password_confirmation" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.password_confirmation }}</p>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="step = 'otp'"
                                    class="h-14 px-6 rounded-2xl bg-white/10 text-white font-bold hover:bg-white/20 transition-all uppercase tracking-widest text-xs"
                                >
                                    Quay lại
                                </button>
                                <button
                                    type="submit"
                                    class="flex-1 group relative h-14 overflow-hidden rounded-2xl bg-blue-600 text-base font-black text-white shadow-[0_20px_40px_-12px_rgba(59,130,246,0.5)] transition-all hover:bg-blue-500 active:scale-[0.98] disabled:opacity-50"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="flex items-center gap-2 justify-center">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Đang xử lý...
                                    </span>
                                    <span v-else class="flex items-center justify-center gap-2 uppercase tracking-widest">
                                        Hoàn tất <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform" />
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-6 pt-6 border-t border-white/10">
                        <Link
                            :href="route('login')"
                            class="flex items-center justify-center gap-2 w-full h-12 rounded-xl bg-white/5 border border-white/10 text-slate-400 font-bold text-sm hover:bg-white/10 hover:text-white transition-all active:scale-[0.98]"
                        >
                            Quay lại trang Đăng nhập
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 text-center pb-4">
                <p class="text-white/90 text-xs font-bold tracking-widest uppercase drop-shadow-md">
                    &copy; 2026 UTC eLibrary System &bull; Version 2.0
                </p>
            </div>
        </div>
    </AuthLayout>
</template>
