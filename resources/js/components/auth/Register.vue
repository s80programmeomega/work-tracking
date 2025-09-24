<template>
  <div class="register-page">
    <div class="register-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <h1><b>Work</b>Tracking</h1>
        </div>
        <div class="card-body">
          <p class="login-box-msg">Créer un nouveau compte</p>

          <form @submit.prevent="register">
            <div class="input-group mb-3">
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom }"
                placeholder="Nom complet"
                v-model="form.nom"
                required
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-user"></span>
                </div>
              </div>
              <div v-if="errors.nom" class="invalid-feedback">
                {{ errors.nom[0] }}
              </div>
            </div>

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
              <select
                class="form-control"
                :class="{ 'is-invalid': errors.role }"
                v-model="form.role"
                required
              >
                <option value="">Sélectionner un rôle</option>
                <option value="stagiaire">Stagiaire</option>
                <option value="cadre">Cadre</option>
                <option value="responsable_n2">Responsable N2</option>
                <option value="responsable_n1">Responsable N1</option>
                <option value="manager">Manager</option>
                <option value="super_admin">Super Admin</option>
              </select>
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-user-tag"></span>
                </div>
              </div>
              <div v-if="errors.role" class="invalid-feedback">
                {{ errors.role[0] }}
              </div>
            </div>

            <div class="input-group mb-3">
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.fonction }"
                placeholder="Fonction (optionnel)"
                v-model="form.fonction"
              >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-briefcase"></span>
                </div>
              </div>
              <div v-if="errors.fonction" class="invalid-feedback">
                {{ errors.fonction[0] }}
              </div>
            </div>

            <div class="input-group mb-3">
              <input
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

            <div class="mb-4">
              <div class="icheck-primary">
                <input type="checkbox" id="agreeTerms" v-model="form.terms" required>
                <label for="agreeTerms">
                  J'accepte les <a href="#">conditions d'utilisation</a>
                </label>
              </div>
            </div>

            <div class="w-full">
              <button
                type="submit"
                class="btn btn-primary w-full"
                :disabled="loading"
              >
                <span v-if="loading" class="spinner-border spinner-border-sm mr-2"></span>
                <i v-if="!loading" class="fas fa-user-plus mr-2"></i>
                Créer mon compte
              </button>
            </div>
          </form>

          <p class="mb-0 text-center">
            <router-link to="/login" class="text-center">
              Déjà un compte ? Se connecter
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
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'Register',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()

    const form = reactive({
      nom: '',
      email: '',
      password: '',
      password_confirmation: '',
      role: '',
      fonction: '',
      terms: false
    })

    const loading = ref(false)
    const errors = ref({})

    const register = async () => {
      try {
        loading.value = true
        errors.value = {}

        await authStore.register({
          nom: form.nom,
          email: form.email,
          password: form.password,
          password_confirmation: form.password_confirmation,
          role: form.role,
          fonction: form.fonction
        })

        router.push('/login')

      } catch (error) {
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors
        } else {
          errors.value = { email: ['Une erreur est survenue lors de l\'inscription'] }
        }
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      errors,
      register
    }
  }
}
</script>

<style scoped>
.register-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.register-box {
  width: 500px;
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

.icheck-primary {
  margin-top: 8px;
}

.icheck-primary label {
  font-size: 0.95rem;
  color: #6c757d;
}
</style>