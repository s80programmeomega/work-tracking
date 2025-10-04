<template>
  <div class="min-h-screen bg-background">
    <div class="container mx-auto px-4 py-8">
      <!-- Navigation Tabs -->
      <div class="mb-6" v-if="currentView !== 'detail'">
        <Tabs :value="currentTab" @update:value="currentTab = $event">
          <TabsList class="grid w-fit grid-cols-2">
            <TabsTrigger value="dashboard">Dashboard</TabsTrigger>
            <TabsTrigger value="list">Liste des activités</TabsTrigger>
          </TabsList>
        </Tabs>
      </div>

      <!-- Dashboard View -->
      <ActivityDashboard
        v-if="currentView === 'dashboard'"
        @view-all="showList"
        @view-activite="viewActivite"
      />

      <!-- List View -->
      <ActivityList
        v-else-if="currentView === 'list'"
        @view-activite="viewActivite"
      />

      <!-- Detail View -->
      <ActivityDetail
        v-else-if="currentView === 'detail'"
        :activite="selectedActivite"
        :taches="activiteTaches"
        @go-back="goBack"
        @edit-activite="editActivite"
        @view-tasks="viewTasks"
        @create-task="createTask"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import ActivityList from '@/components/activites/ActivityList.vue'
import ActivityDetail from '@/components/activites/ActivityDetail.vue'
import ActivityDashboard from '@/components/activites/ActivityDashboard.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabsList from '@/components/ui/tabs/TabsList.vue'
import TabsTrigger from '@/components/ui/tabs/TabsTrigger.vue'

const currentTab = ref('dashboard')
const selectedActivite = ref(null)
const activiteTaches = ref([])

const currentView = computed(() => {
  if (selectedActivite.value) return 'detail'
  return currentTab.value
})

const viewActivite = async (activiteId) => {
  try {
    // TODO: Replace with actual API calls
    const [activiteResponse, tachesResponse] = await Promise.all([
      activiteApi.get(activiteId),
      tacheApi.getByActivite(activiteId)
    ])

    selectedActivite.value = activiteResponse.data
    activiteTaches.value = tachesResponse.data
    currentView.value = 'detail'
  } catch (error) {
    console.error('Erreur lors du chargement de l\'activité:', error)
  }
}

const editActivite = (activite) => {
  // TODO: This will be handled by the ActivityForm component
  // which is already included in ActivityList
  currentTab.value = 'list'
}

const goBack = () => {
  selectedActivite.value = null
  activiteTaches.value = []
}

const showList = () => {
  currentTab.value = 'list'
}

const viewTasks = (activiteId) => {
  // TODO: Navigate to tasks page filtered by activity
  console.log('View tasks for activity:', activiteId)
}

const createTask = () => {
  // TODO: Open task creation modal for this activity
  console.log('Create task for activity:', selectedActivite.value?.id)
}

// Set document title
onMounted(() => {
  document.title = 'Activités - Work Tracking'
})
</script>