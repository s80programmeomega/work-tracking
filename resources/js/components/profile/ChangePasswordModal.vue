<template>
  <div class="modal fade" :class="{ show: show }" :style="{ display: show ? 'block' : 'none' }">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Changer le mot de passe</h4>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="changePassword">
            <div class="form-group">
              <label for="current_password">Mot de passe actuel <span class="text-danger">*</span></label>
              <div class="input-group">
                <input
                  v-model="form.current_password"
                  :type="showCurrentPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-invalid': errors.current_password }"
                  id="current_password"
                  placeholder="Entrez votre mot de passe actuel"
                  required>
                <div class="input-group-append">
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="showCurrentPassword = !showCurrentPassword">
                    <svg v-if="showCurrentPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                  </button>
                </div>
                <div v-if="errors.current_password" class="invalid-feedback">
                  {{ errors.current_password[0] }}
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="new_password">Nouveau mot de passe <span class="text-danger">*</span></label>
              <div class="input-group">
                <input
                  v-model="form.new_password"
                  :type="showNewPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-invalid': errors.new_password }"
                  id="new_password"
                  placeholder="Entrez votre nouveau mot de passe"
                  required
                  minlength="8">
                <div class="input-group-append">
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="showNewPassword = !showNewPassword">
                    <svg v-if="showNewPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                  </button>
                </div>
                <div v-if="errors.new_password" class="invalid-feedback">
                  {{ errors.new_password[0] }}
                </div>
              </div>
              <small class="form-text text-muted">
                Le mot de passe doit contenir au moins 8 caractères.
              </small>
            </div>

            <div class="form-group">
              <label for="new_password_confirmation">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
              <div class="input-group">
                <input
                  v-model="form.new_password_confirmation"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-invalid': passwordMismatch }"
                  id="new_password_confirmation"
                  placeholder="Confirmez votre nouveau mot de passe"
                  required>
                <div class="input-group-append">
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="showConfirmPassword = !showConfirmPassword">
                    <svg v-if="showConfirmPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                  </button>
                </div>
                <div v-if="passwordMismatch" class="invalid-feedback">
                  Les mots de passe ne correspondent pas.
                </div>
              </div>
            </div>

            <!-- Password Strength Indicator -->
            <div v-if="form.new_password" class="form-group">
              <label class="form-label">Force du mot de passe:</label>
              <div class="progress" style="height: 8px;">
                <div
                  class="progress-bar"
                  :class="passwordStrengthClass"
                  role="progressbar"
                  :style="{ width: passwordStrengthPercent + '%' }"
                  :aria-valuenow="passwordStrengthPercent"
                  aria-valuemin="0"
                  aria-valuemax="100">
                </div>
              </div>
              <small class="form-text" :class="passwordStrengthTextClass">
                {{ passwordStrengthText }}
              </small>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="closeModal">
            Annuler
          </button>
          <button
            type="button"
            class="btn btn-primary"
            dusk="change-password-submit"
            @click="changePassword"
            :disabled="!canSubmit || loading">
            <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
            Changer le mot de passe
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api/axios'

export default {
  name: 'ChangePasswordModal',
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      form: {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      },
      showCurrentPassword: false,
      showNewPassword: false,
      showConfirmPassword: false,
      loading: false,
      errors: {}
    }
  },
  computed: {
    passwordMismatch() {
      return this.form.new_password_confirmation &&
             this.form.new_password !== this.form.new_password_confirmation
    },
    passwordStrength() {
      const password = this.form.new_password
      if (!password) return 0

      let strength = 0

      // Length check
      if (password.length >= 8) strength += 25
      if (password.length >= 12) strength += 25

      // Character variety checks
      if (/[a-z]/.test(password)) strength += 12.5
      if (/[A-Z]/.test(password)) strength += 12.5
      if (/[0-9]/.test(password)) strength += 12.5
      if (/[^A-Za-z0-9]/.test(password)) strength += 12.5

      return Math.min(100, strength)
    },
    passwordStrengthPercent() {
      return this.passwordStrength
    },
    passwordStrengthClass() {
      if (this.passwordStrength < 30) return 'bg-danger'
      if (this.passwordStrength < 60) return 'bg-warning'
      if (this.passwordStrength < 80) return 'bg-info'
      return 'bg-success'
    },
    passwordStrengthText() {
      if (this.passwordStrength < 30) return 'Faible'
      if (this.passwordStrength < 60) return 'Moyen'
      if (this.passwordStrength < 80) return 'Bon'
      return 'Très bon'
    },
    passwordStrengthTextClass() {
      if (this.passwordStrength < 30) return 'text-danger'
      if (this.passwordStrength < 60) return 'text-warning'
      if (this.passwordStrength < 80) return 'text-info'
      return 'text-success'
    },
    canSubmit() {
      return this.form.current_password &&
             this.form.new_password &&
             this.form.new_password_confirmation &&
             !this.passwordMismatch &&
             this.form.new_password.length >= 8
    }
  },
  watch: {
    show(newVal) {
      if (newVal) {
        this.resetForm()
      }
    }
  },
  methods: {
    resetForm() {
      this.form = {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      }
      this.showCurrentPassword = false
      this.showNewPassword = false
      this.showConfirmPassword = false
      this.errors = {}
    },
    closeModal() {
      this.resetForm()
      this.$emit('close')
    },
    async changePassword() {
      if (!this.canSubmit) return

      try {
        this.loading = true
        this.errors = {}

        const response = await api.post('/users/change-password', this.form)

        this.$toast.success(response.data.message)
        this.closeModal()
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors
        } else if (error.response?.status === 400) {
          this.$toast.error(error.response.data.message)
        } else {
          console.error('Erreur lors du changement de mot de passe:', error)
          this.$toast.error('Erreur lors du changement de mot de passe')
        }
      } finally {
        this.loading = false
      }
    }
  }
}
</script>