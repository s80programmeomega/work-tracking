<!-- resources/js/pages/workspace/WorkspaceTempAdmin.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- Accès refusé -->
      <div
        v-if="accessDenied"
        class="rounded-3 border border-red-200 bg-red-50 px-6 py-4 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
      >
        <p class="font-semibold">{{ $t('temp_admin.access_denied_title') }}</p>
        <p class="mt-1 text-sm">{{ $t('temp_admin.access_denied_desc') }}</p>
      </div>

      <template v-else>
        <!-- En-tête -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
              {{ $t('temp_admin.title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ $t('temp_admin.subtitle') }}
            </p>
          </div>
          <button
            dusk="open-promote-modal"
            @click="openPromoteModal"
            class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          >
            <ShieldCheckIcon class="h-4 w-4" />
            {{ $t('temp_admin.promote_btn') }}
          </button>
        </div>

        <!-- Erreur API -->
        <div
          v-if="error"
          class="rounded-3 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ error }}
        </div>

        <!-- Feedback succès -->
        <transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-300" leave-to-class="opacity-0">
          <div
            dusk="success-message"
            v-if="successMessage"
            class="rounded-3 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
          >
            {{ successMessage }}
          </div>
        </transition>

        <!-- Tableau des comptes admins temporaires -->
        <div class="overflow-hidden rounded-3 border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
          <div v-if="loading" class="flex justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
          </div>

          <div v-else-if="admins.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ $t('temp_admin.col_user') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ $t('temp_admin.col_expires') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ $t('temp_admin.col_role') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ $t('temp_admin.col_action') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Statut
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ $t('temp_admin.col_actions') }}
                  </th>
                </tr>
              </thead>
              <tbody ref="staggerRef" class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="admin in admins"
                  :key="admin.id"
                  :dusk="`temp-admin-row-${admin.id}`"
                  class="stagger-item transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
                >
                  <td class="px-6 py-4">
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ admin.nom }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ admin.email }}</p>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(admin.admin_expires_at) }}
                  </td>
                  <td class="px-6 py-4">
                    <span :class="roleClass(admin.workspace_role)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                      {{ $t('temp_admin.modal_role_' + (admin.workspace_role || 'observateur')) }}
                    </span>
                    <span
                      v-if="admin.custom_permissions && admin.custom_permissions.length"
                      :dusk="`custom-badge-${admin.id}`"
                      class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400"
                    >
                      custom
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                      {{ admin.admin_expiry_action === 'delete' ? $t('temp_admin.action_delete') : $t('temp_admin.action_suspend') }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span
                      v-if="admin.is_suspended"
                      class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/20 dark:text-orange-400"
                    >
                      {{ $t('temp_admin.status_suspended') }}
                    </span>
                    <span
                      v-else-if="isExpired(admin.admin_expires_at)"
                      class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400"
                    >
                      {{ $t('temp_admin.status_expired') }}
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400"
                    >
                      {{ $t('temp_admin.status_active') }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <!-- Compte suspendu : seule l'action Réactiver est disponible -->
                      <template v-if="admin.is_suspended">
                        <button
                          :dusk="`reactivate-admin-${admin.id}`"
                          @click="reactivateAdmin(admin)"
                          :disabled="processingId === admin.id"
                          class="inline-flex items-center gap-1 rounded-3 border border-green-300 bg-white px-3 py-1.5 text-xs font-medium text-green-600 transition-colors hover:bg-green-50 disabled:opacity-50 dark:border-green-700 dark:bg-transparent dark:text-green-400 dark:hover:bg-green-900/20"
                        >
                          <ArrowPathIcon class="h-3.5 w-3.5" />
                          {{ $t('temp_admin.action_reactivate') }}
                        </button>
                      </template>
                      <!-- Compte actif ou expiré : actions normales -->
                      <template v-else>
                        <button
                          :dusk="`send-credentials-${admin.id}`"
                          @click="sendCredentials(admin)"
                          :disabled="processingId === admin.id"
                          class="inline-flex items-center gap-1 rounded-3 border border-blue-300 bg-white px-3 py-1.5 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-50 disabled:opacity-50 dark:border-blue-700 dark:bg-transparent dark:text-blue-400 dark:hover:bg-blue-900/20"
                        >
                          <EnvelopeIcon class="h-3.5 w-3.5" />
                          {{ $t('temp_admin.action_send_credentials') }}
                        </button>
                        <button
                          :dusk="`terminate-admin-${admin.id}`"
                          @click="terminateAdmin(admin)"
                          :disabled="processingId === admin.id"
                          class="inline-flex items-center gap-1 rounded-3 border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 disabled:opacity-50 dark:border-red-700 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-900/20"
                        >
                          <NoSymbolIcon class="h-3.5 w-3.5" />
                          {{ $t('temp_admin.action_terminate') }}
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="py-16 text-center">
            <ShieldCheckIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
            <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ $t('temp_admin.empty_title') }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('temp_admin.empty_desc') }}</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Modal création admin temporaire -->
    <div
      v-if="modal.open"
      class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 py-8"
      @click.self="modal.open = false"
    >
      <div class="w-full max-w-lg rounded-3 bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">
            {{ $t('temp_admin.modal_title') }}
          </h3>
          <button @click="modal.open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div class="space-y-4 px-6 py-4">
          <!-- Nom du nouveau compte -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('temp_admin.modal_nom') }}
            </label>
            <input
              dusk="modal-nom"
              v-model="modal.nom"
              type="text"
              :placeholder="$t('temp_admin.modal_nom_placeholder')"
              class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Email du nouveau compte -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('temp_admin.modal_email') }}
            </label>
            <input
              dusk="modal-email"
              v-model="modal.email"
              type="email"
              :placeholder="$t('temp_admin.modal_email_placeholder')"
              class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Workspaces accessibles -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('temp_admin.modal_workspaces') }}
            </label>
            <div class="max-h-36 space-y-1 overflow-y-auto rounded-3 border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-700">
              <label
                v-for="ws in ownedWorkspaces"
                :key="ws.id"
                class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                <input
                  type="checkbox"
                  :value="ws.id"
                  v-model="modal.workspaceIds"
                  class="h-4 w-4 rounded border-gray-300 text-blue-600"
                />
                <span class="text-gray-900 dark:text-white">{{ ws.nom }}</span>
              </label>
            </div>
            <p v-if="!ownedWorkspaces.length" class="mt-1 text-xs text-gray-400">{{ $t('temp_admin.no_owned_workspaces') }}</p>
          </div>

          <!-- Toggle personnalisation des permissions -->
          <div class="rounded-3 border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-700/50">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('temp_admin.role_toggle') }}
                </p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                  {{ $t('temp_admin.role_toggle_hint') }}
                </p>
              </div>
              <!-- Toggle switch -->
              <button
                dusk="role-custom-toggle"
                type="button"
                @click="modal.customPermissions = !modal.customPermissions"
                :class="modal.customPermissions
                  ? 'bg-blue-600'
                  : 'bg-gray-300 dark:bg-gray-600'"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors focus:outline-none"
              >
                <span
                  :class="modal.customPermissions ? 'translate-x-6' : 'translate-x-1'"
                  class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                />
              </button>
            </div>

            <!-- Sélecteur de rôle — affiché seulement si toggle actif -->
            <transition
              enter-active-class="transition-all duration-200 overflow-hidden"
              enter-from-class="max-h-0 opacity-0"
              enter-to-class="max-h-screen opacity-100"
              leave-active-class="transition-all duration-200 overflow-hidden"
              leave-from-class="max-h-screen opacity-100"
              leave-to-class="max-h-0 opacity-0"
            >
              <div v-if="modal.customPermissions" dusk="permissions-panel" class="mt-4 space-y-4">
                <!-- Rôle de base -->
                <div>
                  <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('temp_admin.modal_role') }}
                  </label>
                  <select
                    dusk="modal-workspace-role"
                    v-model="modal.workspaceRole"
                    @change="resetPermissionsToRole"
                    class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                  >
                    <option value="observateur">{{ $t('temp_admin.modal_role_observateur') }}</option>
                    <option value="cadre">{{ $t('temp_admin.modal_role_cadre') }}</option>
                    <option value="manager">{{ $t('temp_admin.modal_role_manager') }}</option>
                  </select>
                </div>

                <!-- Accordéon des permissions -->
                <div>
                  <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('temp_admin.permissions_title') }}
                    <span class="ml-1 text-xs text-gray-400">
                      ({{ $t('temp_admin.permissions_based_on', { role: modal.workspaceRole }) }})
                    </span>
                  </p>
                  <div class="space-y-2">
                    <div
                      v-for="group in permissionGroups"
                      :key="group.key"
                      class="overflow-hidden rounded-3 border border-gray-200 dark:border-gray-600"
                    >
                      <!-- En-tête du groupe -->
                      <button
                        type="button"
                        :dusk="`perm-group-${group.key}`"
                        @click="toggleGroup(group.key)"
                        class="flex w-full items-center justify-between bg-gray-50 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 hover:bg-gray-100 dark:bg-gray-700/50 dark:text-gray-300 dark:hover:bg-gray-700"
                      >
                        <span>{{ $t('temp_admin.perm_groups.' + group.key) }}</span>
                        <ChevronDownIcon
                          :class="openGroups.includes(group.key) ? 'rotate-180' : ''"
                          class="h-4 w-4 transition-transform"
                        />
                      </button>
                      <!-- Permissions du groupe -->
                      <div v-if="openGroups.includes(group.key)" class="divide-y divide-gray-100 dark:divide-gray-700">
                        <label
                          v-for="perm in group.permissions"
                          :key="perm.key"
                          class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/30"
                        >
                          <input
                            type="checkbox"
                            :value="perm.key"
                            v-model="modal.selectedPermissions"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600"
                          />
                          <span class="text-gray-800 dark:text-gray-200">{{ $t('temp_admin.perms.' + perm.i18nKey) }}</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </transition>
          </div>

          <!-- Durée -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('temp_admin.modal_expires') }}
            </label>
            <input
              v-model.number="modal.expiresInDays"
              type="number"
              min="1"
              max="365"
              class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Action à l'expiration -->
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('temp_admin.modal_action') }}
            </label>
            <select
              v-model="modal.expiryAction"
              class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option value="suspend">{{ $t('temp_admin.modal_action_suspend') }}</option>
              <option value="delete">{{ $t('temp_admin.modal_action_delete') }}</option>
            </select>
          </div>

          <div v-if="modal.error" class="rounded-3 border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ modal.error }}
          </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
          <button
            @click="modal.open = false"
            class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            dusk="modal-submit"
            @click="confirmPromote"
            :disabled="modal.loading || !modal.email || !modal.nom || modal.workspaceIds.length === 0"
            class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            <span v-if="modal.loading" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></span>
            {{ $t('temp_admin.modal_confirm') }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import {
  ShieldCheckIcon,
  NoSymbolIcon,
  XMarkIcon,
  EnvelopeIcon,
  ChevronDownIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/auth'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'
import { useWorkspace } from '@/composables/useWorkspace'
import { useStagger } from '@/composables/useAnimations'

const { t } = useI18n()
const authStore = useAuthStore()
const { currentWorkspace } = useWorkspace()
const { isDirecteur } = useWorkspacePermissions(currentWorkspace)
const { staggerRef, applyStagger } = useStagger(50)

const workspaceId = computed(() => authStore.currentWorkspaceId)
const accessDenied = computed(() => currentWorkspace.value && !isDirecteur.value)

const ownedWorkspaces = computed(() =>
  authStore.workspaces.filter((ws) => ws.owner_id === authStore.user?.id)
)

// Permissions par rôle — miroir de Permission::forRole() côté PHP.
const ROLE_PERMISSIONS = {
  observateur: [
    'workspaces.view', 'projets.view', 'activites.view', 'taches.view',
    'sous_taches.view', 'documents.view', 'evaluations.view_score',
    'evaluations.view_fiche', 'help_articles.read',
  ],
  cadre: [
    'workspaces.view', 'workspaces.invite_member',
    'projets.view', 'projets.manage_members',
    'activites.view', 'activites.edit', 'activites.create_task', 'activites.validate_n1',
    'taches.view', 'taches.edit', 'taches.create_subtask', 'taches.validate_n1', 'taches.comment', 'taches.inline_edit',
    'sous_taches.view', 'sous_taches.edit', 'sous_taches.delete', 'sous_taches.assign',
    'documents.view', 'documents.upload',
    'reports.view',
    'evaluations.view_pending', 'evaluations.view_score', 'evaluations.view_fiche',
    'evaluations.export_fiche', 'evaluations.view_dashboard',
    'search.scoped', 'help_articles.read',
  ],
  manager: [
    'workspaces.view', 'workspaces.create_project', 'workspaces.invite_member',
    'workspaces.remove_member', 'workspaces.manage_settings', 'workspaces.view_members',
    'projets.view', 'projets.edit', 'projets.delete', 'projets.manage_members',
    'activites.view', 'activites.edit', 'activites.delete', 'activites.create_task', 'activites.validate_n1',
    'taches.view', 'taches.edit', 'taches.delete', 'taches.create_subtask',
    'taches.validate_n2', 'taches.comment', 'taches.inline_edit',
    'sous_taches.view', 'sous_taches.edit', 'sous_taches.delete', 'sous_taches.assign',
    'documents.view', 'documents.upload', 'documents.delete', 'documents.share',
    'reports.view', 'reports.create',
    'evaluations.view_pending', 'evaluations.view_score', 'evaluations.view_fiche',
    'evaluations.export_fiche', 'evaluations.view_dashboard',
    'search.global', 'help_articles.read',
  ],
}

// Groupes de permissions pour l'accordéon.
const permissionGroups = [
  {
    key: 'workspace',
    permissions: [
      { key: 'workspaces.view', i18nKey: 'workspaces_view' },
      { key: 'workspaces.create_project', i18nKey: 'workspaces_create_project' },
      { key: 'workspaces.invite_member', i18nKey: 'workspaces_invite_member' },
      { key: 'workspaces.remove_member', i18nKey: 'workspaces_remove_member' },
      { key: 'workspaces.manage_settings', i18nKey: 'workspaces_manage_settings' },
      { key: 'workspaces.view_members', i18nKey: 'workspaces_view_members' },
    ],
  },
  {
    key: 'projects',
    permissions: [
      { key: 'projets.view', i18nKey: 'projets_view' },
      { key: 'projets.edit', i18nKey: 'projets_edit' },
      { key: 'projets.delete', i18nKey: 'projets_delete' },
      { key: 'projets.manage_members', i18nKey: 'projets_manage_members' },
    ],
  },
  {
    key: 'activities',
    permissions: [
      { key: 'activites.view', i18nKey: 'activites_view' },
      { key: 'activites.edit', i18nKey: 'activites_edit' },
      { key: 'activites.delete', i18nKey: 'activites_delete' },
      { key: 'activites.create_task', i18nKey: 'activites_create_task' },
      { key: 'activites.validate_n1', i18nKey: 'activites_validate_n1' },
    ],
  },
  {
    key: 'tasks',
    permissions: [
      { key: 'taches.view', i18nKey: 'taches_view' },
      { key: 'taches.edit', i18nKey: 'taches_edit' },
      { key: 'taches.delete', i18nKey: 'taches_delete' },
      { key: 'taches.comment', i18nKey: 'taches_comment' },
      { key: 'taches.create_subtask', i18nKey: 'taches_create_subtask' },
      { key: 'taches.inline_edit', i18nKey: 'taches_inline_edit' },
      { key: 'taches.validate_n1', i18nKey: 'taches_validate_n1' },
      { key: 'taches.validate_n2', i18nKey: 'taches_validate_n2' },
      { key: 'taches.submit_result', i18nKey: 'taches_submit_result' },
    ],
  },
  {
    key: 'subtasks',
    permissions: [
      { key: 'sous_taches.view', i18nKey: 'sous_taches_view' },
      { key: 'sous_taches.edit', i18nKey: 'sous_taches_edit' },
      { key: 'sous_taches.delete', i18nKey: 'sous_taches_delete' },
      { key: 'sous_taches.assign', i18nKey: 'sous_taches_assign' },
    ],
  },
  {
    key: 'documents',
    permissions: [
      { key: 'documents.view', i18nKey: 'documents_view' },
      { key: 'documents.upload', i18nKey: 'documents_upload' },
      { key: 'documents.delete', i18nKey: 'documents_delete' },
      { key: 'documents.share', i18nKey: 'documents_share' },
    ],
  },
  {
    key: 'reports',
    permissions: [
      { key: 'reports.view', i18nKey: 'reports_view' },
      { key: 'reports.create', i18nKey: 'reports_create' },
    ],
  },
  {
    key: 'evaluations',
    permissions: [
      { key: 'evaluations.view_pending', i18nKey: 'evaluations_view_pending' },
      { key: 'evaluations.view_score', i18nKey: 'evaluations_view_score' },
      { key: 'evaluations.view_fiche', i18nKey: 'evaluations_view_fiche' },
      { key: 'evaluations.export_fiche', i18nKey: 'evaluations_export_fiche' },
      { key: 'evaluations.view_dashboard', i18nKey: 'evaluations_view_dashboard' },
    ],
  },
  {
    key: 'search',
    permissions: [
      { key: 'search.global', i18nKey: 'search_global' },
      { key: 'search.scoped', i18nKey: 'search_scoped' },
    ],
  },
  {
    key: 'help',
    permissions: [
      { key: 'help_articles.read', i18nKey: 'help_articles_read' },
    ],
  },
]

const admins = ref([])
const loading = ref(false)
const error = ref('')
const successMessage = ref('')
const processingId = ref(null)
const openGroups = ref([])

const modal = ref({
  open: false,
  nom: '',
  email: '',
  workspaceIds: [],
  workspaceRole: 'observateur',
  customPermissions: false,
  selectedPermissions: [],
  expiresInDays: 30,
  expiryAction: 'suspend',
  loading: false,
  error: null,
})

const resetPermissionsToRole = () => {
  modal.value.selectedPermissions = [...(ROLE_PERMISSIONS[modal.value.workspaceRole] ?? [])]
}

const toggleGroup = (key) => {
  const idx = openGroups.value.indexOf(key)
  if (idx === -1) {
    openGroups.value.push(key)
  } else {
    openGroups.value.splice(idx, 1)
  }
}

const fetchAdmins = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/my-superadmins')
    admins.value = data.data ?? []
    await applyStagger()
  } catch (e) {
    error.value = e.response?.data?.message || t('temp_admin.load_error')
  } finally {
    loading.value = false
  }
}

