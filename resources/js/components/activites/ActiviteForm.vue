<!-- resources/js/components/activites/ActiviteForm.vue -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 " @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-3 max-w-3xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">

      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ activite ? 'Modifier l\'activité' : 'Nouvelle activité' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ activite ? 'Mettre à jour les informations de l\'activité' : 'Créer une nouvelle activité pour votre projet' }}
            </p>
          </div>
          <button @click="$emit('close')" class="p-2 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <XIcon class="w-5 h-5 text-gray-400" />
          </button>
        </div>
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
          class="flex-shrink-0 overflow-hidden border-b-2 border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-8 py-3">
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
              class="flex-shrink-0 p-1.5 rounded-3 hover:bg-red-100 dark:hover:bg-red-800 transition-colors">
              <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </Transition>

      <!-- Form Body -->
      <div class="flex-1 overflow-y-auto px-8 py-6" ref="formScrollContainer">
        <form @submit.prevent="handleSubmit" class="space-y-6">

          <!-- Section 1: Informations générales -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Informations générales
            </h3>

            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Nom de l'activité <span class="text-red-500">*</span>
              </label>
              <input v-model="formData.nom" type="text" required placeholder="Ex: Phase de développement"
                :class="['w-full px-4 py-3 border-2 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-4 transition-all',
                  fieldErrors.nom
                    ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10 focus:ring-red-500/20 focus:border-red-500'
                    : 'border-gray-300 dark:border-gray-600 focus:border-purple-500 focus:ring-purple-500/10']" />
              <p v-if="fieldErrors.nom" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ fieldErrors.nom }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Description</label>
              <textarea v-model="formData.description" rows="3" placeholder="Décrivez les objectifs et le périmètre de cette activité..."
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"></textarea>
            </div>
          </div>

          <!-- Section 2: Projet & Responsable -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Projet & Responsable
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Projet verrouillé -->
              <div v-if="isProjetLocked">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Projet</label>
                <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-3 bg-gray-50 dark:bg-gray-900/40">
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ currentProjet?.nom || 'Projet sélectionné' }}
                      </div>
                      <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        <template v-if="currentProjet?.workspace?.nom">Workspace : {{ currentProjet.workspace.nom }}</template>
                        <template v-else>ID projet : {{ formData.projet_id }}</template>
                      </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-1 rounded-3 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">Verrouillé</span>
                  </div>
                  <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">ⓘ Le projet est fixé car ce formulaire a été ouvert depuis sa vue.</p>
                </div>
              </div>

              <!-- Sélecteur de projet -->
              <div v-else>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Projet <span class="text-red-500">*</span>
                </label>
                <select v-model="formData.projet_id" required @change="onProjetChange" :disabled="loadingProjets"
                  :class="['w-full px-4 py-3 border-2 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-4 transition-all disabled:opacity-50 disabled:cursor-not-allowed',
                    fieldErrors.projet_id
                      ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10 focus:ring-red-500/20 focus:border-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-purple-500 focus:ring-purple-500/10']">
                  <option value="">{{ loadingProjets ? 'Chargement des projets...' : 'Sélectionner un projet' }}</option>
                  <option v-for="projet in accessibleProjets" :key="projet.id" :value="projet.id">
                    {{ projet.nom }}<template v-if="projet.workspace"> - {{ projet.workspace.nom }}</template>
                  </option>
                </select>
                <p v-if="fieldErrors.projet_id" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                  <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  {{ fieldErrors.projet_id }}
                </p>
                <div v-if="!loadingProjets && accessibleProjets.length === 0" class="mt-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-3">
                  <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Aucun projet disponible</p>
                  <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">Vous n'avez accès à aucun projet dans ce workspace.</p>
                </div>
              </div>

              <!-- Responsable -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Responsable <span class="text-red-500">*</span>
                </label>
                <select v-model="formData.responsable_id" required :disabled="!formData.projet_id || loadingMembers"
                  :class="['w-full px-4 py-3 border-2 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-4 transition-all disabled:opacity-50 disabled:cursor-not-allowed',
                    fieldErrors.responsable_id
                      ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10 focus:ring-red-500/20 focus:border-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-purple-500 focus:ring-purple-500/10']">
                  <option value="">{{ loadingMembers ? 'Chargement...' : 'Sélectionner un responsable' }}</option>
                  <option v-for="member in availableMembers" :key="member.id" :value="member.id">
                    {{ member.nom }} {{ member.email ? `(${member.email})` : '' }}
                  </option>
                </select>
                <p v-if="fieldErrors.responsable_id" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                  <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  {{ fieldErrors.responsable_id }}
                </p>
                <p v-else class="mt-1 text-xs text-gray-500 dark:text-gray-400">ⓘ Seuls les membres du projet sélectionné sont disponibles</p>
              </div>
            </div>
          </div>

          <!-- Section 3: Planification -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <CalendarIcon class="w-5 h-5 text-green-500" />
              Planification
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Date de début</label>
                <DatePicker v-model="formData.date_debut" :enable-time-picker="false" auto-apply
                  :format="'yyyy-MM-dd'" :locale="'fr'" :dark="isDark"
                  :input-class-name="fieldErrors.date_debut ? 'dp-error-input' : ''"
                  placeholder="Sélectionner une date" class="w-full date-input">
                  <template #input-icon><CalendarIcon class="w-5 h-5 text-gray-400" /></template>
                </DatePicker>
                <p v-if="fieldErrors.date_debut" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                  <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  {{ fieldErrors.date_debut }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Date de fin</label>
                <DatePicker v-model="formData.date_fin" :enable-time-picker="false" auto-apply
                  :format="'yyyy-MM-dd'" :locale="'fr'" :dark="isDark" :min-date="formData.date_debut"
                  :input-class-name="fieldErrors.date_fin ? 'dp-error-input' : ''"
                  placeholder="Sélectionner une date" class="w-full date-input">
                  <template #input-icon><CalendarIcon class="w-5 h-5 text-gray-400" /></template>
                </DatePicker>
                <p v-if="fieldErrors.date_fin" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                  <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  {{ fieldErrors.date_fin }}
                </p>
              </div>
            </div>
          </div>

          <!-- Section 4: Statut & Progression -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Statut & Progression
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                <div class="grid grid-cols-2 gap-3">
                  <label v-for="statut in statusOptions" :key="statut.value"
                    class="relative flex items-center gap-3 p-3 border-2 rounded-3 cursor-pointer transition-all "
                    :class="formData.status === statut.value
                      ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                      : 'border-gray-300 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-700'">
                    <input type="radio" v-model="formData.status" :value="statut.value" class="sr-only" />
                    <span :class="statut.color" class="w-3 h-3 rounded-full flex-shrink-0"></span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ statut.label }}</span>
                    <svg v-if="formData.status === statut.value" class="absolute right-3 w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </label>
                </div>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Progression ({{ formData.progression }}%)
                </label>
                <input v-model.number="formData.progression" type="range" min="0" max="100" step="5"
                  class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full appearance-none cursor-pointer accent-purple-500" />
                <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                  <div class="h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${formData.progression}%` }"></div>
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                  <span>0%</span><span>50%</span><span>100%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 5: Apparence -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
              Apparence
            </h3>
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Couleur de l'activité</label>
              <div class="flex gap-3 items-center">
                <input v-model="formData.couleur" type="color" class="h-12 w-16 border-2 border-gray-300 dark:border-gray-600 rounded-3 cursor-pointer" />
                <input v-model="formData.couleur" type="text" placeholder="#3B82F6" pattern="^#[0-9A-Fa-f]{6}$"
                  class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all font-mono" />
                <div class="h-12 w-16 rounded-3 border-2 border-gray-300 dark:border-gray-600" :style="{ backgroundColor: formData.couleur }"></div>
              </div>
              <div class="flex gap-2 mt-3">
                <button v-for="color in presetColors" :key="color" type="button" @click="formData.couleur = color"
                  :style="{ backgroundColor: color }"
                  :class="['w-8 h-8 rounded-3 border-2 transition-transform ',
                    formData.couleur === color ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent']"></button>
              </div>
            </div>
          </div>

        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex-shrink-0">
        <div class="flex justify-end gap-3">
          <button type="button" @click="$emit('close')"
            class="px-6 py-2.5 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-3 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
            Annuler
          </button>
          <button type="submit" @click="handleSubmit" :disabled="loading"
            class="px-6 py-2.5 text-white font-semibold rounded-3 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
            <span v-if="loading" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enregistrement...
            </span>
            <span v-else>{{ activite ? 'Mettre à jour' : 'Créer l\'activité' }}</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed, nextTick } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useActivites } from '@/composables/useActivites'
