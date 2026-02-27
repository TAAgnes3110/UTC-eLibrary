<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';

const darkMode = ref(typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));
const language = ref('vi');

const toggles = ref({ darkMode: darkMode.value });

const toggleDark = () => {
    toggles.value.darkMode = !toggles.value.darkMode;
    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', toggles.value.darkMode);
        localStorage.setItem('theme', toggles.value.darkMode ? 'dark' : 'light');
    }
};

const save = () => {
    // TODO: API lưu giao diện (ngôn ngữ, v.v.)
};
</script>

<template>
    <Head title="Cài đặt giao diện - Admin" />
    <AdminLayout
        title="Cài đặt giao diện"
        :breadcrumbs="[
            { label: 'Hệ thống' },
            { label: 'Cấu hình thư viện' },
            { label: 'Cài đặt giao diện' },
        ]"
    >
        <div class="space-y-6 animate-in fade-in-50 duration-500 max-w-2xl">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Cài đặt giao diện</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Chế độ tối, ngôn ngữ hiển thị</p>
            </div>

            <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="flex items-center gap-4 px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                        <Icon icon="lucide:settings" class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Giao diện & Hệ thống</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Tùy chọn hiển thị</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Chế độ tối</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Chuyển giao diện sang nền tối</p>
                        </div>
                        <button
                            type="button"
                            @click="toggleDark"
                            :class="[
                                'relative w-12 h-7 rounded-full transition-colors duration-300 shrink-0',
                                toggles.darkMode ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600'
                            ]"
                        >
                            <span
                                :class="[
                                    'absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white shadow-md transition-transform duration-300',
                                    toggles.darkMode ? 'translate-x-5' : 'translate-x-0'
                                ]"
                            />
                        </button>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Ngôn ngữ hiển thị</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Chọn ngôn ngữ giao diện</p>
                        </div>
                        <select
                            v-model="language"
                            class="h-10 px-4 pr-9 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 outline-none appearance-none cursor-pointer min-w-[140px]"
                        >
                            <option value="vi" class="bg-white dark:bg-slate-900">Tiếng Việt</option>
                            <option value="en" class="bg-white dark:bg-slate-900">English</option>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <Button type="button" @click="save" class="h-9 px-5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white">
                        Lưu thay đổi
                    </Button>
                </div>
            </section>

            <!-- Vùng nguy hiểm -->
            <section class="bg-white dark:bg-slate-900 rounded-2xl border border-red-200 dark:border-red-900/40 overflow-hidden shadow-sm">
                <div class="flex items-center gap-4 px-6 py-4 border-b border-red-100 dark:border-red-900/30">
                    <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                        <Icon icon="lucide:alert-triangle" class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="font-bold text-red-700 dark:text-red-400">Vùng nguy hiểm</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Thao tác không thể hoàn tác</p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-6 py-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Xóa tài khoản</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Xóa vĩnh viễn tài khoản và toàn bộ dữ liệu liên quan</p>
                    </div>
                    <button
                        type="button"
                        class="h-10 px-5 rounded-xl border-2 border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-xs font-bold hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shrink-0"
                    >
                        Xóa tài khoản
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
