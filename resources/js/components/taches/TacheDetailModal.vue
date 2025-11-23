<!-- resources/js/components/taches/TacheDetailModal.vue - VERSION HYBRIDE -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-gradient bg-opacity-50 p-4 backdrop-blur-sm" 
       @click.self="$emit('close')">
    
    <!-- Container principal avec taille adaptative -->
    <!-- <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full overflow-hidden flex flex-col transition-all duration-300"> -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-hidden flex flex-col":class="modalSizeClass">
      
      <!-- Header unifié -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
        <div class="flex justify-between items-start">
          <!-- Titre et badges -->
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ tache.titre }}</h2>
            <div class="flex items-center gap-3 flex-wrap">
              <!-- Badges statut et priorité -->
              <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full shadow-sm"
                    :class="getStatusClass(tache.statut)">
                <span class="w-2 h-2 rounded-full mr-2" :class="getStatusDotClass(tache.statut)"></span>
                {{ tache.statut_label }}
              </span>
              
              <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full shadow-sm"
                    :class="getPriorityClass(tache.priorite)">
                {{ getPriorityIcon(tache.priorite) }} {{ tache.priorite_label }}
              </span>

              <!-- Badges validation -->
              <span v-if="tache.validation?.n2_validated_at" 
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                ✓✓ Validé N2
              </span>
              <span v-else-if="tache.validation?.n1_validated_at" 
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                ✓ Validé N1
              </span>
            </div>
          </div>

          <!-- Actions header -->
          <div class="flex items-center gap-2 ml-4">
            <!-- Bascule mode détaillé -->
            <button 
              v-if="!isDetailedView"
              @click="enableDetailedView"
              class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
              title="Vue détaillée">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
            </button>

            <button @click="$emit('close')" 
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Onglets - Seulement en mode détaillé -->
        <div v-if="isDetailedView" class="mt-4 flex gap-4 border-b border-gray-200 dark:border-gray-700">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="px-4 py-2 font-medium rounded-t-lg transition-all border-b-2"
            :class="activeTab === tab.id
              ? 'border-brand-500 text-brand-600 dark:text-brand-400'
              : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
          >
            {{ tab.label }}
            <span v-if="tab.count" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700">
              {{ tab.count }}
            </span>
          </button>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="flex-1 overflow-y-auto">
        
        <!-- MODE RAPIDE -->
        <div v-if="!isDetailedView" class="px-8 py-6">
          <div class="grid grid-cols-3 gap-8">
            <!-- Colonne principale -->
            <div class="col-span-2 space-y-6">
              <!-- Description -->
              <SectionCollapsible title="Description" :default-open="!!tache.description">
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ tache.description || 'Aucune description' }}</p>
              </SectionCollapsible>

              <!-- Objectif -->
              <SectionCollapsible title="Objectif" :default-open="!!tache.objectif">
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ tache.objectif || 'Aucun objectif défini' }}</p>
              </SectionCollapsible>

              <!-- Indicateurs -->
              <SectionCollapsible title="Indicateurs de résultats" :default-open="!!tache.indicateurs_resultats">
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ tache.indicateurs_resultats || 'Aucun indicateur défini' }}</p>
              </SectionCollapsible>

              <!-- Commentaire récent -->
              <SectionCollapsible v-if="latestComment" title="Dernier commentaire" :default-open="true">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                  <p class="text-gray-700 dark:text-gray-300">{{ latestComment.content }}</p>
                  <p class="text-xs text-gray-500 mt-2">
                    Par {{ latestComment.user?.name }} • {{ formatRelativeTime(latestComment.created_at) }}
                  </p>
                </div>
                <button 
                  @click="enableDetailedView('commentaires')"
                  class="mt-2 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400">
                  Voir tous les commentaires →
                </button>
              </SectionCollapsible>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
              <!-- Actions rapides -->
              <QuickActionsPanel 
                :tache="tache"
                @edit="$emit('edit', tache)"
                @validate-n1="$emit('validate-n1', tache)"
                @validate-n2="$emit('validate-n2', tache)"
                @complete="handleCompleteTask"
              />

              <!-- Informations essentielles -->
              <EssentialInfoPanel :tache="tache" />

              <!-- Assignés -->
              <AssigneesPanel :assignees="tache.assignees" />

              <!-- Labels -->
              <LabelsPanel v-if="tache.labels?.length > 0" :labels="tache.labels" />
            </div>
          </div>
        </div>

        <!-- MODE DÉTAILLÉ -->
        <div v-else class="px-8 py-6">
          
          <!-- Onglet Détails -->
          <div v-if="activeTab === 'details'">
            <DetailedTaskView :tache="tache" />
          </div>

          <!-- Onglet Résultats -->
          <div v-if="activeTab === 'resultats'">
            <ResultatsSection
              :tache="tache"
              @resultat-added="$emit('resultat-added')"
            />
          </div>

          <!-- Onglet Commentaires -->
          <div v-if="activeTab === 'commentaires'">
            <CommentSection
              v-if="tache?.id"
              commentable-type="App\Models\Tache"
              :commentable-id="tache.id"
              :current-user-id="currentUser?.id"
            />
          </div>

          <!-- Onglet Documents -->
          <div v-if="activeTab === 'documents'">
            <DocumentSection
              v-if="tache?.id"
              documentable-type="App\Models\Tache"
              :documentable-id="tache.id"
              :current-user-id="currentUser?.id"
            />
          </div>

        </div>
      </div>

      <!-- Footer adaptatif -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          <span>Créée le {{ formatDate(tache.created_at) }}</span>
          <span v-if="tache.updated_at !== tache.created_at" class="ml-3">
            • Modifiée le {{ formatDate(tache.updated_at) }}
          </span>
        </div>
        
        <div class="flex gap-3">
          <button
            @click="$emit('close')"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all"
          >
            Fermer
          </button>
          
          <button
            v-if="tache.permissions?.can_edit"
            @click="$emit('edit', tache)"
            class="px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
