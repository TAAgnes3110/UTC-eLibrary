<script setup>
import { ref, watch } from 'vue'
import { Button } from '@/Components/ui/button'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ShieldCheck, ArrowRight, RefreshCw, Undo2, Mail } from 'lucide-vue-next'

const props = defineProps({
    email: String,
    status: String,
})

const form = useForm({
    email: props.email ?? '',
    otp: '',
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

import { onMounted, onUnmounted } from 'vue'
onMounted(() => {
    startTimer()
})
onUnmounted(() => {
    if (timer) clearInterval(timer)
})

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

    // Focus the last filled input
    const nextIndex = Math.min(index + digits.length, 5)
    inputRefs.value[nextIndex]?.focus()
}

const handleKeyDown = (index, event) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        inputRefs.value[index - 1]?.focus()
    }
}

const submit = () => {
    form.post(route('verify-otp'), {
        preserveScroll: true,
    })
}

const resendOtp = () => {
    if (!canResend.value) return

    router.post(route('verify-otp.resend'), { email: form.email }, {
        preserveScroll: true,
        onSuccess: () => {
            otpDigits.value = ['', '', '', '', '', '']
            form.reset('otp')
            startTimer()
        },
    })
}
</script>

<template>
    <AuthLayout>
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Xác thực OTP" />

            <!-- Brand Header -->
            <div class="mb-2 flex flex-col items-center animate-in fade-in slide-in-from-top-4 duration-700">
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

        <div class="w-full max-w-[480px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2.5rem] border border-white/10 overflow-hidden animate-in zoom-in-95 duration-700">
            <div class="px-7 py-5">
                <!-- Title Section -->
                <div class="mb-4 relative flex items-center justify-center min-h-[50px]">
                    <!-- Icon on the Left -->
                    <div class="absolute left-0">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center border border-white/10 text-blue-400/80 shadow-lg ring-1 ring-white/5">
                            <ShieldCheck :size="20" />
                        </div>
                    </div>

                    <!-- Centered Text -->
                    <div class="text-center space-y-1">
                        <h2 class="font-display text-2xl font-black text-white tracking-tight uppercase leading-loose">
                            XÁC THỰC OTP
                        </h2>
                        <div class="flex items-center justify-center gap-2 opacity-40">
                            <div class="h-px w-4 bg-white"></div>
                            <p class="text-[8px] text-white font-bold uppercase tracking-[0.2em]">Hệ thống thư viện điện tử UTC</p>
                            <div class="h-px w-4 bg-white"></div>
                        </div>
                    </div>
                </div>

                <!-- Status Alert -->
                <div v-if="status" class="mb-4 animate-in fade-in zoom-in-95 duration-500">
                    <div class="relative overflow-hidden rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-2.5">
                        <div class="relative flex items-center gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500/70">
                                <Mail :size="16" class="animate-pulse" />
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

                <!-- Info Section -->
                <div class="text-center mb-5 space-y-1">
                    <p class="text-slate-600 text-[10px] font-bold uppercase tracking-widest opacity-80 leading-tight">
                        Mã xác minh đã được gửi tới
                    </p>
                    <p class="text-base font-bold text-slate-300 tracking-wide leading-tight">{{ email }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex justify-between gap-2 sm:gap-3 px-2">
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
                                class="w-full h-14 text-center text-xl font-bold rounded-xl border-2 border-white/5 bg-white/5 text-white focus:bg-blue-600/10 focus:border-blue-500/40 focus:ring-0 transition-all duration-200 outline-none"
                                :class="{ 'border-red-500/40 bg-red-500/5': form.errors.otp }"
                            />
                        </template>
                    </div>
                    <p v-if="form.errors.otp" class="text-xs text-red-500 font-bold text-center animate-shake uppercase tracking-wider">{{ form.errors.otp }}</p>

                    <div class="flex items-center justify-end gap-2 pr-1">
                        <p class="text-slate-600 font-bold text-[10px] uppercase tracking-widest">Chưa nhận được mã?</p>
                        <button
                            type="button"
                            @click="resendOtp"
                            :disabled="!canResend || form.processing"
                            class="flex items-center gap-1.5 font-bold text-[11px] transition-all uppercase tracking-wide"
                            :class="canResend ? 'text-blue-400 hover:text-blue-300' : 'text-slate-500 cursor-not-allowed'"
                        >
                            <RefreshCw :size="11" :class="{ 'animate-spin': form.processing }" />
                            <span>{{ canResend ? 'Gửi lại mã' : `sau ${countdown}s` }}</span>
                        </button>
                    </div>

                    <Button
                        type="submit"
                        class="group relative w-full h-14 overflow-hidden rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_8px_20px_rgba(37,99,235,0.3)] transition-all hover:bg-blue-500 active:scale-[0.98] disabled:opacity-50 uppercase tracking-widest"
                        :disabled="form.processing"
                    >
                        <span class="flex items-center gap-2">
                            {{ form.processing ? 'ĐANG XÁC THỰC...' : 'XÁC NHẬN & HOÀN TẤT' }}
                            <ArrowRight v-if="!form.processing" :size="18" class="group-hover:translate-x-1 transition-transform" />
                        </span>
                    </Button>
                </form>

                <div class="mt-4 flex flex-col items-center gap-4">
                    <div class="flex flex-col items-center gap-3 w-full pt-3 border-t border-white/5">
                        <Link
                            :href="route('register')"
                            class="flex items-center justify-center gap-2 w-full h-12 rounded-xl bg-white/5 border border-white/10 text-slate-400 font-bold text-sm hover:bg-white/10 hover:text-white transition-all active:scale-[0.98]"
                        >
                            <Undo2 :size="16" />
                            Quay lại trang Đăng ký
                        </Link>
                    </div>
                </div>
            </div>
        </div>

            <div class="mt-6 text-center">
                <p class="text-white/90 text-xs font-bold tracking-widest uppercase drop-shadow-md">
                    &copy; 2026 UTC eLibrary System &bull; Version 2.0
                </p>
            </div>
        </div>
    </AuthLayout>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
.animate-shake {
    animation: shake 0.2s ease-in-out 0s 2;
}
</style>
