<!-- resources\js\components\projets\EditProjetMemberModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden"
        @click.stop>
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Modifier les permissions
          </h2>
          <button @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <!-- Member Info -->
          <div class="flex items-center gap-3 mb-6 p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-lg border border-gray-200 dark:border-gray-600">
            <div v-if="membre.avatar" class="w-14 h-14 rounded-full overflow-hidden flex-shrink-0 ring-2 ring-white dark:ring-gray-600">
              <img :src="membre.avatar" :alt="membre.nom" class="w-full h-full object-cover" />
            </div>
            <div v-else
              class="w-14 h-14 rounded-full bg-gradient-to-br from-brand-600 to-brand-700 flex items-center justify-center text-white font-bold text-lg flex-shrink-0 ring-2 ring-white dark:ring-gray-600">
              {{ getInitials(membre.nom) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-base font-semibold text-gray-900 dark:text-white truncate">
                  {{ membre.nom }}
                </span>
                <!-- Badges de rôles spéciaux -->
                <span v-if="membre.id === projetResponsableId"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  Responsable projet
                </span>
                <span v-if="workspaceOwnerId && membre.id === workspaceOwnerId"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                  </svg>
                  Propriétaire workspace
                </span>
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                {{ membre.email }}
              </div>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">Rôle actuel:</span>
                <span :class="getRoleBadgeClass(membre.role)" class="text-xs font-medium px-2 py-0.5 rounded">
                  {{ getRoleLabel(membre.role) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Avertissement pour les rôles spéciaux -->
          <div v-if="membre.id === projetResponsableId"
            class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mt-0.5 flex-shrink-0" fill="currentColor"
                viewBox="0 0 20 20">
                <path
                  d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              <div>
                <p class="text-sm font-medium text-purple-900 dark:text-purple-300">
                  Responsable du projet
                </p>
                <p class="text-xs text-purple-800 dark:text-purple-400 mt-1">
                  Le responsable du projet a automatiquement toutes les permissions. Pour modifier ces permissions, vous
                  devez d'abord changer le responsable du projet.
                </p>
              </div>
            </div>
          </div>

          <div v-if="workspaceOwnerId && membre.id === workspaceOwnerId"
            class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5 flex-shrink-0" fill="currentColor"
                viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
              </svg>
              <div>
                <p class="text-sm font-medium text-indigo-900 dark:text-indigo-300">
                  Propriétaire du workspace
                </p>
                <p class="text-xs text-indigo-800 dark:text-indigo-400 mt-1">
                  Le propriétaire du workspace a automatiquement tous les droits sur tous les projets et activités du workspace.
                </p>
              </div>
            </div>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-6"
            v-if="membre.id !== projetResponsableId && membre.id !== workspaceOwnerId">

            <!-- Role Selection avec description -->
            <div class="space-y-3">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Rôle <span class="text-red-500">*</span>
              </label>
              
              <div class="grid grid-cols-1 gap-3">
                <!-- Admin Role -->
                <label :class="[
                  'relative flex cursor-pointer rounded-lg border p-4 focus:outline-none transition-all',
                  form.role === 'admin'
                    ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500 dark:border-blue-400 ring-2 ring-blue-500'
                    : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'
                ]">
                  <input type="radio" v-model="form.role" value="admin" class="sr-only" @change="handleRoleChange">
                  <div class="flex items-start w-full">
                    <div class="flex items-center h-5">
                      <svg :class="form.role === 'admin' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400'"
                        class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="ml-3 flex-1">
                      <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Administrateur</span>
                        <span
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                          Tous les droits
                        </span>
                      </div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Accès complet au projet : peut modifier, supprimer, inviter des membres et gérer toutes les activités
                      </p>
                    </div>
                  </div>
                </label>

                <!-- Member Role -->
                <label :class="[
                  'relative flex cursor-pointer rounded-lg border p-4 focus:outline-none transition-all',
                  form.role === 'member'
                    ? 'bg-green-50 dark:bg-green-900/20 border-green-500 dark:border-green-400 ring-2 ring-green-500'
                    : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 hover:border-green-400 dark:hover:border-green-500'
                ]">
                  <input type="radio" v-model="form.role" value="member" class="sr-only" @change="handleRoleChange">
                  <div class="flex items-start w-full">
                    <div class="flex items-center h-5">
                      <svg :class="form.role === 'member' ? 'text-green-600 dark:text-green-400' : 'text-gray-400'"
                        class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="ml-3 flex-1">
                      <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Membre</span>
                        <span
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                          Édition
                        </span>
                      </div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Peut voir et modifier le projet, créer et éditer des activités, mais ne peut pas supprimer ou inviter
                      </p>
                    </div>
                  </div>
                </label>

                <!-- Viewer Role -->
                <label :class="[
                  'relative flex cursor-pointer rounded-lg border p-4 focus:outline-none transition-all',
                  form.role === 'viewer'
                    ? 'bg-gray-50 dark:bg-gray-700 border-gray-500 dark:border-gray-400 ring-2 ring-gray-500'
                    : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 hover:border-gray-400'
                ]">
                  <input type="radio" v-model="form.role" value="viewer" class="sr-only" @change="handleRoleChange">
                  <div class="flex items-start w-full">
                    <div class="flex items-center h-5">
                      <svg :class="form.role === 'viewer' ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400'"
                        class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="ml-3 flex-1">
                      <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Observateur</span>
                        <span
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                          Lecture seule
                        </span>
                      </div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Peut uniquement consulter le projet et ses activités, aucune modification possible
                      </p>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Permissions détaillées -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Permissions spécifiques du projet
                </label>
                <button type="button" @click="showPermissionDetails = !showPermissionDetails"
                  class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                  {{ showPermissionDetails ? 'Masquer' : 'Afficher' }} les détails
                </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Édition projet -->
                <div
                  class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <input v-model="form.can_edit" :disabled="form.role === 'viewer'" type="checkbox" id="edit_can_edit"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                  <div class="flex-1">
                    <label for="edit_can_edit" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                      Modifier le projet
                    </label>
                    <p v-if="showPermissionDetails" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de modifier les informations générales du projet (nom, description, dates, etc.)
                    </p>
                  </div>
                </div>

                <!-- Suppression projet -->
                <div
                  class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <input v-model="form.can_delete" :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox" id="edit_can_delete"
                    class="mt-1 w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500" />
                  <div class="flex-1">
                    <label for="edit_can_delete" class="text-sm font-medium text-red-700 dark:text-red-300 cursor-pointer">
                      Supprimer le projet
                    </label>
                    <p v-if="showPermissionDetails" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      ⚠️ Permission sensible : permet de supprimer définitivement le projet
                    </p>
                  </div>
                </div>

                <!-- Invitation membres -->
                <div
                  class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <input v-model="form.can_invite" :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox" id="edit_can_invite"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                  <div class="flex-1">
                    <label for="edit_can_invite" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                      Inviter des membres
                    </label>
                    <p v-if="showPermissionDetails" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet d'ajouter de nouveaux membres au projet
                    </p>
                  </div>
                </div>

                <!-- Retirer membres -->
                <div
                  class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <input v-model="form.can_delete_member" :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox" id="can_delete_member"
                    class="mt-1 w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500" />
                  <div class="flex-1">
                    <label for="can_delete_member"
                      class="text-sm font-medium text-orange-700 dark:text-orange-300 cursor-pointer">
                      Retirer des membres
                    </label>
                    <p v-if="showPermissionDetails" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de retirer des membres du projet
                    </p>
                  </div>
                </div>
              </div>

              <!-- Permissions activités -->
              <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Permissions sur les activités
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <!-- Création activités -->
                  <div
                    class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input v-model="form.can_create_activity" :disabled="form.role === 'viewer'" type="checkbox"
                      id="inv_can_create_activity"
                      class="mt-1 w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500" />
                    <div class="flex-1">
                      <label for="inv_can_create_activity"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                        Créer des activités
                      </label>
                      <p v-if="showPermissionDetails" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Permet de créer de nouvelles activités dans le projet
                      </p>
                    </div>
                  </div>

                  <!-- Édition activités -->
                  <div
                    class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input v-model="form.can_edit_activity" :disabled="form.role === 'viewer'" type="checkbox"
                      id="inv_can_edit_activity"
                      class="mt-1 w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" />
                    <div class="flex-1">
                      <label for="inv_can_edit_activity"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                        Modifier des activités
                      </label>
                      <p v-if="showPermissionDetails" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Permet de modifier les informations des activités existantes
                      </p>
                    </div>
                  </div>

                  <!-- Suppression activités -->
                  <div
                    class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input v-model="form.can_delete_activity"
                      :disabled="form.role === 'member' || form.role === 'viewer'" type="checkbox"
                      id="inv_can_delete_activity"
                      class="mt-1 w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500" />
                    <div class="flex-1">
                      <label for="inv_can_delete_activity"
                        class="text-sm font-medium text-red-700 dark:text-red-300 cursor-pointer">
                        Supprimer des activités
                      </label>
                      <p v-if="showPermissionDetails" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ⚠️ Permission sensible : réservée aux administrateurs
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Avertissement pour le rôle membre -->
              <div v-if="form.role === 'member'"
                class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start gap-2">
                  <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                      clip-rule="evenodd" />
                  </svg>
                  <p class="text-xs text-yellow-800 dark:text-yellow-300">
                    Les membres ne peuvent pas avoir les permissions de suppression (projet/activités) ou d'invitation de membres
                  </p>
                </div>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="error"
              class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="currentColor"
                  viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-red-800 dark:text-red-400">
                  {{ error }}
                </p>
              </div>
            </div>
          </form>

          <!-- Message si c'est un utilisateur spécial -->
          <div v-if="membre.id === projetResponsableId || (workspaceOwnerId && membre.id === workspaceOwnerId)" class="text-center py-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Les permissions de cet utilisateur ne peuvent pas être modifiées.
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div
          class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <button type="button" @click="$emit('close')"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            Annuler
          </button>
          <button v-if="membre.id !== projetResponsableId && membre.id !== workspaceOwnerId" @click="handleSubmit"
            :disabled="submitting"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
            <span v-if="submitting" class="animate-spin">⏳</span>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Mettre à jour
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useProjets } from '@/composables/useProjets'
import { XIcon } from '@/icons'
import api from '@/api/axios'

