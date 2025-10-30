<!-- resources/js/components/workspaces/MemberDetailsModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Détails du membre
          </h2>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <!-- Member Profile -->
          <div class="flex items-start gap-4 mb-6">
            <div
              v-if="member.avatar"
              class="w-20 h-20 rounded-full overflow-hidden flex-shrink-0"
            >
              <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
            </div>
            <div
              v-else
              class="w-20 h-20 rounded-full bg-brand-600 flex items-center justify-center text-white text-2xl font-medium flex-shrink-0"
            >
              {{ getInitials(member.nom) }}
            </div>
            <div class="flex-1">
              <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ member.nom }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ member.email }}
              </p>
              <div class="flex items-center gap-2 mt-3">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getRoleColor(member.pivot?.role)
                  ]"
                >
                  {{ getRoleLabel(member.pivot?.role) }}
                </span>
                <span
                  :class="[
                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                    member.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                  ]"
                >
                  {{ member.is_active ? 'Actif' : 'Inactif' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Statistics -->
          <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ member.projets_count || 0 }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Projets
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ member.taches_count || 0 }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Tâches
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ member.taches_completees || 0 }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Complétées
              </div>
            </div>
          </div>

          <!-- Permissions -->
          <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
              Permissions du workspace
            </h4>
            <div class="space-y-2">
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  Créer des projets
                </span>
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    member.pivot?.can_create_projects
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-300'
                  ]"
                >
                  {{ member.pivot?.can_create_projects ? 'Autorisé' : 'Non autorisé' }}
                </span>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  Inviter des membres
                </span>
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    member.pivot?.can_invite_members
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-300'
                  ]"
                >
                  {{ member.pivot?.can_invite_members ? 'Autorisé' : 'Non autorisé' }}
                </span>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  Gérer les paramètres
                </span>
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    member.pivot?.can_manage_settings
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-300'
                  ]"
                >
                  {{ member.pivot?.can_manage_settings ? 'Autorisé' : 'Non autorisé' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Membership Info -->
          <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
              Informations d'adhésion
            </h4>
            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-gray-600 dark:text-gray-400">Date d'invitation</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ formatDate(member.pivot?.invited_at) }}
                </span>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-gray-600 dark:text-gray-400">Dernière activité</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ formatDate(member.last_activity_at) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Projects List -->
          <div v-if="memberProjects.length > 0">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
              Projets ({{ memberProjects.length }})
            </h4>
            <div class="space-y-2">
              <div
                v-for="projet in memberProjects"
                :key="projet.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="w-2 h-2 rounded-full"
                    :style="{ backgroundColor: projet.couleur || '#3B82F6' }"
                  ></div>
                  <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ projet.nom }}
                  </span>
                </div>
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    getRoleColor(projet.pivot?.role)
                  ]"
                >
                  {{ getRoleLabel(projet.pivot?.role) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { XIcon } from '@/icons'

const props = defineProps({
  member: {
    type: Object,
    required: true
  },
  workspaceId: {
    type: Number,
    required: true
  }
})

defineEmits(['close'])

const memberProjects = ref([])

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getRoleColor = (role) => {
  const colors = {
    owner: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    member: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return colors[role] || colors.viewer
}

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    member: 'Membre',
    viewer: 'Observateur'
  }
  return labels[role] || role
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  // TODO: Load member's projects
  memberProjects.value = props.member.projets || []
})
</script>