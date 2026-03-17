/**
 * Application Configuration
 * Environment variables can be set during build or at runtime
 */

// Authentication API Base URL
export const AUTH_BASE = process.env.AUTH_API_URL || 'http://127.0.0.1:8000/api';

// Application environment (development, production, testing)
export const ENVIRONMENT = process.env.NODE_ENV || 'development';

// Enable debugging in development
export const DEBUG = ENVIRONMENT === 'development';

// API endpoints
export const API_ENDPOINTS = {
    LOGIN: '/login',
    REGISTER: '/register',
    LOGOUT: '/logout'
};

export default {
    AUTH_BASE,
    ENVIRONMENT,
    DEBUG,
    API_ENDPOINTS
};
