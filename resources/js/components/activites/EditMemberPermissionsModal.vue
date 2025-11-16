<!-- resources/js/components/activites/EditMemberPermissionsModal.vue -->
<template>
  <TransitionRoot :show="true" as="template">
    <Dialog as="div" class="relative z-[100]" @close="$emit('close')">
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
            <DialogPanel 
              class="w-full max-w-md transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-xl transition-all"
              @click.stop
            >
              <!-- Header -->
              <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                  Modifier les permissions
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
              <div class="p-6 space-y-6" @click.stop>
                <!-- Membre info -->
                <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                    {{ getInitials(member.nom) }}
                  </div>
                  <div>
                    <div class="font-medium text-gray-900 dark:text-white">
                      {{ member.nom }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ member.email }}
                    </div>
                  </div>
                </div>

                <!-- Rôle -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Rôle
                  </label>
                  <select
                    v-model="form.role"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    @click.stop
                  >
                    <option value="collaborator">Collaborateur</option>
                    <option value="viewer">Observateur</option>
                  </select>
                </div>

                <!-- Permissions -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Permissions
                  </label>
                  <div class="space-y-3">
                    <label class="flex items-start space-x-3 cursor-pointer" @click.stop>
                      <input
                        v-model="form.can_create_tasks"
                        type="checkbox"
                        class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                        @click.stop
                      />
                      <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Créer des tâches</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Peut créer de nouvelles tâches
                        </p>
                      </div>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer" @click.stop>
                      <input
                        v-model="form.can_edit_tasks"
                        type="checkbox"
                        class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                        @click.stop
                      />
                      <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Modifier les tâches</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Peut modifier les tâches existantes
                        </p>
                      </div>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer" @click.stop>
                      <input
                        v-model="form.can_delete_tasks"
                        type="checkbox"
                        class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                        @click.stop
                      />
                      <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Supprimer les tâches</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Peut supprimer des tâches
                        </p>
                      </div>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer" @click.stop>
                      <input
                        v-model="form.can_validate_results"
                        type="checkbox"
                        class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                        @click.stop
                      />
                      <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Valider les résultats (N1)</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Peut valider les résultats des tâches
                        </p>
                      </div>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer" @click.stop>
                      <input
                        v-model="form.can_assign_users"
                        type="checkbox"
                        class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                        @click.stop
                      />
                      <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Gérer les membres</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          Peut ajouter/retirer des membres
                        </p>
                      </div>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex items-center justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                <button
                  @click="$emit('close')"
                  class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  Annuler
                </button>
                <button
                  @click="handleSubmit"
                  :disabled="loading"
                  class="px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="loading">Enregistrement...</span>
                  <span v-else>Enregistrer</span>
                </button>
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
  member: {
    type: Object,
    required: true
  },
  activiteId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const loading = ref(false)

const form = ref({
  role: 'collaborator',
  can_create_tasks: false,
  can_edit_tasks: false,
  can_delete_tasks: false,
  can_validate_results: false,
  can_assign_users: false
})

const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const handleSubmit = async () => {
  loading.value = true

  try {
    await api.put(
      `/activites/${props.activiteId}/members/${props.member.id}`,
      form.value
    )

    emit('updated')
    emit('close')
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error)
    alert(error.response?.data?.message || 'Erreur lors de la mise à jour')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Initialiser le formulaire avec les permissions actuelles
  if (props.member.permissions) {
    form.value = {
      role: props.member.pivot?.role || 'collaborator',
      can_create_tasks: props.member.permissions.can_create_tasks || false,
      can_edit_tasks: props.member.permissions.can_edit_tasks || false,
      can_delete_tasks: props.member.permissions.can_delete_tasks || false,
      can_validate_results: props.member.permissions.can_validate_results || false,
      can_assign_users: props.member.permissions.can_assign_users || false
    }
  }
})
</script>