import axios from 'axios';
import type { ApiErrorResponse, ValidationErrors } from '@/types/api';

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status?: number,
        public readonly errors: ValidationErrors = {},
        public readonly code?: string,
    ) {
        super(message);

        this.name = 'ApiError';
    }

    get hasValidationErrors(): boolean {
        return Object.keys(this.errors).length > 0;
    }
}

export function normalizeApiError(error: unknown): ApiError {
    if (!axios.isAxiosError<ApiErrorResponse>(error)) {
        return new ApiError(error instanceof Error ? error.message : 'An unexpected error occurred.');
    }

    const status = error.response?.status;
    const responseData = error.response?.data;

    return new ApiError(responseData?.message ?? error.message ?? 'An unexpected error occurred.', status, responseData?.errors ?? {}, error.code);
}
