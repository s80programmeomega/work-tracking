<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 backdrop-blur-sm" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[95vh] overflow-hidden flex flex-col">

      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-brand-50 to-white dark:from-gray-900 dark:to-gray-800">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shadow-lg">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ tache ? 'Modifier la tâche' : 'Nouvelle tâche' }}
              </h2>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ tache ? 'Mettez à jour les informations de la tâche' : 'Créez une nouvelle tâche pour votre projet' }}
              </p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mx-8 mt-4 p-4 bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800 rounded-xl">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="font-medium text-red-800 dark:text-red-200">{{ errorMessage }}</p>
            <ul v-if="validationErrors.length > 0" class="mt-2 list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
              <li v-for="(error, index) in validationErrors" :key="index">{{ error }}</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto">
        <form @submit.prevent="handleSubmit" class="px-8 py-6">
          <div class="space-y-6">

            <!-- Section Informations Générales -->
            <div class="space-y-4">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Informations générales
              </h3>

              <!-- Activité -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Activité <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="formData.activite_id"
                  required
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                >
                  <option value="">Sélectionner une activité</option>
                  <option v-for="activite in activites" :key="activite.id" :value="activite.id">
                    {{ activite.nom }}
                  </option>
                </select>
              </div>

              <!-- Titre -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Titre de la tâche <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.titre"
                  type="text"
                  required
                  placeholder="Ex: Implémenter l'authentification utilisateur"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                />
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Description
                </label>
                <textarea
                  v-model="formData.description"
                  rows="4"
                  placeholder="Décrivez la tâche en détail..."
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"
                ></textarea>
              </div>

              <!-- Objectif & Indicateurs -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Objectif
                  </label>
                  <textarea
                    v-model="formData.objectif"
                    rows="3"
                    placeholder="Quel est l'objectif de cette tâche ?"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"
                  ></textarea>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Indicateurs de résultats
                  </label>
                  <textarea
                    v-model="formData.indicateurs_resultats"
                    rows="3"
                    placeholder="Comment mesurer le succès ?"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- Section Planification -->
            <div class="space-y-4">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Planification
              </h3>

              <!-- Statut & Priorité -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    Statut
                  </label>
                  <div class="grid grid-cols-1 gap-2">
                    <label
                      v-for="statut in statutOptions"
                      :key="statut.value"
                      class="relative flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                      :class="formData.statut === statut.value
                        ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                        : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'"
                    >
                      <input
                        type="radio"
                        v-model="formData.statut"
                        :value="statut.value"
                        class="sr-only"
                      />
                      <span :class="statut.color" class="w-3 h-3 rounded-full"></span>
                      <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ statut.label }}</span>
                      <svg v-if="formData.statut === statut.value" class="absolute right-3 w-5 h-5 text-brand-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </label>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    Priorité
                  </label>
                  <div class="grid grid-cols-1 gap-2">
                    <label
                      v-for="priorite in prioriteOptions"
                      :key="priorite.value"
                      class="relative flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                      :class="formData.priorite === priorite.value
                        ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                        : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'"
                    >
                      <input
                        type="radio"
                        v-model="formData.priorite"
                        :value="priorite.value"
                        class="sr-only"
                      />
                      <span class="text-lg">{{ priorite.icon }}</span>
                      <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ priorite.label }}</span>
                      <svg v-if="formData.priorite === priorite.value" class="absolute right-3 w-5 h-5 text-brand-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Dates -->
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Date de début
                  </label>
                  <input
                    v-model="formData.date_debut"
                    type="date"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Date d'échéance
                  </label>
                  <input
                    v-model="formData.echeance"
                    type="date"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Date de fin réelle
                  </label>
                  <input
                    v-model="formData.date_fin_reelle"
                    type="date"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                  />
                </div>
              </div>

              <!-- Progression & Heures -->
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Progression (%)
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="formData.taux_realisation"
                      type="number"
                      min="0"
                      max="100"
                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                    />
                    <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div
                        class="bg-gradient-to-r from-brand-500 to-brand-600 h-2 rounded-full transition-all"
                        :style="{ width: `${formData.taux_realisation}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Heures estimées
                  </label>
                  <input
                    v-model.number="formData.estimated_hours"
                    type="number"
                    min="0"
                    step="0.5"
                    placeholder="0"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Heures réelles
                  </label>
                  <input
                    v-model.number="formData.actual_hours"
                    type="number"
                    min="0"
                    step="0.5"
                    placeholder="0"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                  />
                </div>
              </div>
            </div>

            <!-- Section Équipe & Labels -->
            <div class="space-y-4">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Équipe & Organisation
              </h3>

              <!-- Assignés -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Assigner à
                </label>
                <select
                  v-model="formData.assignee_ids"
                  multiple
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                  size="4"
                >
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.nom }}
                  </option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs</p>
              </div>

              <!-- Labels -->
              <TaskLabelsSelector
                v-model="formData.label_ids"
                :show-create-button="true"
                @create-label="showLabelModal = true"
              />
            </div>

            <!-- Section Apparence -->
            <div class="space-y-4">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                </svg>
                Apparence
              </h3>

              <div class="grid grid-cols-2 gap-4">
                <!-- Couleur -->
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Couleur
                  </label>
                  <div class="flex gap-3 items-center">
                    <input
                      v-model="formData.couleur"
                      type="color"
                      class="h-12 w-20 border-2 border-gray-300 dark:border-gray-700 rounded-lg cursor-pointer"
                    />
                    <input
                      v-model="formData.couleur"
                      type="text"
                      placeholder="#3B82F6"
                      pattern="^#[0-9A-Fa-f]{6}$"
                      class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                    />
                  </div>
                </div>

                <!-- Image de couverture -->
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Image de couverture
                  </label>
                  <input
                    type="file"
                    accept="image/*"
                    @change="handleCoverImageUpload"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                  />
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF jusqu'à 2 Mo</p>
                </div>
              </div>

              <!-- Preview Image -->
              <div v-if="coverImagePreview" class="mt-3">
                <img :src="coverImagePreview" alt="Preview" class="h-40 w-full rounded-xl object-cover shadow-lg" />
              </div>
            </div>

            <!-- Section Commentaire -->
            <div class="space-y-4">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                Commentaire
              </h3>
              <textarea
                v-model="formData.commentaire"
                rows="3"
                placeholder="Ajoutez un commentaire ou une note..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"
              ></textarea>
            </div>

          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
        <button
          type="button"
          @click="$emit('close')"
          class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all"
        >
          Annuler
        </button>
        <button
          type="button"
          @click="handleSubmit"
          :disabled="loading"
          class="px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
        </button>
      </div>

    </div>

    <!-- Label Modal -->
    <LabelModal
      v-if="showLabelModal"
      @saved="handleLabelCreated"
      @close="showLabelModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useTaches } from '@/composables/useTaches'
