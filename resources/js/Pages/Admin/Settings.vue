<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';

const darkMode = ref(document.documentElement.classList.contains('dark'));
const language = ref('vi');

const settingsGroups = [
    {
        title: 'Giao diện',
        icon: 'lucide:palette',
        iconBg: 'bg-purple-50 dark:bg-purple-900/20',
        iconColor: 'text-purple-600 dark:text-purple-400',
        items: [
            { label: 'Chế độ tối', description: 'Chuyển giao diện sang nền tối', type: 'toggle', key: 'darkMode' },
        ]
    },
    {
        title: 'Ngôn ngữ & Vùng',
        icon: 'lucide:globe',
        iconBg: 'bg-blue-50 dark:bg-blue-900/20',
        iconColor: 'text-blue-600 dark:text-blue-400',
        items: [
            { label: 'Ngôn ngữ hiển thị', description: 'Chọn ngôn ngữ giao diện', type: 'select', key: 'language', options: [
                { value: 'vi', label: 'Tiếng Việt' },
                { value: 'en', label: 'English' },
            ]},
        ]
    },
    {
        title: 'Thông báo',
        icon: 'lucide:bell',
        iconBg: 'bg-amber-50 dark:bg-amber-900/20',
        iconColor: 'text-amber-600 dark:text-amber-400',
        items: [
            { label: 'Thông báo email', description: 'Nhận email khi có cập nhật quan trọng', type: 'toggle', key: 'emailNotif' },
            { label: 'Thông báo trình duyệt', description: 'Hiển thị popup trên trình duyệt', type: 'toggle', key: 'browserNotif' },
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
    if (key === 'darkMode') {
        document.documentElement.classList.toggle('dark', toggles.value[key]);
        localStorage.setItem('theme', toggles.value[key] ? 'dark' : 'light');
    }
};
</script>

<template>
    <AdminLayout title="Cài đặt">
        <Head title="Cài đặt" />

        <div class="max-w-3xl space-y-6">
            <template v-for="(group, gi) in settingsGroups" :key="gi">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <!-- Group Header -->
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', group.iconBg, group.iconColor]">
                            <Icon :icon="group.icon" class="w-5 h-5" />
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white">{{ group.title }}</h3>
                    </div>

                    <!-- Items -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div v-for="(item, ii) in group.items" :key="ii"
                            class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.label }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ item.description }}</p>
                            </div>

                            <!-- Toggle -->
                            <button v-if="item.type === 'toggle'" @click="toggleSetting(item.key)"
                                :class="[
                                    'relative w-12 h-7 rounded-full transition-colors duration-300 shrink-0',
                                    toggles[item.key] ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600'
                                ]">
                                <span :class="[
                                    'absolute top-0.5 w-6 h-6 rounded-full bg-white shadow-md transition-transform duration-300',
                                    toggles[item.key] ? 'translate-x-[22px]' : 'translate-x-0.5'
                                ]"></span>
                            </button>

                            <!-- Select -->
                            <select v-else-if="item.type === 'select'" v-model="language"
                                class="h-9 px-3 pr-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 outline-none appearance-none cursor-pointer">
                                <option v-for="opt in item.options" :key="opt.value" :value="opt.value" class="bg-white dark:bg-slate-900">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Danger Zone -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-red-200 dark:border-red-900/30 overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-red-100 dark:border-red-900/20">
                    <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-600 dark:text-red-400">
                        <Icon icon="lucide:alert-triangle" class="w-5 h-5" />
                    </div>
                    <h3 class="font-bold text-red-700 dark:text-red-400">Vùng nguy hiểm</h3>
                </div>
                <div class="px-6 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Xóa tài khoản</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Xóa vĩnh viễn tài khoản và toàn bộ dữ liệu</p>
                    </div>
                    <button class="px-4 h-9 rounded-xl border-2 border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-xs font-bold hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                        Xóa tài khoản
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
