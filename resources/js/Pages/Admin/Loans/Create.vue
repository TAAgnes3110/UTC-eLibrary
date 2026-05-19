<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { loansApi } from '@/api/loans';
import { libraryCardsApi } from '@/api/libraryCards';
import { booksApi } from '@/api/books';
import { toast } from '@/store/toast';
import { extractApiPaginator } from '@/utils/adminPagination';
import { bookResourceTypeLabel } from '@/utils/bookResourceTypeLabel';

const saving = ref(false);
const cardLookupLoading = ref(false);
const cardLookupError = ref('');
const cardSuggestionsLoading = ref(false);
const cardSuggestions = ref([]);
const showCardSuggestions = ref(false);
const resolvedCard = ref(null);
const allowHome = ref(false);
const allowOnsite = ref(true);
const limits = ref(null);
const currentBorrowed = ref(null);
const dueDateError = ref('');
const borrowRequestPrefill = ref(null);
const BORROW_REQUEST_PREFILL_KEY = 'loanBorrowRequestApprovalPrefill';

const form = reactive({
    card_number_input: '',
    library_card_id: '',
    loan_type: 'home',
    loan_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    status: 'da_muon',
    notes: '',
    lines: [createEmptyLine()],
});

const isBorrowRequestMode = computed(() => !!borrowRequestPrefill.value?.request_id);

const bookSearchTimers = {};
let cardSuggestTimer = null;
let cardAutoLookupTimer = null;
let cardSuggestRequestId = 0;

