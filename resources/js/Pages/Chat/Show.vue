<template>
  <div class="flex h-screen bg-gray-950 overflow-hidden">

    <!-- ── Sidebar conversations ──────────────────────────────────── -->
    <aside class="w-80 flex-shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col">
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
          @click="router.visit(`/conversations/${conv.id}`)"
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
          <button @click="startCall('audio')" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-full flex items-center justify-center transition" title="Appel audio">📞</button>
          <button @click="startCall('video')" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-full flex items-center justify-center transition" title="Appel vidéo">📹</button>
          <button @click="showInfo = !showInfo" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-full flex items-center justify-center transition">ℹ️</button>
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
        <div class="flex items-end gap-3">

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
      </div>
    </main>

    <!-- ── Panneau info droite ──────────────────────────────────── -->
    <aside v-if="showInfo" class="w-72 bg-gray-900 border-l border-gray-800 flex flex-col overflow-y-auto">
      <div class="p-4 border-b border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-white">Infos</h3>
        <button @click="showInfo = false" class="text-gray-400 hover:text-white">✕</button>
      </div>
      <div class="p-4 text-center border-b border-gray-800">
        <img :src="conversation.avatar" class="w-20 h-20 rounded-full mx-auto object-cover mb-3" />
        <h3 class="font-bold text-white text-lg">{{ conversation.name }}</h3>
        <p v-if="conversation.description" class="text-gray-400 text-sm mt-1">{{ conversation.description }}</p>
      </div>
      <div class="p-4">
        <h4 class="text-sm font-semibold text-gray-400 mb-3 uppercase">Membres ({{ participants.length }})</h4>
        <div v-for="p in participants" :key="p.id" class="flex items-center gap-3 mb-3">
          <div class="relative">
            <img :src="p.avatar_url" class="w-9 h-9 rounded-full object-cover" />
            <span :class="['status-dot absolute bottom-0 right-0', p.status]"></span>
          </div>
          <div>
            <p class="text-sm font-medium text-white">{{ p.name }}</p>
            <p class="text-xs text-gray-500">{{ p.last_seen }}</p>
          </div>
        </div>
      </div>
      <div class="p-4 border-t border-gray-800 space-y-2">
        <button @click="archive" class="w-full text-left text-gray-400 hover:text-white text-sm py-2 px-3 rounded-lg hover:bg-gray-800 transition">📁 Archiver</button>
        <button @click="mute" class="w-full text-left text-gray-400 hover:text-white text-sm py-2 px-3 rounded-lg hover:bg-gray-800 transition">🔇 Couper les notifications</button>
      </div>
    </aside>

    <!-- Modales -->
    <NewChatModal v-if="showNewChat" @close="showNewChat = false" />
    <CallModal v-if="store.incomingCall" :call="store.incomingCall" :auth-user="authUser" />
    <ActiveCallModal v-if="store.activeCall" :call="store.activeCall" :auth-user="authUser"
      :local-stream="localStream" :remote-stream="remoteStream"
      @end="endCall" @toggle-mute="toggleMute" @toggle-video="toggleVideo"
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
const newMessage  = ref('');
const replyTo     = ref(null);
const showEmoji   = ref(false);
const showStickers= ref(false);
const showInfo    = ref(false);
const showNewChat = ref(false);
const sidebarSearch = ref('');
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
