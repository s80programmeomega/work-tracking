<template>
  <div class="space-y-6" v-if="activite">
    <!-- Header with Breadcrumb -->
    <div class="space-y-4">
      <Breadcrumb>
        <BreadcrumbList>
          <BreadcrumbItem>
            <BreadcrumbLink @click="$emit('go-back')">Activités</BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbLink @click="navigateToProject">{{ activite.projet?.nom }}</BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbPage>{{ activite.nom }}</BreadcrumbPage>
          </BreadcrumbItem>
        </BreadcrumbList>
      </Breadcrumb>

      <div class="flex flex-col space-y-4 sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
        <div class="space-y-2">
          <h1 class="text-3xl font-bold tracking-tight">{{ activite.nom }}</h1>
          <div class="flex items-center space-x-4">
            <Badge :variant="getStatusVariant(activite.status)">
              {{ getStatusLabel(activite.status) }}
            </Badge>
            <Badge :variant="getPrioriteVariant(activite.priorite)">
              {{ getPrioriteLabel(activite.priorite) }}
            </Badge>
            <span class="text-muted-foreground">
              Créé le {{ formatDate(activite.created_at) }}
            </span>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <Button variant="outline" @click="$emit('edit-activite', activite)">
            <Edit class="w-4 h-4 mr-2" />
            Modifier
          </Button>
          <Button @click="$emit('view-tasks', activite.id)">
            <List class="w-4 h-4 mr-2" />
            Voir les tâches
          </Button>
        </div>
      </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid gap-4 md:grid-cols-4">
      <Card class="p-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ taches.length }}</div>
          <div class="text-sm text-muted-foreground">Tâches totales</div>
        </div>
      </Card>
      <Card class="p-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">
            {{ taches.filter(t => t.status === 'termine').length }}
          </div>
          <div class="text-sm text-muted-foreground">Tâches terminées</div>
        </div>
      </Card>
      <Card class="p-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-600">
            {{ Math.round(activite.pourcentage_avancement || 0) }}%
          </div>
          <div class="text-sm text-muted-foreground">Avancement</div>
        </div>
      </Card>
      <Card class="p-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600">
            {{ activite.budget_alloue ? formatCurrency(activite.budget_alloue) : 'N/A' }}
          </div>
          <div class="text-sm text-muted-foreground">Budget alloué</div>
        </div>
      </Card>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Description -->
        <Card class="p-6">
          <div class="space-y-4">
            <h3 class="text-lg font-semibold flex items-center">
              <FileText class="w-5 h-5 mr-2" />
              Description
            </h3>
            <p class="text-muted-foreground">
              {{ activite.description || 'Aucune description fournie.' }}
            </p>
          </div>
        </Card>

        <!-- Progress Overview -->
        <Card class="p-6">
          <div class="space-y-4">
            <h3 class="text-lg font-semibold flex items-center">
              <TrendingUp class="w-5 h-5 mr-2" />
              Progression globale
            </h3>
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">Avancement</span>
                <span class="text-sm text-muted-foreground">
                  {{ Math.round(activite.pourcentage_avancement || 0) }}%
                </span>
              </div>
              <Progress :model-value="activite.pourcentage_avancement || 0" class="w-full" />
            </div>

            <div class="grid gap-4 mt-4 sm:grid-cols-2">
              <div class="text-center p-4 border rounded-lg">
                <div class="text-2xl font-bold text-red-600">
                  {{ taches.filter(t => t.status === 'a_faire').length }}
                </div>
                <div class="text-sm text-muted-foreground">À faire</div>
              </div>
              <div class="text-center p-4 border rounded-lg">
                <div class="text-2xl font-bold text-blue-600">
                  {{ taches.filter(t => t.status === 'en_cours').length }}
                </div>
                <div class="text-sm text-muted-foreground">En cours</div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Recent Tasks -->
        <Card class="p-6">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold flex items-center">
                <CheckSquare class="w-5 h-5 mr-2" />
                Tâches récentes
              </h3>
              <Button variant="outline" size="sm" @click="$emit('view-tasks', activite.id)">
                Voir tout
              </Button>
            </div>

            <div class="space-y-3">
              <div
                v-for="tache in recentTaches"
                :key="tache.id"
                class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50"
              >
                <div class="space-y-1">
                  <div class="font-medium">{{ tache.titre }}</div>
                  <div class="text-sm text-muted-foreground">
                    Assignée à {{ tache.assignee?.nom }}
                  </div>
                </div>
                <Badge :variant="getTaskStatusVariant(tache.status)">
                  {{ getTaskStatusLabel(tache.status) }}
                </Badge>
              </div>

              <div v-if="recentTaches.length === 0" class="text-center py-8 text-muted-foreground">
                <CheckSquare class="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>Aucune tâche créée</p>
                <p class="text-sm">Créez des tâches pour cette activité</p>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Activity Info -->
        <Card class="p-6">
          <div class="space-y-4">
            <h3 class="text-lg font-semibold flex items-center">
              <Info class="w-5 h-5 mr-2" />
              Informations
            </h3>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">Projet</span>
                <Badge variant="outline">{{ activite.projet?.nom }}</Badge>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">Responsable</span>
                <div class="flex items-center space-x-2">
                  <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-xs font-medium">
                      {{ getInitials(activite.responsable?.nom) }}
                    </span>
                  </div>
                  <span class="text-sm">{{ activite.responsable?.nom }}</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">Date début</span>
                <span class="text-sm text-muted-foreground">
                  {{ formatDate(activite.date_debut) }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">Date fin</span>
                <span class="text-sm" :class="{ 'text-red-600': isOverdue }">
                  {{ formatDate(activite.date_fin) }}
                </span>
              </div>

              <div v-if="isOverdue" class="flex items-center space-x-2 text-red-600">
                <AlertTriangle class="w-4 h-4" />
                <span class="text-sm font-medium">En retard</span>
              </div>
            </div>
          </div>
        </Card>

        <!-- Team Members -->
        <Card class="p-6">
          <div class="space-y-4">
            <h3 class="text-lg font-semibold flex items-center">
              <Users class="w-5 h-5 mr-2" />
              Équipe
            </h3>

            <div class="space-y-3">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                  <Crown class="w-4 h-4 text-orange-500" />
                </div>
                <div class="flex-1">
                  <div class="text-sm font-medium">{{ activite.responsable?.nom }}</div>
                  <div class="text-xs text-muted-foreground">Responsable</div>
                </div>
              </div>

              <div v-for="member in teamMembers" :key="member.id" class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                  <span class="text-sm font-medium">
                    {{ getInitials(member.nom) }}
                  </span>
                </div>
                <div class="flex-1">
                  <div class="text-sm font-medium">{{ member.nom }}</div>
                  <div class="text-xs text-muted-foreground">{{ member.fonction }}</div>
                </div>
              </div>

              <div v-if="teamMembers.length === 0" class="text-sm text-muted-foreground text-center py-4">
                Aucun autre membre assigné
              </div>
            </div>
          </div>
        </Card>

        <!-- Quick Actions -->
        <Card class="p-6">
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Actions rapides</h3>

            <div class="space-y-2">
              <Button variant="outline" size="sm" class="w-full justify-start" @click="createTask">
                <Plus class="w-4 h-4 mr-2" />
                Créer une tâche
              </Button>

              <Button variant="outline" size="sm" class="w-full justify-start" @click="generateReport">
                <FileText class="w-4 h-4 mr-2" />
                Générer un rapport
              </Button>

              <Button variant="outline" size="sm" class="w-full justify-start" @click="viewGantt">
                <Calendar class="w-4 h-4 mr-2" />
                Vue planning
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Loader2 class="w-8 h-8 animate-spin" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  Edit,
  List,
  FileText,
  TrendingUp,
  CheckSquare,
  Info,
  Users,
  Crown,
  Plus,
  Calendar,
  AlertTriangle,
  Loader2
} from 'lucide-vue-next'

