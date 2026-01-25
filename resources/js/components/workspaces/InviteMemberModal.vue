<!-- resources/js/components/workspaces/InviteMemberModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden"
        @click.stop>
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-brand-100 dark:bg-brand-900/30 rounded-lg">
              <MailIcon class="w-6 h-6 text-brand-600 dark:text-brand-400" />
            </div>
            <div>
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                Inviter des membres au workspace
              </h2>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Ajoutez de nouveaux membres à votre workspace
              </p>
            </div>
          </div>
          <button @click="handleClose"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <form @submit.prevent="handleSubmit" class="space-y-6">

            <!-- ✅ Message principal de résultat -->
            <div v-if="invitationResult" class="space-y-3">
              <!-- Message principal -->
              <div v-if="invitationResult.message" :class="[
                'p-4 rounded-lg border',
                invitationResult.success_count > 0
                  ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                  : invitationResult.warning_count > 0 && invitationResult.error_count === 0
                    ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                    : 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800'
              ]">
                <div class="flex items-start gap-3">
                  <svg :class="[
                    'w-5 h-5 mt-0.5 flex-shrink-0',
                    invitationResult.success_count > 0
                      ? 'text-green-600 dark:text-green-400'
                      : invitationResult.warning_count > 0 && invitationResult.error_count === 0
                        ? 'text-blue-600 dark:text-blue-400'
                        : 'text-orange-600 dark:text-orange-400'
                  ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="invitationResult.success_count > 0" stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="flex-1">
                    <p :class="[
                      'text-sm font-medium',
                      invitationResult.success_count > 0
                        ? 'text-green-900 dark:text-green-300'
                        : invitationResult.warning_count > 0 && invitationResult.error_count === 0
                          ? 'text-blue-900 dark:text-blue-300'
                          : 'text-orange-900 dark:text-orange-300'
                    ]">
                      {{ invitationResult.message }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Succès détaillés -->
              <div v-if="invitationResult.success_count > 0"
                class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-green-900 dark:text-green-300 mb-2">
                      ✓ {{ invitationResult.success_count }} invitation(s) envoyée(s) avec succès
                    </p>
                    <ul v-if="invitationResult.invitations && invitationResult.invitations.length > 0"
                      class="space-y-2">
                      <li v-for="inv in invitationResult.invitations" :key="inv.email" class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-green-600 rounded-full mt-1.5 flex-shrink-0"></span>
                        <div class="text-xs text-green-800 dark:text-green-400">
                          <div class="font-medium">{{ inv.email }}</div>
                          <div class="flex flex-wrap gap-1 mt-0.5">
                            <span v-if="inv.requires_registration"
                              class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 rounded text-xs">
                              Nouveau compte requis
                            </span>
                            <span v-else class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 rounded text-xs">
                              Utilisateur existant
                            </span>
                            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 rounded text-xs">
                              {{ getRoleLabel(inv.role) }}
                            </span>
                          </div>
                          <div v-if="inv.expires_at" class="mt-1 text-green-600 dark:text-green-500">
                            Expire le {{ formatDate(inv.expires_at) }}
                          </div>
                        </div>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Warnings: Invitations déjà en attente -->
              <div v-if="invitationResult.warnings && invitationResult.warnings.length > 0"
                class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-3">
                      ℹ {{ invitationResult.warning_count }} invitation(s) déjà en attente
                    </p>

                    <div v-for="warning in invitationResult.warnings" :key="warning.email" class="mb-3 last:mb-0">
                      <div class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-blue-600 rounded-full mt-1.5 flex-shrink-0"></span>
                        <div class="flex-1">
                          <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-300">
                              {{ warning.email }}
                              <span v-if="warning.user_name" class="text-xs font-normal">({{ warning.user_name
                                }})</span>
                            </span>
                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 rounded text-xs">
                              En attente
                            </span>
                          </div>
                          <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">{{ warning.message }}</p>

                          <!-- Actions pour les invitations en attente -->
                          <div v-if="warning.invitation_id" class="mt-2 flex items-center gap-2">
                            <button type="button" @click="resendInvitation(warning.invitation_id, warning.email)"
                              class="text-xs px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800/50 transition-colors flex items-center gap-1">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                              </svg>
                              Renvoyer
                            </button>
                            <button type="button" @click="cancelInvitation(warning.invitation_id, warning.email)"
                              class="text-xs px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-1">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12" />
                              </svg>
                              Annuler
                            </button>
                          </div>

                          <!-- Date d'expiration -->
                          <div v-if="warning.expires_at" class="mt-1 text-xs text-blue-600 dark:text-blue-500">
                            Expire le {{ formatDate(warning.expires_at) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Dans le template, après la section des warnings -->

              <!-- Section pour les membres déjà présents -->
              <div v-if="alreadyMemberErrors.length > 0"
                class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-amber-900 dark:text-amber-300 mb-3">
                      ⚠️ {{ alreadyMemberErrors.length }} membre(s) déjà présent(s)
                    </p>

                    <div v-for="err in alreadyMemberErrors" :key="err.email" class="mb-4 last:mb-0">
                      <div
                        class="flex items-start gap-2 p-3 bg-white dark:bg-amber-800/10 rounded-lg border border-amber-100 dark:border-amber-800/30">
                        <div class="flex-1">
                          <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                              <span class="text-sm font-medium text-amber-900 dark:text-amber-300">
                                {{ err.user_name || err.email }}
                              </span>
                              <span
                                class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded text-xs">
                                {{ err.existing_role_label || err.existing_role }}
                              </span>
                            </div>
                            <span class="text-xs text-amber-700 dark:text-amber-400">
                              Depuis {{ err.joined_at }}
                            </span>
                          </div>

                          <p class="text-xs text-amber-800 dark:text-amber-400 mb-3">
                            {{ err.message }}
                          </p>

                          <!-- Statistiques du membre -->
                          <div v-if="err.stats" class="grid grid-cols-2 gap-3 mb-3">
                            <div class="text-center p-2 bg-amber-50 dark:bg-amber-900/10 rounded">
                              <div class="text-lg font-semibold text-amber-700 dark:text-amber-300">
                                {{ err.stats.projects_count || 0 }}
                              </div>
                              <div class="text-xs text-amber-600 dark:text-amber-400">
                                Projet(s)
                              </div>
                            </div>
                            <div class="text-center p-2 bg-amber-50 dark:bg-amber-900/10 rounded">
                              <div class="text-lg font-semibold text-amber-700 dark:text-amber-300">
                                {{ err.stats.tasks_count || 0 }}
                              </div>
                              <div class="text-xs text-amber-600 dark:text-amber-400">
                                Tâche(s)
                              </div>
                            </div>
                          </div>

                          <!-- Actions suggérées -->
                          <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-amber-800 dark:text-amber-300">
                              Actions suggérées :
                            </span>
                            <button type="button" @click="viewMemberProfile(err.user_id)"
                              class="text-xs px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded hover:bg-amber-200 dark:hover:bg-amber-800/50 transition-colors">
                              Voir le profil
                            </button>
                            <button type="button" @click="updateMemberRole(err.user_id)"
                              class="text-xs px-3 py-1 bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 rounded hover:bg-brand-200 dark:hover:bg-brand-800/50 transition-colors">
                              Modifier le rôle
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section pour les autres erreurs (non already_member) -->
              <div v-if="otherErrors.length > 0"
                class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-red-900 dark:text-red-300 mb-3">
                      ✗ {{ otherErrors.length }} erreur(s) détectée(s)
                    </p>

                    <div v-for="err in otherErrors" :key="err.email" class="mb-3 last:mb-0">
                      <div class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-red-600 rounded-full mt-1.5 flex-shrink-0"></span>
                        <div class="flex-1">
                          <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">
                              {{ err.email }}
                              <span v-if="err.user_name" class="text-xs font-normal">({{ err.user_name }})</span>
                            </span>
                            <span v-if="err.type && err.type !== 'already_member'"
                              class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 rounded text-xs capitalize">
                              {{ formatErrorType(err.type) }}
                            </span>
                          </div>
                          <p class="text-xs text-red-700 dark:text-red-400 mt-1">{{ err.message }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Suggestions -->
              <div v-if="hasActionableItems"
                class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                  </svg>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-yellow-900 dark:text-yellow-300 mb-2">
                      Suggestions
                    </p>
                    <ul class="text-xs text-yellow-800 dark:text-yellow-400 space-y-1">
                      <li v-if="invitationResult.warning_count > 0" class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1 flex-shrink-0"></span>
                        <span>Vous pouvez renvoyer ou annuler les invitations en attente</span>
                      </li>
                      <li v-if="hasAlreadyMembers" class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1 flex-shrink-0"></span>
                        <span>Certains utilisateurs sont déjà membres du workspace</span>
                      </li>
                      <li v-if="invitationResult.error_count > 0" class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1 flex-shrink-0"></span>
                        <span>Vérifiez les adresses email avec des erreurs et réessayez</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Bouton pour réessayer -->
              <div v-if="invitationResult && (invitationResult.warning_count > 0 || invitationResult.error_count > 0)"
                class="flex justify-center mt-4">
                <button @click="resetAndRetry"
                  class="px-4 py-2 text-sm bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 rounded-lg hover:bg-brand-200 dark:hover:bg-brand-800/50 transition-colors flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Réessayer avec d'autres adresses
                </button>
              </div>
            </div>

            <!-- Email Addresses -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Adresses email <span class="text-red-500">*</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">
                  (une par ligne ou séparées par des virgules)
                </span>
              </label>

              <!-- Email Input Area -->
              <div class="relative">
                <textarea v-model="emailInput" @input="processEmailInput" @paste="handlePaste" rows="3"
                  placeholder="exemple@email.com, autre@domaine.com" :disabled="submitting"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none font-mono text-sm disabled:opacity-50"></textarea>
                <div class="absolute top-2 right-2">
                  <button type="button" @click="clearEmails"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Effacer tout"
                    :disabled="submitting">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Email Tags -->
              <div v-if="emailTags.length > 0" class="mt-3">
                <div class="flex flex-wrap gap-2">
                  <div v-for="(email, index) in emailTags" :key="index"
                    class="flex items-center gap-2 px-3 py-1.5 bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 rounded-full text-sm">
                    <span>{{ email }}</span>
                    <button type="button" @click="removeEmailTag(index)"
                      class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-200"
                      :disabled="submitting">
                      <XIcon class="w-3 h-3" />
                    </button>
                  </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                  {{ emailTags.length }} adresse(s) email à inviter
                </p>
              </div>

              <!-- Email Validation Errors -->
              <div v-if="emailErrors.length > 0" class="mt-3 space-y-1">
                <div v-for="(error, index) in emailErrors" :key="index"
                  class="flex items-center gap-2 text-xs text-red-600 dark:text-red-400">
                  <AlertCircleIcon class="w-3 h-3 flex-shrink-0" />
                  <span>{{ error }}</span>
                </div>
              </div>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Rôle dans le workspace <span class="text-red-500">*</span>
              </label>

              <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div v-for="role in availableRoles" :key="role.value" @click="form.role = role.value" :class="[
                  'p-4 border-2 rounded-lg cursor-pointer transition-all duration-200',
                  form.role === role.value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 dark:border-brand-400'
                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]" :disabled="submitting">
                  <div class="flex items-center gap-3 mb-2">
                    <div :class="[
                      'w-4 h-4 rounded-full border-2 flex items-center justify-center',
                      form.role === role.value
                        ? 'border-brand-500 bg-brand-500'
                        : 'border-gray-300 dark:border-gray-500'
                    ]">
                      <div v-if="form.role === role.value" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">
                      {{ role.label }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    {{ role.description }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Permissions Section -->
            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                Permissions supplémentaires
              </h4>
              <div class="space-y-3">
                <div v-for="permission in availablePermissions" :key="permission.key"
                  class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                  <input :id="permission.key" v-model="form.permissions[permission.key]" type="checkbox"
                    :disabled="permission.disabled || submitting"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50" />
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <label :for="permission.key" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ permission.label }}
                      </label>
                      <span v-if="permission.recommended"
                        class="px-1.5 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                        Recommandé
                      </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ permission.description }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Custom Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Message personnalisé
                <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                  (optionnel)
                </span>
              </label>
              <div class="relative">
                <textarea v-model="form.message" rows="3" :disabled="submitting"
                  placeholder="Bonjour, je vous invite à rejoindre notre workspace..."
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none disabled:opacity-50"
                  maxlength="500"></textarea>
                <div class="absolute bottom-2 right-2">
                  <span class="text-xs text-gray-400">
                    {{ form.message.length }}/500
                  </span>
                </div>
              </div>
            </div>

            <!-- Notification Options -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
              <div class="flex items-start gap-3">
                <input v-model="form.send_email" type="checkbox" id="send_email" :disabled="submitting"
                  class="mt-1 w-4 h-4 text-brand-600 bg-white border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50" />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <label for="send_email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Envoyer une invitation par email
                    </label>
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    Les membres recevront un email avec un lien pour rejoindre le workspace directement.
                    Désactivez cette option si vous préférez partager le lien manuellement.
                  </p>
                </div>
              </div>
            </div>

          </form>
        </div>

        <!-- Footer -->
        <div
          class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            <span v-if="emailTags.length > 0">
              {{ emailTags.length }} invitation(s) à envoyer
            </span>
            <span v-else>
              Prêt à inviter des membres
            </span>
          </div>
          <div class="flex items-center gap-3">
            <button type="button" @click="handleClose" :disabled="submitting"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50">
              {{ invitationResult ? 'Fermer' : 'Annuler' }}
            </button>
            <button @click="handleSubmit" :disabled="submitting || !canSubmit"
              class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
              <MailIcon v-if="!submitting" class="w-4 h-4" />
              <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              {{ submitting ? 'Envoi en cours...' : `Inviter (${emailTags.length})` }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { useInvitationPermissions } from '@/composables/useInvitationPermissions'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'
import { useToast } from "vue-toastification"
import {
  XIcon,
  MailIcon,
  AlertCircleIcon,
  CheckCircleIcon,
  TrashIcon
} from '@/icons'

const props = defineProps({
  workspaceId: {
    type: Number,
    required: true
  }
})

// Initialiser les permissions
const workspace = ref({ id: props.workspaceId })
const permissions = useWorkspacePermissions(workspace)
const invitationPermissions = useInvitationPermissions(workspace)
const toast = useToast()

const emit = defineEmits(['close', 'invited'])

const { inviteMembers, getAvailableRoles, getRoleLabel, resendInvitation: resendInvitationService, cancelInvitation: cancelInvitationService } = useWorkspace()

const submitting = ref(false)
const emailInput = ref('')
const emailTags = ref([])
const emailErrors = ref([])
const invitationResult = ref(null)

const availableRoles = getAvailableRoles()

// Computed property pour les permissions disponibles
const availablePermissions = computed(() => {
  return invitationPermissions.getAvailablePermissionsForRole(form.value.role)
})

const form = ref({
  role: 'member',
  message: '',
  permissions: {
    can_create_projects: true,
    can_view_all_projects: false,
    can_invite_members: false,
    can_manage_settings: false,
    can_transfer_ownership: false,
    can_delete_members: false
  },
  send_email: true
})

// Méthodes pour gérer les membres déjà présents
const viewMemberProfile = (userId) => {
  // Utilisez l'URL fournie par l'API
  // const memberUrl = `/workspaces/${props.workspaceId}/members/${userId}`;
  // window.open(memberUrl, '_blank');
  // toast.info('Ouvrir le profil du membre...');
};

const updateMemberRole = (userId) => {
  // Ouvrir le modal de modification de rôle
  // Utilisez l'URL fournie par l'API
  const editUrl = `/workspaces/${props.workspaceId}/members/${userId}`;
  // Vous pouvez émettre un événement ou rediriger
  emit('edit-member', userId);
  toast.info('Ouverture du formulaire de modification de rôle...');
};

// Dans la fonction formatErrorType, ajouter :
const formatErrorType = (type) => {
  const types = {
    'pending_invitation': 'Invitation en attente',
    'already_member': 'Membre déjà présent',
    'server_error': 'Erreur serveur',
    'validation_error': 'Erreur de validation'
  }
  return types[type] || type;
};

const canSubmit = computed(() => {
  return emailTags.value.length > 0 && form.value.role && !submitting.value
})

// Computed pour les erreurs
const hasCorrectableErrors = computed(() => {
  if (!invitationResult.value?.errors) return false
  return invitationResult.value.errors.some(err =>
    err.type === 'pending_invitation' || err.type === 'already_member'
  )
})

const hasPendingInvitations = computed(() => {
  if (!invitationResult.value?.errors) return false
  return invitationResult.value.errors.some(err => err.type === 'pending_invitation')
})

const hasActionableItems = computed(() => {
  if (!invitationResult.value) return false
  return (
    invitationResult.value.warning_count > 0 || 
    alreadyMemberErrors.value.length > 0 ||
    otherErrors.value.length > 0
  )
})

// Computed properties - METTRE À JOUR
const hasAlreadyMembers = computed(() => {
  if (!invitationResult.value?.errors) return false
  return invitationResult.value.errors.some(err => err.type === 'already_member')
})

// Ajouter cette computed property pour filtrer les erreurs par type
const alreadyMemberErrors = computed(() => {
  if (!invitationResult.value?.errors) return []
  return invitationResult.value.errors.filter(err => err.type === 'already_member')
})

const otherErrors = computed(() => {
  if (!invitationResult.value?.errors) return []
  return invitationResult.value.errors.filter(err => err.type !== 'already_member')
})

// Email validation function
const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Process email input
const processEmailInput = () => {
  const emails = emailInput.value
    .split(/[\n,]/)
    .map(email => email.trim())
    .filter(email => email.length > 0)

  emailTags.value = [...new Set(emails)] // Remove duplicates
  validateEmails()
}

// Handle paste event
const handlePaste = (event) => {
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text')
  emailInput.value = pastedData
  processEmailInput()
}

// Validate emails
const validateEmails = () => {
  emailErrors.value = []

  emailTags.value.forEach(email => {
    if (!isValidEmail(email)) {
      emailErrors.value.push(`"${email}" n'est pas une adresse email valide`)
    }
  })

  // Check for duplicate emails
  const uniqueEmails = new Set(emailTags.value)
  if (uniqueEmails.size !== emailTags.value.length) {
    emailErrors.value.push('Certaines adresses email sont en double')
  }
}

// Remove email tag
const removeEmailTag = (index) => {
  emailTags.value.splice(index, 1)
  updateEmailInput()
}

// Clear all emails
const clearEmails = () => {
  emailTags.value = []
  emailInput.value = ''
  emailErrors.value = []
}

// Update email input from tags
const updateEmailInput = () => {
  emailInput.value = emailTags.value.join(', ')
}

// Watch for role changes to update permissions
watch(() => form.value.role, (newRole) => {
  if (newRole) {
    const defaultPermissions = invitationPermissions.getDefaultPermissionsForRole(newRole)
    form.value.permissions = { ...defaultPermissions }

    // Validation optionnelle (pour le debug)
    const validation = invitationPermissions.validatePermissions(form.value.permissions, newRole)
    if (!validation.isValid) {
      console.warn('Permissions validation errors:', validation.errors)
    }
  }
}, { immediate: true })

// Helper functions
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// const formatErrorType = (type) => {
//   const types = {
//     'pending_invitation': 'Invitation en attente',
//     'already_member': 'Déjà membre',
//     'server_error': 'Erreur serveur'
//   }
//   return types[type] || type
// }

const handleClose = () => {
  if (invitationResult.value?.success_count > 0) {
    emit('invited')
  }
  emit('close')
}

const resetAndRetry = () => {
  invitationResult.value = null
  clearEmails()
  form.value.role = 'member'
  form.value.message = ''
  form.value.send_email = true
}

const resendInvitation = async (invitationId, email) => {
  try {
    const response = await resendInvitationService(props.workspaceId, invitationId)

    // Mettre à jour le warning
    const warningIndex = invitationResult.value.warnings.findIndex(w => w.email === email)
    if (warningIndex !== -1) {
      invitationResult.value.warnings[warningIndex].message = 'Invitation renvoyée'
      toast.success(`Invitation renvoyée à ${email}`)
    }
  } catch (err) {
    toast.error(`Erreur lors du renvoi: ${err.response?.data?.message || err.message}`)
  }
}

const cancelInvitation = async (invitationId, email) => {
  try {
    const response = await cancelInvitationService(props.workspaceId, invitationId)

    // Retirer le warning
    const warningIndex = invitationResult.value.warnings.findIndex(w => w.email === email)
    if (warningIndex !== -1) {
      invitationResult.value.warnings.splice(warningIndex, 1)
      invitationResult.value.warning_count--
      toast.success(`Invitation annulée pour ${email}`)
    }
  } catch (err) {
    toast.error(`Erreur lors de l'annulation: ${err.response?.data?.message || err.message}`)
  }
}

// La fonction handleSubmit améliorée
const handleSubmit = async () => {
  try {
    submitting.value = true
    invitationResult.value = null

    // 1. Validation de base des emails
    validateEmails()
    if (emailErrors.value.length > 0) {
      toast.error('Veuillez corriger les erreurs dans les adresses email')
      submitting.value = false
      return
    }

    if (emailTags.value.length === 0) {
      toast.error('Veuillez saisir au moins une adresse email valide')
      submitting.value = false
      return
    }

    if (!form.value.role) {
      toast.error('Veuillez sélectionner un rôle')
      submitting.value = false
      return
    }

    // 2. Validation des permissions
    const permissionValidation = invitationPermissions.validatePermissions(form.value.permissions, form.value.role)
    if (!permissionValidation.isValid) {
      toast.error(permissionValidation.errors.join(', '))
      submitting.value = false
      return
    }

    // 3. Envoi de l'invitation via l'API
    const response = await inviteMembers(props.workspaceId, {
      emails: emailTags.value,
      role: form.value.role,
      message: form.value.message,
      permissions: form.value.permissions,
      send_email: form.value.send_email
    })

    // 4. Traitement des résultats
    invitationResult.value = response.data

    // Afficher des notifications selon le résultat
    const { success_count, warning_count, error_count, message } = response.data

    if (success_count > 0 && warning_count === 0 && error_count === 0) {
      // Succès total
      toast.success(message || `${success_count} invitation(s) envoyée(s) avec succès`)
    } else if (success_count > 0 && (warning_count > 0 || error_count > 0)) {
      // Succès partiel
      toast.info(message || 'Invitations envoyées avec quelques remarques')
    } else if (success_count === 0 && warning_count > 0 && error_count === 0) {
      // Uniquement des invitations déjà en attente
      toast.info(message || 'Ces invitations sont déjà en attente de réponse')
    } else if (error_count > 0 && success_count === 0) {
      // Uniquement des erreurs
      toast.warning(message || 'Aucune invitation n\'a pu être envoyée')
    }

  } catch (err) {
    console.error('Erreur détaillée lors de l\'invitation:', err)

    // Gestion d'erreur détaillée
    let errorMessage = 'Erreur lors de l\'invitation'

    if (err.response) {
      // Erreur de réponse HTTP
      if (err.response.status === 422) {
        // Validation errors from Laravel
        const validationErrors = err.response.data.errors
        if (validationErrors) {
          const errorMessages = []
          Object.keys(validationErrors).forEach(key => {
            validationErrors[key].forEach(msg => errorMessages.push(msg))
          })
          errorMessage = errorMessages.join(', ')
        } else {
          errorMessage = err.response.data.message || 'Erreur de validation'
        }
      } else if (err.response.status === 403) {
        errorMessage = 'Vous n\'avez pas la permission d\'inviter des membres'
      } else if (err.response.status === 404) {
        errorMessage = 'Workspace non trouvé'
      } else {
        errorMessage = err.response.data?.message || `Erreur serveur (${err.response.status})`
      }
    } else if (err.request) {
      // Aucune réponse reçue
      errorMessage = 'Impossible de contacter le serveur. Vérifiez votre connexion.'
    } else {
      // Erreur de configuration
      errorMessage = err.message || 'Une erreur inattendue est survenue'
    }

    toast.error(errorMessage)
  } finally {
    submitting.value = false
  }
}

// Debug: Afficher l'état du formulaire
watch(() => form.value, (newForm) => {
  console.log('Form updated:', newForm)
}, { deep: true })

watch(() => emailTags.value, (newTags) => {
  console.log('Email tags updated:', newTags)
})
</script>