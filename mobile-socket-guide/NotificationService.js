// mobile-socket-guide/NotificationService.js
// React Native — copy this file into your mobile app's src/services/

import { io } from 'socket.io-client';

const SOCKET_URL = process.env.SOCKET_URL || 'https://your-socket-service.up.railway.app'; // ← your Railway socket service URL

let socket = null;

/**
 * Connect and register the mobile user.
 * Call this after login, passing the userId and role.
 *
 * @param {string|number} userId
 * @param {string} role  — 'user' | 'admin' | 'staff'
 * @param {function} onNotification  — callback(data) when notification arrives
 */
export function connectSocket(userId, role, onNotification) {
    if (socket && socket.connected) return;

    socket = io(SOCKET_URL, {
        transports: ['websocket'],
        reconnectionAttempts: 10,
        reconnectionDelay: 3000,
    });

    socket.on('connect', () => {
        console.log('📱 Socket connected:', socket.id);
        socket.emit('register', { userId: String(userId), role });
    });

    socket.on('notification', (data) => {
        console.log('🔔 Notification received:', data);
        if (typeof onNotification === 'function') {
            onNotification(data);
        }
    });

    socket.on('disconnect', () => {
        console.log('❌ Socket disconnected');
    });
}

/** Disconnect socket (call on logout) */
export function disconnectSocket() {
    if (socket) {
        socket.disconnect();
        socket = null;
    }
}

/** Emit new order event (call after successful order placement) */
export function emitNewOrder({ orderId, orderNumber, customerName, totalAmount }) {
    if (!socket) return;
    socket.emit('order:new', { orderId, orderNumber, customerName, totalAmount });
}

/** Emit order status changed (admin/staff mobile) */
export function emitOrderStatusChanged({ orderId, orderNumber, customerId, newStatus }) {
    if (!socket) return;
    socket.emit('order:status_changed', { orderId, orderNumber, customerId, newStatus });
}
