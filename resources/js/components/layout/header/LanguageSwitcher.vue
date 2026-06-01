<!-- resources/js/components/layout/header/LanguageSwitcher.vue -->
<template>
  <button
    class="relative flex items-center justify-center gap-1.5 text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white select-none text-base"
    :title="locale === 'fr' ? 'Switch to English' : 'Passer en français'"
    @click="toggleLocale"
  >
    {{ locale === 'fr' ? '🇺🇸' : '🇫🇷' }}
  </button>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/authStore';

const { locale } = useI18n();
const authStore = useAuthStore();

const toggleLocale = () => {
  const next = locale.value === 'fr' ? 'en' : 'fr';
  authStore.setLanguage(next);
};
</script>
