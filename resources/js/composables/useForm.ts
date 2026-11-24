import { computed, reactive, watch } from 'vue';
import type { AxiosResponse } from 'axios';

import api from '@/services/api';
import { ApiError } from '@/services/api-errors';

type FormData = Record<string, unknown>;
type FormMethod = 'post' | 'put' | 'patch' | 'delete';
type FormTransform<T extends FormData> = (data: Partial<T>) => unknown;

export function useForm<T extends FormData>(initialData: T) {
    const data = reactive(structuredClone(initialData)) as T;
    const errors = reactive<Record<string, string[]>>({});
    const processing = reactive({ value: false });
    const hasErrors = computed(() => Object.keys(errors).length > 0);

    let onlyFields: (keyof T)[] = [];
    let exceptFields: (keyof T)[] = [];

    let transformData: FormTransform<T> = (data) => data;

    const defaults = structuredClone(initialData);

    /**
     * Prepare form data for submission.
     */
    function getData(): Partial<T> {
        const result = {} as Partial<T>;

        for (const key of Object.keys(data) as (keyof T)[]) {
            if (onlyFields.length > 0 && !onlyFields.includes(key)) {
                continue;
            }

            if (exceptFields.includes(key)) {
                continue;
            }

            result[key] = structuredClone(data[key]);
        }

        return result;
    }

    /**
     * Select fields to submit.
     */
    function only(...fields: (keyof T)[]): void {
        onlyFields = fields;
        exceptFields = [];
    }

    /**
     * Exclude fields from submission.
     */
    function except(...fields: (keyof T)[]): void {
        exceptFields = fields;
        onlyFields = [];
    }

    /**
     * Transform data before submission.
     */
    function transform(callback: FormTransform<T>): void {
        transformData = callback;
    }

    /**
     * Clear validation errors.
     */
    function clearErrors(...fields: (keyof T)[]): void {
        if (fields.length === 0) {
            for (const key of Object.keys(errors)) {
                delete errors[key];
            }

            return;
        }

        for (const field of fields) {
            delete errors[String(field)];
        }
    }

    /**
     * Reset form data and errors.
     */
    function reset(): void {
        Object.assign(data, structuredClone(defaults));

        clearErrors();

        onlyFields = [];
        exceptFields = [];
    }

    /**
     * Submit form.
     */
    async function submit(method: FormMethod, url: string): Promise<AxiosResponse<unknown>> {
        processing.value = true;

        clearErrors();

        try {
            const payload = transformData(getData());

            return await api.request<unknown>({
                method,
                url,
                data: payload,
            });
        } catch (error: unknown) {
            if (error instanceof ApiError) {
                Object.assign(errors, error.errors);
            }

            throw error;
        } finally {
            processing.value = false;
        }
    }

    function post(url: string) {
        return submit('post', url);
    }

    function put(url: string) {
        return submit('put', url);
    }

    function patch(url: string) {
        return submit('patch', url);
    }

    function deleteRequest(url: string) {
        return submit('delete', url);
    }

    /**
     * Clear field errors when corresponding data changes.
     */
    for (const key of Object.keys(data) as (keyof T)[]) {
        watch(
            () => data[key],
            () => clearErrors(key),
            { deep: true },
        );
    }

    return {
        data,
        errors,
        processing: computed(() => processing.value),
        hasErrors,
        only,
        except,
        transform,
        clearErrors,
        reset,
        submit,
        post,
        put,
        patch,
        delete: deleteRequest,
    };
}
