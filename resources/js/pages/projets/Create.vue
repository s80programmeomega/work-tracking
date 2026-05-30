<template>
  <AdminLayout>
    <ProjetForm
      :projet="null"
      @close="goBack"
      @saved="onSaved"
    />
  </AdminLayout>
</template>

<script setup>
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ProjetForm from '@/components/projets/ProjetForm.vue'

const router = useRouter()

const goBack = () => {
  if (window.history.length > 2) {
    router.back()
  } else {
    router.push({ name: 'projets.my' })
  }
}

const onSaved = () => {
  router.push({ name: 'projets.my' })
}
</script>
