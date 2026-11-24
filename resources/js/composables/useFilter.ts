import { computed, reactive, ref, watch } from 'vue';

export type SortDirection = 'asc' | 'desc';
export type FilterParams = Record<string, unknown>;

interface UseFilterOptions<TFilters extends FilterParams> {
    initialFilters?: TFilters;
    initialSearch?: string;
    initialPage?: number;
    initialPerPage?: number;
    initialSortBy?: string | null;
    initialSortDirection?: SortDirection;
    onChange?: (params: FilterParams) => void;
}

export function useFilter<TFilters extends FilterParams = FilterParams>(options: UseFilterOptions<TFilters> = {}) {
    const search = ref(options.initialSearch ?? '');
    const page = ref(options.initialPage ?? 1);
    const perPage = ref(options.initialPerPage ?? 15);
    const sortBy = ref<string | null>(options.initialSortBy ?? null);
    const sortDirection = ref<SortDirection>(options.initialSortDirection ?? 'asc');

    const filters = reactive({
        ...options.initialFilters,
    }) as TFilters;

    const queryParams = computed<FilterParams>(() => ({
        ...filters,
        ...(search.value.trim() ? { search: search.value.trim() } : {}),
        page: page.value,
        per_page: perPage.value,
        ...(sortBy.value
            ? {
                  sort_by: sortBy.value,
                  sort_direction: sortDirection.value,
              }
            : {}),
    }));

    function reset(): void {
        search.value = options.initialSearch ?? '';
        page.value = options.initialPage ?? 1;
        perPage.value = options.initialPerPage ?? 15;
        sortBy.value = options.initialSortBy ?? null;
        sortDirection.value = options.initialSortDirection ?? 'asc';

        Object.assign(filters, options.initialFilters ?? {});
    }

    watch(queryParams, (params) => options.onChange?.(params), { deep: true });

    return {
        search,
        page,
        perPage,
        sortBy,
        sortDirection,
        filters,
        queryParams,
        reset,
    };
}
