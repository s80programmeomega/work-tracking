<!-- resources/js/components/layout/header/NotificationsStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques des notifications</h3>
      <button
        @click="$emit('close')"
        class="rounded-3 p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <SkeletonLoader v-if="loading" type="stats" :cols="4" />

    <div v-else class="space-y-6">
      <!-- Overview cards -->
      <div ref="staggerRef" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="stagger-item rounded-3 border border-brand-200 dark:border-brand-800/50 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-3 bg-brand-500">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            </div>
            <div>
              <p class="text-xs font-medium text-brand-600 dark:text-brand-400">Total</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ statistics.total || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="stagger-item rounded-3 border border-warning-200 dark:border-warning-800/50 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-3 bg-warning-500">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
            </div>
            <div>
              <p class="text-xs font-medium text-warning-600 dark:text-warning-400">Non lues</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ statistics.unread || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="stagger-item rounded-3 border border-success-200 dark:border-success-800/50 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-3 bg-success-500">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
            </div>
            <div>
              <p class="text-xs font-medium text-success-600 dark:text-success-400">Lues aujourd'hui</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ statistics.read_today || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="stagger-item rounded-3 border border-purple-200 dark:border-purple-800/50 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-3 bg-purple-500">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125z"/></svg>
            </div>
            <div>
              <p class="text-xs font-medium text-purple-600 dark:text-purple-400">Catégories</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ Object.keys(statistics.by_type || {}).length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Read rate bar -->
      <div v-if="statistics.total > 0" class="rounded-3 border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Taux de lecture</span>
          <span class="text-sm font-bold text-success-600 dark:text-success-400 counter-pop">{{ readRate }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
          <div
            class="h-2.5 rounded-full bg-success-500 transition-all duration-700 ease-out"
            :style="{ width: `${readRate}%` }"
          ></div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
          <span>{{ (statistics.total || 0) - (statistics.unread || 0) }} lues</span>
          <span>{{ statistics.unread || 0 }} non lues</span>
        </div>
      </div>

      <!-- By type breakdown -->
      <div v-if="Object.keys(statistics.by_type || {}).length > 0"
        class="rounded-3 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-5">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Répartition par type</h4>
        <div class="space-y-2">
          <div
            v-for="(count, type) in statistics.by_type"
            :key="type"
            class="flex items-center justify-between"
          >
            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ friendlyType(type) }}</span>
            <div class="flex items-center gap-3">
              <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                  class="h-full bg-brand-500 transition-all duration-500"
                  :style="{ width: `${Math.round((count / statistics.total) * 100)}%` }"
                ></div>
              </div>
              <span class="w-6 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ count }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'
import { useStagger } from '@/composables/useAnimations'

const props = defineProps({
  statistics: { type: Object, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(60)

const readRate = computed(() => {
  if (!props.statistics.total) { return 0 }
  const read = props.statistics.total - (props.statistics.unread || 0)
  return Math.round((read / props.statistics.total) * 100)
})

const TYPE_LABELS = {
  task_assigned: 'Tâche assignée',
  task_updated: 'Tâche mise à jour',
  task_completed: 'Tâche terminée',
  task_due_soon: 'Échéance proche',
  resultat_soumis: 'Résultat soumis',
  resultat_valide_n1: 'Validé N1',
  resultat_valide_n2: 'Validé N2',
  resultat_rejete: 'Résultat rejeté',
  workspace_invitation: 'Invitation workspace',
  projet_invitation: 'Invitation projet',
  tache_statut_auto_changed: 'Statut automatique',
  document_uploaded: 'Document ajouté',
  comment_added: 'Commentaire',
  mentioned_in_comment: 'Mention',
}

function friendlyType(type) {
  return TYPE_LABELS[type] || type.replace(/_/g, ' ')
}

onMounted(() => applyStagger())
</script>
