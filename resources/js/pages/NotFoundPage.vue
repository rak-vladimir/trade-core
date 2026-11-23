<template>
    <section class="flex flex-1 flex-col items-center justify-center gap-4">
        <h1 class="text-6xl font-bold">404</h1>

        <p class="text-lg">
            {{ $t('errors.page_not_found') }}
        </p>

        <RouterLink to="/" class="underline">
            {{ $t('actions.back_to_dashboard') }}
        </RouterLink>
    </section>
</template>
