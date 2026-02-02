<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const showModal = ref(false);
const form = useForm({
    name: '',
    address: '',
    phone: '',
});

const publishers = ref([
    { id: 1, name: 'NXB Bách Khoa', address: 'Số 1 Đại Cồ Việt, Hà Nội', phone: '024 3869 2242' },
    { id: 2, name: 'NXB Giáo Dục', address: '81 Trần Hưng Đạo, Hà Nội', phone: '024 3822 0801' },
    { id: 3, name: 'NXB ĐH Quốc Gia', address: '16 Hàng Chuối, Hà Nội', phone: '024 3971 4896' },
    { id: 4, name: 'NXB Giao Thông Vận Tải', address: '80A Trần Hưng Đạo, Hà Nội', phone: '024 3942 2167' },
]);

const searchQuery = ref('');
const filteredPublishers = computed(() => {
    return publishers.value.filter(p => p.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});
</script>

<template>
    <Head title="Quản lý NXB - Admin" />
    <AdminLayout title="Quản lý Nhà xuất bản">
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <div class="flex justify-between items-center">
                <Button @click="showModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl">
                    <Icon icon="lucide:building-2" class="w-4 h-4 mr-2" />
                    Thêm NXB
                </Button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400">Tên Nhà Xuất Bản</th>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400">Địa chỉ</th>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400">Liên hệ</th>
                            <th class="p-5 text-[10px] uppercase font-black text-slate-400 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="p in filteredPublishers" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="p-5 font-bold text-slate-900 dark:text-white">{{ p.name }}</td>
                            <td class="p-5 text-sm text-slate-600 dark:text-slate-400">{{ p.address }}</td>
                            <td class="p-5 text-sm text-slate-600 dark:text-slate-400">{{ p.phone }}</td>
                            <td class="p-5">
                                <div class="flex justify-end gap-2">
                                    <button class="p-2 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg">
                                        <Icon icon="lucide:edit-2" class="w-4 h-4" />
                                    </button>
                                    <button class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg">
                                        <Icon icon="lucide:trash-2" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg animate-in zoom-in-95">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between">
                        <h3 class="text-xl font-black dark:text-white">Thêm NXB</h3>
                        <button @click="showModal = false"><Icon icon="lucide:x" class="text-slate-400" /></button>
                    </div>
                    <div class="p-8 space-y-4">
                        <div>
                            <label class="block text-sm font-bold mb-2 dark:text-slate-300">Tên NXB</label>
                            <Input v-model="form.name" class="h-12 rounded-xl dark:bg-slate-800 dark:border-none dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2 dark:text-slate-300">Số điện thoại</label>
                            <Input v-model="form.phone" class="h-12 rounded-xl dark:bg-slate-800 dark:border-none dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2 dark:text-slate-300">Địa chỉ</label>
                            <textarea v-model="form.address" class="w-full p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border-none h-24 dark:text-white focus:ring-2 focus:ring-indigo-600/20"></textarea>
                        </div>
                    </div>
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <Button @click="showModal = false" variant="outline" class="rounded-xl dark:text-slate-300">Hủy</Button>
                        <Button class="rounded-xl bg-indigo-600 text-white">Lưu</Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
