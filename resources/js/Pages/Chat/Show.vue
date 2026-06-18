<template>
  <div class="flex h-screen bg-gray-950 overflow-hidden">
    <!-- Backdrop mobile -->
    <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 md:hidden"></div>

    <!-- ── Sidebar conversations ──────────────────────────────────── -->
    <aside :class="['fixed md:static inset-y-0 left-0 w-80 flex-shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col transition-transform duration-300 z-50 md:z-auto', sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0']">
      <div class="p-4 border-b border-gray-800 flex items-center justify-between">
        <a href="/conversations" class="text-white font-bold text-lg flex items-center gap-2">💬 Messenger</a>
        <button @click="showNewChat = true"
          class="w-9 h-9 bg-indigo-600 hover:bg-indigo-500 rounded-full flex items-center justify-center text-white text-lg transition">✏️</button>
      </div>
      <div class="p-3 border-b border-gray-800">
        <input v-model="sidebarSearch" placeholder="Rechercher…"
          class="w-full bg-gray-800 rounded-xl px-4 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
      </div>
      <div class="flex-1 overflow-y-auto">
        <div v-for="conv in filteredSidebar" :key="conv.id"
          @click="sidebarOpen = false; router.visit(`/conversations/${conv.id}`)"
          :class="['flex items-center gap-3 px-3 py-3 cursor-pointer transition hover:bg-gray-800 border-b border-gray-800/50',
            { 'bg-indigo-900/30 border-l-2 border-l-indigo-500': conv.id === conversation.id }]">
          <img :src="conv.avatar" class="w-11 h-11 rounded-full object-cover flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <div class="flex justify-between">
              <p class="font-semibold text-white text-sm truncate">{{ conv.name }}</p>
              <span class="text-xs text-gray-500">{{ formatTime(conv.last_message_at) }}</span>
            </div>
            <p class="text-xs text-gray-400 truncate">{{ conv.last_message?.content || '…' }}</p>
          </div>
          <span v-if="conv.unread_count > 0" class="bg-indigo-600 text-white text-xs rounded-full px-1.5 py-0.5 flex-shrink-0">
            {{ conv.unread_count }}
          </span>
        </div>
      </div>
    </aside>

    <!-- ── Zone principale ──────────────────────────────────────────── -->
    <main class="flex-1 flex flex-col min-w-0">

      <!-- Header conversation -->
      <header class="flex items-center gap-3 px-4 py-3 bg-gray-900 border-b border-gray-800 flex-shrink-0">
        <!-- Toggle button mobile -->
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-lg flex items-center justify-center text-white transition flex-shrink-0" title="Menu">
          ☰
        </button>
        <img :src="conversation.avatar" class="w-10 h-10 rounded-full object-cover" />
        <div class="flex-1">
          <h2 class="font-semibold text-white">{{ conversation.name }}</h2>
          <p class="text-xs text-gray-400">
            <span v-if="typingText">{{ typingText }}</span>
            <span v-else-if="conversation.type === 'direct'">
              {{ otherParticipant?.status === 'online' ? '🟢 En ligne' : otherParticipant?.last_seen }}
            </span>
            <span v-else>{{ participants.length }} membres</span>
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="startCall('audio')" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-full flex items-center justify-center transition text-gray-300 hover:text-white" title="Appel audio">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </button>
          <button @click="startCall('video')" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-full flex items-center justify-center transition text-gray-300 hover:text-white" title="Appel vidéo">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
          </button>
          <button @click="showInfo = !showInfo" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-full flex items-center justify-center transition text-gray-300 hover:text-white" title="Infos">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          </button>
        </div>
      </header>

      <!-- Messages -->
      <div ref="messagesEl" class="flex-1 overflow-y-auto p-4 space-y-1" @scroll="onScroll">
        <!-- Charger plus -->
        <div v-if="hasMore" class="flex justify-center pb-2">
          <button @click="loadMore" :disabled="loadingMore"
            class="text-indigo-400 hover:text-indigo-300 text-sm disabled:opacity-50">
            {{ loadingMore ? 'Chargement…' : '⬆️ Messages précédents' }}
          </button>
        </div>

        <!-- Groupe de messages par jour -->
        <template v-for="group in groupedMessages" :key="group.date">
          <div class="flex justify-center my-3">
            <span class="bg-gray-800 text-gray-400 text-xs px-3 py-1 rounded-full">{{ group.date }}</span>
          </div>
          <MessageBubble
            v-for="msg in group.messages" :key="msg.id"
            :message="msg"
            :is-mine="msg.sender_id === authUser.id"
            :auth-user="authUser"
            @reply="setReply"
            @react="react"
            @edit="editMessage"
            @delete="deleteMessage"
          />
        </template>

        <!-- Typing indicator -->
        <div v-if="currentTyping.length > 0" class="flex items-center gap-2 px-2 py-1">
          <img v-if="currentTyping[0]" :src="`https://ui-avatars.com/api/?name=${currentTyping[0].user_name}&size=28`"
            class="w-7 h-7 rounded-full" />
          <div class="bubble-received px-4 py-2 flex items-center gap-1">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
          </div>
        </div>
        <!-- Indicateurs d'upload en cours -->
        <UploadingBubble
          v-for="upload in uploadingFiles"
          :key="upload.id"
          :type="upload.type"
          :file-name="upload.fileName"
          :progress="upload.progress"
        />
        <div ref="bottomEl"></div>
      </div>

      <!-- Reply preview -->
      <div v-if="replyTo" class="px-4 py-2 bg-gray-800/50 border-t border-gray-700 flex items-center gap-3">
        <div class="flex-1 border-l-2 border-indigo-500 pl-3">
          <p class="text-xs text-indigo-400 font-medium">{{ replyTo.sender?.name }}</p>
          <p class="text-xs text-gray-400 truncate">{{ replyTo.content || typeLabel(replyTo.type) }}</p>
        </div>
        <button @click="replyTo = null" class="text-gray-400 hover:text-white">✕</button>
      </div>

      <!-- Input area -->
      <div class="px-4 py-3 bg-gray-900 border-t border-gray-800 flex-shrink-0">
        <!-- Desktop layout -->
        <div class="hidden md:flex items-end gap-3">

          <!-- Emoji -->
          <div class="relative">
            <button @click="showEmoji = !showEmoji"
              class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-white text-xl transition">😊</button>
            <div v-if="showEmoji" class="absolute bottom-12 left-0 z-50">
              <emoji-picker @emoji-click="onEmojiClick"></emoji-picker>
            </div>
          </div>

          <!-- Stickers -->
          <div class="relative">
            <button @click="showStickers = !showStickers"
              class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-white text-xl transition">🎭</button>
            <div v-if="showStickers" class="absolute bottom-12 left-0 z-50 bg-gray-800 rounded-2xl p-3 grid grid-cols-5 gap-2 w-64 shadow-xl">
              <button v-for="s in stickers" :key="s" @click="sendSticker(s)"
                class="text-2xl hover:scale-125 transition">{{ s }}</button>
            </div>
          </div>

          <!-- Textarea -->
          <div class="flex-1 relative">
            <textarea
              ref="textareaEl"
              v-model="newMessage"
              @keydown="onKeydown"
              @input="onInput"
              rows="1"
              placeholder="Aa…"
              class="w-full bg-gray-800 border border-gray-700 rounded-2xl px-4 py-3 pr-12 text-white placeholder-gray-500 resize-none focus:outline-none focus:border-indigo-500 transition"
              style="max-height: 120px; overflow-y: auto;"
            ></textarea>
          </div>

          <!-- Attachments -->
          <div class="flex items-center gap-1">
            <label class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-white text-xl cursor-pointer transition" title="Image">
              🖼️<input type="file" accept="image/*" class="hidden" @change="(e) => sendFile(e, 'image')" />
            </label>
            <label class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-white text-xl cursor-pointer transition" title="Vidéo">
              🎥<input type="file" accept="video/*" class="hidden" @change="(e) => sendFile(e, 'video')" />
            </label>
            <label class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-white text-xl cursor-pointer transition" title="Audio">
              🎵<input type="file" accept="audio/*" class="hidden" @change="(e) => sendFile(e, 'audio')" />
            </label>
            <label class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-white text-xl cursor-pointer transition" title="Fichier">
              📎<input type="file" class="hidden" @change="(e) => sendFile(e, 'file')" />
            </label>
          </div>

          <!-- Send -->
          <button @click="sendMessage" :disabled="!newMessage.trim() && !sending"
            class="w-10 h-10 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 rounded-full flex items-center justify-center text-white text-lg transition flex-shrink-0">
            ➤
          </button>
        </div>

        <!-- Mobile layout -->
        <div class="md:hidden flex items-end gap-2">
          <!-- Textarea responsive -->
          <div class="flex-1 relative">
            <textarea
              ref="textareaEl"
              v-model="newMessage"
              @keydown="onKeydown"
              @input="onInput"
              rows="1"
              placeholder="Écrivez…"
              class="w-full bg-gray-800 border border-gray-700 rounded-2xl px-4 py-2 text-white placeholder-gray-500 resize-none focus:outline-none focus:border-indigo-500 transition"
              style="max-height: 150px; overflow-y: auto;"
            ></textarea>
          </div>

          <!-- Stickers -->
          <div class="relative flex-shrink-0">
            <button @click="showStickers = !showStickers" ref="stickersBtn"
              class="w-10 h-10 flex items-center justify-center bg-gray-800 hover:bg-gray-700 text-white text-xl rounded-lg transition">🎭</button>
            <div v-if="showStickers" class="fixed z-50 bg-gray-800 rounded-2xl p-3 grid grid-cols-5 gap-2 shadow-xl" :style="stickersMenuStyle">
              <button v-for="s in stickers" :key="s" @click="sendSticker(s); showStickers = false"
                class="text-2xl hover:scale-125 transition">{{ s }}</button>
            </div>
          </div>

          <!-- More options -->
          <div class="relative flex-shrink-0">
            <button @click="showMoreOptions = !showMoreOptions" ref="moreOptionsBtn"
              class="w-10 h-10 flex items-center justify-center bg-gray-800 hover:bg-gray-700 text-white text-lg rounded-lg transition">⋮</button>
            <div v-if="showMoreOptions" class="fixed z-50 bg-gray-900 rounded-2xl border border-gray-700 shadow-xl overflow-hidden min-w-48" :style="moreOptionsMenuStyle">
              <!-- Emoji -->
              <div class="p-2">
                <button @click="showEmoji = !showEmoji"
                  class="w-full flex items-center gap-3 px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition text-left whitespace-nowrap">
                  😊 Emoji
                </button>
                <div v-if="showEmoji" class="mt-2">
                  <emoji-picker @emoji-click="onEmojiClick"></emoji-picker>
                </div>
              </div>
              <div class="border-t border-gray-700"></div>
              <!-- Attachments -->
              <div class="p-2 space-y-1">
                <label class="w-full flex items-center gap-3 px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition cursor-pointer whitespace-nowrap">
                  🖼️ Image <input type="file" accept="image/*" class="hidden" @change="(e) => { sendFile(e, 'image'); showMoreOptions = false; }" />
                </label>
                <label class="w-full flex items-center gap-3 px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition cursor-pointer whitespace-nowrap">
                  🎥 Vidéo <input type="file" accept="video/*" class="hidden" @change="(e) => { sendFile(e, 'video'); showMoreOptions = false; }" />
                </label>
                <label class="w-full flex items-center gap-3 px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition cursor-pointer whitespace-nowrap">
                  🎵 Audio <input type="file" accept="audio/*" class="hidden" @change="(e) => { sendFile(e, 'audio'); showMoreOptions = false; }" />
                </label>
                <label class="w-full flex items-center gap-3 px-4 py-2 text-gray-300 hover:bg-gray-800 rounded-lg transition cursor-pointer whitespace-nowrap">
                  📎 Fichier <input type="file" class="hidden" @change="(e) => { sendFile(e, 'file'); showMoreOptions = false; }" />
                </label>
              </div>
            </div>
          </div>

          <!-- Send -->
          <button @click="sendMessage" :disabled="!newMessage.trim() && !sending"
            class="w-10 h-10 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 rounded-lg flex items-center justify-center text-white text-lg transition flex-shrink-0">
            ➤
          </button>
        </div>
      </div>
    </main>

    <!-- Backdrop mobile pour le panneau info -->
    <div v-if="showInfo" @click="showInfo = false" class="fixed inset-0 bg-black/50 z-40 md:hidden"></div>

    <!-- ── Panneau info droite ──────────────────────────────────── -->
    <aside :class="['fixed md:static inset-y-0 right-0 w-80 md:w-72 bg-gray-900 border-l border-gray-800 flex flex-col overflow-y-auto transition-transform duration-300 z-50 md:z-auto', showInfo ? 'translate-x-0' : 'translate-x-full md:translate-x-0']">
      <div class="p-4 border-b border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-white">Infos</h3>
        <button @click="showInfo = false" class="md:hidden text-gray-400 hover:text-white text-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="p-4 text-center border-b border-gray-800">
        <img :src="conversation.avatar" class="w-20 h-20 rounded-full mx-auto object-cover mb-3" />
        <h3 class="font-bold text-white text-lg">{{ conversation.name }}</h3>
        <p v-if="conversation.description" class="text-gray-400 text-sm mt-1">{{ conversation.description }}</p>
      </div>
      <div class="p-4 flex-1">
        <h4 class="text-sm font-semibold text-gray-400 mb-3 uppercase">Membres ({{ participants.length }})</h4>
        <div class="space-y-3 max-h-[calc(100vh-400px)] md:max-h-none overflow-y-auto">
          <div v-for="p in participants" :key="p.id" class="flex items-center gap-3">
            <div class="relative flex-shrink-0">
              <img :src="p.avatar_url" class="w-9 h-9 rounded-full object-cover" />
              <span :class="['status-dot absolute bottom-0 right-0', p.status]"></span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-white truncate">{{ p.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ p.last_seen }}</p>
            </div>
          </div>
        </div>
      </div>
      <div class="p-4 border-t border-gray-800 space-y-2">
        <button @click="archive; showInfo = false" class="w-full flex items-center gap-3 text-gray-400 hover:text-white text-sm py-2 px-3 rounded-lg hover:bg-gray-800 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 5 7 13"/><line x1="7" y1="5" x2="17" y2="5"/></svg>
          Archiver
        </button>
        <button @click="mute; showInfo = false" class="w-full flex items-center gap-3 text-gray-400 hover:text-white text-sm py-2 px-3 rounded-lg hover:bg-gray-800 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 3.54a9 9 0 0 1 0 12.73M19.07 4.93a15 15 0 0 1 0 14.14M9.9 20.1h.01"/><line x1="9.9" y1="20" x2="9.9" y2="19.9"/></svg>
          Couper les notifications
        </button>
      </div>
    </aside>

    <!-- Modales -->
    <NewChatModal v-if="showNewChat" @close="showNewChat = false" />
    <CallModal v-if="store.incomingCall" :call="store.incomingCall" :auth-user="authUser" />
    <ActiveCallModal v-if="store.activeCall" :call="store.activeCall" :auth-user="authUser"
      :local-stream="localStream" :remote-stream="remoteStream"
      @end="handleEndCall" @toggle-mute="toggleMute" @toggle-video="toggleVideo"
      :is-muted="isMuted" :is-video-off="isVideoOff" />
  </div>
