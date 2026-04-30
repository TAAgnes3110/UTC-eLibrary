<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { useLibraryCardCounterPage } from '@/composables/admin/useLibraryCardCounterPage';
import { holderLabel } from '@/config/libraryCardUi';

const props = defineProps({
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
});

const c = useLibraryCardCounterPage(props);

function onPhotoChange(e) {
    const file = e.target?.files?.[0];
    if (!file) return;
    c.form.photoFile = file instanceof File ? file : null;
}
</script>

<template>
    <Head title="Cấp thẻ tại quầy — Admin" />
    <AdminLayout
        title="Thẻ thư viện"
        :breadcrumbs="[
            { label: 'Thẻ thư viện' },
            { label: 'Cấp thẻ tại quầy' },
        ]"
    >
        <div class="max-w-3xl space-y-3 animate-in fade-in-50 duration-500">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">Cấp thẻ tại quầy</h2>
                <div
                    class="flex flex-col gap-1.5 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end sm:gap-2 w-full sm:w-auto"
                >
                    <button
                        type="button"
                        :class="[
                            'inline-flex items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition-all !h-8 !min-h-8 px-2.5 whitespace-nowrap w-full sm:w-auto',
                            c.flowMode === 'with_account' ? 'btn-admin-green' : 'admin-filter-btn',
                        ]"
                        @click="c.setFlowMode('with_account')"
                    >
                        <Icon icon="lucide:user-check" class="w-3.5 h-3.5 shrink-0" />
                        Đã có tài khoản
                    </button>
                    <button
                        type="button"
                        :class="[
                            'inline-flex items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition-all !h-8 !min-h-8 px-2.5 whitespace-nowrap w-full sm:w-auto',
                            c.flowMode === 'without_account' ? 'btn-admin-green' : 'admin-filter-btn',
                        ]"
                        @click="c.setFlowMode('without_account')"
                    >
                        <Icon icon="lucide:user-plus" class="w-3.5 h-3.5 shrink-0" />
                        Chưa có tài khoản
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-4 sm:p-5 space-y-4">
                <!-- ========== Đã có tài khoản ========== -->
                <template v-if="c.flowMode === 'with_account'">
                    <div class="space-y-2">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Tìm bạn đọc</p>
                        <div class="flex gap-2 flex-wrap">
                            <input
                                v-model="c.userSearch"
                                type="text"
                                placeholder="Nhập mã định danh (CCCD/mã số) hoặc tên, email..."
                                class="admin-search-input flex-1 min-w-[220px] min-h-[44px]"
                            />
                            <button
                                v-if="c.selectedUser"
                                type="button"
                                class="admin-filter-btn min-h-[44px] !h-auto py-2.5 shrink-0"
                                @click="c.clearPickedUser"
                            >
                                Bỏ chọn
                            </button>
                        </div>
                        <p v-if="c.userSearchLoading" class="text-xs text-slate-500">Đang tìm…</p>
                        <ul
                            v-if="c.userHits.length"
                            class="border border-slate-200 dark:border-slate-700 rounded-lg divide-y divide-slate-100 dark:divide-slate-800 max-h-52 overflow-y-auto"
                        >
                            <li v-for="u in c.userHits" :key="u.id">
                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2.5 min-h-[44px] hover:bg-slate-100 dark:hover:bg-slate-800 text-sm"
                                    @click="c.pickUser(u)"
                                >
                                    <span class="font-mono text-xs text-blue-600 dark:text-blue-400">{{ u.code }}</span>
                                    <span class="text-slate-500"> · {{ c.userTypeDisplay(u.user_type) }}</span>
                                    <br />
                                    <span class="font-medium text-slate-900 dark:text-white">{{ u.name }}</span>
                                    <span class="text-slate-500"> — {{ u.email }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <template v-if="c.selectedUser">
                        <div class="border-b border-slate-200 dark:border-slate-700 pb-3 space-y-0.5">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                {{ c.selectedUser.name }}
                                <span class="font-normal text-slate-500">· {{ c.userTypeDisplay(c.selectedUser.user_type) }}</span>
                            </p>
                            <p class="text-xs text-slate-500 truncate">{{ c.selectedUser.email }}</p>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-500">Loại thẻ</label>
                            <select v-model="c.form.holder_type" class="admin-filter-select w-full mt-1 min-h-[44px]">
                                <option :value="c.LibraryCard.HOLDER_STUDENT">{{ holderLabel('student') }}</option>
                                <option :value="c.LibraryCard.HOLDER_TEACHER">{{ holderLabel('teacher') }}</option>
                                <option :value="c.LibraryCard.HOLDER_EXTERNAL">{{ holderLabel('external') }}</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Mã định danh (mã thẻ) *</label>
                                <input v-model="c.form.code" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Mã định danh (hệ thống)</label>
                                <input :value="c.selectedUser.code" type="text" disabled class="admin-filter-input w-full mt-1 min-h-[44px] opacity-70 cursor-not-allowed" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Họ tên *</label>
                                <input v-model="c.form.full_name" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Email *</label>
                                <input v-model="c.form.email" type="email" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Số điện thoại *</label>
                                <input v-model="c.form.phone" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Ngày sinh *</label>
                                <input v-model="c.form.date_of_birth" type="date" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-500">Địa chỉ</label>
                            <textarea v-model="c.form.address" rows="2" class="admin-filter-input w-full mt-1 py-2 min-h-[72px]" placeholder="Có thể chỉnh sửa theo giấy tờ" />
                        </div>

                        <template v-if="c.form.holder_type === c.LibraryCard.HOLDER_STUDENT">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-500">Khoa *</label>
                                    <select v-model="c.form.faculty_id" class="admin-filter-select w-full mt-1 min-h-[44px]">
                                        <option value="">—</option>
                                        <option v-for="f in c.faculties" :key="f.id" :value="String(f.id)">{{ f.code }} — {{ f.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-500">Niên khóa *</label>
                                    <select v-model="c.form.period_id" class="admin-filter-select w-full mt-1 min-h-[44px]">
                                        <option value="">—</option>
                                        <option v-for="p in c.periods" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Lớp *</label>
                                <input v-model="c.form.class_code" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                            </div>
                        </template>

                        <template v-if="c.form.holder_type === c.LibraryCard.HOLDER_TEACHER">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Khoa *</label>
                                <select v-model="c.form.faculty_id" class="admin-filter-select w-full mt-1 min-h-[44px]">
                                    <option value="">—</option>
                                    <option v-for="f in c.faculties" :key="f.id" :value="String(f.id)">{{ f.code }} — {{ f.name }}</option>
                                </select>
                            </div>
                        </template>

                        <template v-if="c.form.holder_type === c.LibraryCard.HOLDER_EXTERNAL">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Đơn vị / tổ chức (tuỳ chọn)</label>
                                <input v-model="c.form.external_organization" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                            </div>
                        </template>
                    </template>

                    <p v-else class="text-xs text-amber-800 dark:text-amber-200/90 bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/60 rounded-lg px-3 py-2">
                        Chọn một dòng trong danh sách tìm được.
                    </p>
                </template>

                <!-- ========== Chưa có tài khoản ========== -->
                <template v-else>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Loại thẻ</label>
                        <select v-model="c.form.holder_type" class="admin-filter-select w-full mt-1 min-h-[44px]">
                            <option :value="c.LibraryCard.HOLDER_EXTERNAL">{{ holderLabel('external') }} (mặc định)</option>
                            <option :value="c.LibraryCard.HOLDER_STUDENT">{{ holderLabel('student') }}</option>
                            <option :value="c.LibraryCard.HOLDER_TEACHER">{{ holderLabel('teacher') }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Mã định danh *</label>
                            <input v-model="c.form.code" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Họ tên *</label>
                            <input v-model="c.form.full_name" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Ngày sinh *</label>
                            <input v-model="c.form.date_of_birth" type="date" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Email *</label>
                            <input v-model="c.form.email" type="email" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Số điện thoại *</label>
                            <input v-model="c.form.phone" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Địa chỉ (tuỳ chọn)</label>
                            <input v-model="c.form.address" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" placeholder="Nếu để trống, hệ thống ghi nhận theo quy định" />
                        </div>
                    </div>

                    <template v-if="c.form.holder_type === c.LibraryCard.HOLDER_STUDENT">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Khoa *</label>
                                <select v-model="c.form.faculty_id" class="admin-filter-select w-full mt-1 min-h-[44px]">
                                    <option value="">—</option>
                                    <option v-for="f in c.faculties" :key="f.id" :value="String(f.id)">{{ f.code }} — {{ f.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Niên khóa *</label>
                                <select v-model="c.form.period_id" class="admin-filter-select w-full mt-1 min-h-[44px]">
                                    <option value="">—</option>
                                    <option v-for="p in c.periods" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Lớp *</label>
                            <input v-model="c.form.class_code" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                        </div>
                    </template>

                    <template v-if="c.form.holder_type === c.LibraryCard.HOLDER_TEACHER">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Khoa *</label>
                            <select v-model="c.form.faculty_id" class="admin-filter-select w-full mt-1 min-h-[44px]">
                                <option value="">—</option>
                                <option v-for="f in c.faculties" :key="f.id" :value="String(f.id)">{{ f.code }} — {{ f.name }}</option>
                            </select>
                        </div>
                    </template>

                    <template v-if="c.form.holder_type === c.LibraryCard.HOLDER_EXTERNAL">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Đơn vị / tổ chức (tuỳ chọn)</label>
                            <input v-model="c.form.external_organization" type="text" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                        </div>
                    </template>
                </template>

                <!-- Thanh toán + ảnh -->
                <div class="border-t border-slate-200 dark:border-slate-700 pt-3 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 cursor-pointer min-h-[44px]">
                            <input v-model="c.form.paid_at_counter" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                            <span class="text-sm text-slate-700 dark:text-slate-200">Đã thu phí tại quầy</span>
                        </label>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Số tiền</label>
                            <input v-model.number="c.form.payment_amount" type="number" min="0" step="1" class="admin-filter-input w-full mt-1 min-h-[44px]" />
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-500">Ảnh thẻ * (ảnh đại diện trên thẻ)</label>
                        <input id="counter-card-photo-input" type="file" accept="image/*" class="mt-1 block w-full text-sm min-h-[44px]" @change="onPhotoChange" />
                    </div>

                    <button
                        type="button"
                        class="btn-admin-green inline-flex items-center justify-center gap-2 w-full sm:w-auto min-h-[44px] !h-auto py-2.5 px-4 rounded-lg text-xs font-bold disabled:opacity-50 disabled:pointer-events-none"
                        :disabled="c.submitLoading || (c.flowMode === 'with_account' && !c.selectedUser)"
                        @click="c.submit"
                    >
                        <Icon icon="lucide:id-card" class="w-4 h-4 shrink-0" />
                        {{ c.submitLoading ? 'Đang gửi…' : 'Tạo hồ sơ thẻ' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
