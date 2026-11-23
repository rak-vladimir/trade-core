import axios from 'axios';
import { normalizeApiError } from './api-errors';

const api = axios.create({
    baseURL: '/api/v1',
    timeout: 15_000,
    headers: {
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

api.interceptors.response.use(
    (response) => response,
    (error: unknown) => Promise.reject(normalizeApiError(error)),
);

export default api;
