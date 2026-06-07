<!-- Centre d'aide — gestion : création / édition d'un article. -->
<template>
  <AdminLayout>
    <div class="mx-auto max-w-4xl space-y-5">

      <!-- Fil d'Ariane -->
      <router-link
        :to="{ name: 'admin.help.articles' }"
        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-600 dark:text-gray-400"
      >
        <ChevronLeftIcon class="h-4 w-4" />
        {{ $t('help.admin.title') }}
      </router-link>

      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ isEdit ? $t('help.admin.edit_article') : $t('help.admin.create_article') }}
      </h1>

      <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ $t('help.loading') }}</p>

      <form v-else class="space-y-5" @submit.prevent="save">
        <!-- Catégorie + slug -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.field_category') }}</label>
            <div class="flex gap-2">
              <select v-model="form.category_id" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="" disabled>{{ $t('help.admin.select_category') }}</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.nom_fr }}</option>
              </select>
              <button
                v-if="canManageHelpCategories"
                type="button"
                @click="categoryModalOpen = true"
                :title="$t('help.admin.new_category')"
                class="shrink-0 rounded-3 border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
              >
                +
              </button>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.field_slug') }}</label>
            <input v-model="form.slug" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
          </div>
        </div>

        <!-- Titres -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.field_title_fr') }}</label>
            <input v-model="form.titre_fr" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.field_title_en') }}</label>
            <input v-model="form.titre_en" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
          </div>
        </div>

        <!-- Corps FR -->
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.field_body_fr') }}</label>
          <HelpArticleEditor v-model="form.body_fr" :article-id="articleId" />
        </div>

        <!-- Corps EN -->
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.field_body_en') }}</label>
          <HelpArticleEditor v-model="form.body_en" :article-id="articleId" />
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
          <router-link :to="{ name: 'admin.help.articles' }" class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
            {{ $t('help.admin.cancel') }}
          </router-link>
          <button type="submit" :disabled="saving" class="rounded-3 bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
            {{ isEdit ? $t('help.admin.save') : $t('help.admin.save_draft') }}
          </button>
        </div>
      </form>

      <!-- Modale de création rapide d'une catégorie. -->
      <HelpCategoryModal
        v-if="categoryModalOpen"
        :category="null"
        @saved="onCategoryCreated"
        @close="categoryModalOpen = false"
      />

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronLeftIcon } from '@heroicons/vue/24/outline'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import HelpArticleEditor from '@/components/help/HelpArticleEditor.vue'
import HelpCategoryModal from '@/components/help/HelpCategoryModal.vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const { currentWorkspace } = useWorkspace()
const { canManageHelpCategories } = useWorkspacePermissions(currentWorkspace)

const isEdit = computed(() => !!route.params.id)
const articleId = ref(route.params.id ? Number(route.params.id) : null)

const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const categoryModalOpen = ref(false)

// Après création inline d'une catégorie : on recharge la liste et on la sélectionne.
const onCategoryCreated = async (category) => {
  categoryModalOpen.value = false
  await fetchCategories()
  if (category?.id) {
    form.value.category_id = category.id
  }
}

const form = ref({
  category_id: '',
  slug: '',
  titre_fr: '',
  titre_en: '',
  body_fr: '',
  body_en: '',
})

const fetchCategories = async () => {
  // Catégories admin (brouillons inclus) pour le sélecteur.
  const { data } = await api.get('/admin/help/categories')
  categories.value = data.categories ?? []
}

const fetchArticle = async () => {
  const { data } = await api.get(`/admin/help/articles/${articleId.value}`)
  const a = data.article
  form.value = {
    category_id: a.category_id,
    slug: a.slug,
    titre_fr: a.titre_fr,
    titre_en: a.titre_en,
    body_fr: a.body_fr,
    body_en: a.body_en,
  }
}

const save = async () => {
  saving.value = true
  error.value = null
  try {
    if (isEdit.value) {
      await api.put(`/admin/help/articles/${articleId.value}`, form.value)
    } else {
      const { data } = await api.post('/admin/help/articles', form.value)
      // Bascule en mode édition pour permettre l'upload d'images sur le nouvel article.
      articleId.value = data.article.id
      router.replace({ name: 'admin.help.articles.edit', params: { id: articleId.value } })
    }
    router.push({ name: 'admin.help.articles' })
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Erreur lors de l\'enregistrement.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await fetchCategories()
    if (isEdit.value) await fetchArticle()
  } finally {
    loading.value = false
  }
})
</script>
