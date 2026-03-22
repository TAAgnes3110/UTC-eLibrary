import { ref, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usersApi } from '@/api/users';
import { toast } from '@/store/toast';
import { USER_FORM_FIELD_MAP, USER_ERROR_DISPLAY_KEYS } from '@/utils/laravelApiError';
import { useApiFieldErrors } from '@/composables/useApiFieldErrors';
import { toastShort, userFormClientError } from '@/constants/adminUiMessages';

function collectUserClientErrors(form, isEditing) {
    const errors = {};
    if (!String(form.name ?? '').trim()) errors.name = userFormClientError.nameRequired;
    if (!String(form.email ?? '').trim()) errors.email = userFormClientError.emailRequired;
    if (!String(form.code ?? '').trim()) errors.code = userFormClientError.codeRequired;
    const phone = String(form.phone ?? '').trim();
    if (phone && !/^0[0-9]{9,10}$/.test(phone)) {
        errors.phone = userFormClientError.phoneInvalid;
    }
    if (!isEditing) {
        const pw = String(form.password ?? '').trim();
        const pwc = String(form.password_confirmation ?? '').trim();
        if (!pw) errors.password = userFormClientError.passwordRequired;
        else if (pw.length < 6) errors.password = userFormClientError.passwordMin;
        if (!pwc) errors.password_confirmation = userFormClientError.passwordConfirmRequired;
        else if (pw && pwc && pw !== pwc) errors.password_confirmation = userFormClientError.passwordConfirmMismatch;
    }
    return { ok: Object.keys(errors).length === 0, errors };
}

export const USERS_SEARCH_IN_OPTIONS = [
    { key: 'name', label: 'Họ tên' },
    { key: 'email', label: 'Email' },
    { key: 'code', label: 'Mã số' },
    { key: 'phone', label: 'Số điện thoại' },
];

