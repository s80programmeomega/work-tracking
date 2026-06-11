<!-- resources\js\components\admin\UserProfileModal.vue -->
<template>
  <div
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
    @click.self="$emit('close')"
    dusk="user-profile-modal"
  >
    <div class="bg-white dark:bg-gray-900 rounded-3 w-full max-w-2xl max-h-[90vh] flex flex-col shadow-xl">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ canEdit && editMode ? $t('user_profile_modal.title_edit') : $t('user_profile_modal.title_view') }}
          </h3>
          <span
            v-if="canEdit"
            class="px-2 py-0.5 text-xs rounded-full"
            :class="editMode
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
              : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
          >
            {{ editMode ? $t('user_profile_modal.mode_edit') : $t('user_profile_modal.mode_read') }}
          </span>
        </div>
        <div class="flex items-center gap-2">
          <!-- Edit toggle — only super-admin sees this -->
          <button
            v-if="canEdit"
            dusk="profile-edit-toggle"
            @click="editMode = !editMode"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-3 transition-colors"
            :class="editMode
              ? 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
              : 'bg-blue-600 text-white hover:bg-blue-700'"
          >
            <PencilIcon class="w-4 h-4" />
            {{ editMode ? $t('user_profile_modal.btn_cancel_edit') : $t('user_profile_modal.btn_edit') }}
          </button>
          <button
            @click="$emit('close')"
            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="overflow-y-auto flex-1 px-6 py-5">
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-10">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error -->
        <div v-else-if="loadError" class="py-8 text-center text-red-500 dark:text-red-400">
          {{ loadError }}
        </div>

        <!-- Profile content -->
        <div v-else-if="profileData" class="space-y-6">

          <!-- Avatar + name row -->
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full overflow-hidden bg-brand-100 dark:bg-brand-900/20 shrink-0 flex items-center justify-center text-brand-600 dark:text-brand-400 text-2xl font-bold">
              <img v-if="profileData.avatar" :src="profileData.avatar" class="w-full h-full object-cover" :alt="profileData.nom" />
              <span v-else>{{ initials }}</span>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-900 dark:text-white">{{ profileData.nom }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ profileData.email }}</p>
              <p v-if="profileData.fonction" class="text-sm text-gray-500 dark:text-gray-400">{{ profileData.fonction }}</p>
            </div>
          </div>

          <!-- Read-only fields -->
          <template v-if="!editMode">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.field_role') }}</span>
                <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ rolesDisplay }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.field_status') }}</span>
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="profileData.is_active
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                >
                  {{ profileData.is_active ? $t('user_profile_modal.status_active') : $t('user_profile_modal.status_inactive') }}
                </span>
              </div>
              <div v-if="profileData.telephone" class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.field_phone') }}</span>
                <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ profileData.telephone }}</span>
              </div>
              <div v-if="profileData.adresse" class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.field_address') }}</span>
                <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ profileData.adresse }}</span>
              </div>
              <div v-if="profileData.timezone" class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.field_timezone') }}</span>
                <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ profileData.timezone }}</span>
              </div>
              <div v-if="profileData.language" class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.field_language') }}</span>
                <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ profileData.language === 'fr' ? 'Français' : 'English' }}</span>
              </div>
              <div v-if="profileData.last_login_at" class="flex flex-col gap-1 col-span-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.field_last_login') }}</span>
                <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ formatDate(profileData.last_login_at) }}</span>
              </div>
            </div>

            <!-- Stats (only if super-admin or self) -->
            <div v-if="canEdit && profileData.stats" class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ profileData.stats?.projets_actifs ?? '—' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.stat_projects') }}</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ profileData.stats?.taches_total ?? '—' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.stat_tasks') }}</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ profileData.stats?.taux_completion != null ? profileData.stats.taux_completion + '%' : '—' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('user_profile_modal.stat_completion') }}</p>
              </div>
            </div>
          </template>

          <!-- Edit form — super-admin only -->
          <template v-else>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-400">{{ $t('user_profile_modal.field_name') }}</label>
                <input v-model="editForm.nom" type="text" class="w-full h-10 px-3 py-2 text-sm text-gray-800 dark:text-white/90 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10" />
              </div>
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-400">{{ $t('user_profile_modal.field_email') }}</label>
                <input v-model="editForm.email" type="email" class="w-full h-10 px-3 py-2 text-sm text-gray-800 dark:text-white/90 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10" />
              </div>
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-400">{{ $t('user_profile_modal.field_phone') }}</label>
                <input v-model="editForm.telephone" type="text" class="w-full h-10 px-3 py-2 text-sm text-gray-800 dark:text-white/90 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10" />
              </div>
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-400">{{ $t('user_profile_modal.field_fonction') }}</label>
                <input v-model="editForm.fonction" type="text" class="w-full h-10 px-3 py-2 text-sm text-gray-800 dark:text-white/90 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10" />
              </div>
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-400">{{ $t('user_profile_modal.field_status') }}</label>
                <select v-model="editForm.is_active" class="w-full h-10 px-3 py-2 text-sm text-gray-800 dark:text-white/90 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10">
                  <option :value="true">{{ $t('user_profile_modal.status_active') }}</option>
                  <option :value="false">{{ $t('user_profile_modal.status_inactive') }}</option>
                </select>
              </div>
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-400">{{ $t('user_profile_modal.field_timezone') }}</label>
                <input v-model="editForm.timezone" type="text" class="w-full h-10 px-3 py-2 text-sm text-gray-800 dark:text-white/90 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10" />
              </div>
            </div>
            <p v-if="saveError" class="text-sm text-red-500 dark:text-red-400">{{ saveError }}</p>
          </template>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
        >
          {{ $t('user_profile_modal.btn_close') }}
        </button>
        <button
          v-if="editMode && canEdit"
          dusk="profile-save-btn"
          @click="saveEdit"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-3 hover:bg-blue-700 disabled:opacity-50 transition-colors"
        >
          <span v-if="saving" class="flex items-center gap-1.5">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ $t('user_profile_modal.btn_saving') }}
          </span>
          <span v-else>{{ $t('user_profile_modal.btn_save') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { PencilIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import api from '@/api/axios'
import { useNotifications } from '@/composables/useNotifications'

const props = defineProps({
  userId: { type: [Number, String], required: true },
})

const emit = defineEmits(['close', 'saved'])

const { t } = useI18n()
const { showSuccess, showError } = useNotifications()

const loading = ref(true)
const loadError = ref(null)
const saving = ref(false)
const saveError = ref(null)
const profileData = ref(null)
const canEdit = ref(false)
const editMode = ref(false)

const editForm = ref({
  nom: '',
  email: '',
  telephone: '',
  fonction: '',
  is_active: true,
  timezone: '',
})

const initials = computed(() => {
  const nom = profileData.value?.nom ?? ''
  return nom.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

const rolesDisplay = computed(() => {
  return (profileData.value?.roles ?? []).map(r => r.name ?? r).join(', ') || '—'
})

const formatDate = (iso) => {
  if (!iso) { return '—' }
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const loadProfile = async () => {
  loading.value = true
  loadError.value = null
  try {
    const res = await api.get(`/users/${props.userId}/profile-view`)
    profileData.value = res.data.data
    canEdit.value = res.data.can_edit === true
    // Pré-remplir le formulaire d'édition
    editForm.value = {
      nom: profileData.value.nom ?? '',
      email: profileData.value.email ?? '',
      telephone: profileData.value.telephone ?? '',
      fonction: profileData.value.fonction ?? '',
      is_active: profileData.value.is_active ?? true,
      timezone: profileData.value.timezone ?? '',
    }
  } catch (err) {
    loadError.value = err.response?.data?.message ?? t('user_profile_modal.load_error')
  } finally {
    loading.value = false
  }
}

const saveEdit = async () => {
  saving.value = true
  saveError.value = null
  try {
    await api.put(`/users/${props.userId}`, editForm.value)
    showSuccess(t('user_profile_modal.save_success'))
    editMode.value = false
    await loadProfile()
    emit('saved')
  } catch (err) {
    saveError.value = err.response?.data?.message ?? t('user_profile_modal.save_error')
    showError(saveError.value)
  } finally {
    saving.value = false
  }
}

onMounted(loadProfile)
</script>

