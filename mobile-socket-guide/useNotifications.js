// mobile-socket-guide/useNotifications.js
// React Native hook — copy into your mobile app's src/hooks/

import { useEffect, useState } from 'react';
import { connectSocket, disconnectSocket } from '../services/NotificationService';

/**
 * Usage in any screen:
 *
 *   const { notifications, unreadCount, clearAll } = useNotifications(userId, role);
 */
export function useNotifications(userId, role) {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount]     = useState(0);

    useEffect(() => {
        if (!userId) return;

        connectSocket(userId, role, (data) => {
            setNotifications(prev => [data, ...prev]);
            setUnreadCount(prev => prev + 1);

            // Show local push notification (requires react-native-push-notification or expo-notifications)
            // PushNotification.localNotification({ title: data.title, message: data.message });
        });

        return () => {
            disconnectSocket();
        };
    }, [userId, role]);

    const clearAll = () => {
        setNotifications([]);
        setUnreadCount(0);
    };

    return { notifications, unreadCount, clearAll };
}