import { XIcon, CalendarIcon } from '@/icons'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import api from '@/api/axios'
import { useToast } from 'vue-toastification'

const props = defineProps({
  activite: { type: Object, default: null },
  projetId: { type: [Number, String], default: null }
})
const emit = defineEmits(['close', 'saved'])

const isProjetLocked = computed(() => !!props.projetId || !!props.activite)
const authStore = useAuthStore()
const { createActivite, updateActivite } = useActivites()
const toast = useToast()

// ── DOM ref pour scroll ───────────────────────────────────────
const formScrollContainer = ref(null)

// ── État ──────────────────────────────────────────────────────
const loadingProjets    = ref(false)
const loading           = ref(false)
const loadingMembers    = ref(false)
const accessibleProjets = ref([])
const availableMembers  = ref([])
const currentProjet     = ref(null)
const errorBanner       = ref('')
const validationErrors  = ref([])
const fieldErrors       = ref({ nom: '', projet_id: '', responsable_id: '', date_debut: '', date_fin: '' })

const isDark = computed(() => document.documentElement.classList.contains('dark'))

const statusOptions = [
  { value: 'active', label: 'Actif', color: 'bg-green-500' },
  { value: 'archived', label: 'Archivé', color: 'bg-gray-500' }
]
const presetColors = ['#3B82F6','#10B981','#8B5CF6','#F59E0B','#EF4444','#EC4899','#14B8A6','#F97316']

