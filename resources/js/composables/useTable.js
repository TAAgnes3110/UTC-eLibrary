/**
 * Composable: logic bảng (selection, pagination, loading) — dùng chung cho Admin list pages.
 */
import { ref, computed } from 'vue';

export function useTable(options = {}) {
    const { multiple = true } = options;
    const selected = ref(multiple ? [] : null);
    const loading = ref(false);
    const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

    const hasSelection = computed(() =>
        multiple ? selected.value.length > 0 : selected.value != null
    );

    function setPagination(meta) {
        if (!meta) return;
        pagination.value = {
            current_page: meta.current_page ?? 1,
            last_page: meta.last_page ?? 1,
            per_page: meta.per_page ?? 10,
            total: meta.total ?? 0,
        };
    }

    function clearSelection() {
        selected.value = multiple ? [] : null;
    }

    return {
        selected,
        loading,
        pagination,
        hasSelection,
        setPagination,
        clearSelection,
    };
}
