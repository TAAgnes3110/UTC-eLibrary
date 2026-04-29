<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { readerBookShowStrings as S } from '@/config/readerStrings'
import { toast } from '@/store/toast'
import { meBorrowRequestsApi } from '@/api/meBorrowRequests'

const page = usePage()
const isAuthed = computed(() => !!page.props.auth?.user)
const creatingBorrowRequest = ref(false)
const borrowQty = ref(1)
const showBorrowPreview = ref(false)
const loanType = ref('home')
const requestedDueDate = ref('')
const CART_KEY = 'reader_borrow_cart_v1'
const todayIso = computed(() => new Date().toISOString().slice(0, 10))

const props = defineProps({
    book: { type: Object, required: true },
    availability: {
        type: Object,
        required: true,
        validator: (v) => v && typeof v.total === 'number',
    },
})

async function createBorrowRequestSingle() {
    if (!isAuthed.value || creatingBorrowRequest.value) {
        return
    }
    if (loanType.value === 'home' && !requestedDueDate.value) {
        toast.warn('Vui lòng chọn ngày trả dự kiến khi mượn về nhà.', { title: 'Yêu cầu mượn' })
        return
    }
    if (Number(props.availability?.available || 0) <= 0) {
        toast.warn('Sách hiện đã hết khả dụng để tạo yêu cầu mượn.', { title: 'Yêu cầu mượn' })
        return
    }
    creatingBorrowRequest.value = true
    try {
        await meBorrowRequestsApi.create({
            loan_type: loanType.value,
            book_ids: [props.book.id],
            quantity: [Math.max(1, Number(borrowQty.value || 1))],
            requested_due_date: loanType.value === 'home' ? requestedDueDate.value : undefined,
        })
        toast.success('Đã tạo yêu cầu mượn cho sách này.', { title: 'Yêu cầu mượn' })
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không tạo được yêu cầu mượn.', { title: 'Yêu cầu mượn' })
    } finally {
        creatingBorrowRequest.value = false
    }
}

function openBorrowPreview() {
    if (Number(props.availability?.available || 0) <= 0) {
        toast.warn('Sách hiện đã hết khả dụng để tạo yêu cầu mượn.', { title: 'Yêu cầu mượn' })
        return
    }
    borrowQty.value = Math.max(1, Math.min(Number(props.availability?.available || 1), Number(borrowQty.value || 1)))
    showBorrowPreview.value = true
}

function adjustBorrowQty(delta) {
    const max = Math.max(1, Number(props.availability?.available || 1))
    const current = Math.max(1, Number(borrowQty.value || 1))
    borrowQty.value = Math.max(1, Math.min(max, current + delta))
}

function onBorrowQtyInput(event) {
    const max = Math.max(1, Number(props.availability?.available || 1))
    const next = Math.max(1, Math.min(max, Number(event?.target?.value || 1)))
    borrowQty.value = next
}

function addToBorrowCart() {
    if (!isAuthed.value) {
        toast.warn('Vui lòng đăng nhập để dùng giỏ mượn.', { title: 'Giỏ mượn' })
        return
    }
    const qty = Math.max(1, Math.min(Number(props.availability?.available || 1), Number(borrowQty.value || 1)))
    try {
        const current = JSON.parse(localStorage.getItem(CART_KEY) || '[]')
        const items = Array.isArray(current) ? current : []
        const idx = items.findIndex((x) => Number(x.book_id) === Number(props.book.id))
        if (idx >= 0) {
            items[idx].quantity = qty
        } else {
            items.push({ book_id: Number(props.book.id), quantity: qty })
        }
        localStorage.setItem(CART_KEY, JSON.stringify(items))
        toast.success('Đã thêm vào giỏ mượn.', { title: 'Giỏ mượn' })
    } catch {
        toast.error('Không thể thêm vào giỏ mượn.', { title: 'Giỏ mượn' })
    }
}

