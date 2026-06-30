<!-- resources\js\components\activites\ActivityForm.vue -->
<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ activite ? 'Modifier l\'activité' : 'Nouvelle activité' }}
        </DialogTitle>
        <DialogDescription>
          {{ activite ? 'Modifiez les informations de l\'activité' : 'Créez une nouvelle activité pour votre projet' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Basic Information -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium">Informations générales</h3>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <Label for="nom" class="text-sm font-medium">Nom de l'activité *</Label>
              <Input
                id="nom"
                v-model="form.nom"
                placeholder="Nom de l'activité"
                :class="{ 'border-red-500': errors.nom }"
                required
              />
              <p v-if="errors.nom" class="text-sm text-red-600 mt-1">{{ errors.nom[0] }}</p>
            </div>

            <div>
              <Label for="projet_id" class="text-sm font-medium">Projet *</Label>
              <Select v-model="form.projet_id" required>
                <SelectTrigger :class="{ 'border-red-500': errors.projet_id }">
                  <SelectValue placeholder="Sélectionnez un projet" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="projet in projets" :key="projet.id" :value="projet.id.toString()">
                    {{ projet.nom }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.projet_id" class="text-sm text-red-600 mt-1">{{ errors.projet_id[0] }}</p>
            </div>
          </div>

          <div>
            <Label for="description" class="text-sm font-medium">Description</Label>
            <Textarea
              id="description"
              v-model="form.description"
              placeholder="Description détaillée des objectifs de l'activité..."
              rows="3"
              :class="{ 'border-red-500': errors.description }"
            />
            <p v-if="errors.description" class="text-sm text-red-600 mt-1">{{ errors.description[0] }}</p>
          </div>
        </div>

        <!-- Assignment and Scheduling -->
        <Separator />
        <div class="space-y-4">
          <h3 class="text-lg font-medium">Attribution et planification</h3>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <Label for="responsable_id" class="text-sm font-medium">Responsable *</Label>
              <Select v-model="form.responsable_id" required>
                <SelectTrigger :class="{ 'border-red-500': errors.responsable_id }">
                  <SelectValue placeholder="Sélectionnez un responsable" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                    {{ user.nom }} - {{ user.fonction }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.responsable_id" class="text-sm text-red-600 mt-1">{{ errors.responsable_id[0] }}</p>
            </div>

            <div>
              <Label for="priorite" class="text-sm font-medium">Priorité *</Label>
              <Select v-model="form.priorite" required>
                <SelectTrigger :class="{ 'border-red-500': errors.priorite }">
                  <SelectValue placeholder="Sélectionnez une priorité" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="basse">
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                      <span>Basse</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="normale">
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                      <span>Normale</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="haute">
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                      <span>Haute</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="critique">
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 rounded-full bg-red-500"></div>
                      <span>Critique</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.priorite" class="text-sm text-red-600 mt-1">{{ errors.priorite[0] }}</p>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <Label for="date_debut" class="text-sm font-medium">Date de début *</Label>
              <Input
                id="date_debut"
                v-model="form.date_debut"
                type="date"
                :class="{ 'border-red-500': errors.date_debut }"
                required
              />
              <p v-if="errors.date_debut" class="text-sm text-red-600 mt-1">{{ errors.date_debut[0] }}</p>
            </div>

            <div>
              <Label for="date_fin" class="text-sm font-medium">Date de fin *</Label>
              <Input
                id="date_fin"
                v-model="form.date_fin"
                type="date"
                :class="{ 'border-red-500': errors.date_fin }"
                required
              />
              <p v-if="errors.date_fin" class="text-sm text-red-600 mt-1">{{ errors.date_fin[0] }}</p>
            </div>
          </div>
        </div>

        <!-- Status and Budget -->
        <Separator />
        <div class="space-y-4">
          <h3 class="text-lg font-medium">Statut et budget</h3>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <Label for="status" class="text-sm font-medium">Statut *</Label>
              <Select v-model="form.status" required>
                <SelectTrigger :class="{ 'border-red-500': errors.status }">
                  <SelectValue placeholder="Sélectionnez un statut" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="planifiee">
                    <div class="flex items-center space-x-2">
                      <Calendar class="w-4 h-4" />
                      <span>Planifiée</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="en_cours">
                    <div class="flex items-center space-x-2">
                      <Play class="w-4 h-4" />
                      <span>En cours</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="suspendue">
                    <div class="flex items-center space-x-2">
                      <Pause class="w-4 h-4" />
                      <span>Suspendue</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="terminee">
                    <div class="flex items-center space-x-2">
                      <CheckCircle class="w-4 h-4" />
                      <span>Terminée</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="annulee">
                    <div class="flex items-center space-x-2">
                      <X class="w-4 h-4" />
                      <span>Annulée</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.status" class="text-sm text-red-600 mt-1">{{ errors.status[0] }}</p>
            </div>

            <div>
              <Label for="budget_alloue" class="text-sm font-medium">Budget alloué (€)</Label>
              <Input
                id="budget_alloue"
                v-model="form.budget_alloue"
                type="number"
                step="0.01"
                placeholder="0.00"
                :class="{ 'border-red-500': errors.budget_alloue }"
              />
              <p v-if="errors.budget_alloue" class="text-sm text-red-600 mt-1">{{ errors.budget_alloue[0] }}</p>
            </div>
          </div>
        </div>

        <DialogFooter class="flex justify-end space-x-2 pt-4">
          <Button type="button" variant="outline" @click="handleClose" :disabled="loading">
            Annuler
          </Button>
          <Button type="submit" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
            {{ activite ? 'Modifier' : 'Créer' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue'
import api from '@/api/axios'
import { useWorkspace } from '@/composables/useWorkspace'
import {
  Calendar,
  Play,
  Pause,
  CheckCircle,
  X,
  Loader2
} from 'lucide-vue-next'

import Dialog from '@/components/ui/dialog/Dialog.vue'
import DialogContent from '@/components/ui/dialog/DialogContent.vue'
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue'
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue'
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Textarea from '@/components/ui/textarea/Textarea.vue'
import Label from '@/components/ui/label/Label.vue'
import Select from '@/components/ui/select/Select.vue'
import SelectContent from '@/components/ui/select/SelectContent.vue'
import SelectItem from '@/components/ui/select/SelectItem.vue'
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue'
import SelectValue from '@/components/ui/select/SelectValue.vue'
import Separator from '@/components/ui/separator/Separator.vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  activite: {
    type: Object,
    default: null
  },
  projets: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'created', 'updated'])

const { currentWorkspaceId } = useWorkspace()

const loading = ref(false)
const users = ref([])
const errors = ref({})

const form = reactive({
  nom: '',
  description: '',
  projet_id: '',
  responsable_id: '',
  date_debut: '',
  date_fin: '',
  status: 'planifiee',
  priorite: 'normale',
  budget_alloue: ''
})

const resetForm = () => {
  form.nom = ''
  form.description = ''
  form.projet_id = ''
  form.responsable_id = ''
  form.date_debut = ''
  form.date_fin = ''
  form.status = 'planifiee'
  form.priorite = 'normale'
  form.budget_alloue = ''
  errors.value = {}
}

const loadFormData = () => {
  if (props.activite) {
    form.nom = props.activite.nom || ''
    form.description = props.activite.description || ''
    form.projet_id = props.activite.projet_id?.toString() || ''
    form.responsable_id = props.activite.responsable_id?.toString() || ''
    form.date_debut = props.activite.date_debut ? props.activite.date_debut.split('T')[0] : ''
    form.date_fin = props.activite.date_fin ? props.activite.date_fin.split('T')[0] : ''
    form.status = props.activite.status || 'planifiee'
    form.priorite = props.activite.priorite || 'normale'
    form.budget_alloue = props.activite.budget_alloue || ''
  } else {
    resetForm()
  }
}

const fetchUsers = async () => {
  const workspaceId = currentWorkspaceId.value
  if (!workspaceId) { return }
  try {
    const { data } = await api.get(`/workspaces/${workspaceId}/users`)
    users.value = data.data || data || []
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  }
}

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const formData = {
      ...form,
      projet_id: parseInt(form.projet_id),
      responsable_id: parseInt(form.responsable_id),
      budget_alloue: form.budget_alloue ? parseFloat(form.budget_alloue) : null
    }

    if (props.activite) {
      // TODO: Replace with actual API call
      await activiteApi.update(props.activite.id, formData)
      emit('updated')
    } else {
      // TODO: Replace with actual API call
      await activiteApi.create(formData)
      emit('created')
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Erreur lors de la sauvegarde:', error)
    }
  } finally {
    loading.value = false
  }
}

const handleClose = () => {
  if (!loading.value) {
    emit('close')
  }
}

// Watch for prop changes
watch(() => props.open, (newValue) => {
  if (newValue) {
    nextTick(() => {
      loadFormData()
      if (users.value.length === 0) {
        fetchUsers()
      }
    })
  } else {
    resetForm()
  }
}, { immediate: true })

watch(() => props.activite, () => {
  if (props.open) {
    loadFormData()
  }
})
</script>