<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">
            <i class="fas fa-users"></i> {{ team?.nom }}
          </h4>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="team">
            <div class="row">
              <!-- Team Info -->
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header">
                    <h5>Informations de l'équipe</h5>
                  </div>
                  <div class="card-body">
                    <dl class="row">
                      <dt class="col-sm-4">Nom :</dt>
                      <dd class="col-sm-8">{{ team.nom }}</dd>

                      <dt class="col-sm-4">Description :</dt>
                      <dd class="col-sm-8">{{ team.description || 'Aucune description' }}</dd>

                      <dt class="col-sm-4">Responsable :</dt>
                      <dd class="col-sm-8">
                        <div v-if="team.responsable" class="d-flex align-items-center">
                          <div class="mr-3">
                            <img
                              :src="team.responsable.avatar ? `/storage/${team.responsable.avatar}` : '/images/default-avatar.png'"
                              class="img-circle elevation-2"
                              alt="Avatar"
                              style="width: 30px; height: 30px;">
                          </div>
                          <div>
                            <strong>{{ team.responsable.nom }}</strong><br>
                            <small class="text-muted">{{ team.responsable.role }} - {{ team.responsable.fonction }}</small>
                          </div>
                        </div>
                        <span v-else class="text-muted">Non assigné</span>
                      </dd>

                      <dt class="col-sm-4">Créée le :</dt>
                      <dd class="col-sm-8">{{ formatDate(team.created_at) }}</dd>
                    </dl>
                  </div>
                </div>
              </div>

              <!-- Team Members -->
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Membres de l'équipe ({{ team.membres.length }})</h5>
                    <button
                      v-if="canManageMembers"
                      @click="showMemberModal = true"
                      class="btn btn-sm btn-primary">
                      <i class="fas fa-user-plus"></i> Ajouter
                    </button>
                  </div>
                  <div class="card-body">
                    <div v-if="team.membres.length === 0" class="text-center text-muted">
                      <p>Aucun membre dans cette équipe.</p>
                    </div>
                    <div v-else>
                      <div
                        v-for="member in team.membres"
                        :key="member.id"
                        class="d-flex align-items-center mb-3">
                        <div class="mr-3">
                          <img
                            :src="member.avatar ? `/storage/${member.avatar}` : '/images/default-avatar.png'"
                            class="img-circle elevation-2"
                            alt="Avatar"
                            style="width: 40px; height: 40px;">
                        </div>
                        <div class="flex-grow-1">
                          <strong>{{ member.nom }}</strong><br>
                          <small class="text-muted">{{ member.role }} - {{ member.fonction }}</small>
                        </div>
                        <div v-if="canManageMembers">
                          <button
                            @click="removeMember(member)"
                            class="btn btn-sm btn-outline-danger"
                            title="Retirer de l'équipe">
                            <i class="fas fa-user-minus"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="$emit('close')">
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <AddMemberModal
      :show="showMemberModal"
      :team="team"
      @close="showMemberModal = false"
      @member-added="handleMemberAdded"
    />
  </div>
</template>

<script>
import AddMemberModal from './AddMemberModal.vue'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'TeamDetailModal',
  components: {
    AddMemberModal
  },
  props: {
    show: {
      type: Boolean,
      default: false
    },
    team: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      showMemberModal: false
    }
  },
  computed: {
    authStore() {
      return useAuthStore()
    },
    canManageMembers() {
      return this.authStore.hasPermission('team.manage_members')
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    async removeMember(member) {
      if (!confirm(`Êtes-vous sûr de vouloir retirer ${member.nom} de cette équipe ?`)) {
        return
      }

      try {
        await axios.delete(`/api/teams/${this.team.id}/members`, {
          data: { user_id: member.id }
        })

        this.$toast.success('Membre retiré de l\'équipe avec succès')

        // Update local team data
        const index = this.team.membres.findIndex(m => m.id === member.id)
        if (index !== -1) {
          this.team.membres.splice(index, 1)
        }
      } catch (error) {
        console.error('Erreur lors de la suppression du membre:', error)
        this.$toast.error(error.response?.data?.message || 'Erreur lors de la suppression du membre')
      }
    },
    handleMemberAdded(member) {
      this.showMemberModal = false
      // Add member to local team data
      this.team.membres.push(member)
    }
  }
}
</script>