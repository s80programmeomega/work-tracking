<!-- resources\js\components\profile\EditProfileModal.vue -->
<template>
  <Modal @close="$emit('close')">
    <template #body>
      <div class="relative w-full max-w-2xl overflow-y-auto bg-white rounded-3xl dark:bg-gray-900">
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
            class="p-2 text-gray-400 transition-colors rounded-lg hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
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
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Prénom *
                </label>
                <input
                  v-model="form.firstName"
                  type="text"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.firstName }"
                />
                <p v-if="errors.firstName" class="mt-1 text-sm text-red-600">
                  {{ errors.firstName }}
                </p>
              </div>

              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Nom *
                </label>
                <input
                  v-model="form.lastName"
                  type="text"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.lastName }"
                />
                <p v-if="errors.lastName" class="mt-1 text-sm text-red-600">
                  {{ errors.lastName }}
                </p>
              </div>

              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Email *
                </label>
                <input
                  v-model="form.email"
                  type="email"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                  Langue
                </label>
                <select
                  v-model="form.language"
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                  class="w-full px-4 py-3 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
              class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
            >
              Réinitialiser
            </button>
          </div>
          <div class="flex items-center space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
            >
              Annuler
            </button>
            <button
              type="button"
              @click="handleSave"
              :disabled="saving"
              class="px-4 py-2 text-sm font-medium text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
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

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  initialData: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'save'])

const activeTab = ref('personal')
const saving = ref(false)
const errors = reactive({})

const tabs = [
  { id: 'personal', label: 'Informations personnelles' },
  { id: 'address', label: 'Adresse' },
  { id: 'social', label: 'Réseaux sociaux' }
]

const form = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  bio: '',
  address: '',
  language: 'fr',
  timezone: 'Europe/Paris',
  linkedin: '',
  twitter: '',
  github: ''
})

watch(() => props.initialData, (newData) => {
  Object.assign(form, newData)
}, { immediate: true })

const validateForm = () => {
  // Clear errors
  Object.keys(errors).forEach(key => delete errors[key])

  let isValid = true

  if (!form.firstName.trim()) {
    errors.firstName = 'Le prénom est requis'
    isValid = false
  }

  if (!form.lastName.trim()) {
    errors.lastName = 'Le nom est requis'
    isValid = false
  }

  if (!form.email.trim()) {
    errors.email = 'L\'email est requis'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Format d\'email invalide'
    isValid = false
  }

  return isValid
}

const handleSave = async () => {
  if (!validateForm()) {
    return
  }

  saving.value = true

  try {
    const data = {
      nom: `${form.firstName} ${form.lastName}`.trim(),
      email: form.email,
      numero_telephone: form.phone,
      bio: form.bio,
      adresse: form.address,
      language: form.language,
      timezone: form.timezone,
      social_links: {
        linkedin: form.linkedin,
        twitter: form.twitter,
        github: form.github
      }
    }

    await emit('save', data)
  } catch (error) {
    console.error('Error saving profile:', error)
  } finally {
    saving.value = false
  }
}

const resetForm = () => {
  Object.assign(form, props.initialData)
}
</script>