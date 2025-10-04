<template>
  <Dialog :open="open" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>
          {{ isEditing ? 'Modifier le projet' : 'Créer un nouveau projet' }}
        </DialogTitle>
        <DialogDescription>
          {{ isEditing ? 'Modifiez les informations du projet.' : 'Remplissez les informations pour créer un nouveau projet.' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Nom du projet -->
        <div class="space-y-2">
          <Label for="nom">Nom du projet *</Label>
          <Input
            id="nom"
            v-model="form.nom"
            placeholder="Nom du projet"
            :class="{ 'border-destructive': errors.nom }"
          />
          <p v-if="errors.nom" class="text-sm text-destructive">{{ errors.nom[0] }}</p>
        </div>

        <!-- Description -->
        <div class="space-y-2">
          <Label for="description">Description</Label>
          <Textarea
            id="description"
            v-model="form.description"
            placeholder="Description détaillée du projet"
            rows="4"
            :class="{ 'border-destructive': errors.description }"
          />
          <p v-if="errors.description" class="text-sm text-destructive">{{ errors.description[0] }}</p>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="date_debut">Date de début *</Label>
            <Input
              id="date_debut"
              v-model="form.date_debut"
              type="date"
              :class="{ 'border-destructive': errors.date_debut }"
            />
            <p v-if="errors.date_debut" class="text-sm text-destructive">{{ errors.date_debut[0] }}</p>
          </div>

          <div class="space-y-2">
            <Label for="date_fin">Date de fin *</Label>
            <Input
              id="date_fin"
              v-model="form.date_fin"
              type="date"
              :class="{ 'border-destructive': errors.date_fin }"
            />
            <p v-if="errors.date_fin" class="text-sm text-destructive">{{ errors.date_fin[0] }}</p>
          </div>
        </div>

        <!-- Responsable -->
        <div class="space-y-2">
          <Label for="responsable">Responsable du projet *</Label>
          <Select v-model="form.responsable_id">
            <SelectTrigger :class="{ 'border-destructive': errors.responsable_id }">
              <SelectValue placeholder="Sélectionner un responsable" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="responsable in responsables"
                :key="responsable.id"
                :value="responsable.id.toString()"
              >
                {{ responsable.nom }} ({{ responsable.email }})
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="errors.responsable_id" class="text-sm text-destructive">{{ errors.responsable_id[0] }}</p>
        </div>

        <!-- Status et Priorité -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="status">Statut *</Label>
            <Select v-model="form.status">
              <SelectTrigger :class="{ 'border-destructive': errors.status }">
                <SelectValue placeholder="Sélectionner un statut" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="planifie">
                  <div class="flex items-center space-x-2">
                    <Badge variant="secondary" class="text-xs">Planifié</Badge>
                  </div>
                </SelectItem>
                <SelectItem value="en_cours">
                  <div class="flex items-center space-x-2">
                    <Badge variant="default" class="text-xs">En cours</Badge>
                  </div>
                </SelectItem>
                <SelectItem value="suspendu">
                  <div class="flex items-center space-x-2">
                    <Badge variant="destructive" class="text-xs">Suspendu</Badge>
                  </div>
                </SelectItem>
                <SelectItem value="termine">
                  <div class="flex items-center space-x-2">
                    <Badge variant="outline" class="text-xs">Terminé</Badge>
                  </div>
                </SelectItem>
                <SelectItem value="annule">
                  <div class="flex items-center space-x-2">
                    <Badge variant="destructive" class="text-xs">Annulé</Badge>
                  </div>
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="errors.status" class="text-sm text-destructive">{{ errors.status[0] }}</p>
          </div>

          <div class="space-y-2">
            <Label for="priorite">Priorité *</Label>
            <Select v-model="form.priorite">
              <SelectTrigger :class="{ 'border-destructive': errors.priorite }">
                <SelectValue placeholder="Sélectionner une priorité" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="basse">
                  <div class="flex items-center space-x-2">
                    <Badge variant="secondary" class="text-xs">Basse</Badge>
                  </div>
                </SelectItem>
                <SelectItem value="normale">
                  <div class="flex items-center space-x-2">
                    <Badge variant="outline" class="text-xs">Normale</Badge>
                  </div>
                </SelectItem>
                <SelectItem value="haute">
                  <div class="flex items-center space-x-2">
                    <Badge variant="default" class="text-xs">Haute</Badge>
                  </div>
                </SelectItem>
                <SelectItem value="critique">
                  <div class="flex items-center space-x-2">
                    <Badge variant="destructive" class="text-xs">Critique</Badge>
                  </div>
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="errors.priorite" class="text-sm text-destructive">{{ errors.priorite[0] }}</p>
          </div>
        </div>

        <!-- Budget -->
        <div class="space-y-2">
          <Label for="budget">Budget (€)</Label>
          <Input
            id="budget"
            v-model.number="form.budget"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
            :class="{ 'border-destructive': errors.budget }"
          />
          <p v-if="errors.budget" class="text-sm text-destructive">{{ errors.budget[0] }}</p>
          <p class="text-xs text-muted-foreground">Laissez vide si aucun budget n'est défini</p>
        </div>

        <!-- Objectifs stratégiques (si édition) -->
        <div v-if="isEditing" class="space-y-2">
          <Label for="objectifs">Objectifs stratégiques</Label>
          <Textarea
            id="objectifs"
            v-model="form.objectifs"
            placeholder="Définissez les objectifs stratégiques du projet"
            rows="3"
          />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="$emit('close')" :disabled="loading">
            Annuler
          </Button>
          <Button type="submit" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
            {{ isEditing ? 'Mettre à jour' : 'Créer le projet' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Loader2 } from 'lucide-vue-next'

import Dialog from '@/components/ui/dialog/Dialog.vue'
import DialogContent from '@/components/ui/dialog/DialogContent.vue'
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue'
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue'
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Textarea from '@/components/ui/textarea/Textarea.vue'
import Label from '@/components/ui/label/Label.vue'
import Select from '@/components/ui/select/Select.vue'
import SelectContent from '@/components/ui/select/SelectContent.vue'
import SelectItem from '@/components/ui/select/SelectItem.vue'
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue'
import SelectValue from '@/components/ui/select/SelectValue.vue'
import Badge from '@/components/ui/badge/Badge.vue'

import { projetApi } from '@/services/api'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  projet: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'created', 'updated'])

const loading = ref(false)
const responsables = ref([])
const errors = ref({})

const isEditing = computed(() => !!props.projet)

const form = reactive({
  nom: '',
  description: '',
  date_debut: new Date().toISOString().split('T')[0],
  date_fin: '',
  responsable_id: '',
  budget: null,
  status: 'planifie',
  priorite: 'normale',
  objectifs: ''
})

const resetForm = () => {
  form.nom = ''
  form.description = ''
  form.date_debut = new Date().toISOString().split('T')[0]
  form.date_fin = ''
  form.responsable_id = ''
  form.budget = null
  form.status = 'planifie'
  form.priorite = 'normale'
  form.objectifs = ''
  errors.value = {}
}

const loadFormData = () => {
  if (props.projet) {
    form.nom = props.projet.nom || ''
    form.description = props.projet.description || ''
    form.date_debut = props.projet.date_debut || ''
    form.date_fin = props.projet.date_fin || ''
    form.responsable_id = props.projet.responsable_id?.toString() || ''
    form.budget = props.projet.budget
    form.status = props.projet.status || 'planifie'
    form.priorite = props.projet.priorite || 'normale'
    form.objectifs = props.projet.objectifs || ''
  } else {
    resetForm()
  }
}

const fetchResponsables = async () => {
  try {
    const response = await projetApi.getResponsables()
    responsables.value = response.data
  } catch (error) {
    console.error('Erreur lors du chargement des responsables:', error)
  }
}

const validateForm = () => {
  const newErrors = {}

  if (!form.nom.trim()) {
    newErrors.nom = ['Le nom du projet est requis']
  }

  if (!form.date_debut) {
    newErrors.date_debut = ['La date de début est requise']
  }

  if (!form.date_fin) {
    newErrors.date_fin = ['La date de fin est requise']
  }

  if (form.date_debut && form.date_fin && new Date(form.date_debut) >= new Date(form.date_fin)) {
    newErrors.date_fin = ['La date de fin doit être postérieure à la date de début']
  }

  if (!form.responsable_id) {
    newErrors.responsable_id = ['Le responsable est requis']
  }

  if (!form.status) {
    newErrors.status = ['Le statut est requis']
  }

  if (!form.priorite) {
    newErrors.priorite = ['La priorité est requise']
  }

  if (form.budget !== null && form.budget < 0) {
    newErrors.budget = ['Le budget doit être positif']
  }

  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  loading.value = true
  try {
    const formData = {
      ...form,
      responsable_id: parseInt(form.responsable_id),
      budget: form.budget || null
    }

    if (isEditing.value) {
      await projetApi.update(props.projet.id, formData)
      emit('updated')
    } else {
      await projetApi.create(formData)
      emit('created')
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Erreur lors de la soumission:', error)
    }
  } finally {
    loading.value = false
  }
}

// Watch for prop changes
watch(() => props.open, (newVal) => {
  if (newVal) {
    loadFormData()
  }
})

watch(() => props.projet, () => {
  if (props.open) {
    loadFormData()
  }
})

onMounted(() => {
  fetchResponsables()
  if (props.open) {
    loadFormData()
  }
})
</script>