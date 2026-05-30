// socket-server.js
// Deployed as a separate Railway service.
// Railway automatically injects PORT — do NOT hardcode it.

const { createServer } = require('http');
const { Server } = require('socket.io');

const httpServer = createServer((req, res) => {
    // Health check endpoint — Railway uses this to verify the service is alive
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', uptime: process.uptime() }));
        return;
    }
    res.writeHead(200);
    res.end('SMSol Socket.io Server');
});

// CORS: allow your Railway Symfony app URL + local dev
// Set ALLOWED_ORIGIN in Railway env vars to your Symfony app URL
// e.g. https://eccom-production.up.railway.app
const allowedOrigins = (process.env.ALLOWED_ORIGIN || 'http://localhost:8000')
    .split(',')
    .map(o => o.trim());

const io = new Server(httpServer, {
    cors: {
        origin: allowedOrigins,
        methods: ['GET', 'POST'],
        credentials: true
    }
});

// Track connected users: userId => socketId
const connectedUsers = new Map();

io.on('connection', (socket) => {
    console.log('🔌 New connection:', socket.id);

    // ── Register user after connect ──────────────────────────────
    socket.on('register', ({ userId, role }) => {
        connectedUsers.set(String(userId), socket.id);
        socket.userId   = String(userId);
        socket.userRole = role;

        socket.join(`role:${role}`);   // role:admin | role:staff | role:user
        socket.join(`user:${userId}`); // personal room

        console.log(`✅ Registered: userId=${userId} role=${role}`);
    });

    // ── New order placed by user/mobile ─────────────────────────
    socket.on('order:new', ({ orderId, orderNumber, customerName, totalAmount }) => {
        io.to('role:admin').to('role:staff').emit('notification', {
            type:        'new_order',
            title:       '🛒 New Order Received',
            message:     `${customerName} placed order #${orderNumber} — ₱${Number(totalAmount).toFixed(2)}`,
            orderId,
            orderNumber,
            timestamp:   new Date().toISOString(),
            sound:       true
        });
        console.log(`📦 New order ${orderNumber} from ${customerName}`);
    });

    // ── Order status changed by admin/staff ──────────────────────
    socket.on('order:status_changed', ({ orderId, orderNumber, customerId, newStatus }) => {
        const statusMessages = {
            accepted:   `✅ Your order #${orderNumber} has been accepted!`,
            processing: `⚙️ Your order #${orderNumber} is now being processed.`,
            completed:  `🎉 Your order #${orderNumber} is completed. Thank you!`,
            declined:   `❌ Your order #${orderNumber} was declined.`,
            cancelled:  `🚫 Your order #${orderNumber} has been cancelled.`,
        };

        io.to(`user:${customerId}`).emit('notification', {
            type:        'order_status',
            title:       '📦 Order Update',
            message:     statusMessages[newStatus] || `Order #${orderNumber} is now ${newStatus}`,
            orderId,
            orderNumber,
            status:      newStatus,
            timestamp:   new Date().toISOString(),
            sound:       true
        });
        console.log(`🔔 Order ${orderNumber} → ${newStatus}, notified user ${customerId}`);
    });

    // ── Disconnect ───────────────────────────────────────────────
    socket.on('disconnect', () => {
        if (socket.userId) {
            connectedUsers.delete(socket.userId);
            console.log(`❌ Disconnected: userId=${socket.userId}`);
        }
    });
});

// Railway injects PORT automatically — always use process.env.PORT
const PORT = process.env.PORT || 3001;
httpServer.listen(PORT, '0.0.0.0', () => {
    console.log(`\n🚀 Socket.io server running on port ${PORT}`);
    console.log(`🌐 Allowed origins: ${allowedOrigins.join(', ')}\n`);
});
