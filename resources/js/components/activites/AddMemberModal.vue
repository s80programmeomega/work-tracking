<!-- resources/js/components/activites/AddMemberModal.vue -->
<template>
  <TransitionRoot :show="show" as="template">
    <Dialog as="div" class="relative z-[70]" @close="handleClose">
      <TransitionChild
        as="div"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0" >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"  />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
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
            <DialogPanel class="w-full max-w-3xl transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-xl transition-all">
              <!-- Header -->
              <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                  Ajouter des membres à l'activité
                </DialogTitle>
                <button @click="handleClose"
                  class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Recherche -->
                <div class="mb-6">
                  <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                      v-model="searchTerm"
                      type="text"
                      placeholder="Rechercher par nom ou email..."
                      class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                      @click.stop
                    />
                  </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="flex justify-center py-12">
                  <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-600"></div>
                </div>

                <!-- Empty State -->
                <div v-else-if="availableMembers.length === 0" class="text-center py-12">
                  <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <p class="text-gray-500 dark:text-gray-400 font-medium">Aucun membre disponible</p>
                  <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Tous les membres du projet sont déjà assignés</p>
                </div>

                <!-- Liste des membres disponibles -->
                <div v-else class="space-y-2">
                  <div
                      v-for="member in filteredMembers"
                      :key="member.id"
                      @click.stop="toggleMemberSelection(member)"
                      class="flex items-center justify-between p-4 border-2 rounded-lg cursor-pointer transition-all hover:shadow-md"
                      :class="isSelected(member.id) 
                        ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20' 
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                    >
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                      <!-- Avatar -->
                      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ getInitials(member.nom) }}
                      </div>

                      <!-- Info -->
                      <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 dark:text-white truncate">
                          {{ member.nom }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                          {{ member.email }}
                        </div>
                      </div>

                      <!-- Checkbox -->
                      <div class="flex-shrink-0">
                        <div 
                          class="w-6 h-6 rounded border-2 flex items-center justify-center transition-colors"
                          :class="isSelected(member.id) 
                            ? 'bg-brand-600 border-brand-600' 
                            : 'border-gray-300 dark:border-gray-600'"
                        >
                          <svg v-if="isSelected(member.id)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Membres sélectionnés -->
                <div v-if="selectedMembers.length > 0" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">
                    Membres sélectionnés ({{ selectedMembers.length }})
                  </h3>

                  <div class="space-y-4">
                    <div
                      v-for="member in selectedMembers"
                      :key="member.id"
                      class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4"
                    >
                      <!-- En-tête membre -->
                      <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                            {{ getInitials(member.nom) }}
                          </div>
                          <span class="font-medium text-gray-900 dark:text-white">{{ member.nom }}</span>
                        </div>
                        <button
                          @click.stop="removeMemberFromSelection(member.id)"
                          class="p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>

                      <!-- Rôle -->
                      <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                          Rôle
                        </label>
                        <select
                          v-model="memberPermissions[member.id].role"
                          @click.stop
                          class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
                        >
                          <option value="collaborator">Collaborateur</option>
                          <option value="viewer">Observateur</option>
                        </select>
                      </div>

                      <!-- Permissions -->
                      <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 cursor-pointer" @click.stop>
                          <input
                            v-model="memberPermissions[member.id].can_create_tasks"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500"
                            @click.stop
                          />
                          <span class="text-xs text-gray-700 dark:text-gray-300">Créer tâches</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer" @click.stop>
                          <input
                            v-model="memberPermissions[member.id].can_edit_tasks"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500"
                            @click.stop
                          />
                          <span class="text-xs text-gray-700 dark:text-gray-300">Modifier tâches</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer" @click.stop>
                          <input
                            v-model="memberPermissions[member.id].can_delete_tasks"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500"
                            @click.stop
                          />
                          <span class="text-xs text-gray-700 dark:text-gray-300">Supprimer tâches</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer" @click.stop>
                          <input
                            v-model="memberPermissions[member.id].can_validate_results"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500"
                            @click.stop
                          />
                          <span class="text-xs text-gray-700 dark:text-gray-300">Valider N1</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer col-span-2" @click.stop>
                          <input
                            v-model="memberPermissions[member.id].can_assign_users"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500"
                            @click.stop
                          />
                          <span class="text-xs text-gray-700 dark:text-gray-300">Assigner membres</span>
                        </label>
                      </div>
                      
                    </div>
                  </div>
                </div>

                <!-- Erreur -->
                <div v-if="error" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                  <p class="text-sm text-red-700 dark:text-red-400">{{ error }}</p>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ selectedMembers.length }} membre(s) sélectionné(s)
                </p>
                <div class="flex items-center gap-3">
                 <button
                    @click="handleClose"
                    type="button"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                  <button
                    @click="handleSubmit"
                    :disabled="submitting || selectedMembers.length === 0"
                    class="px-6 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <span v-if="submitting">Ajout en cours...</span>
                    <span v-else>Ajouter {{ selectedMembers.length }} membre(s)</span>
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
import { ref, computed, watch, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import api from '@/api/axios'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  activiteId: {
    type: Number,
    required: true
  },
  projetId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'members-added'])

