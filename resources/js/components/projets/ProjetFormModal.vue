<!-- resources/js/components/projets/ProjetFormModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col"
        @click.stop>

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            {{ isEdit ? 'Modifier le projet: ' + projet.nom : 'Créer un nouveau projet' }}
          </h2>
          <button @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- ✅ BANNER ERREUR STICKY — toujours visible, jamais derrière le scroll -->
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 max-h-0"
          enter-to-class="opacity-100 max-h-40"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 max-h-40"
          leave-to-class="opacity-0 max-h-0"
        >
          <div v-if="errorBanner"
            class="flex-shrink-0 overflow-hidden border-b-2 border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-6 py-3">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-800/50 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-red-800 dark:text-red-200">{{ errorBanner }}</p>
                <ul v-if="validationErrors.length > 1" class="mt-1.5 space-y-0.5">
                  <li v-for="(err, i) in validationErrors" :key="i"
                    class="text-xs text-red-700 dark:text-red-300 flex items-center gap-1.5">
                    <span class="w-1 h-1 rounded-full bg-red-500 flex-shrink-0"></span>
                    {{ err }}
                  </li>
                </ul>
              </div>
              <button @click="clearErrors()"
                class="flex-shrink-0 p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-800 transition-colors">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </Transition>

        <!-- Body scrollable -->
        <div class="flex-1 overflow-y-auto p-6" ref="formScrollContainer">
          <form @submit.prevent="handleSubmit" class="space-y-6">

            <!-- Workspace Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Workspace <span class="text-red-500">*</span>
              </label>
              <select v-model="form.workspace_id" required :disabled="!!workspaceId"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed">
                <option value="">Sélectionner un workspace</option>
                <option v-for="workspace in workspaces" :key="workspace.id" :value="workspace.id">
                  {{ workspace.nom }}
                </option>
              </select>
              <p v-if="workspaceId" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Workspace prédéfini pour ce contexte
              </p>
            </div>

            <!-- Nom du projet -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nom du projet <span class="text-red-500">*</span>
              </label>
              <input v-model="form.nom" type="text" required placeholder="Ex: Système de gestion RH"
                :class="['w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors',
                  fieldErrors.nom
                    ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10 focus:ring-red-300'
                    : 'border-gray-300 dark:border-gray-600']" />
              <p v-if="fieldErrors.nom" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ fieldErrors.nom }}
              </p>
            </div>

            <!-- Code -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Code du projet</label>
              <input v-model="form.code" type="text" placeholder="Auto-généré si vide"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Laissez vide pour générer automatiquement</p>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
              <textarea v-model="form.description" rows="4" placeholder="Décrivez votre projet..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"></textarea>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de début <span class="text-red-500">*</span>
                </label>
                <DatePicker v-model="form.date_debut" :enable-time-picker="false" auto-apply
                  :format="'yyyy-MM-dd'" :locale="'fr'" :dark="isDark"
                  :input-class-name="fieldErrors.date_debut ? 'dp-error-input' : ''"
                  class="w-full">
                  <template #input-icon>
                    <CalendarIcon class="w-5 h-5 text-gray-400" />
                  </template>
                </DatePicker>
                <p v-if="fieldErrors.date_debut" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                  <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  {{ fieldErrors.date_debut }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de fin <span class="text-red-500">*</span>
                </label>
                <DatePicker v-model="form.date_fin" :enable-time-picker="false" auto-apply
                  :format="'yyyy-MM-dd'" :locale="'fr'" :dark="isDark" :min-date="form.date_debut"
                  :input-class-name="fieldErrors.date_fin ? 'dp-error-input' : ''"
                  class="w-full">
                  <template #input-icon>
                    <CalendarIcon class="w-5 h-5 text-gray-400" />
                  </template>
                </DatePicker>
                <p v-if="fieldErrors.date_fin" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                  <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  {{ fieldErrors.date_fin }}
                </p>
              </div>
            </div>

            <!-- Responsable -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Responsable du projet <span class="text-red-500">*</span>
              </label>
              <select v-model="form.responsable_id" required
                :class="['w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors',
                  fieldErrors.responsable_id
                    ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10 focus:ring-red-300'
                    : 'border-gray-300 dark:border-gray-600']">
                <option value="">Sélectionner un responsable</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.nom }} - {{ user.email }}
                </option>
              </select>
              <p v-if="fieldErrors.responsable_id" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ fieldErrors.responsable_id }}
              </p>
              <p v-if="users.length === 0 && form.workspace_id" class="mt-1 text-xs text-yellow-600 dark:text-yellow-400">
                Aucun membre trouvé dans ce workspace
              </p>
            </div>

            <!-- Status et Visibilité -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                <select v-model="form.status"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                  <option value="active">Actif</option>
                  <option value="pending">En attente</option>
                  <option value="completed">Terminé</option>
                  <option value="archived">Archivé</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Visibilité</label>
                <select v-model="form.visibility"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                  <option value="public">Public - Visible par tous</option>
                  <option value="team">Équipe - Visible par les membres</option>
                  <option value="private">Privé - Visible uniquement par le responsable</option>
                </select>
              </div>
            </div>

            <!-- Budget -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Budget (optionnel)</label>
              <div class="relative">
                <input v-model.number="form.budget" type="number" step="0.01" min="0" placeholder="0.00"
                  class="w-full px-4 py-2 pl-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">XAF</span>
              </div>
            </div>

            <!-- Couleur -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Couleur du projet</label>
              <div class="flex items-center gap-4">
                <input v-model="form.couleur" type="color"
                  class="w-16 h-10 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer" />
                <input v-model="form.couleur" type="text" placeholder="#3B82F6"
                  class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
              </div>
              <div class="flex gap-2 mt-3">
                <button v-for="color in presetColors" :key="color" type="button" @click="form.couleur = color"
                  :style="{ backgroundColor: color }"
                  :class="['w-8 h-8 rounded-lg border-2 transition-transform hover:scale-110',
                    form.couleur === color ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent']"></button>
              </div>
            </div>

            <!-- Objectifs -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Objectifs du projet</label>
              <textarea v-model="form.objectifs" rows="3" placeholder="Décrivez les objectifs principaux du projet..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"></textarea>
            </div>

            <!-- Options -->
            <div class="space-y-3">
              <div class="flex items-center gap-3">
                <input v-model="form.is_template" type="checkbox" id="is_template"
                  class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-600" />
                <label for="is_template" class="text-sm text-gray-700 dark:text-gray-300">Utiliser comme modèle pour de futurs projets</label>
              </div>
              <div class="flex items-center gap-3">
                <input v-model="form.is_favorite" type="checkbox" id="is_favorite"
                  class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-600" />
                <label for="is_favorite" class="text-sm text-gray-700 dark:text-gray-300">Ajouter aux favoris</label>
              </div>
            </div>

          </form>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
          <button type="button" @click="$emit('close')"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            Annuler
          </button>
          <button @click="handleSubmit" :disabled="loading"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
            <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isEdit ? 'Mettre à jour' : 'Créer le projet' }}
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useProjets } from '@/composables/useProjets'
import { useWorkspace } from '@/composables/useWorkspace'
import { useAuthStore } from '@/stores/authStore'
import { XIcon, CalendarIcon } from '@/icons'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { useToast } from 'vue-toastification'

