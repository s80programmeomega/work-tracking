<template>
  <router-link to="/" class="lg:hidden">
    <img :src="Logo" alt="Logo" width="200" />
</router-link>

</template>

<script setup>
import { RouterLink } from 'vue-router' 

const Logo = new URL('@/assets/images/logo/logo-transparent.png', import.meta.url).href
</script>
