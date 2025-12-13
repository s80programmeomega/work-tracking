<template>
  <div class="space-y-6">
    <!-- Mon résultat -->
    <div v-if="canSubmitResult || tache.my_result" class="bg-gradient-to-br from-brand-50 to-blue-50 dark:from-brand-900/20 dark:to-blue-900/20 rounded-xl p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
          <i class="fas fa-clipboard-check text-brand-600"></i>
          Mon résultat
        </h3>
        <div class="flex gap-2">
          <button 
            v-if="!tache.my_result && canSubmitResult"
            @click="openSubmitModal"
            class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors"
          >
            <i class="fas fa-plus mr-2"></i>Soumettre
          </button>
          <button 
            v-if="tache.my_result && !tache.my_result.valide_par_n1"
            @click="openEditModal"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            <i class="fas fa-edit mr-2"></i>Modifier
          </button>
        </div>
      </div>

      <!-- Résultat existant -->
      <div v-if="tache.my_result" class="bg-white dark:bg-gray-800 rounded-lg p-4 border-2 border-gray-200 dark:border-gray-700">
        <div class="space-y-4">
          <!-- Statut de validation -->
          <div class="flex items-center gap-3 flex-wrap">
            <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="getValidationStatusClass(tache.my_result.validation_status)">
              {{ getValidationStatusLabel(tache.my_result.validation_status) }}
            </span>
            <span class="text-sm text-gray-600 dark:text-gray-400">
              Taux: <strong class="text-gray-900 dark:text-white">{{ tache.my_result.taux_realisation }}%</strong>
            </span>
            <span v-if="tache.my_result.soumis_le" class="text-sm text-gray-600 dark:text-gray-400">
              Soumis le {{ formatDate(tache.my_result.soumis_le) }}
            </span>
          </div>

          <!-- Résultats attendus -->
          <div v-if="tache.my_result.resultats_attendus">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Résultats attendus</label>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-gray-50 dark:bg-gray-900 rounded p-3">{{ tache.my_result.resultats_attendus }}</p>
          </div>

          <!-- Résultats obtenus -->
          <div v-if="tache.my_result.resultats_obtenus">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Résultats obtenus</label>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-gray-50 dark:bg-gray-900 rounded p-3">{{ tache.my_result.resultats_obtenus }}</p>
          </div>

          <!-- Difficultés -->
          <div v-if="tache.my_result.difficultes_rencontrees">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Difficultés rencontrées</label>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-gray-50 dark:bg-gray-900 rounded p-3">{{ tache.my_result.difficultes_rencontrees }}</p>
          </div>

          <!-- Solutions -->
          <div v-if="tache.my_result.solutions_envisagees">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Solutions envisagées</label>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-gray-50 dark:bg-gray-900 rounded p-3">{{ tache.my_result.solutions_envisagees }}</p>
          </div>

          <!-- Observations -->
          <div v-if="tache.my_result.observations">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Observations</label>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-gray-50 dark:bg-gray-900 rounded p-3">{{ tache.my_result.observations }}</p>
          </div>

          <!-- Documents -->
          <div v-if="tache.my_result.documents_count > 0" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <i class="fas fa-paperclip"></i>
            <span>{{ tache.my_result.documents_count }} document(s) joint(s)</span>
          </div>

          <!-- Validation N1 -->
          <div v-if="tache.my_result.valide_par_n1" class="border-l-4 border-green-500 pl-3 py-2 bg-green-50 dark:bg-green-900/20 rounded">
            <p class="text-sm font-medium text-green-700 dark:text-green-400 mb-1">
              <i class="fas fa-check-circle mr-1"></i>Validé N+1
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-400">
              Le {{ formatDate(tache.my_result.valide_le_n1) }}
            </p>
            <p v-if="tache.my_result.commentaire_n1" class="text-sm text-gray-700 dark:text-gray-300 mt-2 italic">
              "{{ tache.my_result.commentaire_n1 }}"
            </p>
          </div>

          <!-- Validation N2 -->
          <div v-if="tache.my_result.valide_par_n2" class="border-l-4 border-green-500 pl-3 py-2 bg-green-50 dark:bg-green-900/20 rounded">
            <p class="text-sm font-medium text-green-700 dark:text-green-400 mb-1">
              <i class="fas fa-check-double mr-1"></i>Validé N+2
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-400">
              Le {{ formatDate(tache.my_result.valide_le_n2) }}
            </p>
            <p v-if="tache.my_result.commentaire_n2" class="text-sm text-gray-700 dark:text-gray-300 mt-2 italic">
              "{{ tache.my_result.commentaire_n2 }}"
            </p>
          </div>
        </div>
      </div>

      <!-- Message si pas encore soumis -->
      <div v-else-if="canSubmitResult" class="text-center py-8">
        <i class="fas fa-clipboard-list text-4xl text-gray-300 dark:text-gray-700 mb-3"></i>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Vous n'avez pas encore soumis de résultat</p>
      </div>
    </div>

    <!-- Tous les résultats (pour responsables) -->
    <div v-if="tache.all_results && tache.all_results.length > 0">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-users text-brand-600"></i>
        Résultats de l'équipe ({{ tache.all_results.length }})
      </h3>

      <div class="space-y-4">
        <div 
          v-for="result in tache.all_results" 
          :key="result.id"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 border-2 border-gray-200 dark:border-gray-700"
        >
          <div class="flex items-start gap-4">
            <!-- Avatar -->
            <div class="flex-shrink-0">
              <img 
                v-if="result.user?.avatar" 
                :src="result.user.avatar" 
                :alt="result.user.nom"
                class="w-10 h-10 rounded-full"
              />
              <div v-else class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-semibold text-sm">
                {{ getInitials(result.user?.nom) }}
              </div>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="font-semibold text-gray-900 dark:text-white">{{ result.user?.nom }}</h4>
                <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="getValidationStatusClass(result.validation_status)">
                  {{ getValidationStatusLabel(result.validation_status) }}
                </span>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                  {{ result.taux_realisation }}%
                </span>
              </div>

              <p v-if="result.resultats_obtenus" class="text-sm text-gray-700 dark:text-gray-300 mb-2 line-clamp-2">
                {{ result.resultats_obtenus }}
              </p>

              <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span v-if="result.soumis_le">
                  <i class="fas fa-calendar mr-1"></i>{{ formatDate(result.soumis_le) }}
                </span>
                <span v-if="result.documents_count > 0">
                  <i class="fas fa-paperclip mr-1"></i>{{ result.documents_count }} doc(s)
                </span>
                <span v-if="result.valide_par_n1" class="text-green-600 dark:text-green-400">
                  <i class="fas fa-check mr-1"></i>N+1
                </span>
                <span v-if="result.valide_par_n2" class="text-green-600 dark:text-green-400">
                  <i class="fas fa-check-double mr-1"></i>N+2
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <button 
                @click="viewResult(result)"
                class="p-2 text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/20 rounded-lg"
                title="Voir détails"
              >
                <i class="fas fa-eye"></i>
              </button>
              <button 
                v-if="permissions.can_validate_n1 && !result.valide_par_n1"
                @click="validateN1(result)"
                class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg"
                title="Valider N+1"
              >
                <i class="fas fa-check"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- État vide -->
    <div v-if="!canSubmitResult && !tache.my_result && (!tache.all_results || tache.all_results.length === 0)" class="text-center py-12">
      <i class="fas fa-clipboard text-6xl text-gray-300 dark:text-gray-700 mb-4"></i>
      <p class="text-gray-600 dark:text-gray-400">Aucun résultat soumis</p>
    </div>

    <!-- Modal de soumission -->
    <ResultSubmitModal 
      v-if="showSubmitModal"
      :tache="tache"
      @close="showSubmitModal = false"
      @submitted="handleResultSubmitted"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
