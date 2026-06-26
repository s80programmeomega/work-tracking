<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
    <!-- Barre supérieure : logo à gauche, contrôles à droite -->
    <header class="shrink-0 w-full border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-3 flex items-center justify-between">
      <img
        width="120"
        height="108"
        :src="LogoDark"
        alt="Uptiimum Work Tracking"
        class="rounded-3"
      />
      <div class="flex items-center gap-2">
        <LanguageSwitcher />
        <ThemeToggler />
        <UserMenu />
      </div>
    </header>

    <!-- Titre centré sous la barre -->
    <div class="text-center pt-8 pb-4 px-4">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ $t('workspace_picker.title') }}
      </h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t('workspace_picker.subtitle') }}
      </p>
    </div>

    <!-- Barre d'action : recherche + bouton nouveau workspace -->
    <div v-if="!loading && workspaces.length > 0" class="w-full max-w-5xl mx-auto px-4 mb-4 flex items-center gap-3">
      <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          dusk="picker-search-input"
          type="text"
          :placeholder="$t('workspace_picker.search_placeholder')"
          class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>
      <button
        dusk="picker-create-btn"
        @click="router.push({ name: 'workspaces.create' })"
        class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-3 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('workspace_picker.new_workspace') }}
      </button>
    </div>

    <!-- Contenu principal -->
    <main class="flex-1 w-full max-w-5xl mx-auto px-4 pb-12">

      <!-- Chargement -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto mb-3"></div>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('workspace_picker.loading') }}</p>
        </div>
      </div>

      <!-- Aucun workspace -->
      <div v-else-if="workspaces.length === 0" dusk="picker-empty-state" class="text-center py-20">
        <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('workspace_picker.no_workspaces') }}</p>
        <button
          @click="router.push({ name: 'workspaces.create' })"
          class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-3 transition-colors"
        >
          {{ $t('workspace_picker.create_btn') }}
        </button>
      </div>

      <template v-else>
        <!-- Bannière des statistiques agrégées du compte -->
        <div ref="statStaggerRef" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
          <div dusk="stat-total-workspaces" class="stagger-item bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded-3">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-900 dark:text-white">{{ accountStats.total }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('workspace_picker.stat_workspaces') }}</p>
            </div>
          </div>

          <div dusk="stat-total-projects" class="stagger-item bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
            <div class="p-2 bg-green-100 dark:bg-green-900/40 rounded-3">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-900 dark:text-white">{{ accountStats.totalProjects }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('workspace_picker.stat_projects') }}</p>
            </div>
          </div>

          <div dusk="stat-total-members" class="stagger-item bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900/40 rounded-3">
              <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.67 3.913a6 6 0 01-1.41 2.614" />
              </svg>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-900 dark:text-white">{{ accountStats.totalMembers }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('workspace_picker.stat_members') }}</p>
            </div>
          </div>

          <div dusk="stat-active" class="stagger-item bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900/40 rounded-3">
              <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-900 dark:text-white">{{ accountStats.active }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('workspace_picker.stat_active') }}</p>
            </div>
          </div>
        </div>

        <!-- Note explicative sur la portée des statistiques -->
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-6 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ $t('workspace_picker.stats_owner_note') }}
        </p>

        <!-- Aucun résultat de recherche -->
        <div v-if="filteredWorkspaces.length === 0" class="text-center py-12">
          <p class="text-gray-500 dark:text-gray-400 text-sm">
            {{ $t('workspace_picker.no_workspaces') }}
          </p>
        </div>

        <!-- Grille des cartes workspace -->
        <div
          v-else
          ref="staggerRef"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"
        >
          <div
            v-for="workspace in filteredWorkspaces"
            :key="workspace.id"
            :dusk="`workspace-card-${workspace.id}`"
            class="stagger-item group relative bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition-all duration-200 cursor-pointer overflow-hidden"
            @click="select(workspace)"
          >
            <!-- Bande colorée en haut — couleur déterministe par workspace, atténuée si inactif -->
            <div
              class="absolute top-0 left-0 w-full h-1.5 transition-opacity"
              :class="[cardAccentClass(workspace.id), workspace.is_active ? 'opacity-100' : 'opacity-30']"
            ></div>

            <!-- Bouton paramètres visible au hover -->
            <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity z-10">
              <button
                :dusk="`workspace-settings-btn-${workspace.id}`"
                :title="$t('workspace_picker.settings_tooltip')"
                class="w-7 h-7 rounded-md bg-white/90 dark:bg-gray-700/90 border border-gray-200 dark:border-gray-600 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                @click.stop="openSettings(workspace)"
              >
                <svg class="w-3.5 h-3.5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>

            <div class="p-5 pt-6">
              <!-- Logo + nom du workspace -->
              <div class="flex items-start gap-4 mb-4">
                <div class="shrink-0">
                  <div v-if="workspace.logo_url" class="w-14 h-14 rounded-3 overflow-hidden border border-gray-200 dark:border-gray-600">
                    <img :src="workspace.logo_url" :alt="workspace.nom" class="w-full h-full object-cover" />
                  </div>
                  <div
                    v-else
                    class="w-14 h-14 rounded-3 flex items-center justify-center"
                    :class="cardAvatarClass(workspace.id)"
                  >
                    <span class="text-white font-bold text-lg">{{ getInitials(workspace.nom) }}</span>
                  </div>
                </div>

                <div class="flex-1 min-w-0">
                  <h2 class="text-base font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ workspace.nom }}
                  </h2>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ workspace.code }}</p>

                  <!-- Badges rôle et statut -->
                  <div class="flex flex-wrap items-center gap-1.5 mt-2">
                    <span
                      v-if="workspace.user_role === 'owner'"
                      dusk="owner-badge"
                      class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                    >
                      {{ $t('workspace_picker.badge_owner') }}
                    </span>
                    <span
                      v-else-if="workspace.user_role"
                      dusk="role-badge"
                      class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 capitalize"
                    >
                      {{ workspace.user_role }}
                    </span>

                    <span
                      :class="[
                        'px-2 py-0.5 text-xs font-medium rounded-full',
                        workspace.is_active
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                          : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                      ]"
                    >
                      {{ workspace.is_active ? $t('workspace_picker.active') : $t('workspace_picker.inactive') }}
                    </span>

                    <SubscriptionBadge
                      v-if="workspace.subscription_mode && workspace.user_role === 'owner'"
                      :mode="workspace.subscription_mode"
                      dusk="subscription-badge"
                    />
                  </div>
                </div>
              </div>

              <!-- Description du workspace -->
              <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-4">
                {{ workspace.description || $t('workspace_picker.no_description') }}
              </p>

              <!-- Pied de carte : statistiques (propriétaires) ou mention de restriction (membres) -->
              <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                <!-- Propriétaire : statistiques complètes -->
                <div v-if="workspace.user_role === 'owner'" class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                  <span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ workspace.projets_count || 0 }}</span>
                    {{ $t('workspace_picker.projects') }}
                  </span>
                  <span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ workspace.member_count ?? workspace.members_count ?? 0 }}</span>
                    {{ $t('workspace_picker.members') }}
                  </span>
                </div>

                <!-- Membre non-propriétaire : mention discrète -->
                <div v-else class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-600 italic">
                  <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H8m4-6V9m0 0V7m0 2h2M12 9H10" />
                  </svg>
                  {{ $t('workspace_picker.member_stats_hidden') }}
                </div>

                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                  <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </main>

    <!-- Pied de page -->
    <footer class="shrink-0 text-center pb-6 text-xs text-gray-400 dark:text-gray-600">
      {{ $t('workspace_picker.footer') }}
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import { useWorkspace } from '@/composables/useWorkspace'
import { useAuthStore } from '@/stores/authStore'
import { useStagger } from '@/composables/useAnimations'
import ThemeToggler from '@/components/common/ThemeToggler.vue'
import LanguageSwitcher from '@/components/common/LanguageSwitcher.vue'
import UserMenu from '@/components/layout/header/UserMenu.vue'
import SubscriptionBadge from '@/components/admin/SubscriptionBadge.vue'

