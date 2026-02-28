<script setup>
import { computed } from 'vue';
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    content: { type: String, default: '' },
});

const defaultRules = `
<h2 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Nội quy thư viện</h2>
<p class="text-slate-600 dark:text-slate-400 mb-4">Bạn đọc vui lòng tuân thủ nội quy khi sử dụng thư viện và dịch vụ mượn trả sách.</p>
<h3 class="text-base font-semibold text-slate-800 dark:text-slate-200 mt-4 mb-2">1. Đối tượng phục vụ</h3>
<p class="text-slate-600 dark:text-slate-400 text-sm">Cán bộ, giảng viên, sinh viên và học viên của Trường Đại học Giao thông Vận tải có thẻ thư viện hợp lệ.</p>
<h3 class="text-base font-semibold text-slate-800 dark:text-slate-200 mt-4 mb-2">2. Quy định mượn trả</h3>
<ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-400 text-sm">
  <li>Mang thẻ thư viện khi đến thư viện và khi mượn/trả sách</li>
  <li>Tuân thủ số lượng sách được mượn và thời hạn trả theo quy định</li>
  <li>Gia hạn sách đúng hạn khi có nhu cầu (nếu được phép)</li>
  <li>Giữ gìn tài liệu, không làm hư hỏng hoặc làm bẩn</li>
</ul>
<h3 class="text-base font-semibold text-slate-800 dark:text-slate-200 mt-4 mb-2">3. Trách nhiệm</h3>
<p class="text-slate-600 dark:text-slate-400 text-sm">Bạn đọc chịu trách nhiệm bồi thường nếu làm mất hoặc hư hỏng tài liệu. Vi phạm nội quy có thể bị tạm ngừng quyền sử dụng thư viện.</p>
<p class="text-slate-600 dark:text-slate-400 mt-6 text-sm">Chi tiết quy định mượn trả có thể được cập nhật bởi Thư viện. Mọi thắc mắc vui lòng liên hệ bộ phận Thư viện.</p>
`.trim();

const htmlContent = computed(() => {
  const raw = (props.content || '').trim();
  return raw ? raw : defaultRules;
});
</script>

<template>
    <Head title="Nội quy - Thư viện số" />
    <ReaderDashboardLayout title="Nội quy">
        <div class="space-y-6 max-w-3xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Nội quy thư viện</h1>
                <Link :href="route('library.search')" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Tra cứu sách</Link>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                <div class="p-6 lg:p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <Icon icon="lucide:file-text" class="w-6 h-6" />
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900 dark:text-white">Nội quy & Hướng dẫn</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Quy định sử dụng thư viện và mượn trả sách</p>
                        </div>
                    </div>
                    <div
                        class="prose prose-slate dark:prose-invert prose-sm max-w-none reader-rules"
                        v-html="htmlContent"
                    />
                </div>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>

<style scoped>
.reader-rules :deep(h2) { @apply text-lg font-bold mt-6 mb-2; }
.reader-rules :deep(h3) { @apply text-base font-semibold mt-4 mb-2; }
.reader-rules :deep(p) { @apply mb-3 text-slate-600 dark:text-slate-400; }
.reader-rules :deep(ul) { @apply list-disc list-inside space-y-1; }
</style>
