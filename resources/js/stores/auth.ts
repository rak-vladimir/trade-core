import { defineStore } from 'pinia';
import { ref } from 'vue';

import type { LoginCredentials } from '@/services/auth';
import { getCurrentUser, login as loginUser, logout as logoutUser } from '@/services/auth';
import { ApiError } from '@/services/api-errors';
import type { AuthenticatedUser } from '@/types/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<AuthenticatedUser | null>(null);
    const isInitialized = ref(false);
    const isLoading = ref(false);
    const isAuthenticated = ref(false);

    async function fetchUser(): Promise<void> {
        isLoading.value = true;

        try {
            user.value = await getCurrentUser();
            isAuthenticated.value = true;
        } catch (error: unknown) {
            if (error instanceof ApiError && error.status === 401) {
                user.value = null;
                isAuthenticated.value = false;
            } else {
                throw error;
            }
        } finally {
            isInitialized.value = true;
            isLoading.value = false;
        }
    }

    async function login(credentials: LoginCredentials): Promise<void> {
        isLoading.value = true;

        try {
            user.value = await loginUser(credentials);
            isAuthenticated.value = true;
            isInitialized.value = true;
        } finally {
            isLoading.value = false;
        }
    }

    async function logout(): Promise<void> {
        isLoading.value = true;

        try {
            await logoutUser();

            user.value = null;
            isAuthenticated.value = false;
        } finally {
            isLoading.value = false;
        }
    }

    return {
        user,
        isAuthenticated,
        isInitialized,
        isLoading,
        fetchUser,
        login,
        logout,
    };
});
