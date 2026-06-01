<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-black/30"></div>

      <div ref="dialogRef" :style="dragStyle" class="relative bg-white dark:bg-gray-800 rounded-3 max-w-3xl w-full p-6">
        <!-- Header -->
        <div ref="handleRef" class="flex items-center justify-between mb-6 cursor-move select-none">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">
            Détails de l'utilisateur
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- User Profile Header -->
        <div class="flex items-center gap-6 pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex-shrink-0">
            <img
              v-if="user.avatar"
              :src="user.avatar"
              :alt="user.nom"
              class="w-24 h-24 rounded-full object-cover border-4 border-gray-200 dark:border-gray-700"
            />
            <div
              v-else
              class="w-24 h-24 rounded-full flex items-center justify-center text-white text-3xl font-bold border-4 border-gray-200 dark:border-gray-700"
            >
              {{ user.initials }}
            </div>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
              {{ user.nom }}
            </h2>
            <div class="flex flex-wrap items-center gap-3">
              <span :class="getRoleBadgeClass(user.role)" class="px-3 py-1 text-sm font-medium rounded-full">
                {{ getRoleLabel(user.role) }}
              </span>
              <span
                :class="user.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                class="px-3 py-1 text-sm font-medium rounded-full"
              >
                {{ user.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>
          </div>
        </div>

        <!-- User Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Email -->
          <div class="space-y-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              Email
            </label>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
              </svg>
              <p class="text-sm text-gray-900 dark:text-white">{{ user.email }}</p>
            </div>
          </div>

          <!-- Fonction -->
          <div class="space-y-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              Fonction
            </label>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
              </svg>
              <p class="text-sm text-gray-900 dark:text-white">{{ user.fonction || 'Non renseigné' }}</p>
            </div>
          </div>

          <!-- Téléphone -->
          <div class="space-y-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              Téléphone
            </label>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
              </svg>
              <p class="text-sm text-gray-900 dark:text-white">{{ user.numero_telephone || 'Non renseigné' }}</p>
            </div>
          </div>

          <!-- Équipe -->
          <div class="space-y-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              Équipe
            </label>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
              </svg>
              <p class="text-sm text-gray-900 dark:text-white">{{ user.team?.nom || 'Aucune équipe' }}</p>
            </div>
          </div>

          <!-- Langue -->
          <div class="space-y-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              Langue
            </label>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733 18.992 18.992 0 01-1.487-2.494 1 1 0 111.79-.89c.234.47.489.928.764 1.372.417-.934.752-1.913.997-2.927H3a1 1 0 110-2h3V3a1 1 0 011-1zm6 6a1 1 0 01.894.553l2.991 5.982a.869.869 0 01.02.037l.99 1.98a1 1 0 11-1.79.895L15.383 16h-4.764l-.724 1.447a1 1 0 11-1.788-.894l.99-1.98.019-.038 2.99-5.982A1 1 0 0113 8zm-1.382 6h2.764L13 11.236 11.618 14z" clip-rule="evenodd" />
              </svg>
              <p class="text-sm text-gray-900 dark:text-white">{{ user.language === 'fr' ? 'Français' : 'English' }}</p>
            </div>
          </div>

          <!-- Fuseau horaire -->
          <div class="space-y-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              Fuseau horaire
            </label>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
              </svg>
              <p class="text-sm text-gray-900 dark:text-white">{{ user.timezone || 'UTC' }}</p>
            </div>
          </div>

          <!-- Dernière connexion -->
          <div class="space-y-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              Dernière connexion
            </label>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
              </svg>
              <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(user.last_login_at) }}</p>
            </div>
          </div>

          <!-- Date de création -->
          <div class="space-y-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
              Membre depuis
            </label>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
              </svg>
              <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(user.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Bio Section (Full Width) -->
        <div v-if="user.bio" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
          <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2 block">
            Bio
          </label>
          <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
            {{ user.bio }}
          </p>
        </div>

        <!-- Adresse Section -->
        <div v-if="user.adresse" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
          <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2 block">
            Adresse
          </label>
          <div class="flex items-start gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              {{ user.adresse }}
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-end">
          <button
            @click="$emit('close')"
            class="px-6 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-3 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useDraggable } from '@/composables/useDraggable'

const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()
onMounted(attachHandle)

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
})

defineEmits(['close'])

const getRoleLabel = (role) => {
  const labels = {
    super_admin: 'Super Admin',
    manager: 'Manager',
    responsable_n1: 'Responsable N1',
    responsable_n2: 'Responsable N2',
    cadre: 'Cadre',
    stagiaire: 'Stagiaire',
  }
  return labels[role] || role
}

const getRoleBadgeClass = (role) => {
  const classes = {
    super_admin: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    manager: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    responsable_n1: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    responsable_n2: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
    cadre: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    stagiaire: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  }
  return classes[role] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100'
}

const formatDate = (date) => {
  if (!date) return 'Non disponible'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
