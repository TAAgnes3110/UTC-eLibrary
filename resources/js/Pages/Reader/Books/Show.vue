<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    book: { type: Object, required: true },
});

const savedIds = ref(
    (() => {
        try {
            return JSON.parse(localStorage.getItem('utc_elibrary_saved') || '[]');
        } catch {
            return [];
        }
    })()
);

const isSaved = computed(() => savedIds.value.includes(props.book.id));

const toggleSaved = () => {
    const id = props.book.id;
    const idx = savedIds.value.indexOf(id);
    if (idx >= 0) savedIds.value.splice(idx, 1);
    else savedIds.value.push(id);
    localStorage.setItem('utc_elibrary_saved', JSON.stringify(savedIds.value));
};

const authorNames = computed(() => props.book.authors?.map((a) => a.name).join(', ') || '—');
</script>

<template>
    <Head :title="`${book.title} - UTC eLibrary`" />
    <ReaderDashboardLayout :title="book.title">
        <div class="space-y-6">
            <Link :href="route('library.search')" class="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-blue-400">
                <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                Quay lại Tra cứu
            </Link>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden dark:border-slate-700 dark:bg-slate-900/50">
                <div class="grid gap-6 p-6 md:grid-cols-[200px_1fr]">
                    <div class="h-64 w-full shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800 md:h-80 md:w-48">
                        <img
                            v-if="book.image_url"
                            :src="book.image_url"
                            :alt="book.title"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
                            <Icon icon="lucide:book-open" class="h-16 w-16" />
                        </div>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ book.title }}</h1>
                        <p class="mt-1 text-slate-600 dark:text-slate-400">Tác giả: {{ authorNames }}</p>
                        <p class="text-slate-500 text-sm">Nhà xuất bản: {{ book.publisher_name || '—' }} · {{ book.published_year || '—' }}</p>
                        <p class="text-slate-500 text-sm">Mã phân loại: {{ book.classification_code || '—' }} · Thể loại: {{ book.category_name || '—' }}</p>
                        <p v-if="book.total_pages" class="text-slate-500 text-sm">Số trang: {{ book.total_pages }}</p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-slate-300 dark:border-slate-600"
                                @click="toggleSaved"
                            >
                                <Icon :icon="isSaved ? 'lucide:heart' : 'lucide:heart'" :class="isSaved ? 'fill-rose-500 text-rose-500' : ''" class="mr-1 h-4 w-4" />
                                {{ isSaved ? 'Đã lưu' : 'Lưu sách' }}
                            </Button>
                            <Button v-if="book.ebook_url" as="a" :href="book.ebook_url" target="_blank" size="sm" class="bg-blue-600 hover:bg-blue-700">
                                <Icon icon="lucide:file-text" class="mr-1 h-4 w-4" />
                                Ebook
                            </Button>
                            <Button v-if="book.audio_url" as="a" :href="book.audio_url" target="_blank" size="sm" variant="outline" class="border-slate-300 dark:border-slate-600">
                                <Icon icon="lucide:headphones" class="mr-1 h-4 w-4" />
                                Audio
                            </Button>
                            <Button v-if="book.detail_url" as="a" :href="book.detail_url" target="_blank" size="sm" variant="outline" class="border-slate-300 dark:border-slate-600">
                                Link chi tiết
                            </Button>
                            <Button as="button" class="bg-blue-600 hover:bg-blue-700" disabled title="Liên hệ thủ thư để mượn sách">
                                Mượn sách
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tình trạng sách (bản in) -->
            <div v-if="book.copies?.length" class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900/50">
                <h2 class="mb-3 text-lg font-semibold text-slate-900 dark:text-white">Tình trạng sách</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-slate-600 dark:text-slate-300">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="pb-2 text-left font-medium">Mã bản in</th>
                                <th class="pb-2 text-left font-medium">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in book.copies" :key="c.id" class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-2">{{ c.barcode || c.id }}</td>
                                <td class="py-2">
                                    <span :class="c.status === 'available' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                        {{ c.status === 'available' ? 'Sẵn có' : 'Đang mượn' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mô tả -->
            <div v-if="book.description" class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900/50">
                <h2 class="mb-3 text-lg font-semibold text-slate-900 dark:text-white">Mô tả</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm whitespace-pre-wrap">{{ book.description }}</p>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>
