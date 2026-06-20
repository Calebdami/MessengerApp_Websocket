<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-950 p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="text-6xl mb-3">💬</div>
        <h1 class="text-3xl font-bold text-white">Messenger</h1>
        <p class="text-gray-400 mt-1">Connectez-vous à votre compte</p>
      </div>

      <!-- Card -->
      <div class="bg-gray-900 rounded-2xl p-8 border border-gray-800 shadow-2xl">
        <form @submit.prevent="submit">
          <!-- Email -->
          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
            <input
              v-model="form.email" type="email" required autocomplete="email"
              placeholder="votre@email.com"
              class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
            />
            <p v-if="errors.email" class="text-red-400 text-xs mt-1">{{ errors.email }}</p>
          </div>

          <!-- Password -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>
            <div class="relative">
              <input
                v-model="form.password" :type="showPassword ? 'text' : 'password'" required
                placeholder="••••••••"
                class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 pr-12 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
              />
              <button type="button" @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white flex items-center justify-center" :title="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
              <SvgIcon :name="showPassword ? 'eye-off' : 'eye'" className="w-5 h-5" />
            </button>
            </div>
            <p v-if="errors.password" class="text-red-400 text-xs mt-1">{{ errors.password }}</p>
          </div>

          <!-- Remember -->
          <div class="flex items-center justify-between mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.remember" type="checkbox" class="rounded border-gray-600 bg-gray-800 text-indigo-500" />
              <span class="text-sm text-gray-400">Se souvenir de moi</span>
            </label>
          </div>

          <!-- Submit -->
          <button type="submit" :disabled="loading"
            class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
            <span v-if="loading" class="animate-spin">⟳</span>
            {{ loading ? 'Connexion…' : 'Se connecter' }}
          </button>
        </form>

        <div class="mt-6 text-center text-gray-400 text-sm">
          Pas encore de compte ?
          <a href="/register" class="text-indigo-400 hover:text-indigo-300 font-medium">S'inscrire</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import SvgIcon from '@/Components/UI/SvgIcon.vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const errors = ref(page.props.errors || {});
const loading = ref(false);
const showPassword = ref(false);

const form = ref({ email: '', password: '', remember: false });

function submit() {
  loading.value = true;
  router.post('/login', form.value, {
    onError: (e) => { errors.value = e; },
    onFinish: () => { loading.value = false; },
  });
}
</script>
