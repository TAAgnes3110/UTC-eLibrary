<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    books: { type: Object, default: () => ({ data: [], links: [] }) },
    categories: { type: Array, default: () => [] },
    publishers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const BOOK_TYPES = [
    { value: '', label: 'Tất cả loại' },
    { value: 'book', label: 'Sách' },
    { value: 'textbook', label: 'Giáo trình' },
    { value: 'thesis', label: 'Khóa luận / Đồ án' },
    { value: 'magazine', label: 'Tạp chí' },
    { value: 'other', label: 'Khác' },
];

const form = ref({
    q: props.filters.q ?? '',
    category_id: props.filters.category_id ?? '',
    publisher_id: props.filters.publisher_id ?? '',
    type: props.filters.type ?? '',
    year: props.filters.year ?? '',
});

const submitSearch = () => {
    router.get(route('library.search'), form.value, { preserveState: true });
};

const savedIds = ref(
    (() => {
        try {
            return JSON.parse(localStorage.getItem('utc_elibrary_saved') || '[]');
        } catch {
            return [];
        }
    })()
);

const toggleSaved = (id, e) => {
    e.preventDefault();
    e.stopPropagation();
    const idx = savedIds.value.indexOf(id);
    if (idx >= 0) savedIds.value.splice(idx, 1);
    else savedIds.value.push(id);
    localStorage.setItem('utc_elibrary_saved', JSON.stringify(savedIds.value));
};

const isSaved = (id) => savedIds.value.includes(id);

const bookList = computed(() => props.books?.data ?? []);
const totalCount = computed(() => props.books?.total ?? bookList.value.length);
const showPagination = computed(() => bookList.value.length > 0 && (props.books?.links?.length ?? 0) > 1);
</script>

<template>
    <Head title="Tra cứu sách - UTC eLibrary" />
    <ReaderDashboardLayout title="Tra cứu sách">
        <div class="space-y-8">
            <!-- Tiêu đề -->
            <div class="text-center sm:text-left">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Tra cứu sách thư viện</h1>
                <p class="mt-1 text-slate-600 dark:text-slate-400">
                    Tìm kiếm thông tin sách, xem tình trạng mượn và thực hiện mượn sách.
                </p>
            </div>

            <!-- Bộ lọc (card) - cùng style admin -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60 sm:p-6">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Bộ lọc</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">Từ khóa</label>
                        <input
                            v-model="form.q"
                            type="text"
                            placeholder="Tên sách, mã, tác giả..."
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 dark:border-slate-600 dark:bg-slate-800/80 dark:text-white dark:placeholder-slate-500 dark:focus:border-amber-500/50 dark:focus:ring-amber-500/20"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">Thể loại</label>
                        <select
                            v-model="form.category_id"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 dark:border-slate-600 dark:bg-slate-800/80 dark:text-white dark:focus:border-amber-500/50 dark:focus:ring-amber-500/20"
                        >
                            <option value="">-- Tất cả --</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">Nhà xuất bản</label>
                        <select
                            v-model="form.publisher_id"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 dark:border-slate-600 dark:bg-slate-800/80 dark:text-white dark:focus:border-amber-500/50 dark:focus:ring-amber-500/20"
                        >
                            <option value="">-- Tất cả --</option>
                            <option v-for="p in publishers" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">Loại tài liệu</label>
                        <select
                            v-model="form.type"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 dark:border-slate-600 dark:bg-slate-800/80 dark:text-white dark:focus:border-amber-500/50 dark:focus:ring-amber-500/20"
                        >
                            <option v-for="t in BOOK_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">Năm xuất bản</label>
                        <input
                            v-model="form.year"
                            type="text"
                            placeholder="VD: 2023"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 dark:border-slate-600 dark:bg-slate-800/80 dark:text-white dark:placeholder-slate-500 dark:focus:border-amber-500/50 dark:focus:ring-amber-500/20"
                        />
                    </div>
                </div>
                <Button
                    type="button"
                    @click="submitSearch"
                    class="mt-4 rounded-xl bg-slate-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-600 dark:bg-amber-500 dark:text-slate-900 dark:hover:bg-amber-400"
                >
                    <Icon icon="lucide:search" class="mr-2 h-4 w-4" />
                    Tìm kiếm
                </Button>
            </div>

            <!-- Kết quả -->
            <div>
                <p class="mb-3 text-sm text-slate-600 dark:text-slate-400">
                    Kết quả tìm kiếm: <span class="font-semibold text-slate-900 dark:text-white">{{ totalCount }}</span> đầu sách
                </p>

                <div
                    v-if="bookList.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white py-20 text-center dark:border-slate-800 dark:bg-slate-900/40"
                >
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800/80 dark:text-slate-500">
                        <Icon icon="lucide:book-open" class="h-10 w-10" />
                    </div>
                    <p class="mt-4 max-w-sm text-slate-600 dark:text-slate-400">
                        Chưa có kết quả. Hãy nhập từ khóa hoặc thử đổi bộ lọc rồi bấm <strong class="text-slate-700 dark:text-slate-300">Tìm kiếm</strong>.
                    </p>
                    <Button
                        type="button"
                        @click="submitSearch"
                        variant="outline"
                        class="mt-4 border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Thử tìm với bộ lọc hiện tại
                    </Button>
                </div>

                <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link
                        v-for="book in bookList"
                        :key="book.id"
                        :href="route('library.books.show', book.id)"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-slate-300 hover:shadow dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-slate-700"
                    >
                        <div class="flex flex-1 gap-4 p-4">
                            <div class="h-36 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
                                <img
                                    v-if="book.image_url"
                                    :src="book.image_url"
                                    :alt="book.title"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
                                    <Icon icon="lucide:book-open" class="h-10 w-10" />
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-slate-900 line-clamp-2 dark:text-white group-hover:text-slate-700 dark:group-hover:text-amber-400">
                                    {{ book.title }}
                                </h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ book.authors?.map((a) => a.name).join(', ') || '—' }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ book.publisher_name }} · {{ book.published_year || '—' }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Mã: {{ book.classification_code || '—' }} · SL: {{ book.quantity }}
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2 border-t border-slate-200 p-3 dark:border-slate-700/80">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                class="flex-1 border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                                @click.prevent="toggleSaved(book.id, $event)"
                            >
                                <Icon
                                    :icon="isSaved(book.id) ? 'lucide:heart' : 'lucide:heart'"
                                    :class="isSaved(book.id) ? 'fill-rose-500 text-rose-500' : ''"
                                    class="mr-1.5 h-4 w-4"
                                />
                                {{ isSaved(book.id) ? 'Đã lưu' : 'Lưu sách' }}
                            </Button>
                            <Button
                                as="span"
                                size="sm"
                                class="flex-1 rounded-lg bg-slate-700 text-white hover:bg-slate-600 dark:bg-amber-500 dark:text-slate-900 dark:hover:bg-amber-400"
                                @click.prevent="router.visit(route('library.books.show', book.id))"
                            >
                                Mượn sách
                            </Button>
                        </div>
                    </Link>
                </div>

                <div v-if="showPagination" class="mt-8 flex justify-center gap-2">
                    <template v-for="(link, i) in books.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-xl border px-4 py-2.5 text-sm font-medium transition"
                            :class="
                                link.active
                                    ? 'border-slate-600 bg-slate-700 text-white dark:border-amber-500 dark:bg-amber-500 dark:text-slate-900'
                                    : 'border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white'
                            "
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>