import { useLabels } from '@/composables/useLabels'
import { TaskLabelsSelector, LabelModal } from '@/components/labels'
import api from '@/api/axios'

const props = defineProps({
  tache: {
    type: Object,
    default: null
  },
  activiteId: {
    type: Number,
    default: null
  },
  initialStatut: {
    type: String,
    default: 'a_faire'
  }
})

const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const { createTache, updateTache } = useTaches()
const { labels, fetchLabels } = useLabels()

const loading = ref(false)
const users = ref([])
const activites = ref([])
const errorMessage = ref('')
const validationErrors = ref([])
const showLabelModal = ref(false)

const formData = ref({
  activite_id: props.activiteId || '',
  titre: '',
  description: '',
  objectif: '',
  indicateurs_resultats: '',
  statut: props.initialStatut || 'a_faire',
  priorite: 'moyenne',
  echeance: '',
  date_debut: '',
  date_fin_reelle: '',
  taux_realisation: 0,
  estimated_hours: null,
  actual_hours: null,
  cover_image: null,
  couleur: '#3B82F6',
  commentaire: '',
  assignee_ids: [],
  label_ids: []
})

const coverImagePreview = ref(null)

const statutOptions = [
  { value: 'a_faire', label: 'À faire', color: 'bg-gray-500' },
  { value: 'en_cours', label: 'En cours', color: 'bg-blue-500' },
  { value: 'termine', label: 'Terminé', color: 'bg-green-500' }
]