export function useUsersAdminPage(props) {
    const usersData = ref(null);
    const rolesData = ref(null);
    const loadingFallback = ref(false);

    const syncFromProps = () => {
        usersData.value = props.users;
        rolesData.value = props.roles;
    };

    onMounted(syncFromProps);

    const fetchUsers = async () => {
        loadingFallback.value = true;
        try {
            const payload = await usersApi.list();
            const items = Array.isArray(payload) ? payload : (payload?.data ?? []);
            const meta = payload?.meta ?? {};
            usersData.value = {
                data: items,
                current_page: meta?.current_page ?? 1,
                last_page: meta?.last_page ?? 1,
                per_page: meta?.per_page ?? 20,
                total: meta?.total ?? items.length,
                from: meta?.from ?? null,
                to: meta?.to ?? null,
            };
        } catch (e) {
            console.error('Lỗi khi tải lại danh sách tài khoản:', e);
        } finally {
            loadingFallback.value = false;
        }
    };

    const showModal = ref(false);
    const showDeleteModal = ref(false);
    const showToggleConfirmModal = ref(false);
    const userToToggle = ref(null);
    const showTrashDrawer = ref(false);
    const trashedUsers = ref([]);
    const loadingTrash = ref(false);
    const showAvatarModal = ref(false);
    const avatarTargetUserId = ref(null);
    const avatarUploadLoading = ref(false);
    const avatarBulkMode = ref(false);
    const isEditing = ref(false);
    const selectedUser = ref(null);
    const saveUserLoading = ref(false);

    const {
        fieldErrors: userFormErrors,
        clearField: clearUserFieldError,
        clearAll: clearUserFormErrors,
        applyAxios422: applyUserApiErrors,
        setClientErrors: setUserClientErrors,
    } = useApiFieldErrors(USER_FORM_FIELD_MAP, { displayKeys: USER_ERROR_DISPLAY_KEYS });

    const form = useForm({
        id: null,
        name: '',
        email: '',
        phone: '',
        code: '',
        role: 'MEMBER',
        is_active: true,
        password: '',
        password_confirmation: '',
    });

    const usersList = computed(() => (usersData.value ?? props.users)?.data ?? []);

    const filterValues = ref({
        status: '',
        searchKeyword: '',
        searchIn: { name: true, email: true, code: true, phone: true },
        roleFilter: {},
    });
    const showFilterPanel = ref(false);

    const roleOptions = computed(() => {
        const roles = rolesData.value ?? props.roles;
        return roles?.length ? roles : [];
    });

    const ROLE_FILTER_OPTIONS = computed(() => ({
        title: 'Phân quyền',
        options: (roleOptions.value || [])
            .map((r) => ({
                key: r.id ?? r.value ?? r.role ?? '',
                label: r.text ?? r.label ?? r.name ?? r.id ?? r.value ?? '',
            }))
            .filter((o) => o.key),
    }));

    const filteredUsers = computed(() => {
        let result = usersList.value;
        const kw = (filterValues.value.searchKeyword || '').trim().toLowerCase();
        const sin = filterValues.value.searchIn || {};
        if (kw) {
            const anyChecked = Object.values(sin).some(Boolean);
            if (anyChecked) {
                result = result.filter((u) => {
                    const m = [];
                    if (sin.name) m.push((u.name || '').toLowerCase().includes(kw));
                    if (sin.email) m.push((u.email || '').toLowerCase().includes(kw));
                    if (sin.code) m.push((u.code || '').toLowerCase().includes(kw));
                    if (sin.phone) m.push((u.phone || '').toLowerCase().includes(kw));
                    return m.some(Boolean);
                });
            }
        }
        if (filterValues.value.status) result = result.filter((u) => u.status === filterValues.value.status);
        const rf = filterValues.value.roleFilter || {};
        const checkedRoles = Object.entries(rf).filter(([, v]) => v).map(([k]) => k);
        if (checkedRoles.length) result = result.filter((u) => checkedRoles.includes(u.role));
        return result;
    });

    const formatDateTime = (value) => {
        if (!value) return '—';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return '—';
        return d.toLocaleString('vi-VN');
    };

    const selectedIds = ref([]);
    const hasSelection = computed(() => selectedIds.value.length > 0);
    const isAllSelected = computed(
        () => filteredUsers.value.length > 0 && selectedIds.value.length === filteredUsers.value.length,
    );

    const toggleSelect = (id) => {
        const idx = selectedIds.value.indexOf(id);
        if (idx >= 0) selectedIds.value.splice(idx, 1);
        else selectedIds.value.push(id);
    };
    const toggleAll = () => {
        if (isAllSelected.value) selectedIds.value = [];
        else selectedIds.value = filteredUsers.value.map((u) => u.id);
    };
    const deselectAll = () => {
        selectedIds.value = [];
    };

    const openAddModal = () => {
        isEditing.value = false;
        form.reset();
        form.clearErrors();
        clearUserFormErrors();
        form.role = 'MEMBER';
        form.is_active = true;
        showModal.value = true;
    };

    const openEditModal = (user) => {
        isEditing.value = true;
        selectedUser.value = user;
        form.clearErrors();
        clearUserFormErrors();
        form.id = user.id;
        form.name = user.name;
        form.email = user.email;
        form.phone = user.phone || '';
        form.code = user.code;
        form.role = user.role;
        form.is_active = !!user.is_active;
        form.password = '';
        form.password_confirmation = '';
        showModal.value = true;
    };

    const closeUserModal = () => {
        showModal.value = false;
        isEditing.value = false;
        selectedUser.value = null;
        form.clearErrors();
        clearUserFormErrors();
    };

    const confirmDelete = (user) => {
        selectedUser.value = user;
        showDeleteModal.value = true;
    };

    const confirmBulkDelete = () => {
        selectedUser.value = null;
        showDeleteModal.value = true;
    };

    const saveUser = async () => {
        if (saveUserLoading.value) return;
        form.clearErrors();
        clearUserFormErrors();
        const client = collectUserClientErrors(form, isEditing.value);
        if (!client.ok) {
            setUserClientErrors(client.errors);
            toast.error(toastShort.fail);
            return;
        }
        const payload = {
            name: form.name,
            email: form.email,
            phone: form.phone || null,
            code: form.code,
            role: form.role,
            is_active: form.is_active,
        };
        if (!isEditing.value) {
            payload.password = form.password;
            payload.password_confirmation = form.password_confirmation;
        } else {
            const pw = String(form.password ?? '').trim();
            if (pw) {
                payload.password = form.password;
                payload.password_confirmation = form.password_confirmation;
            }
        }
        saveUserLoading.value = true;
        try {
            if (isEditing.value && form.id) {
                await usersApi.update(form.id, payload);
            } else {
                await usersApi.create(payload);
            }
            await fetchUsers();
            toast.success(toastShort.ok);
            showModal.value = false;
            form.reset();
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('Lỗi khi lưu tài khoản:', e);
            applyUserApiErrors(e);
            toast.error(toastShort.fail);
        } finally {
            saveUserLoading.value = false;
        }
    };

    const deleteUser = async () => {
        try {
            if (selectedUser.value) {
                await usersApi.remove(selectedUser.value.id);
            } else if (selectedIds.value.length > 0) {
                for (const id of selectedIds.value) {
                    await usersApi.remove(id);
                }
            }
            await fetchUsers();
            if (showTrashDrawer.value) {
                await fetchTrash();
            }
        } catch (e) {
            console.error('Lỗi khi xóa tài khoản:', e);
        }
        selectedUser.value = null;
        selectedIds.value = [];
        showDeleteModal.value = false;
    };

    const openTrashDrawer = () => {
        showTrashDrawer.value = true;
        fetchTrash();
    };

    const fetchTrash = async () => {
        loadingTrash.value = true;
        try {
            const payload = await usersApi.trash();
            trashedUsers.value = Array.isArray(payload) ? payload : (payload?.data ?? []);
        } catch (e) {
            trashedUsers.value = [];
            console.error('Lỗi khi tải thùng rác tài khoản:', e);
        }
        loadingTrash.value = false;
    };

    const onRestoreUser = async (id) => {
        try {
            await usersApi.restore(id);
            await fetchUsers();
            await fetchTrash();
            toast.success('Đã khôi phục.', { title: 'Thùng rác' });
        } catch (e) {
            console.error('Lỗi khi khôi phục tài khoản:', e);
            toast.error('Không thể khôi phục. Vui lòng thử lại.', { title: 'Thùng rác' });
        }
    };

    const onRestoreManyUsers = async (ids) => {
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!confirm(`Khôi phục ${ids.length} mục?`)) return;
        try {
            if (typeof usersApi.restoreMany === 'function') {
                await usersApi.restoreMany(ids);
            } else {
                await Promise.all(ids.map((id) => usersApi.restore(id)));
            }
            await fetchUsers();
            await fetchTrash();
            toast.success(`Đã khôi phục ${ids.length} mục.`, { title: 'Thùng rác' });
        } catch (e) {
            console.error('Lỗi khi khôi phục nhiều tài khoản:', e);
            toast.error('Không thể khôi phục các mục đã chọn.', { title: 'Thùng rác' });
        }
    };

    const onForceDeleteUser = async (id) => {
        if (!confirm('Xóa vĩnh viễn? Không thể khôi phục.')) return;
        try {
            await usersApi.forceDelete(id);
            trashedUsers.value = (trashedUsers.value || []).filter((u) => u.id !== id);
            await fetchUsers();
            await fetchTrash();
            toast.success('Đã xóa vĩnh viễn.', { title: 'Thùng rác' });
        } catch (e) {
            console.error('Lỗi khi xóa vĩnh viễn tài khoản:', e);
            toast.error('Không thể xóa vĩnh viễn. Vui lòng thử lại.', { title: 'Thùng rác' });
        }
    };

    const onForceDeleteManyUsers = async (ids) => {
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!confirm(`Xóa vĩnh viễn ${ids.length} mục? Không thể khôi phục.`)) return;
        try {
            if (typeof usersApi.forceDeleteMany === 'function') {
                await usersApi.forceDeleteMany(ids);
            } else {
                await Promise.all(ids.map((id) => usersApi.forceDelete(id)));
            }
            trashedUsers.value = (trashedUsers.value || []).filter((u) => !ids.includes(u.id));
            await fetchUsers();
            await fetchTrash();
            toast.success(`Đã xóa vĩnh viễn ${ids.length} mục.`, { title: 'Thùng rác' });
        } catch (e) {
            console.error('Lỗi khi xóa vĩnh viễn nhiều tài khoản:', e);
            toast.error('Không thể xóa vĩnh viễn các mục đã chọn.', { title: 'Thùng rác' });
        }
    };

    const openToggleConfirm = (user) => {
        userToToggle.value = user;
        showToggleConfirmModal.value = true;
    };

    const closeToggleConfirm = () => {
        showToggleConfirmModal.value = false;
        userToToggle.value = null;
    };

    const toggleStatus = async () => {
        const user = userToToggle.value;
        if (!user) return;
        try {
            const res = await usersApi.toggleStatus(user.id);
            if (res?.is_active !== undefined) {
                user.is_active = res.is_active;
                user.status = res.is_active ? 'active' : 'blocked';
            }
            closeToggleConfirm();
        } catch (e) {
            console.error('Lỗi khi khóa/mở khóa tài khoản:', e);
            closeToggleConfirm();
        }
    };

    const isLockAction = computed(() => userToToggle.value?.status === 'active');

    const exportExcel = async () => {
        try {
            const params = {};
            if (selectedIds.value.length > 0) {
                params.ids = selectedIds.value;
            } else if (
                filterValues.value.searchKeyword ||
                filterValues.value.status ||
                Object.values(filterValues.value.searchIn || {}).some((v) => v !== undefined) ||
                Object.values(filterValues.value.roleFilter || {}).some(Boolean)
            ) {
                params.ids = filteredUsers.value.map((u) => u.id);
            }
            const response = await usersApi.export(params);
            const blob = new Blob([response.data], {
                type:
                    response.headers['content-type'] ||
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'danh_sach_tai_khoan.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            toast.success('Đã xuất Excel.', { title: 'Xuất Excel' });
        } catch (e) {
            console.error('Lỗi khi xuất Excel:', e);
            const res = e?.response?.data || {};
            const msg = res?.message || res?.error || 'Không thể xuất Excel.';
            toast.error(msg, { title: 'Xuất Excel' });
        }
    };

    const openImportModal = () => {};

    const closeAvatarModal = () => {
        showAvatarModal.value = false;
        avatarTargetUserId.value = null;
        avatarBulkMode.value = false;
    };

    const openAvatarModal = (user = null) => {
        if (user) {
            avatarBulkMode.value = false;
            avatarTargetUserId.value = user.id;
        } else {
            avatarBulkMode.value = selectedIds.value.length !== 1;
            avatarTargetUserId.value = selectedIds.value.length === 1 ? selectedIds.value[0] : null;
        }
        showAvatarModal.value = true;
    };

    const uploadAvatar = async (file) => {
        if (!file) return;
        avatarUploadLoading.value = true;
        try {
            const formData = new FormData();
            if (avatarBulkMode.value) {
                formData.append('file', file);
                if (selectedIds.value.length > 0) {
                    formData.append('ids', JSON.stringify(selectedIds.value));
                }
                const body = await usersApi.bulkUpdateAvatar(formData);
                const summary = body?.data ?? {};
                const updated = Number(summary.updated ?? 0);
                const skipped = Number(summary.skipped ?? 0);
                const selectedCount = summary.selected_count != null ? Number(summary.selected_count) : 0;
                const selectedMissing = summary.selected_missing != null ? Number(summary.selected_missing) : 0;
                const hadSelectionFilter = summary.selected_count != null;
                await fetchUsers();
                if (updated > 0) {
                    if (hadSelectionFilter && selectedMissing > 0) {
                        toast.warn(
                            `Cập nhật ${updated}/${selectedCount} — thiếu ${selectedMissing} ảnh trong zip.` +
                                (skipped > 0 ? ` (+${skipped} file bỏ qua)` : ''),
                            { title: 'Ảnh đại diện' },
                        );
                    } else if (hadSelectionFilter && selectedMissing === 0 && selectedCount > 0) {
                        toast.success(`Đủ ${updated}/${selectedCount} tài khoản đã chọn.`, { title: 'Ảnh đại diện' });
                    } else if (skipped > 0) {
                        toast.success(`${updated} ảnh · ${skipped} file bỏ qua`, { title: 'Ảnh đại diện' });
                    } else {
                        toast.success(`${updated} ảnh đại diện`, { title: 'Ảnh đại diện' });
                    }
                } else {
                    const picked = selectedIds.value.length > 0;
                    toast.warn(
                        skipped > 0
                            ? picked
                                ? `0 ảnh — không khớp mã với ${skipped} file.`
                                : `0 ảnh — ${skipped} file không khớp mã.`
                            : 'Zip trống hoặc không đọc được.',
                        { title: 'Ảnh đại diện' },
                    );
                }
            } else {
                const userId = avatarTargetUserId.value ?? selectedIds.value[0];
                if (!userId) {
                    toast.info('Vui lòng chọn đúng 1 người để cập nhật ảnh đại diện.', { title: 'Ảnh đại diện' });
                    avatarUploadLoading.value = false;
                    return;
                }
                formData.append('avatar', file);
                await usersApi.updateAvatar(userId, formData);
                await fetchUsers();
                toast.success('Cập nhật ảnh đại diện thành công.', { title: 'Ảnh đại diện' });
            }
            closeAvatarModal();
        } catch (err) {
            console.error('Lỗi khi cập nhật ảnh đại diện:', err);
            const res = err?.response?.data || {};
            const message =
                res.message ||
                res.messages ||
                res.error ||
                'Cập nhật ảnh đại diện không thành công. Vui lòng kiểm tra lại file.';
            toast.error(message, { title: 'Ảnh đại diện' });
        } finally {
            avatarUploadLoading.value = false;
        }
    };

    return {
        loadingFallback,
        showModal,
        showDeleteModal,
        showToggleConfirmModal,
        userToToggle,
        showTrashDrawer,
        trashedUsers,
        loadingTrash,
        showAvatarModal,
        avatarUploadLoading,
        avatarBulkMode,
        isEditing,
        selectedUser,
        saveUserLoading,
        userFormErrors,
        clearUserFieldError,
        clearUserFormErrors,
        form,
        filterValues,
        showFilterPanel,
        filteredUsers,
        roleOptions,
        ROLE_FILTER_OPTIONS,
        formatDateTime,
        selectedIds,
        hasSelection,
        isAllSelected,
        toggleSelect,
        toggleAll,
        deselectAll,
        openAddModal,
        openEditModal,
        closeUserModal,
        confirmDelete,
        confirmBulkDelete,
        saveUser,
        deleteUser,
        openTrashDrawer,
        onRestoreUser,
        onRestoreManyUsers,
        onForceDeleteUser,
        onForceDeleteManyUsers,
        openToggleConfirm,
        closeToggleConfirm,
        toggleStatus,
        isLockAction,
        exportExcel,
        openImportModal,
        closeAvatarModal,
        openAvatarModal,
        uploadAvatar,
    };
}
