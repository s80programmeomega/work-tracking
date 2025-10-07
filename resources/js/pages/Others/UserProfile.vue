<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <div
      class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
    >
      <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-7">Profile</h3>

      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-500">Chargement du profil...</p>
      </div>

      <!-- Profile Content -->
      <template v-else-if="profileData">
        <profile-card :user="profileData" @refresh="loadProfile" />
        <personal-info-card :user="profileData" @refresh="loadProfile" />
        <address-card :user="profileData" @refresh="loadProfile" />
      </template>

      <!-- Error State -->
      <div v-else-if="error" class="p-8 text-center text-red-600">
        {{ error }}
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import AdminLayout from '../../components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { ref, onMounted } from 'vue'
import ProfileCard from '../../components/profile/ProfileCard.vue'
import PersonalInfoCard from '../../components/profile/PersonalInfoCard.vue'
import AddressCard from '../../components/profile/AddressCard.vue'
import { useUsers } from '@/composables/useUsers'

const currentPageTitle = ref('User Profile')
const profileData = ref(null)
const loading = ref(false)
const error = ref(null)

const { fetchProfile } = useUsers()

const loadProfile = async () => {
  loading.value = true
  error.value = null

  try {
    profileData.value = await fetchProfile()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement du profil'
    console.error('Error loading profile:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>