import { ref, computed, onMounted } from 'vue'
import { useTaches } from '@/composables/useTaches'
import CommentSection from '@/components/comments/CommentSection.vue'
import DocumentSection from '@/components/common/DocumentSection.vue'
import ResultatsSection from '@/components/taches/ResultatsSection.vue'

// Composants modulaires pour le mode rapide
import QuickActionsPanel from './panels/QuickActionsPanel.vue'
import EssentialInfoPanel from './panels/EssentialInfoPanel.vue'
// import AssigneesPanel from './panels/AssigneesPanel.vue'
// import LabelsPanel from './panels/LabelsPanel.vue'
// import SectionCollapsible from './panels/SectionCollapsible.vue'
// import DetailedTaskView from './DetailedTaskView.vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'edit', 'validate-n1', 'validate-n2', 'resultat-added'])

const { completeTache } = useTaches()

// État réactif
const isDetailedView = ref(false)
const activeTab = ref('details')
const latestComment = ref(null)

// Computed
const modalSizeClass = computed(() => 
  isDetailedView.value ? 'max-w-7xl max-h-[95vh]' : 'max-w-4xl max-h-[90vh]'
)

const tabs = computed(() => [
  { id: 'details', label: 'Détails' },
  { id: 'resultats', label: 'Résultats', count: props.tache.resultats_count || 0 },
  { id: 'commentaires', label: 'Commentaires', count: props.tache.comments_count || 0 },
  { id: 'documents', label: 'Documents', count: props.tache.documents_count || 0 },
])

const currentUser = computed(() => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
})

// Détection automatique du mode détaillé
const shouldUseDetailedView = computed(() => {
  const t = props.tache
  return (
    (t.comments_count > 3) ||
    (t.documents_count > 2) ||
    (t.resultats_count > 5) ||
    (t.description?.length > 500) ||
    (t.assignees?.length > 5)
  )
})

// Méthodes
const enableDetailedView = (tab = 'details') => {
  isDetailedView.value = true
  activeTab.value = tab
}

const handleCompleteTask = async () => {
  if (!props.tache.permissions?.can_complete) {
    alert('Vous n\'avez pas la permission de marquer cette tâche comme terminée')
    return
  }

  try {
    await completeTache(props.tache.id)
    emit('close')
  } catch (error) {
    console.error('Error completing task:', error)
    alert(error.response?.data?.message || 'Erreur lors de la complétion de la tâche')
  }
}

// Méthodes utilitaires (conservées de l'original)
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

const getPriorityIcon = (priorite) => {
  const icons = {
    faible: '🟢',
    moyenne: '🟡',
    elevee: '🟠',
    critique: '🔴'
  }
  return icons[priorite] || '🟡'
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatRelativeTime = (date) => {
  // Implémentation simplifiée
  const now = new Date()
  const diffMs = now - new Date(date)
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 60) return `il y a ${diffMins} min`
  if (diffHours < 24) return `il y a ${diffHours} h`
  if (diffDays < 7) return `il y a ${diffDays} j`
  return formatDate(date)
}

// Lifecycle
onMounted(() => {
  // Détection automatique du mode au chargement
  if (shouldUseDetailedView.value) {
    isDetailedView.value = true
  }

  // Charger le dernier commentaire pour l'aperçu
  // (à implémenter avec votre API)
})
</script>