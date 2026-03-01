/**
 * Master data cache: faculties, departments, cohorts, role_types. Load 1 lần khi app khởi động.
 * Có thể chuyển sang Pinia (defineStore) khi cài pinia.
 */
import { ref } from 'vue';
import { masterDataApi } from '../api';

export const faculties = ref([]);
export const departments = ref([]);
export const cohorts = ref([]);
export const roleTypes = ref([]);
export const masterDataLoaded = ref(false);

export async function loadMasterData() {
    if (masterDataLoaded.value) return { faculties: faculties.value, departments: departments.value, cohorts: cohorts.value, role_types: roleTypes.value };
    const data = await masterDataApi.get();
    const payload = data?.data ?? data;
    faculties.value = payload.faculties ?? [];
    departments.value = payload.departments ?? [];
    cohorts.value = payload.cohorts ?? [];
    roleTypes.value = payload.role_types ?? [];
    masterDataLoaded.value = true;
    return { faculties: faculties.value, departments: departments.value, cohorts: cohorts.value, role_types: roleTypes.value };
}

export function clearMasterData() {
    faculties.value = [];
    departments.value = [];
    cohorts.value = [];
    roleTypes.value = [];
    masterDataLoaded.value = false;
}
