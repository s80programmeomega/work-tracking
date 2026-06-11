<!-- resources\js\pages\Errors\FourZeroFour.vue -->
<template>
  <AdminLayout>
    <div class="flex flex-col items-center justify-center min-h-[70vh] px-8 py-16 text-center">
      <div class="max-w-lg">
        <!-- Icône contextuelle -->
        <div class="mb-6">
          <div
            class="inline-flex items-center justify-center w-20 h-20 rounded-full"
            :class="iconBg"
          >
            <component :is="icon" class="w-10 h-10" :class="iconColor" />
          </div>
        </div>

        <!-- Code d'erreur -->
        <p class="mb-2 text-5xl font-extrabold tracking-tight" :class="codeColor">
          {{ errorCode }}
        </p>

        <!-- Titre -->
        <h1 class="mb-3 text-2xl font-bold text-gray-900 dark:text-white">
          {{ title }}
        </h1>

        <!-- Description contextuelle -->
        <p class="mb-2 text-gray-600 dark:text-gray-400">
          {{ description }}
        </p>

        <!-- Hint supplémentaire si présent -->
        <p v-if="hint" class="mb-8 text-sm text-gray-400 dark:text-gray-500 italic">
          {{ hint }}
        </p>
        <div v-else class="mb-8" />

        <!-- Actions -->
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
          <button
            @click="$router.back()"
            class="px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-3 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
          >
            {{ $t('error_page.btn_back') }}
          </button>
          <router-link
            to="/"
            class="px-5 py-2.5 text-white bg-brand-600 rounded-3 hover:bg-brand-700 text-center transition-colors"
          >
            {{ $t('error_page.btn_home') }}
          </router-link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ShieldExclamationIcon,
  MagnifyingGlassIcon,
  ExclamationTriangleIcon,
  ServerStackIcon,
  LockClosedIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const route = useRoute()
const { t } = useI18n()

const code = computed(() => String(route.query.code ?? route.meta?.errorCode ?? '404'))
const customMessage = computed(() => route.query.message ? String(route.query.message) : null)
const fromPath = computed(() => route.query.from ? String(route.query.from) : null)

const errorConfig = computed(() => {
  switch (code.value) {
    case '401':
      return {
        icon: LockClosedIcon,
        iconBg: 'bg-yellow-100 dark:bg-yellow-900/20',
        iconColor: 'text-yellow-600 dark:text-yellow-400',
        codeColor: 'text-yellow-500',
        title: t('error_page.401_title'),
        description: t('error_page.401_desc'),
        hint: fromPath.value ? t('error_page.401_hint', { path: fromPath.value }) : null,
      }
    case '403':
      return {
        icon: ShieldExclamationIcon,
        iconBg: 'bg-orange-100 dark:bg-orange-900/20',
        iconColor: 'text-orange-600 dark:text-orange-400',
        codeColor: 'text-orange-500',
        title: t('error_page.403_title'),
        description: t('error_page.403_desc'),
        hint: t('error_page.403_hint'),
      }
    case '404':
      return {
        icon: MagnifyingGlassIcon,
        iconBg: 'bg-blue-100 dark:bg-blue-900/20',
        iconColor: 'text-blue-600 dark:text-blue-400',
        codeColor: 'text-blue-500',
        title: t('error_page.404_title'),
        description: fromPath.value
          ? t('error_page.404_desc_path', { path: fromPath.value })
          : t('error_page.404_desc'),
        hint: t('error_page.404_hint'),
      }
    case '419':
      return {
        icon: ClockIcon,
        iconBg: 'bg-purple-100 dark:bg-purple-900/20',
        iconColor: 'text-purple-600 dark:text-purple-400',
        codeColor: 'text-purple-500',
        title: t('error_page.419_title'),
        description: t('error_page.419_desc'),
        hint: t('error_page.419_hint'),
      }
    case '500':
      return {
        icon: ServerStackIcon,
        iconBg: 'bg-red-100 dark:bg-red-900/20',
        iconColor: 'text-red-600 dark:text-red-400',
        codeColor: 'text-red-500',
        title: t('error_page.500_title'),
        description: t('error_page.500_desc'),
        hint: t('error_page.500_hint'),
      }
    default:
      return {
        icon: ExclamationTriangleIcon,
        iconBg: 'bg-gray-100 dark:bg-gray-800',
        iconColor: 'text-gray-600 dark:text-gray-400',
        codeColor: 'text-gray-500',
        title: t('error_page.generic_title'),
        description: t('error_page.generic_desc'),
        hint: null,
      }
  }
})

const icon = computed(() => errorConfig.value.icon)
const iconBg = computed(() => errorConfig.value.iconBg)
const iconColor = computed(() => errorConfig.value.iconColor)
const codeColor = computed(() => errorConfig.value.codeColor)
const errorCode = computed(() => code.value)
const title = computed(() => customMessage.value ?? errorConfig.value.title)
const description = computed(() => errorConfig.value.description)
const hint = computed(() => errorConfig.value.hint)
</script>
