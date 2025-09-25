<template>
  <div class="space-y-6">
    <!-- Header with Search and Filters -->
    <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Projets</h1>
        <p class="text-muted-foreground">Gérer tous vos projets</p>
      </div>

      <div class="flex items-center space-x-2">
        <Button @click="openCreateDialog" class="bg-primary text-primary-foreground hover:bg-primary/90">
          <Plus class="w-4 h-4 mr-2" />
          Nouveau projet
        </Button>
      </div>
    </div>

    <!-- Filters and Search -->
    <Card class="p-4">
      <div class="flex flex-col space-y-4 md:flex-row md:items-center md:space-y-0 md:space-x-4">
        <div class="flex-1">
          <Input
            v-model="filters.search"
            placeholder="Rechercher par nom ou description..."
            class="max-w-sm"
            @input="debouncedSearch"
          >
            <template #prefix>
              <Search class="w-4 h-4 text-muted-foreground" />
            </template>
          </Input>
        </div>

        <Select v-model="filters.status">
          <SelectTrigger class="w-40">
            <SelectValue placeholder="Statut" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="">Tous les statuts</SelectItem>
            <SelectItem value="planifie">Planifié</SelectItem>
            <SelectItem value="en_cours">En cours</SelectItem>
            <SelectItem value="suspendu">Suspendu</SelectItem>
            <SelectItem value="termine">Terminé</SelectItem>
            <SelectItem value="annule">Annulé</SelectItem>
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
    <div class="grid gap-4 md:grid-cols-4">
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold">{{ stats.total }}</div>
          <div class="text-sm text-muted-foreground">Total</div>
        </div>
      </Card>
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ stats.planifies }}</div>
          <div class="text-sm text-muted-foreground">Planifiés</div>
        </div>
      </Card>
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ stats.actifs }}</div>
          <div class="text-sm text-muted-foreground">Actifs</div>
        </div>
      </Card>
      <Card class="p-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-600">{{ stats.termines }}</div>
          <div class="text-sm text-muted-foreground">Terminés</div>
        </div>
      </Card>
    </div>

    <!-- Projects Table -->
    <Card>
      <div class="relative w-full overflow-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[300px]">Projet</TableHead>
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
              <TableCell colspan="8" class="text-center py-8">
                <div class="flex items-center justify-center">
                  <Loader2 class="w-6 h-6 animate-spin mr-2" />
                  Chargement...
                </div>
              </TableCell>
            </TableRow>

            <TableRow v-else-if="projets.length === 0">
              <TableCell colspan="8" class="text-center py-8">
                <div class="text-muted-foreground">
                  <FolderOpen class="w-12 h-12 mx-auto mb-4 opacity-50" />
                  <p>Aucun projet trouvé</p>
                  <p class="text-sm">Créez votre premier projet pour commencer</p>
                </div>
              </TableCell>
            </TableRow>

            <TableRow
              v-else
              v-for="projet in projets"
              :key="projet.id"
              class="hover:bg-muted/50 cursor-pointer"
              @click="$emit('view-projet', projet.id)"
            >
              <TableCell>
                <div class="space-y-1">
                  <div class="font-medium">{{ projet.nom }}</div>
                  <div class="text-sm text-muted-foreground">
                    {{ projet.description?.substring(0, 80) }}{{ projet.description?.length > 80 ? '...' : '' }}
                  </div>
                </div>
              </TableCell>

              <TableCell>
                <div class="flex items-center space-x-2">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-sm font-medium">
                      {{ getInitials(projet.responsable?.nom) }}
                    </span>
                  </div>
                  <span class="text-sm">{{ projet.responsable?.nom }}</span>
                </div>
              </TableCell>

              <TableCell>
                <Badge :variant="getStatusVariant(projet.status)">
                  {{ getStatusLabel(projet.status) }}
                </Badge>
              </TableCell>

              <TableCell>
                <Badge :variant="getPrioriteVariant(projet.priorite)">
                  {{ getPrioriteLabel(projet.priorite) }}
                </Badge>
              </TableCell>

              <TableCell>
                <div class="text-sm">
                  {{ formatDate(projet.date_fin) }}
                  <div v-if="isOverdue(projet)" class="text-xs text-red-600">
                    En retard
                  </div>
                </div>
              </TableCell>

              <TableCell>
                <div class="space-y-1">
                  <div class="flex items-center space-x-2">
                    <Progress :model-value="projet.progression_globale || 0" class="flex-1" />
                    <span class="text-xs text-muted-foreground w-12">
                      {{ Math.round(projet.progression_globale || 0) }}%
                    </span>
                  </div>
                </div>
              </TableCell>

              <TableCell>
                <div class="text-sm">
                  {{ projet.budget ? formatCurrency(projet.budget) : 'N/A' }}
                </div>
              </TableCell>

              <TableCell class="text-right">
                <div class="flex items-center justify-end space-x-1">
                  <Button
                    variant="ghost"
                    size="sm"
                    @click.stop="editProjet(projet)"
                  >
                    <Edit class="w-4 h-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click.stop="deleteProjet(projet)"
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
    <ProjetForm
      v-if="showDialog"
      :open="showDialog"
      :projet="selectedProjet"
      @close="closeDialog"
      @created="onProjetCreated"
      @updated="onProjetUpdated"
    />

    <!-- Delete Confirmation Dialog -->
    <AlertDialog :open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Supprimer le projet</AlertDialogTitle>
          <AlertDialogDescription>
            Êtes-vous sûr de vouloir supprimer le projet "{{ selectedProjet?.nom }}" ?
            Cette action est irréversible.
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
  FolderOpen,
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

import ProjetForm from './ProjetForm.vue'
import { projetApi } from '@/services/api'

defineEmits(['view-projet'])

const projets = ref([])
const stats = ref({
  total: 0,
  actifs: 0,
  planifies: 0,
  termines: 0
})

const loading = ref(false)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedProjet = ref(null)

const filters = reactive({
  search: '',
  status: '',
  priorite: '',
  responsable_id: ''
})

const fetchProjets = async () => {
  loading.value = true
  try {
    const response = await projetApi.getAll(filters)
    projets.value = response.data.data
    stats.value = response.data.stats
  } catch (error) {
    console.error('Erreur lors du chargement des projets:', error)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  fetchProjets()
}, 300)

const openCreateDialog = () => {
  selectedProjet.value = null
  showDialog.value = true
}

const editProjet = (projet) => {
  selectedProjet.value = projet
  showDialog.value = true
}

const deleteProjet = (projet) => {
  selectedProjet.value = projet
  showDeleteDialog.value = true
}

const closeDialog = () => {
  showDialog.value = false
  selectedProjet.value = null
}

const onProjetCreated = () => {
  closeDialog()
  fetchProjets()
}

const onProjetUpdated = () => {
  closeDialog()
  fetchProjets()
}

const confirmDelete = async () => {
  try {
    await projetApi.delete(selectedProjet.value.id)
    showDeleteDialog.value = false
    selectedProjet.value = null
    fetchProjets()
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
  }
}

const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.priorite = ''
  filters.responsable_id = ''
  fetchProjets()
}

// Helper functions
const getInitials = (name) => {
  if (!name) return ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
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

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const isOverdue = (projet) => {
  return new Date(projet.date_fin) < new Date() && projet.status !== 'termine'
}

// Watch filters
watch([() => filters.status, () => filters.priorite, () => filters.responsable_id], () => {
  fetchProjets()
})

onMounted(() => {
  fetchProjets()
})
</script>