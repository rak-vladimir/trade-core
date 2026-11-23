/**
 * Laravel validation errors.
 *
 * Example:
 * {
 *     email: ['The email field is required.'],
 *     'items.0.quantity': ['The quantity must be greater than 0.']
 * }
 */
export type ValidationErrors = Record<string, string[]>;

/**
 * Standard Laravel JSON validation error response.
 */
export interface ApiErrorResponse {
    message: string;
    errors?: ValidationErrors;
}
