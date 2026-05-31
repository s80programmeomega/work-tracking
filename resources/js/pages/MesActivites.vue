<!-- resources/js/pages/MesActivites.vue - AVEC GESTION DES MEMBRES -->
<template>
  <AdminLayout>
    <!-- Workspace Selector -->
    <div class="mb-6">
      <WorkspaceSelector @workspace-changed="onWorkspaceChanged" />
    </div>

    <PageBreadcrumb :pageTitle="'Mes Activités'" />

    <div class="rounded-3 border border-gray-200 bg-white dark:bg-gray-800 p-7.5 shadow-default">
      <!-- Stats panel -->
      <transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
      >
        <MesActivitesStats
          v-if="showStats"
          :stats="stats"
          :loading="loading"
          class="mb-6"
          @close="showStats = false"
        />
      </transition>

      <!-- Filters -->
      <div class="mb-6 bg-gray-50 dark:bg-gray-900 rounded-3 p-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="flex-1">
            <input v-model="filters.search" type="text" placeholder="Rechercher une activité..."
              class="w-full max-w-md rounded-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500"
              @input="debouncedSearch" />
          </div>

          <div class="flex gap-2">
            <select v-model="filters.projet_id"
              class="rounded-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white"
              @change="loadActivites">
              <option value="">Tous les projets</option>
              <option v-for="projet in accessibleProjets" :key="projet.id" :value="projet.id">
                {{ projet.nom }}
              </option>
            </select>

            <select v-model="filters.status"
              class="rounded-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white"
              @change="loadActivites">
              <option value="">Tous les statuts</option>
              <option value="active">Actives</option>
              <option value="archived">Archivées</option>
            </select>

            <button @click="resetFilters"
              class="rounded-3 border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
              Réinitialiser
            </button>

            <button
              @click="showStats = !showStats"
              class="inline-flex items-center gap-2 rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Statistiques
            </button>

             <button
                v-if="canCreateActivity"
                @click="showCreateForm = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700 transition-colors" >
                + Nouvelle activité
              </button>

          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
        <span class="ml-2 text-gray-500 dark:text-gray-400">Chargement...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="activites.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400 mb-2">Aucune activité trouvée</p>
      </div>

      <!-- Activities Table -->
      <div v-else class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
              <tr>
                <th class="px-6 py-3">Activité</th>
                <th class="px-6 py-3">Projet</th>
                <th class="px-6 py-3">Responsable</th>
                <th class="px-6 py-3">Équipe</th>
                <th class="px-6 py-3">Statut</th>
                <th class="px-6 py-3">Progression</th>
                <th class="px-6 py-3">Date fin</th>
                <th class="px-6 py-3">Actions</th>
              </tr>
            </thead>
            <tbody ref="staggerRef">
              <tr v-for="activite in activites" :key="activite.id"
                class="stagger-item border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                <!-- Activité -->
                <td class="px-6 py-4">
                  <div class="cursor-pointer" @click="viewActivityDetail(activite)">
                    <div class="font-medium text-gray-900 dark:text-white hover:text-brand-600">
                      {{ activite.nom }}
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-xs">{{ activite.code }}</div>
                  </div>
                </td>

                <!-- Projet -->
                <td class="px-6 py-4">
                  <span class="text-gray-700 dark:text-gray-300">
                    {{ activite.projet?.nom || '-' }}
                  </span>
                </td>

                <!-- Responsable -->
                <td class="px-6 py-4">
                  <div v-if="activite.responsable" class="flex items-center gap-2">
                    <div
                      class="w-6 h-6 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-medium">
                      {{ getInitials(activite.responsable.nom) }}
                    </div>
                    <span class="text-gray-700 dark:text-gray-300">{{ activite.responsable.nom }}</span>
                  </div>
                </td>

                <!-- Équipe -->
                <td class="px-6 py-4">
                  <button v-if="getPermissions(activite).canManageMembers" @click="openMembersModal(activite)"
                    class="flex items-center gap-2 px-3 py-1.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-3 hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{ activite.membres_count || 0 }} membre(s)
                  </button>
                  <div v-else class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{ activite.membres_count || 0 }} membre(s)
                  </div>
                </td>

                <!-- Statut -->
                <td class="px-6 py-4">
                  <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusClass(activite.status)]">
                    {{ getStatusLabel(activite.status) }}
                  </span>
                </td>

                <!-- Progression -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2 min-w-32">
                    <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div class="h-full bg-brand-500 transition-all duration-500" :style="{ width: `${activite.progression || 0}%` }"></div>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 min-w-8">
                      {{ activite.progression || 0 }}%
                    </span>
                  </div>
                </td>

                <!-- Date fin -->
                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                  {{ activite.date_fin ? formatDate(activite.date_fin) : '-' }}
                </td>

                <!-- Actions -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <button @click="viewActivityDetail(activite)"
                      class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-3" title="Voir">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>

                    <button v-if="getPermissions(activite).canEdit" @click="editActivite(activite)"
                      class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-3"
                      title="Modifier">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <!-- Bouton Supprimer - Seulement si permission -->
                    <button v-if="getPermissions(activite).canDelete" @click="deleteActivite(activite)"
                      class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3" title="Supprimer">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                    <!-- Indicateur si aucune action disponible -->
                    <span v-if="!getPermissions(activite).canEdit && !getPermissions(activite).canDelete"
                      class="text-xs text-gray-400 px-2">
                      Lecture seule
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1"
          class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Affichage de {{ (pagination.current_page - 1) * pagination.per_page + 1 }}
            à {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
            sur {{ pagination.total }} activités
          </div>
          <div class="flex gap-1">
            <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
              class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
              Précédent
            </button>
            <button @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ✅ MODALS DE GESTION -->
   <ActiviteForm
      v-if="showCreateForm"
      :projet-id="filters.projet_id ? Number(filters.projet_id) : null"
      @close="showCreateForm = false"
      @saved="onActiviteCreated"
    />


    <ActiviteForm v-if="showEditForm" :activite="editingActivite" @close="showEditForm = false; editingActivite = null"
      @saved="onActiviteUpdated" />

    <ManageMembersModal v-if="showMembersModal" :activite="selectedActiviteForMembers" @close="showMembersModal = false"
      @updated="onMembersUpdated" @edit-member="onEditMember" @add-member="onAddMember" />

    <!-- Nouveaux modaux ajoutés ici -->
    <EditMemberPermissionsModal v-if="showEditMemberModal" :member="editingMember"
      :activite-id="selectedActiviteForMembers?.id" @close="showEditMemberModal = false"
      @updated="onMemberPermissionsUpdated" />

    <AddMemberModal v-if="showAddMemberModal" :show="showAddMemberModal" :activite-id="selectedActiviteForMembers?.id"
      :projet-id="selectedActiviteForMembers?.projet_id" @close="showAddMemberModal = false"
      @member-added="onMemberAdded" />

  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { useStagger } from '@/composables/useAnimations'
import { useRouter } from 'vue-router'
import { useActivites } from '@/composables/useActivites'
import { useProjets } from '@/composables/useProjets'
import { useWorkspace } from '@/composables/useWorkspace'

import { useAuthStore } from '@/stores/authStore'
const authStore = useAuthStore()


// Components
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import WorkspaceSelector from '@/components/activites/WorkspaceSelector.vue'
import ActiviteForm from '@/components/activites/ActiviteForm.vue'
import ManageMembersModal from '@/components/activites/ManageMembersModal.vue'
import EditMemberPermissionsModal from '@/components/activites/EditMemberPermissionsModal.vue'
import AddMemberModal from '@/components/activites/AddMemberModal.vue'
import MesActivitesStats from '@/components/activites/MesActivitesStats.vue'

const router = useRouter()

// ✅ INITIALISATION CORRECTE DU WORKSPACE
const {
  currentWorkspace,
  currentWorkspaceId,
  currentWorkspaceName,
  workspaces,
  loading: workspaceLoading,
  onWorkspaceChanged,
  initializeCurrentWorkspace
} = useWorkspace()

const {
  activites,
  loading: activitesLoading,
  pagination,
  fetchMesActivites,
  deleteActivite: deleteAct,
  getStatusLabel,
  getStatusClass
} = useActivites()

const {
  projets: accessibleProjets,
  fetchProjetsByWorkspace
} = useProjets()

const { staggerRef, applyStagger } = useStagger(40)

const filters = ref({
  search: '',
  status: '',
  projet_id: '',
  page: 1
})

const showStats = ref(false)
const showCreateForm = ref(false)
const showEditForm = ref(false)
const showMembersModal = ref(false)
const showEditMemberModal = ref(false)
const showAddMemberModal = ref(false)
const editingActivite = ref(null)
const selectedActiviteForMembers = ref(null)
const editingMember = ref(null)

// ✅ NOUVEAU : Cache des permissions pour éviter les recalculs
const permissionsCache = ref(new Map())
// ✅ GESTION DU CHARGEMENT COMBINÉ
const loading = computed(() => activitesLoading.value || workspaceLoading.value)

// ✅ Stats calculées
const stats = computed(() => {
  const total = activites.value.length
  const active = activites.value.filter(a => a.status === 'active').length
  const overdue = activites.value.filter(a => a.is_overdue).length
  const avgProgress = total > 0 ? Math.round(activites.value.reduce((sum, a) => sum + (a.progression || 0), 0) / total) : 0

  return { total, active, overdue, avgProgress }
})
 
// ✅ FONCTION POUR OBTENIR LES PERMISSIONS
const getPermissions = (activite) => {
  if (!activite) {
    return {
      canEdit: false,
      canDelete: false,
      canManageMembers: false,
      canView: false
    }
  }
  // Utiliser le cache si disponible
  const cacheKey = `${activite.id}_${activite.updated_at}`
  if (permissionsCache.value.has(cacheKey)) {
    return permissionsCache.value.get(cacheKey)
  }

  // console.log('Calcul des permissions pour l\'activité ID:', activite);
  // Calculer les permissions
  const permissions = {
    // Utiliser les permissions de l'API depuis user_permissions
    canEdit: activite.user_permissions?.can_edit_activity || false,
    canDelete: activite.user_permissions?.can_delete_activity || false,
    canManageMembers: activite.user_permissions?.can_manage_members || false,
    canView: true // Par défaut, si l'activité est dans la liste, l'utilisateur peut la voir

  }


  // Mettre en cache
  permissionsCache.value.set(cacheKey, permissions)
  return permissions
} 



// ✅ Chargement des activités
const loadActivites = async () => {
  if (!currentWorkspaceId.value) {
    console.log('Aucun workspace sélectionné')
    return
  }

  try {
    await fetchMesActivites({
      ...filters.value,
      workspace_id: currentWorkspaceId.value
    })
    permissionsCache.value.clear()
  } catch (error) {
    console.error('Erreur lors du chargement des activités:', error)
  }
}

// ✅ Chargement des projets
const loadProjets = async () => {
  if (!currentWorkspaceId.value) return
  
  try {
    await fetchProjetsByWorkspace(currentWorkspaceId.value, { per_page: 100 })
  } catch (error) {
    console.error('Erreur lors du chargement des projets:', error)
  }
}

// ✅ Chargement complet des données
const loadData = async () => {
  if (!currentWorkspaceId.value) {
    console.log('⚠️ Aucun workspace sélectionné, attente...')
    return
  }

  console.log('📊 Chargement des données pour workspace:', currentWorkspaceId.value)

  try {
    await Promise.all([
      loadActivites(),
      loadProjets()
    ])
    console.log('✅ Données chargées avec succès')
    applyStagger()
  } catch (error) {
    console.error('❌ Erreur lors du chargement des données:', error)
  }
}

// ✅ Gestion du changement de workspace depuis WorkspaceSelector
let unsubscribeWorkspaceListener = null

 
const handleWorkspaceChangeInActivities  = async (event) => {
  console.log('🔄 Changement de workspace détecté:', event.detail)

  // Réinitialiser les filtres
  filters.value = {
    search: '',
    status: '',
    projet_id: '',
    page: 1
  }

  // Recharger les données
  await loadData()
}

// ✅ Autres fonctions
const debouncedSearch = (() => {
  let searchTimeout
  return () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      loadActivites()
    }, 500)
  }
})()

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    projet_id: '',
    page: 1
  }
  loadActivites()
}

