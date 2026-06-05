import { ref, computed, onMounted } from 'vue';
import { loanPoliciesApi } from '@/api/loanPolicies';
import { toast } from '@/store/toast';
import { extractLaravelValidationErrors } from '@/utils/laravelApiError';

export const USER_TYPE_LABELS = {
    STUDENT: 'Sinh viên',
    TEACHER: 'Giảng viên',
    MEMBER: 'Bạn đọc ngoài',
};

export function userTypeLabel(value) {
    if (!value) return '—';
    return USER_TYPE_LABELS[value] ?? value;
}

function emptyForm() {
    return {
        id: null,
        code: '',
        name: '',
        user_type: '',
        max_books: 0,
        max_days: 0,
        max_renewals: 0,
        overdue_fine_per_day: '0',
        allow_home: true,
        allow_onsite: true,
        max_textbooks: '',
        max_reference: '',
        /** Bản sao params từ server để merge khi PUT */
        _paramsBase: {},
    };
}

function rowToForm(row) {
    if (!row || !row.id) {
        return emptyForm();
    }
    return {
        id: row.id,
        code: row.code ?? '',
        name: row.name ?? '',
        user_type: row.user_type ?? '',
        max_books: row.max_books ?? 0,
        max_days: row.max_days ?? 0,
        max_renewals: row.max_renewals ?? 0,
        overdue_fine_per_day:
            row.overdue_fine_per_day !== undefined && row.overdue_fine_per_day !== null
                ? String(row.overdue_fine_per_day)
                : '0',
        allow_home: !!row.allow_home,
        allow_onsite: row.allow_onsite !== false,
        max_textbooks: row.params?.max_textbooks ?? '',
        max_reference: row.params?.max_reference ?? '',
        _paramsBase: row.params && typeof row.params === 'object' ? { ...row.params } : {},
    };
}

