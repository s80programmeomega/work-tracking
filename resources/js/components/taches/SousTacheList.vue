<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <i class="fas fa-list-check text-brand-600"></i>
        Sous-tâches
        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium">
          {{ sousTaches.length }}
        </span>
      </h3>

      <button
        v-if="canCreate && !showForm"
        @click="showForm = true"
        class="px-3 py-1.5 text-xs bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors flex items-center gap-1"
      >
        <i class="fas fa-plus"></i>
        Ajouter
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <i class="fas fa-spinner fa-spin text-2xl text-brand-600"></i>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="text-sm text-red-600 bg-red-50 dark:bg-red-900/20 rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <template v-else>
      <!-- Global progress when weighted -->
      <div v-if="sousTaches.length > 0 && totalPoids > 0" class="bg-gray-50 dark:bg-gray-800 rounded-xl px-4 py-3">
        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1.5">
          <span>Progression pondérée</span>
          <span class="font-semibold">{{ weightedProgress }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
          <div
            class="bg-brand-500 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${weightedProgress}%` }"
          ></div>
        </div>
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
          Poids total alloué: {{ totalPoids }}% / 100%
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="sousTaches.length === 0 && !showForm" class="text-center py-8 text-gray-500 dark:text-gray-400">
        <i class="fas fa-list-check text-3xl mb-2 opacity-30"></i>
        <p class="text-sm">Aucune sous-tâche pour l'instant.</p>
        <button
          v-if="canCreate"
          @click="showForm = true"
          class="mt-3 text-xs text-brand-600 hover:underline"
        >
          Créer la première sous-tâche
        </button>
      </div>

      <!-- Sous-tâche items -->
      <div class="space-y-2">
        <div
          v-for="st in orderedSousTaches"
          :key="st.id"
          class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4 transition-all"
          :class="{
            'border-l-4 border-l-red-400': st.statut === 'en_retard',
            'border-l-4 border-l-green-400': st.statut === 'termine',
            'border-l-4 border-l-brand-400': st.statut === 'en_cours',
          }"
        >
          <div class="flex items-start justify-between gap-3">
            <!-- Left: checkbox-style status toggle + title -->
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <!-- Quick complete toggle -->
              <button
                v-if="canEdit"
                @click="toggleComplete(st)"
                :title="st.statut === 'termine' ? 'Marquer comme en cours' : 'Marquer comme terminé'"
                class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full border-2 transition-colors flex items-center justify-center"
                :class="st.statut === 'termine'
                  ? 'bg-green-500 border-green-500 text-white'
                  : 'border-gray-400 dark:border-gray-600 hover:border-brand-500'"
              >
                <i v-if="st.statut === 'termine'" class="fas fa-check text-xs"></i>
              </button>
              <div v-else class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center">
                <i v-if="st.statut === 'termine'" class="fas fa-check text-xs text-green-500"></i>
              </div>

              <div class="flex-1 min-w-0">
                <!-- Title + statut badge -->
                <div class="flex items-center gap-2 flex-wrap">
                  <span
                    class="text-sm font-medium text-gray-900 dark:text-white"
                    :class="{ 'line-through text-gray-400 dark:text-gray-500': st.statut === 'termine' }"
                  >
                    {{ st.titre }}
                  </span>
                  <span
                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="getStatutClass(st.statut)"
                  >
                    {{ getStatutLabel(st.statut) }}
                  </span>
                  <!-- Overdue indicator -->
                  <span
                    v-if="st.is_overdue"
                    class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300"
                  >
                    En retard
                  </span>
                </div>

                <!-- Description -->
                <p v-if="st.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
                  {{ st.description }}
                </p>

                <!-- Meta row -->
                <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                  <!-- Progression -->
                  <div v-if="st.poids > 0" class="flex items-center gap-1.5">
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                      <div
                        class="bg-brand-500 h-1.5 rounded-full transition-all"
                        :style="{ width: `${st.progression}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ st.progression }}%</span>
                    <span class="text-xs text-gray-400">· {{ st.poids }}%</span>
                  </div>
                  <div v-else-if="st.progression > 0" class="flex items-center gap-1.5">
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                      <div
                        class="bg-brand-500 h-1.5 rounded-full transition-all"
                        :style="{ width: `${st.progression}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ st.progression }}%</span>
                  </div>

                  <!-- Deadline -->
                  <div v-if="st.date_echeance" class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-calendar-alt text-gray-400"></i>
                    <span :class="{ 'text-red-600 font-medium': st.is_overdue }">
                      {{ formatDate(st.date_echeance) }}
                    </span>
                  </div>

                  <!-- Blocking badge -->
                  <span v-if="st.bloque_progression && st.statut !== 'termine'" class="text-xs text-orange-600 dark:text-orange-400 flex items-center gap-1">
                    <i class="fas fa-lock text-xs"></i>
                    Bloquante
                  </span>
                </div>
              </div>
            </div>

            <!-- Right: edit/delete actions -->
            <div v-if="canEdit || canDelete" class="relative flex-shrink-0">
              <button
                @click.stop="toggleMenu(st.id)"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1"
              >
                <i class="fas fa-ellipsis-v text-xs"></i>
              </button>

              <div
                v-if="openMenuId === st.id"
                v-click-outside="closeMenus"
                class="absolute right-0 mt-1 w-36 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-20 py-1 text-sm"
              >
                <button
                  v-if="canEdit"
                  @click="startEdit(st)"
                  class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
                >
                  <i class="fas fa-edit w-4 text-gray-500"></i>
                  Modifier
                </button>
                <button
                  v-if="canDelete"
                  @click="confirmDelete(st)"
                  class="w-full px-3 py-2 text-left text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
                >
                  <i class="fas fa-trash-alt w-4"></i>
                  Supprimer
                </button>
              </div>
            </div>
          </div>

          <!-- Inline progress editor (when editing) -->
          <div v-if="editingId === st.id" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 gap-3 mb-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Statut</label>
                <select
                  v-model="editForm.statut"
                  class="w-full px-2 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                >
                  <option value="a_faire">À faire</option>
                  <option value="en_cours">En cours</option>
                  <option value="en_retard">En retard</option>
                  <option value="termine">Terminé</option>
                  <option value="a_refaire">À refaire</option>
                  <option value="annule">Annulé</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Progression (%)</label>
                <input
                  v-model.number="editForm.progression"
                  type="number"
                  min="0"
                  max="100"
                  class="w-full px-2 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                />
              </div>
            </div>
            <div class="flex justify-end gap-2">
              <button
                @click="cancelEdit"
                class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900"
              >
                Annuler
              </button>
              <button
                @click="saveEdit(st)"
                :disabled="saving"
                class="px-3 py-1.5 text-xs bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50"
              >
                <i v-if="saving" class="fas fa-spinner fa-spin mr-1"></i>
                Enregistrer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Create form -->
      <SousTacheForm
        v-if="showForm"
        ref="formRef"
        :tache-id="tacheId"
        :parent-echeance="parentEcheance"
        :total-poids="totalPoids"
        :loading="creating"
        @submit="handleCreate"
        @cancel="showForm = false"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import SousTacheForm from './SousTacheForm.vue'
