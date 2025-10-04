<template>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Gestion des Utilisateurs</h1>
          </div>
          <div class="col-sm-6">
            <button
              v-if="canCreateUser"
              @click="showCreateModal = true"
              class="btn btn-primary float-right">
              <i class="fas fa-plus"></i> Nouvel Utilisateur
            </button>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <!-- Filter and Search -->
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Filtrer par rôle:</label>
                  <select v-model="filters.role" class="form-control">
                    <option value="">Tous les rôles</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="manager">Manager</option>
                    <option value="responsable_n1">Responsable N1</option>
                    <option value="responsable_n2">Responsable N2</option>
                    <option value="cadre">Cadre</option>
                    <option value="stagiaire">Stagiaire</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Filtrer par équipe:</label>
                  <select v-model="filters.team" class="form-control">
                    <option value="">Toutes les équipes</option>
                    <option value="null">Sans équipe</option>
                    <option
                      v-for="team in teams"
                      :key="team.id"
                      :value="team.id">
                      {{ team.nom }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Recherche:</label>
                  <input
                    v-model="filters.search"
                    type="text"
                    class="form-control"
                    placeholder="Nom, email...">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Table -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Liste des Utilisateurs ({{ filteredUsers.length }})</h3>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center">
              <div class="spinner-border" role="status">
                <span class="sr-only">Chargement...</span>
              </div>
            </div>

            <div v-else class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>Avatar</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Fonction</th>
                    <th>Équipe</th>
                    <th>Téléphone</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in paginatedUsers" :key="user.id">
                    <td>
                      <img
                        :src="user.avatar ? `/storage/${user.avatar}` : '/images/default-avatar.png'"
                        alt="Avatar"
                        class="img-circle"
                        style="width: 40px; height: 40px;">
                    </td>
                    <td>{{ user.nom }}</td>
                    <td>{{ user.email }}</td>
                    <td>
                      <span class="badge" :class="getRoleBadgeClass(user.role)">
                        {{ user.role }}
                      </span>
                    </td>
                    <td>{{ user.fonction || '-' }}</td>
                    <td>
                      <span v-if="user.team">{{ user.team.nom }}</span>
                      <span v-else class="text-muted">Aucune</span>
                    </td>
                    <td>{{ user.numero_telephone || '-' }}</td>
                    <td>{{ formatDate(user.created_at) }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <button
                          v-if="canEditUser"
                          @click="editUser(user)"
                          class="btn btn-sm btn-outline-warning"
                          title="Modifier">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          v-if="canDeleteUser && user.id !== currentUser.id"
                          @click="confirmDelete(user)"
                          class="btn btn-sm btn-outline-danger"
                          title="Supprimer">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="d-flex justify-content-center mt-3">
              <nav>
                <ul class="pagination">
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <button class="page-link" @click="currentPage = 1" :disabled="currentPage === 1">
                      <i class="fas fa-angle-double-left"></i>
                    </button>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <button class="page-link" @click="currentPage--" :disabled="currentPage === 1">
                      <i class="fas fa-angle-left"></i>
                    </button>
                  </li>
                  <li
                    v-for="page in visiblePages"
                    :key="page"
                    class="page-item"
                    :class="{ active: page === currentPage }">
                    <button class="page-link" @click="currentPage = page">{{ page }}</button>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <button class="page-link" @click="currentPage++" :disabled="currentPage === totalPages">
                      <i class="fas fa-angle-right"></i>
                    </button>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <button class="page-link" @click="currentPage = totalPages" :disabled="currentPage === totalPages">
                      <i class="fas fa-angle-double-right"></i>
                    </button>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Create/Edit User Modal -->
    <UserModal
      :show="showCreateModal || showEditModal"
      :user="selectedUser"
      :is-edit="showEditModal"
      :teams="teams"
      @close="closeModal"
      @saved="handleUserSaved"
    />

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" :class="{ show: showDeleteModal }" :style="{ display: showDeleteModal ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Confirmer la suppression</h4>
            <button type="button" class="close" @click="showDeleteModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>{{ selectedUser?.nom }}</strong> ?</p>
            <p class="text-danger">Cette action est irréversible.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" @click="showDeleteModal = false">Annuler</button>
            <button type="button" class="btn btn-danger" @click="deleteUser">Supprimer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import UserModal from './UserModal.vue'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'UserManagement',
  components: {
    UserModal
  },
  data() {
    return {
      users: [],
      teams: [],
      loading: true,
      showCreateModal: false,
      showEditModal: false,
      showDeleteModal: false,
      selectedUser: null,
      filters: {
        role: '',
        team: '',
        search: ''
      },
      currentPage: 1,
      itemsPerPage: 10
    }
  },
  computed: {
    authStore() {
      return useAuthStore()
    },
    currentUser() {
      return this.authStore.user
    },
    canCreateUser() {
      return this.authStore.hasPermission('user.create')
    },
    canEditUser() {
      return this.authStore.hasPermission('user.edit')
    },
    canDeleteUser() {
      return this.authStore.hasPermission('user.delete')
    },
    filteredUsers() {
      let filtered = [...this.users]

      if (this.filters.role) {
        filtered = filtered.filter(user => user.role === this.filters.role)
      }

      if (this.filters.team !== '') {
        if (this.filters.team === 'null') {
          filtered = filtered.filter(user => !user.team_id)
        } else {
          filtered = filtered.filter(user => user.team_id == this.filters.team)
        }
      }

      if (this.filters.search) {
        const search = this.filters.search.toLowerCase()
        filtered = filtered.filter(user =>
          user.nom.toLowerCase().includes(search) ||
          user.email.toLowerCase().includes(search) ||
          (user.fonction && user.fonction.toLowerCase().includes(search))
        )
      }

      return filtered
    },
    totalPages() {
      return Math.ceil(this.filteredUsers.length / this.itemsPerPage)
    },
    paginatedUsers() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredUsers.slice(start, end)
    },
    visiblePages() {
      const pages = []
      const start = Math.max(1, this.currentPage - 2)
      const end = Math.min(this.totalPages, this.currentPage + 2)

      for (let i = start; i <= end; i++) {
        pages.push(i)
      }

      return pages
    }
  },
  watch: {
    filteredUsers() {
      this.currentPage = 1
    }
  },
  async mounted() {
    await this.fetchData()
  },
  methods: {
    async fetchData() {
      try {
        this.loading = true
        const [usersResponse, teamsResponse] = await Promise.all([
          axios.get('/api/users'),
          axios.get('/api/teams')
        ])

        this.users = usersResponse.data.users
        this.teams = teamsResponse.data.teams
      } catch (error) {
        console.error('Erreur lors du chargement des données:', error)
        this.$toast.error('Erreur lors du chargement des données')
      } finally {
        this.loading = false
      }
    },
    getRoleBadgeClass(role) {
      const classes = {
        'super_admin': 'badge-danger',
        'manager': 'badge-primary',
        'responsable_n1': 'badge-info',
        'responsable_n2': 'badge-warning',
        'cadre': 'badge-success',
        'stagiaire': 'badge-secondary'
      }
      return classes[role] || 'badge-secondary'
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    },
    editUser(user) {
      this.selectedUser = user
      this.showEditModal = true
    },
    confirmDelete(user) {
      this.selectedUser = user
      this.showDeleteModal = true
    },
    async deleteUser() {
      try {
        await axios.delete(`/api/users/${this.selectedUser.id}`)
        this.$toast.success('Utilisateur supprimé avec succès')
        this.showDeleteModal = false
        await this.fetchData()
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
        this.$toast.error(error.response?.data?.message || 'Erreur lors de la suppression')
      }
    },
    closeModal() {
      this.showCreateModal = false
      this.showEditModal = false
      this.selectedUser = null
    },
    async handleUserSaved() {
      this.closeModal()
      await this.fetchData()
    }
  }
}
</script>