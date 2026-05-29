<template>
  <Teleport to="body">
    <div v-if="visible" class="confirm-overlay" @click.self="cancel">
      <div class="confirm-modal">

        <!-- Icône -->
        <div class="text-center mb-5">
          <div :class="['w-16 h-16 rounded-full flex items-center justify-center text-3xl mx-auto mb-4', iconClass]">
            {{ options.icon ?? '🚪' }}
          </div>
          <h3 class="font-bold text-lg" :style="{ color: 'var(--text-primary)' }">
            {{ options.title ?? 'Confirmer' }}
          </h3>
          <p class="text-sm mt-2" :style="{ color: 'var(--text-secondary)' }">
            {{ options.message ?? 'Êtes-vous sûr ?' }}
          </p>
        </div>

        <!-- Boutons -->
        <div class="flex gap-3">
          <button @click="cancel"
            class="flex-1 py-3 rounded-xl font-semibold text-sm transition"
            :style="{ background: 'var(--bg-tertiary)', color: 'var(--text-primary)' }">
            {{ options.cancelLabel ?? 'Annuler' }}
          </button>
          <button @click="confirm"
            :class="['flex-1 py-3 rounded-xl font-semibold text-sm text-white transition', confirmClass]">
            {{ options.confirmLabel ?? 'Confirmer' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';

const visible  = ref(false);
const options  = ref({});
let resolveFn  = null;

const iconClass = computed(() => ({
  danger:  'bg-red-500/20',
  warning: 'bg-yellow-500/20',
  info:    'bg-indigo-500/20',
}[options.value.variant ?? 'danger'] ?? 'bg-red-500/20'));

const confirmClass = computed(() => ({
  danger:  'bg-red-500 hover:bg-red-400',
  warning: 'bg-yellow-500 hover:bg-yellow-400',
  info:    'bg-indigo-600 hover:bg-indigo-500',
}[options.value.variant ?? 'danger'] ?? 'bg-red-500 hover:bg-red-400'));

function open(opts = {}) {
  options.value = opts;
  visible.value = true;
  return new Promise(resolve => { resolveFn = resolve; });
}

function confirm() {
  visible.value = false;
  resolveFn?.(true);
}

function cancel() {
  visible.value = false;
  resolveFn?.(false);
}

defineExpose({ open });
</script>