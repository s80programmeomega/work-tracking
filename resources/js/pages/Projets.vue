<template>
  <div class="container mx-auto py-6">
    <!-- Navigation Tabs -->
    <Tabs v-model="activeTab" class="w-full">
      <TabsList class="grid w-full grid-cols-3">
        <TabsTrigger value="dashboard">Dashboard</TabsTrigger>
        <TabsTrigger value="list">Liste des projets</TabsTrigger>
        <TabsTrigger value="detail" :disabled="!selectedProjetId">Détail projet</TabsTrigger>
      </TabsList>

      <TabsContent value="dashboard" class="mt-6">
        <ProjetDashboard
          @view-all="activeTab = 'list'"
          @view-projet="viewProjet"
        />
      </TabsContent>

      <TabsContent value="list" class="mt-6">
        <ProjetList
          @view-projet="viewProjet"
        />
      </TabsContent>

      <TabsContent value="detail" class="mt-6">
        <ProjetDetail
          v-if="selectedProjetId"
          :projet-id="selectedProjetId"
          @back="backToList"
          @create-activity="createActivity"
          @view-activity="viewActivity"
        />
      </TabsContent>
    </Tabs>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabsList from '@/components/ui/tabs/TabsList.vue'
import TabsTrigger from '@/components/ui/tabs/TabsTrigger.vue'
import TabsContent from '@/components/ui/tabs/TabsContent.vue'

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