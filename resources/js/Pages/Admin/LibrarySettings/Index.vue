<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import LoanPolicyAccordion from '@/Components/Admin/LibrarySettings/LoanPolicyAccordion.vue';
import LoanPolicyInlineForm from '@/Components/Admin/LibrarySettings/LoanPolicyInlineForm.vue';
import { useLibrarySettingsPage } from '@/composables/admin/useLibrarySettingsPage';

const {
    studentPolicies,
    teacherPolicies,
    externalPolicies,
    loading,
    saving,
    studentForm,
    teacherForm,
    externalForm,
    errorsStudent,
    errorsTeacher,
    errorsExternal,
    openStudent,
    openTeacher,
    openExternal,
    toggleStudent,
    toggleTeacher,
    toggleExternal,
    fetchPolicies,
    saveAll,
    cancelAll,
} = useLibrarySettingsPage();
</script>

<template>
    <Head title="Chính sách mượn - Admin" />
    <AdminLayout
        title="Chính sách mượn"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Chính sách mượn' }]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500 pb-28 sm:pb-8">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Chính sách mượn</h2>
                </div>
                <Button variant="outline" size="sm" class="min-h-11 gap-1.5 shrink-0 self-start" @click="fetchPolicies">
                    <Icon icon="lucide:refresh-cw" class="w-4 h-4" />
                    Tải lại
                </Button>
            </div>

            <div v-if="loading" class="text-sm text-slate-500 py-8 text-center rounded-lg border border-dashed border-slate-200 dark:border-slate-700">
                Đang tải…
            </div>

            <template v-else>
                <p
                    v-if="!studentPolicies.length && !teacherPolicies.length && !externalPolicies.length"
                    class="text-sm text-center text-slate-500 py-6 rounded-xl border border-dashed border-slate-200 dark:border-slate-700"
                >
                    Chưa có cấu hình. Chạy bộ seed dữ liệu mẫu hoặc thêm bản ghi trong cơ sở dữ liệu.
                </p>

                <div v-else class="space-y-3">
                    <LoanPolicyAccordion title="Học sinh" :open="openStudent" @toggle="toggleStudent">
                        <LoanPolicyInlineForm
                            v-if="studentForm.id"
                            mode="internal"
                            :form="studentForm"
                            :field-errors="errorsStudent"
                        />
                        <p v-else class="text-sm text-slate-500">Chưa có cấu hình cho nhóm này.</p>
                    </LoanPolicyAccordion>

                    <LoanPolicyAccordion title="Giáo viên" :open="openTeacher" @toggle="toggleTeacher">
                        <LoanPolicyInlineForm
                            v-if="teacherForm.id"
                            mode="internal"
                            :form="teacherForm"
                            :field-errors="errorsTeacher"
                        />
                        <p v-else class="text-sm text-slate-500">Chưa có cấu hình cho nhóm này.</p>
                    </LoanPolicyAccordion>

                    <LoanPolicyAccordion title="Bạn đọc ngoài" :open="openExternal" @toggle="toggleExternal">
                        <LoanPolicyInlineForm
                            v-if="externalForm.id"
                            mode="external"
                            :form="externalForm"
                            :field-errors="errorsExternal"
                        />
                        <p v-else class="text-sm text-slate-500">Chưa có cấu hình cho nhóm này.</p>
                    </LoanPolicyAccordion>
                </div>
            </template>

            <!-- Nút hành động: trên điện thoại dính đáy màn hình -->
            <div
                class="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur-sm px-4 py-3 sm:static sm:border-0 sm:bg-transparent sm:backdrop-blur-none sm:px-0 sm:py-0 sm:mt-8 flex flex-wrap gap-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]"
            >
                <Button variant="outline" class="min-h-11 px-6" :disabled="loading || saving" @click="cancelAll">Hủy bỏ</Button>
                <Button class="min-h-11 px-6 bg-blue-700 hover:bg-blue-800 text-white" :disabled="loading || saving" @click="saveAll">
                    {{ saving ? 'Đang lưu…' : 'Lưu' }}
                </Button>
            </div>
        </div>
    </AdminLayout>
</template>
