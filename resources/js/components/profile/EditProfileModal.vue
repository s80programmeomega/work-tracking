<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Modifier mon profil</h4>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updateProfile" enctype="multipart/form-data">
            <div class="row">
              <!-- Avatar Upload -->
              <div class="col-md-4 text-center">
                <div class="form-group">
                  <label>Photo de profil</label>
                  <div class="mb-3">
                    <img
                      :src="previewAvatar || (user?.avatar ? `/storage/${user.avatar}` : '/images/default-avatar.png')"
                      alt="Avatar"
                      class="img-circle"
                      style="width: 150px; height: 150px;">
                  </div>
                  <div class="input-group">
                    <div class="custom-file">
                      <input
                        ref="avatarInput"
                        type="file"
                        class="custom-file-input"
                        :class="{ 'is-invalid': errors.avatar }"
                        id="avatar"
                        accept="image/*"
                        @change="handleAvatarChange">
                      <label class="custom-file-label" for="avatar">
                        {{ avatarFileName || 'Choisir une image...' }}
                      </label>
                    </div>
                  </div>
                  <div v-if="errors.avatar" class="invalid-feedback d-block">
                    {{ errors.avatar[0] }}
                  </div>
                  <small class="form-text text-muted">
                    Formats acceptés: JPG, PNG, GIF. Taille max: 2MB
                  </small>
                </div>
              </div>

              <!-- Profile Form -->
              <div class="col-md-8">
                <div class="form-group">
                  <label for="nom">Nom complet <span class="text-danger">*</span></label>
                  <input
                    v-model="form.nom"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.nom }"
                    id="nom"
                    placeholder="Votre nom complet"
                    required>
                  <div v-if="errors.nom" class="invalid-feedback">
                    {{ errors.nom[0] }}
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
                    placeholder="Votre fonction dans l'organisation">
                  <div v-if="errors.fonction" class="invalid-feedback">
                    {{ errors.fonction[0] }}
                  </div>
                </div>

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

                <!-- Read-only fields -->
                <div class="form-group">
                  <label>Email</label>
                  <input
                    :value="user?.email"
                    type="email"
                    class="form-control"
                    disabled>
                  <small class="form-text text-muted">
                    L'email ne peut pas être modifié depuis ce formulaire
                  </small>
                </div>

                <div class="form-group">
                  <label>Rôle</label>
                  <input
                    :value="user?.role"
                    type="text"
                    class="form-control"
                    disabled>
                  <small class="form-text text-muted">
                    Votre rôle ne peut être modifié que par un administrateur
                  </small>
                </div>

                <div class="form-group">
                  <label>Équipe</label>
                  <input
                    :value="user?.team ? user.team.nom : 'Aucune équipe'"
                    type="text"
                    class="form-control"
                    disabled>
                  <small class="form-text text-muted">
                    L'affectation d'équipe est gérée par les responsables
                  </small>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="$emit('close')">
            Annuler
          </button>
          <button type="button" class="btn btn-primary" @click="updateProfile" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
            Mettre à jour
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EditProfileModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    user: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      form: {
        nom: '',
        fonction: '',
        numero_telephone: ''
      },
      avatarFile: null,
      previewAvatar: null,
      avatarFileName: '',
      loading: false,
      errors: {}
    }
  },
  watch: {
    show(newVal) {
      if (newVal && this.user) {
        this.initForm()
      }
    }
  },
  methods: {
    initForm() {
      this.form = {
        nom: this.user?.nom || '',
        fonction: this.user?.fonction || '',
        numero_telephone: this.user?.numero_telephone || ''
      }
      this.avatarFile = null
      this.previewAvatar = null
      this.avatarFileName = ''
      this.errors = {}
    },
    handleAvatarChange(event) {
      const file = event.target.files[0]
      if (file) {
        this.avatarFile = file
        this.avatarFileName = file.name

        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
          this.previewAvatar = e.target.result
        }
        reader.readAsDataURL(file)
      } else {
        this.avatarFile = null
        this.previewAvatar = null
        this.avatarFileName = ''
      }
    },
    async updateProfile() {
      try {
        this.loading = true
        this.errors = {}

        // Create FormData for file upload
        const formData = new FormData()

        // Add form fields
        Object.keys(this.form).forEach(key => {
          if (this.form[key] !== null && this.form[key] !== '') {
            formData.append(key, this.form[key])
          }
        })

        // Add avatar file if selected
        if (this.avatarFile) {
          formData.append('avatar', this.avatarFile)
        }

        // Add method override for PUT request
        formData.append('_method', 'PUT')

        const response = await axios.post('/api/profile', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        this.$toast.success(response.data.message)
        this.$emit('updated', response.data.user)
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors
        } else {
          console.error('Erreur lors de la mise à jour du profil:', error)
          this.$toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour')
        }
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.custom-file-label::after {
  content: "Parcourir";
}
</style>