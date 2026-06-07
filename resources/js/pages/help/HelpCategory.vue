<!-- Centre d'aide — page catégorie : en-tête + liste d'articles paginée. -->
<template>
  <AdminLayout>
    <div class="space-y-5">

      <!-- Fil d'Ariane -->
      <router-link
        :to="{ name: 'help.index' }"
        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-600 dark:text-gray-400"
      >
        <ChevronLeftIcon class="h-4 w-4" />
        {{ $t('help.back_to_help') }}
      </router-link>

      <!-- En-tête catégorie -->
      <div v-if="category" class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-3 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
              <i :class="['fas', category.icon || 'fa-book', 'text-lg']"></i>
            </span>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ localized(category, 'nom') }}</h1>
              <p v-if="localized(category, 'description')" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                {{ localized(category, 'description') }}
              </p>
            </div>
          </div>

          <!-- Sélecteur de langue d'affichage (indépendant de la langue de l'UI). -->
          <div class="flex shrink-0 overflow-hidden rounded-3 border border-gray-300 text-xs font-medium dark:border-gray-700">
            <button
              v-for="lang in ['fr', 'en']"
              :key="lang"
              @click="displayLang = lang"
              class="px-3 py-1.5 transition-colors"
              :class="displayLang === lang
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
            >
              {{ lang.toUpperCase() }}
            </button>
          </div>
        </div>
      </div>

      <!-- Liste d'articles -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3">
        <p v-if="loading" class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ $t('help.loading') }}</p>
        <p v-else-if="articles.length === 0" class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ $t('help.no_articles') }}</p>

        <ul v-else ref="articlesRef" class="divide-y divide-gray-100 dark:divide-gray-800">
          <li v-for="article in articles" :key="article.id" class="stagger-item">
            <router-link
              :to="{ name: 'help.article', params: { slug: article.slug } }"
              class="flex items-center justify-between gap-3 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50"
            >
              <span class="font-medium text-gray-900 dark:text-white">{{ localized(article, 'titre') }}</span>
              <ChevronRightIcon class="h-4 w-4 shrink-0 text-gray-400" />
            </router-link>
          </li>
        </ul>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between border-t border-gray-100 px-6 py-3 dark:border-gray-800">
          <button
            :disabled="meta.current_page <= 1"
            @click="changePage(meta.current_page - 1)"
            class="rounded-3 border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 disabled:opacity-40 dark:border-gray-700 dark:text-gray-300"
          >
            &larr;
          </button>
          <span class="text-xs text-gray-500 dark:text-gray-400">{{ meta.current_page }} / {{ meta.last_page }}</span>
          <button
            :disabled="meta.current_page >= meta.last_page"
            @click="changePage(meta.current_page + 1)"
            class="rounded-3 border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 disabled:opacity-40 dark:border-gray-700 dark:text-gray-300"
          >
            &rarr;
          </button>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const route = useRoute()
const { locale } = useI18n()
const { staggerRef: articlesRef, applyStagger } = useStagger(40)

const category = ref(null)
const articles = ref([])
const meta = ref(null)
const loading = ref(false)

// Langue d'affichage — initialisée sur la langue de l'UI, modifiable via FR|EN.
const displayLang = ref(locale.value)

const localized = (obj, field) => {
  if (!obj) return ''
  const key = `${field}_${displayLang.value}`
  return obj[key] ?? obj[`${field}_fr`] ?? ''
}

const fetchCategory = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await api.get(`/help/categories/${route.params.slug}`, { params: { page } })
    category.value = data.category
    articles.value = data.articles?.data ?? []
    meta.value = {
      current_page: data.articles?.current_page ?? 1,
      last_page: data.articles?.last_page ?? 1,
    }
    await applyStagger()
  } finally {
    loading.value = false
  }
}

const changePage = (page) => fetchCategory(page)

// Recharge si le slug change (navigation entre catégories).
watch(() => route.params.slug, () => fetchCategory())

onMounted(() => fetchCategory())
</script>
