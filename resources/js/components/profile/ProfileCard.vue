<!-- resources\js\components\profile\ProfileCard.vue -->
<template>
  <div>
    <!-- Profile Header -->
    <div class="p-5 mb-6 border border-gray-200 rounded-3 dark:border-gray-800 lg:p-6">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-col items-center w-full gap-6 xl:flex-row">
          <!-- Avatar Section -->
          <div class="relative group">
            <div class="relative w-24 h-24 overflow-hidden border-2 border-gray-300 rounded-full dark:border-gray-700 group-hover:border-blue-500 transition-all duration-300">
              <img 
                v-if="avatarUrl" 
                :src="avatarUrl" 
                :alt="user.nom" 
                class="w-full h-full object-cover transition-transform duration-300 "
              />
              <div
                v-else
                class="w-full h-full flex items-center justify-center text-white text-3xl font-bold"
              >
                {{ user.initials }}
              </div>
              
              <!-- Loading Overlay -->
              <div
                v-if="uploadingAvatar"
                class="absolute inset-0 bg-black/70 flex items-center justify-center"
              >
                <div class="animate-spin rounded-full h-10 w-10 border-3 border-t-transparent border-white"></div>
              </div>
            </div>
            
            <!-- Upload Button -->
            <label
              class="absolute bottom-1 right-1 flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded-full cursor-pointer transition-all duration-300 "
              :title="$t('profile_card.change_avatar')"
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
            <div v-if="avatarPreview" class="absolute top-0 left-0 w-24 h-24 rounded-full overflow-hidden border-4 border-white ">
              <img :src="avatarPreview" class="w-full h-full object-cover" />
            </div>
          </div>
          
          <!-- User Info -->
          <div class="order-3 xl:order-2 text-center xl:text-left">
            <h4 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">
              {{ user.nom }}
            </h4>
            <div class="flex flex-col items-center gap-1 xl:flex-row xl:gap-3">
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ user.fonction || user.bio || $t('profile_card.not_set') }}</p>
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
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t('profile_card.stat_projects') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-800 dark:text-white/90">47</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t('profile_card.stat_tasks') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-800 dark:text-white/90">89%</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t('profile_card.stat_efficiency') }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUsers } from '@/composables/useUsers'
import { useNotifications } from '@/composables/useNotifications'
 

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['refresh'])

const { t } = useI18n()
const { updateProfile } = useUsers()
const { showSuccess, showError } = useNotifications()

const uploadingAvatar = ref(false)
const avatarPreview = ref(null)
const avatarInput = ref(null)

const avatarUrl = computed(() => {
  if (props.user?.avatar) {
    return props.user.avatar
  }
  return null
})

const handleAvatarChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validation
  if (!file.type.startsWith('image/')) {
    showError(t('profile_card.avatar_invalid'))
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    showError(t('profile_card.avatar_too_large'))
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
    showSuccess(t('profile_card.avatar_success'))
    emit('refresh')

    // Reset preview after successful upload
    setTimeout(() => {
      avatarPreview.value = null
    }, 2000)
  } catch (error) {
    console.error('Error uploading avatar:', error)
    showError(error.response?.data?.message || t('profile_card.avatar_error'))
    avatarPreview.value = null
  } finally {
    uploadingAvatar.value = false
    if (avatarInput.value) {
      avatarInput.value.value = ''
    }
  }
}

</script>