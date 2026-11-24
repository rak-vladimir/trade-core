import axios from 'axios';

import i18n from '@/i18n';
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

api.interceptors.request.use((config) => {
    config.headers.set('Accept-Language', i18n.global.locale.value);

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error: unknown) => Promise.reject(normalizeApiError(error)),
);

export default api;
