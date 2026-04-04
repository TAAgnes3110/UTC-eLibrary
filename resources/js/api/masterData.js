import client from './axios';

/**
 * GET /api/v1/master-data — faculties, periods, departments, …
 * @returns {Promise<Record<string, unknown>>}
 */
export async function fetchMasterDataPayload() {
    const res = await client.get('/master-data');
    return res.data?.data ?? {};
}
