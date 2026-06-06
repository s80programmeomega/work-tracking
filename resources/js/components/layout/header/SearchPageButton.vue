<!-- Raccourci topbar vers la page de recherche globale (/search). -->
<!-- Visible uniquement pour les rôles disposant d'un accès recherche (canSearch). -->
<template>
  <button
    v-if="canSearch"
    dusk="search-page-button"
    :title="$t('navigation.search')"
    :aria-label="$t('navigation.search')"
    class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
    @click="goToSearch"
  >
    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20">
      <path
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z"
      />
    </svg>
  </button>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useWorkspace } from '@/composables/useWorkspace'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'

const router = useRouter()
const { currentWorkspace } = useWorkspace()
const { canSearch } = useWorkspacePermissions(currentWorkspace)

const goToSearch = () => {
  router.push('/search')
}
</script>
