<template>
  <div class="space-y-4" dusk="soustache-list">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <i class="fas fa-list-check text-brand-600"></i>
        Sous-tâches
        <span dusk="soustache-count" class="px-2 py-0.5 rounded-full text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium">
          {{ sousTaches.length }}
        </span>
      </h3>

      <button
        v-if="canCreate && !showForm"
        dusk="add-soustache-btn"
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
            <!-- Left: checkbox + title -->
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <!-- Quick complete toggle -->
              <button
                v-if="canEdit"
                @click="toggleComplete(st)"
                :title="st.statut === 'termine' ? 'Marquer comme en cours' : 'Marquer comme terminé'"
                class="mt-0.5 shrink-0 w-5 h-5 rounded-full border-2 transition-colors flex items-center justify-center"
                :class="st.statut === 'termine'
                  ? 'bg-green-500 border-green-500 text-white'
                  : 'border-gray-400 dark:border-gray-600 hover:border-brand-500'"
              >
                <i v-if="st.statut === 'termine'" class="fas fa-check text-xs"></i>
              </button>
              <div v-else class="mt-0.5 shrink-0 w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center">
                <i v-if="st.statut === 'termine'" class="fas fa-check text-xs text-green-500"></i>
              </div>

              <div class="flex-1 min-w-0">
                <!-- Titre inline editable -->
                <div class="flex items-center gap-2 flex-wrap mb-1">
                  <input
                    v-if="editing.id === st.id && editing.field === 'titre'"
                    v-model="editing.value"
                    @blur="saveInlineEdit(st)"
                    @keydown.enter.prevent="saveInlineEdit(st)"
                    @keydown.escape="cancelInlineEdit"
                    autofocus
                    class="flex-1 text-sm font-medium px-2 py-0.5 border border-brand-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 min-w-0"
                  />
                  <span
                    v-else
                    @click="startInlineEdit(st, 'titre', st.titre)"
                    class="text-sm font-medium text-gray-900 dark:text-white"
                    :class="[
                      { 'line-through text-gray-400 dark:text-gray-500': st.statut === 'termine' },
                      canEdit ? 'cursor-pointer hover:text-brand-600 dark:hover:text-brand-400' : '',
                    ]"
                    :title="canEdit ? 'Cliquer pour modifier' : ''"
                  >{{ st.titre }}</span>

                  <!-- Statut inline editable -->
                  <select
                    v-if="editing.id === st.id && editing.field === 'statut'"
                    v-model="editing.value"
                    @change="saveInlineEdit(st)"
                    @blur="cancelInlineEdit"
                    @keydown.escape="cancelInlineEdit"
                    autofocus
                    class="px-2 py-0.5 text-xs font-medium rounded-full border border-brand-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                  >
                    <option value="a_faire">À faire</option>
                    <option value="en_cours">En cours</option>
                    <option value="en_retard">En retard</option>
                    <option value="termine">Terminé</option>
                    <option value="a_refaire">À refaire</option>
                    <option value="annule">Annulé</option>
                  </select>
                  <span
                    v-else
                    @click="startInlineEdit(st, 'statut', st.statut)"
                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="[getStatutClass(st.statut), canEdit ? 'cursor-pointer hover:opacity-75' : '']"
                    :title="canEdit ? 'Cliquer pour modifier' : ''"
                  >{{ getStatutLabel(st.statut) }}</span>

                  <!-- Overdue indicator -->
                  <span
                    v-if="st.is_overdue"
                    class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300"
                  >
                    En retard
                  </span>
                </div>

                <!-- Description inline editable -->
                <div v-if="editing.id === st.id && editing.field === 'description'" class="mb-1">
                  <textarea
                    v-model="editing.value"
                    @blur="saveInlineEdit(st)"
                    @keydown.escape="cancelInlineEdit"
                    autofocus
                    rows="2"
                    class="w-full text-xs px-2 py-1 border border-brand-500 rounded-md bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"
                  ></textarea>
                </div>
                <p
                  v-else-if="st.description || canEdit"
                  @click="startInlineEdit(st, 'description', st.description)"
                  class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1"
                  :class="canEdit ? 'cursor-pointer hover:text-brand-600 dark:hover:text-brand-400' : ''"
                  :title="canEdit ? 'Cliquer pour modifier la description' : ''"
                >{{ st.description || (canEdit ? '+ Ajouter une description…' : '') }}</p>

                <!-- Meta row: progression + date_echeance + blocking -->
                <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                  <!-- Progression inline editable -->
                  <div v-if="editing.id === st.id && editing.field === 'progression'" class="flex items-center gap-1.5">
                    <input
                      type="range"
                      min="0"
                      max="100"
                      v-model.number="editing.value"
                      class="w-20 accent-brand-600"
                    />
                    <input
                      type="number"
                      min="0"
                      max="100"
                      v-model.number="editing.value"
                      @blur="saveInlineEdit(st)"
                      @keydown.enter.prevent="saveInlineEdit(st)"
                      @keydown.escape="cancelInlineEdit"
                      autofocus
                      class="w-12 text-xs text-center border border-brand-500 rounded px-1 py-0.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none"
                    />
                    <span class="text-xs text-gray-400">%</span>
                    <button @click="saveInlineEdit(st)" class="text-xs px-1.5 py-0.5 bg-brand-600 text-white rounded hover:bg-brand-700">✓</button>
                    <button @click="cancelInlineEdit" class="text-xs px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300">✕</button>
                  </div>
                  <div
                    v-else-if="st.poids > 0 || st.progression > 0"
                    @click="startInlineEdit(st, 'progression', st.progression)"
                    class="flex items-center gap-1.5"
                    :class="canEdit ? 'cursor-pointer' : ''"
                    :title="canEdit ? 'Cliquer pour modifier la progression' : ''"
                  >
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                      <div
                        class="bg-brand-500 h-1.5 rounded-full transition-all"
                        :style="{ width: `${st.progression}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ st.progression }}%</span>
                    <span v-if="st.poids > 0" class="text-xs text-gray-400">· {{ st.poids }}%</span>
                  </div>

                  <!-- Date échéance inline editable -->
                  <div v-if="editing.id === st.id && editing.field === 'date_echeance'" class="flex items-center gap-1">
                    <DatePicker
                      v-model="editing.value"
                      :enable-time-picker="false"
                      auto-apply
                      :format="'dd-MM-yyyy'"
                      :locale="'fr'"
                      :dark="isDark"
                      placeholder="Sélectionner une date"
                      @update:model-value="saveInlineEdit(st)"
                      @keydown.escape="cancelInlineEdit"
                      inline
                    />
                  </div>
                  <div
                    v-else
                    @click="startInlineEdit(st, 'date_echeance', st.date_echeance)"
                    class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400"
                    :class="canEdit ? 'cursor-pointer hover:text-brand-600 dark:hover:text-brand-400' : ''"
                    :title="canEdit ? 'Cliquer pour modifier l\'échéance' : ''"
                  >
                    <i class="fas fa-calendar-alt text-gray-400"></i>
                    <span v-if="st.date_echeance" :class="{ 'text-red-600 font-medium': st.is_overdue }">
                      {{ formatDate(st.date_echeance) }}
                    </span>
                    <span v-else-if="canEdit" class="italic text-gray-400">+ Échéance</span>
                  </div>

                  <!-- Blocking badge -->
                  <span v-if="st.bloque_progression && st.statut !== 'termine'" class="text-xs text-orange-600 dark:text-orange-400 flex items-center gap-1">
                    <i class="fas fa-lock text-xs"></i>
                    Bloquante
                  </span>
                </div>
              </div>
            </div>

            <!-- Right: delete action only (edit is now inline) -->
            <div v-if="canDelete" class="relative shrink-0">
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
                  @click="confirmDelete(st)"
                  class="w-full px-3 py-2 text-left text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
                >
                  <i class="fas fa-trash-alt w-4"></i>
                  Supprimer
                </button>
              </div>
            </div>
          </div>

          <!-- Intervenants row -->
          <div
            v-if="canAssign || (st.intervenants && st.intervenants.length > 0)"
            class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2 flex-wrap"
          >
            <span
              v-for="iv in (st.intervenants ?? [])"
              :key="iv.id"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 font-medium"
            >
              {{ iv.nom || iv.name }}
              <button
                v-if="canAssign"
                @click="handleRemoveIntervenant(st, iv.id)"
                class="ml-0.5 text-brand-500 hover:text-red-500 transition-colors leading-none"
                :title="`Retirer ${iv.nom || iv.name}`"
              >&times;</button>
            </span>

            <div v-if="canAssign" class="relative">
              <button
                @click.stop="toggleIntervenantMenu(st.id)"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs border border-dashed border-gray-400 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-brand-500 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
              >
                <i class="fas fa-user-plus text-xs"></i>
                Ajouter
              </button>

              <div
                v-if="intervenantMenuId === st.id"
                v-click-outside="closeIntervenantMenus"
                class="absolute left-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-30 py-1 text-sm border border-gray-200 dark:border-gray-700"
              >
                <div class="px-2 py-1">
                  <input
                    v-model="memberSearch"
                    type="text"
                    placeholder="Rechercher…"
                    class="w-full px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-1 focus:ring-brand-500"
                    @click.stop
                  />
                </div>
                <div class="max-h-40 overflow-y-auto">
                  <button
                    v-for="m in filteredMembers"
                    :key="m.id"
                    @click="handleAssignIntervenant(st, m.id)"
                    :disabled="(st.intervenants ?? []).some(i => i.id === m.id) || assigningId === m.id"
                    class="w-full px-3 py-1.5 text-left hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2 text-xs"
                  >
                    <i v-if="assigningId === m.id" class="fas fa-spinner fa-spin text-gray-400"></i>
                    <i v-else-if="(st.intervenants ?? []).some(i => i.id === m.id)" class="fas fa-check text-green-500"></i>
                    <i v-else class="fas fa-user text-gray-400 w-3"></i>
                    {{ m.nom || m.name }}
                  </button>
                  <div v-if="filteredMembers.length === 0" class="px-3 py-2 text-xs text-gray-400">
                    Aucun résultat
                  </div>
                </div>
              </div>
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
        :members="canAssign ? workspaceMembers : []"
        @submit="handleCreate"
        @cancel="showForm = false"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import SousTacheForm from './SousTacheForm.vue'
