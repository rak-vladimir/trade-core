import { nextTick } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import { useFilter } from '../useFilter';

describe('useFilter', () => {
    it('initializes with default values', () => {
        const filter = useFilter();

        expect(filter.search.value).toBe('');
        expect(filter.page.value).toBe(1);
        expect(filter.perPage.value).toBe(15);
        expect(filter.sortBy.value).toBeNull();
        expect(filter.sortDirection.value).toBe('asc');
        expect(filter.queryParams.value).toEqual({
            page: 1,
            per_page: 15,
        });
    });

    it('initializes with custom values', () => {
        const filter = useFilter({
            initialSearch: 'Laptop',
            initialPage: 2,
            initialPerPage: 25,
            initialSortBy: 'price',
            initialSortDirection: 'desc',
            initialFilters: {
                category_id: 3,
                is_active: true,
            },
        });

        expect(filter.queryParams.value).toEqual({
            category_id: 3,
            is_active: true,
            search: 'Laptop',
            page: 2,
            per_page: 25,
            sort_by: 'price',
            sort_direction: 'desc',
        });
    });

    it('trims search when building query params', () => {
        const filter = useFilter();

        filter.search.value = '  Laptop  ';

        expect(filter.queryParams.value.search).toBe('Laptop');
    });

    it('omits empty search from query params', () => {
        const filter = useFilter();

        filter.search.value = '   ';

        expect(filter.queryParams.value).not.toHaveProperty('search');
    });

    it('includes sorting params only when sortBy is set', () => {
        const filter = useFilter();

        expect(filter.queryParams.value).not.toHaveProperty('sort_by');
        expect(filter.queryParams.value).not.toHaveProperty('sort_direction');

        filter.sortBy.value = 'name';
        filter.sortDirection.value = 'desc';

        expect(filter.queryParams.value).toMatchObject({
            sort_by: 'name',
            sort_direction: 'desc',
        });
    });

    it('resets filters to their initial values', () => {
        const filter = useFilter({
            initialSearch: 'Laptop',
            initialPage: 2,
            initialPerPage: 25,
            initialSortBy: 'price',
            initialSortDirection: 'desc',
            initialFilters: {
                category_id: 3,
            },
        });

        filter.search.value = 'Phone';
        filter.page.value = 5;
        filter.perPage.value = 50;
        filter.sortBy.value = 'name';
        filter.sortDirection.value = 'asc';
        filter.filters.category_id = 7;

        filter.reset();

        expect(filter.queryParams.value).toEqual({
            category_id: 3,
            search: 'Laptop',
            page: 2,
            per_page: 25,
            sort_by: 'price',
            sort_direction: 'desc',
        });
    });

    it('calls onChange when query params change', async () => {
        const onChange = vi.fn();
        const filter = useFilter({ onChange });

        filter.search.value = 'Laptop';

        await nextTick();

        expect(onChange).toHaveBeenCalledWith({
            search: 'Laptop',
            page: 1,
            per_page: 15,
        });
    });
});