import Card from '@/components/ui/card/Card.vue'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Progress from '@/components/ui/progress/Progress.vue'
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue'
import BreadcrumbList from '@/components/ui/breadcrumb/BreadcrumbList.vue'
import BreadcrumbItem from '@/components/ui/breadcrumb/BreadcrumbItem.vue'
import BreadcrumbLink from '@/components/ui/breadcrumb/BreadcrumbLink.vue'
import BreadcrumbPage from '@/components/ui/breadcrumb/BreadcrumbPage.vue'
import BreadcrumbSeparator from '@/components/ui/breadcrumb/BreadcrumbSeparator.vue'

const props = defineProps({
  activite: {
    type: Object,
    default: null
  },
  taches: {
    type: Array,
    default: () => []
  }
})

defineEmits(['go-back', 'edit-activite', 'view-tasks', 'create-task'])

const teamMembers = ref([])

const recentTaches = computed(() => {
  return props.taches
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 5)
})

const isOverdue = computed(() => {
  if (!props.activite?.date_fin) return false
  return new Date(props.activite.date_fin) < new Date() &&
         !['terminee', 'annulee'].includes(props.activite.status)
})

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

const getTaskStatusVariant = (status) => {
  const variants = {
    'a_faire': 'secondary',
    'en_cours': 'default',
    'termine': 'outline'
  }
  return variants[status] || 'secondary'
}

const getTaskStatusLabel = (status) => {
  const labels = {
    'a_faire': 'À faire',
    'en_cours': 'En cours',
    'termine': 'Terminé'
  }
  return labels[status] || status
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

const navigateToProject = () => {
  // TODO: Navigate to project detail
  console.log('Navigate to project:', props.activite?.projet_id)
}

const createTask = () => {
  // TODO: Open task creation modal
  console.log('Create task for activity:', props.activite?.id)
}

const generateReport = () => {
  // TODO: Generate activity report
  console.log('Generate report for activity:', props.activite?.id)
}

const viewGantt = () => {
  // TODO: Show Gantt chart view
  console.log('View Gantt for activity:', props.activite?.id)
}
</script>