import { useSousTaches } from '@/composables/useSousTaches'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
    tacheId: { type: Number, required: true },
    parentEcheance: { type: String, default: null },
    canCreate: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canAssign: { type: Boolean, default: false },
})

const authStore = useAuthStore()
const workspaceMembers = computed(() => authStore.currentWorkspace?.members ?? [])

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
    assignIntervenant,
    removeIntervenant,
} = useSousTaches(props.tacheId)

fetchSousTaches()

const showForm = ref(false)
const creating = ref(false)
const openMenuId = ref(null)
const formRef = ref(null)

const intervenantMenuId = ref(null)
const assigningId = ref(null)
const memberSearch = ref('')

const isDark = computed(() => document.documentElement.classList.contains('dark'))

// Inline edit state — tracks which subtask+field is being edited
const editing = reactive({ id: null, field: null, value: null })

const startInlineEdit = (st, field, value) => {
    if (!props.canEdit) return
    editing.id = st.id
    editing.field = field
    editing.value = field === 'date_echeance' ? (value ? new Date(value) : null) : value
}

const cancelInlineEdit = () => {
    editing.id = null
    editing.field = null
    editing.value = null
}

const formatDateForApi = (date) => {
    if (!date) return null
    const d = date instanceof Date ? date : new Date(date)
    if (isNaN(d.getTime())) return null
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const saveInlineEdit = async (st) => {
    const { field, value } = editing
    if (!field) return

    const apiValue = field === 'date_echeance' ? formatDateForApi(value) : value
    const oldValue = st[field]

    if ((apiValue ?? '') === (oldValue ?? '')) {
        cancelInlineEdit()
        return
    }

    cancelInlineEdit()
    try {
        await updateSousTache(st.id, { [field]: apiValue })
        emit('updated')
    } catch (err) {
        console.error('Erreur mise à jour sous-tâche:', err)
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Escape' && editing.id) cancelInlineEdit()
}

onMounted(() => { window.addEventListener('keydown', handleKeydown) })
onBeforeUnmount(() => { window.removeEventListener('keydown', handleKeydown) })

const filteredMembers = computed(() => {
    const q = memberSearch.value.toLowerCase().trim()
    return workspaceMembers.value.filter(m => {
        const name = (m.nom || m.name || '').toLowerCase()
        return !q || name.includes(q)
    })
})

const toggleIntervenantMenu = (stId) => {
    intervenantMenuId.value = intervenantMenuId.value === stId ? null : stId
    memberSearch.value = ''
}

const closeIntervenantMenus = () => {
    intervenantMenuId.value = null
    memberSearch.value = ''
}

const handleAssignIntervenant = async (st, userId) => {
    assigningId.value = userId
    try {
        const updated = await assignIntervenant(st.id, userId)
        const idx = sousTaches.value.findIndex(s => s.id === st.id)
        if (idx !== -1 && updated?.data) { sousTaches.value[idx] = updated.data }
        intervenantMenuId.value = null
        emit('updated')
    } finally {
        assigningId.value = null
    }
}

const handleRemoveIntervenant = async (st, userId) => {
    try {
        await removeIntervenant(st.id, userId)
        const idx = sousTaches.value.findIndex(s => s.id === st.id)
        if (idx !== -1 && sousTaches.value[idx].intervenants) {
            sousTaches.value[idx] = {
                ...sousTaches.value[idx],
                intervenants: sousTaches.value[idx].intervenants.filter(i => i.id !== userId),
            }
        }
        emit('updated')
    } catch {
        // silent — user stays in place
    }
}

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

const toggleComplete = async (st) => {
    const newStatut = st.statut === 'termine' ? 'en_cours' : 'termine'
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
