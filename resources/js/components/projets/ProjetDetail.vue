<template>
  <div v-if="loading" class="flex items-center justify-center py-12">
    <Loader2 class="w-8 h-8 animate-spin" />
    <span class="ml-2">Chargement...</span>
  </div>

  <div v-else-if="projet" class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
      <div class="space-y-1">
        <div class="flex items-center space-x-3">
          <Button variant="ghost" size="sm" @click="$emit('back')" class="p-0">
            <ArrowLeft class="w-4 h-4" />
          </Button>
          <h1 class="text-3xl font-bold">{{ projet.nom }}</h1>
          <Badge :variant="getStatusVariant(projet.status)">
            {{ getStatusLabel(projet.status) }}
          </Badge>
        </div>
        <p class="text-muted-foreground">{{ projet.description }}</p>
      </div>

      <div class="flex items-center space-x-2">
        <Button variant="outline" @click="editProjet">
          <Edit class="w-4 h-4 mr-2" />
          Modifier
        </Button>
        <Button @click="$emit('create-activity')">
          <Plus class="w-4 h-4 mr-2" />
          Nouvelle activité
        </Button>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid gap-4 md:grid-cols-4">
      <Card class="p-4">
        <div class="flex items-center space-x-2">
          <Calendar class="w-4 h-4 text-muted-foreground" />
          <div class="space-y-1">
            <p class="text-sm font-medium">Durée</p>
            <p class="text-2xl font-bold">{{ projet.duree_jours }} j</p>
          </div>
        </div>
      </Card>

      <Card class="p-4">
        <div class="flex items-center space-x-2">
          <Clock class="w-4 h-4 text-muted-foreground" />
          <div class="space-y-1">
            <p class="text-sm font-medium">Jours restants</p>
            <p class="text-2xl font-bold" :class="getTimeRemainingClass()">
              {{ projet.jours_restants }}
            </p>
          </div>
        </div>
      </Card>

      <Card class="p-4">
        <div class="flex items-center space-x-2">
          <TrendingUp class="w-4 h-4 text-muted-foreground" />
          <div class="space-y-1">
            <p class="text-sm font-medium">Progression</p>
            <p class="text-2xl font-bold">{{ Math.round(projet.progression_globale || 0) }}%</p>
          </div>
        </div>
      </Card>

      <Card class="p-4">
        <div class="flex items-center space-x-2">
          <DollarSign class="w-4 h-4 text-muted-foreground" />
          <div class="space-y-1">
            <p class="text-sm font-medium">Budget</p>
            <p class="text-2xl font-bold">
              {{ projet.budget ? formatCurrency(projet.budget) : 'N/A' }}
            </p>
          </div>
        </div>
      </Card>
    </div>

    <!-- Progress and Timeline -->
    <div class="grid gap-4 md:grid-cols-2">
      <Card class="p-6">
        <h3 class="text-lg font-semibold mb-4">Progression du projet</h3>
        <div class="space-y-4">
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span>Avancement global</span>
              <span>{{ Math.round(projet.progression_globale || 0) }}%</span>
            </div>
            <Progress :model-value="projet.progression_globale || 0" />
          </div>

          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span>Progression temporelle</span>
              <span>{{ Math.round(projet.progression_temporelle || 0) }}%</span>
            </div>
            <Progress
              :model-value="projet.progression_temporelle || 0"
              class="[&_[data-slot=progress-indicator]]:bg-blue-500"
            />
          </div>

          <div class="pt-2">
            <div class="flex items-center space-x-2 text-sm">
              <Badge :variant="getHealthVariant()">
                {{ getHealthStatus() }}
              </Badge>
              <span class="text-muted-foreground">{{ getHealthDescription() }}</span>
            </div>
          </div>
        </div>
      </Card>

      <Card class="p-6">
        <h3 class="text-lg font-semibold mb-4">Informations projet</h3>
        <dl class="space-y-3">
          <div class="flex justify-between">
            <dt class="text-sm text-muted-foreground">Responsable</dt>
            <dd class="text-sm font-medium">{{ projet.responsable?.nom }}</dd>
          </div>

          <div class="flex justify-between">
            <dt class="text-sm text-muted-foreground">Priorité</dt>
            <dd>
              <Badge :variant="getPrioriteVariant(projet.priorite)">
                {{ getPrioriteLabel(projet.priorite) }}
              </Badge>
            </dd>
          </div>

          <div class="flex justify-between">
            <dt class="text-sm text-muted-foreground">Date début</dt>
            <dd class="text-sm font-medium">{{ formatDate(projet.date_debut) }}</dd>
          </div>

          <div class="flex justify-between">
            <dt class="text-sm text-muted-foreground">Date fin</dt>
            <dd class="text-sm font-medium">{{ formatDate(projet.date_fin) }}</dd>
          </div>

          <div class="flex justify-between">
            <dt class="text-sm text-muted-foreground">Créé le</dt>
            <dd class="text-sm font-medium">{{ formatDate(projet.created_at) }}</dd>
          </div>
        </dl>
      </Card>
    </div>

    <!-- Activities Section -->
    <Card class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Activités ({{ projet.activites?.length || 0 }})</h3>
        <Button @click="$emit('create-activity')">
          <Plus class="w-4 h-4 mr-2" />
          Nouvelle activité
        </Button>
      </div>

      <div v-if="!projet.activites || projet.activites.length === 0" class="text-center py-8">
        <FolderOpen class="w-12 h-12 mx-auto mb-4 opacity-50" />
        <p class="text-muted-foreground mb-2">Aucune activité pour ce projet</p>
        <p class="text-sm text-muted-foreground">Créez votre première activité pour commencer</p>
        <Button @click="$emit('create-activity')" class="mt-4">
          <Plus class="w-4 h-4 mr-2" />
          Créer une activité
        </Button>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="activite in projet.activites"
          :key="activite.id"
          class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 cursor-pointer"
          @click="$emit('view-activity', activite.id)"
        >
          <div class="flex-1">
            <h4 class="font-medium">{{ activite.nom }}</h4>
            <p class="text-sm text-muted-foreground">{{ activite.description }}</p>
            <div class="flex items-center space-x-4 mt-2">
              <span class="text-xs text-muted-foreground">
                {{ activite.taches_count || 0 }} tâche(s)
              </span>
              <Badge :variant="getStatusVariant(activite.status)">
                {{ getStatusLabel(activite.status) }}
              </Badge>
            </div>
          </div>

          <div class="text-right space-y-1">
            <div class="text-sm font-medium">{{ Math.round(activite.taux_realisation || 0) }}%</div>
            <Progress :model-value="activite.taux_realisation || 0" class="w-20" />
          </div>
        </div>
      </div>
    </Card>

    <!-- Edit Dialog -->
    <ProjetForm
      v-if="showEditDialog"
      :open="showEditDialog"
      :projet="projet"
      @close="showEditDialog = false"
      @updated="onProjetUpdated"
    />
  </div>

  <div v-else class="text-center py-12">
    <AlertTriangle class="w-12 h-12 mx-auto mb-4 opacity-50" />
    <p class="text-muted-foreground">Projet non trouvé</p>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import {
  ArrowLeft,
  Edit,
  Plus,
  Calendar,
  Clock,
  TrendingUp,
  DollarSign,
  FolderOpen,
  AlertTriangle,
  Loader2
} from 'lucide-vue-next'