export function useLibrarySettingsPage() {
    const policies = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    const studentForm = ref(emptyForm());
    const teacherForm = ref(emptyForm());
    const externalForm = ref(emptyForm());

    const snapshot = ref({
        student: null,
        teacher: null,
        external: null,
    });

    const openStudent = ref(true);
    const openTeacher = ref(false);
    const openExternal = ref(false);

    const errorsStudent = ref({});
    const errorsTeacher = ref({});
    const errorsExternal = ref({});

    const teacherPolicies = computed(() => policies.value.filter((p) => p.user_type === 'TEACHER'));
    const studentPolicies = computed(() => policies.value.filter((p) => p.user_type === 'STUDENT'));
    const externalPolicies = computed(() => policies.value.filter((p) => p.user_type === 'MEMBER'));

    const parseListPayload = (payload) => {
        const data = payload?.data ?? payload;
        if (Array.isArray(data)) return data;
        if (data && typeof data === 'object' && Array.isArray(data.data)) return data.data;
        return [];
    };

    function applyFormsFromPolicies() {
        const st = studentPolicies.value[0];
        const te = teacherPolicies.value[0];
        const ex = externalPolicies.value[0];

        studentForm.value = rowToForm(st);
        teacherForm.value = rowToForm(te);
        externalForm.value = rowToForm(ex);

        snapshot.value = {
            student: JSON.parse(JSON.stringify(studentForm.value)),
            teacher: JSON.parse(JSON.stringify(teacherForm.value)),
            external: JSON.parse(JSON.stringify(externalForm.value)),
        };
    }

    const fetchPolicies = async () => {
        loading.value = true;
        errorsStudent.value = {};
        errorsTeacher.value = {};
        errorsExternal.value = {};
        try {
            const payload = await loanPoliciesApi.list();
            policies.value = parseListPayload(payload);
            applyFormsFromPolicies();
        } catch (e) {
            policies.value = [];
            toast.error('Không tải được danh sách quy định mượn.');
        } finally {
            loading.value = false;
        }
    };

    onMounted(() => {
        fetchPolicies();
    });
    function clampInt0(v, fallback = 0) {
        const n = Number(v);
        if (Number.isNaN(n)) {
            return fallback;
        }
        return Math.max(0, Math.trunc(n));
    }

    /** Chuỗi số thập phân ≥ 0 (khớp decimal:0,2 phía server). */
    function clampFineString(raw) {
        const n = parseFloat(String(raw ?? '0').replace(',', '.'));
        if (Number.isNaN(n)) {
            return '0';
        }
        return String(Math.max(0, n));
    }

    function buildParamsPayload(form) {
        const p = { ...(form._paramsBase || {}) };
        delete p.max_loan_days;

        if (form.user_type === 'MEMBER') {
            delete p.max_textbooks;
            delete p.max_reference;
        } else {
            const mt = form.max_textbooks;
            const mr = form.max_reference;
            if (mt !== '' && mt !== null && mt !== undefined) {
                p.max_textbooks = clampInt0(mt);
            } else {
                delete p.max_textbooks;
            }
            if (mr !== '' && mr !== null && mr !== undefined) {
                p.max_reference = clampInt0(mr);
            } else {
                delete p.max_reference;
            }
        }

        return Object.keys(p).length ? p : null;
    }

    function buildUpdatePayload(form) {
        const { _paramsBase: _b, ...rest } = form;
        void _b;
        const allowHome = form.user_type === 'MEMBER' ? false : !!form.allow_home;
        return {
            code: rest.code,
            name: rest.name,
            user_type: rest.user_type || null,
            max_books: clampInt0(rest.max_books),
            max_days: clampInt0(rest.max_days),
            max_renewals: clampInt0(rest.max_renewals),
            overdue_fine_per_day: clampFineString(rest.overdue_fine_per_day),
            allow_home: allowHome,
            allow_onsite: !!rest.allow_onsite,
            params: buildParamsPayload(form),
        };
    }

    function map422ToObject(err) {
        const raw = extractLaravelValidationErrors(err);
        if (!raw) return {};
        const out = {};
        for (const [k, v] of Object.entries(raw)) {
            const msg = Array.isArray(v) ? v[0] : v;
            out[k] = typeof msg === 'string' ? msg : String(msg);
        }
        return out;
    }

    async function putPolicy(formRef, errorRef) {
        const form = formRef.value;
        if (!form.id) {
            return { ok: false, skipped: true };
        }
        try {
            await loanPoliciesApi.update(form.id, buildUpdatePayload(form));
            return { ok: true };
        } catch (e) {
            if (e?.response?.status === 422) {
                errorRef.value = map422ToObject(e);
                return { ok: false, validation: true };
            }
            throw e;
        }
    }

    const saveAll = async () => {
        saving.value = true;
        errorsStudent.value = {};
        errorsTeacher.value = {};
        errorsExternal.value = {};

        const order = [
            { formRef: studentForm, errRef: errorsStudent },
            { formRef: teacherForm, errRef: errorsTeacher },
            { formRef: externalForm, errRef: errorsExternal },
        ];

        try {
            for (const { formRef, errRef } of order) {
                const r = await putPolicy(formRef, errRef);
                if (r.skipped) continue;
                if (!r.ok && r.validation) {
                    toast.error('Vui lòng kiểm tra lại dữ liệu đã nhập.');
                    return;
                }
            }
            toast.success('Đã lưu cấu hình thư viện.');
            await fetchPolicies();
        } catch (e) {
            toast.error(e?.response?.data?.messages ?? 'Lưu thất bại.');
        } finally {
            saving.value = false;
        }
    };

    const cancelAll = () => {
        errorsStudent.value = {};
        errorsTeacher.value = {};
        errorsExternal.value = {};
        const s = snapshot.value;
        if (s.student) studentForm.value = JSON.parse(JSON.stringify(s.student));
        if (s.teacher) teacherForm.value = JSON.parse(JSON.stringify(s.teacher));
        if (s.external) externalForm.value = JSON.parse(JSON.stringify(s.external));
    };

    function toggleStudent() {
        openStudent.value = !openStudent.value;
    }
    function toggleTeacher() {
        openTeacher.value = !openTeacher.value;
    }
    function toggleExternal() {
        openExternal.value = !openExternal.value;
    }

    const boolLabel = (v) => (v ? 'Có' : 'Không');

    return {
        policies,
        teacherPolicies,
        studentPolicies,
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
        userTypeLabel,
        boolLabel,
    };
}
