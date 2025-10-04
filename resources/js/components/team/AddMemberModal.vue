<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Ajouter un membre à l'équipe</h4>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="text-center">
            <div class="spinner-border" role="status">
              <span class="sr-only">Chargement...</span>
            </div>
          </div>

          <div v-else-if="availableUsers.length === 0" class="text-center text-muted">
            <p>Aucun utilisateur disponible pour rejoindre cette équipe.</p>
          </div>

          <div v-else>
            <div class="form-group">
              <label for="selected_user">Sélectionner un utilisateur</label>
              <select
                v-model="selectedUserId"
                class="form-control"
                id="selected_user">
                <option value="">Choisir un utilisateur...</option>
                <option
                  v-for="user in availableUsers"
                  :key="user.id"
                  :value="user.id">
                  {{ user.nom }} ({{ user.role }}) - {{ user.email }}
                </option>
              </select>
            </div>

            <div v-if="selectedUserId" class="mt-3">
              <div class="card">
                <div class="card-body">
                  <h6>Utilisateur sélectionné :</h6>
                  <div class="d-flex align-items-center">
                    <div class="mr-3">
                      <i class="fas fa-user-circle fa-2x text-muted"></i>
                    </div>
                    <div>
                      <strong>{{ selectedUser?.nom }}</strong><br>
                      <small class="text-muted">
                        {{ selectedUser?.role }} - {{ selectedUser?.fonction }}
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="$emit('close')">
            Annuler
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="addMember"
            :disabled="!selectedUserId || saving">
            <span v-if="saving" class="spinner-border spinner-border-sm" role="status"></span>
            Ajouter à l'équipe
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AddMemberModal',
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
      availableUsers: [],
      selectedUserId: '',
      loading: true,
      saving: false
    }
  },
  computed: {
    selectedUser() {
      return this.availableUsers.find(user => user.id == this.selectedUserId)
    }
  },
  watch: {
    show(newVal) {
      if (newVal) {
        this.fetchAvailableUsers()
        this.selectedUserId = ''
      }
    }
  },
  methods: {
    async fetchAvailableUsers() {
      try {
        this.loading = true
        const response = await axios.get('/api/teams/members/available')
        this.availableUsers = response.data.users
      } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error)
        this.$toast.error('Erreur lors du chargement des utilisateurs disponibles')
      } finally {
        this.loading = false
      }
    },
    async addMember() {
      if (!this.selectedUserId || !this.team) return

      try {
        this.saving = true

        await axios.post(`/api/teams/${this.team.id}/members`, {
          user_id: this.selectedUserId
        })

        this.$toast.success('Membre ajouté à l\'équipe avec succès')

        // Emit the selected user data to parent
        this.$emit('member-added', this.selectedUser)

        // Remove the user from available users list
        this.availableUsers = this.availableUsers.filter(
          user => user.id != this.selectedUserId
        )

        this.selectedUserId = ''
      } catch (error) {
        console.error('Erreur lors de l\'ajout du membre:', error)
        this.$toast.error(error.response?.data?.message || 'Erreur lors de l\'ajout du membre')
      } finally {
        this.saving = false
      }
    }
  }
}
</script>