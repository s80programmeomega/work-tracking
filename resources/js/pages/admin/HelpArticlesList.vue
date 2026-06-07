<!-- Centre d'aide — gestion : liste des articles (brouillons inclus). -->
<template>
  <AdminLayout>
    <div class="space-y-5">

      <!-- En-tête -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('help.admin.title') }}</h1>
        <router-link
          :to="{ name: 'admin.help.articles.create' }"
          class="rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          {{ $t('help.admin.new_article') }}
        </router-link>
      </div>

      <!-- Filtres + recherche -->
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="f in statusFilters"
          :key="f.value"
          @click="setStatus(f.value)"
          class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
          :class="status === f.value
            ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
            : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'"
        >
          {{ f.label }}
        </button>
        <input
          v-model="search"
          type="text"
          :placeholder="$t('help.search_placeholder')"
          class="ml-auto w-56 rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          @input="onSearchInput"
        />
      </div>

      <!-- Tableau -->
      <div class="overflow-hidden rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3">
        <p v-if="loading" class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ $t('help.loading') }}</p>
        <p v-else-if="articles.length === 0" class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ $t('help.admin.no_articles') }}</p>

        <table v-else class="w-full text-sm">
          <thead class="border-b border-gray-200 text-left text-xs uppercase text-gray-400 dark:border-gray-800">
            <tr>
              <th class="px-4 py-3">{{ $t('help.admin.col_title') }}</th>
              <th class="px-4 py-3">{{ $t('help.admin.col_category') }}</th>
              <th class="px-4 py-3">{{ $t('help.admin.col_status') }}</th>
              <th class="px-4 py-3">{{ $t('help.admin.col_views') }}</th>
              <th class="px-4 py-3 text-right">{{ $t('help.admin.col_actions') }}</th>
            </tr>
          </thead>
          <tbody ref="rowsRef" class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr v-for="article in articles" :key="article.id" class="stagger-item">
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ article.titre_fr }}</td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ article.category?.nom_fr ?? '—' }}</td>
              <td class="px-4 py-3">
                <span
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="article.published_at
                    ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                >
                  {{ article.published_at ? $t('help.admin.status_published') : $t('help.admin.status_draft') }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ article.views_count ?? 0 }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="togglePublish(article)"
                    class="rounded-3 border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                  >
                    {{ article.published_at ? $t('help.admin.unpublish') : $t('help.admin.publish') }}
                  </button>
                  <router-link
                    :to="{ name: 'admin.help.articles.edit', params: { id: article.id } }"
                    class="rounded-3 border border-blue-500 px-2 py-1 text-xs text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                  >
                    {{ $t('help.admin.edit') }}
                  </router-link>
                  <button
                    @click="remove(article)"
                    class="rounded-3 border border-red-400 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                  >
                    {{ $t('help.admin.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between border-t border-gray-100 px-4 py-3 dark:border-gray-800">
          <button :disabled="meta.current_page <= 1" @click="fetchArticles(meta.current_page - 1)" class="rounded-3 border border-gray-300 px-3 py-1.5 text-xs disabled:opacity-40 dark:border-gray-700 dark:text-gray-300">&larr;</button>
          <span class="text-xs text-gray-500 dark:text-gray-400">{{ meta.current_page }} / {{ meta.last_page }}</span>
          <button :disabled="meta.current_page >= meta.last_page" @click="fetchArticles(meta.current_page + 1)" class="rounded-3 border border-gray-300 px-3 py-1.5 text-xs disabled:opacity-40 dark:border-gray-700 dark:text-gray-300">&rarr;</button>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const { t } = useI18n()
const { staggerRef: rowsRef, applyStagger } = useStagger(40)

const articles = ref([])
const meta = ref(null)
const loading = ref(false)
const status = ref('all')
const search = ref('')
let searchTimer = null

const statusFilters = [
  { value: 'all', label: t('help.admin.filter_all') },
  { value: 'published', label: t('help.admin.filter_published') },
  { value: 'draft', label: t('help.admin.filter_draft') },
]

const fetchArticles = async (page = 1) => {
  loading.value = true
  try {
    const params = { page }
    if (status.value !== 'all') params.status = status.value
    if (search.value.trim()) params.q = search.value.trim()
    const { data } = await api.get('/admin/help/articles', { params })
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

const setStatus = (value) => {
  status.value = value
  fetchArticles()
}

const onSearchInput = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchArticles(), 300)
}

const togglePublish = async (article) => {
  const action = article.published_at ? 'unpublish' : 'publish'
  await api.post(`/admin/help/articles/${article.id}/${action}`)
  fetchArticles(meta.value?.current_page ?? 1)
}

const remove = async (article) => {
  if (!window.confirm(t('help.admin.confirm_delete'))) return
  await api.delete(`/admin/help/articles/${article.id}`)
  fetchArticles(meta.value?.current_page ?? 1)
}

onMounted(() => fetchArticles())
</script>
