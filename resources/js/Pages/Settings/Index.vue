<template>
  <div class="min-h-screen bg-gray-950 flex">

    <!-- Sidebar paramètres -->
    <aside class="w-64 bg-gray-900 border-r border-gray-800 p-4 flex flex-col">
      <div class="flex items-center gap-3 mb-6">
        <a href="/conversations" class="text-gray-400 hover:text-white transition" title="Retour">
          <SvgIcon name="arrow-left" className="w-5 h-5" />
        </a>
        <h1 class="text-white font-bold text-lg">Paramètres</h1>
      </div>

      <!-- Avatar + nom -->
      <div class="text-center mb-6 p-4 bg-gray-800 rounded-2xl">
        <div class="relative inline-block">
          <img :src="previewAvatar || authUser.avatar_url" class="w-20 h-20 rounded-full object-cover mx-auto" />
          <label class="absolute bottom-0 right-0 w-7 h-7 bg-indigo-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-indigo-500 transition" title="Modifier l'avatar">
            <SvgIcon name="pen" className="w-4 h-4 text-white" /><input type="file" accept="image/*" class="hidden" @change="previewAvatarChange" />
          </label>
        </div>
        <p class="text-white font-semibold mt-3">{{ authUser.name }}</p>
        <p class="text-gray-400 text-sm">@{{ authUser.username }}</p>
      </div>

      <!-- Navigation -->
      <nav class="space-y-1">
        <button v-for="item in navItems" :key="item.key"
          @click="activeSection = item.key"
          :class="['w-full text-left px-3 py-2.5 rounded-xl text-sm transition flex items-center gap-3',
            activeSection === item.key ? 'bg-indigo-600/20 text-indigo-400' : 'text-gray-400 hover:bg-gray-800 hover:text-white']">
          <SvgIcon :name="item.icon" className="w-4 h-4" /> {{ item.label }}
        </button>
      </nav>
    </aside>

    <!-- Contenu -->
    <main class="flex-1 p-8 overflow-y-auto">

      <!-- Succès -->
      <div v-if="success" class="mb-6 bg-green-500/20 border border-green-500/30 rounded-xl px-4 py-3 text-green-400 text-sm flex items-center gap-2">
        <SvgIcon name="check-circle" className="w-5 h-5 text-green-400" /> {{ success }}
      </div>

      <!-- ── Profil ── -->
      <section v-if="activeSection === 'profile'">
        <h2 class="text-white text-xl font-bold mb-6">Mon profil</h2>
        <form @submit.prevent="saveProfile" class="space-y-5 max-w-lg">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-400 mb-2">Nom complet</label>
              <input v-model="profileForm.name" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" required />
              <p v-if="errors.name" class="text-red-400 text-xs mt-1">{{ errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm text-gray-400 mb-2">Nom d'utilisateur</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">@</span>
                <input v-model="profileForm.username" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition pl-7" required />
              </div>
              <p v-if="errors.username" class="text-red-400 text-xs mt-1">{{ errors.username }}</p>
            </div>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-2">Bio</label>
            <textarea v-model="profileForm.bio" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition resize-none" rows="3" placeholder="Parlez de vous…" maxlength="300"></textarea>
            <p class="text-gray-500 text-xs mt-1">{{ (profileForm.bio || '').length }}/300</p>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-2">Téléphone</label>
            <input v-model="profileForm.phone" type="tel" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" placeholder="+229 XX XX XX XX" />
          </div>
          <button type="submit" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-xl transition flex items-center gap-2">
            <SvgIcon v-if="!saving" name="save" className="w-4 h-4" />
            {{ saving ? 'Enregistrement…' : 'Enregistrer le profil' }}
          </button>
        </form>
      </section>

      <!-- ── Sécurité ── -->
      <section v-if="activeSection === 'security'">
        <h2 class="text-white text-xl font-bold mb-6">Sécurité</h2>
        <form @submit.prevent="savePassword" class="space-y-5 max-w-lg">
          <div>
            <label class="block text-sm text-gray-400 mb-2">Mot de passe actuel</label>
            <input v-model="passwordForm.current_password" type="password" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" required />
            <p v-if="errors.current_password" class="text-red-400 text-xs mt-1">{{ errors.current_password }}</p>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-2">Nouveau mot de passe</label>
            <input v-model="passwordForm.password" type="password" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" required />
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-2">Confirmer</label>
            <input v-model="passwordForm.password_confirmation" type="password" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" required />
            <p v-if="errors.password" class="text-red-400 text-xs mt-1">{{ errors.password }}</p>
          </div>
          <button type="submit" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-xl transition flex items-center gap-2">
            <SvgIcon name="lock" className="w-4 h-4" />
            {{ saving ? 'Changement…' : 'Changer le mot de passe' }}
          </button>
        </form>
      </section>

      <!-- ── Préférences ── -->
      <section v-if="activeSection === 'preferences'">
        <h2 class="text-white text-xl font-bold mb-6">Préférences</h2>
        <form @submit.prevent="savePreferences" class="space-y-6 max-w-lg">

          <!-- Thème -->
          <div>
            <label class="block text-sm text-gray-400 mb-3">Thème</label>
            <div class="grid grid-cols-3 gap-3">
              <button v-for="t in themes" :key="t.value" type="button"
                @click="prefForm.theme = t.value"
                :class="['p-3 rounded-xl border text-sm text-center transition flex flex-col items-center justify-center gap-2', prefForm.theme === t.value ? 'border-indigo-500 bg-indigo-500/20 text-indigo-400' : 'border-gray-700 text-gray-400 hover:border-gray-600']">
                <SvgIcon :name="t.icon" className="w-5 h-5 mx-auto" />
                {{ t.label }}
              </button>
            </div>
          </div>

          <!-- Toggles -->
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-800 rounded-xl">
              <div>
                <p class="text-white text-sm font-medium">Afficher mon statut en ligne</p>
                <p class="text-gray-400 text-xs">Les autres peuvent voir si vous êtes connecté</p>
              </div>
              <button type="button" @click="prefForm.show_online_status = !prefForm.show_online_status"
                :class="['w-12 h-6 rounded-full transition relative', prefForm.show_online_status ? 'bg-indigo-600' : 'bg-gray-600']">
                <span :class="['absolute top-1 w-4 h-4 bg-white rounded-full transition-all', prefForm.show_online_status ? 'left-7' : 'left-1']"></span>
              </button>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-800 rounded-xl">
              <div>
                <p class="text-white text-sm font-medium">Autoriser les appels</p>
                <p class="text-gray-400 text-xs">Recevoir des appels audio et vidéo</p>
              </div>
              <button type="button" @click="prefForm.allow_calls = !prefForm.allow_calls"
                :class="['w-12 h-6 rounded-full transition relative', prefForm.allow_calls ? 'bg-indigo-600' : 'bg-gray-600']">
                <span :class="['absolute top-1 w-4 h-4 bg-white rounded-full transition-all', prefForm.allow_calls ? 'left-7' : 'left-1']"></span>
              </button>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-800 rounded-xl">
              <div>
                <p class="text-white text-sm font-medium">Notifications</p>
                <p class="text-gray-400 text-xs">Recevoir des notifications pour les nouveaux messages</p>
              </div>
              <button type="button" @click="prefForm.notifications_enabled = !prefForm.notifications_enabled"
                :class="['w-12 h-6 rounded-full transition relative', prefForm.notifications_enabled ? 'bg-indigo-600' : 'bg-gray-600']">
                <span :class="['absolute top-1 w-4 h-4 bg-white rounded-full transition-all', prefForm.notifications_enabled ? 'left-7' : 'left-1']"></span>
              </button>
            </div>
          </div>

          <button type="submit" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-xl transition flex items-center gap-2">
            <SvgIcon name="save" className="w-4 h-4" />
            {{ saving ? 'Enregistrement…' : 'Enregistrer les préférences' }}
          </button>
        </form>
      </section>

    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import SvgIcon from '@/Components/UI/SvgIcon.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps(['authUser']);

const activeSection  = ref('profile');
const saving         = ref(false);
const success        = ref('');
const errors         = ref({});
const previewAvatar  = ref(null);
const avatarFile     = ref(null);

const navItems = [
  { key: 'profile',     icon: 'user', label: 'Profil' },
  { key: 'security',    icon: 'lock', label: 'Sécurité' },
  { key: 'preferences', icon: 'cog', label: 'Préférences' },
];

const themes = [
  { value: 'dark',   icon: 'moon', label: 'Sombre' },
  { value: 'light',  icon: 'sun', label: 'Clair' },
  { value: 'system', icon: 'computer', label: 'Système' },
];

const profileForm = ref({
  name:     props.authUser.name,
  username: props.authUser.username,
  bio:      props.authUser.bio || '',
  phone:    props.authUser.phone || '',
});

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });

