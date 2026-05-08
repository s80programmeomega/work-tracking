<template>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Gestion des Équipes</h1>
          </div>
          <div class="col-sm-6">
            <button
              v-if="canCreateTeam"
              @click="showCreateModal = true"
              class="btn btn-primary float-right">
              <i class="fas fa-plus"></i> Nouvelle Équipe
            </button>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <!-- Teams List -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Liste des Équipes</h3>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center">
              <div class="spinner-border" role="status">
                <span class="sr-only">Chargement...</span>
              </div>
            </div>

            <div v-else-if="teams.length === 0" class="text-center text-muted">
              <p>Aucune équipe trouvée.</p>
            </div>

            <div v-else class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Responsable</th>
                    <th>Membres</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="team in teams" :key="team.id">
                    <td>{{ team.nom }}</td>
                    <td>{{ team.description || 'Aucune description' }}</td>
                    <td>
                      <span v-if="team.responsable">
                        {{ team.responsable.nom }}
                        <small class="text-muted">({{ team.responsable.role }})</small>
                      </span>
                      <span v-else class="text-muted">Non assigné</span>
                    </td>
                    <td>
                      <span class="badge badge-info">{{ team.membres.length }} membre(s)</span>
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button
                          @click="viewTeam(team)"
                          class="btn btn-sm btn-outline-info">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button
                          v-if="canEditTeam"
                          @click="editTeam(team)"
                          class="btn btn-sm btn-outline-warning">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          v-if="canDeleteTeam"
                          @click="confirmDelete(team)"
                          class="btn btn-sm btn-outline-danger">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Create/Edit Modal -->
    <TeamModal
      :show="showCreateModal || showEditModal"
      :team="selectedTeam"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleTeamSaved"
    />

    <!-- View Modal -->
    <TeamDetailModal
      :show="showViewModal"
      :team="selectedTeam"
      @close="showViewModal = false"
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
            <p>Êtes-vous sûr de vouloir supprimer l'équipe <strong>{{ selectedTeam?.nom }}</strong> ?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" @click="showDeleteModal = false">Annuler</button>
            <button type="button" class="btn btn-danger" @click="deleteTeam">Supprimer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import TeamModal from './TeamModal.vue'
import TeamDetailModal from './TeamDetailModal.vue'
import { useAuthStore } from '@/stores/authStore'

export default {
  name: 'TeamList',
  components: {
    TeamModal,
    TeamDetailModal
  },
  data() {
    return {
      teams: [],
      loading: true,
      showCreateModal: false,
      showEditModal: false,
      showViewModal: false,
      showDeleteModal: false,
      selectedTeam: null,
    }
  },
  computed: {
    authStore() {
      return useAuthStore()
    },
    canCreateTeam() {
      return this.authStore.hasPermission('team.create')
    },
    canEditTeam() {
      return this.authStore.hasPermission('team.edit')
    },
    canDeleteTeam() {
      return this.authStore.hasPermission('team.delete')
    }
  },
  async mounted() {
    await this.fetchTeams()
  },
  methods: {
    async fetchTeams() {
      try {
        this.loading = true
        const response = await axios.get('/api/teams')
        this.teams = response.data.teams
      } catch (error) {
        console.error('Erreur lors du chargement des équipes:', error)
        this.$toast.error('Erreur lors du chargement des équipes')
      } finally {
        this.loading = false
      }
    },
    viewTeam(team) {
      this.selectedTeam = team
      this.showViewModal = true
    },
    editTeam(team) {
      this.selectedTeam = team
      this.showEditModal = true
    },
    confirmDelete(team) {
      this.selectedTeam = team
      this.showDeleteModal = true
    },
    async deleteTeam() {
      try {
        await axios.delete(`/api/teams/${this.selectedTeam.id}`)
        this.$toast.success('Équipe supprimée avec succès')
        this.showDeleteModal = false
        await this.fetchTeams()
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
        this.$toast.error(error.response?.data?.message || 'Erreur lors de la suppression')
      }
    },
    closeModal() {
      this.showCreateModal = false
      this.showEditModal = false
      this.selectedTeam = null
    },
    async handleTeamSaved() {
      this.closeModal()
      await this.fetchTeams()
    }
  }
}
</script>