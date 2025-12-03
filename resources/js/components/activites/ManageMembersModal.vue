<!-- resources/js/components/activites/ManageMembersModal.vue -->
<template>
  <TransitionRoot :show="true" appear>
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
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
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-xl transition-all">
              <!-- Header -->
              <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                  Gérer les membres - {{ activite.nom }}
                </DialogTitle>
                <button
                  @click="$emit('close')"
                  class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Liste des membres -->
                <div class="space-y-4">
                  <div
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                    :class="member.id === activite.responsable_id ? 'bg-orange-50 dark:bg-orange-900/10 border-orange-200 dark:border-orange-800' : ''"
                  >
                    <div class="flex items-center space-x-3 flex-1">
                      <div 
                        class="w-12 h-12 rounded-full flex items-center justify-center text-white text-sm font-bold"
                        :class="member.id === activite.responsable_id ? 'bg-gradient-to-br from-orange-500 to-yellow-500' : 'bg-gradient-to-br from-blue-500 to-purple-500'"
                      >
                        {{ getInitials(member.nom) }}
                      </div>
                      
                      <div class="flex-1">
                        <div class="flex items-center space-x-2">
                          <span class="font-medium text-gray-900 dark:text-white">
                            {{ member.nom }}
                          </span>
                          <span 
                            v-if="member.id === activite.responsable_id"
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300"
                          >
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            Responsable
                          </span>
                        </div>
                        
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                          {{ member.email }}
                        </p>
                        
                        <!-- Permissions -->
                        <div class="mt-2 flex flex-wrap gap-1">
                          <span 
                            v-if="member.permissions?.can_create_tasks"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300"
                          >
                            Créer tâches
                          </span>
                          <span 
                            v-if="member.permissions?.can_edit_tasks"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
                          >
                            Modifier tâches
                          </span>
                          <span 
                            v-if="member.permissions?.can_validate_results"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300"
                          >
                            Valider N1
                          </span>
                          <span 
                            v-if="member.permissions?.can_assign_users"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300"
                          >
                            Assigner membres
                          </span>
                        </div>
                      </div>
                    </div>

                    <div class="flex items-center space-x-2 ml-4">
                      <button
                        v-if="member.id !== activite.responsable_id"
                        @click="editMember(member)"
                        class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                        title="Modifier les permissions"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      
                      <button
                        v-if="member.id !== activite.responsable_id"
                        @click="removeMember(member)"
                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        title="Retirer"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </div>

                  <div v-if="members.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="font-medium">Aucun membre</p>
                    <p class="text-sm mt-1">Ajoutez des membres pour collaborer</p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ members.length }} membre(s)
                </p>
                <div class="flex items-center space-x-3">
                  <button
                    @click="$emit('close')"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    Fermer
                  </button>
                   <button
                    @click="openAddMemberModal"
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700"
                  >
                    <span class="flex items-center">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      Ajouter un membre
                    </span>
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
import { ref, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import api from '@/api/axios'

const props = defineProps({
  activite: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated', 'edit-member', 'add-member']) // Ajouter 'add-member'

const members = ref([])
const loading = ref(false)

const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const loadMembers = async () => {
  loading.value = true
  try {
    const response = await api.get(`/activites/${props.activite.id}/members`)
    members.value = response.data.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des membres:', error)
  } finally {
    loading.value = false
  }
}

// Émettre des événements pour ouvrir les sous-modaux
const editMember = (member) => {
  console.log('Editing member:', member)
  emit('edit-member', member)
}

const openAddMemberModal = () => {
  emit('add-member')
}

const removeMember = async (member) => {
  if (!confirm(`Êtes-vous sûr de vouloir retirer ${member.nom} ?`)) {
    return
  }

  try {
    await api.delete(`/activites/${props.activite.id}/members/${member.id}`)
    await loadMembers()
    emit('updated')
  } catch (error) {
    console.error('Erreur lors du retrait du membre:', error)
    alert(error.response?.data?.message || 'Erreur lors du retrait du membre')
  }
}

const showAddMember = ref(false)
const onMemberAdded = () => {
  showAddMember.value = false
  loadMembers()
  emit('updated')
}

onMounted(() => {
  loadMembers()
})
</script>