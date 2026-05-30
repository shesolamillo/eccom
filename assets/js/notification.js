// assets/js/notification.js
// Handles Socket.io real-time notifications for web (admin + user)

import { io } from 'socket.io-client';

const SOCKET_URL = 'http://localhost:3001';

let socket = null;

// ── Connect & Register ────────────────────────────────────────────
export function initNotifications(userId, userRole) {
    if (!userId) return;

    socket = io(SOCKET_URL, {
        transports: ['websocket', 'polling'],
        reconnectionAttempts: 5,
        reconnectionDelay: 2000,
    });

    socket.on('connect', () => {
        console.log('🔌 Socket connected:', socket.id);
        socket.emit('register', { userId, role: userRole });
    });

    socket.on('disconnect', () => {
        console.log('❌ Socket disconnected');
    });

    // Listen for incoming notifications
    socket.on('notification', (data) => {
        showToastNotification(data);
        incrementBadge();
        addToNotificationList(data);
    });
}

// ── Emit: new order placed (call after successful checkout) ───────
export function emitNewOrder(orderId, orderNumber, customerName, totalAmount) {
    if (!socket) return;
    socket.emit('order:new', { orderId, orderNumber, customerName, totalAmount });
}

// ── Emit: order status changed (call after admin updates status) ──
export function emitOrderStatusChanged(orderId, orderNumber, customerId, newStatus) {
    if (!socket) return;
    socket.emit('order:status_changed', { orderId, orderNumber, customerId, newStatus });
}

// ── Toast Notification ────────────────────────────────────────────
function showToastNotification(data) {
    // Create toast container if not exists
    let container = document.getElementById('toast-notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 360px;
        `;
        document.body.appendChild(container);
    }

    const iconMap = {
        new_order:    '🛒',
        order_status: '📦',
    };

    const colorMap = {
        new_order:    '#E91E63',
        order_status: '#1976D2',
    };

    const toast = document.createElement('div');
    toast.style.cssText = `
        background: #fff;
        border-left: 5px solid ${colorMap[data.type] || '#E91E63'};
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        padding: 14px 18px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: slideInRight 0.3s ease;
        cursor: pointer;
    `;

    toast.innerHTML = `
        <span style="font-size:1.5rem; line-height:1;">${iconMap[data.type] || '🔔'}</span>
        <div style="flex:1;">
            <div style="font-weight:700; font-size:0.9rem; color:#222; margin-bottom:3px;">${data.title}</div>
            <div style="font-size:0.82rem; color:#555;">${data.message}</div>
            <div style="font-size:0.75rem; color:#aaa; margin-top:4px;">${new Date(data.timestamp).toLocaleTimeString()}</div>
        </div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;font-size:1rem;cursor:pointer;color:#aaa;padding:0;line-height:1;">✕</button>
    `;

    // Click to go to order
    if (data.orderId) {
        toast.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                const role = document.body.dataset.userRole;
                if (role === 'admin' || role === 'staff') {
                    window.location.href = `/admin/orders/${data.orderId}/manage`;
                } else {
                    window.location.href = `/order/${data.orderId}`;
                }
            }
        });
    }

    container.appendChild(toast);

    // Play sound
    if (data.sound) playNotificationSound();

    // Auto remove after 6 seconds
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 6000);
}

// ── Badge counter ─────────────────────────────────────────────────
function incrementBadge() {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        const current = parseInt(badge.textContent) || 0;
        badge.textContent = current + 1;
        badge.style.display = 'inline-flex';
    }
}

// ── Add to dropdown list ──────────────────────────────────────────
function addToNotificationList(data) {
    const list = document.getElementById('notification-list');
    if (!list) return;

    // Remove "no notifications" placeholder
    const empty = list.querySelector('.notification-empty');
    if (empty) empty.remove();

    const item = document.createElement('li');
    item.innerHTML = `
        <a class="dropdown-item py-2 px-3 border-bottom" href="${data.orderId ? (document.body.dataset.userRole === 'admin' || document.body.dataset.userRole === 'staff' ? '/admin/orders/' + data.orderId + '/manage' : '/order/' + data.orderId) : '#'}">
            <div class="fw-semibold" style="font-size:0.85rem;">${data.title}</div>
            <div class="text-muted" style="font-size:0.78rem;">${data.message}</div>
            <div class="text-muted" style="font-size:0.72rem;">${new Date(data.timestamp).toLocaleTimeString()}</div>
        </a>
    `;
    list.prepend(item);
}

// ── Notification sound ────────────────────────────────────────────
function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        oscillator.frequency.setValueAtTime(880, ctx.currentTime);
        oscillator.frequency.setValueAtTime(660, ctx.currentTime + 0.1);
        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.4);
    } catch (e) {
        // Audio not available, skip
    }
}

// ── CSS Animations ────────────────────────────────────────────────
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(120%); opacity: 0; }
        to   { transform: translateX(0);    opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to   { opacity: 0; transform: translateX(120%); }
    }
`;
document.head.appendChild(style);
