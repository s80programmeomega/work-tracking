<!-- resources\js\components\activites\ActivityList.vue -->
<template>
  <div class="space-y-6">
    <!-- Header with Search and Filters -->
    <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Activités</h1>
        <p class="text-muted-foreground">Gérer les activités de vos projets</p>
      </div>

      <div class="flex items-center space-x-2">
        <Button @click="openCreateDialog" class="bg-primary text-primary-foreground hover:bg-primary/90">
          <Plus class="w-4 h-4 mr-2" />
          Nouvelle activité
        </Button>
      </div>
    </div>

    <!-- Filters and Search -->
    <Card class="p-4">
      <div class="flex flex-col space-y-4 md:flex-row md:items-center md:space-y-0 md:space-x-4">
        <div class="flex-1">
          <Input
            v-model="filters.search"
            placeholder="Rechercher par nom d'activité..."
            class="max-w-sm"
            @input="debouncedSearch"
          >
            <template #prefix>
              <Search class="w-4 h-4 text-muted-foreground" />
            </template>
          </Input>
        </div>

        <Select v-model="filters.projet_id">
          <SelectTrigger class="w-52">
            <SelectValue placeholder="Projet" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="">Tous les projets</SelectItem>
            <SelectItem v-for="projet in projets" :key="projet.id" :value="projet.id.toString()">
              {{ projet.nom }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="filters.status">
          <SelectTrigger class="w-40">
            <SelectValue placeholder="Statut" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="">Tous les statuts</SelectItem>
            <SelectItem value="planifiee">Planifiée</SelectItem>
            <SelectItem value="en_cours">En cours</SelectItem>
            <SelectItem value="suspendue">Suspendue</SelectItem>
            <SelectItem value="terminee">Terminée</SelectItem>
            <SelectItem value="annulee">Annulée</SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="filters.priorite">
          <SelectTrigger class="w-40">
            <SelectValue placeholder="Priorité" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="">Toutes priorités</SelectItem>
            <SelectItem value="basse">Basse</SelectItem>
            <SelectItem value="normale">Normale</SelectItem>
            <SelectItem value="haute">Haute</SelectItem>
            <SelectItem value="critique">Critique</SelectItem>
          </SelectContent>
        </Select>

        <Button variant="outline" @click="resetFilters">
          <X class="w-4 h-4 mr-2" />
          Réinitialiser
        </Button>
      </div>
    </Card>

    <!-- Stats Summary -->
    <div class="grid gap-4 md:grid-cols-5">
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold">{{ stats.total }}</div>
          <div class="text-sm text-muted-foreground">Total</div>
        </div>
      </Card>
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ stats.planifiees }}</div>
          <div class="text-sm text-muted-foreground">Planifiées</div>
        </div>
      </Card>
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ stats.actives }}</div>
          <div class="text-sm text-muted-foreground">En cours</div>
        </div>
      </Card>
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-600">{{ stats.suspendues }}</div>
          <div class="text-sm text-muted-foreground">Suspendues</div>
        </div>
      </Card>
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ stats.terminees }}</div>
          <div class="text-sm text-muted-foreground">Terminées</div>
        </div>
      </Card>
    </div>

    <!-- Activities Table -->
    <Card>
      <div class="relative w-full overflow-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[250px]">Activité</TableHead>
              <TableHead>Projet</TableHead>
              <TableHead>Responsable</TableHead>
              <TableHead>Statut</TableHead>
              <TableHead>Priorité</TableHead>
              <TableHead>Échéance</TableHead>
              <TableHead>Progression</TableHead>
              <TableHead>Budget</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="loading">
              <TableCell colspan="9" class="text-center py-8">
                <div class="flex items-center justify-center">
                  <Loader2 class="w-6 h-6 animate-spin mr-2" />
                  Chargement...
                </div>
              </TableCell>
            </TableRow>

            <TableRow v-else-if="activites.length === 0">
              <TableCell colspan="9" class="text-center py-8">
                <div class="text-muted-foreground">
                  <Activity class="w-12 h-12 mx-auto mb-4 opacity-50" />
                  <p>Aucune activité trouvée</p>
                  <p class="text-sm">Créez votre première activité pour commencer</p>
                </div>
              </TableCell>
            </TableRow>

            <TableRow
              v-else
              v-for="activite in activites"
              :key="activite.id"
              class="hover:bg-muted/50 cursor-pointer"
              @click="$emit('view-activite', activite.id)"
            >
              <TableCell>
                <div class="space-y-1">
                  <div class="font-medium">{{ activite.nom }}</div>
                  <div class="text-sm text-muted-foreground">
                    {{ activite.description?.substring(0, 60) }}{{ activite.description?.length > 60 ? '...' : '' }}
                  </div>
                </div>
              </TableCell>

              <TableCell>
                <div class="flex items-center space-x-2">
                  <Badge variant="outline">{{ activite.projet?.nom }}</Badge>
                </div>
              </TableCell>

              <TableCell>
                <div class="flex items-center space-x-2">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-sm font-medium">
                      {{ getInitials(activite.responsable?.nom) }}
                    </span>
                  </div>
                  <span class="text-sm">{{ activite.responsable?.nom }}</span>
                </div>
              </TableCell>

              <TableCell>
                <Badge :variant="getStatusVariant(activite.status)">
                  {{ getStatusLabel(activite.status) }}
                </Badge>
              </TableCell>

              <TableCell>
                <Badge :variant="getPrioriteVariant(activite.priorite)">
                  {{ getPrioriteLabel(activite.priorite) }}
                </Badge>
              </TableCell>

              <TableCell>
                <div class="text-sm">
                  {{ formatDate(activite.date_fin) }}
                  <div v-if="isOverdue(activite)" class="text-xs text-red-600">
                    En retard
                  </div>
                </div>
              </TableCell>

              <TableCell>
                <div class="space-y-1">
                  <div class="flex items-center space-x-2">
                    <Progress :model-value="activite.pourcentage_avancement || 0" class="flex-1" />
                    <span class="text-xs text-muted-foreground w-12">
                      {{ Math.round(activite.pourcentage_avancement || 0) }}%
                    </span>
                  </div>
                </div>
              </TableCell>

              <TableCell>
                <div class="text-sm">
                  {{ activite.budget_alloue ? formatCurrency(activite.budget_alloue) : 'N/A' }}
                </div>
              </TableCell>

              <TableCell class="text-right">
                <div class="flex items-center justify-end space-x-1">
                  <Button
                    variant="ghost"
                    size="sm"
                    @click.stop="viewTasks(activite)"
                    class="text-blue-600 hover:text-blue-700"
                  >
                    <List class="w-4 h-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click.stop="editActivite(activite)"
                  >
                    <Edit class="w-4 h-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click.stop="deleteActivite(activite)"
                    class="text-destructive hover:text-destructive"
                  >
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </Card>

    <!-- Create/Edit Dialog -->
    <ActivityForm
      v-if="showDialog"
      :open="showDialog"
      :activite="selectedActivite"
      :projets="projets"
      @close="closeDialog"
      @created="onActiviteCreated"
      @updated="onActiviteUpdated"
    />

    <!-- Delete Confirmation Dialog -->
    <AlertDialog :open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Supprimer l'activité</AlertDialogTitle>
          <AlertDialogDescription>
            Êtes-vous sûr de vouloir supprimer l'activité "{{ selectedActivite?.nom }}" ?
            Cette action supprimera également toutes les tâches associées.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="showDeleteDialog = false">Annuler</AlertDialogCancel>
          <AlertDialogAction @click="confirmDelete" class="bg-destructive text-destructive-foreground">
            Supprimer
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { debounce } from 'lodash-es'
import {
  Plus,
  Search,
  X,
  Edit,
  Trash2,
  Activity,
  List,
  Loader2
} from 'lucide-vue-next'