const publicationLine = computed(() => {
    const b = props.book
    const parts = []
    if (b.publishers_label) {
        parts.push(b.publishers_label)
    }
    if (b.published_year) {
        parts.push(String(b.published_year))
    }
    if (b.publisher_place) {
        parts.push(b.publisher_place)
    }
    return parts.length ? parts.join(' — ') : '—'
})

const physicalLine = computed(() => {
    const b = props.book
    const parts = []
    if (b.pages) {
        parts.push(`${b.pages} tr.`)
    }
    if (b.book_size) {
        parts.push(b.book_size)
    }
    return parts.length ? parts.join(' · ') : '—'
})

const priceFmt = computed(() => {
    const n = Number(props.book.price)
    if (!Number.isFinite(n)) {
        return '—'
    }
    return new Intl.NumberFormat('vi-VN').format(n) + ' đ'
})

const subjectLine = computed(() => {
    const b = props.book
    const c = b.classification?.name
    return c || '—'
})

const keywordLine = computed(() => {
    const k = props.book.thesis_metadata?.keywords
    if (k && String(k).trim() !== '') {
        return String(k)
    }
    return '—'
})

const headTitle = computed(() => `${props.book.title} — ${S.headTitleSuffix}`)
</script>

<template>
    <ReaderLayout>
        <Head :title="headTitle" />
        <div class="mx-auto max-w-5xl animate-in fade-in-50 duration-500">
            <div class="mb-4">
                <Link
                    :href="route('reader.catalog')"
                    class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-400"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ S.backCatalog }}
                </Link>
            </div>
            <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-400" aria-label="Breadcrumb">
                <Link :href="route('reader.home')" class="font-medium hover:text-blue-800 dark:hover:text-blue-400">{{
                    S.breadcrumbHome
                }}</Link>
                <span aria-hidden="true">/</span>
                <Link :href="route('reader.catalog')" class="font-medium hover:text-blue-800 dark:hover:text-blue-400">{{
                    S.breadcrumbCatalog
                }}</Link>
                <span aria-hidden="true">/</span>
                <span class="line-clamp-1 font-semibold text-slate-900 dark:text-slate-100">{{ book.title }}</span>
            </nav>

            <article
                class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg dark:border-slate-700/80 dark:bg-slate-900"
            >
                <div class="grid gap-8 p-5 sm:p-8 lg:grid-cols-[240px_1fr] lg:gap-10">
                    <div class="relative mx-auto w-full max-w-[240px] shrink-0">
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
                            <img
                                v-if="book.cover_image"
                                :src="book.cover_image"
                                :alt="book.title"
                                class="aspect-[3/4] w-full object-cover"
                            />
                            <div v-else class="flex aspect-[3/4] w-full items-center justify-center text-slate-400">
                                <Icon icon="lucide:book-open" class="h-20 w-20 opacity-40" aria-hidden="true" />
                                <span class="sr-only">{{ S.noCover }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <h1 class="text-2xl font-black leading-tight text-slate-900 dark:text-white sm:text-3xl">
                            {{ book.title }}
                        </h1>
                        <p v-if="book.sub_title" class="mt-2 text-base text-slate-600 dark:text-slate-300">
                            {{ book.sub_title }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                            >
                                {{ book.resource_type_label }}
                            </span>
                            <span
                                v-if="book.book_code"
                                class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-600 dark:text-slate-400"
                            >
                                {{ book.book_code }}
                            </span>
                        </div>

                        <dl class="mt-6 space-y-3 text-sm">
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.authors }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ book.authors_label || '—' }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.publicationInfo }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ publicationLine }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.physicalDesc }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ physicalLine }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.price }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ priceFmt }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.subject }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ subjectLine }}</dd>
                            </div>
                            <div class="grid gap-1 sm:grid-cols-[140px_1fr] sm:gap-4">
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ S.keywords }}</dt>
                                <dd class="text-slate-900 dark:text-slate-100">{{ keywordLine }}</dd>
                            </div>
                        </dl>

                        <p class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-200">
                            {{ S.borrowAtDeskHint }}
                        </p>

                        <div class="mt-6">
                            <template v-if="!isAuthed">
                                <Link
                                    :href="route('login')"
                                    class="inline-flex min-h-[48px] min-w-[48px] items-center justify-center gap-2 rounded-xl bg-blue-900 px-6 text-sm font-bold text-white hover:bg-blue-800"
                                >
                                    <Icon icon="lucide:bookmark" class="h-5 w-5 shrink-0" aria-hidden="true" />
                                    Đăng nhập để mượn
                                </Link>
                            </template>
                            <template v-else>
                                <div class="mb-3 flex flex-wrap items-center gap-3">
                                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Số lượng:</label>
                                    <div class="inline-flex h-11 items-center overflow-hidden rounded-xl border border-slate-300/80 bg-white shadow-sm dark:border-slate-600 dark:bg-slate-900">
                                        <button
                                            type="button"
                                            class="inline-flex h-full w-11 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                            :disabled="Number(borrowQty || 1) <= 1"
                                            @click="adjustBorrowQty(-1)"
                                        >
                                            -
                                        </button>
                                        <input
                                            :value="borrowQty"
                                            type="number"
                                            min="1"
                                            :max="Math.max(1, Number(availability.available || 1))"
                                            class="h-full w-14 border-x border-slate-200 bg-transparent text-center text-base font-bold text-slate-900 [appearance:textfield] focus:outline-none dark:border-slate-700 dark:text-slate-100"
                                            @input="onBorrowQtyInput($event)"
                                        />
                                        <button
                                            type="button"
                                            class="inline-flex h-full w-11 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                            :disabled="Number(borrowQty || 1) >= Math.max(1, Number(availability.available || 1))"
                                            @click="adjustBorrowQty(1)"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Tối đa {{ Math.max(0, Number(availability.available || 0)) }}</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-3">
                                <button
                                    type="button"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border border-emerald-600 bg-white px-4 text-sm font-bold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-400 dark:bg-slate-900 dark:text-emerald-300 dark:hover:bg-slate-800"
                                    :disabled="Number(availability.available || 0) <= 0"
                                    @click="addToBorrowCart"
                                >
                                    <Icon icon="lucide:shopping-cart" class="h-5 w-5" />
                                    {{ Number(availability.available || 0) > 0 ? 'Thêm vào giỏ mượn' : 'Hết sách' }}
                                </button>
                                <button
                                    type="button"
                                        class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-600 disabled:opacity-60"
                                    :disabled="creatingBorrowRequest || Number(availability.available || 0) <= 0"
                                    @click="openBorrowPreview"
                                >
                                    <Icon :icon="creatingBorrowRequest ? 'lucide:loader-2' : 'lucide:shopping-cart'" :class="creatingBorrowRequest ? 'h-5 w-5 animate-spin' : 'h-5 w-5'" />
                                    {{ Number(availability.available || 0) > 0 ? 'Tạo yêu cầu mượn' : 'Hết sách' }}
                                </button>
                                </div>
                            </template>
                        </div>
                        <p v-if="!isAuthed" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Đăng nhập để thêm vào giỏ mượn và gửi yêu cầu mượn.
                        </p>
                        <Link
                            v-if="isAuthed"
                            :href="route('reader.services.borrow-cart')"
                            class="mt-2 inline-flex text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-300"
                        >
                            Xem giỏ mượn
                        </Link>
                    </div>
                </div>

                <div class="border-t border-slate-100 px-5 py-6 dark:border-slate-800 sm:px-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ S.availabilityTitle }}
                    </h2>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <span class="inline-flex rounded-full bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                            {{ S.totalCopies }}: {{ availability.total }}
                        </span>
                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                            {{ S.availableCopies }}: {{ availability.available }}
                        </span>
                        <span class="inline-flex rounded-full bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-900 dark:bg-rose-950 dark:text-rose-200">
                            {{ S.borrowedCopies }}: {{ availability.borrowed }}
                        </span>
                    </div>
                </div>

                <div v-if="book.summary" class="border-t border-slate-100 px-5 py-6 dark:border-slate-800 sm:px-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ S.summaryTitle }}
                    </h2>
                    <p class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                        {{ book.summary }}
                    </p>
                </div>

                <div
                    v-if="Array.isArray(book.digital_assets) && book.digital_assets.length"
                    class="border-t border-slate-100 px-5 py-6 dark:border-slate-800 sm:px-8"
                >
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ S.digitalAssets }}
                    </h2>
                    <ul class="mt-3 list-inside list-disc text-sm text-slate-700 dark:text-slate-300">
                        <li v-for="(asset, idx) in book.digital_assets" :key="asset.id ?? idx">
                            {{ asset.original_name || 'Tài liệu' }}
                        </li>
                    </ul>
                </div>

            </article>

            <div v-if="showBorrowPreview" class="fixed inset-0 z-50 flex items-end justify-center bg-black/55 p-3 backdrop-blur-[2px] sm:items-center">
                <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Xác nhận tạo yêu cầu mượn</h3>
                        <button type="button" class="rounded-md px-2 py-1 text-sm text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white" @click="showBorrowPreview = false">Đóng</button>
                    </div>
                    <p class="text-sm text-slate-700 dark:text-slate-200">Sách: <span class="font-semibold">{{ book.title }}</span></p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Số lượng sách khả dụng: {{ availability.available }}</p>
                    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-600 dark:bg-slate-800/80">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Hình thức mượn</p>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                <input v-model="loanType" type="radio" value="home" />
                                Mượn về nhà
                            </label>
                            <label class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                <input v-model="loanType" type="radio" value="onsite" />
                                Đọc tại chỗ
                            </label>
                        </div>
                        <div v-if="loanType === 'home'" class="mt-3">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-200">
                                Ngày trả dự kiến
                            </label>
                            <input
                                v-model="requestedDueDate"
                                type="date"
                                :min="todayIso"
                                class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-100 dark:[color-scheme:dark] dark:focus:border-emerald-400 dark:focus:ring-emerald-900/50"
                            />
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Số lượng mượn</label>
                        <div class="inline-flex h-11 items-center overflow-hidden rounded-xl border border-slate-300/80 bg-white shadow-sm dark:border-slate-600 dark:bg-slate-900">
                            <button
                                type="button"
                                class="inline-flex h-full w-11 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                :disabled="Number(borrowQty || 1) <= 1"
                                @click="adjustBorrowQty(-1)"
                            >
                                -
                            </button>
                            <input
                                :value="borrowQty"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                class="h-full w-14 border-x border-slate-200 bg-transparent text-center text-base font-bold text-slate-900 [appearance:textfield] focus:outline-none dark:border-slate-700 dark:text-slate-100"
                                @input="onBorrowQtyInput($event)"
                            />
                            <button
                                type="button"
                                class="inline-flex h-full w-11 items-center justify-center text-xl font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800"
                                :disabled="Number(borrowQty || 1) >= Math.max(1, Number(availability.available || 1))"
                                @click="adjustBorrowQty(1)"
                            >
                                +
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Tối đa: {{ Math.max(1, Number(availability.available || 1)) }} · Hình thức: {{ loanType === 'home' ? 'Mượn về nhà' : 'Đọc tại chỗ' }}
                        </p>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-700" @click="showBorrowPreview = false">Hủy</button>
                        <button
                            type="button"
                            class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-60 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                            :disabled="creatingBorrowRequest"
                            @click="showBorrowPreview = false; createBorrowRequestSingle()"
                        >
                            {{ creatingBorrowRequest ? 'Đang gửi...' : 'Gửi yêu cầu' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ReaderLayout>
</template>
