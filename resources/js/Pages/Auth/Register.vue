<script setup>
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { User, Phone, Mail, Lock, CreditCard, ArrowRight, UserPlus, Eye, EyeOff, Calendar, Users, Home, ChevronDown } from 'lucide-vue-next'
import { ref } from 'vue'

const form = useForm({
    code: '',
    name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    gender: 'male',
    address: '',
    password: '',
    password_confirmation: '',
})

const dateInputRef = ref(null)

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <AuthLayout>
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Đăng ký Độc giả ngoài" />

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

            <!-- Registration Card -->
            <div class="w-full max-w-[600px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2.5rem] border border-white/10 overflow-hidden animate-in zoom-in-95 fade-in duration-700">
                <div class="px-5 py-5 sm:px-8">
                    <!-- Title Section -->
                    <div class="mb-4 relative flex items-center min-h-[50px]">
                        <!-- Icon on the Left -->
                        <div class="absolute left-0 group">
                            <div class="absolute -inset-4 bg-blue-500/20 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center border border-white/10 text-blue-400 shadow-2xl ring-1 ring-white/5">
                                <UserPlus :size="24" class="animate-pulse" />
                            </div>
                        </div>

                        <!-- Centered Text -->
                        <div class="flex-1 text-center pl-10 space-y-0.5">
                            <h2 class="font-display text-2xl font-black text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-b from-white to-white/70 uppercase tracking-widest leading-normal">
                                ĐĂNG KÝ
                            </h2>
                            <div class="flex items-center justify-center gap-2">
                                <div class="h-px w-6 bg-blue-500/20"></div>
                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-[0.3em]">Hệ thống thư viện điện tử UTC</p>
                                <div class="h-px w-6 bg-blue-500/20"></div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Primary Identity Group -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2 group">
                                <Label for="name" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                    Họ và tên <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                    <User class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <Input
                                        id="name"
                                        type="text"
                                        placeholder="Nhập họ và tên"
                                        v-model="form.name"
                                        class="h-12 w-full border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.name" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-2 group">
                                <Label for="code" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                    Số CCCD / CMND <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                    <CreditCard class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <Input
                                        id="code"
                                        type="text"
                                        placeholder="Nhập số định danh"
                                        v-model="form.code"
                                        class="h-12 w-full border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.code" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.code }}</p>
                            </div>
                        </div>

                        <!-- Contact Group -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                             <div class="space-y-2 group">
                                <Label for="email" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                    Email cá nhân <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                    <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <Input
                                        id="email"
                                        type="email"
                                        placeholder="example@email.com"
                                        v-model="form.email"
                                        class="h-12 w-full border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.email" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.email }}</p>
                            </div>
                            <div class="space-y-2 group">
                                <Label for="phone" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                    Số điện thoại <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                    <Phone class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <Input
                                        id="phone"
                                        type="tel"
                                        placeholder="0xxxxxxxxx"
                                        v-model="form.phone"
                                        class="h-12 w-full border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.phone" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.phone }}</p>
                            </div>
                        </div>

                        <!-- Personal Details Group -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2 group">
                                 <Label for="date_of_birth" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">Ngày sinh</Label>
                                 <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                    <Calendar
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400 cursor-pointer hover:text-blue-300 z-10"
                                        :size="18"
                                        @click="dateInputRef?.showPicker()"
                                    />
                                    <input
                                        ref="dateInputRef"
                                        id="date_of_birth"
                                        type="date"
                                        v-model="form.date_of_birth"
                                        class="premium-date-input h-12 w-full border border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white focus:bg-blue-600/10 focus:border-blue-500/50 outline-none shadow-inner transition-all duration-300 [color-scheme:dark]"
                                    />
                                 </div>
                            </div>
                            <div class="space-y-2 group">
                                 <Label for="gender" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">Giới tính</Label>
                                 <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                    <Users class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <select
                                        id="gender"
                                        v-model="form.gender"
                                        class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-10 rounded-xl text-white focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner appearance-none outline-none transition-all duration-300 cursor-pointer"
                                    >
                                        <option value="male" class="bg-slate-900 border-none py-2">Nam</option>
                                        <option value="female" class="bg-slate-900 border-none py-2">Nữ</option>
                                        <option value="other" class="bg-slate-900 border-none py-2">Khác</option>
                                    </select>
                                    <ChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 group-focus-within:text-blue-400" :size="14" />
                                 </div>
                            </div>
                        </div>

                        <!-- Address Field -->
                        <div class="space-y-1 group">
                            <Label for="address" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">Địa chỉ cư trú</Label>
                            <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                <Home class="absolute left-3.5 top-3 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                <textarea
                                    id="address"
                                    placeholder="Nhập địa chỉ cư trú hiện tại"
                                    v-model="form.address"
                                    class="w-full min-h-[50px] border-white/5 bg-white/5 pl-11 pr-4 py-2 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none resize-none leading-relaxed text-sm"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Password Group -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2 group">
                                <Label for="password" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                    Mật khẩu <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative group">
                                    <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <Input
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="8+ ký tự"
                                        v-model="form.password"
                                        class="h-12 w-full border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-500/5 focus:border-blue-500/50 shadow-inner ring-0 transition-all duration-300"
                                        :class="{ 'border-red-500/50 focus:border-red-500/60': form.errors.password }"
                                        required
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
                            <div class="space-y-2 group">
                                <Label for="password_confirmation" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                    Xác nhận mật khẩu <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative group">
                                    <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                    <Input
                                        id="password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        placeholder="Nhập lại mật khẩu"
                                        v-model="form.password_confirmation"
                                        class="h-12 w-full border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-500/5 focus:border-blue-500/50 shadow-inner ring-0 transition-all duration-300"
                                        :class="{ 'border-red-500/50 focus:border-red-500/60': form.errors.password_confirmation }"
                                        required
                                    />
                                    <button
                                        type="button"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5"
                                    >
                                        <Eye v-if="!showPasswordConfirmation" :size="18" />
                                        <EyeOff v-else :size="18" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password_confirmation" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.password_confirmation }}</p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <Button
                                type="submit"
                                class="group relative w-full h-14 overflow-hidden rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_0_20px_rgba(59,130,246,0.5)] transition-all hover:bg-blue-500 hover:shadow-[0_0_30px_rgba(59,130,246,0.7)] active:scale-[0.98] disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                <div class="absolute inset-0 flex items-center justify-center transition-all duration-300 group-hover:translate-x-1">
                                    <span v-if="form.processing" class="flex items-center gap-2">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Đang xử lý...
                                    </span>
                                    <span v-else class="flex items-center gap-2 uppercase tracking-widest leading-none">
                                        Đăng ký <ArrowRight :size="20" class="group-hover:translate-x-1 transition-transform" />
                                    </span>
                                </div>
                            </Button>
                        </div>
                    </form>

                    <div class="text-center mt-2 pt-2 border-t border-white/5 space-y-2">
                        <p class="text-slate-500 font-semibold text-[13px]">Bạn đã là thành viên?</p>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center justify-center gap-3 w-full h-12 rounded-2xl border-2 border-white/10 bg-white/5 text-base font-black text-white hover:bg-white/10 hover:border-blue-500/30 transition-all duration-300 active:scale-[0.98] shadow-lg shadow-black/20"
                        >
                            <span class="leading-none">ĐĂNG NHẬP NGAY</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 text-center animate-in fade-in slide-in-from-bottom-4 duration-1000 delay-300">
                <p class="text-white/90 text-xs font-bold tracking-widest uppercase drop-shadow-md">
                    &copy; 2026 UTC eLibrary System &bull; Version 2.0
                </p>
            </div>
        </div>
    </AuthLayout>