const formData = ref({
  nom: '',
  description: '',
  projet_id: '',
  responsable_id: '',
  date_debut: '',
  date_fin: '',
  progression: 0,
  status: 'active',
  couleur: '#3B82F6'
})

// ── Helpers erreurs ───────────────────────────────────────────
const clearErrors = () => {
  errorBanner.value = ''
  validationErrors.value = []
  Object.keys(fieldErrors.value).forEach(k => (fieldErrors.value[k] = ''))
}

/**
 * Affiche le banner sticky EN HAUT du modal (hors zone scrollable).
 * Scroll en plus vers le début du form pour montrer le contexte.
 */
const showError = (message, errors = []) => {
  errorBanner.value = message
  validationErrors.value = errors
  nextTick(() => {
    formScrollContainer.value?.scrollTo({ top: 0, behavior: 'smooth' })
  })
}

// Effacer l'erreur d'un champ dès que l'utilisateur le modifie
watch(() => formData.value.nom,            () => { fieldErrors.value.nom = '' })
watch(() => formData.value.projet_id,      () => { fieldErrors.value.projet_id = '' })
watch(() => formData.value.responsable_id, () => { fieldErrors.value.responsable_id = '' })
watch(() => formData.value.date_debut,     () => { fieldErrors.value.date_debut = '' })
watch(() => formData.value.date_fin,       () => { fieldErrors.value.date_fin = '' })

// ── Chargement données ────────────────────────────────────────
const loadAccessibleProjets = async () => {
  loadingProjets.value = true
  try {
    const { data } = await api.get('/projets/mes-projets')
    accessibleProjets.value = data.data || []
  } catch {
    accessibleProjets.value = []
    toast.warning('Erreur lors du chargement des projets', { position: 'top-right' })
  } finally {
    loadingProjets.value = false
  }
}

