<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="$t('notifications.preferences_page.title')" />
  <div class="mx-auto max-w-screen-2xl">

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <svg class="animate-spin h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
    </div>

    <!-- Preferences Form -->
    <div v-else class="space-y-6">
      <!-- Global Channel Settings -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600 dark:text-blue-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
          {{ $t('notifications.preferences_page.channels') }}
        </h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <label class="font-medium text-gray-900 dark:text-white">{{ $t('notifications.preferences_page.in_app') }}</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('notifications.preferences_page.in_app_desc') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="preferences.in_app_enabled"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <label class="font-medium text-gray-900 dark:text-white">{{ $t('notifications.preferences_page.email') }}</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('notifications.preferences_page.email_desc') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="preferences.email_enabled"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <label class="font-medium text-gray-900 dark:text-white">{{ $t('notifications.preferences_page.push') }}</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('notifications.preferences_page.push_desc') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="preferences.push_enabled"
                class="sr-only peer"
                dusk="push-enabled-toggle"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <!-- Task 8b — Inscription de cet appareil aux notifications Web Push -->
          <!-- Apparaît seulement quand le master switch push_enabled est activé -->
          <div
            v-if="preferences.push_enabled"
            dusk="webpush-device-panel"
            class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <label class="font-medium text-gray-900 dark:text-white">{{ $t('notifications.preferences_page.this_device') }}</label>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  <template v-if="webPushStatus === 'unsupported'">
                    {{ $t('notifications.preferences_page.unsupported') }}
                  </template>
                  <template v-else-if="webPushStatus === 'denied'">
                    {{ $t('notifications.preferences_page.denied') }}
                  </template>
                  <template v-else-if="webPushStatus === 'subscribed'">
                    {{ $t('notifications.preferences_page.subscribed') }}
                  </template>
                  <template v-else>
                    {{ $t('notifications.preferences_page.not_subscribed') }}
                  </template>
                </p>
                <p v-if="webPushError" class="text-xs text-red-600 dark:text-red-400 mt-1">
                  {{ webPushError }}
                </p>
              </div>
              <button
                v-if="webPushStatus === 'not-subscribed'"
                @click="onWebPushSubscribe"
                :disabled="webPushLoading"
                dusk="webpush-subscribe"
                class="px-4 py-2 bg-blue-600 text-white rounded-3 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
              >
                {{ webPushLoading ? $t('notifications.preferences_page.activating') : $t('notifications.preferences_page.activate') }}
              </button>
              <button
                v-else-if="webPushStatus === 'subscribed'"
                @click="onWebPushUnsubscribe"
                :disabled="webPushLoading"
                dusk="webpush-unsubscribe"
                class="px-4 py-2 bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200 rounded-3 text-sm font-medium hover:bg-gray-300 disabled:opacity-50"
              >
                {{ webPushLoading ? $t('notifications.preferences_page.activating') : $t('notifications.preferences_page.deactivate') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Notification Types -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600 dark:text-blue-400"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
          {{ $t('notifications.preferences_page.types') }}
        </h3>

        <div class="space-y-6">
          <!-- Task Assigned -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ $t('notifications.preferences_page.task_assigned') }}</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 ml-4">
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_assigned_in_app" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">In-app</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_assigned_email" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Email</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_assigned_push" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Push</span>
              </label>
            </div>
          </div>

          <!-- Task Due Soon -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ $t('notifications.preferences_page.task_due_soon') }}</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 ml-4">
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_due_soon_in_app" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">In-app</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_due_soon_email" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Email</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_due_soon_push" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Push</span>
              </label>
            </div>
          </div>

          <!-- Mentioned in Comment -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ $t('notifications.preferences_page.mentioned') }}</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 ml-4">
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.mentioned_in_comment_in_app" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">In-app</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.mentioned_in_comment_email" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Email</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.mentioned_in_comment_push" class="mr-2 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Push</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Quiet Hours -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600 dark:text-blue-400"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998z"/></svg>
          {{ $t('notifications.preferences_page.quiet_hours') }}
        </h3>

        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <label class="font-medium text-gray-900 dark:text-white">{{ $t('notifications.preferences_page.enable_quiet') }}</label>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="preferences.quiet_hours_enabled"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
            </label>
          </div>

          <div v-if="preferences.quiet_hours_enabled" class="grid grid-cols-2 gap-4 ml-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $t('notifications.preferences_page.start') }}</label>
              <input
                type="time"
                v-model="preferences.quiet_hours_start"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $t('notifications.preferences_page.end') }}</label>
              <input
                type="time"
                v-model="preferences.quiet_hours_end"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end gap-3">
        <router-link
          to="/notifications"
          class="px-6 py-3 rounded-3 text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          {{ $t('common.cancel') }}
        </router-link>
        <button
          @click="savePreferences"
          :disabled="saving"
          class="px-6 py-3 rounded-3 text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="saving" class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
          {{ saving ? $t('notifications.preferences_page.saving') : $t('notifications.preferences_page.save') }}
        </button>
      </div>
    </div>
  </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/api/axios';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import { useWebPush } from '@/composables/useWebPush';

const { t } = useI18n();
const router = useRouter();
const loading = ref(true);
const saving = ref(false);

// Task 8b — composable Web Push : gère l'inscription navigateur de cet appareil.
// On expose ses propriétés sous des alias pour éviter les collisions de noms
// avec les variables existantes du fichier (loading, error).
const {
    status: webPushStatus,
    loading: webPushLoading,
    error: webPushError,
    subscribe: webPushSubscribe,
    unsubscribe: webPushUnsubscribe,
} = useWebPush();

const onWebPushSubscribe = async () => {
    await webPushSubscribe();
};

const onWebPushUnsubscribe = async () => {
    await webPushUnsubscribe();
};

const preferences = ref({
  in_app_enabled: true,
  email_enabled: true,
  // Activé par défaut: aligné sur la valeur par défaut côté BDD
  // (migration enable_push_notifications_by_default). loadPreferences()
  // synchronisera ensuite avec ce que le backend a réellement persisté
  // pour cet utilisateur.
  push_enabled: true,
  task_assigned_in_app: true,
  task_assigned_email: true,
  task_assigned_push: false,
  task_due_soon_in_app: true,
  task_due_soon_email: true,
  task_due_soon_push: true,
  mentioned_in_comment_in_app: true,
  mentioned_in_comment_email: true,
  mentioned_in_comment_push: true,
  quiet_hours_enabled: false,
  quiet_hours_start: '22:00',
  quiet_hours_end: '08:00',
});

const loadPreferences = async () => {
  loading.value = true;
  try {
    const response = await api.get('/notification-preferences');
    if (response.data.data) {
      Object.assign(preferences.value, response.data.data);
    }
  } catch (error) {
    console.error('Error loading preferences:', error);
  } finally {
    loading.value = false;
  }
};

const savePreferences = async () => {
  saving.value = true;
  try {
    await api.put('/notification-preferences', preferences.value);
    alert(t('notifications.preferences_page.saved_success'));
    router.push('/notifications');
  } catch (error) {
    console.error('Error saving preferences:', error);
    alert(t('notifications.preferences_page.save_error'));
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadPreferences();
});
</script>
