<!-- resources\js\components\taches\tacheDetail\TacheDetailsTab.vue -->
<template>
  <div class="space-y-6">
    <!-- Informations principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Statut et progression -->
      <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <i class="fas fa-tasks text-brand-600"></i>
          Statut et progression
        </h3>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Statut</label>
            <div v-if="editing.field === 'statut'">
              <select
                v-model="editing.value"
                @change="saveEdit"
                @blur="cancelEdit"
                @keydown.escape="cancelEdit"
                autofocus
                class="w-full px-3 py-1.5 text-sm border border-brand-500 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
              >
                <option value="a_faire">À faire</option>
                <option value="en_cours">En cours</option>
                <option value="termine">Terminé</option>
              </select>
            </div>
            <span
              v-else
              @click="startEdit('statut', localTache.statut)"
              class="px-3 py-1 rounded-full text-sm font-semibold"
              :class="[getStatutClass(localTache.statut), permissions.can_update ? 'cursor-pointer hover:opacity-80' : '']"
              :title="permissions.can_update ? 'Cliquer pour modifier' : ''"
            >
              {{ localTache.statut_label }}
            </span>
          </div>

          <div v-if="localTache.priorite">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Priorité</label>
            <div v-if="editing.field === 'priorite'">
              <select
                v-model="editing.value"
                @change="saveEdit"
                @blur="cancelEdit"
                @keydown.escape="cancelEdit"
                autofocus
                class="w-full px-3 py-1.5 text-sm border border-brand-500 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
              >
                <option value="faible">Faible</option>
                <option value="moyenne">Moyenne</option>
                <option value="elevee">Élevée</option>
                <option value="critique">Critique</option>
              </select>
            </div>
            <span
              v-else
              @click="startEdit('priorite', localTache.priorite)"
              class="px-3 py-1 rounded-full text-sm font-semibold"
              :class="[getPrioriteClass(localTache.priorite), permissions.can_update ? 'cursor-pointer hover:opacity-80' : '']"
              :title="permissions.can_update ? 'Cliquer pour modifier' : ''"
            >
              <i :class="['fas', localTache.priorite_icon, 'mr-1']"></i>
              {{ localTache.priorite_label }}
            </span>
          </div>

          <div v-if="localTache.taux_realisation !== null">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">
              Progression globale: {{ editing.field === 'taux_realisation' ? editing.value : localTache.taux_realisation }}%
            </label>
            <div v-if="editing.field === 'taux_realisation'" class="space-y-2">
              <div class="flex items-center gap-3">
                <input
                  type="range"
                  min="0"
                  max="100"
                  v-model.number="editing.value"
                  class="flex-1 accent-brand-600"
                />
                <input
                  type="number"
                  min="0"
                  max="100"
                  v-model.number="editing.value"
                  class="w-16 px-2 py-1 text-sm border border-brand-500 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
                <span class="text-sm text-gray-500">%</span>
              </div>
              <div class="flex gap-2">
                <button @click="saveEdit" class="px-3 py-1 text-xs bg-brand-600 text-white rounded-md hover:bg-brand-700">OK</button>
                <button @click="cancelEdit" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">Annuler</button>
              </div>
            </div>
            <div
              v-else
              @click="startEdit('taux_realisation', localTache.taux_realisation)"
              :class="permissions.can_update ? 'cursor-pointer' : ''"
              :title="permissions.can_update ? 'Cliquer pour modifier' : ''"
            >
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div
                  class="bg-brand-600 h-3 rounded-full transition-all duration-300"
                  :style="{ width: localTache.taux_realisation + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dates importantes -->
      <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <i class="fas fa-calendar text-brand-600"></i>
          Dates importantes
        </h3>
        <div class="space-y-3">
          <div v-if="localTache.date_debut" class="flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Date de début</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(localTache.date_debut) }}</span>
          </div>

          <div class="flex items-center justify-between" :class="{ 'text-red-600 dark:text-red-400': localTache.is_overdue }">
            <span class="text-sm">Échéance</span>
            <div v-if="editing.field === 'echeance'" class="ml-2">
              <DatePicker
                v-model="editing.value"
                :enable-time-picker="false"
                auto-apply
                :format="'dd-MM-yyyy'"
                :locale="'fr'"
                :dark="isDark"
                placeholder="Sélectionner une date"
                @update:model-value="saveEdit"
                @keydown.escape="cancelEdit"
                inline
              />
            </div>
            <span
              v-else-if="localTache.echeance"
              @click="startEdit('echeance', localTache.echeance)"
              class="text-sm font-medium flex items-center gap-1"
              :class="permissions.can_update ? 'cursor-pointer hover:opacity-80' : ''"
              :title="permissions.can_update ? 'Cliquer pour modifier' : ''"
            >
              {{ formatDate(localTache.echeance) }}
              <i v-if="localTache.is_overdue" class="fas fa-exclamation-triangle ml-1"></i>
            </span>
            <span
              v-else-if="permissions.can_update"
              @click="startEdit('echeance', null)"
              class="text-sm text-gray-400 cursor-pointer hover:text-brand-600 dark:hover:text-brand-400"
            >
              + Ajouter
            </span>
          </div>

          <div v-if="localTache.date_fin_reelle" class="flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Date de fin réelle</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(localTache.date_fin_reelle) }}</span>
          </div>

          <div v-if="localTache.week_number" class="flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Suivi hebdomadaire</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Semaine {{ localTache.week_number }}/{{ localTache.year }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Description et objectifs -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-align-left text-brand-600"></i>
        Description et objectifs
      </h3>
      <div class="space-y-4">
        <!-- Description -->
        <div>
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Description</label>
          <div v-if="editing.field === 'description'">
            <textarea
              v-model="editing.value"
              @blur="saveEdit"
              @keydown.escape="cancelEdit"
              autofocus
              rows="4"
              class="w-full px-3 py-2 text-sm border border-brand-500 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"
            ></textarea>
          </div>
          <p
            v-else
            @click="startEdit('description', localTache.description)"
            class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap min-h-8 rounded-lg p-2 -m-2"
            :class="permissions.can_update ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/60' : ''"
            :title="permissions.can_update ? 'Cliquer pour modifier' : ''"
          >{{ localTache.description || (permissions.can_update ? '+ Ajouter une description' : '—') }}</p>
        </div>

        <!-- Objectif -->
        <div>
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Objectif</label>
          <div v-if="editing.field === 'objectif'">
            <textarea
              v-model="editing.value"
              @blur="saveEdit"
              @keydown.escape="cancelEdit"
              autofocus
              rows="4"
              class="w-full px-3 py-2 text-sm border border-brand-500 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"
            ></textarea>
          </div>
          <p
            v-else
            @click="startEdit('objectif', localTache.objectif)"
            class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap min-h-8 rounded-lg p-2 -m-2"
            :class="permissions.can_update ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/60' : ''"
            :title="permissions.can_update ? 'Cliquer pour modifier' : ''"
          >{{ localTache.objectif || (permissions.can_update ? '+ Ajouter un objectif' : '—') }}</p>
        </div>

        <!-- Indicateurs de résultats -->
        <div>
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Indicateurs de résultats</label>
          <div v-if="editing.field === 'indicateurs_resultats'">
            <textarea
              v-model="editing.value"
              @blur="saveEdit"
              @keydown.escape="cancelEdit"
              autofocus
              rows="4"
              class="w-full px-3 py-2 text-sm border border-brand-500 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"
            ></textarea>
          </div>
          <p
            v-else
            @click="startEdit('indicateurs_resultats', localTache.indicateurs_resultats)"
            class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap min-h-8 rounded-lg p-2 -m-2"
            :class="permissions.can_update ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/60' : ''"
            :title="permissions.can_update ? 'Cliquer pour modifier' : ''"
          >{{ localTache.indicateurs_resultats || (permissions.can_update ? '+ Ajouter des indicateurs' : '—') }}</p>
        </div>
      </div>
    </div>

    <!-- Temps estimé vs réel -->
    <div v-if="localTache.estimated_hours || localTache.actual_hours" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-clock text-brand-600"></i>
        Suivi du temps
      </h3>
      <div class="grid grid-cols-2 gap-4">
        <div v-if="localTache.estimated_hours">
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Temps estimé</label>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ localTache.estimated_hours }}h</p>
        </div>
        <div v-if="localTache.actual_hours">
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Temps réel</label>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ localTache.actual_hours }}h</p>
        </div>
      </div>
    </div>

    <!-- Labels -->
    <div v-if="localTache.labels && localTache.labels.length > 0" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-tags text-brand-600"></i>
        Étiquettes
      </h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="label in localTache.labels"
          :key="label.id"
          class="px-3 py-1 rounded-full text-sm font-medium"
          :style="{ backgroundColor: label.color + '20', color: label.color }"
        >
          {{ label.nom }}
        </span>
      </div>
    </div>

    <!-- Validation -->
    <div v-if="localTache.validation" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-check-circle text-brand-600"></i>
        Validation
      </h3>
      <div class="space-y-4">
        <!-- Validation N1 -->
        <div v-if="localTache.validation.n1_required" class="border-l-4 pl-4" :class="localTache.validation.n1_validated_at ? 'border-green-500' : 'border-yellow-500'">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Validation N+1</span>
            <span v-if="localTache.validation.n1_validated_at" class="text-sm text-green-600 dark:text-green-400">
              <i class="fas fa-check-circle mr-1"></i>Validé
            </span>
            <span v-else class="text-sm text-yellow-600 dark:text-yellow-400">
              <i class="fas fa-clock mr-1"></i>En attente
            </span>
          </div>
          <div v-if="localTache.validation.n1_validated_at">
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Validé le {{ formatDate(localTache.validation.n1_validated_at) }}
              <span v-if="localTache.validation.n1_validated_by">par {{ localTache.validation.n1_validated_by.nom }}</span>
            </p>
            <p v-if="localTache.validation.n1_commentaire" class="text-sm text-gray-600 dark:text-gray-400 mt-1 italic">
              "{{ localTache.validation.n1_commentaire }}"
            </p>
          </div>
        </div>

        <!-- Validation N2 -->
        <div v-if="localTache.validation.n2_required" class="border-l-4 pl-4" :class="localTache.validation.n2_validated_at ? 'border-green-500' : 'border-yellow-500'">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Validation N+2</span>
            <span v-if="localTache.validation.n2_validated_at" class="text-sm text-green-600 dark:text-green-400">
              <i class="fas fa-check-circle mr-1"></i>Validé
            </span>
            <span v-else class="text-sm text-yellow-600 dark:text-yellow-400">
              <i class="fas fa-clock mr-1"></i>En attente
            </span>
          </div>
          <div v-if="localTache.validation.n2_validated_at">
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Validé le {{ formatDate(localTache.validation.n2_validated_at) }}
              <span v-if="localTache.validation.n2_validated_by">par {{ localTache.validation.n2_validated_by.nom }}</span>
            </p>
            <p v-if="localTache.validation.n2_commentaire" class="text-sm text-gray-600 dark:text-gray-400 mt-1 italic">
              "{{ localTache.validation.n2_commentaire }}"
            </p>
          </div>
        </div>

        <!-- Statut global -->
        <div v-if="localTache.validation.is_fully_validated" class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
          <p class="text-sm text-green-700 dark:text-green-400 font-medium">
            <i class="fas fa-check-double mr-2"></i>Tâche entièrement validée
          </p>
        </div>
      </div>
    </div>

    <!-- Métadonnées -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-info-circle text-brand-600"></i>
        Informations
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <span class="text-gray-600 dark:text-gray-400">Code:</span>
          <span class="ml-2 font-mono text-gray-900 dark:text-white">{{ localTache.code }}</span>
        </div>
        <div>
          <span class="text-gray-600 dark:text-gray-400">Créé le:</span>
          <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(localTache.created_at) }}</span>
        </div>
        <div>
          <span class="text-gray-600 dark:text-gray-400">Dernière mise à jour:</span>
          <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(localTache.updated_at) }}</span>
        </div>
        <div v-if="localTache.visibility">
          <span class="text-gray-600 dark:text-gray-400">Visibilité:</span>
          <span class="ml-2 text-gray-900 dark:text-white">{{ localTache.visibility }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import api from '@/api/axios'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  permissions: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['refresh'])

