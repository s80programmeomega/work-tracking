<template>
  <router-link to="/" class="lg:hidden">
    <img
        class="dark:hidden"
        :src="LogoLight"
        alt="Logo"
        width="200"
    />

    <img
        class="hidden dark:block"
        :src="LogoDark"
        alt="Logo"
    />
</router-link>

</template>

<script setup>
import { RouterLink } from 'vue-router' 

const LogoDark = new URL('@/assets/images/logo/Logo-dark.jpg', import.meta.url).href
const LogoLight = new URL('@/assets/images/logo/Logo.png', import.meta.url).href
</script>