</template>

<script setup>
import ToastNotification from '@/Components/UI/ToastNotification.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';
import { useTheme } from '@/composables/useTheme.js';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useMessengerStore } from '@/stores/messenger.js';
import { useRealtime } from '@/composables/useRealtime.js';
import { useWebRTC } from '@/composables/useWebRTC.js';
import MessageBubble from '@/Components/Chat/MessageBubble.vue';
import NewChatModal from '@/Components/Chat/NewChatModal.vue';
import CallModal from '@/Components/Call/CallModal.vue';
import ActiveCallModal from '@/Components/Call/ActiveCallModal.vue';
import UploadingBubble from '@/Components/Chat/UploadingBubble.vue';

// Enregistrer emoji-picker en web component
import 'emoji-picker-element';

const toast   = ref(null);
const confirm = ref(null);
const { theme, setTheme } = useTheme();

const props = defineProps(['conversation', 'messages', 'participants', 'authUser']);
const store = useMessengerStore();
const page  = usePage();

const { subscribePresence, subscribeNotifications, subscribeConversation } = useRealtime(props.authUser.id);
const { localStream, remoteStream, callStatus, isMuted, isVideoOff, startCall: initCall, answerCall, handleSignal, endCall, toggleMute, toggleVideo } = useWebRTC(props.authUser.id);

