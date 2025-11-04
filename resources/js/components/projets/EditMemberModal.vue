<!-- resources/js/components/projets/EditMemberModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Modifier les permissions
          </h2>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6">
          <!-- Member Info -->
          <div class="flex items-center gap-3 mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div
              v-if="membre.avatar"
              class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0"
            >
              <img :src="membre.avatar" :alt="membre.nom" class="w-full h-full object-cover" />
            </div>
            <div
              v-else
              class="w-12 h-12 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium flex-shrink-0"
            >
              {{ getInitials(membre.nom) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ membre.nom }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ membre.email }}
              </div>
            </div>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Rôle <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.role"
                required
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="admin">Administrateur</option>
                <option value="member">Membre</option>
                <option value="viewer">Observateur</option>
              </select>
            </div>

            <!-- Permissions -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Permissions spécifiques
              </label>
              <div class="space-y-3">
                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_edit"
                    type="checkbox"
                    id="edit_can_edit"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="edit_can_edit" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut modifier le projet
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de modifier les informations du projet, créer des activités et tâches
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_delete"
                    type="checkbox"
                    id="edit_can_delete"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="edit_can_delete" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut supprimer
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de supprimer des activités, tâches et documents
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_invite"
                    type="checkbox"
                    id="edit_can_invite"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="edit_can_invite" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut inviter des membres
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet d'ajouter ou retirer des membres du projet
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error Message -->
            <div
              v-if="error"
              class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
            >
              <p class="text-sm text-red-800 dark:text-red-400">
                {{ error }}
              </p>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            Annuler
          </button>
          <button
            @click="handleSubmit"
            :disabled="submitting"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <span v-if="submitting" class="animate-spin">⏳</span>
            Mettre à jour
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useProjets } from '@/composables/useProjets'
import { XIcon } from '@/icons'

const props = defineProps({
  membre: {
    type: Object,
    required: true
  },
  projetId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const { updateMember } = useProjets()

const submitting = ref(false)
const error = ref(null)

const form = ref({
  role: '',
  can_edit: false,
  can_delete: false,
  can_invite: false
})

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

// Auto-set permissions based on role
watch(() => form.value.role, (newRole) => {
  if (newRole === 'admin') {
    form.value.can_edit = true
    form.value.can_delete = true
    form.value.can_invite = true
  } else if (newRole === 'member') {
    form.value.can_edit = true
    form.value.can_delete = false
    form.value.can_invite = false
  } else if (newRole === 'viewer') {
    form.value.can_edit = false
    form.value.can_delete = false
    form.value.can_invite = false
  }
})

const handleSubmit = async () => {
  try {
    submitting.value = true
    error.value = null

    await updateMember(props.projetId, props.membre.id, form.value)
    emit('updated')
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue'
    console.error('Error updating member:', err)
  } finally {
    submitting.value = false
  }
}

// Initialize form with current member data
onMounted(() => {
  form.value = {
    role: props.membre.pivot?.role || 'member',
    can_edit: props.membre.pivot?.can_edit || false,
    can_delete: props.membre.pivot?.can_delete || false,
    can_invite: props.membre.pivot?.can_invite || false
  }
})
</script>