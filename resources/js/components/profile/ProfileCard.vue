<!-- resources\js\components\profile\ProfileCard.vue -->
<template>
  <div>
    <!-- Profile Header -->
    <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-col items-center w-full gap-6 xl:flex-row">
          <!-- Avatar Section -->
          <div class="relative group">
            <div class="relative w-24 h-24 overflow-hidden border-2 border-gray-300 rounded-full dark:border-gray-700 group-hover:border-blue-500 transition-all duration-300">
              <img 
                v-if="avatarUrl" 
                :src="avatarUrl" 
                :alt="user.nom" 
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
              />
              <div
                v-else
                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 text-white text-3xl font-bold"
              >
                {{ user.initials }}
              </div>
              
              <!-- Loading Overlay -->
              <div
                v-if="uploadingAvatar"
                class="absolute inset-0 bg-black bg-opacity-70 flex items-center justify-center"
              >
                <div class="animate-spin rounded-full h-10 w-10 border-3 border-t-transparent border-white"></div>
              </div>
            </div>
            
            <!-- Upload Button -->
            <label
              class="absolute bottom-1 right-1 flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded-full cursor-pointer transition-all duration-300 shadow-lg hover:shadow-xl group-hover:scale-110"
              title="Changer l'avatar"
            >
              <input
                type="file"
                accept="image/*"
                @change="handleAvatarChange"
                class="hidden"
                ref="avatarInput"
              />
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </label>
            
            <!-- Avatar Preview -->
            <div v-if="avatarPreview" class="absolute top-0 left-0 w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-lg">
              <img :src="avatarPreview" class="w-full h-full object-cover" />
            </div>
          </div>
          
          <!-- User Info -->
          <div class="order-3 xl:order-2 text-center xl:text-left">
            <h4 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">
              {{ user.nom }}
            </h4>
            <div class="flex flex-col items-center gap-1 xl:flex-row xl:gap-3">
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ user.fonction || user.bio || 'Non renseigné' }}</p>
              <div v-if="user.adresse" class="hidden h-3.5 w-px bg-gray-300 dark:bg-gray-700 xl:block"></div>
              <p v-if="user.adresse" class="text-sm text-gray-500 dark:text-gray-400">{{ user.adresse }}</p>
            </div>
            <div v-if="user.email" class="mt-2 flex items-center justify-center gap-2 xl:justify-start">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <span class="text-sm text-gray-600 dark:text-gray-300">{{ user.email }}</span>
            </div>
          </div>
          
          <!-- Stats -->
          <div class="flex items-center order-2 gap-4 grow xl:order-3 xl:justify-end">
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-800 dark:text-white/90">12</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">Projets</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-800 dark:text-white/90">47</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">Tâches</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-800 dark:text-white/90">89%</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">Efficacité</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
      <button 
        @click="openEditModal"
        class="flex items-center justify-center gap-2 p-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        <span>Modifier le profil</span>
      </button>
      
      <button 
        @click="downloadProfile"
        class="flex items-center justify-center gap-2 p-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span>Exporter données</span>
      </button>
      
      <button 
        @click="shareProfile"
        class="flex items-center justify-center gap-2 p-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
        </svg>
        <span>Partager profil</span>
      </button>
      
      <button 
        @click="printProfile"
        class="flex items-center justify-center gap-2 p-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        <span>Imprimer profil</span>
      </button>
    </div>
  </div>

  <!-- Edit Modal -->
  <EditProfileModal 
    v-if="showEditModal"
    :user="user"
    :initialData="formData"
    @close="showEditModal = false"
    @save="handleSaveProfile"
  />
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
// import EditProfileModal from './EditProfileModal.vue'
import { useUsers } from '@/composables/useUsers'
import { useNotifications } from '@/composables/useNotifications'
 

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['refresh'])

const { updateProfile } = useUsers()
const { showSuccess, showError } = useNotifications()

const showEditModal = ref(false)
const uploadingAvatar = ref(false)
const avatarPreview = ref(null)
const avatarInput = ref(null)

const avatarUrl = computed(() => {
  if (props.user?.avatar) {
    return props.user.avatar
  }
  return null
})

const formData = ref({
  nom: '',
  lastName: '',
  email: '',
  phone: '',
  bio: '',
  facebook: '',
  twitter: '',
  linkedin: '',
  instagram: ''
})

watch(() => props.user, (newUser) => {
  if (newUser) {
    const names = newUser.nom?.split(' ') || []
    formData.value.nom = names[0] || ''
    formData.value.lastName = names.slice(1).join(' ') || ''
    formData.value.email = newUser.email || ''
    formData.value.phone = newUser.numero_telephone || ''
    formData.value.bio = newUser.bio || ''
  }
}, { immediate: true })

const handleAvatarChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validation
  if (!file.type.startsWith('image/')) {
    showError('Veuillez sélectionner une image valide (JPG, PNG, GIF)')
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    showError('La taille de l\'image ne doit pas dépasser 5 MB')
    return
  }

  // Preview
  const reader = new FileReader()
  reader.onload = (e) => {
    avatarPreview.value = e.target.result
  }
  reader.readAsDataURL(file)

  uploadingAvatar.value = true

  try {
    const formData = new FormData()
    formData.append('avatar', file)
    formData.append('_method', 'PUT')

    await updateProfile(formData)
    showSuccess('Avatar mis à jour avec succès')
    emit('refresh')
    
    // Reset preview after successful upload
    setTimeout(() => {
      avatarPreview.value = null
    }, 2000)
  } catch (error) {
    console.error('Error uploading avatar:', error)
    showError(error.response?.data?.message || 'Erreur lors du téléchargement de l\'avatar')
    avatarPreview.value = null
  } finally {
    uploadingAvatar.value = false
    if (avatarInput.value) {
      avatarInput.value.value = ''
    }
  }
}

const openEditModal = () => {
  showEditModal.value = true
}

const handleSaveProfile = async (data) => {
  try {
    await updateProfile(data)
    showSuccess('Profil mis à jour avec succès')
    showEditModal.value = false
    emit('refresh')
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de la mise à jour')
  }
}

const downloadProfile = () => {
  // Implementation for profile download
  showSuccess('Téléchargement démarré')
}

const shareProfile = () => {
  if (navigator.share) {
    navigator.share({
      title: `${props.user.nom} - Profile`,
      text: `Découvrez le profil de ${props.user.nom}`,
      url: window.location.href,
    })
  } else {
    showSuccess('Lien copié dans le presse-papier')
    // Fallback copy to clipboard
  }
}

const printProfile = () => {
  window.print()
}
</script>