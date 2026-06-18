<template>
  <div :class="['flex items-end gap-2 mb-1 group', isMine ? 'flex-row-reverse' : 'flex-row']">

    <!-- Avatar -->
    <img v-if="!isMine" :src="message.sender?.avatar_url || avatarUrl(message.sender?.name)"
      class="w-7 h-7 rounded-full object-cover flex-shrink-0 mb-1" />

    <div :class="['max-w-[70%] flex flex-col relative', isMine ? 'items-end' : 'items-start']">

      <!-- Nom expéditeur -->
      <span v-if="!isMine && showSenderName" class="text-xs text-indigo-400 mb-1 ml-1">
        {{ message.sender?.name }}
      </span>

      <!-- Reply preview -->
      <div v-if="message.reply_to"
        :class="['rounded-xl px-3 py-2 mb-1 text-xs border-l-2 border-indigo-400 opacity-70 max-w-full', isMine ? 'bg-indigo-800/40' : 'bg-gray-700']">
        <p class="font-semibold text-indigo-300">{{ message.reply_to.sender?.name }}</p>
        <p class="truncate text-gray-300">{{ message.reply_to.content || '📎 Média' }}</p>
      </div>

      <!-- Bulle + bouton 3 points -->
      <div class="flex items-center gap-1.5"
        :class="isMine ? 'flex-row-reverse' : 'flex-row'">

        <!-- Bulle principale -->
        <div :class="['relative rounded-2xl px-4 py-2',
          isMine ? 'bubble-sent' : 'bubble-received',
          message.type === 'image' ? 'p-1' : '',
          message.is_deleted ? 'opacity-50 italic' : '']">

          <!-- Supprimé -->
          <span v-if="message.is_deleted" class="text-sm">🚫 Message supprimé</span>

          <!-- Texte -->
          <p v-else-if="message.type === 'text'"
            class="text-sm whitespace-pre-wrap break-words">{{ message.content }}</p>

          <!-- Emoji seul -->
          <p v-else-if="message.type === 'emoji'" class="text-4xl">{{ message.content }}</p>

          <!-- Sticker -->
          <p v-else-if="message.type === 'sticker'" class="text-5xl">{{ message.content }}</p>

          <!-- Image -->
          <div v-else-if="message.type === 'image'">
            <img :src="message.file_url || message.content"
              @click="showLightbox = true"
              class="rounded-xl max-w-xs max-h-64 object-cover cursor-zoom-in"
              loading="lazy" />
          </div>

          <!-- Vidéo -->
          <div v-else-if="message.type === 'video'">
            <video :src="message.file_url || message.content"
              controls class="rounded-xl max-w-xs md:max-w-md max-h-64 w-full" preload="metadata" />
          </div>

          <!-- Audio -->
          <div v-else-if="message.type === 'audio'"
            class="flex items-center gap-3 min-w-[200px]">
            <button @click="toggleAudio"
              class="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
              {{ isPlaying ? '⏸️' : '▶️' }}
            </button>
            <div class="flex-1">
              <div class="h-1 bg-white/20 rounded-full relative">
                <div :style="{ width: audioProgress + '%' }"
                  class="h-full bg-white rounded-full"></div>
              </div>
              <p class="text-xs mt-1 opacity-70">{{ audioDuration }}</p>
            </div>
            <audio ref="audioEl" :src="message.file_url || message.content"
              @timeupdate="onTimeUpdate" @loadedmetadata="onLoadedMetadata"
              @ended="isPlaying = false" class="hidden" />
          </div>

          <!-- Fichier -->
          <a v-else-if="message.type === 'file'"
            :href="message.file_url || message.content"
            target="_blank" download
            class="flex items-center gap-3 min-w-[180px] hover:opacity-80 transition">
            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
              {{ fileIcon(message.file_mime) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ message.file_name || 'Fichier' }}</p>
              <p class="text-xs opacity-60">{{ message.file_size_human }}</p>
            </div>
            <span class="text-lg">⬇️</span>
          </a>

          <!-- Appel -->
          <div v-else-if="message.type === 'call'"
            class="flex items-center gap-2 text-sm">
            <span>{{ message.content }}</span>
          </div>

          <!-- Système -->
          <div v-else-if="message.type === 'system'"
            class="text-xs opacity-70 text-center">
            {{ message.content }}
          </div>

          <!-- Heure + statut -->
          <div :class="['flex items-center gap-1 mt-1',
            isMine ? 'justify-end' : 'justify-start']">
            <span class="text-xs opacity-50">{{ formatTime(message.created_at) }}</span>
            <span v-if="message.is_edited" class="text-xs opacity-40">• modifié</span>
            <span v-if="isMine" class="text-xs opacity-60">
              {{ message.reads?.length > 0 ? '✓✓' : '✓' }}
            </span>
          </div>
        </div>

        <!-- ── Bouton 3 points (visible au hover du groupe) ── -->
        <div v-if="!message.is_deleted" class="relative flex-shrink-0">
          <button
            ref="menuBtn"
            @click.stop="toggleMenu"
            class="w-7 h-7 rounded-full flex items-center justify-center transition
                   opacity-0 group-hover:opacity-100 hover:!opacity-100
                   bg-gray-700/80 hover:bg-gray-600 text-gray-300"
            title="Options">
            <!-- SVG 3 points verticaux -->
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
              viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="5"  r="2"/>
              <circle cx="12" cy="12" r="2"/>
              <circle cx="12" cy="19" r="2"/>
            </svg>
          </button>

          <!-- Menu déroulant -->
          <Transition name="menu">
            <div v-if="showMenu"
              class="fixed z-50 bg-gray-800 border border-gray-700 rounded-2xl shadow-2xl py-1 min-w-52 overflow-hidden"
              :style="menuStyle">

              <!-- Réactions rapides -->
              <div class="flex justify-around px-3 py-2.5 border-b border-gray-700">
                <button v-for="e in quickEmojis" :key="e"
                  @click="react(e)"
                  class="text-xl hover:scale-125 transition-transform">{{ e }}</button>
              </div>

              <!-- Actions -->
              <button @click="reply"
                class="menu-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
                </svg>
                Répondre
              </button>

              <button v-if="message.type === 'text' || message.type === 'emoji'"
                @click="copyText"
                class="menu-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                  <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Copier
              </button>

              <button v-if="isMine && message.type === 'text'"
                @click="startEdit"
                class="menu-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Modifier
              </button>

              <button v-if="message.type === 'image' || message.type === 'file' || message.type === 'video' || message.type === 'audio'"
                @click="downloadFile"
                class="menu-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Télécharger
              </button>

              <button v-if="isMine"
                @click="deleteMsg"
                class="menu-item text-red-400 hover:bg-red-500/10">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                  <path d="M10 11v6"/><path d="M14 11v6"/>
                  <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                </svg>
                Supprimer
              </button>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Réactions -->
      <div v-if="groupedReactions.length > 0"
        :class="['flex flex-wrap gap-1 mt-1', isMine ? 'justify-end' : 'justify-start']">
        <button v-for="r in groupedReactions" :key="r.emoji"
          @click="$emit('react', message, r.emoji)"
          :class="['flex items-center gap-1 bg-gray-800 rounded-full px-2 py-0.5 text-xs hover:bg-gray-700 transition border',
            r.mine ? 'border-indigo-500' : 'border-transparent']">
          {{ r.emoji }} <span class="text-gray-400">{{ r.count }}</span>
        </button>
      </div>
    </div>

    <!-- Lightbox -->
    <div v-if="showLightbox"
      class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center"
      @click="showLightbox = false">
      <img :src="message.file_url || message.content"
        class="max-w-full max-h-full object-contain rounded-xl" />
      <button class="absolute top-4 right-4 text-white text-2xl">✕</button>
    </div>

    <!-- Modal d'édition -->
    <div v-if="editing"
      class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
      @click.self="editing = false">
      <div class="bg-gray-800 rounded-2xl p-5 w-full max-w-lg border border-gray-700 shadow-2xl">
        <h3 class="text-white font-semibold mb-3 flex items-center gap-2">✏️ Modifier le message</h3>
        <textarea v-model="editContent"
          class="w-full bg-gray-700 border border-gray-600 rounded-xl p-3 text-white resize-none focus:outline-none focus:border-indigo-500"
          rows="3" @keydown.enter.ctrl="saveEdit"></textarea>
        <p class="text-xs text-gray-500 mt-1">Ctrl+Entrée pour valider</p>
        <div class="flex justify-end gap-2 mt-4">
          <button @click="editing = false"
            class="px-4 py-2 text-gray-400 hover:text-white transition rounded-xl hover:bg-gray-700">
            Annuler
          </button>
          <button @click="saveEdit"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-medium transition">
            Enregistrer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps(['message', 'isMine', 'authUser', 'showSenderName']);