import { useSousTaches } from '@/composables/useSousTaches'

const props = defineProps({
    tacheId: { type: Number, required: true },
    parentEcheance: { type: String, default: null },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
})

const emit = defineEmits(['updated'])

const {
    sousTaches,
    loading,
    error,
    totalPoids,
    fetchSousTaches,
    createSousTache,
    updateSousTache,
    deleteSousTache,
} = useSousTaches(props.tacheId)

fetchSousTaches()

const showForm = ref(false)
const creating = ref(false)
const saving = ref(false)
const openMenuId = ref(null)
const editingId = ref(null)
const formRef = ref(null)
const editForm = ref({ statut: '', progression: 0 })

const orderedSousTaches = computed(() =>
    [...sousTaches.value].sort((a, b) => (a.ordre ?? 0) - (b.ordre ?? 0))
)

const weightedProgress = computed(() => {
    if (totalPoids.value === 0) return 0
    const sum = sousTaches.value.reduce((acc, st) => acc + (st.progression ?? 0) * (st.poids ?? 0) / 100, 0)
    return Math.round(sum)
})

const toggleMenu = (id) => {
    openMenuId.value = openMenuId.value === id ? null : id
}

const closeMenus = () => {
    openMenuId.value = null
}

const startEdit = (st) => {
    openMenuId.value = null
    editingId.value = st.id
    editForm.value = { statut: st.statut, progression: st.progression }
}

const cancelEdit = () => {
    editingId.value = null
}

const saveEdit = async (st) => {
    saving.value = true
    try {
        await updateSousTache(st.id, { ...editForm.value })
        editingId.value = null
        emit('updated')
    } finally {
        saving.value = false
    }
}

const toggleComplete = async (st) => {
    const newStatut = st.statut === 'termine' ? 'en_cours' : 'termine'
    // Forward: jump to 100. Backward (un-toggling from a 100 % completed sous-tâche): reset to 0
    // so the parent task's weighted progression recalculates downward. Otherwise keep current progression.
    const newProgression = newStatut === 'termine'
        ? 100
        : (st.progression >= 100 ? 0 : st.progression)
    await updateSousTache(st.id, { statut: newStatut, progression: newProgression })
    emit('updated')
}

const handleCreate = async (payload) => {
    creating.value = true
    try {
        await createSousTache(payload)
        showForm.value = false
        formRef.value?.reset()
        emit('updated')
    } catch (err) {
        const data = err.response?.data
        if (data?.errors) {
            formRef.value?.setErrors(data.errors)
        } else {
            formRef.value?.setErrors(data?.message ?? 'Une erreur est survenue.')
        }
    } finally {
        creating.value = false
    }
}

const confirmDelete = async (st) => {
    openMenuId.value = null
    if (!confirm(`Supprimer la sous-tâche "${st.titre}" ?`)) return
    await deleteSousTache(st.id)
    emit('updated')
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const getStatutLabel = (statut) => {
    const labels = {
        a_faire: 'À faire',
        en_cours: 'En cours',
        en_retard: 'En retard',
        termine: 'Terminé',
        a_refaire: 'À refaire',
        annule: 'Annulé',
    }
    return labels[statut] ?? statut
}

const getStatutClass = (statut) => {
    const classes = {
        a_faire: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        en_cours: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        en_retard: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        termine: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        a_refaire: 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
        annule: 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
    }
    return classes[statut] ?? classes.a_faire
}

// Click-outside directive
const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value()
            }
        }
        document.addEventListener('click', el.clickOutsideEvent)
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent)
    },
}
</script>
