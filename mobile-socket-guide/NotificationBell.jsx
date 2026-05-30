// mobile-socket-guide/NotificationBell.jsx
// React Native component — copy into your mobile app's src/components/

import React from 'react';
import { View, Text, TouchableOpacity, FlatList, StyleSheet } from 'react-native';
import { useNotifications } from '../hooks/useNotifications';

export default function NotificationBell({ userId, role, navigation }) {
    const { notifications, unreadCount, clearAll } = useNotifications(userId, role);
    const [open, setOpen] = React.useState(false);

    return (
        <View>
            {/* Bell icon button */}
            <TouchableOpacity onPress={() => setOpen(!open)} style={styles.bellBtn}>
                <Text style={styles.bellIcon}>🔔</Text>
                {unreadCount > 0 && (
                    <View style={styles.badge}>
                        <Text style={styles.badgeText}>{unreadCount}</Text>
                    </View>
                )}
            </TouchableOpacity>

            {/* Dropdown list */}
            {open && (
                <View style={styles.dropdown}>
                    <View style={styles.dropdownHeader}>
                        <Text style={styles.dropdownTitle}>Notifications</Text>
                        <TouchableOpacity onPress={clearAll}>
                            <Text style={styles.clearBtn}>Clear all</Text>
                        </TouchableOpacity>
                    </View>

                    {notifications.length === 0 ? (
                        <Text style={styles.empty}>No notifications yet</Text>
                    ) : (
                        <FlatList
                            data={notifications}
                            keyExtractor={(_, i) => String(i)}
                            style={{ maxHeight: 300 }}
                            renderItem={({ item }) => (
                                <TouchableOpacity
                                    style={styles.notifItem}
                                    onPress={() => {
                                        setOpen(false);
                                        if (item.orderId) {
                                            // Navigate to order detail screen
                                            navigation.navigate('OrderDetail', { orderId: item.orderId });
                                        }
                                    }}
                                >
                                    <Text style={styles.notifTitle}>{item.title}</Text>
                                    <Text style={styles.notifMsg}>{item.message}</Text>
                                    <Text style={styles.notifTime}>
                                        {new Date(item.timestamp).toLocaleTimeString()}
                                    </Text>
                                </TouchableOpacity>
                            )}
                        />
                    )}
                </View>
            )}
        </View>
    );
}

const styles = StyleSheet.create({
    bellBtn:       { position: 'relative', padding: 8 },
    bellIcon:      { fontSize: 22 },
    badge:         { position: 'absolute', top: 2, right: 2, backgroundColor: '#E91E63', borderRadius: 10, minWidth: 18, height: 18, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 3 },
    badgeText:     { color: '#fff', fontSize: 10, fontWeight: 'bold' },
    dropdown:      { position: 'absolute', top: 44, right: 0, width: 300, backgroundColor: '#fff', borderRadius: 10, shadowColor: '#000', shadowOpacity: 0.15, shadowRadius: 10, elevation: 8, zIndex: 999 },
    dropdownHeader:{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 12, borderBottomWidth: 1, borderBottomColor: '#eee' },
    dropdownTitle: { fontWeight: 'bold', fontSize: 15 },
    clearBtn:      { color: '#E91E63', fontSize: 12 },
    empty:         { textAlign: 'center', color: '#aaa', padding: 20, fontSize: 13 },
    notifItem:     { padding: 12, borderBottomWidth: 1, borderBottomColor: '#f0f0f0' },
    notifTitle:    { fontWeight: '700', fontSize: 13, color: '#222' },
    notifMsg:      { fontSize: 12, color: '#555', marginTop: 2 },
    notifTime:     { fontSize: 11, color: '#aaa', marginTop: 3 },
});
