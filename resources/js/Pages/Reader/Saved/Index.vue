<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const savedIds = ref([]);
const savedBooks = ref([]);
const loading = ref(true);

onMounted(() => {
    try {
        savedIds.value = JSON.parse(localStorage.getItem('utc_elibrary_saved') || '[]');
    } catch {
        savedIds.value = [];
    }
    if (savedIds.value.length > 0) {
        router.reload({ only: ['savedBooks'], data: { saved_ids: savedIds.value } });
    }
    loading.value = false;
});

const removeSaved = (id) => {
    savedIds.value = savedIds.value.filter((x) => x !== id);
    localStorage.setItem('utc_elibrary_saved', JSON.stringify(savedIds.value));
    savedBooks.value = savedBooks.value.filter((b) => b.id !== id);
};
</script>

<template>
    <Head title="Sách đã lưu - UTC eLibrary" />
    <ReaderDashboardLayout title="Sách đã lưu">
        <div class="space-y-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Sách đã lưu</h1>
            <p class="text-slate-600 dark:text-slate-400 text-sm">Danh mục sách bạn đã lưu để tra cứu và mượn sau.</p>

            <div v-if="savedIds.length === 0" class="rounded-xl border border-slate-200 bg-white py-16 text-center dark:border-slate-700 dark:bg-slate-900/30">
                <Icon icon="lucide:bookmark" class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-600" />
                <p class="mt-3 text-slate-600 dark:text-slate-400">Chưa có sách nào được lưu.</p>
                <Link :href="route('library.search')" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Tra cứu sách
                    <Icon icon="lucide:arrow-right" class="h-4 w-4" />
                </Link>
            </div>

            <div v-else class="rounded-xl border border-slate-200 bg-white py-12 text-center dark:border-slate-700 dark:bg-slate-900/30">
                <p class="text-slate-600 dark:text-slate-400">Sách đã lưu được lưu trên trình duyệt. Các sách bạn đã bấm "Lưu sách" tại trang Tra cứu sẽ hiển thị tại đây khi chúng ta kết nối API.</p>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Hiện tại vui lòng dùng <strong class="text-slate-700 dark:text-slate-300">Tra cứu</strong> và bấm vào từng sách để xem chi tiết. Số sách đã lưu: {{ savedIds.length }}.</p>
                <Link :href="route('library.search')" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-600 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Đi tới Tra cứu
                </Link>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>