const messagesEl  = ref(null);
const bottomEl    = ref(null);
const textareaEl  = ref(null);
const moreOptionsBtn = ref(null);
const stickersBtn = ref(null);
const newMessage  = ref('');
const replyTo     = ref(null);
const showEmoji   = ref(false);
const showStickers= ref(false);
const showInfo    = ref(false);
const showNewChat = ref(false);
const showMoreOptions = ref(false);
const sidebarSearch = ref('');
const sidebarOpen = ref(false);
const sending     = ref(false);
const hasMore     = ref(props.messages?.length === 50);
const loadingMore = ref(false);
const typingTimeout = ref(null);
const uploadingFiles = ref([]); // fichiers en cours d'upload

const stickers = ['🎉','🔥','❤️','😂','👍','🎊','✨','🌟','😎','🙌','💪','🤩','😍','🥳','🎈','🌈','💯','🚀','🦋','🌸'];

onMounted(() => {
  const sorted = [...(props.messages || [])].sort(
    (a, b) => new Date(a.created_at) - new Date(b.created_at)
  );
  store.setMessages(sorted);
  if (!store.conversations.length) store.setConversations([props.conversation]);
  store.setActiveConversation(props.conversation);

  subscribePresence();
  subscribeNotifications(props.authUser.id);
  subscribeConversation(props.conversation.id);

  scrollToBottom();
  markAsRead();
  window.addEventListener('messenger:notification', (e) => {
    toast.value?.add({
      type:    'message',
      title:   e.detail.sender_name,
      message: e.detail.preview,
      avatar:  e.detail.sender_avatar,
      duration: 5000,
    });
  });
});

