<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">
            {{ isEdit ? 'Modifier l\'utilisateur' : 'Créer un nouvel utilisateur' }}
          </h4>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveUser">
            <div class="row">
              <!-- Basic Information -->
              <div class="col-md-6">
                <div class="form-group">
                  <label for="nom">Nom complet <span class="text-danger">*</span></label>
                  <input
                    v-model="form.nom"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.nom }"
                    id="nom"
                    placeholder="Nom complet"
                    required>
                  <div v-if="errors.nom" class="invalid-feedback">
                    {{ errors.nom[0] }}
                  </div>
                </div>

                <div class="form-group">
                  <label for="email">Email <span class="text-danger">*</span></label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                    id="email"
                    placeholder="Adresse email"
                    required>
                  <div v-if="errors.email" class="invalid-feedback">
                    {{ errors.email[0] }}
                  </div>
                </div>

                <div v-if="!isEdit" class="form-group">
                  <label for="password">Mot de passe <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control"
                      :class="{ 'is-invalid': errors.password }"
                      id="password"
                      placeholder="Mot de passe"
                      required>
                    <div class="input-group-append">
                      <button
                        type="button"
                        class="btn btn-outline-secondary"
                        @click="showPassword = !showPassword">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                      </button>
                    </div>
                    <div v-if="errors.password" class="invalid-feedback">
                      {{ errors.password[0] }}
                    </div>
                  </div>
                  <small class="form-text text-muted">
                    Minimum 8 caractères
                  </small>
                </div>
              </div>

              <!-- Role and Organization -->
              <div class="col-md-6">
                <div class="form-group">
                  <label for="role">Rôle <span class="text-danger">*</span></label>
                  <select
                    v-model="form.role"
                    class="form-control"
                    :class="{ 'is-invalid': errors.role }"
                    id="role"
                    required>
                    <option value="">Sélectionnez un rôle</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="manager">Manager</option>
                    <option value="responsable_n1">Responsable N1</option>
                    <option value="responsable_n2">Responsable N2</option>
                    <option value="cadre">Cadre</option>
                    <option value="stagiaire">Stagiaire</option>
                  </select>
                  <div v-if="errors.role" class="invalid-feedback">
                    {{ errors.role[0] }}
                  </div>
                </div>

                <div class="form-group">
                  <label for="fonction">Fonction</label>
                  <input
                    v-model="form.fonction"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.fonction }"
                    id="fonction"
                    placeholder="Fonction dans l'organisation">
                  <div v-if="errors.fonction" class="invalid-feedback">
                    {{ errors.fonction[0] }}
                  </div>
                </div>

                <div class="form-group">
                  <label for="team_id">Équipe</label>
                  <select
                    v-model="form.team_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.team_id }"
                    id="team_id">
                    <option value="">Aucune équipe</option>
                    <option
                      v-for="team in teams"
                      :key="team.id"
                      :value="team.id">
                      {{ team.nom }}
                    </option>
                  </select>
                  <div v-if="errors.team_id" class="invalid-feedback">
                    {{ errors.team_id[0] }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact Information -->
            <div class="row">
              <div class="col-12">
                <h5>Informations de contact</h5>
                <hr>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="numero_telephone">Numéro de téléphone</label>
                  <input
                    v-model="form.numero_telephone"
                    type="tel"
                    class="form-control"
                    :class="{ 'is-invalid': errors.numero_telephone }"
                    id="numero_telephone"
                    placeholder="Ex: +33123456789">
                  <div v-if="errors.numero_telephone" class="invalid-feedback">
                    {{ errors.numero_telephone[0] }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Role Description -->
            <div v-if="form.role" class="row">
              <div class="col-12">
                <div class="alert alert-info">
                  <h6><i class="fas fa-info-circle"></i> Description du rôle:</h6>
                  <p class="mb-0">{{ getRoleDescription(form.role) }}</p>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="$emit('close')">
            Annuler
          </button>
          <button type="button" class="btn btn-primary" @click="saveUser" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
            {{ isEdit ? 'Mettre à jour' : 'Créer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    user: {
      type: Object,
      default: null
    },
    isEdit: {
      type: Boolean,
      default: false
    },
    teams: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      form: {
        nom: '',
        email: '',
        password: '',
        role: '',
        fonction: '',
        numero_telephone: '',
        team_id: ''
      },
      showPassword: false,
      loading: false,
      errors: {}
    }
  },
  watch: {
    show(newVal) {
      if (newVal) {
        this.initForm()
      }
    }
  },
  methods: {
    initForm() {
      if (this.isEdit && this.user) {
        this.form = {
          nom: this.user.nom || '',
          email: this.user.email || '',
          password: '', // Password not included in edit
          role: this.user.role || '',
          fonction: this.user.fonction || '',
          numero_telephone: this.user.numero_telephone || '',
          team_id: this.user.team_id || ''
        }
      } else {
        this.form = {
          nom: '',
          email: '',
          password: '',
          role: '',
          fonction: '',
          numero_telephone: '',
          team_id: ''
        }
      }
      this.errors = {}
      this.showPassword = false
    },
    getRoleDescription(role) {
      const descriptions = {
        'super_admin': 'Accès complet au système, gestion de tous les utilisateurs et paramètres.',
        'manager': 'Gestion de projets et équipes, création de tâches, validation des résultats.',
        'responsable_n1': 'Supervision directe des tâches, gestion des membres d\'équipe.',
        'responsable_n2': 'Coordination inter-équipes, supervision de niveau intermédiaire.',
        'cadre': 'Exécution et supervision limitée, gestion de ses propres tâches.',
        'stagiaire': 'Accès en lecture et exécution de base, apprentissage.'
      }
      return descriptions[role] || ''
    },
    async saveUser() {
      try {
        this.loading = true
        this.errors = {}

        // Prepare form data, excluding empty password for edit
        const formData = { ...this.form }
        if (this.isEdit && !formData.password) {
          delete formData.password
        }

        // Convert empty strings to null for optional fields
        if (!formData.team_id) formData.team_id = null
        if (!formData.fonction) formData.fonction = null
        if (!formData.numero_telephone) formData.numero_telephone = null

        let response
        if (this.isEdit) {
          response = await axios.put(`/api/users/${this.user.id}`, formData)
        } else {
          response = await axios.post('/api/users', formData)
        }

        this.$toast.success(response.data.message)
        this.$emit('saved', response.data.user)
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors
        } else {
          console.error('Erreur lors de la sauvegarde:', error)
          this.$toast.error(error.response?.data?.message || 'Erreur lors de la sauvegarde')
        }
      } finally {
        this.loading = false
      }
    }
  }
}
</script>