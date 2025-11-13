<!-- resources/js/pages/Errors/FourZeroFour.vue - Version simplifiée -->
<template>
  <AdminLayout>
    <!-- Header minimal -->
    <div class="px-8 pt-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Page non trouvée
          </h1>
        </div>
        <div class="flex items-center gap-2">
          <button 
            @click="$router.back()"
            class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
          >
            ← Retour
          </button>
          <router-link 
            to="/"
            class="px-3 py-2 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400"
          >
            Accueil
          </router-link>
        </div>
      </div>
    </div>

    <!-- Contenu principal centré -->
    <div class="flex flex-col items-center justify-center flex-1 px-8 py-16">
      <div class="max-w-md text-center">
        <!-- Icône d'erreur -->
        <div class="mb-8">
          <div class="inline-flex items-center justify-center w-24 h-24 bg-red-100 rounded-full dark:bg-red-900/20">
            <ExclamationTriangleIcon class="w-12 h-12 text-red-600 dark:text-red-400" />
          </div>
        </div>

        <!-- Message -->
        <h2 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white">
          404 - Page introuvable
        </h2>
        
        <p class="mb-8 text-gray-600 dark:text-gray-400">
          La page que vous recherchez n'existe pas ou a été déplacée.
        </p>

        <!-- Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
          <button 
            @click="$router.back()"
            class="px-6 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Page précédente
          </button>
          <router-link 
            to="/"
            class="px-6 py-3 text-white bg-brand-600 rounded-lg hover:bg-brand-700"
          >
            Retour à l'accueil
          </router-link>
        </div>

        <!-- Lien de support -->
        <p class="mt-8 text-sm text-gray-500 dark:text-gray-400">
          Si vous pensez qu'il s'agit d'une erreur,{" "}
          <a 
            href="mailto:support@votreapp.com" 
            class="text-brand-600 hover:underline dark:text-brand-400"
          >
            contactez le support
          </a>
        </p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/layout/AdminLayout.vue'
</script>