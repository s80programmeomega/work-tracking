<!-- resources\js\components\taches\TacheViewModal.vue --------->
<template>
  <div class="fixed inset-0 z-990 flex items-center justify-center bg-black bg-opacity-50 p-4 " @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-4xl max-h-[95vh] overflow-hidden flex flex-col">

      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-start">
          <div class="flex-1">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ tache.titre }}</h2>
            <div class="flex items-center gap-3 flex-wrap">
              <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full "
                :class="getStatusClass(tache.statut)">
                <span class="w-2 h-2 rounded-full mr-2" :class="getStatusDotClass(tache.statut)"></span>
                {{ tache.statut_label }}
              </span>
              <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full "
                :class="getPriorityClass(tache.priorite)">
                {{ tache.priorite_icon }} {{ tache.priorite_label }}
              </span>
              <!-- Validation Status Badges -->
              <span v-if="tache.validation?.n2_validated_at" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                ✓✓ Validé N2
              </span>
              <span v-else-if="tache.validation?.n1_validated_at" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                ✓ Validé N1
              </span>
            </div>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors ml-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Body avec tabs -->
      <div class="flex-1 overflow-hidden flex flex-col">
        
        <!-- Tabs -->
        <div class="px-8 py-4 border-b border-gray-200 dark:border-gray-700 flex gap-4">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="px-4 py-2 font-medium rounded-3 transition-all"
            :class="activeTab === tab.id
              ? 'bg-brand-500 text-white '
              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
          >
            {{ tab.label }}
            <span v-if="tab.count" class="ml-2 px-2 py-0.5 text-xs rounded-full"
              :class="activeTab === tab.id ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-700'">
              {{ tab.count }}
            </span>
          </button>
        </div>

        <!-- Tab Content -->
        <div class="flex-1 overflow-y-auto">
          
          <!-- Détails Tab -->
          <div v-if="activeTab === 'details'" class="px-8 py-6">
            <!-- <TaskDetailsView :tache="tache" /> -->
          </div>

          <!-- Résultats Tab -->
          <div v-if="activeTab === 'resultats'" class="px-8 py-6">
            <ResultatsSection
              :tache="tache"
              @resultat-added="$emit('resultat-added')"
            />
          </div>

          <!-- Commentaires Tab -->
          <div v-if="activeTab === 'commentaires'" class="px-8 py-6">
            <CommentSection
              v-if="tache?.id"
              commentable-type="App\Models\Tache"
              :commentable-id="tache.id"
              :current-user-id="currentUser?.id"
            />
          </div>

          <!-- Documents Tab -->
          <div v-if="activeTab === 'documents'" class="px-8 py-6">
            <DocumentSection
              v-if="tache?.id"
              documentable-type="App\Models\Tache"
              :documentable-id="tache.id"
              :current-user-id="currentUser?.id"
            />
          </div>

        </div>
      </div>

      <!-- Footer Actions -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          <span>Créée le {{ formatDate(tache.created_at) }}</span>
        </div>
        <div class="flex gap-3">
          <button
            @click="$emit('close')"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all"
          >
            Fermer
          </button>
          <button
            v-if="tache.permissions?.can_edit"
            @click="$emit('edit', tache)"
            class="px-5 py-2.5 text-white rounded-3 font-medium transition-all"
          >
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Modifier
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import CommentSection from '@/components/comments/CommentSection.vue'
import DocumentSection from '@/components/common/DocumentSection.vue'
// import TaskDetailsView from '@/components/taches/TaskDetailsView.vue'
import ResultatsSection from '@/components/taches/ResultatsSection.vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

defineEmits(['close', 'edit', 'resultat-added'])

const activeTab = ref('details')

const tabs = computed(() => [
  { id: 'details', label: 'Détails' },
  { id: 'resultats', label: 'Résultats', count: props.tache.resultats_count || 0 },
  { id: 'commentaires', label: 'Commentaires' },
  { id: 'documents', label: 'Documents' },
])

const currentUser = computed(() => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
})

const getStatusClass = (statut) => {
  const classes = {
    'a_faire': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
  }
  return classes[statut] || classes.a_faire
}

const getStatusDotClass = (statut) => {
  const classes = {
    'a_faire': 'bg-gray-500',
    'en_cours': 'bg-blue-500',
    'termine': 'bg-green-500'
  }
  return classes[statut] || classes.a_faire
}

const getPriorityClass = (priorite) => {
  const classes = {
    'faible': 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    'moyenne': 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
    'elevee': 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    'critique': 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
  }
  return classes[priorite] || classes.moyenne
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  })
}
</script>