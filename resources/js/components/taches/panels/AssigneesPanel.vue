<!-- resources/js/components/taches/panels/AssigneesPanel.vue -->
<template>
  <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-6 space-y-5">

    <!-- Responsable section -->
    <div>
      <div class="flex items-center justify-between mb-3">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
          Responsable
        </h4>
        <div class="flex items-center gap-2">
          <button
            v-if="tache?.responsable && permissions?.can_update && !showResponsablePicker"
            @click="startRemoveResponsable"
            :disabled="savingResponsable"
            class="text-xs text-error-500 hover:text-error-600 dark:text-error-400 disabled:opacity-50"
            title="Retirer le responsable"
          >
            Retirer
          </button>
          <button
            v-if="permissions?.can_update && !showResponsablePicker"
            @click="showResponsablePicker = true"
            class="text-xs text-brand-500 hover:text-brand-600 dark:text-brand-400"
          >
            {{ tache?.responsable ? 'Changer' : 'Assigner' }}
          </button>
          <button
            v-if="showResponsablePicker"
            @click="showResponsablePicker = false"
            class="text-xs text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300"
          >
            Annuler
          </button>
        </div>
      </div>

      <!-- Current responsable -->
      <div v-if="tache?.responsable && !showResponsablePicker" class="flex items-center gap-3 p-3 rounded-3 border border-brand-200 bg-brand-50 dark:border-brand-500/30 dark:bg-brand-500/10">
        <div class="relative shrink-0">
          <div v-if="tache.responsable.avatar" class="w-9 h-9 rounded-full overflow-hidden">
            <img :src="tache.responsable.avatar" :alt="tache.responsable.nom" class="w-full h-full object-cover" />
          </div>
          <div v-else class="w-9 h-9 rounded-full flex items-center justify-center font-semibold text-white text-sm"
               :style="{ backgroundColor: getColorFromName(tache.responsable.nom) }">
            {{ getInitials(tache.responsable.nom) }}
          </div>
          <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-brand-500 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-800">
            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ tache.responsable.nom }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ tache.responsable.email }}</p>
        </div>
      </div>

      <!-- No responsable -->
      <div v-else-if="!tache?.responsable && !showResponsablePicker" class="text-xs text-gray-400 dark:text-gray-500 italic">
        Aucun responsable assigné
      </div>

      <!-- Picker -->
      <div v-if="showResponsablePicker" class="flex items-center gap-2">
        <select
          v-model="selectedResponsableId"
          class="flex-1 rounded-[4px] border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        >
          <option value="">— Choisir un membre —</option>
          <option v-for="a in assignees" :key="a.id" :value="a.id">
            {{ a.nom }}
          </option>
        </select>
        <button
          @click="saveResponsable"
          :disabled="!selectedResponsableId || savingResponsable"
          class="inline-flex items-center gap-1.5 rounded-[4px] bg-brand-500 px-3 py-2 text-xs font-medium text-white hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="savingResponsable" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Confirmer
        </button>
      </div>

      <!-- Error -->
      <p v-if="responsableError" class="mt-2 text-xs text-error-500">{{ responsableError }}</p>
    </div>

    <!-- Divider -->
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    <!-- Assignees section -->
    <div>
      <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3 flex items-center justify-between">
        Équipe
        <span class="font-normal normal-case text-gray-400">{{ assignees?.length || 0 }}</span>
      </h4>

      <div v-if="assignees && assignees.length > 0" class="space-y-2">
        <div
          v-for="assignee in assignees"
          :key="assignee.id"
          class="flex items-center gap-3 p-3 rounded-3 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors group"
        >
          <!-- Avatar -->
          <div class="relative shrink-0">
            <div v-if="assignee.avatar" class="w-9 h-9 rounded-full overflow-hidden">
              <img :src="assignee.avatar" :alt="assignee.nom" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-9 h-9 rounded-full flex items-center justify-center font-semibold text-white text-sm"
                 :style="{ backgroundColor: getColorFromName(assignee.nom) }">
              {{ getInitials(assignee.nom) }}
            </div>

            <!-- Responsable star badge -->
            <div v-if="assignee.id === tache?.responsable_id"
                 class="absolute -bottom-1 -right-1 w-4 h-4 bg-brand-500 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-800"
                 title="Responsable">
              <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ assignee.nom }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ assignee.email }}</p>

            <div v-if="assignee.pivot" class="flex flex-wrap gap-1 mt-1">
              <span v-if="assignee.pivot.can_edit"
                    class="inline-flex items-center px-1 py-0.5 text-[10px] font-medium bg-brand-50 text-brand-500 border border-brand-200 rounded-1"
                    title="Peut modifier">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </span>
              <span v-if="assignee.pivot.can_complete"
                    class="inline-flex items-center px-1 py-0.5 text-[10px] font-medium bg-success-50 text-success-500 border border-success-300 rounded-1"
                    title="Peut compléter">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <span v-if="assignee.pivot.can_validate"
                    class="inline-flex items-center px-1 py-0.5 text-[10px] font-medium bg-purple-50 text-purple-500 border border-purple-200 rounded-1"
                    title="Peut valider">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </span>
            </div>
          </div>

          <!-- Contact action -->
          <div class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click="$emit('contact', assignee)"
              class="p-1.5 text-gray-400 hover:text-brand-500 dark:hover:text-brand-400 rounded-3 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors"
              title="Contacter">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-6">
        <div class="w-12 h-12 mx-auto mb-2 bg-gray-100 dark:bg-gray-900 rounded-full flex items-center justify-center">
          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucun membre assigné</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/api/axios'

const props = defineProps({
  tache: { type: Object, default: null },
  assignees: { type: Array, default: () => [] },
  permissions: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['contact', 'updated'])

const showResponsablePicker = ref(false)
const selectedResponsableId = ref('')
const savingResponsable = ref(false)
const responsableError = ref('')

const saveResponsable = async () => {
  if (!selectedResponsableId.value || !props.tache?.id) return
  savingResponsable.value = true
  responsableError.value = ''
  try {
    const { data } = await api.post(`/taches/${props.tache.id}/assign-responsable`, {
      responsable_id: selectedResponsableId.value
    })
    emit('updated', data.data)
    showResponsablePicker.value = false
    selectedResponsableId.value = ''
  } catch (err) {
    responsableError.value = err.response?.data?.message || 'Erreur lors de l\'assignation'
  } finally {
    savingResponsable.value = false
  }
}

const startRemoveResponsable = async () => {
  if (!props.tache?.id) return
  savingResponsable.value = true
  responsableError.value = ''
  try {
    const { data } = await api.delete(`/taches/${props.tache.id}/remove-responsable`)
    emit('updated', data.data)
  } catch (err) {
    responsableError.value = err.response?.data?.message || 'Erreur lors du retrait'
  } finally {
    savingResponsable.value = false
  }
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().substring(0, 2)
}

const getColorFromName = (name) => {
  if (!name) return '#6B7280'
  const colors = ['#3B82F6','#8B5CF6','#EC4899','#F59E0B','#10B981','#06B6D4','#6366F1','#F97316']
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return colors[Math.abs(hash) % colors.length]
}
</script>
