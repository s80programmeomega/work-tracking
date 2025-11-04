<!-- resources/js/layouts/AppLayout.vue -->
<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Votre contenu existant -->
    <slot />
    
    <!-- Toast Notifications -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import ToastNotifications from '@/components/common/ToastNotifications.vue'
</script>