const emit  = defineEmits(['reply', 'react', 'edit', 'delete']);

const showMenu    = ref(false);
const showLightbox= ref(false);
const editing     = ref(false);
const editContent = ref('');
const audioEl     = ref(null);
const isPlaying   = ref(false);
const audioProgress = ref(0);
const audioDuration = ref('0:00');
const menuBtn     = ref(null);

const quickEmojis = ['❤️','😂','😮','😢','😡','👍'];

const groupedReactions = computed(() => {
    const map = {};
    props.message.reactions?.forEach(r => {
        if (!map[r.emoji]) map[r.emoji] = { emoji: r.emoji, count: 0, mine: false };
        map[r.emoji].count++;
        if (r.user?.id === props.authUser?.id) map[r.emoji].mine = true;
    });
    return Object.values(map);
});

const menuStyle = computed(() => {
    if (!menuBtn.value || !showMenu.value) return {};
    const rect = menuBtn.value.getBoundingClientRect();
    const menuWidth = 208; // w-52 = 13rem = 208px
    const menuHeight = 300; // hauteur approximative du menu
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

    // Ajuster si le menu sort par le haut
    if (top < padding) {
        top = padding;
    }

    return {
        left: `${left}px`,
        top: `${top}px`,
    };
});

function toggleMenu(e) {
    showMenu.value = !showMenu.value;
}

