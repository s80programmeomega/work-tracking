<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 backdrop-blur-sm"
       @click.self="$emit('close')">
    
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
      
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-brand-500 to-brand-600">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-white">
            {{ isEditing ? 'Modifier le résultat' : 'Nouveau résultat' }}
          </h2>
          <button @click="$emit('close')" class="text-white hover:text-gray-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <p class="text-brand-100 text-sm mt-1">{{ tache.titre }}</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto p-6 space-y-6">
        
        <!-- Résultats attendus -->
        <div>
          <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
            📋 Résultats attendus <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.resultats_attendus"
            rows="4"
            required
            placeholder="Décrivez les résultats qui étaient attendus pour cette tâche..."
            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-900 dark:text-white transition-all"
          ></textarea>
        </div>

        <!-- Résultats obtenus -->
        <div>
          <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
            ✅ Résultats obtenus <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.resultats_obtenus"
            rows="4"
            required
            placeholder="Décrivez les résultats que vous avez réellement obtenus..."
            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-900 dark:text-white transition-all"
          ></textarea>
        </div>

        <!-- Taux de réalisation -->
        <div>
          <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
            📊 Taux de réalisation <span class="text-red-500">*</span>
          </label>
          <div class="space-y-3">
            <input
              v-model.number="form.taux_realisation"
              type="range"
              min="0"
              max="100"
              step="5"
              class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-brand-500"
            />
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-3">
                <input
                  v-model.number="form.taux_realisation"
                  type="number"
                  min="0"
                  max="100"
                  class="w-20 px-3 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-center font-bold text-lg dark:bg-gray-900 dark:text-white"
                />
                <span class="text-2xl font-bold text-gray-900 dark:text-white">%</span>
              </div>
              <div class="px-4 py-2 rounded-lg font-semibold"
                   :class="getProgressColorClass(form.taux_realisation)">
                {{ getProgressLabel(form.taux_realisation) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Difficultés rencontrées -->
        <div>
          <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
            ⚠️ Difficultés rencontrées
          </label>
          <textarea
            v-model="form.difficultes_rencontrees"
            rows="3"
            placeholder="Décrivez les difficultés ou obstacles rencontrés (optionnel)..."
            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-900 dark:text-white transition-all"
          ></textarea>
        </div>

        <!-- Solutions envisagées -->
        <div>
          <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
            💡 Solutions envisagées
          </label>
          <textarea
            v-model="form.solutions_envisagees"
            rows="3"
            placeholder="Décrivez les solutions que vous avez mises en place ou envisagées (optionnel)..."
            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-900 dark:text-white transition-all"
          ></textarea>
        </div>

        <!-- Observations -->
        <div>
          <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
            💬 Observations supplémentaires
          </label>
          <textarea
            v-model="form.observations"
            rows="3"
            placeholder="Ajoutez des observations ou commentaires supplémentaires (optionnel)..."
            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-900 dark:text-white transition-all"
          ></textarea>
        </div>

        <!-- Info helper -->
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-lg">
          <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-300">
              <p class="font-semibold mb-1">💡 Conseil</p>
              <p>Soyez précis et factuel dans vos descriptions. Ces informations seront utilisées pour l'évaluation et peuvent servir de référence future.</p>
            </div>
          </div>
        </div>
      </form>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center gap-3">
        <button
          type="button"
          @click="$emit('close')"
          class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all"
        >
          Annuler
        </button>

        <div class="flex gap-3">
          <button
            v-if="!isEditing"
            @click="handleSubmit(false)"
            :disabled="saving || !isFormValid"
            type="button"
            class="px-5 py-2.5 border-2 border-brand-500 text-brand-600 dark:text-brand-400 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-900/20 font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            💾 Enregistrer le brouillon
          </button>

          <button
            @click="handleSubmit(true)"
            :disabled="saving || !isFormValid"
            type="button"
            class="px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
          >
            <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ saving ? 'Enregistrement...' : (isEditing ? '✓ Enregistrer' : '🚀 Enregistrer et soumettre') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/api/axios'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  resultat: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const saving = ref(false)
const isEditing = computed(() => !!props.resultat)

const form = reactive({
  resultats_attendus: '',
  resultats_obtenus: '',
  taux_realisation: 0,
  difficultes_rencontrees: '',
  solutions_envisagees: '',
  observations: ''
})

const isFormValid = computed(() => {
  return form.resultats_attendus.trim() !== '' &&
         form.resultats_obtenus.trim() !== '' &&
         form.taux_realisation >= 0 &&
         form.taux_realisation <= 100
})

const handleSubmit = async (shouldSubmit = true) => {
  if (!isFormValid.value || saving.value) return

  saving.value = true

  try {
    const payload = {
      ...form,
      submit: shouldSubmit && !isEditing.value
    }

    let response
    if (isEditing.value) {
      response = await api.put(`/taches/${props.tache.id}/resultats/${props.resultat.id}`, payload)
    } else {
      response = await api.post(`/taches/${props.tache.id}/resultats`, payload)
    }

    emit('saved', response.data.data)
    emit('close')
  } catch (error) {
    console.error('Error saving resultat:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

const getProgressColorClass = (value) => {
  if (value >= 90) return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
  if (value >= 75) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  if (value >= 50) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300'
  return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
}

const getProgressLabel = (value) => {
  if (value >= 90) return 'Excellent'
  if (value >= 75) return 'Très bien'
  if (value >= 50) return 'Satisfaisant'
  if (value >= 25) return 'Insuffisant'
  return 'Faible'
}

onMounted(() => {
  if (props.resultat) {
    Object.assign(form, {
      resultats_attendus: props.resultat.resultats_attendus || '',
      resultats_obtenus: props.resultat.resultats_obtenus || '',
      taux_realisation: props.resultat.taux_realisation || 0,
      difficultes_rencontrees: props.resultat.difficultes_rencontrees || '',
      solutions_envisagees: props.resultat.solutions_envisagees || '',
      observations: props.resultat.observations || ''
    })
  }
})
</script>