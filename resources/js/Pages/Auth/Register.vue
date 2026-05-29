<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-950 p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="text-6xl mb-3">💬</div>
        <h1 class="text-3xl font-bold text-white">Créer un compte</h1>
        <p class="text-gray-400 mt-1">Rejoignez Messenger</p>
      </div>

      <div class="bg-gray-900 rounded-2xl p-8 border border-gray-800 shadow-2xl">
        <form @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Nom complet</label>
              <input v-model="form.name" type="text" required placeholder="Jean Dupont"
                class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" />
              <p v-if="errors.name" class="text-red-400 text-xs mt-1">{{ errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Nom d'utilisateur</label>
              <input v-model="form.username" type="text" required placeholder="jean_dupont"
                class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" />
              <p v-if="errors.username" class="text-red-400 text-xs mt-1">{{ errors.username }}</p>
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
            <input v-model="form.email" type="email" required placeholder="votre@email.com"
              class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" />
            <p v-if="errors.email" class="text-red-400 text-xs mt-1">{{ errors.email }}</p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>
            <input v-model="form.password" type="password" required placeholder="8 caractères minimum"
              class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" />
            <p v-if="errors.password" class="text-red-400 text-xs mt-1">{{ errors.password }}</p>
          </div>

          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">Confirmer le mot de passe</label>
            <input v-model="form.password_confirmation" type="password" required placeholder="Répéter le mot de passe"
              class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition" />
          </div>

          <button type="submit" :disabled="loading"
            class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition">
            {{ loading ? 'Création…' : 'Créer mon compte' }}
          </button>
        </form>

        <div class="mt-6 text-center text-gray-400 text-sm">
          Déjà un compte ?
          <a href="/login" class="text-indigo-400 hover:text-indigo-300 font-medium">Se connecter</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const errors = ref(usePage().props.errors || {});
const loading = ref(false);
const form = ref({ name:'', username:'', email:'', password:'', password_confirmation:'' });

function submit() {
  loading.value = true;
  router.post('/register', form.value, {
    onError: (e) => { errors.value = e; },
    onFinish: () => { loading.value = false; },
  });
}
</script>
