<template>
  <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-gray-900 rounded-2xl w-full max-w-md border border-gray-800 shadow-2xl">
      <!-- Header -->
      <div class="p-4 border-b border-gray-800 flex items-center justify-between">
        <h2 class="text-white font-semibold text-lg">Nouvelle conversation</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-white transition" title="Fermer">
          <SvgIcon name="close" className="w-4 h-4" />
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-gray-800">
        <button @click="tab = 'direct'" :class="['flex-1 py-3 text-sm font-medium transition flex items-center justify-center gap-2', tab === 'direct' ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-gray-500']">
          <SvgIcon name="chat" className="w-4 h-4" /> Discussion directe
        </button>
        <button @click="tab = 'group'" :class="['flex-1 py-3 text-sm font-medium transition flex items-center justify-center gap-2', tab === 'group' ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-gray-500']">
          <SvgIcon name="group" className="w-4 h-4" /> Groupe
        </button>
      </div>

      <div class="p-4">
        <!-- Recherche utilisateurs -->
        <input v-model="search" @input="searchUsers" type="text" placeholder="Rechercher par nom ou @username…"
          class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 mb-4 transition" />

        <!-- Groupe : nom -->
        <div v-if="tab === 'group'" class="mb-4">
          <input v-model="groupName" type="text" placeholder="Nom du groupe"
            class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" />
        </div>

        <!-- Sélectionnés -->
        <div v-if="tab === 'group' && selected.length > 0" class="flex flex-wrap gap-2 mb-4">
          <div v-for="u in selected" :key="u.id" class="flex items-center gap-1 bg-indigo-600/20 border border-indigo-500/30 rounded-full pl-1 pr-2 py-1">
            <img :src="u.avatar_url || avatarUrl(u.name)" class="w-5 h-5 rounded-full" />
            <span class="text-xs text-indigo-300">{{ u.name }}</span>
            <button @click="toggleSelect(u)" class="text-indigo-300 hover:text-white ml-1" title="Retirer">
              <SvgIcon name="close" className="w-3 h-3" />
            </button>
          </div>
        </div>

        <!-- Résultats -->
        <div class="space-y-1 max-h-64 overflow-y-auto">
          <div v-if="loading" class="text-center py-4 text-gray-400 text-sm">Recherche…</div>
          <div v-else-if="results.length === 0 && search.length > 0" class="text-center py-4 text-gray-500 text-sm">
            Aucun résultat
          </div>
          <div v-for="user in results" :key="user.id"
            @click="tab === 'direct' ? openDirect(user) : toggleSelect(user)"
            :class="['flex items-center gap-3 p-3 rounded-xl cursor-pointer transition',
              isSelected(user) ? 'bg-indigo-600/20 border border-indigo-500/30' : 'hover:bg-gray-800']">
            <div class="relative">
              <img :src="user.avatar_url || avatarUrl(user.name)" class="w-10 h-10 rounded-full object-cover" />
              <span :class="['status-dot absolute bottom-0 right-0', user.status || 'offline']"></span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-white font-medium text-sm">{{ user.name }}</p>
              <p class="text-gray-400 text-xs">@{{ user.username }}</p>
            </div>
            <span v-if="isSelected(user)" class="text-indigo-400">
              <SvgIcon name="check" className="w-4 h-4" />
            </span>
          </div>
        </div>

        <!-- Créer groupe -->
        <div v-if="tab === 'group' && selected.length > 0" class="mt-4">
          <button @click="createGroup" :disabled="!groupName.trim() || creating"
            class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition">
            {{ creating ? 'Création…' : `Créer le groupe (${selected.length + 1} membres)` }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import SvgIcon from '@/Components/UI/SvgIcon.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const emit = defineEmits(['close', 'created']);

const tab       = ref('direct');
const search    = ref('');
const results   = ref([]);
const selected  = ref([]);
const groupName = ref('');
const loading   = ref(false);
const creating  = ref(false);
let searchTimer = null;

function searchUsers() {
  clearTimeout(searchTimer);
  if (!search.value.trim()) { results.value = []; return; }
  loading.value = true;
  searchTimer = setTimeout(async () => {
    try {
      const res = await axios.get('/users/search', { params: { q: search.value } });
      results.value = res.data;
    } finally { loading.value = false; }
  }, 300);
}

function openDirect(user) {
  router.post('/conversations', { user_id: user.id }, { onSuccess: () => emit('close') });
}

function toggleSelect(user) {
  const idx = selected.value.findIndex(u => u.id === user.id);
  if (idx !== -1) selected.value.splice(idx, 1);
  else selected.value.push(user);
}

function isSelected(user) {
  return selected.value.some(u => u.id === user.id);
}

async function createGroup() {
  if (!groupName.value.trim() || selected.value.length === 0) return;
  creating.value = true;
  try {
    await router.post('/conversations/group', {
      name:     groupName.value,
      user_ids: selected.value.map(u => u.id),
    }, { onSuccess: () => emit('close') });
  } finally { creating.value = false; }
}

function avatarUrl(name) {
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || '?')}&background=6366f1&color=fff`;
}
</script>