const loadAvailableMembers = async (projetId) => {
  if (!projetId) { availableMembers.value = []; currentProjet.value = null; return }
  loadingMembers.value = true
  try {
    const projetResponse = await api.get(`/projets/${projetId}`)
    currentProjet.value = projetResponse.data.data
    const { data } = await api.get(`/activites/available-members/${projetId}`)
    availableMembers.value = data.data || []
    if (formData.value.responsable_id && !availableMembers.value.find(m => m.id === formData.value.responsable_id)) {
      formData.value.responsable_id = ''
    }
  } catch {
    availableMembers.value = []; currentProjet.value = null
    toast.warning('Erreur lors du chargement des membres du projet', { position: 'top-right' })
  } finally {
    loadingMembers.value = false
  }
}

const onProjetChange = () => { formData.value.responsable_id = '' }

// ── Soumission ────────────────────────────────────────────────
const handleSubmit = async () => {
  clearErrors()

  const errors = []
  if (!formData.value.nom?.trim()) {
    fieldErrors.value.nom = "Le nom de l'activité est obligatoire"
    errors.push(fieldErrors.value.nom)
  }
  if (!isProjetLocked.value && !formData.value.projet_id) {
    fieldErrors.value.projet_id = 'Le projet est requis'
    errors.push(fieldErrors.value.projet_id)
  }
  if (!formData.value.responsable_id) {
    fieldErrors.value.responsable_id = 'Le responsable est requis'
    errors.push(fieldErrors.value.responsable_id)
  }
  if (formData.value.date_debut && formData.value.date_fin) {
    if (new Date(formData.value.date_fin) < new Date(formData.value.date_debut)) {
      fieldErrors.value.date_fin = 'La date de fin doit être après la date de début'
      errors.push(fieldErrors.value.date_fin)
    }
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
    if (props.activite) {
      await updateActivite(props.activite.id, formData.value)
      toast.success('✅ Activité mise à jour avec succès !', { position: 'top-right', timeout: 4000 })
    } else {
      await createActivite(formData.value)
      toast.success('✅ Activité créée avec succès !', { position: 'top-right', timeout: 4000 })
    }
    emit('saved')
  } catch (error) {
    if (error.response?.status === 422) {
      const apiErrors = error.response.data.errors
      if (apiErrors) {
        const flat = Object.values(apiErrors).flat()
        if (apiErrors.nom)            fieldErrors.value.nom = apiErrors.nom[0]
        if (apiErrors.projet_id)      fieldErrors.value.projet_id = apiErrors.projet_id[0]
        if (apiErrors.responsable_id) fieldErrors.value.responsable_id = apiErrors.responsable_id[0]
        if (apiErrors.date_debut)     fieldErrors.value.date_debut = apiErrors.date_debut[0]
        if (apiErrors.date_fin)       fieldErrors.value.date_fin = apiErrors.date_fin[0]
        showError('Veuillez corriger les erreurs ci-dessous', flat)
      } else {
        showError(error.response.data.message || 'Erreur de validation')
      }
    } else if (error.response?.data?.message) {
      showError(error.response.data.message)
    } else if (error.message === 'Network Error') {
      showError('Erreur de connexion. Vérifiez votre connexion internet.')
    } else {
      showError("Une erreur s'est produite. Veuillez réessayer.")
    }
  } finally {
    loading.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────────
onMounted(async () => {
  if (props.activite) {
    await loadAccessibleProjets()
    formData.value = {
      nom: props.activite.nom || '',
      description: props.activite.description || '',
      projet_id: props.activite.projet_id || '',
      responsable_id: props.activite.responsable_id || '',
      date_debut: props.activite.date_debut || '',
      date_fin: props.activite.date_fin || '',
      progression: props.activite.progression || 0,
      status: props.activite.status || 'active',
      couleur: props.activite.couleur || '#3B82F6'
    }
    if (formData.value.projet_id) await loadAvailableMembers(formData.value.projet_id)
    return
  }
  if (props.projetId) {
    formData.value.projet_id = Number(props.projetId)
    await loadAvailableMembers(formData.value.projet_id)
    return
  }
  await loadAccessibleProjets()
})

watch(() => formData.value.projet_id, (newId) => {
  if (newId) loadAvailableMembers(newId)
  else { availableMembers.value = []; currentProjet.value = null }
})
</script>

<style scoped>
.date-input { position: relative; z-index: 1; }
.date-input:focus { z-index: 100000; }

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