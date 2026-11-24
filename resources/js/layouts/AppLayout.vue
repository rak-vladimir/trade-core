<script setup lang="ts">
import { computed } from 'vue';
import i18n from '@/i18n';

type Locale = 'ru' | 'en';

const currentLocale = computed(() => i18n.global.locale.value);

function setLocale(locale: Locale): void {
    i18n.global.locale.value = locale;
    localStorage.setItem('trade-core:locale', locale);
}
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-gray-50 text-gray-900">
        <header class="shrink-0 border-b border-gray-200 bg-white">
            <nav class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-6">
                <span class="text-xl font-semibold tracking-tight">
                    TradeCore
                </span>

                <div
                    class="flex items-center gap-1"
                    role="group"
                    :aria-label="$t('common.language')"
                >
                    <span class="mr-2 text-sm text-gray-500">
                        {{ $t('common.language') }}
                    </span>

                    <button
                        v-for="locale in ['ru', 'en'] as const"
                        :key="locale"
                        type="button"
                        class="rounded px-3 py-1.5 text-sm font-medium transition-colors cursor-pointer"
                        :class="currentLocale === locale
                            ? 'bg-gray-900 text-white'
                            : 'text-gray-600 hover:bg-gray-100'"
                        :aria-pressed="currentLocale === locale"
                        @click="setLocale(locale)"
                    >
                        {{ locale.toUpperCase() }}
                    </button>
                </div>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-7xl flex-1 flex-col px-6 py-8"
        >
            <RouterView/>
        </main>
    </div>
</template>