// import ResultSubmitModal from './ResultSubmitModal.vue';

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  permissions: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['refresh']);

const { showSuccess, showError } = useNotifications();
const showSubmitModal = ref(false);

const canSubmitResult = computed(() => {
  return props.tache.my_status?.statut === 'termine' && !props.tache.my_result;
});

const openSubmitModal = () => {
  showSubmitModal.value = true;
};

const openEditModal = () => {
  showSubmitModal.value = true;
};

const handleResultSubmitted = () => {
  showSubmitModal.value = false;
  emit('refresh');
};

const viewResult = (result) => {
  // TODO: Ouvrir modal de détails
  console.log('View result:', result);
};

const validateN1 = async (result) => {
  // TODO: Implémenter validation N1
  console.log('Validate N1:', result);
};

const getValidationStatusClass = (status) => {
  const classes = {
    'not_submitted': 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    'pending_n1': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'pending_n2': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'fully_validated': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  };
  return classes[status] || classes['not_submitted'];
};

const getValidationStatusLabel = (status) => {
  const labels = {
    'not_submitted': 'Non soumis',
    'pending_n1': 'En attente N+1',
    'pending_n2': 'En attente N+2',
    'fully_validated': 'Validé',
  };
  return labels[status] || status;
};

const getInitials = (name) => {
  if (!name) return '?';
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>