const openPromoteModal = () => {
  modal.value = {
    open: true,
    nom: '',
    email: '',
    workspaceIds: [],
    workspaceRole: 'observateur',
    customPermissions: false,
    selectedPermissions: [...ROLE_PERMISSIONS.observateur],
    expiresInDays: 30,
    expiryAction: 'suspend',
    loading: false,
    error: null,
  }
  openGroups.value = []
}

const confirmPromote = async () => {
  if (!modal.value.email || !modal.value.nom || !modal.value.workspaceIds.length) return
  modal.value.loading = true
  modal.value.error = null

  const payload = {
    nom: modal.value.nom.trim(),
    email: modal.value.email.trim(),
    expires_in_days: modal.value.expiresInDays || 30,
    expiry_action: modal.value.expiryAction,
    workspace_ids: modal.value.workspaceIds,
    workspace_role: modal.value.customPermissions ? modal.value.workspaceRole : 'observateur',
  }

  // N'envoyer les permissions personnalisées que si le toggle est actif et qu'elles
  // diffèrent des permissions par défaut du rôle sélectionné.
  if (modal.value.customPermissions) {
    const roleDefaults = ROLE_PERMISSIONS[modal.value.workspaceRole] ?? []
    const selected = modal.value.selectedPermissions
    const isDifferent =
      selected.length !== roleDefaults.length ||
      selected.some((p) => !roleDefaults.includes(p))

    if (isDifferent) {
      payload.custom_permissions = selected
    }
  }

  try {
    await api.post('/admin/temp-admins', payload)
    modal.value.open = false
    showSuccess(t('temp_admin.create_success'))
    await fetchAdmins()
  } catch (e) {
    modal.value.error = e.response?.data?.message || t('temp_admin.create_error')
  } finally {
    modal.value.loading = false
  }
}

