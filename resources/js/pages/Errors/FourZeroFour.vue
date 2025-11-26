<!-- Version simplifiée -->
<template>
  <AdminLayout>
    <div class="flex flex-col items-center justify-center min-h-screen px-8 py-16 text-center">
      <div class="max-w-md">
        <!-- Icône -->
        <div class="mb-6">
          <div class="inline-flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full dark:bg-orange-900/20">
            <ShieldExclamationIcon class="w-10 h-10 text-orange-600 dark:text-orange-400" />
          </div>
        </div>

        <!-- Message -->
        <h1 class="mb-4 text-2xl font-bold text-gray-900 dark:text-white">
          Accès non autorisé
        </h1>
        
        <p class="mb-8 text-gray-600 dark:text-gray-400">
          Vous n'avez pas l'autorisation d'accéder à cette page.
        </p>

        <!-- Actions -->
        <div class="flex flex-col gap-3">
          <button 
            @click="$router.back()"
            class="px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Retour
          </button>
          <router-link 
            to="/"
            class="px-5 py-2.5 text-white bg-brand-600 rounded-lg hover:bg-brand-700 text-center"
          >
            Page d'accueil
          </router-link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ShieldExclamationIcon } from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/layout/AdminLayout.vue'
</script>