const prioriteOptions = [
  { value: 'faible', label: 'Faible', icon: '🟢' },
  { value: 'moyenne', label: 'Moyenne', icon: '🟡' },
  { value: 'elevee', label: 'Élevée', icon: '🟠' },
  { value: 'critique', label: 'Critique', icon: '🔴' }
]

const handleLabelCreated = async () => {
  showLabelModal.value = false
  await fetchLabels()
}

const handleCoverImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    formData.value.cover_image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      coverImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const loadUsers = async () => {
  try {
    const { data } = await api.get('/users')
    users.value = data.data || []
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const loadActivites = async () => {
  try {
    const { data } = await api.get('/activites')
    activites.value = data.data || []
  } catch (error) {
    console.error('Error loading activites:', error)
  }
}

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''
  validationErrors.value = []

  try {
    let dataToSend = formData.value

    if (formData.value.cover_image instanceof File) {
      const formDataObj = new FormData()
      Object.keys(formData.value).forEach(key => {
        if (key === 'assignee_ids' || key === 'label_ids') {
          formData.value[key].forEach(id => {
            formDataObj.append(`${key}[]`, id)
          })
        } else if (formData.value[key] !== null && formData.value[key] !== '') {
          formDataObj.append(key, formData.value[key])
        }
      })
      dataToSend = formDataObj
    }

    if (props.tache) {
      await updateTache(props.tache.id, dataToSend)
    } else {
      await createTache(dataToSend)
    }
    emit('saved')
  } catch (error) {
    console.error(error)

    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      if (errors) {
        validationErrors.value = Object.values(errors).flat()
        errorMessage.value = 'Veuillez corriger les erreurs suivantes :'
      } else {
        errorMessage.value = error.response.data.message || 'Erreur de validation'
      }
    } else if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message
    } else if (error.message === 'Network Error') {
      errorMessage.value = 'Erreur de connexion. Veuillez vérifier votre connexion internet.'
    } else {
      errorMessage.value = 'Une erreur s\'est produite. Veuillez réessayer.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    await Promise.all([loadUsers(), loadActivites(), fetchLabels()])
  } catch (error) {
    console.error('Error loading form data:', error)
  }

  if (props.tache) {
    formData.value = {
      activite_id: props.tache.activite_id || '',
      titre: props.tache.titre || '',
      description: props.tache.description || '',
      objectif: props.tache.objectif || '',
      indicateurs_resultats: props.tache.indicateurs_resultats || '',
      statut: props.tache.statut || 'a_faire',
      priorite: props.tache.priorite || 'moyenne',
      echeance: props.tache.echeance || '',
      date_debut: props.tache.date_debut || '',
      date_fin_reelle: props.tache.date_fin_reelle || '',
      taux_realisation: props.tache.taux_realisation || 0,
      estimated_hours: props.tache.estimated_hours || null,
      actual_hours: props.tache.actual_hours || null,
      cover_image: null,
      couleur: props.tache.couleur || '#3B82F6',
      commentaire: props.tache.commentaire || '',
      assignee_ids: props.tache.assignees?.map(a => a.id) || [],
      label_ids: props.tache.labels?.map(l => l.id) || []
    }

    if (props.tache.cover_image) {
      coverImagePreview.value = props.tache.cover_image
    }
  }
})
</script>