// Auto-scroll quand nouveaux messages
watch(() => store.messages.length, () => nextTick(scrollToBottom));

const filteredSidebar = computed(() => {
  const q = sidebarSearch.value.toLowerCase();
  return store.sortedConversations.filter(c => !q || c.name?.toLowerCase().includes(q));
});

const otherParticipant = computed(() =>
  props.participants?.find(p => p.id !== props.authUser.id)
);

const currentTyping = computed(() =>
  store.typingUsers[props.conversation.id] || []
);

const typingText = computed(() => {
  const t = currentTyping.value;
  if (!t.length) return '';
  if (t.length === 1) return `${t[0].user_name} écrit…`;
  return `${t.map(u => u.user_name).join(', ')} écrivent…`;
});

const groupedMessages = computed(() => {
  const groups = {};
  store.messages.forEach(m => {
    const date = new Date(m.created_at).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long' });
    if (!groups[date]) groups[date] = [];
    groups[date].push(m);
  });
  return Object.entries(groups).map(([date, messages]) => ({ date, messages }));
});

const moreOptionsMenuStyle = computed(() => {
  if (!moreOptionsBtn.value || !showMoreOptions.value) return {};
  const rect = moreOptionsBtn.value.getBoundingClientRect();
  const menuWidth = 192; // min-w-48 = 12rem = 192px
  const menuHeight = 250;
  const padding = 10;

  let left = rect.right + padding;
  let top = rect.top;

  // Ajuster si le menu sort par la droite
  if (left + menuWidth > window.innerWidth) {
    left = rect.left - menuWidth - padding;
  }

  // Ajuster si le menu sort par le bas
  if (top + menuHeight > window.innerHeight) {
    top = window.innerHeight - menuHeight - padding;
  }

  return {
    left: `${left}px`,
    top: `${top}px`,
  };
});

