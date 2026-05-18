<template>
  <div class="login-page">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <h1><b>Work</b>Tracking</h1>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Connectez-vous pour accéder à votre espace</p>

          <form @submit.prevent="login">
            <div class="input-group mb-3">
              <input
                dusk="email"
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
                dusk="password"
                type="password"
                class="form-control"
                :class="{ 'is-invalid': errors.password }"
                placeholder="Mot de passe"
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

            <div class="mb-4">
              <div class="icheck-primary">
                <input type="checkbox" id="remember" v-model="form.remember">
                <label for="remember">
                  Se souvenir de moi
                </label>
              </div>
            </div>

            <div class="w-full">
              <button
                dusk="login-button"
                type="submit"
                class="btn btn-primary w-full"
                :disabled="loading"
              >
                <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
                <i v-if="!loading" class="fas fa-sign-in-alt mr-2"></i>
                Se connecter
              </button>
            </div>
          </form>

          <p class="mb-1">
            <router-link to="/forgot-password" class="text-center">
              Mot de passe oublié ?
            </router-link>
          </p>
          <p class="mb-0">
            <router-link to="/register" class="text-center">
              Créer un compte
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

export default {
  name: 'Login',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()

    const form = reactive({
      email: '',
      password: '',
      remember: false
    })

    const loading = ref(false)
    const errors = ref({})

    const login = async () => {
      try {
        loading.value = true
        errors.value = {}

        await authStore.login(form)

        // Redirect to dashboard
        router.push('/')

      } catch (error) {
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors
        } else if (error.response?.data?.message) {
          errors.value = { email: [error.response.data.message] }
        } else {
          errors.value = { email: ['Une erreur est survenue lors de la connexion'] }
        }
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      errors,
      login
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

.icheck-primary label {
  font-size: 0.95rem;
  color: #6c757d;
}
</style>