const changePage = (page) => {
  filters.value.page = page
  loadActivites()
}

const viewActivityDetail = (activite) => {
  router.push(`/activites/${activite.id}`)
}

const editActivite = (activite) => {
  // Vérifier une dernière fois les permissions avant d'ouvrir le modal
  if (!getPermissions(activite).canEdit) {
    alert('Vous n\'avez pas la permission de modifier cette activité')
    return
  }

  editingActivite.value = activite
  showEditForm.value = true
}

const deleteActivite = async (activite) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${activite.nom}" ?`)) {
    return
  }

  try {
    await deleteAct(activite.id)
    await loadActivites()
  } catch (error) {
    alert('Erreur lors de la suppression')
  }
}

// ✅ GESTION DES MEMBRES
const openMembersModal = (activite) => {
  if (!getPermissions(activite).canManageMembers) {
    alert('Vous n\'avez pas la permission de gérer les membres de cette activité')
    return
  }

  selectedActiviteForMembers.value = activite
  showMembersModal.value = true
}

const onEditMember = (member) => {
  editingMember.value = member
  showEditMemberModal.value = true
}

const onAddMember = () => {
  showAddMemberModal.value = true
}

const onMemberPermissionsUpdated = () => {
  showEditMemberModal.value = false
  editingMember.value = null
  // Recharger les membres si nécessaire
  if (showMembersModal.value) {
    // Ou simplement fermer et rouvrir le modal
    showMembersModal.value = false
    setTimeout(() => {
      showMembersModal.value = true
    }, 100)
  }
}

