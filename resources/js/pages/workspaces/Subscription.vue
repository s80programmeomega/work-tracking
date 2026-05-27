<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Abonnement'" />
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Abonnement</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Statut et limites de votre workspace</p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="rounded-3 border border-error-300 bg-error-50 dark:bg-error-500/10 p-5 text-sm text-error-500">
        {{ error }}
      </div>

      <template v-else-if="summary">
        <!-- Status card -->
        <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/[0.03] p-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-3 flex items-center justify-center"
                   :class="summary.subscription_mode === 'paid' ? 'bg-success-50' : summary.trial_expired ? 'bg-error-50' : 'bg-warning-50'">
                <svg class="w-6 h-6" :class="summary.subscription_mode === 'paid' ? 'text-success-500' : summary.trial_expired ? 'text-error-500' : 'text-warning-500'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-base font-semibold text-gray-900 dark:text-white">Plan actuel</span>
                  <SubscriptionBadge :mode="summary.subscription_mode" />
                </div>
                <p v-if="summary.subscription_mode === 'trial' && !summary.trial_expired" class="text-sm text-gray-500 dark:text-gray-400">
                  {{ summary.remaining_trial_days }} jour{{ summary.remaining_trial_days !== 1 ? 's' : '' }} restant{{ summary.remaining_trial_days !== 1 ? 's' : '' }}
                  <span v-if="summary.expiring_soon" class="ml-1 text-warning-500 font-medium">— expire bientôt</span>
                </p>
                <p v-else-if="summary.trial_expired" class="text-sm text-error-500 font-medium">Période d'essai expirée</p>
                <p v-else-if="summary.subscription_mode === 'paid'" class="text-sm text-gray-500 dark:text-gray-400">Accès complet activé</p>
              </div>
            </div>

            <a
              href="mailto:teams@cerdafrica.org?subject=Upgrade workspace"
              class="inline-flex items-center gap-2 rounded-[4px] bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
              Passer au plan payant
            </a>
          </div>
        </div>

        <!-- Trial info (if on trial) -->
        <div v-if="summary.subscription_mode === 'trial'" class="rounded-3 border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/[0.03] p-6">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Période d'essai</h2>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-3 border border-gray-200 dark:border-gray-700 p-4 text-center">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.trial_duration_days }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Durée totale (jours)</p>
            </div>
            <div class="rounded-3 border dark:border-gray-700 p-4 text-center"
                 :class="summary.trial_expired ? 'border-error-300 bg-error-50 dark:bg-error-500/10' : summary.expiring_soon ? 'border-warning-300 bg-warning-50 dark:bg-warning-500/10' : 'border-gray-200'">
              <p class="text-2xl font-bold" :class="summary.trial_expired ? 'text-error-500' : summary.expiring_soon ? 'text-warning-500' : 'text-gray-900 dark:text-white'">
                {{ summary.trial_expired ? '0' : summary.remaining_trial_days }}
              </p>
              <p class="text-xs mt-1" :class="summary.trial_expired ? 'text-error-500' : 'text-gray-500 dark:text-gray-400'">Jours restants</p>
            </div>
            <div class="rounded-3 border border-gray-200 dark:border-gray-700 p-4 text-center">
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(summary.trial_started_at) }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Date de début</p>
            </div>
          </div>
        </div>

        <!-- Usage limits -->
        <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/[0.03] p-6">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Limites d'utilisation</h2>
          <div class="space-y-5">

            <!-- Members -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <span class="text-sm text-gray-700 dark:text-gray-300">Membres</span>
                </div>
                <span class="text-sm font-medium" :class="summary.limits.members.reached ? 'text-error-500' : 'text-gray-900 dark:text-white'">
                  {{ summary.limits.members.current }} / {{ summary.limits.members.max }}
                </span>
              </div>
              <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-1 overflow-hidden">
                <div
                  class="h-full rounded-1 transition-all"
                  :class="summary.limits.members.reached ? 'bg-error-500' : 'bg-brand-500'"
                  :style="{ width: Math.min(100, (summary.limits.members.current / summary.limits.members.max) * 100) + '%' }"
                ></div>
              </div>
              <p v-if="summary.limits.members.reached" class="mt-1 text-xs text-error-500">Limite atteinte — passez au plan payant pour ajouter plus de membres.</p>
            </div>

            <!-- File size -->
            <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Taille max par fichier</span>
              </div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">{{ summary.limits.file_size_mb }} Mo</span>
            </div>

            <!-- Storage -->
            <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">Stockage total</span>
              </div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">{{ summary.limits.storage_mb }} Mo</span>
            </div>
          </div>
        </div>

        <!-- Upgrade CTA -->
        <div class="rounded-3 border border-brand-200 bg-brand-50 dark:border-brand-500/30 dark:bg-brand-500/10 p-6">
          <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
              <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Besoin de plus ?</h2>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Contactez-nous pour débloquer des membres illimités, des fichiers plus grands et plus de stockage.
              </p>
            </div>
            <a
              href="mailto:teams@cerdafrica.org?subject=Demande d'upgrade - Work Tracking"
              class="shrink-0 inline-flex items-center gap-2 rounded-[4px] border border-brand-500 px-4 py-2 text-sm font-medium text-brand-500 hover:bg-brand-500 hover:text-white transition-colors"
            >
              Contacter l'équipe
            </a>
          </div>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import SubscriptionBadge from '@/components/admin/SubscriptionBadge.vue'
import api from '@/api/axios'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const summary = ref(null)

const formatDate = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

onMounted(async () => {
  const workspaceId = route.params.id
  try {
    const { data } = await api.get(`/workspaces/${workspaceId}/subscription`)
    summary.value = data.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Impossible de charger les informations d\'abonnement.'
  } finally {
    loading.value = false
  }
})
</script>
