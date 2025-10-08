<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Gestion des Projets'" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <!-- Navigation Tabs -->
      <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <nav class="-mb-px flex space-x-8" aria-label="Tabs">
          <button
            @click="activeTab = 'dashboard'"
            :class="[
              activeTab === 'dashboard'
                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Dashboard
          </button>
          <button
            @click="activeTab = 'list'"
            :class="[
              activeTab === 'list'
                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Liste des projets
          </button>
          <button
            v-if="selectedProjetId"
            @click="activeTab = 'detail'"
            :class="[
              activeTab === 'detail'
                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Détail projet
          </button>
        </nav>
      </div>
    </div>

    <!-- Tab Contents -->
    <div class="mt-6">
      <ProjetDashboard
        v-if="activeTab === 'dashboard'"
        @view-all="activeTab = 'list'"
        @view-projet="viewProjet"
      />

      <ProjetList
        v-if="activeTab === 'list'"
        @view-projet="viewProjet"
      />

      <ProjetDetail
        v-if="activeTab === 'detail' && selectedProjetId"
        :projet-id="selectedProjetId"
        @back="backToList"
        @create-activity="createActivity"
        @view-activity="viewActivity"
      />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'

import {
  ProjetDashboard,
  ProjetList,
  ProjetDetail
} from '@/components/projets'

const activeTab = ref('dashboard')
const selectedProjetId = ref(null)

const viewProjet = (projetId) => {
  selectedProjetId.value = projetId
  activeTab.value = 'detail'
}

const backToList = () => {
  activeTab.value = 'list'
}

const createActivity = () => {
  // Navigate to activity creation form
  console.log('Creating activity for project:', selectedProjetId.value)
  // TODO: Implement activity creation
}

const viewActivity = (activityId) => {
  // Navigate to activity detail
  console.log('Viewing activity:', activityId)
  // TODO: Implement activity view
}
</script>