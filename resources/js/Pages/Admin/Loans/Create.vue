<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { loansApi } from '@/api/loans';
import { libraryCardsApi } from '@/api/libraryCards';
import { booksApi } from '@/api/books';
import { toast } from '@/store/toast';

const saving = ref(false);
const loadingMaster = ref(false);
const libraryCards = ref([]);
const books = ref([]);

const form = reactive({
    library_card_id: '',
    loan_type: 'home',
    loan_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    status: 'da_muon',
    notes: '',
    lines: [{ book_id: '', quantity: 1, condition_on_loan: 'tot' }],
});

function extractItems(payload) {
    const inner = payload?.data;
    if (Array.isArray(inner)) return inner;
    if (inner && Array.isArray(inner.data)) return inner.data;
    return [];
}

function addLine() {
    form.lines.push({ book_id: '', quantity: 1, condition_on_loan: 'tot' });
}

function removeLine(index) {
    if (form.lines.length === 1) return;
    form.lines.splice(index, 1);
}

async function loadMasterData() {
    loadingMaster.value = true;
    try {
        const [cardsRes, booksRes] = await Promise.all([
            libraryCardsApi.list({ per_page: 100, management: 1 }),
            booksApi.list({ per_page: 200 }),
        ]);
        libraryCards.value = extractItems(cardsRes);
        books.value = extractItems(booksRes);
    } catch (e) {
        toast.error('Không tải được dữ liệu thẻ/sách.', { title: 'Lỗi' });
    } finally {
        loadingMaster.value = false;
    }
}

async function saveLoan() {
    if (!form.library_card_id || !form.loan_date || !form.due_date) {
        toast.warn('Vui lòng nhập đầy đủ thẻ, ngày mượn và ngày hẹn trả.');
        return;
    }
    const validLines = form.lines.filter((x) => x.book_id && Number(x.quantity) > 0);
    if (validLines.length === 0) {
        toast.warn('Vui lòng thêm ít nhất 1 sách mượn hợp lệ.');
        return;
    }

    saving.value = true;
    try {
        const payload = {
            library_card_id: Number(form.library_card_id),
            loan_type: form.loan_type,
            loan_date: form.loan_date,
            due_date: form.due_date,
            status: form.status,
            notes: form.notes || null,
            book_ids: validLines.map((x) => Number(x.book_id)),
            quantity: validLines.map((x) => Number(x.quantity)),
            condition_on_loan: validLines.map((x) => x.condition_on_loan || 'tot'),
        };

        await loansApi.create(payload);
        toast.success('Tạo phiếu mượn thành công.', { title: 'Thành công' });
        router.visit(route('admin.loans.index'));
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không tạo được phiếu mượn.', { title: 'Lỗi' });
    } finally {
        saving.value = false;
    }
}

onMounted(loadMasterData);
</script>

<template>
    <Head title="Tạo phiếu mượn" />
    <AdminLayout
        title="Phiếu mượn"
        :breadcrumbs="[
            { label: 'Phiếu mượn', href: route('admin.loans.index') },
            { label: 'Tạo phiếu mới' },
        ]"
    >
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4 space-y-4">
                <h2 class="text-base font-bold text-gray-800 dark:text-white">Thông tin phiếu mượn</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Thẻ thư viện</span>
                        <select v-model="form.library_card_id" class="admin-filter-select w-full" :disabled="loadingMaster">
                            <option value="">Chọn thẻ</option>
                            <option v-for="card in libraryCards" :key="card.id" :value="card.id">
                                {{ card.card_number || `#${card.id}` }} - {{ card.full_name || 'N/A' }}
                            </option>
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Loại phiếu</span>
                        <select v-model="form.loan_type" class="admin-filter-select w-full">
                            <option value="home">Mượn về nhà</option>
                            <option value="onsite">Đọc/mượn tại chỗ</option>
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Ngày mượn</span>
                        <input v-model="form.loan_date" type="date" class="admin-filter-input w-full" />
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Ngày hẹn trả</span>
                        <input v-model="form.due_date" type="date" class="admin-filter-input w-full" />
                    </label>
                </div>
                <label class="space-y-1 block">
                    <span class="text-sm font-medium">Ghi chú</span>
                    <textarea v-model="form.notes" rows="2" class="admin-filter-input w-full" placeholder="Ghi chú thêm..." />
                </label>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Danh sách sách mượn</h3>
                    <button class="admin-filter-btn px-3 py-1.5 min-h-[36px]" @click="addLine">Thêm dòng</button>
                </div>
                <div class="space-y-2">
                    <div v-for="(line, index) in form.lines" :key="index" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end border border-slate-200 dark:border-slate-700 rounded-lg p-2">
                        <div class="md:col-span-6">
                            <label class="text-xs text-slate-500">Sách</label>
                            <select v-model="line.book_id" class="admin-filter-select w-full">
                                <option value="">Chọn sách</option>
                                <option v-for="book in books" :key="book.id" :value="book.id">
                                    {{ book.title }} (SL: {{ book.quantity ?? 0 }})
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-slate-500">Số lượng</label>
                            <input v-model.number="line.quantity" type="number" min="1" class="admin-filter-input w-full" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-xs text-slate-500">Tình trạng</label>
                            <select v-model="line.condition_on_loan" class="admin-filter-select w-full">
                                <option value="tot">Sách còn tốt</option>
                                <option value="hong">Sách hư hỏng</option>
                                <option value="mat">Sách bị mất</option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <button class="admin-filter-btn w-full px-2 py-2 min-h-[38px]" @click="removeLine(index)">Xóa</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button class="admin-filter-btn px-4 py-2.5 min-h-[44px]" :disabled="saving" @click="saveLoan">
                    {{ saving ? 'Đang lưu...' : 'Tạo phiếu' }}
                </button>
                <button class="admin-filter-btn px-4 py-2.5 min-h-[44px]" @click="router.visit(route('admin.loans.index'))">
                    Quay lại
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
