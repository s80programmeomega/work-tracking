<!-- Centre d'aide — page article : HTML assaini rendu en prose + articles liés. -->
<template>
  <AdminLayout>
    <div class="space-y-5">

      <!-- Fil d'Ariane -->
      <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <router-link :to="{ name: 'help.index' }" class="hover:text-blue-600">{{ $t('help.title') }}</router-link>
        <template v-if="article?.category">
          <span>/</span>
          <router-link
            :to="{ name: 'help.category', params: { slug: article.category.slug } }"
            class="hover:text-blue-600"
          >
            {{ localized(article.category, 'nom') }}
          </router-link>
        </template>
      </div>

      <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ $t('help.loading') }}</p>

      <div v-else-if="article" class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- Contenu principal -->
        <article class="lg:col-span-2 rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3 sm:p-8">
          <div class="flex items-start justify-between gap-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ localized(article, 'titre') }}</h1>

            <!-- Sélecteur de langue de l'article (indépendant de la langue de l'UI). -->
            <div class="flex shrink-0 overflow-hidden rounded-3 border border-gray-300 text-xs font-medium dark:border-gray-700">
              <button
                v-for="lang in ['fr', 'en']"
                :key="lang"
                :dusk="`help-article-lang-${lang}`"
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

          <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-400">
            <span>{{ $t('help.views', { count: article.views_count ?? 0 }) }}</span>
            <span v-if="article.updated_at">{{ $t('help.updated_on', { date: formatDate(article.updated_at) }) }}</span>
          </div>

          <!-- Corps : HTML déjà assaini côté serveur (mews/purifier). -->
          <div
            class="prose prose-sm mt-6 max-w-none dark:prose-invert prose-a:text-blue-600 prose-img:rounded-3"
            v-html="localized(article, 'body')"
          ></div>
        </article>

        <!-- Articles liés -->
        <aside v-if="related.length" class="lg:col-span-1">
          <div class="rounded-3 border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/3">
            <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $t('help.related_articles') }}</h2>
            <ul ref="relatedRef" class="space-y-2">
              <li v-for="rel in related" :key="rel.id" class="stagger-item">
                <router-link
                  :to="{ name: 'help.article', params: { slug: rel.slug } }"
                  class="block rounded-3 px-2 py-1.5 text-sm text-gray-600 hover:bg-gray-50 hover:text-blue-600 dark:text-gray-300 dark:hover:bg-gray-800/50"
                >
                  {{ localized(rel, 'titre') }}
                </router-link>
              </li>
            </ul>
          </div>
        </aside>

      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const route = useRoute()
const { locale } = useI18n()
const { staggerRef: relatedRef, applyStagger } = useStagger(40)

const article = ref(null)
const related = ref([])
const loading = ref(false)

// Langue d'affichage de l'article — initialisée sur la langue de l'UI mais
// modifiable indépendamment par le lecteur via le sélecteur FR|EN.
const displayLang = ref(locale.value)

const localized = (obj, field) => {
  if (!obj) return ''
  const key = `${field}_${displayLang.value}`
  return obj[key] ?? obj[`${field}_fr`] ?? ''
}

const formatDate = (iso) => {
  try {
    return new Date(iso).toLocaleDateString(locale.value)
  } catch {
    return iso
  }
}

const fetchArticle = async () => {
  loading.value = true
  try {
    const { data } = await api.get(`/help/articles/${route.params.slug}`)
    article.value = data.article
    related.value = data.related ?? []
    await applyStagger()
  } finally {
    loading.value = false
  }
}

// Recharge si le slug change (navigation entre articles liés).
watch(() => route.params.slug, () => fetchArticle())

onMounted(fetchArticle)
</script>
