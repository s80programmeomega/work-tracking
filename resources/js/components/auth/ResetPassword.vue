<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <h1><b>Work</b>Tracking</h1>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Réinitialisation de votre mot de passe</p>

          <div v-if="message" class="alert alert-success">
            {{ message }}
          </div>

          <form @submit.prevent="resetPassword" v-if="!message">
            <div class="input-group mb-3">
              <input
                type="email"
                class="form-control"
                :class="{ 'is-invalid': errors.email }"
                placeholder="Email"
                v-model="form.email"
                required
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
              <div v-if="errors.email" class="invalid-feedback">
                {{ errors.email[0] }}
              </div>
            </div>

            <div class="input-group mb-3">
              <input
                type="password"
                class="form-control"
                :class="{ 'is-invalid': errors.password }"
                placeholder="Nouveau mot de passe"
                v-model="form.password"
                required
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
              <div v-if="errors.password" class="invalid-feedback">
                {{ errors.password[0] }}
              </div>
            </div>

            <div class="input-group mb-3">
              <input
                type="password"
                class="form-control"
                :class="{ 'is-invalid': errors.password_confirmation }"
                placeholder="Confirmer le mot de passe"
                v-model="form.password_confirmation"
                required
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
              <div v-if="errors.password_confirmation" class="invalid-feedback">
                {{ errors.password_confirmation[0] }}
              </div>
            </div>

            <div class="w-full">
              <button
                type="submit"
                class="btn btn-primary w-full"
                :disabled="loading"
              >
                <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
                <i v-if="!loading" class="fas fa-key mr-2"></i>
                Réinitialiser
              </button>
            </div>
          </form>

          <p class="mt-3 mb-1">
            <router-link to="/login">Retour à la connexion</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'ResetPassword',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const authStore = useAuthStore()

    const form = reactive({
      token: '',
      email: '',
      password: '',
      password_confirmation: ''
    })

    const loading = ref(false)
    const errors = ref({})
    const message = ref('')

    onMounted(() => {
      form.token = route.query.token || ''
      form.email = route.query.email || ''
    })

    const resetPassword = async () => {
      try {
        loading.value = true
        errors.value = {}

        await authStore.resetPassword(form)

        message.value = 'Votre mot de passe a été réinitialisé avec succès.'

        // Redirect to login after 2 seconds
        setTimeout(() => {
          router.push('/login')
        }, 2000)

      } catch (error) {
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors
        } else {
          errors.value = { email: ['Une erreur est survenue lors de la réinitialisation'] }
        }
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      errors,
      message,
      resetPassword
    }
  }
}
</script>

<style scoped>
.login-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-box {
  width: 450px;
  margin: auto;
}

.card {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
}

.card-header {
  background: transparent;
  border-bottom: none;
  padding: 2.5rem 2rem 1.5rem;
}

.card-header h1 {
  font-size: 2.5rem;
  color: #495057;
  margin-bottom: 0;
}

.card-body {
  padding: 2rem;
}

.login-box-msg {
  margin-bottom: 2.5rem;
  text-align: center;
  color: #6c757d;
  font-size: 1.1rem;
  line-height: 1.4;
}

.input-group {
  margin-bottom: 1.5rem !important;
}

.form-control {
  padding: 12px 16px;
  font-size: 1rem;
}

.btn {
  padding: 1rem 1.5rem;
  font-size: 1.125rem;
  font-weight: 600;
  border-radius: 0.5rem;
  transition: all 0.3s ease-in-out;
  width: 100%;
}


.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  border: none;
  box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 123, 255, 0.4);
  background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
}

.btn-primary:disabled {
  background: #6c757d !important;
  box-shadow: none !important;
  transform: none !important;
  cursor: not-allowed;
}

.alert {
  margin-bottom: 1.5rem;
  padding: 1rem 1.25rem;
  border-radius: 8px;
}
</style>