// Palette de couleurs pour la bande supérieure et l'avatar des cartes workspace.
// L'index est déterministe (id % longueur) — même couleur à chaque rendu.
const ACCENT_CLASSES = [
  'bg-blue-500',
  'bg-violet-500',
  'bg-emerald-500',
  'bg-rose-500',
  'bg-amber-500',
  'bg-cyan-500',
  'bg-fuchsia-500',
  'bg-teal-500',
]

const AVATAR_CLASSES = [
  'bg-linear-to-br from-blue-500 to-blue-700',
  'bg-linear-to-br from-violet-500 to-violet-700',
  'bg-linear-to-br from-emerald-500 to-emerald-700',
  'bg-linear-to-br from-rose-500 to-rose-700',
  'bg-linear-to-br from-amber-500 to-amber-700',
  'bg-linear-to-br from-cyan-500 to-cyan-700',
  'bg-linear-to-br from-fuchsia-500 to-fuchsia-700',
  'bg-linear-to-br from-teal-500 to-teal-700',
]

const cardAccentClass = (id: number) => ACCENT_CLASSES[id % ACCENT_CLASSES.length]
const cardAvatarClass = (id: number) => AVATAR_CLASSES[id % AVATAR_CLASSES.length]

const LogoDark = new URL('@/assets/images/logo/logo-transparent.png', import.meta.url).href

