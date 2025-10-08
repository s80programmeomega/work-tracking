<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Gestion des Activités'" />

    <div class="rounded-2xl border border-gray-200 bg-white p-7.5 shadow-default dark:border-gray-700 dark:bg-gray-800 xl:p-12.5">
      <ActiviteList />
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ActiviteList from '@/components/activites/ActiviteList.vue'
</script>