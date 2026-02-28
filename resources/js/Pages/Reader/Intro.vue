<script setup>
import { computed } from 'vue';
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    content: { type: String, default: '' },
});

const defaultIntro = `
<h2 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Giới thiệu Thư viện số UTC eLibrary</h2>
<p class="text-slate-600 dark:text-slate-400 mb-4">Thư viện số UTC eLibrary là hệ thống quản lý và tra cứu tài liệu của Trường Đại học Giao thông Vận tải. Bạn đọc có thể tra cứu sách, tài liệu, xem tình trạng mượn và thực hiện mượn sách trực tuyến.</p>
<h3 class="text-base font-semibold text-slate-800 dark:text-slate-200 mt-4 mb-2">Chức năng chính</h3>
<ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-400 text-sm">
  <li>Tra cứu sách, giáo trình, tài liệu theo từ khóa, thể loại, nhà xuất bản</li>
  <li>Xem thẻ thư viện và quản lý thông tin cá nhân</li>
  <li>Theo dõi sách đang mượn, hạn trả, gia hạn</li>
  <li>Lưu danh sách sách yêu thích để tra cứu nhanh</li>
</ul>
<p class="text-slate-600 dark:text-slate-400 mt-4 text-sm">Mọi thắc mắc vui lòng liên hệ bộ phận Thư viện.</p>
`.trim();

const htmlContent = computed(() => {
  const raw = (props.content || '').trim();
  return raw ? raw : defaultIntro;
});
</script>

<template>
    <Head title="Giới thiệu - Thư viện số" />
    <ReaderDashboardLayout title="Giới thiệu">
        <div class="space-y-6 max-w-3xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Giới thiệu</h1>
                <Link :href="route('library.search')" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Tra cứu sách</Link>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                <div class="p-6 lg:p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <Icon icon="lucide:info" class="w-6 h-6" />
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900 dark:text-white">Thư viện số UTC eLibrary</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Thông tin giới thiệu về hệ thống và dịch vụ</p>
                        </div>
                    </div>
                    <div
                        class="prose prose-slate dark:prose-invert prose-sm max-w-none reader-intro"
                        v-html="htmlContent"
                    />
                </div>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>

<style scoped>
.reader-intro :deep(h2) { @apply text-lg font-bold mt-6 mb-2; }
.reader-intro :deep(h3) { @apply text-base font-semibold mt-4 mb-2; }
.reader-intro :deep(p) { @apply mb-3 text-slate-600 dark:text-slate-400; }
.reader-intro :deep(ul) { @apply list-disc list-inside space-y-1; }
</style>
