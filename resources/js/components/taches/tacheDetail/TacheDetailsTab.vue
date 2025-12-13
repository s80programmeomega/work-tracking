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
            <span class="px-3 py-1 rounded-full text-sm font-semibold" :class="getStatutClass(tache.statut)">
              {{ tache.statut_label }}
            </span>
          </div>
          
          <div v-if="tache.priorite">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Priorité</label>
            <span class="px-3 py-1 rounded-full text-sm font-semibold" :class="getPrioriteClass(tache.priorite)">
              <i :class="['fas', tache.priorite_icon, 'mr-1']"></i>
              {{ tache.priorite_label }}
            </span>
          </div>

          <div v-if="tache.taux_realisation !== null">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">
              Progression globale: {{ tache.taux_realisation }}%
            </label>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
              <div 
                class="bg-brand-600 h-3 rounded-full transition-all duration-300"
                :style="{ width: tache.taux_realisation + '%' }"
              ></div>
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
          <div v-if="tache.date_debut" class="flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Date de début</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(tache.date_debut) }}</span>
          </div>
          
          <div v-if="tache.echeance" class="flex items-center justify-between" :class="{ 'text-red-600 dark:text-red-400': tache.is_overdue }">
            <span class="text-sm">Échéance</span>
            <span class="text-sm font-medium">
              {{ formatDate(tache.echeance) }}
              <i v-if="tache.is_overdue" class="fas fa-exclamation-triangle ml-1"></i>
            </span>
          </div>

          <div v-if="tache.date_fin_reelle" class="flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Date de fin réelle</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(tache.date_fin_reelle) }}</span>
          </div>

          <div v-if="tache.week_number" class="flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Suivi hebdomadaire</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Semaine {{ tache.week_number }}/{{ tache.year }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Description et objectifs -->
    <div v-if="tache.description || tache.objectif" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-align-left text-brand-600"></i>
        Description et objectifs
      </h3>
      <div class="space-y-4">
        <div v-if="tache.description">
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Description</label>
          <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ tache.description }}</p>
        </div>
        
        <div v-if="tache.objectif">
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Objectif</label>
          <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ tache.objectif }}</p>
        </div>

        <div v-if="tache.indicateurs_resultats">
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Indicateurs de résultats</label>
          <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ tache.indicateurs_resultats }}</p>
        </div>
      </div>
    </div>

    <!-- Temps estimé vs réel -->
    <div v-if="tache.estimated_hours || tache.actual_hours" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-clock text-brand-600"></i>
        Suivi du temps
      </h3>
      <div class="grid grid-cols-2 gap-4">
        <div v-if="tache.estimated_hours">
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Temps estimé</label>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ tache.estimated_hours }}h</p>
        </div>
        <div v-if="tache.actual_hours">
          <label class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-1">Temps réel</label>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ tache.actual_hours }}h</p>
        </div>
      </div>
    </div>

    <!-- Labels -->
    <div v-if="tache.labels && tache.labels.length > 0" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-tags text-brand-600"></i>
        Étiquettes
      </h3>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="label in tache.labels" 
          :key="label.id"
          class="px-3 py-1 rounded-full text-sm font-medium"
          :style="{ backgroundColor: label.color + '20', color: label.color }"
        >
          {{ label.nom }}
        </span>
      </div>
    </div>

    <!-- Validation -->
    <div v-if="tache.validation" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-check-circle text-brand-600"></i>
        Validation
      </h3>
      <div class="space-y-4">
        <!-- Validation N1 -->
        <div v-if="tache.validation.n1_required" class="border-l-4 pl-4" :class="tache.validation.n1_validated_at ? 'border-green-500' : 'border-yellow-500'">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Validation N+1</span>
            <span v-if="tache.validation.n1_validated_at" class="text-sm text-green-600 dark:text-green-400">
              <i class="fas fa-check-circle mr-1"></i>Validé
            </span>
            <span v-else class="text-sm text-yellow-600 dark:text-yellow-400">
              <i class="fas fa-clock mr-1"></i>En attente
            </span>
          </div>
          <div v-if="tache.validation.n1_validated_at">
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Validé le {{ formatDate(tache.validation.n1_validated_at) }}
              <span v-if="tache.validation.n1_validated_by">par {{ tache.validation.n1_validated_by.nom }}</span>
            </p>
            <p v-if="tache.validation.n1_commentaire" class="text-sm text-gray-600 dark:text-gray-400 mt-1 italic">
              "{{ tache.validation.n1_commentaire }}"
            </p>
          </div>
        </div>

        <!-- Validation N2 -->
        <div v-if="tache.validation.n2_required" class="border-l-4 pl-4" :class="tache.validation.n2_validated_at ? 'border-green-500' : 'border-yellow-500'">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Validation N+2</span>
            <span v-if="tache.validation.n2_validated_at" class="text-sm text-green-600 dark:text-green-400">
              <i class="fas fa-check-circle mr-1"></i>Validé
            </span>
            <span v-else class="text-sm text-yellow-600 dark:text-yellow-400">
              <i class="fas fa-clock mr-1"></i>En attente
            </span>
          </div>
          <div v-if="tache.validation.n2_validated_at">
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Validé le {{ formatDate(tache.validation.n2_validated_at) }}
              <span v-if="tache.validation.n2_validated_by">par {{ tache.validation.n2_validated_by.nom }}</span>
            </p>
            <p v-if="tache.validation.n2_commentaire" class="text-sm text-gray-600 dark:text-gray-400 mt-1 italic">
              "{{ tache.validation.n2_commentaire }}"
            </p>
          </div>
        </div>

        <!-- Statut global -->
        <div v-if="tache.validation.is_fully_validated" class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
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
          <span class="ml-2 font-mono text-gray-900 dark:text-white">{{ tache.code }}</span>
        </div>
        <div>
          <span class="text-gray-600 dark:text-gray-400">Créé le:</span>
          <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(tache.created_at) }}</span>
        </div>
        <div>
          <span class="text-gray-600 dark:text-gray-400">Dernière mise à jour:</span>
          <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(tache.updated_at) }}</span>
        </div>
        <div v-if="tache.visibility">
          <span class="text-gray-600 dark:text-gray-400">Visibilité:</span>
          <span class="ml-2 text-gray-900 dark:text-white">{{ tache.visibility }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
});

const getStatutClass = (statut) => {
  const classes = {
    'a_faire': 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  };
  return classes[statut] || classes['a_faire'];
};

const getPrioriteClass = (priorite) => {
  const classes = {
    'haute': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'moyenne': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'basse': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  };
  return classes[priorite] || '';
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>