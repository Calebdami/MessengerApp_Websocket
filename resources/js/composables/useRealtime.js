import { onUnmounted } from 'vue';
import { useMessengerStore } from '@/stores/messenger.js';

export function useRealtime(authUserId) {
    const store    = useMessengerStore();
    const channels = new Map();

    /** Abonnement au canal de présence (statuts online) */
    function subscribePresence() {
        if (!window.Echo) return;
        const ch = window.Echo.channel('presence');
        ch.listen('.user.status', (e) => {
            store.setUserStatus(e.user_id, e.status);
        });
        channels.set('presence', ch);
    }

    /** Abonnement aux notifications personnelles */
    function subscribeNotifications(userId) {
        if (!window.Echo) return;
        const ch = window.Echo.channel(`notifications.${userId}`);
        ch.listen('.App\\Notifications\\NewMessageNotification', (e) => {
            store.addNotification(e);
            // Émettre un event global pour le toast
            window.dispatchEvent(new CustomEvent('messenger:notification', { detail: e }));
        });
        channels.set(`notifications.${userId}`, ch);
    }

    /** Abonnement à une conversation */
    function subscribeConversation(conversationId) {
        if (!window.Echo || channels.has(`conv.${conversationId}`)) return;

        const ch = window.Echo.channel(`conversation.${conversationId}`);

        ch.listen('.message.sent', (e) => {
            store.addMessage(e.message);
            // Arrêter le typing de l'expéditeur
            store.setTyping(conversationId, e.message.sender_id, '', false);
        });

        ch.listen('.user.typing', (e) => {
            if (e.user_id === authUserId) return;
            store.setTyping(conversationId, e.user_id, e.user_name, e.is_typing);
            // Auto-stop après 5s
            if (e.is_typing) {
                setTimeout(() => store.setTyping(conversationId, e.user_id, '', false), 5000);
            }
        });

        ch.listen('.message.read', (e) => {
            // Marquer les messages comme lus visuellement
            store.messages.forEach(m => {
                if (m.conversation_id === conversationId) {
                    if (!m.reads) m.reads = [];
                    if (!m.reads.find(r => r.user_id === e.user_id)) {
                        m.reads.push({ user_id: e.user_id, read_at: e.read_at });
                    }
                }
            });
        });

        ch.listen('.call.signal', (e) => {
            if (e.initiated_by !== authUserId && e.call_status === 'ringing') {
                store.setIncomingCall(e);
            } else if (e.call_status === 'ended' || e.call_status === 'declined') {
                store.clearIncomingCall();
                store.clearActiveCall();
            }
        });

        channels.set(`conv.${conversationId}`, ch);
    }

    function unsubscribeConversation(conversationId) {
        const key = `conv.${conversationId}`;
        if (window.Echo && channels.has(key)) {
            window.Echo.leave(`conversation.${conversationId}`);
            channels.delete(key);
        }
    }

    function unsubscribeAll() {
        if (!window.Echo) return;
        channels.forEach((_, key) => {
            if (key.startsWith('conv.')) {
                window.Echo.leave(`conversation.${key.slice(5)}`);
            } else if (key === 'presence') {
                window.Echo.leave('presence');
            } else if (key.startsWith('notifications.')) {
                window.Echo.leave(key.replace('notifications.', 'notifications.'));
            }
        });
        channels.clear();
    }

    onUnmounted(unsubscribeAll);

    return { subscribePresence, subscribeNotifications, subscribeConversation, unsubscribeConversation, unsubscribeAll };
}
