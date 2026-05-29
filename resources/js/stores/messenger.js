import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useMessengerStore = defineStore('messenger', () => {
    const conversations      = ref([]);
    const activeConversation = ref(null);
    const messages           = ref([]);
    const typingUsers        = ref({});   // { conversationId: [{ user_id, user_name }] }
    const onlineUsers        = ref({});   // { userId: status }
    const notifications      = ref([]);
    const unreadTotal        = ref(0);
    const incomingCall       = ref(null); // { call_uuid, call_type, initiator }
    const activeCall         = ref(null);

    // Conversations triées : épinglées d'abord, puis par last_message_at
    const sortedConversations = computed(() => {
        return [...conversations.value].sort((a, b) => {
            if (a.is_pinned && !b.is_pinned) return -1;
            if (!a.is_pinned && b.is_pinned) return 1;
            return new Date(b.last_message_at) - new Date(a.last_message_at);
        });
    });

    const totalUnread = computed(() =>
        conversations.value.reduce((sum, c) => sum + (c.unread_count || 0), 0)
    );

    function setConversations(list) {
        conversations.value = list;
    }

    function setActiveConversation(conv) {
        activeConversation.value = conv;
        if (conv) {
            const idx = conversations.value.findIndex(c => c.id === conv.id);
            if (idx !== -1) conversations.value[idx].unread_count = 0;
        }
    }

    function setMessages(list) {
        messages.value = list;
    }

    function addMessage(message) {
        // Supprimer le message optimiste temporaire (id commence par 'temp-')
        const tempIndex = messages.value.findIndex(m =>
            String(m.id).startsWith('temp-') &&
            m.sender_id === message.sender_id &&
            m.content === message.content
        );
        if (tempIndex !== -1) {
            messages.value.splice(tempIndex, 1);
        }

        // Éviter les vrais doublons (même id réel)
        if (messages.value.find(m => m.id === message.id && !String(m.id).startsWith('temp-'))) {
            // Mettre à jour si le message existe déjà (édition, réaction...)
            updateMessage(message);
            return;
        }

        const existingIndex = messages.value.findIndex(
            m => m.id === message.id && !String(m.id).startsWith('temp-')
        );

        if (existingIndex !== -1) {
            // Message existant → mettre à jour (edit, delete, reaction)
            messages.value[existingIndex] = { ...messages.value[existingIndex], ...message };
            return;
        }

        messages.value.push(message);

        // Mettre à jour last_message dans la conversation
        const idx = conversations.value.findIndex(c => c.id === message.conversation_id);
        if (idx !== -1) {
            conversations.value[idx].last_message = {
                content:    message.is_deleted ? '🚫 Message supprimé' : (message.content || typeLabel(message.type)),
                sender_name: message.sender?.name,
                created_at: message.created_at,
                sender_id:  message.sender_id,
            };
            conversations.value[idx].last_message_at = message.created_at;

            if (activeConversation.value?.id !== message.conversation_id) {
                conversations.value[idx].unread_count = (conversations.value[idx].unread_count || 0) + 1;
            }
        }
    }

    function updateMessage(updated) {
        const idx = messages.value.findIndex(m => m.id === updated.id);
        if (idx !== -1) messages.value[idx] = { ...messages.value[idx], ...updated };
    }

    function setTyping(conversationId, userId, userName, isTyping) {
        if (!typingUsers.value[conversationId]) typingUsers.value[conversationId] = [];
        const list = typingUsers.value[conversationId];
        if (isTyping) {
            if (!list.find(u => u.user_id === userId)) {
                list.push({ user_id: userId, user_name: userName });
            }
        } else {
            typingUsers.value[conversationId] = list.filter(u => u.user_id !== userId);
        }
    }

    function setUserStatus(userId, status) {
        onlineUsers.value[userId] = status;
        // Mettre à jour dans les conversations
        conversations.value.forEach(c => {
            c.participants?.forEach(p => {
                if (p.id === userId) p.status = status;
            });
        });
    }

    function addNotification(notif) {
        notifications.value.unshift(notif);
        unreadTotal.value++;
    }

    function setIncomingCall(callData) { incomingCall.value = callData; }
    function clearIncomingCall()       { incomingCall.value = null; }
    function setActiveCall(callData)   { activeCall.value = callData; }
    function clearActiveCall()         { activeCall.value = null; }

    function typeLabel(type) {
        return { image:'📷 Photo', video:'🎥 Vidéo', audio:'🎵 Audio', file:'📎 Fichier', sticker:'🎭 Sticker' }[type] || '';
    }

    return {
        conversations, activeConversation, messages, typingUsers,
        onlineUsers, notifications, unreadTotal, incomingCall, activeCall,
        sortedConversations, totalUnread,
        setConversations, setActiveConversation, setMessages,
        addMessage, updateMessage, setTyping, setUserStatus,
        addNotification, setIncomingCall, clearIncomingCall,
        setActiveCall, clearActiveCall,
    };
});
