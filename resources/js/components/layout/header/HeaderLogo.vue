<template>
  <router-link to="/" class="lg:hidden">
    <img class="dark:hidden" src="@images/logo/Logo-dark.jpg" alt="Logo" width="200" />
    <img class="hidden dark:block" src="@images/logo/Logo-dark.jpg" alt="Logo" />
  </router-link>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>