const props = defineProps({
  membre: {
    type: Object,
    required: true
  },
  projetId: {
    type: Number,
    required: true
  },
  projetResponsableId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'updated'])

const { updateMember } = useProjets()

const submitting = ref(false)
const error = ref(null)
const showPermissionDetails = ref(false)
const workspaceOwnerId = ref(null)

const form = ref({
  role: '',
  can_edit: false,
  can_delete: false,
  can_invite: false,
  can_delete_member: false,
  can_create_activity: false,
  can_edit_activity: false,
  can_delete_activity: false,
})

// ✅ Gestion intelligente des permissions basées sur le rôle
const handleRoleChange = () => {
  const role = form.value.role

  if (role === 'admin') {
    form.value.can_edit = true
    form.value.can_delete = true
    form.value.can_invite = true
    form.value.can_delete_member = true
    form.value.can_create_activity = true
    form.value.can_edit_activity = true
    form.value.can_delete_activity = true
  } else if (role === 'member') {
    form.value.can_edit = true
    form.value.can_delete = false
    form.value.can_invite = false
    form.value.can_delete_member = false
    form.value.can_create_activity = true
    form.value.can_edit_activity = true
    form.value.can_delete_activity = false
  } else if (role === 'viewer') {
    form.value.can_edit = false
    form.value.can_delete = false
    form.value.can_invite = false
    form.value.can_delete_member = false
    form.value.can_create_activity = false
    form.value.can_edit_activity = false
    form.value.can_delete_activity = false
  }
}

