<template>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Mon Profil</h1>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- Profile Information -->
          <div class="col-md-4">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img
                    class="profile-user-img img-fluid img-circle"
                    :src="user?.avatar ? `/storage/${user.avatar}` : '/images/default-avatar.png'"
                    alt="Avatar utilisateur"
                    style="width: 128px; height: 128px;">
                </div>

                <h3 class="profile-username text-center">{{ user?.nom }}</h3>

                <p class="text-muted text-center">{{ user?.fonction }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Email</b> <a class="float-right">{{ user?.email }}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Rôle</b> <span class="float-right badge badge-info">{{ user?.role }}</span>
                  </li>
                  <li class="list-group-item">
                    <b>Téléphone</b> <span class="float-right">{{ user?.numero_telephone || 'Non renseigné' }}</span>
                  </li>
                  <li class="list-group-item">
                    <b>Équipe</b>
                    <span v-if="user?.team" class="float-right">
                      {{ user.team.nom }}
                    </span>
                    <span v-else class="float-right text-muted">
                      Aucune équipe
                    </span>
                  </li>
                </ul>

                <div class="row">
                  <div class="col-6">
                    <button
                      @click="showEditModal = true"
                      class="btn btn-primary btn-block">
                      <i class="fas fa-edit"></i> Modifier
                    </button>
                  </div>
                  <div class="col-6">
                    <button
                      @click="showPasswordModal = true"
                      class="btn btn-warning btn-block">
                      <i class="fas fa-key"></i> Mot de passe
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Information -->
          <div class="col-md-8">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item">
                    <a class="nav-link active" href="#overview" data-toggle="tab">Vue d'ensemble</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="#permissions" data-toggle="tab">Permissions</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="#activity" data-toggle="tab">Activité</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content">
                  <!-- Overview Tab -->
                  <div class="active tab-pane" id="overview">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="info-box">
                          <span class="info-box-icon bg-info"><i class="fas fa-tasks"></i></span>
                          <div class="info-box-content">
                            <span class="info-box-text">Tâches assignées</span>
                            <span class="info-box-number">0</span>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="info-box">
                          <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                          <div class="info-box-content">
                            <span class="info-box-text">Tâches complétées</span>
                            <span class="info-box-number">0</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="row mt-3">
                      <div class="col-12">
                        <h5>Informations du compte</h5>
                        <table class="table table-striped">
                          <tr>
                            <td><strong>Membre depuis</strong></td>
                            <td>{{ formatDate(user?.created_at) }}</td>
                          </tr>
                          <tr>
                            <td><strong>Dernière connexion</strong></td>
                            <td>{{ formatDate(user?.updated_at) }}</td>
                          </tr>
                          <tr>
                            <td><strong>Statut du compte</strong></td>
                            <td><span class="badge badge-success">Actif</span></td>
                          </tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- Permissions Tab -->
                  <div class="tab-pane" id="permissions">
                    <h5>Rôles et Permissions</h5>

                    <div class="mb-3">
                      <h6>Rôles assignés:</h6>
                      <div v-if="user?.roles && user.roles.length > 0">
                        <span
                          v-for="role in user.roles"
                          :key="role.id"
                          class="badge badge-primary mr-2">
                          {{ role.name }}
                        </span>
                      </div>
                      <div v-else class="text-muted">
                        Aucun rôle assigné via Spatie Permission
                      </div>
                    </div>

                    <div>
                      <h6>Permissions disponibles:</h6>
                      <div v-if="user?.permissions && user.permissions.length > 0" class="row">
                        <div
                          v-for="permission in user.permissions"
                          :key="permission.id"
                          class="col-md-4 mb-2">
                          <span class="badge badge-success">{{ permission.name }}</span>
                        </div>
                      </div>
                      <div v-else class="text-muted">
                        Les permissions sont héritées du rôle système
                      </div>
                    </div>
                  </div>

                  <!-- Activity Tab -->
                  <div class="tab-pane" id="activity">
                    <div class="text-center text-muted">
                      <i class="fas fa-clock fa-3x mb-3"></i>
                      <p>Historique des activités à venir...</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Edit Profile Modal -->
    <EditProfileModal
      :show="showEditModal"
      :user="user"
      @close="showEditModal = false"
      @updated="handleProfileUpdated"
    />

    <!-- Change Password Modal -->
    <ChangePasswordModal
      :show="showPasswordModal"
      @close="showPasswordModal = false"
    />
  </div>
</template>

<script>
import EditProfileModal from './EditProfileModal.vue'
import ChangePasswordModal from './ChangePasswordModal.vue'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'UserProfile',
  components: {
    EditProfileModal,
    ChangePasswordModal
  },
  data() {
    return {
      user: null,
      showEditModal: false,
      showPasswordModal: false
    }
  },
  computed: {
    authStore() {
      return useAuthStore()
    }
  },
  async mounted() {
    await this.fetchProfile()
  },
  methods: {
    async fetchProfile() {
      try {
        const response = await axios.get('/api/profile')
        this.user = response.data.user
      } catch (error) {
        console.error('Erreur lors du chargement du profil:', error)
        this.$toast.error('Erreur lors du chargement du profil')
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'Non disponible'
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    async handleProfileUpdated(updatedUser) {
      this.user = updatedUser
      this.showEditModal = false
      // Update auth store with new user data
      await this.authStore.fetchUser()
    }
  }
}
</script>