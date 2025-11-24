<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 backdrop-blur-sm"
       @click.self="$emit('close')">
    
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg">
      
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-red-500 to-red-600">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-bold text-white">
              Rejeter le résultat
            </h2>
            <p class="text-red-100 text-sm">
              Validation {{ level.toUpperCase() }}
            </p>
          </div>
        </div>
      </div>

      <!-- Body -->
      <form @submit.prevent="handleReject" class="p-6 space-y-5">
        
        <!-- Info résultat -->
        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm">
              <p class="text-gray-600 dark:text-gray-400 mb-2">
                Vous êtes sur le point de rejeter ce résultat. L'utilisateur devra le corriger et le soumettre à nouveau.
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-500">
                Taux de réalisation : <span class="font-semibold">{{ resultat.taux_realisation }}%</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Motif du rejet -->
        <div>
          <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
            Motif du rejet <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="commentaire"
            rows="5"
            required
            placeholder="Expliquez clairement pourquoi vous rejetez ce résultat et ce qui doit être corrigé..."
            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-900 dark:text-white transition-all"
          ></textarea>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            💡 Soyez constructif : indiquez précisément ce qui doit être amélioré
          </p>
        </div>

        <!-- Warning -->
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg">
          <div class="flex gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-sm">
              <p class="font-semibold text-red-800 dark:text-red-300 mb-1">
                Action irréversible
              </p>
              <p class="text-red-700 dark:text-red-400">
                Cette action notifiera l'utilisateur et réinitialisera le statut de validation.
              </p>
            </div>
          </div>
        </div>
      </form>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
        <button
          type="button"
          @click="$emit('close')"
          :disabled="rejecting"
          class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all disabled:opacity-50"
        >
          Annuler
        </button>

        <button
          @click="handleReject"
          :disabled="!commentaire.trim() || rejecting"
          class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
        >
          <svg v-if="rejecting" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ rejecting ? 'Rejet en cours...' : '❌ Confirmer le rejet' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/api/axios'

const props = defineProps({
  resultat: {
    type: Object,
    required: true
  },
  level: {
    type: String,
    required: true,
    validator: (value) => ['n1', 'n2'].includes(value)
  }
})

const emit = defineEmits(['close', 'rejected'])

const commentaire = ref('')
const rejecting = ref(false)

const handleReject = async () => {
  if (!commentaire.value.trim() || rejecting.value) return

  rejecting.value = true

  try {
    await api.post(`/taches/${props.resultat.tache_id}/resultats/${props.resultat.id}/reject`, {
      commentaire: commentaire.value,
      level: props.level
    })

    emit('rejected')
    emit('close')
  } catch (error) {
    console.error('Error rejecting resultat:', error)
    alert(error.response?.data?.message || 'Erreur lors du rejet')
  } finally {
    rejecting.value = false
  }
}
</script>