// ✅ Empêcher la modification manuelle des permissions pour les rôles restreints
watch(() => form.value.role, (newRole) => {
  if (newRole === 'member' || newRole === 'viewer') {
    if (form.value.can_delete) form.value.can_delete = false
    if (form.value.can_invite) form.value.can_invite = false
    if (form.value.can_delete_member) form.value.can_delete_member = false
    if (newRole === 'member' && form.value.can_delete_activity) {
      form.value.can_delete_activity = false
    }
  }
})

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    member: 'Membre',
    viewer: 'Observateur'
  }
  return labels[role] || role
}

const getRoleBadgeClass = (role) => {
  const classes = {
    owner: 'text-purple-800 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300',
    admin: 'text-blue-800 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300',
    member: 'text-green-800 bg-green-100 dark:bg-green-900/30 dark:text-green-300',
    viewer: 'text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-gray-300'
  }
  return classes[role] || classes.viewer
}

const handleSubmit = async () => {
  try {
    submitting.value = true
    error.value = null

    // ✅ Validation des permissions selon le rôle
    if (form.value.role === 'member' && (form.value.can_delete || form.value.can_invite || form.value.can_delete_member || form.value.can_delete_activity)) {
      error.value = 'Les membres ne peuvent pas avoir les permissions de suppression ou d\'invitation'
      return
    }

    if (form.value.role === 'viewer' && Object.keys(form.value).some(
      key => key.startsWith('can_') && form.value[key]
    )) {
      error.value = 'Les observateurs ne peuvent avoir aucune permission'
      return
    }

    await updateMember(props.projetId, props.membre.id, form.value)
    emit('updated')
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue'
    console.error('Error updating member:', err)
  } finally {
    submitting.value = false
  }
}

// ✅ Charger les informations sur le workspace owner
const loadWorkspaceInfo = async () => {
  try {
    const response = await api.get(`/projets/${props.projetId}`)
    workspaceOwnerId.value = response.data.data.workspace?.owner_id
  } catch (error) {
    console.error('Error loading workspace info:', error)
  }
}

// Initialize form with current member data
onMounted(async () => {
  await loadWorkspaceInfo()
  
  form.value = {
    role: props.membre.role || 'member',
    can_edit: props.membre.can_edit || false,
    can_delete: props.membre.can_delete || false,
    can_invite: props.membre.can_invite || false,
    can_delete_member: props.membre.can_delete_member || false,
    can_create_activity: props.membre.can_create_activity || false,
    can_edit_activity: props.membre.can_edit_activity || false,
    can_delete_activity: props.membre.can_delete_activity || false,
  }
})
</script>