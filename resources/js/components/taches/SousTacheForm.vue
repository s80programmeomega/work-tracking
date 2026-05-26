<template>
  <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-dashed border-gray-300 dark:border-gray-600">
    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
      <i class="fas fa-plus-circle text-brand-600"></i>
      Nouvelle sous-tâche
    </h4>

    <form @submit.prevent="handleSubmit" class="space-y-3">
      <!-- Titre -->
      <div>
        <input
          v-model="form.titre"
          type="text"
          required
          dusk="soustache-form-titre"
          placeholder="Titre de la sous-tâche…"
          class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
        />
        <p v-if="errors.titre" class="mt-1 text-xs text-red-600">{{ errors.titre[0] }}</p>
      </div>

      <!-- Description -->
      <div>
        <textarea
          v-model="form.description"
          rows="2"
          placeholder="Description (optionnel)…"
          class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"
        ></textarea>
      </div>

      <!-- Responsable -->
      <div v-if="members.length > 0">
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Responsable (optionnel)</label>
        <select
          v-model="form.responsable_id"
          class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
        >
          <option :value="null">— Aucun —</option>
          <option v-for="m in members" :key="m.id" :value="m.id">{{ m.nom || m.name }}</option>
        </select>
      </div>

      <!-- Row: poids + date_echeance -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
            Poids (%) — restant: {{ remainingPoids }}%
          </label>
          <input
            v-model.number="form.poids"
            type="number"
            min="0"
            max="100"
            placeholder="0"
            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
          />
          <p v-if="errors.poids" class="mt-1 text-xs text-red-600">{{ errors.poids[0] }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
            Échéance
            <span v-if="parentEcheance" class="text-gray-400">(max: {{ formatDate(parentEcheance) }})</span>
          </label>
          <input
            v-model="form.date_echeance"
            type="date"
            :max="parentEcheance"
            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
          />
          <p v-if="errors.date_echeance" class="mt-1 text-xs text-red-600">{{ errors.date_echeance[0] }}</p>
        </div>
      </div>

      <!-- Validation options -->
      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer">
          <input v-model="form.necessite_validation" type="checkbox" class="rounded text-brand-600" />
          Nécessite validation
        </label>
        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer">
          <input v-model="form.bloque_progression" type="checkbox" class="rounded text-brand-600" />
          Bloque la progression
        </label>
      </div>

      <!-- Error banner -->
      <div v-if="globalError" class="text-xs text-red-600 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">
        {{ globalError }}
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-2 pt-1">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
        >
          Annuler
        </button>
        <button
          type="submit"
          :disabled="loading"
          dusk="soustache-form-submit"
          class="px-4 py-2 text-sm bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <i v-if="loading" class="fas fa-spinner fa-spin mr-1"></i>
          <i v-else class="fas fa-plus mr-1"></i>
          Créer
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    tacheId: { type: Number, required: true },
    parentEcheance: { type: String, default: null },
    totalPoids: { type: Number, default: 0 },
    loading: { type: Boolean, default: false },
    members: { type: Array, default: () => [] },
})

const emit = defineEmits(['submit', 'cancel'])

const form = ref({
    titre: '',
    description: '',
    responsable_id: null,
    poids: 0,
    date_echeance: '',
    necessite_validation: false,
    bloque_progression: false,
})

const errors = ref({})
const globalError = ref(null)

const remainingPoids = computed(() => Math.max(0, 100 - props.totalPoids))

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

const handleSubmit = () => {
    errors.value = {}
    globalError.value = null

    if (!form.value.titre.trim()) {
        errors.value.titre = ['Le titre est requis.']
        return
    }

    if (form.value.poids > remainingPoids.value + form.value.poids) {
        errors.value.poids = ['Ce poids dépasse la capacité disponible.']
        return
    }

    emit('submit', { ...form.value })
}

const setErrors = (serverErrors) => {
    if (serverErrors && typeof serverErrors === 'object') {
        errors.value = serverErrors
    } else {
        globalError.value = serverErrors
    }
}

const reset = () => {
    form.value = {
        titre: '',
        description: '',
        responsable_id: null,
        poids: 0,
        date_echeance: '',
        necessite_validation: false,
        bloque_progression: false,
    }
    errors.value = {}
    globalError.value = null
}

defineExpose({ setErrors, reset })
</script>