const canCreateActivity = computed(() => {
  const user = authStore.user
  if (!user) return false

  // super admin: ok
  if (user.is_super_admin) return true

  // workspace owner: ok
  if (currentWorkspace.value?.owner_id && Number(currentWorkspace.value.owner_id) === Number(user.id)) {
    return true
  }

  // sinon: tu peux décider de la règle (ex: au moins un projet sélectionné)
  // car créer une activité demande un projet.
  return Boolean(filters.value.projet_id)
})


const onMemberAdded = () => {
  showAddMemberModal.value = false
  // Recharger les membres dans ManageMembersModal
  if (showMembersModal.value) {
    showMembersModal.value = false
    setTimeout(() => {
      showMembersModal.value = true
    }, 100)
  }
}


const onMembersUpdated = () => {
  loadActivites()
}

const onActiviteCreated = () => {
  showCreateForm.value = false
  loadActivites()
}

const onActiviteUpdated = () => {
  showEditForm.value = false
  editingActivite.value = null
  loadActivites()
}
 

const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

watch(currentWorkspaceId, async (newId, oldId) => {
  if (newId && newId !== oldId) {
    console.log(`Workspace ID changé: ${oldId} -> ${newId}`);
    await loadData();
  }
})

// ✅ WATCH POUR LES CHANGEMENTS DE FILTRES
watch([
  () => filters.value.status,
  () => filters.value.projet_id
  ], () => {
    loadActivites();
});

onMounted(async () => {
  // Initialiser le workspace courant
  await initializeCurrentWorkspace();
  // Charger les données si un workspace est disponible
  if (currentWorkspaceId.value) {
    await loadData();
  }

  // Écouter les changements de workspace
  unsubscribeWorkspaceListener = onWorkspaceChanged(handleWorkspaceChangeInActivities );

  // if (currentWorkspaceId.value) {
  //   await loadActivites()
  //   await loadProjets()
  // }
})

// ✅ NETTOYAGE
onUnmounted(() => {
  if (unsubscribeWorkspaceListener) {
    unsubscribeWorkspaceListener();
  }
});

</script>