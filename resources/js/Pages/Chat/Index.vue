<template>
  <div class="flex h-screen bg-gray-950 overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-80 flex-shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col">
      <!-- Header -->
      <div class="p-4 border-b border-gray-800 flex items-center justify-between">
        <h1 class="text-xl font-bold text-white">💬 Messenger</h1>
        <div class="flex items-center gap-2">
          <button @click="showNewChat = true"
            class="w-9 h-9 bg-indigo-600 hover:bg-indigo-500 rounded-full flex items-center justify-center text-white transition text-lg" title="Nouvelle conversation">
            ✏️
          </button>
          <div class="relative">
            <button @click="showNotifications = !showNotifications"
              class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-full flex items-center justify-center transition">
              🔔
            </button>
            <span v-if="store.totalUnread > 0"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">
              {{ store.totalUnread > 9 ? '9+' : store.totalUnread }}
            </span>
          </div>
        </div>
      </div>

      <!-- Search -->
      <div class="p-3 border-b border-gray-800">
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
          <input v-model="search" type="text" placeholder="Rechercher…"
            class="w-full bg-gray-800 rounded-xl pl-9 pr-4 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
        </div>
      </div>

      <!-- User profile mini -->
      <div class="p-3 border-b border-gray-800 flex items-center gap-3">
        <div class="relative">
          <img :src="authUser.avatar_url" class="w-10 h-10 rounded-full object-cover" />
          <span class="status-dot online absolute bottom-0 right-0"></span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-white truncate">{{ authUser.name }}</p>
          <p class="text-xs text-gray-400">@{{ authUser.username }}</p>
        </div>
        <a href="/settings" class="text-gray-400 hover:text-white transition">⚙️</a>
      </div>

      <!-- Conversations list -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="filteredConversations.length === 0" class="p-8 text-center text-gray-500">
          <div class="text-4xl mb-3">💬</div>
          <p class="text-sm">Aucune conversation</p>
          <button @click="showNewChat = true" class="mt-3 text-indigo-400 text-sm hover:text-indigo-300">
            Démarrer une conversation
          </button>
        </div>

        <div v-for="conv in filteredConversations" :key="conv.id"
          @click="openConversation(conv)"
          :class="['flex items-center gap-3 px-3 py-3 cursor-pointer transition hover:bg-gray-800 border-b border-gray-800/50 group', { 'bg-gray-800': conv.is_pinned }]">

          <!-- Avatar -->
          <div class="relative flex-shrink-0">
            <img :src="conv.avatar" class="w-12 h-12 rounded-full object-cover" />
            <span v-if="conv.type === 'direct'" :class="['status-dot absolute bottom-0 right-0', getStatus(conv)]"></span>
            <span v-if="conv.is_pinned" class="absolute -top-1 -right-1 text-xs">📌</span>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-0.5">
              <p class="font-semibold text-white text-sm truncate">{{ conv.name }}</p>
              <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                {{ formatTime(conv.last_message_at) }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <p class="text-xs text-gray-400 truncate flex items-center gap-1">
                <span v-if="conv.is_muted">🔇</span>
                <span v-if="conv.last_message?.sender_id === authUser.id" class="text-gray-500">Vous: </span>
                {{ conv.last_message?.content || 'Démarrer la conversation' }}
              </p>
              <span v-if="conv.unread_count > 0"
                class="bg-indigo-600 text-white text-xs rounded-full px-1.5 py-0.5 ml-2 flex-shrink-0 min-w-[18px] text-center">
                {{ conv.unread_count }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Logout -->
      <div class="p-3 border-t border-gray-800">
        <button @click="logout"
          class="w-full text-gray-400 hover:text-red-400 text-sm py-2 transition flex items-center justify-center gap-2">
          🚪 Se déconnecter
        </button>
      </div>
    </aside>

    <!-- Empty state -->
    <main class="flex-1 flex items-center justify-center bg-gray-950">
      <div class="text-center text-gray-500">
        <div class="text-8xl mb-4">💬</div>
        <h2 class="text-2xl font-semibold text-gray-400 mb-2">Bienvenue sur Messenger</h2>
        <p class="text-gray-500">Sélectionnez une conversation ou démarrez-en une nouvelle</p>
        <button @click="showNewChat = true"
          class="mt-6 bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-xl font-semibold transition">
          Nouvelle conversation
        </button>
      </div>
    </main>

    <!-- Modal nouvelle conversation -->
    <NewChatModal v-if="showNewChat" @close="showNewChat = false" @created="showNewChat = false" />

    <!-- Panneau notifications -->
    <div v-if="showNotifications"
      class="fixed right-4 top-16 w-80 bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl z-50 overflow-hidden">
      <div class="p-4 border-b border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-white">Notifications</h3>
        <button @click="showNotifications = false" class="text-gray-400 hover:text-white">✕</button>
      </div>
      <div class="max-h-96 overflow-y-auto">
        <div v-if="store.notifications.length === 0" class="p-6 text-center text-gray-500 text-sm">
          Aucune notification
        </div>
        <div v-for="n in store.notifications" :key="n.id"
          class="p-3 border-b border-gray-800 hover:bg-gray-800 cursor-pointer flex items-center gap-3">
          <img :src="n.sender_avatar" class="w-10 h-10 rounded-full object-cover" />
          <div>
            <p class="text-sm font-medium text-white">{{ n.sender_name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ n.preview }}</p>
          </div>
        </div>
      </div>
    </div>
    <ToastNotification ref="toast" />
    <ConfirmModal ref="confirm" />
  </div>
</template>

<script setup>
import ToastNotification from '@/Components/UI/ToastNotification.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';
import { useTheme } from '@/composables/useTheme.js';
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useMessengerStore } from '@/stores/messenger.js';
import { useRealtime } from '@/composables/useRealtime.js';
import NewChatModal from '@/Components/Chat/NewChatModal.vue';

const toast   = ref(null);
const confirm = ref(null);
const { theme, setTheme } = useTheme();

const props = defineProps(['conversations', 'authUser', 'archivedCount']);
const store = useMessengerStore();
const { subscribePresence, subscribeNotifications } = useRealtime(props.authUser.id);

const search = ref('');
const showNewChat = ref(false);
const showNotifications = ref(false);

onMounted(() => {
  store.setConversations(props.conversations);
  subscribePresence();
  subscribeNotifications(props.authUser.id);
  if ('Notification' in window) Notification.requestPermission();
});

const filteredConversations = computed(() => {
  const q = search.value.toLowerCase();
  return store.sortedConversations.filter(c =>
    !q || c.name?.toLowerCase().includes(q)
  );
});

function openConversation(conv) {
  router.visit(`/conversations/${conv.id}`);
}

function getStatus(conv) {
  const other = conv.participants?.find(p => p.id !== props.authUser.id);
  return other?.status || 'offline';
}

function formatTime(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  const now = new Date();
  const diff = now - d;
  if (diff < 60000)     return 'maintenant';
  if (diff < 3600000)   return Math.floor(diff / 60000) + 'm';
  if (diff < 86400000)  return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
}

async function logout() {
  const ok = await confirm.value.open({
    icon:         '🚪',
    title:        'Se déconnecter',
    message:      'Êtes-vous sûr de vouloir vous déconnecter ?',
    variant:      'danger',
    confirmLabel: 'Se déconnecter',
    cancelLabel:  'Annuler',
  });
  if (ok) router.post('/logout');
}
</script>
