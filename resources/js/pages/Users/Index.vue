<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Gestion des Utilisateurs'" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Utilisateurs
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Gérer les utilisateurs et leurs rôles
          </p>
        </div>

        <button
          v-if="canCreate"
          @click="openCreateModal"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          Ajouter un utilisateur
        </button>
      </div>

      <!-- Filters -->
      <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <input
          v-model="filters.search"
          @input="applyFilters"
          type="text"
          placeholder="Rechercher..."
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
        />

        <select
          v-model="filters.role"
          @change="applyFilters"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option :value="null">Tous les rôles</option>
          <option value="super_admin">Super Admin</option>
          <option value="manager">Manager</option>
          <option value="responsable_n1">Responsable N1</option>
          <option value="responsable_n2">Responsable N2</option>
          <option value="cadre">Cadre</option>
          <option value="stagiaire">Stagiaire</option>
        </select>

        <select
          v-model="filters.is_active"
          @change="applyFilters"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option :value="null">Tous les statuts</option>
          <option :value="true">Actif</option>
          <option :value="false">Inactif</option>
        </select>

        <button
          @click="resetFilters"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
        >
          Réinitialiser
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
          <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
            {{ totalUsers }}
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
          <div class="text-sm text-gray-500 dark:text-gray-400">Actifs</div>
          <div class="text-2xl font-bold text-green-600 mt-1">
            {{ activeUsers.length }}
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
          <div class="text-sm text-gray-500 dark:text-gray-400">Managers</div>
          <div class="text-2xl font-bold text-blue-600 mt-1">
            {{ usersByRole('manager').length }}
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
          <div class="text-sm text-gray-500 dark:text-gray-400">Inactifs</div>
          <div class="text-2xl font-bold text-red-600 mt-1">
            {{ totalUsers - activeUsers.length }}
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-500">Chargement...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-8 text-center text-red-600">
        {{ error }}
      </div>

      <!-- Users Table -->
      <div v-else-if="users.length > 0" class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Utilisateur
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Rôle
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Équipe
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Statut
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Dernière connexion
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
              <!-- User Info -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <img
                      v-if="user.avatar"
                      :src="user.avatar"
                      :alt="user.nom"
                      class="h-10 w-10 rounded-full object-cover"
                    />
                    <div
                      v-else
                      class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold"
                    >
                      {{ user.initials }}
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ user.nom }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ user.email }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Role -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getRoleBadgeClass(user.role)" class="px-2 py-1 text-xs font-medium rounded-full">
                  {{ getRoleLabel(user.role) }}
                </span>
              </td>

              <!-- Team -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="user.team" class="text-sm text-gray-900 dark:text-white">
                  {{ user.team.nom }}
                </span>
                <span v-else class="text-sm text-gray-400 dark:text-gray-500 italic">
                  Aucune équipe
                </span>
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ user.is_active ? 'Actif' : 'Inactif' }}
                </span>
              </td>

              <!-- Last Login -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(user.last_login_at) }}
              </td>

              <!-- Actions -->
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <!-- View Button -->
                  <button
                    @click="viewUser(user)"
                    class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                    title="Voir"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                      <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>
                  </button>

                  <!-- Edit Button -->
                  <button
                    @click="openEditModal(user)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                    title="Modifier"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                  </button>

                  <!-- Toggle Active Status -->
                  <button
                    @click="toggleUserStatus(user)"
                    :class="user.is_active ? 'text-green-600 hover:text-green-900 dark:text-green-400' : 'text-orange-600 hover:text-orange-900 dark:text-orange-400'"
                    :title="user.is_active ? 'Désactiver' : 'Activer'"
                  >
                    <svg v-if="user.is_active" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                    </svg>
                  </button>

                  <!-- Delete Button -->
                  <button
                    v-if="canDelete"
                    @click="confirmDelete(user)"
                    class="text-red-600 hover:text-red-900 dark:text-red-400"
                    title="Supprimer"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Affichage {{ (pagination.currentPage - 1) * pagination.perPage + 1 }} à
            {{ Math.min(pagination.currentPage * pagination.perPage, pagination.total) }}
            sur {{ pagination.total }} résultats
          </div>
          <div class="flex gap-2">
            <button
              @click="handlePageChange(pagination.currentPage - 1)"
              :disabled="pagination.currentPage === 1"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Précédent
            </button>
            <button
              @click="handlePageChange(pagination.currentPage + 1)"
              :disabled="pagination.currentPage === pagination.lastPage"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
        Aucun utilisateur trouvé
      </div>
    </div>

    <!-- User Modal (Create/Edit) -->
    <UserModal
      v-if="showModal && modalMode !== 'view'"
      :user="selectedUser"
      :mode="modalMode"
      @close="closeModal"
      @save="handleSave"
    />

    <!-- User View Modal (View Only) -->
    <UserViewModal
      v-if="showModal && modalMode === 'view'"
      :user="selectedUser"
      @close="closeModal"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useUsers } from '@/composables/useUsers'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import UserModal from '@/components/users/UserModal.vue'
import UserViewModal from '@/components/users/UserViewModal.vue'

const {
  users,
  loading,
  error,
  pagination,
  filters,
  activeUsers,
  totalUsers,
  fetchUsers,
  deleteUser,
  toggleActiveStatus,
  setFilters,
  resetFilters: resetStoreFilters,
  usersByRole,
} = useUsers()

const showModal = ref(false)
const selectedUser = ref(null)
const modalMode = ref('create')

const canCreate = computed(() => true) // TODO: implement permission check
const canDelete = computed(() => true) // TODO: implement permission check

onMounted(() => {
  fetchUsers()
})

const applyFilters = () => {
  setFilters(filters.value)
  fetchUsers(1)
}

const resetFilters = () => {
  resetStoreFilters()
  fetchUsers(1)
}

const handlePageChange = (page) => {
  fetchUsers(page)
}

const openCreateModal = () => {
  selectedUser.value = null
  modalMode.value = 'create'
  showModal.value = true
}

const openEditModal = (user) => {
  selectedUser.value = user
  modalMode.value = 'edit'
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedUser.value = null
}

const handleSave = async () => {
  closeModal()
  await fetchUsers(pagination.value.currentPage)
}

const viewUser = (user) => {
  selectedUser.value = user
  modalMode.value = 'view'
  showModal.value = true
}

const toggleUserStatus = async (user) => {
  const action = user.is_active ? 'désactiver' : 'activer'
  if (confirm(`Êtes-vous sûr de vouloir ${action} ${user.nom}?`)) {
    try {
      await toggleActiveStatus(user.id)
    } catch (error) {
      console.error('Error toggling user status:', error)
      alert('Erreur lors du changement de statut')
    }
  }
}

const confirmDelete = async (user) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer ${user.nom}?`)) {
    try {
      await deleteUser(user.id)
    } catch (error) {
      console.error('Error deleting user:', error)
      alert('Erreur lors de la suppression')
    }
  }
}

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
    super_admin: 'bg-purple-100 text-purple-800',
    manager: 'bg-blue-100 text-blue-800',
    responsable_n1: 'bg-indigo-100 text-indigo-800',
    responsable_n2: 'bg-cyan-100 text-cyan-800',
    cadre: 'bg-gray-100 text-gray-800',
    stagiaire: 'bg-yellow-100 text-yellow-800',
  }
  return classes[role] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  if (!date) return 'Jamais'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>