const stickersMenuStyle = computed(() => {
  if (!stickersBtn.value || !showStickers.value) return {};
  const rect = stickersBtn.value.getBoundingClientRect();
  const menuWidth = 224; // w-56 = 14rem = 224px
  const menuHeight = 200;
  const padding = 10;

  let left = rect.right + padding;
  let top = rect.top;

  // Ajuster si le menu sort par la droite
  if (left + menuWidth > window.innerWidth) {
    left = rect.left - menuWidth - padding;
  }

  // Ajuster si le menu sort par le bas
  if (top + menuHeight > window.innerHeight) {
    top = window.innerHeight - menuHeight - padding;
  }

  return {
    left: `${left}px`,
    top: `${top}px`,
  };
});

async function sendMessage() {
  if (!newMessage.value.trim() || sending.value) return;
  const content = newMessage.value.trim();
  newMessage.value = '';
  sending.value = true;

  // Message optimiste
  const tempId = `temp-${Date.now()}`;
  store.addMessage({
    id: tempId, conversation_id: props.conversation.id,
    sender_id: props.authUser.id, type: 'text', content,
    is_deleted: false, is_edited: false, created_at: new Date().toISOString(),
    sender: { id: props.authUser.id, name: props.authUser.name, avatar_url: props.authUser.avatar_url },
    reply_to: replyTo.value, reactions: [], reads: [],
  });

  const data = new FormData();
  data.append('type', 'text');
  data.append('content', content);
  if (replyTo.value) data.append('reply_to_id', replyTo.value.id);
  replyTo.value = null;

  try {
    await axios.post(`/conversations/${props.conversation.id}/messages`, data);
    sendTypingEvent(false);
  } catch (err) {
    console.error(err);
  } finally {
    sending.value = false;
  }
}

