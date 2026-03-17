<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ReaderDashboardLayout from '@/Layouts/ReaderDashboardLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import apiClient from '@/api/axios';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            q: '',
        }),
    },
});

const classifications = ref([]);
const loadingClassifications = ref(true);
const selectedClassificationId = ref(null);

const allBooks = ref([]);
const loadingBooks = ref(false);

const searchKeyword = ref(props.filters?.q ?? '');

const loadClassifications = async () => {
    loadingClassifications.value = true;
    try {
        const response = await apiClient.get('/classifications/list');
        const payload = response?.data;
        classifications.value = Array.isArray(payload?.data) ? payload.data : [];
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Failed to load classifications', e);
        classifications.value = [];
    } finally {
        loadingClassifications.value = false;
    }
};

const loadBooks = async () => {
    loadingBooks.value = true;
    try {
        const response = await apiClient.get('/books', {
            params: {
                per_page: 500,
                keyword: searchKeyword.value || undefined,
            },
        });
        const payload = response?.data;
        const paginator = payload?.data;
        const items = Array.isArray(paginator?.data) ? paginator.data : Array.isArray(paginator) ? paginator : [];
        allBooks.value = items;
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Failed to load books', e);
        allBooks.value = [];
    } finally {
        loadingBooks.value = false;
    }
};

const filteredBooks = computed(() => {
    let list = Array.isArray(allBooks.value) ? [...allBooks.value] : [];

    if (selectedClassificationId.value) {
        list = list.filter((book) => book.classification_id === selectedClassificationId.value);
    }

    return list;
});

const selectedClassification = computed(() =>
    classifications.value.find((c) => c.id === selectedClassificationId.value) || null,
);

const handleSelectClassification = (id) => {
    if (selectedClassificationId.value === id) {
        selectedClassificationId.value = null;
    } else {
        selectedClassificationId.value = id;
    }
};

const handleSearchSubmit = (event) => {
    event?.preventDefault();
    loadBooks();
};

onMounted(async () => {
    await Promise.all([loadClassifications(), loadBooks()]);
});

watch(
    () => props.filters?.q,
    (newQ) => {
        if (typeof newQ === 'string' && newQ !== searchKeyword.value) {
            searchKeyword.value = newQ;
            loadBooks();
        }
    },
);
</script>