</template>

<style scoped>
/* Custom Styling for Date Input */
.custom-date-input::-webkit-calendar-picker-indicator {
    background: transparent;
    bottom: 0;
    color: transparent;
    cursor: pointer;
    height: auto;
    left: 0;
    position: absolute;
    right: 0;
    top: 0;
    width: auto;
    z-index: 10;
}

.custom-date-input {
    position: relative;
}

/* Premium Native Date Input Styling */
.premium-date-input::-webkit-datetime-edit {
    display: flex;
    padding: 0;
}

.premium-date-input::-webkit-datetime-edit-fields-wrapper {
    padding: 0;
}

.premium-date-input::-webkit-datetime-edit-text {
    color: rgba(255, 255, 255, 0.3);
    padding: 0 0.2em;
}

.premium-date-input::-webkit-datetime-edit-month-field,
.premium-date-input::-webkit-datetime-edit-day-field,
.premium-date-input::-webkit-datetime-edit-year-field {
    color: #fff;
    text-transform: uppercase;
    padding: 0 2px;
    border-radius: 4px;
    transition: all 0.2s;
}

.premium-date-input::-webkit-datetime-edit-month-field:focus,
.premium-date-input::-webkit-datetime-edit-day-field:focus,
.premium-date-input::-webkit-datetime-edit-year-field:focus {
    background-color: rgba(59, 130, 246, 0.3);
    color: #fff;
    outline: none;
}

/* Hide default browser UI elements */
.premium-date-input::-webkit-calendar-picker-indicator {
    position: absolute;
    right: 12px;
    opacity: 0;
    cursor: pointer;
    z-index: 5;
}

.premium-date-input::-webkit-inner-spin-button,
.premium-date-input::-webkit-clear-button {
    display: none;
    -webkit-appearance: none;
}

/* Optional: Soft glassmorphism background animation */
@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; }
    50% { opacity: 0.15; }
}
</style>

