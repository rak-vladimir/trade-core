<script setup lang="ts">
import { useRouter } from 'vue-router';
import { computed, ref } from 'vue';

import { useAuthStore } from '@/stores/auth';
import { useForm } from '@/composables/useForm';
import { ApiError } from '@/services/api-errors';

interface LoginForm {
    email: string;
    password: string;
}

const router = useRouter();
const auth = useAuthStore();
const generalError = ref('');
const sessionCheckFailed = computed(() => router.currentRoute.value.query.authCheckFailed === '1');

const form = useForm<LoginForm>({
    email: '',
    password: '',
});

async function submit(): Promise<void> {
    generalError.value = '';
    form.clearErrors();

    try {
        await auth.login(form.data);

        const redirect = router.currentRoute.value.query.redirect;

        await router.replace(typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : { name: 'dashboard' });
    } catch (error: unknown) {
        form.data.password = '';

        if (error instanceof ApiError && error.hasValidationErrors) {
            Object.assign(form.errors, error.errors);
            return;
        }

        generalError.value = error instanceof ApiError && error.status === undefined ? 'network' : 'credentials';
    }
}
</script>

<template>
    <section class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
        <div class="card">
            <h1 class="text-2xl font-semibold">
                {{ $t('auth.login.title') }}
            </h1>

            <p class="mt-2 text-sm text-gray-500">
                {{ $t('auth.login.description') }}
            </p>

            <div v-if="generalError" role="alert" class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $t(`auth.login.errors.${generalError}`) }}
            </div>

            <div v-if="sessionCheckFailed" class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $t('auth.login.errors.sessionCheck') }}
            </div>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <div>
                    <label for="email" class="label">
                        {{ $t('auth.login.email') }}
                    </label>

                    <input
                        id="email"
                        v-model="form.data.email"
                        type="email"
                        autocomplete="username"
                        required
                        class="input"
                        :class="'input' + (form.errors.email ? ' input-error' : '')"
                    />

                    <p v-if="form.errors.email" class="field-error">
                        {{ form.errors.email[0] }}
                    </p>
                </div>

                <div>
                    <label for="password" class="label">
                        {{ $t('auth.login.password') }}
                    </label>

                    <input id="password" v-model="form.data.password" type="password" autocomplete="current-password" required class="input" />

                    <p v-if="form.errors.password" class="field-error">
                        {{ form.errors.password[0] }}
                    </p>
                </div>

                <button type="submit" :disabled="auth.isLoading" class="btn btn-primary w-full">
                    {{ auth.isLoading ? $t('common.loading') : $t('auth.login.submit') }}
                </button>
            </form>
        </div>
    </section>
</template>
