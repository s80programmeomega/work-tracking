<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Tableau de bord - Projets</h1>
        <p class="text-muted-foreground">Vue d'ensemble de tous vos projets</p>
      </div>
      <Button @click="openCreateDialog" class="bg-primary text-primary-foreground hover:bg-primary/90">
        <Plus class="w-4 h-4 mr-2" />
        Nouveau projet
      </Button>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card class="p-6">
        <div class="flex items-center">
          <div class="space-y-1">
            <p class="text-sm font-medium leading-none">Total Projets</p>
            <p class="text-2xl font-bold">{{ stats.total_projets }}</p>
          </div>
          <FolderOpen class="h-4 w-4 text-muted-foreground ml-auto" />
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center">
          <div class="space-y-1">
            <p class="text-sm font-medium leading-none">Projets Actifs</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.projets_actifs }}</p>
          </div>
          <Play class="h-4 w-4 text-green-600 ml-auto" />
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center">
          <div class="space-y-1">
            <p class="text-sm font-medium leading-none">En Retard</p>
            <p class="text-2xl font-bold text-red-600">{{ stats.projets_en_retard }}</p>
          </div>
          <AlertTriangle class="h-4 w-4 text-red-600 ml-auto" />
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center">
          <div class="space-y-1">
            <p class="text-sm font-medium leading-none">Budget Total</p>
            <p class="text-2xl font-bold">{{ formatCurrency(stats.budget_total) }}</p>
          </div>
          <DollarSign class="h-4 w-4 text-muted-foreground ml-auto" />
        </div>
      </Card>
    </div>

    <!-- Charts Section -->
    <div class="grid gap-4 md:grid-cols-2">
      <Card class="p-6">
        <h3 class="text-lg font-semibold mb-4">Répartition par Statut</h3>
        <div class="space-y-3">
          <div v-for="(count, status) in charts.status" :key="status" class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <Badge :variant="getStatusVariant(status)" class="w-20 justify-center">
                {{ getStatusLabel(status) }}
              </Badge>
            </div>
            <span class="font-medium">{{ count }}</span>
          </div>
        </div>
      </Card>

      <Card class="p-6">
        <h3 class="text-lg font-semibold mb-4">Répartition par Priorité</h3>
        <div class="space-y-3">
          <div v-for="(count, priorite) in charts.priorite" :key="priorite" class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <Badge :variant="getPrioriteVariant(priorite)" class="w-20 justify-center">
                {{ getPrioriteLabel(priorite) }}
              </Badge>
            </div>
            <span class="font-medium">{{ count }}</span>
          </div>
        </div>
      </Card>
    </div>

    <!-- Recent Projects -->
    <Card class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Projets Récents</h3>
        <Button variant="outline" @click="$emit('view-all')">
          Voir tout
          <ChevronRight class="w-4 h-4 ml-2" />
        </Button>
      </div>

      <div class="space-y-4">
        <div v-for="projet in projetsRecents" :key="projet.id"
             class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 cursor-pointer"
             @click="$emit('view-projet', projet.id)">
          <div class="flex-1">
            <h4 class="font-medium">{{ projet.nom }}</h4>
            <p class="text-sm text-muted-foreground">{{ projet.description?.substring(0, 100) }}...</p>
            <div class="flex items-center space-x-4 mt-2">
              <span class="text-sm text-muted-foreground">
                Responsable: {{ projet.responsable?.nom }}
              </span>
              <Badge :variant="getStatusVariant(projet.status)">
                {{ getStatusLabel(projet.status) }}
              </Badge>
            </div>
          </div>
          <div class="text-right">
            <div class="text-sm text-muted-foreground">Échéance</div>
            <div class="font-medium">{{ formatDate(projet.date_fin) }}</div>
          </div>
        </div>
      </div>
    </Card>

    <!-- Create Project Dialog -->
    <ProjetForm
      v-if="showCreateDialog"
      :open="showCreateDialog"
      @close="showCreateDialog = false"
      @created="onProjetCreated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  Plus,
  FolderOpen,
  Play,
  AlertTriangle,
  DollarSign,
  ChevronRight
} from 'lucide-vue-next'
import Card from '@/components/ui/card/Card.vue'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import ProjetForm from './ProjetForm.vue'
import { projetApi } from '@/services/api'

defineEmits(['view-all', 'view-projet'])

const stats = ref({
  total_projets: 0,
  projets_actifs: 0,
  projets_en_retard: 0,
  budget_total: 0
})

const charts = ref({
  status: {},
  priorite: {}
})

const projetsRecents = ref([])
const showCreateDialog = ref(false)
const loading = ref(false)

const fetchDashboardData = async () => {
  loading.value = true
  try {
    const response = await projetApi.getDashboard()
    stats.value = response.data.stats
    charts.value = response.data.charts
    projetsRecents.value = response.data.projets_recents
  } catch (error) {
    console.error('Erreur lors du chargement du dashboard:', error)
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
  showCreateDialog.value = true
}

const onProjetCreated = () => {
  showCreateDialog.value = false
  fetchDashboardData()
}

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

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

onMounted(() => {
  fetchDashboardData()
})
</script>