const router = useRouter()
const authStore = useAuthStore()
const { selectWorkspace } = useWorkspace()

// Stagger pour les cartes workspace
const { staggerRef, applyStagger } = useStagger(50)
// Stagger séparé pour les tuiles de stats agrégées
const { staggerRef: statStaggerRef, applyStagger: applyStatStagger } = useStagger(50)

const workspaces = ref<any[]>([])
const loading = ref(true)

// Filtre de recherche local — aucun appel API supplémentaire
const searchQuery = ref('')

// Workspaces filtrés selon le texte de recherche (nom, code, description)
const filteredWorkspaces = computed(() => {
  const q = searchQuery.value.toLowerCase()
  if (!q) return workspaces.value
  return workspaces.value.filter(w =>
    w.nom.toLowerCase().includes(q) ||
    w.code?.toLowerCase().includes(q) ||
    w.description?.toLowerCase().includes(q)
  )
})

// Stats agrégées calculées côté client depuis les données déjà chargées.
// projets_count et members_count ne sont agrégés que sur les workspaces dont
// l'utilisateur est propriétaire — un simple membre n'a pas le droit de voir
// le total des projets ou des membres d'une organisation qui ne lui appartient pas.
const accountStats = computed(() => {
  const all = workspaces.value
  const owned = all.filter((w: any) => w.user_role === 'owner')
  return {
    total: all.length,
    active: all.filter((w: any) => w.is_active).length,
    totalProjects: owned.reduce((a: number, w: any) => a + (w.projets_count || 0), 0),
    totalMembers: owned.reduce((a: number, w: any) => a + (w.member_count ?? w.members_count ?? 0), 0),
  }
})

const getInitials = (name: string): string => {
  if (!name) return 'WS'
  return name
    .split(' ')
    .map((w: string) => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

// Sélectionner un workspace et naviguer vers le tableau de bord
const select = async (workspace: any) => {
  await selectWorkspace(workspace)
  router.push({ name: 'Dashboard' })
}

// Ouvre les paramètres d'un workspace en l'activant d'abord —
// garantit que le contexte (sidebar, permissions) correspond au workspace édité
const openSettings = async (workspace: any) => {
  await selectWorkspace(workspace)
  router.push({ name: 'workspaces.settings', params: { id: workspace.id } })
}

onMounted(async () => {
  // Ne pas charger si plus de token (ex: déconnexion en cours)
  if (!localStorage.getItem('auth_token')) {
    loading.value = false
    return
  }
  try {
    // Admin temporaire : utiliser l'endpoint scopé via temporary_access.
    const endpoint = authStore.isTempAdmin ? '/workspaces/user-workspaces' : '/workspaces'
    const params = authStore.isTempAdmin ? {} : { per_page: 100 }
    const { data } = await api.get(endpoint, { params })
    // Normaliser : paginated (/workspaces) → data.data.data, non-paginé (/workspaces/user-workspaces) → data.data
    const raw = data?.data?.data ?? data?.data ?? data
    workspaces.value = Array.isArray(raw) ? raw : []
  } catch (e) {
    console.error('[WorkspacePicker] fetch error', e)
  } finally {
    loading.value = false
    // Appliquer les animations après chargement des données
    applyStatStagger()
    applyStagger()
  }
})
</script>