function closeMenu() { showMenu.value = false; }

function reply()  { emit('reply', props.message); closeMenu(); }

function react(emoji) {
    emit('react', props.message, emoji);
    closeMenu();
}

function copyText() {
    if (props.message.content) {
        navigator.clipboard.writeText(props.message.content);
    }
    closeMenu();
}

function startEdit() {
    editContent.value = props.message.content;
    editing.value = true;
    closeMenu();
}

function saveEdit() {
    if (editContent.value.trim()) {
        emit('edit', props.message, editContent.value.trim());
    }
    editing.value = false;
}

function deleteMsg() {
    emit('delete', props.message);
    closeMenu();
}

function downloadFile() {
    const url = props.message.file_url || props.message.content;
    if (!url) return;
    const a = document.createElement('a');
    a.href = url;
    a.download = props.message.file_name || 'fichier';
    a.target = '_blank';
    a.click();
    closeMenu();
}

function toggleAudio() {
    if (!audioEl.value) return;
    if (isPlaying.value) { audioEl.value.pause(); isPlaying.value = false; }
    else { audioEl.value.play(); isPlaying.value = true; }
}

function onTimeUpdate() {
    if (!audioEl.value) return;
    audioProgress.value = (audioEl.value.currentTime / audioEl.value.duration) * 100;
    audioDuration.value = formatDuration(audioEl.value.currentTime);
}

function onLoadedMetadata() {
    if (audioEl.value) audioDuration.value = formatDuration(audioEl.value.duration);
}

function formatDuration(s) {
    const m = Math.floor(s / 60);
    const sec = Math.floor(s % 60);
    return `${m}:${sec.toString().padStart(2, '0')}`;
}

function avatarUrl(name) {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || '?')}&background=6366f1&color=fff&size=64`;
}

function formatTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function fileIcon(mime) {
    if (!mime) return '📄';
    if (mime.includes('pdf'))   return '📕';
    if (mime.includes('word'))  return '📘';
    if (mime.includes('excel') || mime.includes('spreadsheet')) return '📗';
    if (mime.includes('zip') || mime.includes('rar')) return '🗜️';
    return '📄';
}

// Fermer le menu si on clique ailleurs
function onClickOutside(e) {
    if (showMenu.value) closeMenu();
}

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>

<style scoped>
.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    text-align: left;
    padding: 10px 16px;
    font-size: 0.875rem;
    color: #d1d5db;
    transition: background-color 0.15s;
    cursor: pointer;
    background: transparent;
    border: none;
}

.menu-item:hover {
    background-color: rgba(255, 255, 255, 0.07);
}

.menu-enter-active {
    animation: menuIn 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.menu-leave-active {
    animation: menuIn 0.1s ease reverse;
}

@keyframes menuIn {
    from { opacity: 0; transform: scale(0.92) translateY(-4px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>