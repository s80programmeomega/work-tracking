<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">
            {{ isEdit ? 'Modifier l\'équipe' : 'Créer une nouvelle équipe' }}
          </h4>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveTeam">
            <div class="form-group">
              <label for="nom">Nom de l'équipe <span class="text-danger">*</span></label>
              <input
                v-model="form.nom"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom }"
                id="nom"
                placeholder="Entrez le nom de l'équipe"
                required>
              <div v-if="errors.nom" class="invalid-feedback">
                {{ errors.nom[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="description">Description</label>
              <textarea
                v-model="form.description"
                class="form-control"
                :class="{ 'is-invalid': errors.description }"
                id="description"
                rows="3"
                placeholder="Description de l'équipe (optionnel)">
              </textarea>
              <div v-if="errors.description" class="invalid-feedback">
                {{ errors.description[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="responsable_id">Responsable de l'équipe <span class="text-danger">*</span></label>
              <select
                v-model="form.responsable_id"
                class="form-control"
                :class="{ 'is-invalid': errors.responsable_id }"
                id="responsable_id"
                required>
                <option value="">Sélectionnez un responsable</option>
                <option
                  v-for="user in managers"
                  :key="user.id"
                  :value="user.id">
                  {{ user.nom }} ({{ user.role }})
                </option>
              </select>
              <div v-if="errors.responsable_id" class="invalid-feedback">
                {{ errors.responsable_id[0] }}
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="$emit('close')">
            Annuler
          </button>
          <button type="button" class="btn btn-primary" @click="saveTeam" :disabled="loading">
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
  name: 'TeamModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    team: {
      type: Object,
      default: null
    },
    isEdit: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      form: {
        nom: '',
        description: '',
        responsable_id: ''
      },
      managers: [],
      loading: false,
      errors: {}
    }
  },
  watch: {
    show(newVal) {
      if (newVal) {
        this.initForm()
        this.fetchManagers()
      }
    }
  },
  methods: {
    initForm() {
      if (this.isEdit && this.team) {
        this.form = {
          nom: this.team.nom,
          description: this.team.description || '',
          responsable_id: this.team.responsable_id
        }
      } else {
        this.form = {
          nom: '',
          description: '',
          responsable_id: ''
        }
      }
      this.errors = {}
    },
    async fetchManagers() {
      try {
        const response = await axios.get('/api/users')
        // Filter users who can be team managers
        this.managers = response.data.users.filter(user =>
          ['super_admin', 'manager', 'responsable_n1', 'responsable_n2'].includes(user.role)
        )
      } catch (error) {
        console.error('Erreur lors du chargement des managers:', error)
        this.$toast.error('Erreur lors du chargement des responsables')
      }
    },
    async saveTeam() {
      try {
        this.loading = true
        this.errors = {}

        let response
        if (this.isEdit) {
          response = await axios.put(`/api/teams/${this.team.id}`, this.form)
        } else {
          response = await axios.post('/api/teams', this.form)
        }

        this.$toast.success(response.data.message)
        this.$emit('saved', response.data.team)
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