import { nextTick } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { AxiosResponse } from 'axios';

import api from '@/services/api';
import { ApiError } from '@/services/api-errors';
import { useForm } from '../useForm';

vi.mock('@/services/api', () => ({
    default: {
        request: vi.fn(),
    },
}));

describe('useForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('initializes with form data and empty errors', () => {
        const form = useForm({
            name: 'John',
            email: 'john@example.com',
        });

        expect(form.data).toEqual({
            name: 'John',
            email: 'john@example.com',
        });
        expect(form.errors).toEqual({});
        expect(form.processing.value).toBe(false);
        expect(form.hasErrors.value).toBe(false);
    });

    it('submits all fields by default', async () => {
        const response = { data: { id: 1 } } as AxiosResponse<unknown>;

        vi.mocked(api.request).mockResolvedValue(response);

        const form = useForm({
            name: 'John',
            email: 'john@example.com',
        });

        await form.post('/users');

        expect(api.request).toHaveBeenCalledWith({
            method: 'post',
            url: '/users',
            data: {
                name: 'John',
                email: 'john@example.com',
            },
        });
    });

    it('submits only selected fields', async () => {
        vi.mocked(api.request).mockResolvedValue({} as AxiosResponse<unknown>);

        const form = useForm({
            name: 'John',
            email: 'john@example.com',
        });

        form.only('name');

        await form.post('/users');

        expect(api.request).toHaveBeenCalledWith(
            expect.objectContaining({
                data: { name: 'John' },
            }),
        );
    });

    it('excludes selected fields from submission', async () => {
        vi.mocked(api.request).mockResolvedValue({} as AxiosResponse<unknown>);

        const form = useForm({
            name: 'John',
            email: 'john@example.com',
        });

        form.except('email');

        await form.post('/users');

        expect(api.request).toHaveBeenCalledWith(
            expect.objectContaining({
                data: { name: 'John' },
            }),
        );
    });

    it('transforms data before submission', async () => {
        vi.mocked(api.request).mockResolvedValue({} as AxiosResponse<unknown>);

        const form = useForm({
            name: 'John',
            email: 'john@example.com',
        });

        form.transform((data) => ({
            ...data,
            name: String(data.name).toUpperCase(),
        }));

        await form.post('/users');

        expect(api.request).toHaveBeenCalledWith(
            expect.objectContaining({
                data: {
                    name: 'JOHN',
                    email: 'john@example.com',
                },
            }),
        );
    });

    it('resets form data and errors', async () => {
        const form = useForm({
            name: 'John',
            email: 'john@example.com',
        });

        form.data.name = 'Jane';
        form.errors.email = ['Invalid email'];

        form.reset();

        expect(form.data).toEqual({
            name: 'John',
            email: 'john@example.com',
        });
        expect(form.errors).toEqual({});
    });

    it('clears field errors when data changes', async () => {
        const form = useForm({
            email: 'invalid',
        });

        form.errors.email = ['Invalid email'];

        form.data.email = 'john@example.com';

        await nextTick();

        expect(form.errors.email).toBeUndefined();
    });

    it('populates validation errors when API request fails', async () => {
        const error = new ApiError('Validation failed', 422, {
            email: ['The email field is required.'],
        });

        vi.mocked(api.request).mockRejectedValue(error);

        const form = useForm({
            email: '',
        });

        await expect(form.post('/users')).rejects.toBe(error);

        expect(form.errors.email).toEqual(['The email field is required.']);
        expect(form.hasErrors.value).toBe(true);
        expect(form.processing.value).toBe(false);
    });

    it('clears all errors when clearErrors is called without fields', () => {
        const form = useForm({
            name: '',
            email: '',
        });

        form.errors.name = ['Required'];
        form.errors.email = ['Invalid'];

        form.clearErrors();

        expect(form.errors).toEqual({});
        expect(form.hasErrors.value).toBe(false);
    });
});
