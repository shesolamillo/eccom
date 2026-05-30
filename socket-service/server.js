// socket-service/server.js
// This is the file Railway will run: node server.js

const { createServer } = require('http');
const { Server } = require('socket.io');

const httpServer = createServer((req, res) => {
    // Health check — Railway pings this to confirm the service is up
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', uptime: process.uptime() }));
        return;
    }
    res.writeHead(200);
    res.end('SMSol Socket.io Server is running.');
});

// ALLOWED_ORIGIN env var — set this in Railway to your Symfony app URL
// Multiple origins: comma-separated, e.g. "https://app.up.railway.app,https://myapp.com"
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

const connectedUsers = new Map();

io.on('connection', (socket) => {
    console.log('🔌 Connected:', socket.id);

    socket.on('register', ({ userId, role }) => {
        connectedUsers.set(String(userId), socket.id);
        socket.userId   = String(userId);
        socket.userRole = role;
        socket.join(`role:${role}`);
        socket.join(`user:${userId}`);
        console.log(`✅ userId=${userId} role=${role}`);
    });

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
        console.log(`📦 New order: ${orderNumber}`);
    });

    socket.on('order:status_changed', ({ orderId, orderNumber, customerId, newStatus }) => {
        const msgs = {
            accepted:   `✅ Your order #${orderNumber} has been accepted!`,
            processing: `⚙️ Your order #${orderNumber} is now being processed.`,
            completed:  `🎉 Your order #${orderNumber} is completed. Thank you!`,
            declined:   `❌ Your order #${orderNumber} was declined.`,
            cancelled:  `🚫 Your order #${orderNumber} has been cancelled.`,
        };
        io.to(`user:${customerId}`).emit('notification', {
            type:        'order_status',
            title:       '📦 Order Update',
            message:     msgs[newStatus] || `Order #${orderNumber} is now ${newStatus}`,
            orderId,
            orderNumber,
            status:      newStatus,
            timestamp:   new Date().toISOString(),
            sound:       true
        });
        console.log(`🔔 ${orderNumber} → ${newStatus} → user ${customerId}`);
    });

    socket.on('disconnect', () => {
        if (socket.userId) connectedUsers.delete(socket.userId);
        console.log('❌ Disconnected:', socket.id);
    });
});

// Railway always provides PORT — never hardcode
const PORT = process.env.PORT || 3001;
httpServer.listen(PORT, '0.0.0.0', () => {
    console.log(`\n🚀 Socket.io server on port ${PORT}`);
    console.log(`🌐 Allowed origins: ${allowedOrigins.join(', ')}\n`);
});
