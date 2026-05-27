<!-- resources\js\components\profile\EditProfileModal.vue -->
<template>
  <Modal @close="$emit('close')">
    <template #body>
      <div class="relative w-full max-w-2xl overflow-y-auto bg-white rounded-3 dark:bg-gray-900">
        <!-- Header -->
        <div class="sticky top-0 z-10 flex items-center justify-between p-6 bg-white border-b dark:bg-gray-900 dark:border-gray-800">
          <div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">
              Modifier le profil
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Mettez à jour vos informations personnelles
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 transition-colors rounded-3 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <div class="p-6 space-y-6">
          <!-- Tabs -->
          <div class="border-b border-gray-200 dark:border-gray-800">
            <div class="flex -mb-px space-x-8">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'py-4 px-1 font-medium text-sm border-b-2 transition-colors',
                  activeTab === tab.id
                    ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                {{ tab.label }}
              </button>
            </div>
          </div>

          <!-- Informations Personnelles -->
          <div v-if="activeTab === 'personal'" class="space-y-6">
            <!-- Avatar picker -->
            <div class="flex items-center gap-4">
              <div class="relative">
                <img
                  :src="avatarPreview || user.avatar || '/images/default-avatar.png'"
                  alt="Avatar"
                  class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700"
                />
                <label class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center cursor-pointer hover:bg-blue-700 transition-colors">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-1.414.94l-4.243 1.415 1.415-4.243a4 4 0 01.94-1.414z" />
                  </svg>
                  <input type="file" accept="image/jpeg,image/png,image/gif" class="sr-only" @change="onAvatarChange" />
                </label>
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                <p>JPG, PNG ou GIF — max 5 Mo</p>
                <p v-if="avatarFile" class="text-blue-600 dark:text-blue-400">{{ avatarFile.name }}</p>
              </div>
            </div>

            <div v-if="globalError" class="text-sm text-red-600 bg-red-50 dark:bg-red-900/20 rounded-3 px-3 py-2">
              {{ globalError }}
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Nom * 
                </label>
                <input
                  v-model="form.nom"
                  type="text"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-3 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.nom }"
                />
                <p v-if="errors.nom" class="mt-1 text-sm text-red-600">
                  {{ errors.nom }}
                </p>
              </div>

              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Prénom *
                </label>
                <input
                  v-model="form.prenom"
                  type="text"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-3 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.prenom }"
                />
                <p v-if="errors.prenom" class="mt-1 text-sm text-red-600">
                  {{ errors.prenom }}
                </p>
              </div>

              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Email *
                </label>
                <input
                  v-model="form.email"
                  type="email"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-3 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.email }"
                />
                <p v-if="errors.email" class="mt-1 text-sm text-red-600">
                  {{ errors.email }}
                </p>
              </div>

              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Téléphone
                </label>
                <input
                  v-model="form.phone"
                  type="tel"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-3 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                Biographie
              </label>
              <textarea
                v-model="form.bio"
                rows="3"
                class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-3 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Décrivez-vous en quelques mots..."
              ></textarea>
              <p class="mt-1 text-xs text-gray-500">
                {{ form.bio.length }}/200 caractères
              </p>
            </div>
          </div>

          <!-- Adresse -->
          <div v-if="activeTab === 'address'" class="space-y-6">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                Adresse complète
              </label>
              <textarea
                v-model="form.address"
                rows="3"
                class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-3 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Langue
                </label>
                <select
                  v-model="form.language"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-3 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="fr">Français</option>
                  <option value="en">English</option>
                  <option value="es">Español</option>
                </select>
              </div>

              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Fuseau horaire
                </label>
                <select
                  v-model="form.timezone"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-3 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="Europe/Paris">Europe/Paris (UTC+1)</option>
                  <option value="UTC">UTC</option>
                  <option value="America/New_York">America/New_York (UTC-5)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Social -->
          <div v-if="activeTab === 'social'" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  LinkedIn
                </label>
                <div class="flex">
                  <span class="inline-flex items-center px-3 text-gray-500 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                  </span>
                  <input
                    v-model="form.linkedin"
                    type="text"
                    placeholder="https://linkedin.com/in/..."
                    class="flex-1 px-4 py-3 text-gray-800 bg-gray-50 border border-l-0 border-gray-300 rounded-r-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Twitter
                </label>
                <div class="flex">
                  <span class="inline-flex items-center px-3 text-gray-500 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.213c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                  </span>
                  <input
                    v-model="form.twitter"
                    type="text"
                    placeholder="https://twitter.com/..."
                    class="flex-1 px-4 py-3 text-gray-800 bg-gray-50 border border-l-0 border-gray-300 rounded-r-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 flex items-center justify-between p-6 bg-gray-50 border-t dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center space-x-4">
            <button
              type="button"
              @click="resetForm"
              class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-3 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
            >
              Réinitialiser
            </button>
          </div>
          <div class="flex items-center space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-3 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
            >
              Annuler
            </button>
            <button
              type="button"
              @click="handleSave"
              :disabled="saving"
              class="px-4 py-2 text-sm font-medium text-white transition-colors bg-blue-600 rounded-3 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="saving">
                <svg class="inline w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Enregistrement...
              </span>
              <span v-else>
                Enregistrer les modifications
              </span>
            </button>
          </div>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import Modal from './Modal.vue'