const prefForm = ref({
  theme:                 props.authUser.theme || 'dark',
  show_online_status:    props.authUser.show_online_status ?? true,
  allow_calls:           props.authUser.allow_calls ?? true,
  notifications_enabled: props.authUser.notifications_enabled ?? true,
});

function previewAvatarChange(e) {
  const file = e.target.files[0];
  if (!file) return;
  avatarFile.value = file;
  previewAvatar.value = URL.createObjectURL(file);
}

async function saveProfile() {
  saving.value = true; errors.value = {};
  const data = new FormData();
  Object.entries(profileForm.value).forEach(([k, v]) => data.append(k, v || ''));
  if (avatarFile.value) data.append('avatar', avatarFile.value);
  try {
    await axios.post('/settings/profile', data);
    success.value = 'Profil mis à jour avec succès !';
    setTimeout(() => success.value = '', 4000);
  } catch (e) { errors.value = e.response?.data?.errors || {}; }
  finally { saving.value = false; }
}

async function savePassword() {
  saving.value = true; errors.value = {};
  try {
    await axios.post('/settings/password', passwordForm.value);
    success.value = 'Mot de passe modifié !';
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
    setTimeout(() => success.value = '', 4000);
  } catch (e) { errors.value = e.response?.data?.errors || {}; }
  finally { saving.value = false; }
}

async function savePreferences() {
  saving.value = true;
  try {
    await axios.post('/settings/preferences', prefForm.value);
    success.value = 'Préférences enregistrées !';
    setTimeout(() => success.value = '', 4000);
  } catch (e) { console.error(e); }
  finally { saving.value = false; }
}
</script>

<style scoped>
.input-field {
    width: 100%;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px 16px;
    color: var(--text-primary);
    transition: border-color 0.2s;
    outline: none;
}

.input-field:focus {
    border-color: #6366f1;
}

.btn-primary {
    background: #6366f1;
    color: white;
    font-weight: 600;
    padding: 12px 24px;
    border-radius: 12px;
    transition: background 0.2s;
    border: none;
    cursor: pointer;
}

.btn-primary:hover {
    background: #5558e3;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
