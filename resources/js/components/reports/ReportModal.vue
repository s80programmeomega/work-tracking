<!-- resources/js/components/reports/ReportModal.vue -->
<template>
  <TransitionRoot :show="true" as="template">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/50 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel ref="dialogRef" :style="dragStyle" class="relative w-full max-w-4xl transform overflow-hidden rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all">
              <!-- Header -->
              <div ref="handleRef" class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 cursor-move select-none">
                <div class="flex items-center justify-between">
                  <DialogTitle class="text-xl font-bold text-gray-900 dark:text-white">
                    Rapport de Performance
                  </DialogTitle>
                  <button @click="$emit('close')" class="rounded-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {{ activite.nom }} - {{ getPeriodLabel() }}
                </p>
              </div>

              <!-- Body -->
              <div class="p-6 max-h-[calc(100vh-200px)] overflow-y-auto">
                <!-- Statistiques globales -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                  <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-3 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ usersData.length }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Membres</p>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-3 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalTasks }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tâches totales</p>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-3 text-center">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ averageProgress }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Progression moyenne</p>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-3 text-center">
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ totalPendingValidation }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">En attente validation</p>
                  </div>
                </div>

                <!-- Détails par utilisateur -->
                <div class="space-y-4">
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white">Détail par collaborateur</h3>
                  <div v-for="userData in usersData" :key="userData.user.id" class="border border-gray-200 dark:border-gray-700 rounded-3 p-4">
                    <div class="flex items-center justify-between mb-3">
                      <div class="flex items-center gap-3">
                        <h4 class="font-bold text-gray-900 dark:text-white">{{ userData.user.nom }}</h4>
                        <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                          {{ getUserRoleLabel(userData.user.role) }}
                        </span>
                      </div>
                      <div class="text-right">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ userData.stats.progression_moyenne }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Progression</p>
                      </div>
                    </div>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-sm">
                      <div>
                        <p class="font-bold text-gray-900 dark:text-white">{{ userData.stats.total }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Total</p>
                      </div>
                      <div>
                        <p class="font-bold text-green-600 dark:text-green-400">{{ userData.stats.termine }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Terminé</p>
                      </div>
                      <div>
                        <p class="font-bold text-blue-600 dark:text-blue-400">{{ userData.stats.en_cours }}</p>
                        <p class="text-gray-500 dark:text-gray-400">En cours</p>
                      </div>
                      <div>
                        <p class="font-bold text-red-600 dark:text-red-400">{{ userData.stats.en_retard }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Retard</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <button @click="$emit('close')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-3 transition-colors">
                    Fermer
                  </button>
                  <button @click="printReport" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-3 hover:bg-blue-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Imprimer
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import { useDraggable } from '@/composables/useDraggable'

// Modale déplaçable par son en-tête (panneau Headless UI).
const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()
onMounted(attachHandle)

const props = defineProps({
  activite: {
    type: Object,
    required: true
  },
  usersData: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close'])

// Computed
const totalTasks = computed(() => {
  return props.usersData.reduce((sum, user) => sum + user.stats.total, 0)
})

const averageProgress = computed(() => {
  if (props.usersData.length === 0) return 0
  const sum = props.usersData.reduce((acc, user) => acc + user.stats.progression_moyenne, 0)
  return Math.round(sum / props.usersData.length)
})

const totalPendingValidation = computed(() => {
  return props.usersData.reduce((sum, user) => {
    return sum + user.taches.filter(t => t.has_result && t.validation_status === 'pending').length
  }, 0)
})

// Methods
function getPeriodLabel() {
  if (props.filters.period === 'custom' && props.filters.start_date && props.filters.end_date) {
    const start = new Date(props.filters.start_date).toLocaleDateString('fr-FR')
    const end = new Date(props.filters.end_date).toLocaleDateString('fr-FR')
    return `Personnalisée (${start} - ${end})`
  }

  const labels = {
    'current_week': 'Semaine en cours',
    'last_week': 'Semaine dernière',
    'current_month': 'Mois en cours',
    'last_month': 'Mois dernier'
  }

  return labels[props.filters.period] || 'Période non spécifiée'
}

function getUserRoleLabel(role) {
  const labels = {
    'responsable': 'Responsable',
    'membre': 'Membre',
    'observateur': 'Observateur'
  }
  return labels[role] || 'Membre'
}

function printReport() {
  window.print()
}
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>