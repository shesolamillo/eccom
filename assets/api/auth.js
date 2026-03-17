// API Base URL - can be overridden by environment variable or config
const BASE_URL = window.AUTH_API_URL || 'http://127.0.0.1:8000/api';

/**
 * Login user with email and password
 * @param {Object} credentials - { email, password }
 * @returns {Promise<Object>} User data if successful
 * @throws {Error} If login fails
 */
export async function authLogin(credentials) {
    const response = await fetch(`${BASE_URL}/login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include', // Include cookies for session management
        body: JSON.stringify(credentials)
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Login failed');
    }

    return await response.json();
}

/**
 * Register a new user
 * @param {Object} credentials - { email, password, name }
 * @returns {Promise<Object>} User data if successful
 * @throws {Error} If registration fails
 */
export async function authRegister(credentials) {
    const response = await fetch(`${BASE_URL}/register`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(credentials)
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Registration failed');
    }

    return await response.json();
}

/**
 * Logout user (clears session)
 * @returns {Promise<void>}
 */
export async function authLogout() {
    // Sessions are automatically cleared by server on logout
    // You can add additional cleanup here if needed
}

export default {
    authLogin,
    authRegister,
    authLogout
};