import api from '@/api/axios'

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'updated'])

const activeTab = ref('personal')
const saving = ref(false)
const errors = reactive({})
const globalError = ref(null)
const avatarFile = ref(null)
const avatarPreview = ref(null)

const tabs = [
  { id: 'personal', label: 'Informations personnelles' },
  { id: 'address', label: 'Adresse' },
  { id: 'social', label: 'Réseaux sociaux' },
]

const form = reactive({
  nom: '',
  prenom: '',
  email: '',
  phone: '',
  bio: '',
  address: '',
  language: 'fr',
  timezone: 'Europe/Paris',
  linkedin: '',
  twitter: '',
  github: '',
})

watch(() => props.user, (u) => {
  if (!u) { return }
  const parts = (u.nom || '').split(' ')
  form.nom = parts[0] ?? ''
  form.prenom = parts.slice(1).join(' ')
  form.email = u.email ?? ''
  form.phone = u.numero_telephone ?? ''
  form.bio = u.bio ?? ''
  form.address = u.adresse ?? ''
  form.language = u.language ?? 'fr'
  form.timezone = u.timezone ?? 'Europe/Paris'
  const links = u.social_links ?? {}
  form.linkedin = links.linkedin ?? ''
  form.twitter = links.twitter ?? ''
  form.github = links.github ?? ''
}, { immediate: true })

const onAvatarChange = (e) => {
  const file = e.target.files?.[0]
  if (!file) { return }
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

const validateForm = () => {
  Object.keys(errors).forEach(key => delete errors[key])
  globalError.value = null
  let isValid = true

  if (!form.nom.trim()) {
    errors.nom = 'Le nom est requis'
    isValid = false
  }
  if (!form.email.trim()) {
    errors.email = "L'email est requis"
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = "Format d'email invalide"
    isValid = false
  }

  return isValid
}

const handleSave = async () => {
  if (!validateForm()) { return }
  saving.value = true

  try {
    const fd = new FormData()
    fd.append('nom', form.nom)
    if (form.prenom) { fd.append('prenom', form.prenom) }
    fd.append('email', form.email)
    if (form.phone) { fd.append('numero_telephone', form.phone) }
    if (form.bio) { fd.append('bio', form.bio) }
    if (form.address) { fd.append('adresse', form.address) }
    fd.append('language', form.language)
    fd.append('timezone', form.timezone)
    fd.append('social_links[linkedin]', form.linkedin)
    fd.append('social_links[twitter]', form.twitter)
    fd.append('social_links[github]', form.github)
    if (avatarFile.value) { fd.append('avatar', avatarFile.value) }

    const { data } = await api.post('/users/profile', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    emit('updated', data.data ?? data)
    emit('close')
  } catch (err) {
    const serverErrors = err.response?.data?.errors
    if (serverErrors) {
      Object.assign(errors, serverErrors)
    } else {
      globalError.value = err.response?.data?.message ?? 'Une erreur est survenue.'
    }
  } finally {
    saving.value = false
  }
}

const resetForm = () => {
  avatarFile.value = null
  avatarPreview.value = null
  Object.keys(errors).forEach(key => delete errors[key])
  globalError.value = null
}
</script>