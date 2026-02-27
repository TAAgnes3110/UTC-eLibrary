<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';

const darkMode = ref(typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));
const language = ref('vi');

const settingsGroups = [
    {
        title: 'Quy định mượn trả',
        description: 'Cấu hình số lượng, thời hạn mượn và gia hạn',
        icon: 'lucide:clipboard-list',
        iconBg: 'bg-indigo-100 dark:bg-indigo-900/30',
        iconColor: 'text-indigo-600 dark:text-indigo-400',
        items: [
            { label: 'Số sách tối đa', description: 'Số lượng sách tối đa một độc giả được mượn', type: 'number', key: 'maxBooks', value: 5 },
            { label: 'Số ngày mượn tối đa', description: 'Thời hạn mượn sách mặc định (ngày)', type: 'number', key: 'maxDays', value: 14 },
            { label: 'Số lần gia hạn', description: 'Số lần tối đa được phép gia hạn một cuốn sách', type: 'number', key: 'maxExtensions', value: 1 },
            { label: 'Số ngày gia hạn', description: 'Số ngày được cộng thêm mỗi lần gia hạn', type: 'number', key: 'extensionDays', value: 7 },
        ]
    },
    {
        title: 'Nội dung thư viện',
        description: 'Nội quy và hướng dẫn hiển thị cho độc giả',
        icon: 'lucide:file-text',
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/30',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
        items: [
            { label: 'Nội quy thư viện', description: 'Hiển thị trên trang chủ và thông báo cho độc giả', type: 'textarea', key: 'libraryRules' },
            { label: 'Hướng dẫn sử dụng', description: 'Hướng dẫn độc giả cách tra cứu và mượn sách', type: 'textarea', key: 'userGuide' },
        ]
    },
    {
        title: 'Giao diện & Hệ thống',
        description: 'Chế độ tối, ngôn ngữ hiển thị',
        icon: 'lucide:settings',
        iconBg: 'bg-purple-100 dark:bg-purple-900/30',
        iconColor: 'text-purple-600 dark:text-purple-400',
        items: [
            { label: 'Chế độ tối', description: 'Chuyển giao diện sang nền tối', type: 'toggle', key: 'darkMode' },
            { label: 'Ngôn ngữ hiển thị', description: 'Chọn ngôn ngữ giao diện', type: 'select', key: 'language', options: [
                { value: 'vi', label: 'Tiếng Việt' },
                { value: 'en', label: 'English' },
            ]},
        ]
    },
];

const toggles = ref({
    darkMode: darkMode.value,
    emailNotif: true,
    browserNotif: false,
});

const toggleSetting = (key) => {
    toggles.value[key] = !toggles.value[key];
    if (key === 'darkMode' && typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', toggles.value[key]);
        localStorage.setItem('theme', toggles.value[key] ? 'dark' : 'light');
    }
};

const saveGroup = (group) => {
    // TODO: Gửi API lưu theo group
};
</script>

<template>
    <AdminLayout
        title="Cấu hình thông tin thư viện"
        :breadcrumbs="[
            { label: 'Hệ thống' },
            { label: 'Cấu hình thông tin thư viện' },
        ]"
    >
        <Head title="Cấu hình thông tin thư viện - Admin" />

        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Cấu hình thông tin thư viện</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Thiết lập quy định mượn trả, nội dung và giao diện hệ thống</p>
            </div>

            <div class="grid gap-6 max-w-3xl">
                <template v-for="(group, gi) in settingsGroups" :key="gi">
                    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="flex items-center gap-4 px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center shrink-0', group.iconBg, group.iconColor]">
                                <Icon :icon="group.icon" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-900 dark:text-white">{{ group.title }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ group.description }}</p>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template v-for="(item, ii) in group.items" :key="ii">
                                <!-- Row: toggle / number / select -->
                                <div v-if="item.type !== 'textarea'"
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.label }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ item.description }}</p>
                                    </div>
                                    <div class="shrink-0 sm:pl-4">
                                        <button v-if="item.type === 'toggle'" @click="toggleSetting(item.key)"
                                            :class="[
                                                'relative w-12 h-7 rounded-full transition-colors duration-300',
                                                toggles[item.key] ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600'
                                            ]">
                                            <span :class="[
                                                'absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white shadow-md transition-transform duration-300',
                                                toggles[item.key] ? 'translate-x-5' : 'translate-x-0'
                                            ]"></span>
                                        </button>
                                        <input v-else-if="item.type === 'number'" type="number" min="1" max="99"
                                            class="w-20 h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-center text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
                                            v-model="item.value"
                                        />
                                        <select v-else-if="item.type === 'select'" v-model="language"
                                            class="h-10 px-4 pr-9 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 outline-none appearance-none cursor-pointer min-w-[140px]">
                                            <option v-for="opt in item.options" :key="opt.value" :value="opt.value" class="bg-white dark:bg-slate-900">
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Block: textarea -->
                                <div v-else class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/20">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white mb-1">{{ item.label }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">{{ item.description }}</p>
                                    <textarea
                                        class="w-full min-h-[120px] p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-blue-500/20 outline-none resize-none"
                                        :placeholder="'Nhập ' + item.label.toLowerCase() + '...'"
                                    ></textarea>
                                </div>
                            </template>
                        </div>

                        <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                            <Button type="button" @click="saveGroup(group)"
                                class="h-9 px-5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition-all shadow-sm">
                                Lưu thay đổi
                            </Button>
                        </div>
                    </section>
                </template>

                <!-- Vùng nguy hiểm -->
                <section class="bg-white dark:bg-slate-900 rounded-2xl border border-red-200 dark:border-red-900/40 overflow-hidden shadow-sm max-w-3xl">
                    <div class="flex items-center gap-4 px-6 py-4 border-b border-red-100 dark:border-red-900/30">
                        <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                            <Icon icon="lucide:alert-triangle" class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-red-700 dark:text-red-400">Vùng nguy hiểm</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Thao tác không thể hoàn tác</p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Xóa tài khoản</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Xóa vĩnh viễn tài khoản và toàn bộ dữ liệu liên quan</p>
                        </div>
                        <button type="button"
                            class="h-10 px-5 rounded-xl border-2 border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-xs font-bold hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shrink-0">
                            Xóa tài khoản
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