const loading = ref(false)
const submitting = ref(false)
const error = ref(null)
const searchTerm = ref('')
const availableMembers = ref([])
const selectedMemberIds = ref([])
const memberPermissions = ref({})

const selectedMembers = computed(() => {
  return availableMembers.value.filter(m => selectedMemberIds.value.includes(m.id))
})

const filteredMembers = computed(() => {
  if (!searchTerm.value) return availableMembers.value

  const term = searchTerm.value.toLowerCase()
  return availableMembers.value.filter(m =>
    m.nom.toLowerCase().includes(term) || 
    m.email.toLowerCase().includes(term)
  )
})

const isSelected = (memberId) => {
  return selectedMemberIds.value.includes(memberId)
}

const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const toggleMemberSelection = (member) => {
  const index = selectedMemberIds.value.indexOf(member.id)
  
  if (index > -1) {
    selectedMemberIds.value.splice(index, 1)
    delete memberPermissions.value[member.id]
  } else {
    selectedMemberIds.value.push(member.id)
    memberPermissions.value[member.id] = {
      role: 'collaborator',
      can_create_tasks: false,
      can_edit_tasks: false,
      can_delete_tasks: false,
      can_validate_results: false,
      can_assign_users: false
    }
  }
}

const removeMemberFromSelection = (memberId) => {
  const index = selectedMemberIds.value.indexOf(memberId)
  if (index > -1) {
    selectedMemberIds.value.splice(index, 1)
    delete memberPermissions.value[memberId]
  }
}

// ✅ Gérer le changement de rôle
const handleRoleChange = (memberId) => {
  if (memberPermissions.value[memberId].role === 'viewer') {
    // Désactiver toutes les permissions pour les viewers
    memberPermissions.value[memberId].can_create_tasks = false
    memberPermissions.value[memberId].can_edit_tasks = false
    memberPermissions.value[memberId].can_delete_tasks = false
    memberPermissions.value[memberId].can_validate_results = false
    memberPermissions.value[memberId].can_assign_users = false
  }
}

const fetchAvailableMembers = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await api.get(`/activites/available-members/${props.projetId}`)
    const allMembers = response.data.data || []

    // Récupérer les membres actuels de l'activité
    const currentResponse = await api.get(`/activites/${props.activiteId}/members`)
    const currentMembers = currentResponse.data.data || []
    const currentMemberIds = currentMembers.map(m => m.id)

    // Filtrer pour exclure les membres déjà assignés
    availableMembers.value = allMembers.filter(m => !currentMemberIds.includes(m.id))

  } catch (err) {
    console.error('Error loading members:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement des membres'
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  if (selectedMembers.value.length === 0) {
    error.value = 'Veuillez sélectionner au moins un membre'
    return
  }

  try {
    submitting.value = true
    error.value = null

    // Ajouter chaque membre sélectionné
    const promises = selectedMembers.value.map(member => {
      return api.post(`/activites/${props.activiteId}/members`, {
        user_id: member.id,
        ...memberPermissions.value[member.id]
      })
    })

    await Promise.all(promises)

    emit('members-added')
    handleClose()

  } catch (err) {
    console.error('Error adding members:', err)
    error.value = err.response?.data?.message || 'Erreur lors de l\'ajout des membres'
  } finally {
    submitting.value = false
  }
}

const handleClose = () => {
  selectedMemberIds.value = []
  memberPermissions.value = {}
  searchTerm.value = ''
  error.value = null
  emit('close')
}

watch(() => props.show, (newValue) => {
  if (newValue) {
    fetchAvailableMembers()
  }
})

onMounted(() => {
  if (props.show) {
    fetchAvailableMembers()
  }
})
</script>