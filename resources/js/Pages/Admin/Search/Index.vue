<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    filters: Object
});

const searchQuery = ref(props.filters?.q || '');
const results = ref([
    { id: 1, type: 'book', title: 'Lập trình PHP chuyên sâu', code: 'BK001', details: 'Nguyễn Văn A - 2023', status: 'available' },
    { id: 2, type: 'reader', title: 'Nguyễn Văn Nam', code: '2021601234', details: 'Sinh viên - CNTT1', status: 'active' },
    { id: 3, type: 'author', title: 'Hồ Ngọc Đại', code: 'AU152', details: 'Tác giả sách giáo khoa', status: null },
    { id: 4, type: 'book', title: 'Cơ sở dữ liệu nâng cao', code: 'BK042', details: 'Trần Thị B - 2022', status: 'borrowed' },
]);

const getIcon = (type) => {
    switch (type) {
        case 'book': return 'lucide:book';
        case 'reader': return 'lucide:user';
        case 'author': return 'lucide:user-pen';
        default: return 'lucide:file';
    }
};

const getTypeLabel = (type) => {
    switch (type) {
        case 'book': return 'Sách';
        case 'reader': return 'Độc giả';
        case 'author': return 'Tác giả';
        default: return 'Khác';
    }
};
</script>

<template>
    <Head title="Kết quả tìm kiếm - Admin" />
    <AdminLayout title="Kết quả tìm kiếm">
        <div class="space-y-6 max-w-5xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
            <!-- Search Header -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-50 dark:bg-blue-900/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

                <div class="relative z-10">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Tra cứu hệ thống</h2>
                    <p class="text-slate-500 dark:text-slate-400 mb-6">Hiển thị kết quả tìm kiếm cho: <span class="font-bold text-blue-600">"{{ searchQuery }}"</span></p>

                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <Icon icon="lucide:search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                            <Input v-model="searchQuery" placeholder="Nhập từ khóa tìm kiếm mới..." class="pl-12 h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none text-lg shadow-inner" />
                        </div>
                        <Button class="h-14 px-8 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-lg shadow-blue-500/20">
                            Tìm kiếm lại
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="grid grid-cols-1 gap-4">
                <div v-if="results.length === 0" class="py-20 text-center">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                        <Icon icon="lucide:search-x" class="w-10 h-10" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Không tìm thấy kết quả</h3>
                    <p class="text-slate-500">Vui lòng thử lại với từ khóa khác</p>
                </div>

                <div v-for="item in results" :key="item.id + item.type"
                    class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-500/50 transition-all group flex items-center gap-5 shadow-sm hover:shadow-md">

                    <div class="w-14 h-14 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-blue-50 dark:group-hover:bg-blue-900/20 group-hover:text-blue-600 transition-colors">
                        <Icon :icon="getIcon(item.type)" class="w-7 h-7" />
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-500">
                                {{ getTypeLabel(item.type) }}
                            </span>
                            <span class="text-xs font-mono font-bold text-slate-400">#{{ item.code }}</span>
                        </div>
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors">
                            {{ item.title }}
                        </h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ item.details }}</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div v-if="item.status" class="hidden sm:block">
                             <span :class="[
                                'px-3 py-1 rounded-full text-[10px] font-black uppercase',
                                item.status === 'available' || item.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                             ]">
                                {{ item.status === 'available' ? 'Sẵn sàng' : (item.status === 'active' ? 'Hoạt động' : 'Đã mượn') }}
                             </span>
                        </div>
                        <Button variant="ghost" size="icon" class="rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
                            <Icon icon="lucide:arrow-right" class="w-5 h-5" />
                        </Button>
                    </div>
                </div>
            </div>

            <div class="text-center pt-10 pb-20">
                <p class="text-slate-400 text-sm">Hiển thị {{ results.length }} kết quả tốt nhất</p>
            </div>
        </div>
    </AdminLayout>
</template>
