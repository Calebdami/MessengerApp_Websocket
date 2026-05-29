<template>
  <div class="fixed inset-0 bg-gray-950 z-[100] flex flex-col items-center justify-between p-8">

    <!-- Remote video / audio -->
    <div class="relative w-full flex-1 flex items-center justify-center">
      <video v-if="call.call_type === 'video' && remoteStream"
        :srcObject="remoteStream" autoplay playsinline
        class="w-full h-full object-cover rounded-2xl" />
      <div v-else class="text-center">
        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-5xl mx-auto mb-4">
          {{ call.initiator?.name?.[0]?.toUpperCase() || '?' }}
        </div>
        <h2 class="text-white text-2xl font-bold">{{ call.initiator?.name }}</h2>
        <p class="text-gray-400 mt-2">{{ statusText }}</p>
      </div>

      <!-- Local video (petit, coin) -->
      <video v-if="call.call_type === 'video' && localStream"
        :srcObject="localStream" autoplay playsinline muted
        :class="['absolute bottom-4 right-4 w-32 h-24 object-cover rounded-xl border-2 border-gray-700 shadow-lg', isVideoOff ? 'hidden' : '']" />

      <!-- Timer -->
      <div class="absolute top-4 left-1/2 -translate-x-1/2 bg-black/50 text-white text-sm px-4 py-2 rounded-full">
        {{ callTimer }}
      </div>
    </div>

    <!-- Controls -->
    <div class="flex items-center justify-center gap-6 mt-8">
      <button @click="$emit('toggle-mute')"
        :class="['w-14 h-14 rounded-full flex items-center justify-center text-2xl transition shadow-lg', isMuted ? 'bg-red-500 hover:bg-red-400' : 'bg-gray-700 hover:bg-gray-600']">
        {{ isMuted ? '🔇' : '🎤' }}
      </button>

      <button v-if="call.call_type === 'video'" @click="$emit('toggle-video')"
        :class="['w-14 h-14 rounded-full flex items-center justify-center text-2xl transition shadow-lg', isVideoOff ? 'bg-red-500 hover:bg-red-400' : 'bg-gray-700 hover:bg-gray-600']">
        {{ isVideoOff ? '📵' : '📹' }}
      </button>

      <button @click="$emit('end')"
        class="w-16 h-16 bg-red-500 hover:bg-red-400 rounded-full flex items-center justify-center text-3xl transition shadow-xl">
        📵
      </button>

      <button @click="toggleSpeaker"
        class="w-14 h-14 bg-gray-700 hover:bg-gray-600 rounded-full flex items-center justify-center text-2xl transition shadow-lg">
        {{ speakerOn ? '🔊' : '🔈' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps(['call', 'authUser', 'localStream', 'remoteStream', 'isMuted', 'isVideoOff']);
defineEmits(['end', 'toggle-mute', 'toggle-video']);

const speakerOn  = ref(true);
const elapsed    = ref(0);
let timerRef     = null;

const statusText = computed(() => {
  if (elapsed.value === 0) return 'Connexion…';
  return `En communication • ${callTimer.value}`;
});

const callTimer = computed(() => {
  const m = Math.floor(elapsed.value / 60);
  const s = elapsed.value % 60;
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
});

onMounted(() => {
  timerRef = setInterval(() => elapsed.value++, 1000);
});

onUnmounted(() => clearInterval(timerRef));

function toggleSpeaker() {
  speakerOn.value = !speakerOn.value;
}

// Attacher les streams aux éléments vidéo
watch(() => props.remoteStream, (stream) => {
  if (stream) {
    const el = document.querySelector('video[autoplay]:not([muted])');
    if (el) el.srcObject = stream;
  }
});

watch(() => props.localStream, (stream) => {
  if (stream) {
    const el = document.querySelector('video[muted]');
    if (el) el.srcObject = stream;
  }
});
</script>