import Card from '@/components/ui/card/Card.vue'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Progress from '@/components/ui/progress/Progress.vue'
import ProjetForm from './ProjetForm.vue'
import { projetApi } from '@/services/api'

const props = defineProps({
  projetId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['back', 'create-activity', 'view-activity'])

const projet = ref(null)
const loading = ref(false)
const showEditDialog = ref(false)

const fetchProjet = async () => {
  loading.value = true
  try {
    const response = await projetApi.get(props.projetId)
    projet.value = response.data.data
  } catch (error) {
    console.error('Erreur lors du chargement du projet:', error)
  } finally {
    loading.value = false
  }
}

const editProjet = () => {
  showEditDialog.value = true
}

const onProjetUpdated = () => {
  showEditDialog.value = false
  fetchProjet()
}

// Health calculation
const getHealthVariant = () => {
  if (!projet.value) return 'secondary'

  const progression = projet.value.progression_globale || 0
  const temporelle = projet.value.progression_temporelle || 0

  const diff = temporelle - progression

  if (diff > 20) return 'destructive' // En retard
  if (diff > 10) return 'default' // Attention
  return 'outline' // Dans les temps
}

const getHealthStatus = () => {
  if (!projet.value) return 'Inconnu'

  const progression = projet.value.progression_globale || 0
  const temporelle = projet.value.progression_temporelle || 0

  const diff = temporelle - progression

  if (diff > 20) return 'En retard'
  if (diff > 10) return 'Attention'
  return 'Dans les temps'
}

const getHealthDescription = () => {
  if (!projet.value) return ''

  const progression = projet.value.progression_globale || 0
  const temporelle = projet.value.progression_temporelle || 0

  const diff = Math.abs(temporelle - progression)

  if (temporelle > progression) {
    return `${Math.round(diff)}% de retard sur la planification`
  } else if (progression > temporelle) {
    return `${Math.round(diff)}% d'avance sur la planification`
  }
  return 'Parfaitement aligné avec la planification'
}

const getTimeRemainingClass = () => {
  if (!projet.value) return ''

  const remaining = projet.value.jours_restants
  if (remaining <= 0) return 'text-red-600'
  if (remaining <= 7) return 'text-orange-600'
  return 'text-green-600'
}

// Helper functions
const getStatusVariant = (status) => {
  const variants = {
    'planifie': 'secondary',
    'en_cours': 'default',
    'suspendu': 'destructive',
    'termine': 'outline',
    'annule': 'destructive'
  }
  return variants[status] || 'secondary'
}

const getStatusLabel = (status) => {
  const labels = {
    'planifie': 'Planifié',
    'en_cours': 'En cours',
    'suspendu': 'Suspendu',
    'termine': 'Terminé',
    'annule': 'Annulé'
  }
  return labels[status] || status
}

const getPrioriteVariant = (priorite) => {
  const variants = {
    'basse': 'secondary',
    'normale': 'outline',
    'haute': 'default',
    'critique': 'destructive'
  }
  return variants[priorite] || 'secondary'
}

const getPrioriteLabel = (priorite) => {
  const labels = {
    'basse': 'Basse',
    'normale': 'Normale',
    'haute': 'Haute',
    'critique': 'Critique'
  }
  return labels[priorite] || priorite
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

onMounted(() => {
  fetchProjet()
})
</script>