<template>
    <Head title="Tra cứu sách - UTC eLibrary" />
    <ReaderDashboardLayout title="Tra cứu sách">
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Tra cứu sách theo danh mục</h1>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Chọn danh mục ở bên trái hoặc nhập tên sách, mã sách, tác giả để xem các bản in hiện có.
                    </p>
                </div>
                <Link
                    :href="route('library.dashboard')"
                    class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-amber-300"
                >
                    <Icon icon="lucide:layout-dashboard" class="h-4 w-4" />
                    Tổng quan
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] gap-5 items-start">
                <!-- Danh mục -->
                <aside
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/50"
                >
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Danh mục phân loại</h2>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">
                            {{ classifications.length }} mục
                        </span>
                    </div>

                    <div v-if="loadingClassifications" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        Đang tải danh mục...
                    </div>
                    <div v-else-if="!classifications.length" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        Chưa có danh mục phân loại.
                    </div>
                    <ul v-else class="space-y-1 max-h-[420px] overflow-y-auto pr-1">
                        <li>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium transition hover:bg-slate-50 dark:hover:bg-slate-800"
                                :class="{
                                    'bg-slate-900 text-white dark:bg-slate-200 dark:text-slate-900':
                                        !selectedClassificationId,
                                    'text-slate-700 dark:text-slate-200': !!selectedClassificationId,
                                }"
                                @click="handleSelectClassification(null)"
                            >
                                <span class="flex items-center gap-2">
                                    <Icon icon="lucide:layers" class="h-4 w-4" />
                                    Tất cả danh mục
                                </span>
                            </button>
                        </li>
                        <li v-for="item in classifications" :key="item.id">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium transition hover:bg-slate-50 dark:hover:bg-slate-800"
                                :class="{
                                    'bg-slate-900 text-white shadow-sm dark:bg-amber-500 dark:text-slate-900':
                                        selectedClassificationId === item.id,
                                    'text-slate-700 dark:text-slate-200':
                                        selectedClassificationId !== item.id,
                                }"
                                @click="handleSelectClassification(item.id)"
                            >
                                <span class="flex items-center gap-2">
                                    <span
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-slate-100 text-[11px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                    >
                                        {{ item.code }}
                                    </span>
                                    <span class="truncate">{{ item.name }}</span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </aside>

                <!-- Danh sách sách -->
                <section class="space-y-4">
                    <form
                        class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/50 sm:flex-row sm:items-center"
                        @submit="handleSearchSubmit"
                    >
                        <div class="relative flex-1">
                            <Icon
                                icon="lucide:search"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />
                            <input
                                v-model="searchKeyword"
                                type="search"
                                name="q"
                                placeholder="Tên sách, mã sách, DKCB, tác giả..."
                                class="h-10 w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400 dark:border-slate-600 dark:bg-slate-800/80 dark:text-white dark:placeholder-slate-500 dark:focus:border-amber-500/50 dark:focus:ring-amber-500/20"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <Button type="submit" class="h-10 rounded-lg bg-slate-800 text-xs font-semibold text-white hover:bg-slate-700 dark:bg-amber-500 dark:text-slate-900 dark:hover:bg-amber-400">
                                Tìm sách
                            </Button>
                        </div>
                    </form>

                    <div
                        class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/40"
                    >
                        <div
                            class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                        >
                            <div class="flex items-center gap-2">
                                <Icon icon="lucide:book-open" class="h-4 w-4" />
                                <span>
                                    {{ loadingBooks ? 'Đang tải sách...' : `Có ${filteredBooks.length} sách phù hợp` }}
                                </span>
                            </div>
                            <div v-if="selectedClassification" class="hidden items-center gap-2 text-[11px] sm:flex">
                                <span class="text-slate-400">Danh mục:</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ selectedClassification.code }} · {{ selectedClassification.name }}
                                </span>
                            </div>
                        </div>

                        <div v-if="loadingBooks" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            Đang tải dữ liệu sách...
                        </div>
                        <div v-else-if="!filteredBooks.length" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            Không tìm thấy sách nào phù hợp với điều kiện hiện tại.
                        </div>
                        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                            <li
                                v-for="book in filteredBooks"
                                :key="book.id"
                                class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-start sm:gap-4"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                                {{ book.title }}
                                            </h3>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                DKCB:
                                                <span class="font-mono text-slate-700 dark:text-slate-200">
                                                    {{ book.registration_number || '—' }}
                                                </span>
                                                · Mã sách:
                                                <span class="font-mono text-slate-700 dark:text-slate-200">
                                                    {{ book.book_code || '—' }}
                                                </span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Tác giả:
                                                <span class="text-slate-700 dark:text-slate-200">
                                                    {{ book.authors_label || '—' }}
                                                </span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                NXB:
                                                <span class="text-slate-700 dark:text-slate-200">
                                                    {{ book.publishers_label || '—' }}
                                                </span>
                                                · Năm XB:
                                                <span class="text-slate-700 dark:text-slate-200">
                                                    {{ book.published_year || '—' }}
                                                </span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Phân loại:
                                                <span class="text-slate-700 dark:text-slate-200">
                                                    {{ book.classification?.code || '—' }}
                                                </span>
                                                ·
                                                <span class="text-slate-700 dark:text-slate-200">
                                                    {{ book.classification?.name || '—' }}
                                                </span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Kho:
                                                <span class="text-slate-700 dark:text-slate-200">
                                                    {{ book.warehouse?.name || '—' }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="flex flex-col items-end gap-1 shrink-0">
                                            <span
                                                class="inline-flex items-center justify-center rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700"
                                            >
                                                SL:
                                                <span class="ml-1 font-bold">
                                                    {{ book.quantity ?? 0 }}
                                                </span>
                                            </span>
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                                    book.is_available
                                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-700/60'
                                                        : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-700/60',
                                                ]"
                                            >
                                                <Icon
                                                    :icon="book.is_available ? 'lucide:check-circle' : 'lucide:clock'"
                                                    class="h-3 w-3"
                                                />
                                                <span>{{ book.status_label || (book.is_available ? 'Có sẵn' : 'Đang mượn') }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </ReaderDashboardLayout>
</template>

