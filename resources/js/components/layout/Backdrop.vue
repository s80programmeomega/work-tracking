<template>
  <div
    v-if="isMobileOpen"
    dusk="sidebar-backdrop"
    class="fixed inset-0 bg-gray-900/50 z-30 lg:hidden"
    @click="toggleMobileSidebar"
  ></div>
</template>

<script setup lang="ts">
import { useSidebar } from '@/composables/useSidebar'
const { toggleMobileSidebar, isMobileOpen } = useSidebar()
</script>