const sendCredentials = async (admin) => {
  processingId.value = admin.id
  try {
    await api.post(`/admin/temp-admins/${admin.id}/send-credentials`)
    showSuccess(t('temp_admin.credentials_sent'))
  } catch (e) {
    error.value = e.response?.data?.message || t('temp_admin.create_error')
  } finally {
    processingId.value = null
  }
}

const terminateAdmin = async (admin) => {
  if (!confirm(t('temp_admin.confirm_terminate'))) return
  processingId.value = admin.id
  error.value = ''
  try {
    await api.post(`/admin/superadmins/${admin.id}/terminate`)
    // Compte supprimé (delete) → retirer de la liste. Compte suspendu → rafraîchir.
    if (admin.admin_expiry_action === 'delete') {
      admins.value = admins.value.filter((a) => a.id !== admin.id)
    } else {
      await fetchAdmins()
    }
    showSuccess(t('temp_admin.terminate_success'))
  } catch (e) {
    error.value = e.response?.data?.message || t('temp_admin.terminate_error')
  } finally {
    processingId.value = null
  }
}

const reactivateAdmin = async (admin) => {
  if (!confirm(t('temp_admin.confirm_reactivate'))) return
  processingId.value = admin.id
  error.value = ''
  try {
    await api.post(`/admin/superadmins/${admin.id}/reactivate`)
    await fetchAdmins()
    showSuccess(t('temp_admin.reactivate_success'))
  } catch (e) {
    error.value = e.response?.data?.message || t('temp_admin.reactivate_error')
  } finally {
    processingId.value = null
  }
}

const roleClass = (role) => {
  if (role === 'manager') return 'bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400'
  if (role === 'cadre') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
  return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
}

const isExpired = (dateStr) => {
  if (!dateStr) return false
  return new Date(dateStr) < new Date()
}

const showSuccess = (msg) => {
  successMessage.value = msg
  setTimeout(() => {
    successMessage.value = ''
  }, 4000)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(() => {
  fetchAdmins()
})
</script>
