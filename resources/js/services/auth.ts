import axios from 'axios';

import api from '@/services/api';
import type { AuthenticatedUser } from '@/types/api';

export interface LoginCredentials {
    email: string;
    password: string;
}

/**
 * Initialize the CSRF protection.
 */
export async function initializeCsrf(): Promise<void> {
    await axios.get('/sanctum/csrf-cookie', {
        withCredentials: true,
        withXSRFToken: true,
    });
}

/**
 * Authenticate the user.
 */
export async function login(credentials: LoginCredentials): Promise<AuthenticatedUser> {
    await initializeCsrf();

    const { data } = await api.post<AuthenticatedUser>('/login', credentials);

    return data;
}

/**
 * Get the authenticated user.
 */
export async function getCurrentUser(): Promise<AuthenticatedUser> {
    const { data } = await api.get<AuthenticatedUser>('/user');

    return data;
}

/**
 * Log out the authenticated user.
 */
export async function logout(): Promise<void> {
    await api.post('/logout');
}