async function sendFile(event, type) {
    const file = event.target.files[0];
    if (!file) return;

    // Créer un ID unique pour cet upload
    const uploadId = `upload-${Date.now()}`;

    // Ajouter à la liste des uploads en cours (affichage immédiat)
    uploadingFiles.value.push({
        id:       uploadId,
        type:     type,
        fileName: file.name,
        progress: 0,
    });

    const data = new FormData();
    data.append('type', type);
    data.append('file', file);

    try {
        await axios.post(
            `/conversations/${props.conversation.id}/messages`,
            data,
            {
                headers: { 'Content-Type': 'multipart/form-data' },
                onUploadProgress: (progressEvent) => {
                    // Mettre à jour la progression en temps réel
                    const percent = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                    const upload = uploadingFiles.value.find(u => u.id === uploadId);
                    if (upload) upload.progress = percent;
                },
            }
        );
    } catch (err) {
        console.error('Erreur upload:', err);
        // Toast d'erreur
        toast.value?.add({
            type:    'error',
            title:   'Échec de l\'envoi',
            message: `Impossible d'envoyer ${file.name}`,
            duration: 5000,
        });
    } finally {
        // Retirer l'indicateur d'upload
        uploadingFiles.value = uploadingFiles.value.filter(u => u.id !== uploadId);
        event.target.value = '';
    }
}

async function sendSticker(emoji) {
  showStickers.value = false;
  const data = new FormData();
  data.append('type', 'sticker');
  data.append('sticker_url', emoji);
  await axios.post(`/conversations/${props.conversation.id}/messages`, data);
}

function onKeydown(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

function onInput() {
  // Auto-resize
  const el = textareaEl.value;
  if (el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 120) + 'px'; }
  // Typing event
  sendTypingEvent(true);
  clearTimeout(typingTimeout.value);
  typingTimeout.value = setTimeout(() => sendTypingEvent(false), 3000);
}

async function sendTypingEvent(isTyping) {
  await axios.post(`/conversations/${props.conversation.id}/typing`, { is_typing: isTyping }).catch(() => {});
}

function onEmojiClick(e) {
  newMessage.value += e.detail.unicode;
  showEmoji.value = false;
  textareaEl.value?.focus();
}

function setReply(msg) { replyTo.value = msg; textareaEl.value?.focus(); }

async function react(msg, emoji) {
  await axios.post(`/messages/${msg.id}/react`, { emoji });
}

async function editMessage(msg, content) {
    try {
        await axios.put(`/messages/${msg.id}`, { content });
        toast.value?.add({
            type:    'success',
            message: 'Message modifié',
            duration: 3000,
        });
    } catch (err) {
        toast.value?.add({
            type:    'error',
            message: 'Impossible de modifier ce message',
            duration: 4000,
        });
    }
}

async function deleteMessage(msg) {
    try {
        await axios.delete(`/messages/${msg.id}`);
    } catch (err) {
        toast.value?.add({
            type:    'error',
            message: 'Impossible de supprimer ce message',
            duration: 4000,
        });
    }
}

async function markAsRead() {
  await axios.post(`/conversations/${props.conversation.id}/messages/read`).catch(() => {});
}

async function loadMore() {
  loadingMore.value = true;
  const firstId = store.messages[0]?.id;
  try {
    const res = await axios.get(`/conversations/${props.conversation.id}/messages/more`, { params: { before_id: firstId } });
    const older = [...res.data.messages].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    store.messages.unshift(...older);
    hasMore.value = res.data.has_more;
  } finally { loadingMore.value = false; }
}

function scrollToBottom() {
  bottomEl.value?.scrollIntoView({ behavior: 'smooth' });
}

function onScroll() { /* détecter scroll vers le haut pour load more */ }

async function startCall(type) {
  await initCall(props.conversation.id, type);
  store.setActiveCall({ type, conversation: props.conversation });
}

async function handleEndCall() {
  await endCall();
  store.clearActiveCall();
}

async function archive() { await axios.post(`/conversations/${props.conversation.id}/archive`); router.visit('/conversations'); }
async function mute()    { await axios.post(`/conversations/${props.conversation.id}/mute`); }

function formatTime(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  const now = new Date();
  const diff = now - d;
  if (diff < 60000)    return 'maintenant';
  if (diff < 3600000)  return Math.floor(diff / 60000) + 'm';
  if (diff < 86400000) return d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
  return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit' });
}

function typeLabel(type) {
  return { image:'📷 Photo', video:'🎥 Vidéo', audio:'🎵 Audio', file:'📎 Fichier', sticker:'🎭 Sticker' }[type] || '';
}
</script>
