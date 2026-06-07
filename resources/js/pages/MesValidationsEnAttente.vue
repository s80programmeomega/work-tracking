<template>
  <AdminLayout>
    <div class="bg-gray-50/50 dark:bg-gray-900/50">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-900 border-b border-gray-200/80 dark:border-gray-800/80">
        <div class="py-8">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-5">
              <div class="relative">
                <div
                  class="w-16 h-16 rounded-3 flex items-center justify-center bg-linear-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/25">
                  <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div
                  v-if="counts.total > 0"
                  class="absolute -top-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center bg-red-500 ring-2 ring-white dark:ring-gray-900">
                  <span class="text-xs font-bold text-white">{{ counts.total }}</span>
                </div>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $t('mes_validations.title') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2 text-lg">
                  {{ $t('mes_validations.subtitle') }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="showStats = !showStats"
                class="inline-flex items-center gap-2 rounded-3 border border-gray-300/80 dark:border-gray-700/80 bg-white/80 dark:bg-gray-800/80 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                {{ $t('common.statistics') }}
              </button>
              <button @click="loadData" :disabled="loading"
                class="p-3 rounded-3 border border-gray-300/80 dark:border-gray-700/80 bg-white/80 dark:bg-gray-800/80 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 disabled:opacity-50"
                :title="$t('mes_validations.refresh')">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" :class="{ 'animate-spin': loading }"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Stats panel -->
          <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
          >
            <MesValidationsStats
              v-if="showStats"
              :counts="counts"
              :loading="loading"
              class="mt-6"
              @close="showStats = false"
            />
          </transition>

          <!-- Tabs -->
          <div class="flex gap-1 mt-8 bg-gray-100/80 dark:bg-gray-800/80 rounded-3 p-1.5">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="flex items-center gap-2.5 px-6 py-3 text-sm font-semibold transition-all duration-200 rounded-3"
              :class="activeTab === tab.id
                ? 'text-white ' + tab.activeClass
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-gray-700/50'">
              <span>{{ tab.label }}</span>
              <span v-if="tab.count > 0"
                class="px-2 py-0.5 text-xs font-bold bg-white/20 rounded-full">{{ tab.count }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="py-6">

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center items-center h-64">
          <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400">{{ $t('common.loading') }}</p>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="error"
          class="rounded-3 border border-red-200 bg-red-50/80 dark:bg-red-900/20 dark:border-red-800/50 p-8">
          <p class="text-red-700 dark:text-red-300">{{ error }}</p>
        </div>

        <!-- Empty -->
        <div v-else-if="currentResultats.length === 0" class="text-center py-20">
          <div class="w-24 h-24 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-6">
            <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ $t('mes_validations.empty_title') }}</h3>
          <p class="text-gray-600 dark:text-gray-400">{{ $t('mes_validations.empty_desc') }}</p>
        </div>

        <!-- Results list -->
        <div v-else ref="listRef" class="space-y-4">
          <div
            v-for="resultat in currentResultats"
            :key="resultat.id"
            class="stagger-item bg-white/80 dark:bg-gray-800/80 rounded-3 border border-gray-200/50 dark:border-gray-700/50 p-6">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                  {{ resultat.tache?.titre }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {{ resultat.tache?.activite?.nom }} &middot; {{ resultat.tache?.activite?.projet?.nom }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {{ $t('mes_validations.submitted') }} {{ formatDate(resultat.soumis_le) }}
                </p>
              </div>

              <div class="flex flex-col items-end gap-2 shrink-0">
                <!-- Statut badge -->
                <span
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                  :class="statutClass(resultat.statut)">
                  {{ statutLabel(resultat.statut) }}
                </span>

                <!-- Taux de réalisation -->
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ resultat.taux_realisation }}%
                </span>
              </div>
            </div>

            <!-- N1 validator info if already validated N1 -->
            <div v-if="resultat.statut === 'en_validation_n2' && resultat.validateur_n1"
              class="mt-4 pt-4 border-t border-gray-200/50 dark:border-gray-700/50 text-sm text-gray-600 dark:text-gray-400">
              Validé N1 par <span class="font-medium text-gray-900 dark:text-white">{{ resultat.validateur_n1?.nom }}</span>
              <span v-if="resultat.commentaire_n1"> &mdash; {{ resultat.commentaire_n1 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import MesValidationsStats from '@/components/evaluations/MesValidationsStats.vue'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import { useToast } from 'vue-toastification'
import { useRealtimeRefresh } from '@/composables/useRealtimeRefresh'

const { t } = useI18n()
const toast = useToast()
const { staggerRef: listRef, applyStagger } = useStagger(50)
const showStats = ref(false)

const loading = ref(false)
const error = ref(null)
const resultats = ref([])
const counts = ref({ en_validation_n1: 0, en_validation_n2: 0, total: 0 })
const activeTab = ref('n1')

const tabs = computed(() => [
  {
    id: 'n1',
    label: t('mes_validations.tab_n1'),
    count: counts.value.en_validation_n1,
    activeClass: 'bg-warning-500',
  },
  {
    id: 'n2',
    label: t('mes_validations.tab_n2'),
    count: counts.value.en_validation_n2,
    activeClass: 'bg-brand-500',
  },
])

const currentResultats = computed(() => {
  const statut = activeTab.value === 'n1' ? 'en_validation_n1' : 'en_validation_n2'
  return resultats.value.filter(r => r.statut === statut)
})

async function loadData() {
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get('/evaluations/mes-resultats/en-attente')
    resultats.value = data.data.resultats || []
    counts.value = data.data.counts || { en_validation_n1: 0, en_validation_n2: 0, total: 0 }
    applyStagger()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}

function formatDate(iso) {
  if (!iso) { return '' }
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
}

function statutLabel(statut) {
  const labels = {
    en_validation_n1: t('mes_validations.tab_n1'),
    en_validation_n2: t('mes_validations.tab_n2'),
  }
  return labels[statut] ?? statut
}

function statutClass(statut) {
  const classes = {
    en_validation_n1: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
    en_validation_n2: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
  }
  return classes[statut] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
}

useRealtimeRefresh({ onResultatChanged: () => loadData() })

onMounted(loadData)
</script>