function suggestDueDateFromLoanDate(loanDateIso, termDays = 7) {
    const base = String(loanDateIso || '').trim() || new Date().toISOString().slice(0, 10);
    const days = Math.max(1, Number(termDays) || 7);
    const d = new Date(`${base}T12:00:00`);
    if (Number.isNaN(d.getTime())) {
        return '';
    }
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

function createEmptyLine() {
    return {
        request_item_id: null,
        bookQuery: '',
        searchResults: [],
        loadingBooks: false,
        book_id: '',
        bookTitle: '',
        stock: null,
        resource_type: '',
        access_mode: '',
        quantity: 1,
        condition_on_loan: 'tot',
        stockMsg: '',
        qtyMsg: '',
    };
}

function loadBorrowRequestPrefillFromSession() {
    const queryRequestId = String(new URLSearchParams(window.location.search).get('from_borrow_request') || '').trim();
    if (!queryRequestId) {
        borrowRequestPrefill.value = null;
        return;
    }
    try {
        const raw = window.sessionStorage.getItem(BORROW_REQUEST_PREFILL_KEY);
        if (!raw) {
            toast.warn('Không tìm thấy dữ liệu yêu cầu mượn để tự điền. Vui lòng mở lại từ màn Duyệt yêu cầu.');
            return;
        }
        const parsed = JSON.parse(raw);
        if (!parsed || String(parsed.request_id || '') !== queryRequestId) {
            toast.warn('Dữ liệu tự điền không khớp yêu cầu đang mở. Vui lòng thao tác lại từ màn Duyệt yêu cầu.');
            return;
        }
        borrowRequestPrefill.value = parsed;
        form.card_number_input = parsed.card_number || '';
        form.library_card_id = String(parsed.library_card_id || '');
        form.loan_type = parsed.loan_type || 'home';
        form.loan_date = parsed.requested_loan_date || new Date().toISOString().slice(0, 10);
        form.due_date =
            parsed.requested_due_date ||
            parsed.suggested_due_date ||
            suggestDueDateFromLoanDate(form.loan_date, 7);
        form.notes = parsed.request_note || '';
        resolvedCard.value = {
            id: parsed.library_card_id || null,
            card_number: parsed.card_number || '',
            full_name: parsed.card_full_name || '—',
            holder_type: parsed.holder_type || '',
        };
        allowHome.value = true;
        allowOnsite.value = true;
        currentBorrowed.value = { textbook: 0, reference: 0, total: 0 };
        form.lines = (parsed.items || []).map((it) => ({
            ...createEmptyLine(),
            request_item_id: it.request_item_id || null,
            book_id: String(it.book_id || ''),
            bookTitle: it.book_title || '',
            bookQuery: it.book_title || it.book_code || '',
            stock: Number(it.available_for_borrow ?? it.book_total_quantity ?? 0),
            resource_type: it.resource_type || '',
            quantity: Number(it.quantity || 1),
            condition_on_loan: 'tot',
        }));
        if (!form.lines.length) {
            form.lines = [createEmptyLine()];
        }
    } catch {
        toast.warn('Không đọc được dữ liệu tự điền từ yêu cầu mượn.');
    }
}

function holderTypeLabel(ht) {
    if (ht === 'student') {
        return 'Sinh viên';
    }
    if (ht === 'teacher') {
        return 'Giảng viên';
    }
    if (ht === 'external') {
        return 'Bạn đọc ngoài';
    }
    return ht || '';
}

function borrowedValue(key) {
    return Number(currentBorrowed.value?.[key] ?? 0);
}

function syncLoanTypeAfterPermissions() {
    if (allowHome.value && !allowOnsite.value) {
        form.loan_type = 'home';
    } else if (!allowHome.value && allowOnsite.value) {
        form.loan_type = 'onsite';
    } else if (allowHome.value && allowOnsite.value) {
        if (form.loan_type !== 'home' && form.loan_type !== 'onsite') {
            form.loan_type = 'home';
        }
    }
}

function validateDueDate() {
    dueDateError.value = '';
    if (!form.due_date || !form.loan_date) {
        return;
    }
    if (form.due_date <= form.loan_date) {
        dueDateError.value = 'Ngày hẹn trả phải sau ngày mượn.';
    }
}

watch(
    () => [form.loan_date, form.due_date],
    () => validateDueDate()
);

function clearCardTimers() {
    if (cardSuggestTimer) {
        clearTimeout(cardSuggestTimer);
        cardSuggestTimer = null;
    }
    if (cardAutoLookupTimer) {
        clearTimeout(cardAutoLookupTimer);
        cardAutoLookupTimer = null;
    }
}

async function searchCardSuggestions(keyword) {
    const q = (keyword || '').trim();
    if (q.length < 1) {
        cardSuggestions.value = [];
        cardSuggestionsLoading.value = false;
        return;
    }

    const reqId = ++cardSuggestRequestId;
    cardSuggestionsLoading.value = true;
    try {
        const payload = await libraryCardsApi.list({
            keyword: q,
            search_in: 'card_number',
            per_page: 8,
            management: 1,
            sort_by: 'newest',
        });
        if (reqId !== cardSuggestRequestId) return;
        const { items } = extractApiPaginator(payload, 8);
        cardSuggestions.value = Array.isArray(items) ? items : [];
    } catch {
        if (reqId !== cardSuggestRequestId) return;
        cardSuggestions.value = [];
    } finally {
        if (reqId === cardSuggestRequestId) {
            cardSuggestionsLoading.value = false;
        }
    }
}

function scheduleCardSuggestionSearch() {
    if (cardSuggestTimer) {
        clearTimeout(cardSuggestTimer);
    }
    const currentInput = form.card_number_input;
    cardSuggestTimer = setTimeout(() => {
        searchCardSuggestions(currentInput);
    }, 220);
}

function scheduleAutoLookupIfExactMatch() {
    if (cardAutoLookupTimer) {
        clearTimeout(cardAutoLookupTimer);
    }
    const currentInput = (form.card_number_input || '').trim();
    cardAutoLookupTimer = setTimeout(() => {
        const exact = cardSuggestions.value.find(
            (c) => String(c?.card_number || '').toLowerCase() === currentInput.toLowerCase()
        );
        if (!exact) return;
        if (resolvedCard.value?.card_number === exact.card_number) return;
        lookupCard(exact.card_number);
    }, 520);
}

function findExactCardFromSuggestions(cardNumberInput) {
    const normalized = String(cardNumberInput || '').trim().toLowerCase();
    if (!normalized) return null;
    return (
        cardSuggestions.value.find((c) => String(c?.card_number || '').toLowerCase() === normalized) || null
    );
}

async function lookupCardByInputSmart(cardNumberInput) {
    const input = String(cardNumberInput || '').trim();
    if (!input) return;
    if (resolvedCard.value?.card_number === input) return;
    const exactInSuggestions = findExactCardFromSuggestions(input);
    if (exactInSuggestions) {
        await lookupCard(exactInSuggestions.card_number);
        return;
    }
    try {
        const payload = await libraryCardsApi.list({
            keyword: input,
            search_in: 'card_number',
            per_page: 8,
            management: 1,
        });
        const { items } = extractApiPaginator(payload, 8);
        const exact = (items || []).find(
            (c) => String(c?.card_number || '').toLowerCase() === input.toLowerCase()
        );
        if (!exact) {
            resetCardStep();
            form.card_number_input = input;
            cardLookupError.value = 'Thẻ không tồn tại.';
            return;
        }
        await lookupCard(exact.card_number);
    } catch {
        await lookupCard(input);
    }
}

function onCardNumberInput() {
    const input = (form.card_number_input || '').trim();
    cardLookupError.value = '';
    if (!input) {
        resetCardStep();
        cardSuggestions.value = [];
        showCardSuggestions.value = false;
        clearCardTimers();
        return;
    }
    if (resolvedCard.value && resolvedCard.value.card_number !== input) {
        resetCardStep();
    }
    showCardSuggestions.value = true;
    scheduleCardSuggestionSearch();
    scheduleAutoLookupIfExactMatch();
}

function selectCardSuggestion(card) {
    if (!card) return;
    form.card_number_input = card.card_number || '';
    showCardSuggestions.value = false;
    cardSuggestions.value = [];
    lookupCard(form.card_number_input);
}

function handleCardInputFocus() {
    if (cardSuggestions.value.length > 0) {
        showCardSuggestions.value = true;
    }
}

function handleCardInputBlur() {
    const input = (form.card_number_input || '').trim();
    if (input && resolvedCard.value?.card_number !== input && !cardLookupLoading.value) {
        lookupCardByInputSmart(input);
    }
    setTimeout(() => {
        showCardSuggestions.value = false;
    }, 140);
}

function handleCardEnter() {
    if (!form.card_number_input?.trim()) return;
    lookupCardByInputSmart(form.card_number_input);
}

async function lookupCard(cardNumberInput = form.card_number_input) {
    cardLookupError.value = '';
    resolvedCard.value = null;
    form.library_card_id = '';
    limits.value = null;
    currentBorrowed.value = null;
    allowHome.value = false;
    allowOnsite.value = true;

    const n = String(cardNumberInput || '').trim();
    if (!n) {
        cardLookupError.value = 'Vui lòng nhập mã thẻ.';
        return;
    }

    cardLookupLoading.value = true;
    try {
        const body = await libraryCardsApi.lookupForLoan({ card_number: n });
        if (body?.status !== 'success' || !body?.data) {
            cardLookupError.value = 'Không tra cứu được thẻ.';
            return;
        }
        const data = body.data;
        resolvedCard.value = data.card;
        form.card_number_input = data.card?.card_number || n;
        form.library_card_id = String(data.card.id);
        allowHome.value = !!data.allow_home;
        allowOnsite.value = !!data.allow_onsite;
        limits.value = data.limits || null;
        currentBorrowed.value = data.current_borrowed || { textbook: 0, reference: 0, total: 0 };
        cardSuggestions.value = [];
        showCardSuggestions.value = false;
        syncLoanTypeAfterPermissions();
        if (!allowHome.value && !allowOnsite.value) {
            cardLookupError.value = 'Thẻ không được phép mượn theo chính sách hiện tại.';
            resolvedCard.value = null;
            form.library_card_id = '';
        }
    } catch (e) {
        const status = Number(e?.response?.status || 0);
        const msg = status === 404 ? 'Thẻ không tồn tại.' : (e?.response?.data?.messages || 'Không tra cứu được thẻ.');
        cardLookupError.value = msg;
    } finally {
        cardLookupLoading.value = false;
    }
}

function resetCardStep() {
    resolvedCard.value = null;
    form.library_card_id = '';
    limits.value = null;
    currentBorrowed.value = null;
    allowHome.value = false;
    allowOnsite.value = true;
    cardLookupError.value = '';
    form.loan_type = 'home';
    cardSuggestions.value = [];
    showCardSuggestions.value = false;
    clearCardTimers();
}

function scheduleBookSearch(index) {
    clearTimeout(bookSearchTimers[index]);
    bookSearchTimers[index] = setTimeout(() => runBookSearch(index), 350);
}

async function runBookSearch(index) {
    const line = form.lines[index];
    if (!line) {
        return;
    }
    const q = (line.bookQuery || '').trim();
    if (q.length < 1) {
        line.searchResults = [];
        return;
    }
    line.loadingBooks = true;
    try {
        const res = await booksApi.list({ keyword: q, per_page: 30 });
        const { items } = extractApiPaginator(res, 30);
        line.searchResults = items;
    } catch {
        line.searchResults = [];
    } finally {
        line.loadingBooks = false;
    }
}

function selectBook(index, book) {
    const line = form.lines[index];
    if (!line || !book) {
        return;
    }
    line.book_id = String(book.id);
    line.bookTitle = book.title || '';
    line.stock = book.quantity ?? 0;
    line.resource_type = book.resource_type || '';
    line.access_mode = book.access_mode || '';
    line.bookQuery = line.bookTitle;
    line.searchResults = [];
    validateLineQuantity(index);
}

function validateLineQuantity(index) {
    const line = form.lines[index];
    if (!line) {
        return;
    }
    line.stockMsg = '';
    line.qtyMsg = '';
    if (!line.book_id) {
        return;
    }
    const stock = Number(line.stock ?? 0);
    if (stock < 1) {
        line.stockMsg = 'Không còn bản sách sẵn sàng cho mượn.';
    }
    const q = Number(line.quantity);
    if (Number.isFinite(q) && q > stock) {
        line.qtyMsg = 'Số lượng mượn không được lớn hơn số lượng còn trong kho.';
    }
}

function addLine() {
    form.lines.push(createEmptyLine());
}

function clearBookLine(index) {
    form.lines[index] = createEmptyLine();
}

function removeLine(index) {
    if (form.lines.length <= 1) {
        clearBookLine(0);
        return;
    }
    form.lines.splice(index, 1);
    delete bookSearchTimers[index];
}

const limitsHint = computed(() => {
    if (!limits.value) {
        return '';
    }
    const L = limits.value;
    return `Hạn mức theo loại thẻ — áp dụng cho mọi phiếu đang mượn (về nhà và tại chỗ), hệ thống đối chiếu khi lưu: tối đa ${L.max_books} cuốn tổng; giáo trình ${L.max_textbooks}; tài liệu tham khảo ${L.max_reference}.`;
});

function buildPayload() {
    const validLines = form.lines.filter((x) => x.book_id && Number(x.quantity) > 0);
    return {
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
}

function buildBorrowRequestApprovalPayload() {
    const validLines = form.lines.filter((x) => x.book_id && Number(x.quantity) > 0);
    const source = borrowRequestPrefill.value;
    return {
        loan_date: form.loan_date,
        due_date: form.due_date,
        loan_type: form.loan_type,
        review_note: form.notes || source?.request_note || null,
        book_ids: validLines.map((x) => Number(x.book_id)),
        quantity: validLines.map((x) => Number(x.quantity)),
        condition_on_loan: validLines.map((x) => x.condition_on_loan || 'tot'),
    };
}

async function saveLoan() {
    if (!form.library_card_id || !resolvedCard.value) {
        toast.warn('Vui lòng kiểm tra mã thẻ trước khi tạo phiếu.');
        return;
    }
    validateDueDate();
    if (dueDateError.value) {
        toast.warn(dueDateError.value);
        return;
    }
    if (!form.loan_date || !form.due_date) {
        toast.warn('Vui lòng nhập đầy đủ ngày mượn và ngày hẹn trả.');
        return;
    }

    for (let i = 0; i < form.lines.length; i++) {
        validateLineQuantity(i);
        const line = form.lines[i];
        if (!line.book_id) {
            continue;
        }
        if (line.stockMsg || line.qtyMsg) {
            toast.warn(line.stockMsg || line.qtyMsg);
            return;
        }
    }

    saving.value = true;
    try {
        if (isBorrowRequestMode.value) {
            const requestId = Number(borrowRequestPrefill.value?.request_id || 0);
            if (!requestId) {
                toast.warn('Không tìm thấy yêu cầu mượn để duyệt.');
                saving.value = false;
                return;
            }
            const approvalPayload = buildBorrowRequestApprovalPayload();
            if (approvalPayload.book_ids.length === 0) {
                toast.warn('Vui lòng thêm ít nhất 1 sách mượn hợp lệ.');
                saving.value = false;
                return;
            }
            await loansApi.approveBorrowRequest(requestId, approvalPayload);
            window.sessionStorage.removeItem(BORROW_REQUEST_PREFILL_KEY);
            toast.success('Đã duyệt yêu cầu và tạo phiếu mượn.', { title: 'Thành công' });
            router.visit(route('admin.loans.borrow-requests'));
            return;
        }

        const payload = buildPayload();
        if (payload.book_ids.length === 0) {
            toast.warn('Vui lòng thêm ít nhất 1 sách mượn hợp lệ.');
            saving.value = false;
            return;
        }
        await loansApi.create(payload);
        toast.success('Tạo phiếu mượn thành công.', { title: 'Thành công' });
        router.visit(route('admin.loans.index'));
    } catch (e) {
        const data = e?.response?.data || {};
        const firstFieldError = data?.errors && typeof data.errors === 'object'
            ? Object.values(data.errors).flat().find((x) => typeof x === 'string')
            : null;
        const msg =
            data?.messages ||
            data?.message ||
            firstFieldError ||
            'Không tạo được phiếu mượn.';
        toast.error(msg, { title: 'Lỗi' });
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    validateDueDate();
    loadBorrowRequestPrefillFromSession();
});
onBeforeUnmount(() => clearCardTimers());
</script>

<template>
    <Head title="Tạo phiếu mượn" />
    <AdminLayout
        title="Tạo phiếu mới"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Phiếu mượn', href: route('admin.loans.index') },
            { label: 'Tạo phiếu mới' },
        ]"
    >
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4 space-y-4">
                <h2 class="text-base font-bold text-gray-800 dark:text-white">Thông tin phiếu mượn</h2>
                <div
                    v-if="isBorrowRequestMode"
                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-900/20 dark:text-emerald-300"
                >
                    Chế độ duyệt yêu cầu mượn: thông tin từ yêu cầu đã được tự điền để tham khảo. Thủ thư có thể chỉnh sửa thông tin, thêm/xóa sách trước khi duyệt tạo phiếu.
                </div>

                <div class="space-y-2">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start">
                        <div class="space-y-1 md:col-span-2">
                            <template v-if="!resolvedCard">
                                <label class="space-y-1 block">
                                    <span class="text-sm font-medium">Mã thẻ thư viện (do bạn đọc cấp)</span>
                                    <div class="relative">
                                        <input
                                            v-model="form.card_number_input"
                                            type="text"
                                            class="admin-filter-input w-full"
                                            placeholder="Nhập mã thẻ..."
                                            @input="onCardNumberInput"
                                            @focus="handleCardInputFocus"
                                            @blur="handleCardInputBlur"
                                            @keydown.enter.prevent="handleCardEnter"
                                        />
                                        <div
                                            v-if="showCardSuggestions && (cardSuggestionsLoading || cardSuggestions.length > 0)"
                                            class="absolute z-20 mt-1 w-full max-h-64 overflow-y-auto rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg"
                                        >
                                            <div v-if="cardSuggestionsLoading" class="px-3 py-2 text-xs text-slate-500">
                                                Đang tìm thẻ...
                                            </div>
                                            <template v-else>
                                                <button
                                                    v-for="card in cardSuggestions"
                                                    :key="card.id"
                                                    type="button"
                                                    class="w-full px-3 py-2 text-left hover:bg-slate-100 dark:hover:bg-slate-800 border-b border-slate-100 dark:border-slate-800 last:border-0"
                                                    @mousedown.prevent="selectCardSuggestion(card)"
                                                >
                                                    <p class="font-medium text-sm text-slate-800 dark:text-slate-100">
                                                        {{ card.card_number || `#${card.id}` }}
                                                    </p>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                        {{ card.full_name || 'Không có tên' }}
                                                    </p>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <p v-if="cardLookupError" class="text-sm text-rose-600 mt-1" aria-live="polite">
                                        {{ cardLookupError }}
                                    </p>
                                </label>
                            </template>
                            <template v-else>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-2">
                                    <label class="space-y-1">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Mã thẻ thư viện</span>
                                        <input
                                            type="text"
                                            class="admin-filter-input w-full bg-slate-50 dark:bg-slate-800"
                                            :value="resolvedCard.card_number || form.card_number_input"
                                            readonly
                                        />
                                    </label>
                                    <label class="space-y-1 md:col-span-1">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Tên độc giả</span>
                                        <input
                                            type="text"
                                            class="admin-filter-input w-full bg-slate-50 dark:bg-slate-800"
                                            :value="resolvedCard.full_name || '—'"
                                            readonly
                                        />
                                    </label>
                                    <label class="space-y-1">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Loại thẻ</span>
                                        <input
                                            type="text"
                                            class="admin-filter-input w-full bg-slate-50 dark:bg-slate-800"
                                            :value="holderTypeLabel(resolvedCard.holder_type)"
                                            readonly
                                        />
                                    </label>
                                    <label class="space-y-1">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Đang mượn</span>
                                        <input
                                            type="text"
                                            class="admin-filter-input w-full bg-slate-50 dark:bg-slate-800"
                                            :value="`${borrowedValue('total')} cuốn`"
                                            readonly
                                        />
                                    </label>
                                </div>
                                <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">
                                    Giáo trình: {{ borrowedValue('textbook') }} cuốn
                                    · Tài liệu tham khảo: {{ borrowedValue('reference') }} cuốn
                                </p>
                                <div class="mt-2">
                                    <button type="button" class="admin-filter-btn px-3 py-2 min-h-[40px]" @click="resetCardStep">
                                        Đổi thẻ khác
                                    </button>
                                </div>
                            </template>
                        </div>
                        <label class="space-y-1 md:col-span-1">
                            <span class="text-sm font-medium">Hình thức mượn</span>
                            <select
                                v-model="form.loan_type"
                                class="admin-filter-select w-full py-0 leading-[2.25rem]"
                                :disabled="!resolvedCard"
                            >
                                <option v-if="allowHome" value="home">Mượn về nhà</option>
                                <option v-if="allowOnsite" value="onsite">Đọc/mượn tại chỗ</option>
                            </select>
                            <p v-if="resolvedCard && !allowHome && allowOnsite" class="text-xs text-slate-500 mt-1">
                                Bạn đọc ngoài chỉ được đọc/mượn tại chỗ.
                            </p>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Ngày mượn</span>
                        <input v-model="form.loan_date" type="date" class="admin-filter-input w-full" />
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Ngày hẹn trả</span>
                        <input
                            v-model="form.due_date"
                            type="date"
                            class="admin-filter-input w-full"
                            @blur="validateDueDate"
                            @change="validateDueDate"
                        />
                        <p v-if="dueDateError" class="text-sm text-rose-600 mt-0.5">{{ dueDateError }}</p>
                    </label>
                </div>

                <p v-if="limitsHint && resolvedCard" class="text-xs text-slate-600 dark:text-slate-400">
                    {{ limitsHint }}
                </p>

                <label class="space-y-1 block">
                    <span class="text-sm font-medium">Ghi chú</span>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="admin-filter-input w-full"
                        placeholder="Ghi chú thêm..."
                    />
                </label>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold">Danh sách sách mượn</h3>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="(line, index) in form.lines"
                        :key="index"
                        class="border border-slate-200 dark:border-slate-700 rounded-lg p-3 space-y-2"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,2.8fr)_minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.8fr)] gap-2.5 items-start">
                            <div class="space-y-1 relative">
                                <label class="text-xs text-slate-500">Tìm theo mã hoặc tên sách</label>
                                <input
                                    v-model="line.bookQuery"
                                    type="text"
                                    class="admin-filter-input w-full"
                                    placeholder="Gõ mã sách hoặc tên sách..."
                                    @input="scheduleBookSearch(index)"
                                />
                                <div
                                    v-if="line.searchResults.length > 0"
                                    class="absolute z-20 mt-0.5 w-full max-h-48 overflow-y-auto rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 shadow-lg text-sm"
                                >
                                    <button
                                        v-for="b in line.searchResults"
                                        :key="b.id"
                                        type="button"
                                        class="w-full text-left px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 border-b border-slate-100 dark:border-slate-800 last:border-0"
                                        @click="selectBook(index, b)"
                                    >
                                        {{ b.title }}
                                        <span class="text-slate-500"> — còn {{ b.quantity ?? 0 }}</span>
                                    </button>
                                </div>
                                <p v-if="line.loadingBooks" class="text-xs text-slate-500">Đang tìm...</p>
                            </div>
                            <div v-if="line.book_id" class="space-y-1">
                                <label class="text-xs text-slate-500">Loại sách</label>
                                <input
                                    type="text"
                                    class="admin-filter-input w-full bg-slate-50 dark:bg-slate-800"
                                    readonly
                                    :value="bookResourceTypeLabel(line.resource_type)"
                                />
                            </div>
                            <div v-if="line.book_id" class="space-y-1">
                                <label class="text-xs text-slate-500">Sẵn sàng mượn</label>
                                <input
                                    type="text"
                                    class="admin-filter-input w-full bg-slate-50 dark:bg-slate-800"
                                    readonly
                                    :value="String(line.stock ?? 0)"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs text-slate-500">Số lượng mượn</label>
                                <input
                                    v-model.number="line.quantity"
                                    type="number"
                                    min="1"
                                    class="admin-filter-input w-full"
                                    :disabled="!line.book_id"
                                    @input="validateLineQuantity(index)"
                                    @blur="validateLineQuantity(index)"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs text-slate-500">Tình trạng khi mượn</label>
                                <select
                                    v-model="line.condition_on_loan"
                                    class="admin-filter-select w-full py-0 leading-[2.25rem]"
                                >
                                    <option value="tot">Sách còn tốt</option>
                                    <option value="hong">Sách hư hỏng</option>
                                    <option value="mat">Sách bị mất</option>
                                </select>
                            </div>
                        </div>
                        <p v-if="line.stockMsg" class="text-sm text-rose-600">{{ line.stockMsg }}</p>
                        <p v-if="line.qtyMsg" class="text-sm text-rose-600">{{ line.qtyMsg }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="admin-filter-btn px-3 py-2 min-h-[40px]" @click="addLine">
                                Thêm sách
                            </button>
                            <button type="button" class="admin-filter-btn px-3 py-2 min-h-[40px]" @click="removeLine(index)">
                                Xóa sách
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="admin-filter-btn px-4 py-2.5 min-h-[44px]" :disabled="saving" @click="saveLoan">
                    {{ saving ? 'Đang lưu...' : (isBorrowRequestMode ? 'Duyệt và tạo phiếu' : 'Tạo phiếu') }}
                </button>
                <button
                    type="button"
                    class="admin-filter-btn px-4 py-2.5 min-h-[44px]"
                    @click="router.visit(route(isBorrowRequestMode ? 'admin.loans.borrow-requests' : 'admin.loans.index'))"
                >
                    Quay lại
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