const props = defineProps({
  projet: { type: Object, default: null },
  workspaceId: { type: [Number, String], default: null }
})
const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const isDark = computed(() => document.documentElement.classList.contains('dark'))
const toast = useToast()
const { createProjet, updateProjet } = useProjets()
const { workspaces, fetchWorkspaces, fetchMembers, currentWorkspaceId } = useWorkspace()

// ── DOM ref pour scroll ───────────────────────────────────────
const formScrollContainer = ref(null)

// ── État ──────────────────────────────────────────────────────
const users           = ref([])
const loading         = ref(false)
const errorBanner     = ref('')
const validationErrors = ref([])
const fieldErrors     = ref({ nom: '', date_debut: '', date_fin: '', responsable_id: '' })

const presetColors = ['#3B82F6','#10B981','#8B5CF6','#F59E0B','#EF4444','#EC4899','#14B8A6','#F97316']
const isEdit = computed(() => !!props.projet)

const form = ref({
  workspace_id: '',
  nom: '',
  code: '',
  description: '',
  date_debut: '',
  date_fin: '',
  responsable_id: '',
  status: 'active',
  visibility: 'team',
  couleur: '#3B82F6',
  budget: null,
  objectifs: '',
  is_template: false,
  is_favorite: false
})

// ── Helpers erreurs ───────────────────────────────────────────
const clearErrors = () => {
  errorBanner.value = ''
  validationErrors.value = []
  Object.keys(fieldErrors.value).forEach(k => (fieldErrors.value[k] = ''))
}

/**
 * Affiche le banner sticky EN HAUT du modal.
 * Pas besoin de scroller : le banner est en dehors de la zone scrollable.
 */
const showError = (message, errors = []) => {
  errorBanner.value = message
  validationErrors.value = errors
  // On scroll quand même vers le haut du conteneur de formulaire
  // pour que l'utilisateur voit le début du formulaire + le banner
  nextTick(() => {
    formScrollContainer.value?.scrollTo({ top: 0, behavior: 'smooth' })
  })
}

// Effacer l'erreur d'un champ dès que l'utilisateur le modifie
watch(() => form.value.nom,            () => { fieldErrors.value.nom = '' })
watch(() => form.value.date_debut,     () => { fieldErrors.value.date_debut = '' })
watch(() => form.value.date_fin,       () => { fieldErrors.value.date_fin = '' })
watch(() => form.value.responsable_id, () => { fieldErrors.value.responsable_id = '' })

