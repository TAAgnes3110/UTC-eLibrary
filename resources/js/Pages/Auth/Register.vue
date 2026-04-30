<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import BrandHeader from "@/Components/Auth/BrandHeader.vue";
import AuthCardTitle from "@/Components/Auth/AuthCardTitle.vue";
import AuthFooter from "@/Components/Auth/AuthFooter.vue";
import SubmitButton from "@/Components/Auth/SubmitButton.vue";
import FormField from "@/Components/Auth/FormField.vue";
import {
    User,
    Phone,
    Mail,
    Lock,
    CreditCard,
    ArrowRight,
    UserPlus,
    Eye,
    EyeOff,
    Calendar,
    Users,
    Home,
    ChevronDown,
} from "lucide-vue-next";
import { useRegisterPage } from "@/composables/auth/useRegisterPage";

const {
    form,
    dateInputRef,
    showPassword,
    showPasswordConfirmation,
    submit,
} = useRegisterPage();
</script>

<template>
    <AuthLayout>
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Đăng ký Độc giả ngoài" />

            <BrandHeader />

            <!-- Registration Card -->
            <div
                class="w-full max-w-[680px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2.5rem] border border-white/10 overflow-hidden animate-in zoom-in-95 fade-in duration-700"
            >
                <div class="px-4 py-4 sm:px-6 sm:py-5">
                    <AuthCardTitle title="ĐĂNG KÝ">
                        <template #icon
                            ><UserPlus :size="24" class="animate-pulse"
                        /></template>
                    </AuthCardTitle>

                    <form
                        @submit.prevent="submit"
                        class="space-y-3"
                        novalidate
                    >
                        <!-- Primary Identity Group -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormField
                                id="name"
                                label="Họ và tên"
                                :error="form.errors.name"
                                required
                            >
                                <template #icon><User :size="18" /></template>
                                <input
                                    id="name"
                                    type="text"
                                    placeholder="Nhập họ và tên"
                                    v-model="form.name"
                                    class="h-11 w-full border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300"
                                />
                            </FormField>
                            <FormField
                                id="code"
                                label="Số CCCD / CMND"
                                :error="form.errors.code"
                                required
                            >
                                <template #icon
                                    ><CreditCard :size="18"
                                /></template>
                                <input
                                    id="code"
                                    type="text"
                                    placeholder="Nhập số định danh"
                                    v-model="form.code"
                                    class="h-11 w-full border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300"
                                />
                            </FormField>
                        </div>

                        <!-- Contact Group -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormField
                                id="email"
                                label="Email cá nhân"
                                :error="form.errors.email"
                                required
                            >
                                <template #icon><Mail :size="18" /></template>
                                <input
                                    id="email"
                                    type="email"
                                    placeholder="example@email.com"
                                    v-model="form.email"
                                    class="h-11 w-full border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300"
                                />
                            </FormField>
                            <FormField
                                id="phone"
                                label="Số điện thoại"
                                :error="form.errors.phone"
                                required
                            >
                                <template #icon><Phone :size="18" /></template>
                                <input
                                    id="phone"
                                    type="tel"
                                    placeholder="0xxxxxxxxx"
                                    v-model="form.phone"
                                    class="h-11 w-full border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300"
                                />
                            </FormField>
                        </div>
                        <!-- Personal Details Group -->
                        <div
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                        >
                            <FormField
                                id="date_of_birth"
                                label="Ngày sinh"
                                :error="form.errors.date_of_birth"
                            >
                                <template #icon>
                                    <Calendar
                                        :size="18"
                                        class="cursor-pointer hover:text-blue-300"
                                        @click="dateInputRef?.showPicker()"
                                    />
                                </template>
                                <input
                                    ref="dateInputRef"
                                    id="date_of_birth"
                                    type="date"
                                    v-model="form.date_of_birth"
                                    class="premium-date-input h-11 w-full border border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white focus:bg-blue-600/10 focus:border-blue-500/50 outline-none shadow-inner transition-all duration-300 [color-scheme:dark]"
                                />
                            </FormField>

                            <FormField
                                id="gender"
                                label="Giới tính"
                                :error="form.errors.gender"
                            >
                                <template #icon><Users :size="18" /></template>
                                <select
                                    id="gender"
                                    v-model="form.gender"
                                    class="h-11 w-full border border-white/5 bg-white/5 pl-11 pr-10 rounded-xl text-white focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner appearance-none outline-none transition-all duration-300 cursor-pointer"
                                >
                                    <option
                                        value="male"
                                        class="bg-slate-900 border-none py-2"
                                    >
                                        Nam
                                    </option>
                                    <option
                                        value="female"
                                        class="bg-slate-900 border-none py-2"
                                    >
                                        Nữ
                                    </option>
                                    <option
                                        value="other"
                                        class="bg-slate-900 border-none py-2"
                                    >
                                        Khác
                                    </option>
                                </select>
                                <ChevronDown
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 group-focus-within:text-blue-400"
                                    :size="14"
                                />
                            </FormField>

                        </div>

                        <!-- Password Group -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormField
                                id="password"
                                label="Mật khẩu"
                                :error="form.errors.password"
                                required
                            >
                                <template #icon><Lock :size="18" /></template>
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="8+ ký tự"
                                    v-model="form.password"
                                    class="h-11 w-full border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-500/5 focus:border-blue-500/50 shadow-inner ring-0 transition-all duration-300"
                                    :class="{
                                        'border-red-500/50 focus:border-red-500/60':
                                            form.errors.password,
                                    }"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5"
                                >
                                    <Eye v-if="!showPassword" :size="18" />
                                    <EyeOff v-else :size="18" />
                                </button>
                            </FormField>
                            <FormField
                                id="password_confirmation"
                                label="Xác nhận mật khẩu"
                                :error="form.errors.password_confirmation"
                                required
                            >
                                <template #icon><Lock :size="18" /></template>
                                <input
                                    id="password_confirmation"
                                    :type="
                                        showPasswordConfirmation
                                            ? 'text'
                                            : 'password'
                                    "
                                    placeholder="Nhập lại mật khẩu"
                                    v-model="form.password_confirmation"
                                    class="h-11 w-full border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-500/5 focus:border-blue-500/50 shadow-inner ring-0 transition-all duration-300"
                                    :class="{
                                        'border-red-500/50 focus:border-red-500/60':
                                            form.errors.password_confirmation,
                                    }"
                                />
                                <button
                                    type="button"
                                    @click="
                                        showPasswordConfirmation =
                                            !showPasswordConfirmation
                                    "
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5"
                                >
                                    <Eye
                                        v-if="!showPasswordConfirmation"
                                        :size="18"
                                    />
                                    <EyeOff v-else :size="18" />
                                </button>
                            </FormField>
                        </div>

                        <!-- Address Field -->
                        <FormField
                            id="address"
                            label="Địa chỉ cư trú"
                            :error="form.errors.address"
                        >
                            <template #icon><Home :size="18" /></template>
                            <textarea
                                id="address"
                                placeholder="Nhập địa chỉ cư trú hiện tại"
                                v-model="form.address"
                                class="w-full min-h-[40px] border-white/5 bg-white/5 pl-11 pr-4 py-1.5 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none resize-none leading-relaxed text-sm"
                            ></textarea>
                        </FormField>

                        <div class="pt-1">
                            <SubmitButton :loading="form.processing">
                                Đăng ký
                                <ArrowRight
                                    :size="20"
                                    class="group-hover:translate-x-1 transition-transform"
                                />
                            </SubmitButton>
                        </div>
                    </form>

                    <div
                        class="text-center mt-1 pt-1 border-t border-white/5 space-y-2"
                    >
                        <p class="text-slate-500 font-semibold text-[13px]">
                            Bạn đã là thành viên?
                        </p>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center justify-center gap-3 w-full h-12 rounded-2xl border-2 border-white/10 bg-white/5 text-base font-black text-white hover:bg-white/10 hover:border-blue-500/30 transition-all duration-300 active:scale-[0.98] shadow-lg shadow-black/20"
                        >
                            <span class="leading-none">ĐĂNG NHẬP NGAY</span>
                        </Link>
                    </div>
                </div>
            </div>

            <AuthFooter />
        </div>
    </AuthLayout>
</template>

<style scoped>
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
</style>