import Card from '@/components/ui/card/Card.vue'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import Input from '@/components/ui/input/Input.vue'
import Progress from '@/components/ui/progress/Progress.vue'
import Table from '@/components/ui/table/Table.vue'
import TableHeader from '@/components/ui/table/TableHeader.vue'
import TableBody from '@/components/ui/table/TableBody.vue'
import TableRow from '@/components/ui/table/TableRow.vue'
import TableHead from '@/components/ui/table/TableHead.vue'
import TableCell from '@/components/ui/table/TableCell.vue'
import Select from '@/components/ui/select/Select.vue'
import SelectContent from '@/components/ui/select/SelectContent.vue'
import SelectItem from '@/components/ui/select/SelectItem.vue'
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue'
import SelectValue from '@/components/ui/select/SelectValue.vue'
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue'
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue'
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue'
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue'
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue'
import AlertDialogFooter from '@/components/ui/alert-dialog/AlertDialogFooter.vue'
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue'
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue'

import ActivityForm from './ActivityForm.vue'

defineEmits(['view-activite'])

const activites = ref([])
const projets = ref([])
const stats = ref({
  total: 0,
  actives: 0,
  planifiees: 0,
  suspendues: 0,
  terminees: 0
})

const loading = ref(false)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedActivite = ref(null)

const filters = reactive({
  search: '',
  projet_id: '',
  status: '',
  priorite: '',
  responsable_id: ''
})

const fetchActivites = async () => {
  loading.value = true
  try {
    // TODO: Replace with actual API call
    const response = await activiteApi.getAll(filters)
    activites.value = response.data.data
    stats.value = response.data.stats
  } catch (error) {
    console.error('Erreur lors du chargement des activités:', error)
  } finally {
    loading.value = false
  }
}

const fetchProjets = async () => {
  try {
    // TODO: Replace with actual API call
    const response = await projetApi.getAll()
    projets.value = response.data.data
  } catch (error) {
    console.error('Erreur lors du chargement des projets:', error)
  }
}

const debouncedSearch = debounce(() => {
  fetchActivites()
}, 300)

const openCreateDialog = () => {
  selectedActivite.value = null
  showDialog.value = true
}

const editActivite = (activite) => {
  selectedActivite.value = activite
  showDialog.value = true
}

const deleteActivite = (activite) => {
  selectedActivite.value = activite
  showDeleteDialog.value = true
}

const viewTasks = (activite) => {
  // TODO: Navigate to tasks view for this activity
  console.log('View tasks for activity:', activite.id)
}

const closeDialog = () => {
  showDialog.value = false
  selectedActivite.value = null
}

const onActiviteCreated = () => {
  closeDialog()
  fetchActivites()
}

const onActiviteUpdated = () => {
  closeDialog()
  fetchActivites()
}

const confirmDelete = async () => {
  try {
    // TODO: Replace with actual API call
    await activiteApi.delete(selectedActivite.value.id)
    showDeleteDialog.value = false
    selectedActivite.value = null
    fetchActivites()
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
  }
}

const resetFilters = () => {
  filters.search = ''
  filters.projet_id = ''
  filters.status = ''
  filters.priorite = ''
  filters.responsable_id = ''
  fetchActivites()
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

const isOverdue = (activite) => {
  return new Date(activite.date_fin) < new Date() && !['terminee', 'annulee'].includes(activite.status)
}

// Watch filters
watch([() => filters.status, () => filters.priorite, () => filters.projet_id, () => filters.responsable_id], () => {
  fetchActivites()
})

onMounted(() => {
  fetchProjets()
  fetchActivites()
})
</script>