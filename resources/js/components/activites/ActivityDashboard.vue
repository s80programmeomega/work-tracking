<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold tracking-tight">Dashboard Activités</h1>
      <p class="text-muted-foreground">Vue d'ensemble de toutes vos activités</p>
    </div>

    <!-- Key Metrics -->
    <div class="grid gap-4 md:grid-cols-4">
      <Card class="p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-2xl font-bold">{{ metrics.total }}</div>
            <div class="text-sm text-muted-foreground">Total activités</div>
          </div>
          <Activity class="w-8 h-8 text-blue-600" />
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-2xl font-bold text-green-600">{{ metrics.enCours }}</div>
            <div class="text-sm text-muted-foreground">En cours</div>
          </div>
          <Play class="w-8 h-8 text-green-600" />
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-2xl font-bold text-orange-600">{{ metrics.enRetard }}</div>
            <div class="text-sm text-muted-foreground">En retard</div>
          </div>
          <AlertTriangle class="w-8 h-8 text-orange-600" />
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-2xl font-bold text-gray-600">{{ metrics.terminees }}</div>
            <div class="text-sm text-muted-foreground">Terminées</div>
          </div>
          <CheckCircle class="w-8 h-8 text-gray-600" />
        </div>
      </Card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <!-- Status Distribution Chart -->
      <Card class="p-6">
        <div class="space-y-4">
          <h3 class="text-lg font-semibold flex items-center">
            <PieChart class="w-5 h-5 mr-2" />
            Répartition par statut
          </h3>
          <div class="h-80">
            <!-- TODO: Replace with actual chart component -->
            <div class="flex items-center justify-center h-full bg-muted/20 rounded-3">
              <div class="text-center">
                <PieChart class="w-12 h-12 mx-auto mb-2 text-muted-foreground" />
                <p class="text-muted-foreground">Graphique en secteurs</p>
                <p class="text-sm text-muted-foreground">Distribution des statuts</p>
              </div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Priority Distribution -->
      <Card class="p-6">
        <div class="space-y-4">
          <h3 class="text-lg font-semibold flex items-center">
            <BarChart class="w-5 h-5 mr-2" />
            Répartition par priorité
          </h3>
          <div class="space-y-3">
            <div v-for="priority in priorityStats" :key="priority.name" class="space-y-2">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 rounded-full" :class="priority.color"></div>
                  <span class="text-sm font-medium">{{ priority.name }}</span>
                </div>
                <span class="text-sm text-muted-foreground">{{ priority.count }}</span>
              </div>
              <Progress :model-value="priority.percentage" class="h-2" />
            </div>
          </div>
        </div>
      </Card>

      <!-- Progress Overview -->
      <Card class="p-6">
        <div class="space-y-4">
          <h3 class="text-lg font-semibold flex items-center">
            <TrendingUp class="w-5 h-5 mr-2" />
            Progression globale
          </h3>
          <div class="space-y-4">
            <div class="text-center">
              <div class="text-4xl font-bold text-blue-600">
                {{ Math.round(metrics.progressionMoyenne) }}%
              </div>
              <div class="text-sm text-muted-foreground">Progression moyenne</div>
            </div>
            <Progress :model-value="metrics.progressionMoyenne" class="w-full h-3" />
            <div class="grid grid-cols-3 gap-4 text-center">
              <div>
                <div class="text-lg font-semibold text-red-600">{{ metrics.tachesAFaire }}</div>
                <div class="text-xs text-muted-foreground">À faire</div>
              </div>
              <div>
                <div class="text-lg font-semibold text-blue-600">{{ metrics.tachesEnCours }}</div>
                <div class="text-xs text-muted-foreground">En cours</div>
              </div>
              <div>
                <div class="text-lg font-semibold text-green-600">{{ metrics.tachesTerminees }}</div>
                <div class="text-xs text-muted-foreground">Terminées</div>
              </div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Timeline Chart -->
      <Card class="p-6">
        <div class="space-y-4">
          <h3 class="text-lg font-semibold flex items-center">
            <Calendar class="w-5 h-5 mr-2" />
            Timeline activités
          </h3>
          <div class="h-80">
            <!-- TODO: Replace with actual timeline/Gantt chart -->
            <div class="flex items-center justify-center h-full bg-muted/20 rounded-3">
              <div class="text-center">
                <Calendar class="w-12 h-12 mx-auto mb-2 text-muted-foreground" />
                <p class="text-muted-foreground">Timeline des activités</p>
                <p class="text-sm text-muted-foreground">Vue chronologique</p>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- Recent Activities -->
    <Card class="p-6">
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold flex items-center">
            <Clock class="w-5 h-5 mr-2" />
            Activités récentes
          </h3>
          <Button variant="outline" size="sm" @click="$emit('view-all')">
            Voir tout
          </Button>
        </div>

        <div class="space-y-3">
          <div
            v-for="activite in recentActivites"
            :key="activite.id"
            class="flex items-center justify-between p-4 border rounded-3 hover:bg-muted/50 cursor-pointer"
            @click="$emit('view-activite', activite.id)"
          >
            <div class="space-y-1">
              <div class="font-medium">{{ activite.nom }}</div>
              <div class="text-sm text-muted-foreground">
                {{ activite.projet?.nom }} • {{ activite.responsable?.nom }}
              </div>
            </div>

            <div class="flex items-center space-x-4">
              <div class="text-right">
                <div class="text-sm font-medium">{{ Math.round(activite.pourcentage_avancement || 0) }}%</div>
                <Progress :model-value="activite.pourcentage_avancement || 0" class="w-20 h-2" />
              </div>
              <Badge :variant="getStatusVariant(activite.status)">
                {{ getStatusLabel(activite.status) }}
              </Badge>
            </div>
          </div>

          <div v-if="recentActivites.length === 0" class="text-center py-8 text-muted-foreground">
            <Activity class="w-12 h-12 mx-auto mb-4 opacity-50" />
            <p>Aucune activité récente</p>
          </div>
        </div>
      </div>
    </Card>

    <!-- Team Performance -->
    <Card class="p-6">
      <div class="space-y-4">
        <h3 class="text-lg font-semibold flex items-center">
          <Users class="w-5 h-5 mr-2" />
          Performance équipe
        </h3>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="member in teamPerformance"
            :key="member.id"
            class="p-4 border rounded-3"
          >
            <div class="flex items-center space-x-3 mb-3">
              <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="text-sm font-medium">
                  {{ getInitials(member.nom) }}
                </span>
              </div>
              <div>
                <div class="font-medium">{{ member.nom }}</div>
                <div class="text-sm text-muted-foreground">{{ member.fonction }}</div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm">Activités</span>
                <span class="text-sm font-medium">{{ member.activites_count }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm">Tâches</span>
                <span class="text-sm font-medium">{{ member.taches_count }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm">Progression</span>
                <span class="text-sm font-medium">{{ Math.round(member.progression_moyenne) }}%</span>
              </div>
              <Progress :model-value="member.progression_moyenne" class="h-2" />
            </div>
          </div>

          <div v-if="teamPerformance.length === 0" class="col-span-full text-center py-8 text-muted-foreground">
            <Users class="w-12 h-12 mx-auto mb-4 opacity-50" />
            <p>Aucune donnée d'équipe disponible</p>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Activity,
  Play,
  AlertTriangle,
  CheckCircle,
  PieChart,
  BarChart,
  TrendingUp,
  Calendar,
  Clock,
  Users
} from 'lucide-vue-next'

import Card from '@/components/ui/card/Card.vue'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Progress from '@/components/ui/progress/Progress.vue'

defineEmits(['view-all', 'view-activite'])

const metrics = ref({
  total: 0,
  enCours: 0,
  enRetard: 0,
  terminees: 0,
  progressionMoyenne: 0,
  tachesAFaire: 0,
  tachesEnCours: 0,
  tachesTerminees: 0
})

const recentActivites = ref([])
const teamPerformance = ref([])

const priorityStats = computed(() => {
  const stats = [
    { name: 'Critique', count: 3, color: 'bg-red-500', percentage: 15 },
    { name: 'Haute', count: 8, color: 'bg-orange-500', percentage: 40 },
    { name: 'Normale', count: 12, color: 'bg-blue-500', percentage: 60 },
    { name: 'Basse', count: 2, color: 'bg-gray-400', percentage: 10 }
  ]

  const total = stats.reduce((sum, stat) => sum + stat.count, 0)
  return stats.map(stat => ({
    ...stat,
    percentage: total > 0 ? (stat.count / total) * 100 : 0
  }))
})

const fetchDashboardData = async () => {
  try {
    // TODO: Replace with actual API calls
    const [metricsResponse, activitesResponse, teamResponse] = await Promise.all([
      activiteApi.getMetrics(),
      activiteApi.getRecent(),
      activiteApi.getTeamPerformance()
    ])

    metrics.value = metricsResponse.data
    recentActivites.value = activitesResponse.data
    teamPerformance.value = teamResponse.data
  } catch (error) {
    console.error('Erreur lors du chargement du dashboard:', error)
  }
}

// Helper functions
const getInitials = (name) => {
  if (!name) return ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getStatusVariant = (status) => {
  const variants = {
    'planifiee': 'secondary',
    'en_cours': 'default',
    'suspendue': 'destructive',
    'terminee': 'outline',
    'annulee': 'destructive'
  }
  return variants[status] || 'secondary'
}

const getStatusLabel = (status) => {
  const labels = {
    'planifiee': 'Planifiée',
    'en_cours': 'En cours',
    'suspendue': 'Suspendue',
    'terminee': 'Terminée',
    'annulee': 'Annulée'
  }
  return labels[status] || status
}

onMounted(() => {
  fetchDashboardData()
})
</script>