<template>
  <div class="fixed inset-0 bg-black/80 z-[100] flex items-center justify-center p-4">
    <div class="bg-gray-900 rounded-3xl w-80 border border-gray-700 shadow-2xl overflow-hidden">
      <!-- Fond animé -->
      <div class="relative bg-gradient-to-br from-indigo-900 to-purple-900 p-8 text-center">
        <div class="absolute inset-0 opacity-20">
          <div class="animate-ping absolute inset-8 rounded-full bg-indigo-500"></div>
        </div>
        <img :src="call.initiator?.avatar_url || avatarUrl(call.initiator?.name)"
          class="w-24 h-24 rounded-full mx-auto object-cover border-4 border-white/20 relative z-10" />
        <h2 class="text-white text-xl font-bold mt-4">{{ call.initiator?.name }}</h2>
        <p class="text-indigo-200 text-sm mt-1">
          {{ call.call_type === 'video' ? '📹 Appel vidéo entrant' : '📞 Appel audio entrant' }}
        </p>
      </div>

      <!-- Boutons -->
      <div class="p-6 flex justify-center gap-8">
        <button @click="decline"
          class="w-16 h-16 bg-red-500 hover:bg-red-400 rounded-full flex items-center justify-center transition shadow-lg" title="Décliner">
          <SvgIcon name="phone-hangup" className="w-8 h-8 text-white" />
        </button>
        <button @click="answer"
          class="w-16 h-16 bg-green-500 hover:bg-green-400 rounded-full flex items-center justify-center transition shadow-lg animate-bounce" :title="call.call_type === 'video' ? 'Répondre en vidéo' : 'Répondre'">
          <SvgIcon :name="call.call_type === 'video' ? 'video-call' : 'phone-incoming'" className="w-8 h-8 text-white" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import SvgIcon from '@/Components/UI/SvgIcon.vue';
import { useMessengerStore } from '@/stores/messenger.js';
import { useWebRTC } from '@/composables/useWebRTC.js';

const props = defineProps(['call', 'authUser']);
const store = useMessengerStore();
const { answerCall } = useWebRTC(props.authUser.id);

async function answer() {
  try {
    const signal = typeof props.call.signal === 'string' ? props.call.signal : JSON.stringify(props.call.signal);
    await answerCall(props.call.call_uuid, signal);
    store.setActiveCall(props.call);
    store.clearIncomingCall();
  } catch (e) { console.error(e); }
}

async function decline() {
  await axios.post(`/calls/${props.call.call_uuid}/signal`, {
    status: 'declined',
    signal: JSON.stringify({ type: 'declined' }),
  });
  store.clearIncomingCall();
}

function avatarUrl(name) {
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || '?')}&background=6366f1&color=fff`;
}
</script>
