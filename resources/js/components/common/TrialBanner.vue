<!-- resources/js/components/common/TrialBanner.vue -->
<template>
  <transition name="slide-down">
    <div
      v-if="visible"
      :class="[
        'flex items-center gap-3 px-4 py-2.5 text-sm font-medium',
        isExpired
          ? 'bg-red-600 text-white'
          : 'bg-amber-500 text-white',
      ]"
      role="alert"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
      </svg>

      <span class="flex-1">
        <template v-if="isExpired">
          {{ $t('subscription.trial.expired') }}
        </template>
        <template v-else>
          {{ $t('subscription.trial.banner', { days: remainingDays }) }}
        </template>
      </span>

      <button
        class="underline underline-offset-2 hover:opacity-80 transition-opacity shrink-0"
        @click="dismiss"
      >
        {{ $t('subscription.trial.expiring_soon') }}
      </button>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import api from '@/api/axios';
import { useWorkspace } from '@/composables/useWorkspace';

const { currentWorkspace } = useWorkspace();

const summary = ref(null);
const dismissed = ref(false);

const isExpired = computed(() => summary.value?.trial_expired === true);
const remainingDays = computed(() => summary.value?.remaining_trial_days ?? 0);

const visible = computed(() => {
  if (dismissed.value) return false;
  if (!summary.value) return false;
  if (summary.value.subscription_mode !== 'trial') return false;
  return isExpired.value || summary.value.expiring_soon === true;
});

const dismiss = () => { dismissed.value = true; };

const fetchSummary = async (workspaceId) => {
  if (!workspaceId) return;
  try {
    const { data } = await api.get(`/workspaces/${workspaceId}/subscription`);
    summary.value = data.data;
  } catch {
    // silently ignore — banner is best-effort
  }
};

onMounted(() => {
  if (currentWorkspace.value?.id) {
    fetchSummary(currentWorkspace.value.id);
  }
});

watch(() => currentWorkspace.value?.id, (newId) => {
  dismissed.value = false;
  summary.value = null;
  if (newId) { fetchSummary(newId); }
});
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-100%);
}
</style>