// ── Chargement membres ────────────────────────────────────────
const loadWorkspaceMembers = async (workspaceId) => {
  try {
    users.value = (await fetchMembers(workspaceId)) || []
  } catch {
    users.value = []
    toast.warning('Impossible de charger les membres du workspace', { position: 'top-right' })
  }
}

// ── Initialisation ────────────────────────────────────────────
const initializeForm = async () => {
  try {
    if (workspaces.value.length === 0) await fetchWorkspaces()
    const defaultId = props.workspaceId || currentWorkspaceId.value || workspaces.value[0]?.id || null
    if (defaultId) {
      form.value.workspace_id = defaultId
      await loadWorkspaceMembers(defaultId)
    }
    if (props.projet) {
      form.value = { ...form.value, ...props.projet }
      if (props.projet.workspace_id && props.projet.workspace_id !== defaultId) {
        await loadWorkspaceMembers(props.projet.workspace_id)
      }
      if (props.projet.responsable_id && !users.value.find(u => u.id === props.projet.responsable_id)) {
        form.value.responsable_id = ''
      }
    } else {
      if (authStore.user?.id && users.value.find(u => u.id === authStore.user.id)) {
        form.value.responsable_id = authStore.user.id
      }
    }
  } catch {
    showError('Erreur lors du chargement des données du formulaire')
  }
}

onMounted(initializeForm)

watch(() => form.value.workspace_id, async (newId) => {
  if (!newId) return
  await loadWorkspaceMembers(newId)
  if (!isEdit.value) {
    form.value.responsable_id = users.value.find(u => u.id === authStore.user?.id) ? authStore.user.id : ''
  }
})

// ── Soumission ────────────────────────────────────────────────
const handleSubmit = async () => {
  clearErrors()

  // Validation locale
  const errors = []
  if (!form.value.workspace_id) {
    errors.push('Veuillez sélectionner un workspace')
  }
  if (!form.value.nom?.trim()) {
    fieldErrors.value.nom = 'Le nom du projet est obligatoire'
    errors.push(fieldErrors.value.nom)
  }
  if (!form.value.date_debut) {
    fieldErrors.value.date_debut = 'La date de début est requise'
    errors.push(fieldErrors.value.date_debut)
  }
  if (!form.value.date_fin) {
    fieldErrors.value.date_fin = 'La date de fin est requise'
    errors.push(fieldErrors.value.date_fin)
  }
  if (form.value.date_debut && form.value.date_fin && new Date(form.value.date_fin) < new Date(form.value.date_debut)) {
    fieldErrors.value.date_fin = 'La date de fin doit être après la date de début'
    errors.push(fieldErrors.value.date_fin)
  }
  if (!form.value.responsable_id) {
    fieldErrors.value.responsable_id = 'Le responsable du projet est requis'
    errors.push(fieldErrors.value.responsable_id)
  }

  if (errors.length > 0) {
    showError(
      errors.length === 1 ? errors[0] : `${errors.length} champs obligatoires manquants`,
      errors.length > 1 ? errors : []
    )
    return
  }

  loading.value = true
  try {
    const submitData = {
      ...form.value,
      code: form.value.code || (isEdit.value ? undefined : null),
      budget: form.value.budget || null
    }
    if (isEdit.value && !submitData.code) delete submitData.code

    if (isEdit.value) {
      await updateProjet(props.projet.id, submitData)
      toast.success('✅ Projet mis à jour avec succès !', { position: 'top-right', timeout: 4000 })
    } else {
      await createProjet(submitData)
      toast.success('✅ Projet créé avec succès !', { position: 'top-right', timeout: 4000 })
    }
    emit('saved')
    emit('close') // Always close the modal after a successful save

  } catch (err) {
    if (err.response?.status === 422) {
      const apiErrors = err.response.data.errors
      if (apiErrors) {
        const flat = Object.values(apiErrors).flat()
        if (apiErrors.nom)            fieldErrors.value.nom = apiErrors.nom[0]
        if (apiErrors.date_debut)     fieldErrors.value.date_debut = apiErrors.date_debut[0]
        if (apiErrors.date_fin)       fieldErrors.value.date_fin = apiErrors.date_fin[0]
        if (apiErrors.responsable_id) fieldErrors.value.responsable_id = apiErrors.responsable_id[0]
        showError('Veuillez corriger les erreurs ci-dessous', flat)
      } else {
        showError(err.response.data.message || 'Erreur de validation')
      }
    } else if (err.response?.data?.message) {
      showError(err.response.data.message)
    } else if (err.message === 'Network Error') {
      showError('Erreur de connexion. Vérifiez votre connexion internet.')
    } else {
      showError('Une erreur est survenue lors de la sauvegarde')
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Bordure rouge sur le champ DatePicker en erreur */
:deep(.dp-error-input) {
  border-color: #f87171 !important;
  background-color: #fff5f5 !important;
}
.dark :deep(.dp-error-input) {
  border-color: #f87171 !important;
  background-color: rgba(239,68,68,0.08) !important;
}
</style>