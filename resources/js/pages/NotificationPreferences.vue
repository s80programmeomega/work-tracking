<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Préférences de notification'" />
  <div class="mx-auto max-w-screen-2xl">

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
    </div>

    <!-- Preferences Form -->
    <div v-else class="space-y-6">
      <!-- Global Channel Settings -->
      <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          <i class="fas fa-toggle-on text-blue-600 dark:text-blue-400 mr-2"></i>
          Canaux de notification
        </h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <label class="font-medium text-gray-900 dark:text-white">Notifications in-app</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">Recevoir des notifications dans l'application</p>
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
              <label class="font-medium text-gray-900 dark:text-white">Notifications par email</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">Recevoir des notifications par email</p>
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
              <label class="font-medium text-gray-900 dark:text-white">Notifications push</label>
              <p class="text-sm text-gray-600 dark:text-gray-400">Recevoir des notifications push sur votre appareil</p>
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
                <label class="font-medium text-gray-900 dark:text-white">Cet appareil</label>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  <template v-if="webPushStatus === 'unsupported'">
                    Ce navigateur ne supporte pas les notifications push.
                  </template>
                  <template v-else-if="webPushStatus === 'denied'">
                    Permission refusée. Réactivez les notifications dans les paramètres du navigateur.
                  </template>
                  <template v-else-if="webPushStatus === 'subscribed'">
                    Inscrit aux notifications push sur ce navigateur.
                  </template>
                  <template v-else>
                    Cet appareil n'est pas encore inscrit. Cliquez pour autoriser les notifications.
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
                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
              >
                {{ webPushLoading ? 'En cours…' : 'Activer' }}
              </button>
              <button
                v-else-if="webPushStatus === 'subscribed'"
                @click="onWebPushUnsubscribe"
                :disabled="webPushLoading"
                dusk="webpush-unsubscribe"
                class="px-4 py-2 bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium hover:bg-gray-300 disabled:opacity-50"
              >
                {{ webPushLoading ? 'En cours…' : 'Désactiver' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Notification Types -->
      <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          <i class="fas fa-bell text-blue-600 dark:text-blue-400 mr-2"></i>
          Types de notifications
        </h3>

        <div class="space-y-6">
          <!-- Task Assigned -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Tâche assignée</h4>
            <div class="grid grid-cols-3 gap-4 ml-4">
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_assigned_in_app" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">In-app</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_assigned_email" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Email</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_assigned_push" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Push</span>
              </label>
            </div>
          </div>

          <!-- Task Due Soon -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Échéance proche</h4>
            <div class="grid grid-cols-3 gap-4 ml-4">
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_due_soon_in_app" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">In-app</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_due_soon_email" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Email</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.task_due_soon_push" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Push</span>
              </label>
            </div>
          </div>

          <!-- Mentioned in Comment -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Mention dans un commentaire</h4>
            <div class="grid grid-cols-3 gap-4 ml-4">
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.mentioned_in_comment_in_app" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">In-app</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.mentioned_in_comment_email" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Email</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" v-model="preferences.mentioned_in_comment_push" class="mr-2 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">Push</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Quiet Hours -->
      <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          <i class="fas fa-moon text-blue-600 dark:text-blue-400 mr-2"></i>
          Heures silencieuses
        </h3>

        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <label class="font-medium text-gray-900 dark:text-white">Activer les heures silencieuses</label>
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
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Début</label>
              <input
                type="time"
                v-model="preferences.quiet_hours_start"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fin</label>
              <input
                type="time"
                v-model="preferences.quiet_hours_end"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end gap-3">
        <router-link
          to="/notifications"
          class="px-6 py-3 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          Annuler
        </router-link>
        <button
          @click="savePreferences"
          :disabled="saving"
          class="px-6 py-3 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <i v-if="saving" class="fas fa-spinner fa-spin mr-2"></i>
          <i v-else class="fas fa-save mr-2"></i>
          {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
        </button>
      </div>
    </div>
  </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api/axios';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import { useWebPush } from '@/composables/useWebPush';

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
    alert('Préférences enregistrées avec succès!');
    router.push('/notifications');
  } catch (error) {
    console.error('Error saving preferences:', error);
    alert('Erreur lors de l\'enregistrement des préférences');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadPreferences();
});
</script>
