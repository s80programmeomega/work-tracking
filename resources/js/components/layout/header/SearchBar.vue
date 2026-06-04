<!-- Palette de recherche globale — Cmd+K / Ctrl+K, gated par search.global -->
<template>
  <div v-if="canSearchGlobal" class="relative hidden lg:block">
    <!-- Déclencheur / champ de saisie -->
    <div class="relative">
      <button class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
        <svg v-if="!loading" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20">
          <path fill-rule="evenodd" clip-rule="evenodd"
            d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z"
            fill="" />
        </svg>
        <svg v-else class="h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </button>

      <input
        ref="inputRef"
        v-model="query"
        type="text"
        dusk="global-search-input"
        :placeholder="$t('search.placeholder')"
        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pl-12 pr-14 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-white/3 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 xl:w-107.5"
        @focus="onInputFocus"
        @blur="delayClose"
        @keydown.esc="close"
        @keydown.down.prevent="moveDown"
        @keydown.up.prevent="moveUp"
        @keydown.enter.prevent="selectFocused"
      />

      <button
        class="absolute right-2.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 px-1.75 py-[4.5px] text-xs -tracking-[0.2px] text-gray-500 dark:border-gray-800 dark:bg-white/3 dark:text-gray-400"
      >
        <span>⌘</span><span>K</span>
      </button>
    </div>

    <!-- Résultats — positionné relativement au champ de saisie -->
    <div
      v-if="open && (hasResults || query.length >= 2)"
      class="absolute left-0 top-full z-9999 mt-1 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900 xl:w-140"
      dusk="search-results"
    >
        <!-- Aucun résultat -->
        <div v-if="!hasResults && query.length >= 2 && !loading" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
          {{ $t('search.no_results', { q: query }) }}
        </div>

        <!-- Résultats groupés -->
        <div v-else class="max-h-120 overflow-y-auto py-2">
          <template v-for="(group, type) in results" :key="type">
            <div v-if="group.length">
              <p class="px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                {{ $t(`search.type_${type}`, type) }}
              </p>
              <button
                v-for="(item, idx) in group"
                :key="item.id"
                :dusk="`search-result-${type}`"
                :class="[
                  'flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition-colors',
                  focusedIdx === flatIndex(type, idx)
                    ? 'bg-brand-50 dark:bg-brand-900/20'
                    : 'hover:bg-gray-50 dark:hover:bg-gray-800',
                ]"
                @mousedown.prevent="navigate(item)"
              >
                <span class="shrink-0 text-base">{{ typeEmoji(type) }}</span>
                <div class="min-w-0 flex-1">
                  <!-- Supprimer les balises <mark> de highlight pour l'affichage texte brut -->
                  <p class="truncate font-medium text-gray-900 dark:text-white">{{ stripMark(item.label) }}</p>
                  <p v-if="item.excerpt" class="truncate text-xs text-gray-500 dark:text-gray-400">{{ stripMark(item.excerpt) }}</p>
                </div>
                <span v-if="item.meta?.statut" class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                  {{ item.meta.statut }}
                </span>
              </button>
            </div>
          </template>
        </div>

        <!-- Pied : lien "Voir tous les résultats" + raccourci clavier -->
        <div class="flex items-center justify-between border-t border-gray-100 px-4 py-2 dark:border-gray-800">
          <button
            @mousedown.prevent="seeAll"
            class="text-xs text-blue-600 hover:underline dark:text-blue-400"
          >
            {{ $t('search.see_all') }} "{{ query }}" →
          </button>
          <button @mousedown.prevent="close" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Esc</button>
        </div>
      </div>
    </div>

  <!-- Placeholder statique pour les non-managers -->
  <div v-else class="hidden lg:block">
    <div class="relative">
      <span class="absolute left-4 top-1/2 -translate-y-1/2">
        <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20">
          <path fill-rule="evenodd" clip-rule="evenodd"
            d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" fill="" />
        </svg>
      </span>
      <input
        type="text"
        :placeholder="$t('search.placeholder')"
        disabled
        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pl-12 pr-14 text-sm text-gray-400 opacity-50 cursor-not-allowed dark:border-gray-800 dark:bg-white/3 xl:w-107.5"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'
import { useAuthStore } from '@/stores/authStore'
import { useWorkspace } from '@/composables/useWorkspace'
import api from '@/api/axios'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { currentWorkspace } = useWorkspace()
const { canSearchGlobal } = useWorkspacePermissions(currentWorkspace)

// ── État ──────────────────────────────────────────────────────────────────────

const query = ref('')
const open = ref(false)
const loading = ref(false)
const results = ref({})
const inputRef = ref(null)
const focusedIdx = ref(-1)

// ── Keyboard global Cmd+K / Ctrl+K ───────────────────────────────────────────

const onGlobalKey = (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    if (!canSearchGlobal.value) return
    inputRef.value?.focus()
    open.value = true
  }
}

// ── Ouverture au focus (clic sur le champ) ────────────────────────────────────

const onInputFocus = () => {
  open.value = true
}

onMounted(() => window.addEventListener('keydown', onGlobalKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onGlobalKey))

// ── Recherche debounced ───────────────────────────────────────────────────────

let debounce = null

watch(query, (val) => {
  clearTimeout(debounce)
  focusedIdx.value = -1
  if (val.trim().length < 2) {
    results.value = {}
    return
  }
  debounce = setTimeout(doSearch, 400)
})

const doSearch = async () => {
  loading.value = true
  try {
    const res = await api.get('/search', {
      params: {
        q: query.value,
        workspace_id: currentWorkspace.value?.id,
      },
    })
    results.value = res.data.results ?? {}
  } catch {
    results.value = {}
  } finally {
    loading.value = false
  }
}

// ── Navigation clavier ────────────────────────────────────────────────────────

const flatItems = computed(() => {
  return Object.entries(results.value).flatMap(([type, items]) =>
    items.map((item) => ({ ...item, _type: type }))
  )
})

const hasResults = computed(() => flatItems.value.length > 0)

const flatIndex = (type, idx) => {
  let offset = 0
  for (const [t, items] of Object.entries(results.value)) {
    if (t === type) return offset + idx
    offset += items.length
  }
  return -1
}

const moveDown = () => {
  if (!hasResults.value) return
  focusedIdx.value = Math.min(focusedIdx.value + 1, flatItems.value.length - 1)
}

const moveUp = () => {
  focusedIdx.value = Math.max(focusedIdx.value - 1, -1)
}

const selectFocused = () => {
  const item = flatItems.value[focusedIdx.value]
  if (item) navigate(item)
}

// ── Navigation ────────────────────────────────────────────────────────────────

const navigate = (item) => {
  close()
  router.push(item.url)
}

// Ouvre la page de recherche complète avec le terme courant
const seeAll = () => {
  close()
  if (query.value.trim()) {
    router.push({ path: '/search', query: { q: query.value.trim() } })
  }
}

// ── Fermeture ─────────────────────────────────────────────────────────────────

const close = () => {
  open.value = false
  focusedIdx.value = -1
}

const delayClose = () => setTimeout(close, 150)

// ── Helpers d'affichage ───────────────────────────────────────────────────────

// Retirer les balises <mark> de Typesense pour l'affichage texte brut du palette
const stripMark = (text) => (text ?? '').replace(/<\/?mark>/gi, '')

const typeEmoji = (type) => {
  const map = { projets: '📁', activites: '🗂️', taches: '✅', documents: '📄', users: '👤' }
  return map[type] ?? '🔍'
}
</script>
