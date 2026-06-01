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
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
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
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
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
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
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
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg>
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
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25z"/></svg>
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
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25z"/></svg>
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
                <svg v-if="!loading" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
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
import { useAuthStore } from '@/stores/authStore'

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