<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';

const items = ref([
    { label: 'Số sách tối đa', description: 'Số lượng sách tối đa một độc giả được mượn', key: 'maxBooks', value: 5 },
    { label: 'Số ngày mượn tối đa', description: 'Thời hạn mượn sách mặc định (ngày)', key: 'maxDays', value: 14 },
    { label: 'Số lần gia hạn', description: 'Số lần tối đa được phép gia hạn một cuốn sách', key: 'maxExtensions', value: 1 },
    { label: 'Số ngày gia hạn', description: 'Số ngày được cộng thêm mỗi lần gia hạn', key: 'extensionDays', value: 7 },
]);

const save = () => {
    // TODO: API lưu quy định mượn trả
};
</script>

<template>
    <Head title="Quy định mượn trả - Admin" />
    <AdminLayout
        title="Quy định mượn trả"
        :breadcrumbs="[
            { label: 'Hệ thống' },
            { label: 'Cấu hình thư viện' },
            { label: 'Quy định mượn trả' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500 max-w-3xl">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Quy định mượn trả</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Cấu hình số lượng, thời hạn mượn và gia hạn</p>
            </div>

            <!-- Bảng kết quả hiện tại -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kết quả hiện tại</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Tổng hợp quy định mượn trả đang áp dụng</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-12">#</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tham số</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mô tả</th>
                                <th class="p-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right w-24">Giá trị</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(item, i) in items" :key="i" class="admin-table-row">
                                <td class="p-3 text-xs text-slate-500 dark:text-slate-400">{{ i + 1 }}</td>
                                <td class="p-3 text-sm font-semibold text-slate-900 dark:text-white">{{ item.label }}</td>
                                <td class="p-3 text-xs text-slate-600 dark:text-slate-400">{{ item.description }}</td>
                                <td class="p-3 text-right">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-sm font-bold text-slate-900 dark:text-white">
                                        {{ item.value }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <section class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                        <Icon icon="lucide:clipboard-list" class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Chỉnh sửa tham số</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Chỉnh và bấm Lưu thay đổi</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div
                        v-for="(item, i) in items"
                        :key="i"
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.label }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ item.description }}</p>
                        </div>
                        <input
                            v-model.number="item.value"
                            type="number"
                            min="1"
                            max="99"
                            class="w-20 h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-center text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 outline-none shrink-0"
                        />
                    </div>
                </div>
                <div class="px-4 py-3 bg-slate-50/50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <Button type="button" @click="save" class="btn-admin-primary h-8 px-4">
                        Lưu thay đổi
                    </Button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
