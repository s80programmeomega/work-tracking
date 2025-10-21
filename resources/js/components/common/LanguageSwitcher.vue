<template>
  <div class="relative">
    <!-- Bouton du sélecteur -->
    <button
      @click="toggleDropdown"
      class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
    >
      <!-- Drapeau de la langue actuelle -->
      <span class="text-base">{{ currentLanguage.flag }}</span>
      <span class="hidden sm:block">{{ currentLanguage.name }}</span>
      <svg
        class="w-4 h-4"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>

    <!-- Menu déroulant -->
    <div
      v-if="isOpen"
      class="absolute right-0 z-50 w-48 mt-2 origin-top-right bg-white rounded-lg shadow-lg dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-hidden"
    >
      <div class="py-1">
        <button
          v-for="lang in languages"
          :key="lang.code"
          @click="changeLanguage(lang.code)"
          :class="[
            'w-full px-4 py-2 text-sm text-left transition-colors flex items-center gap-3',
            currentLanguage.code === lang.code
              ? 'bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300'
              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
          ]"
        >
          <span class="text-base">{{ lang.flag }}</span>
          <span>{{ lang.name }}</span>
          <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ lang.nativeName }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/authStore';

const { locale } = useI18n();
const authStore = useAuthStore();

// État du menu déroulant
const isOpen = ref(false);

// Liste des langues supportées
const languages = [
  {
    code: 'fr',
    name: 'Français',
    nativeName: 'Français',
    flag: '🇫🇷'
  },
  {
    code: 'en',
    name: 'English',
    nativeName: 'English',
    flag: '🇺🇸'
  }
];

// Langue actuelle
const currentLanguage = computed(() => {
  return languages.find(lang => lang.code === locale.value) || languages[0];
});

/**
 * Basculer l'état du menu déroulant
 */
const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

/**
 * Changer la langue
 */
const changeLanguage = async (languageCode: string) => {
  try {
    // Utilise la méthode du store qui gère tout
    await authStore.setLanguage(languageCode);
    isOpen.value = false;
  } catch (error) {
    console.error('Erreur lors du changement de langue:', error);
  }
};

/**
 * Fermer le menu en cliquant à l'extérieur
 */
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.relative')) {
    isOpen.value = false;
  }
};

// Écouteur d'événements
onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>