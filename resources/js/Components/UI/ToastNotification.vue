<template>
  <Teleport to="body">
    <div class="messenger-toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="['messenger-toast', toast.type]"
          @click="remove(toast.id)"
        >
          <!-- Icône ou avatar -->
          <div class="flex-shrink-0">
            <img v-if="toast.avatar" :src="toast.avatar"
              class="w-10 h-10 rounded-full object-cover" />
            <div v-else :class="['w-10 h-10 rounded-full flex items-center justify-center', iconBg(toast.type)]">
              <SvgIcon :name="iconName(toast.type)" className="w-5 h-5" />
            </div>
          </div>

          <!-- Contenu -->
          <div class="flex-1 min-w-0">
            <p v-if="toast.title" class="font-semibold text-sm" :style="{ color: 'var(--text-primary)' }">
              {{ toast.title }}
            </p>
            <p class="text-sm truncate" :style="{ color: 'var(--text-secondary)' }">
              {{ toast.message }}
            </p>
          </div>

          <!-- Barre de progression -->
          <div class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full overflow-hidden">
            <div
              :class="['h-full rounded-full transition-all', progressColor(toast.type)]"
              :style="{ width: toast.progress + '%', transition: `width ${toast.duration}ms linear` }"
            ></div>
          </div>

          <!-- Bouton fermer -->
          <button @click.stop="remove(toast.id)"
            class="flex-shrink-0 opacity-40 hover:opacity-100 transition ml-1 flex items-center justify-center"
            :style="{ color: 'var(--text-primary)' }" title="Fermer">
            <SvgIcon name="close" className="w-4 h-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import SvgIcon from '@/Components/UI/SvgIcon.vue';

const toasts = ref([]);

function add({ type = 'info', title = '', message = '', avatar = null, duration = 4000 }) {
  const id = Date.now() + Math.random();
  const toast = { id, type, title, message, avatar, duration, progress: 100 };
  toasts.value.push(toast);

  // Démarrer la barre de progression
  setTimeout(() => {
    const t = toasts.value.find(t => t.id === id);
    if (t) t.progress = 0;
  }, 50);

  // Auto-supprimer
  setTimeout(() => remove(id), duration);
}

function remove(id) {
  const idx = toasts.value.findIndex(t => t.id === id);
  if (idx !== -1) toasts.value.splice(idx, 1);
}

function iconName(type) {
  return { success: 'check-circle', error: 'x-circle', warning: 'alert-triangle', info: 'info-circle', message: 'chat' }[type] ?? 'info-circle';
}

function iconBg(type) {
  return {
    success: 'bg-green-500/20 text-green-400',
    error:   'bg-red-500/20 text-red-400',
    warning: 'bg-yellow-500/20 text-yellow-400',
    info:    'bg-indigo-500/20 text-indigo-400',
    message: 'bg-indigo-500/20 text-indigo-400',
  }[type] ?? 'bg-gray-500/20';
}

function progressColor(type) {
  return {
    success: 'bg-green-400',
    error:   'bg-red-400',
    warning: 'bg-yellow-400',
    info:    'bg-indigo-400',
    message: 'bg-indigo-400',
  }[type] ?? 'bg-indigo-400';
}

// Exposer la méthode add pour l'utiliser depuis le parent
defineExpose({ add });
</script>

<style scoped>
.toast-enter-active { animation: slideInRight 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-leave-active { animation: slideOutRight 0.3s ease forwards; }
.messenger-toast { position: relative; overflow: hidden; }
</style>