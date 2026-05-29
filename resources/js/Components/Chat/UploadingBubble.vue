<template>
  <div class="flex items-end gap-2 mb-1 flex-row-reverse">
    <div class="max-w-[70%] flex flex-col items-end">
      <div class="bubble-sent px-4 py-3 relative overflow-hidden min-w-[220px]">

        <!-- Contenu selon le type -->
        <div class="flex items-center gap-3">

          <!-- Icône animée -->
          <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 relative"
            :style="{ background: 'rgba(255,255,255,0.15)' }">
            <span class="text-2xl">{{ icon }}</span>
            <!-- Spinner autour de l'icône -->
            <svg class="absolute inset-0 w-12 h-12 animate-spin" viewBox="0 0 48 48">
              <circle cx="24" cy="24" r="20" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="3"/>
              <circle cx="24" cy="24" r="20" fill="none" stroke="white" stroke-width="3"
                stroke-dasharray="30 96" stroke-linecap="round"/>
            </svg>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ fileName }}</p>
            <p class="text-xs text-white/60 mt-0.5">{{ label }}</p>

            <!-- Barre de progression -->
            <div class="mt-2 h-1 bg-white/20 rounded-full overflow-hidden">
              <div class="h-full bg-white rounded-full transition-all duration-300"
                :style="{ width: progress + '%' }"></div>
            </div>
          </div>
        </div>

        <!-- Pourcentage -->
        <div class="flex justify-end mt-2">
          <span class="text-xs text-white/50">{{ progress }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type:     { type: String, default: 'file' },
  fileName: { type: String, default: 'Fichier' },
  progress: { type: Number, default: 0 },
});

const icon = computed(() => ({
  image: '🖼️',
  video: '🎥',
  audio: '🎵',
  file:  '📎',
}[props.type] ?? '📎'));

const label = computed(() => ({
  image: 'Envoi de la photo…',
  video: 'Envoi de la vidéo…',
  audio: 'Envoi de l\'audio…',
  file:  'Envoi du fichier…',
}[props.type] ?? 'Envoi en cours…'));
</script>