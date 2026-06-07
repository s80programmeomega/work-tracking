<!-- Modale réutilisable de création / édition d'une catégorie d'aide.
     Utilisée par la page de gestion des catégories et par le formulaire d'article. -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="$emit('close')">
    <div class="w-full max-w-lg rounded-3 border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900">
      <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
        {{ isEdit ? $t('help.admin.edit_category') : $t('help.admin.new_category') }}
      </h2>

      <form class="space-y-4" @submit.prevent="save">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.cat_name_fr') }}</label>
            <input v-model="form.nom_fr" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" @input="maybeSyncSlug" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.cat_name_en') }}</label>
            <input v-model="form.nom_en" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.cat_slug') }}</label>
          <input v-model="form.slug" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>

        <!-- Sélecteur d'icône : résumé (icône + nom) + grille curée repliable. -->
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.cat_icon') }}</label>

          <!-- Résumé cliquable : ouvre/ferme la grille ; affiche l'icône choisie + son nom. -->
          <button
            type="button"
            @click="iconPickerOpen = !iconPickerOpen"
            class="flex w-full items-center justify-between rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            <span class="flex items-center gap-2">
              <i :class="['fas', form.icon || 'fa-book', 'w-4 text-center text-blue-600 dark:text-blue-400']"></i>
              <span>{{ selectedIconLabel }}</span>
            </span>
            <i :class="['fas', iconPickerOpen ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400']"></i>
          </button>

          <!-- Grille repliée par défaut. Transition "collapse" partagée (animations.css). -->
          <transition name="collapse">
            <div v-if="iconPickerOpen">
              <div class="collapse-inner">
                <div ref="iconGridRef" class="mt-2 grid grid-cols-8 gap-2 rounded-3 border border-gray-200 p-2 dark:border-gray-700">
                  <button
                    v-for="opt in iconOptions"
                    :key="opt.value"
                    type="button"
                    :title="opt.label"
                    :aria-label="opt.label"
                    @click="selectIcon(opt.value)"
                    class="stagger-item flex h-9 w-full items-center justify-center rounded-3 border text-sm transition-colors"
                    :class="form.icon === opt.value
                      ? 'border-blue-500 bg-blue-50 text-blue-600 dark:border-blue-600 dark:bg-blue-900/30 dark:text-blue-300'
                      : 'border-gray-300 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800'"
                  >
                    <i :class="['fas', opt.value]"></i>
                  </button>
                </div>
              </div>
            </div>
          </transition>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.cat_position') }}</label>
          <input v-model.number="form.position" type="number" min="0" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:w-40" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.cat_desc_fr') }}</label>
            <textarea v-model="form.description_fr" rows="2" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('help.admin.cat_desc_en') }}</label>
            <textarea v-model="form.description_en" rows="2" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
          </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <input v-model="form.published" type="checkbox" class="rounded border-gray-300" />
          {{ $t('help.admin.cat_published') }}
        </label>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" @click="$emit('close')" class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
            {{ $t('help.admin.cancel') }}
          </button>
          <button type="submit" :disabled="saving" class="rounded-3 bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
            {{ $t('help.admin.save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'

const props = defineProps({
  // Catégorie existante (édition) ou null (création).
  category: { type: Object, default: null },
})
const emit = defineEmits(['saved', 'close'])

// Animation en cascade de la grille d'icônes (Guide 23). maxItems relevé à 64
// pour que toutes les icônes du set soient animées, pas seulement les 20 premières.
const { staggerRef: iconGridRef, applyStagger } = useStagger(25, 64)

// Jeu d'icônes curé, aligné sur les domaines du produit (Projet → Activité → Tâche,
// workspaces/équipes, chat, évaluations, notifications, documents, recherche,
// sécurité, abonnement, paramètres). Noms FA "classic" (v5) déjà utilisés par l'app
// pour garantir le rendu avec la version FontAwesome chargée (free solid / fas).
const iconOptions = [
  // Aide / documentation
  { value: 'fa-book', label: 'Général / Documentation' },
  { value: 'fa-question-circle', label: 'FAQ / Aide' },
  { value: 'fa-rocket', label: 'Prise en main' },
  { value: 'fa-lightbulb', label: 'Astuces & bonnes pratiques' },
  { value: 'fa-graduation-cap', label: 'Tutoriels / Formation' },
  { value: 'fa-info-circle', label: 'Informations générales' },
  { value: 'fa-circle-question', label: 'Support / Questions' },
  { value: 'fa-life-ring', label: 'Assistance' },
  // Hiérarchie projet
  { value: 'fa-folder', label: 'Projets' },
  { value: 'fa-list-check', label: 'Activités' },
  { value: 'fa-clipboard-list', label: 'Tâches' },
  { value: 'fa-check-double', label: 'Validation / Résultats' },
  { value: 'fa-calendar-alt', label: 'Échéances / Planning' },
  // Collaboration
  { value: 'fa-users', label: 'Workspaces / Membres' },
  { value: 'fa-user-tag', label: 'Rôles & permissions' },
  { value: 'fa-comments', label: 'Chat / Messagerie' },
  { value: 'fa-bell', label: 'Notifications' },
  { value: 'fa-paperclip', label: 'Pièces jointes' },
  // Données & analyse
  { value: 'fa-chart-bar', label: 'Évaluations / Rapports' },
  { value: 'fa-chart-line', label: 'Statistiques / Performance' },
  { value: 'fa-trophy', label: 'Scores / Classement' },
  { value: 'fa-file', label: 'Documents' },
  { value: 'fa-file-export', label: 'Exports' },
  { value: 'fa-search', label: 'Recherche' },
  // Compte & administration
  { value: 'fa-shield-alt', label: 'Sécurité / MFA' },
  { value: 'fa-user-shield', label: 'Confidentialité' },
  { value: 'fa-key', label: 'Authentification / Connexion' },
  { value: 'fa-credit-card', label: 'Abonnement / Paiement' },
  { value: 'fa-cog', label: 'Paramètres / Configuration' },
  { value: 'fa-building', label: 'Organisation' },
  { value: 'fa-globe', label: 'Langue / Localisation' },
  { value: 'fa-wrench', label: 'Dépannage' },
  // Compte & profil
  { value: 'fa-user', label: 'Compte / Profil' },
  { value: 'fa-user-circle', label: 'Mon profil' },
  { value: 'fa-id-card', label: 'Identité / Coordonnées' },
  { value: 'fa-envelope', label: 'E-mails / Messagerie' },
  { value: 'fa-lock', label: 'Mot de passe / Verrouillage' },
  { value: 'fa-sign-in-alt', label: 'Connexion' },
  // Hiérarchie projet (compléments)
  { value: 'fa-tasks', label: 'Suivi des tâches' },
  { value: 'fa-project-diagram', label: 'Structure du projet' },
  { value: 'fa-flag', label: 'Priorités / Jalons' },
  { value: 'fa-clock', label: 'Délais / Historique' },
  { value: 'fa-tag', label: 'Étiquettes / Labels' },
  { value: 'fa-tags', label: 'Catégorisation' },
  // Données, contenus & flux
  { value: 'fa-th-large', label: 'Tableau de bord' },
  { value: 'fa-table', label: 'Tableaux / Données' },
  { value: 'fa-file-import', label: 'Imports' },
  { value: 'fa-download', label: 'Téléchargements' },
  { value: 'fa-upload', label: 'Téléversements' },
  { value: 'fa-archive', label: 'Archives' },
  { value: 'fa-image', label: 'Images / Médias' },
  { value: 'fa-print', label: 'Impression' },
  // Outils & intégrations
  { value: 'fa-plug', label: 'Intégrations' },
  { value: 'fa-sync-alt', label: 'Synchronisation' },
  { value: 'fa-database', label: 'Données / Stockage' },
  { value: 'fa-mobile-alt', label: 'Application mobile' },
  { value: 'fa-desktop', label: 'Application bureau' },
  { value: 'fa-bug', label: 'Problèmes / Bugs' },
  { value: 'fa-exclamation-triangle', label: 'Avertissements' },
  { value: 'fa-check-circle', label: 'Statuts / Confirmations' },
  { value: 'fa-star', label: 'Favoris / Nouveautés' },
  { value: 'fa-bullhorn', label: 'Annonces' },
  { value: 'fa-handshake', label: 'Contact / Partenariats' },
  { value: 'fa-headset', label: 'Support client' },
]

const isEdit = computed(() => !!props.category?.id)

// État du sélecteur d'icône (replié par défaut pour ne pas envahir le formulaire).
const iconPickerOpen = ref(false)

// Libellé auto-rempli de l'icône sélectionnée (nom lisible, pas la classe FA).
const selectedIconLabel = computed(() => {
  const match = iconOptions.find((o) => o.value === form.value.icon)
  return match ? match.label : (form.value.icon || 'fa-book')
})

// Sélection d'une icône : applique + replie le panneau.
const selectIcon = (value) => {
  form.value.icon = value
  iconPickerOpen.value = false
}

// Rejoue l'animation en cascade à chaque ouverture de la grille.
watch(iconPickerOpen, (open) => {
  if (open) { applyStagger() }
})

const form = ref({
  nom_fr: props.category?.nom_fr ?? '',
  nom_en: props.category?.nom_en ?? '',
  slug: props.category?.slug ?? '',
  icon: props.category?.icon ?? 'fa-book',
  position: props.category?.position ?? 0,
  description_fr: props.category?.description_fr ?? '',
  description_en: props.category?.description_en ?? '',
  published: isEdit.value ? !!props.category?.published_at : true,
})

const saving = ref(false)
const error = ref(null)

// Génère un slug à partir du nom FR tant que l'utilisateur n'a pas saisi le sien (création).
const slugTouched = ref(isEdit.value)
const slugify = (s) => s.toString().toLowerCase().trim()
  .normalize('NFD').replace(/[̀-ͯ]/g, '')
  .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
const maybeSyncSlug = () => {
  if (!slugTouched.value) form.value.slug = slugify(form.value.nom_fr)
}

const save = async () => {
  saving.value = true
  error.value = null
  try {
    const payload = {
      nom_fr: form.value.nom_fr,
      nom_en: form.value.nom_en,
      slug: form.value.slug,
      icon: form.value.icon || 'fa-book',
      position: form.value.position ?? 0,
      description_fr: form.value.description_fr || null,
      description_en: form.value.description_en || null,
      // published_at piloté par la case à cocher.
      published_at: form.value.published ? new Date().toISOString() : null,
    }
    const { data } = isEdit.value
      ? await api.put(`/admin/help/categories/${props.category.id}`, payload)
      : await api.post('/admin/help/categories', payload)
    emit('saved', data.category)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Erreur lors de l\'enregistrement.'
  } finally {
    saving.value = false
  }
}
</script>