const localTache = ref({ ...props.tache })
const editing = reactive({ field: null, value: null })
const isDark = computed(() => document.documentElement.classList.contains('dark'))

watch(() => props.tache, (newTache) => {
  localTache.value = { ...newTache }
}, { deep: true })

const formatDateForApi = (date) => {
  if (!date) return null
  const d = date instanceof Date ? date : new Date(date)
  if (isNaN(d.getTime())) return null
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const startEdit = (field, value) => {
  if (!props.permissions?.can_update) return
  editing.field = field
  editing.value = field === 'echeance' ? (value ? new Date(value) : null) : value
}

const cancelEdit = () => {
  editing.field = null
  editing.value = null
}

const saveEdit = async () => {
  const { field, value } = editing
  if (!field) return
  const apiValue = field === 'echeance' ? formatDateForApi(value) : value
  const oldValue = localTache.value[field]
  if ((apiValue ?? '') === (oldValue ?? '')) { cancelEdit(); return }
  cancelEdit()
  try {
    await api.patch(`/taches/${props.tache.id}`, { [field]: apiValue })
    localTache.value[field] = apiValue
    emit('refresh')
  } catch (err) {
    console.error('Erreur mise à jour:', err)
    localTache.value[field] = oldValue
  }
}

const getStatutClass = (statut) => {
  const classes = {
    'a_faire': 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  }
  return classes[statut] || classes['a_faire']
}

const getPrioriteClass = (priorite) => {
  const classes = {
    'faible': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'moyenne': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'elevee': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'critique': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
  }
  return classes[priorite] || ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const handleKeydown = (e) => {
  if (e.key === 'Escape' && editing.field) cancelEdit()
}

onMounted(() => { window.addEventListener('keydown', handleKeydown) })
onBeforeUnmount(() => { window.removeEventListener('keydown', handleKeydown) })
</script>
