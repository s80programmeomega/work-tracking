<!-- Centre d'aide — page d'accueil : recherche + grille de catégories. -->
<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- En-tête + recherche -->
      <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 dusk="help-index-title" class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('help.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('help.subtitle') }}</p>
          </div>
          <router-link
            v-if="canManageHelpArticles"
            :to="{ name: 'admin.help.articles' }"
            class="shrink-0 rounded-3 border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
          >
            {{ $t('help.manage') }}
          </router-link>
        </div>

        <div class="relative mt-4">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            dusk="help-search-input"
            :placeholder="$t('help.search_placeholder')"
            class="w-full rounded-3 border border-gray-300 bg-white py-3 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            @input="onSearchInput"
          />
        </div>
      </div>

      <!-- Résultats de recherche -->
      <div v-if="searchQuery.trim().length >= 2" class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
          {{ $t('help.search_results_for', { q: searchQuery.trim() }) }}
        </h2>

        <p v-if="searchLoading" class="text-sm text-gray-500 dark:text-gray-400">{{ $t('help.loading') }}</p>
        <p v-else-if="searchResults.length === 0" class="text-sm text-gray-500 dark:text-gray-400">{{ $t('help.no_results') }}</p>

        <ul v-else ref="resultsRef" class="divide-y divide-gray-100 dark:divide-gray-800">
          <li v-for="article in searchResults" :key="article.id" class="stagger-item py-3">
            <router-link
              :to="{ name: 'help.article', params: { slug: article.slug } }"
              class="block rounded-3 px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-800/50"
            >
              <p class="font-medium text-gray-900 dark:text-white">{{ localized(article, 'titre') }}</p>
              <p v-if="article.category" class="mt-0.5 text-xs text-gray-400">{{ localized(article.category, 'nom') }}</p>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Grille de catégories -->
      <div v-else>
        <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ $t('help.loading') }}</p>
        <p v-else-if="categories.length === 0" class="rounded-3 border border-gray-200 bg-white p-6 text-sm text-gray-500 dark:border-gray-800 dark:bg-white/3 dark:text-gray-400">
          {{ $t('help.no_categories') }}
        </p>

        <div v-else ref="categoriesRef" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <router-link
            v-for="category in categories"
            :key="category.id"
            :to="{ name: 'help.category', params: { slug: category.slug } }"
            class="stagger-item group flex flex-col rounded-3 border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-white/3"
          >
            <div class="flex items-center gap-3">
              <span class="flex h-10 w-10 items-center justify-center rounded-3 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                <i :class="['fas', category.icon || 'fa-book']"></i>
              </span>
              <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 dark:text-white">
                {{ localized(category, 'nom') }}
              </h3>
            </div>
            <p v-if="localized(category, 'description')" class="mt-3 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">
              {{ localized(category, 'description') }}
            </p>
            <span class="mt-auto pt-3 text-xs text-gray-400">
              {{ $t('help.article_count', { count: category.articles_count ?? 0 }) }}
            </span>
          </router-link>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import { useWorkspace } from '@/composables/useWorkspace'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const { locale } = useI18n()
const { currentWorkspace } = useWorkspace()
const { canManageHelpArticles } = useWorkspacePermissions(currentWorkspace)
const { staggerRef: categoriesRef, applyStagger: applyCategoriesStagger } = useStagger(50)
const { staggerRef: resultsRef, applyStagger: applyResultsStagger } = useStagger(40)

const categories = ref([])
const loading = ref(false)

const searchQuery = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
let searchTimer = null

// Renvoie le champ localisé (champ_fr / champ_en) selon la langue active.
const localized = (obj, field) => {
  if (!obj) return ''
  const key = `${field}_${locale.value}`
  return obj[key] ?? obj[`${field}_fr`] ?? ''
}

const fetchCategories = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/help/categories')
    categories.value = data.categories ?? []
    await applyCategoriesStagger()
  } finally {
    loading.value = false
  }
}

// Recherche FULLTEXT debouncée (300 ms).
const onSearchInput = () => {
  clearTimeout(searchTimer)
  const q = searchQuery.value.trim()
  if (q.length < 2) {
    searchResults.value = []
    return
  }
  searchTimer = setTimeout(() => runSearch(q), 300)
}

const runSearch = async (q) => {
  searchLoading.value = true
  try {
    const { data } = await api.get('/help/articles', { params: { q } })
    searchResults.value = data.articles?.data ?? []
    await applyResultsStagger()
  } finally {
    searchLoading.value = false
  }
}

onMounted(fetchCategories)
</script>
