<!-- resources\js\components\projets\InviteExternalMemberModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden"
        @click.stop>
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Ajouter des membres au projet
          </h2>
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

              <!-- Erreurs détaillées -->
              <div v-if="invitationResult.errors && invitationResult.errors.length > 0"
                class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-red-900 dark:text-red-300 mb-3">
                      ✗ {{ invitationResult.error_count }} erreur(s) détectée(s)
                    </p>

                    <div v-for="err in invitationResult.errors" :key="err.email" class="mb-3 last:mb-0">
                      <div class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-red-600 rounded-full mt-1.5 flex-shrink-0"></span>
                        <div class="flex-1">
                          <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">
                              {{ err.email }}
                              <span v-if="err.user_name" class="text-xs font-normal">({{ err.user_name }})</span>
                            </span>
                            <span v-if="err.type"
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

              <div v-if="invitationResult && invitationResult.added_members?.length > 0"
                class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-green-900 dark:text-green-300 mb-2">
                      ✓ {{ invitationResult.direct_add_count }} membre(s) ajouté(s) directement
                    </p>
                    <ul class="space-y-2">
                      <li v-for="member in invitationResult.added_members" :key="member.user_id"
                        class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-green-600 rounded-full mt-1.5"></span>
                        <div class="text-xs text-green-800 dark:text-green-400">
                          <div class="font-medium">{{ member.user_name }} ({{ member.email }})</div>
                          <div class="flex gap-1 mt-0.5">
                            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 rounded text-xs">
                              {{ getRoleLabel(member.role) }}
                            </span>
                            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 rounded text-xs">
                              Ajouté immédiatement
                            </span>
                          </div>
                          <div class="mt-1 text-green-600">
                            ✉️ Notification envoyée
                          </div>
                        </div>
                      </li>
                    </ul>
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
                        <span>Certains utilisateurs sont déjà membres du projet</span>
                      </li>
                      <li v-if="invitationResult.error_count > 0" class="flex items-start gap-2">
                        <span class="w-1.5 h-1.5 bg-yellow-600 rounded-full mt-1 flex-shrink-0"></span>
                        <span>Vérifiez les adresses email avec des erreurs et réessayez</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Message d'erreur général -->
            <div v-if="error"
              class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none"
                  stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                  <p class="text-sm text-red-800 dark:text-red-400">
                    {{ error }}
                  </p>
                </div>
              </div>
            </div>


            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
              <nav class="flex space-x-8">
                <button type="button" @click="activeTab = 'workspace'" :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                  activeTab === 'workspace'
                    ? 'border-brand-600 text-brand-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                ]">
                  <div class="flex items-center gap-2">
                    <UsersIcon class="w-5 h-5" />
                    Membres du workspace ({{ workspaceMembers.length }})
                  </div>
                </button>
                <button type="button" @click="activeTab = 'email'" :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                  activeTab === 'email'
                    ? 'border-brand-600 text-brand-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                ]">
                  <div class="flex items-center gap-2">
                    <MailIcon class="w-5 h-5" />
                    Par email
                  </div>
                </button>
              </nav>
            </div>

            <!-- Workspace Members Tab -->
            <div v-if="activeTab === 'workspace'">
              <!-- Search -->
              <div class="relative mb-4">
                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input v-model="searchTerm" type="text" placeholder="Rechercher par nom ou email..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
              </div>

              <!-- Members List -->
              <div class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-80 overflow-y-auto">
                <div v-if="loadingMembers" class="flex justify-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
                </div>

                <div v-else-if="filteredWorkspaceMembers.length === 0" class="text-center py-8">
                  <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
                  <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Aucun membre disponible
                  </p>
                </div>

                <div v-else>
                  <label v-for="member in filteredWorkspaceMembers" :key="member.id"
                    class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <input type="checkbox" :value="member.id" v-model="selectedMembers"
                      class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                    <div v-if="member.avatar" class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
                      <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
                    </div>
                    <div v-else
                      class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium flex-shrink-0">
                      {{ getInitials(member.nom) }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ member.nom }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ member.email }}
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <p v-if="selectedMembers.length > 0" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ selectedMembers.length }} membre(s) sélectionné(s)
              </p>
            </div>

            <!-- Email Tab -->
            <div v-if="activeTab === 'email'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Adresses email <span class="text-red-500">*</span>
              </label>
              <textarea v-model="emailsInput" rows="4"
                placeholder="Entrez une ou plusieurs adresses email (séparées par des virgules ou des retours à la ligne)&#10;Exemple: jean@example.com, marie@example.com"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"></textarea>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Les utilisateurs externes recevront un email pour créer un compte
              </p>

              <!-- Parsed emails preview -->
              <div v-if="parsedEmails.length > 0" class="mt-3 flex flex-wrap gap-2">
                <span v-for="(email, index) in parsedEmails" :key="index"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-brand-100 dark:bg-brand-900/30 text-brand-800 dark:text-brand-300 rounded-full text-sm">
                  {{ email }}
                  <button type="button" @click="removeEmail(index)"
                    class="hover:text-brand-900 dark:hover:text-brand-100">
                    <XIcon class="w-3 h-3" />
                  </button>
                </span>
              </div>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Rôle <span class="text-red-500">*</span>
              </label>
              <select v-model="form.role" required @change="handleRoleChange"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <option value="">Sélectionner un rôle</option>
                <option value="admin">Administrateur - Tous les droits</option>
                <option value="member">Membre - Peut voir et éditer le projet</option>
                <option value="viewer">Observateur - Lecture seule</option>
              </select>
            </div>

            <!-- Permissions -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Permissions spécifiques
              </label>
              <div class="space-y-3">
                <div class="flex items-start gap-3">
                  <input v-model="form.can_edit" :disabled="form.role === 'viewer'" type="checkbox" id="inv_can_edit"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                  <div>
                    <label for="inv_can_edit" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut modifier le projet
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de modifier les informations du projet
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input v-model="form.can_delete" :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox" id="inv_can_delete"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                  <div>
                    <label for="inv_can_delete" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut supprimer le projet
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de supprimer un projet
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input v-model="form.can_invite" :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox" id="inv_can_invite"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                  <div>
                    <label for="inv_can_invite" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut inviter des membres
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet d'ajouter des membres au projet
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input v-model="form.can_delete_member" :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox" id="can_delete_member"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                  <div>
                    <label for="can_delete_member" class="text-sm font-medium text-orange-700 dark:text-orange-300">
                      Peut supprimer des membres
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de retirer des membres du projet
                    </p>
                  </div>
                </div>
              </div>
              <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Permissions sur les activités
                </label>

                <div class="space-y-3">
                  <div class="flex items-start gap-3">
                    <input v-model="form.can_create_activity" :disabled="form.role === 'viewer'" type="checkbox"
                      id="inv_can_create_activity"
                      class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                    <div>
                      <label for="inv_can_create_activity" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Peut créer des activités
                      </label>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Permet de créer de nouvelles activités dans le projet
                      </p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3">
                    <input v-model="form.can_edit_activity" :disabled="form.role === 'viewer'" type="checkbox"
                      id="inv_can_edit_activity"
                      class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                    <div>
                      <label for="inv_can_edit_activity" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Peut modifier des activités
                      </label>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Permet de modifier les informations des activités
                      </p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3">
                    <input v-model="form.can_delete_activity"
                      :disabled="form.role === 'member' || form.role === 'viewer'" type="checkbox"
                      id="inv_can_delete_activity"
                      class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
                    <div>
                      <label for="inv_can_delete_activity" class="text-sm font-medium text-red-700 dark:text-red-300">
                        Peut supprimer des activités
                      </label>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Permet de supprimer des activités (réservé aux admins)
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Avertissement pour les membres -->
                <div v-if="form.role === 'member'"
                  class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <p class="text-xs text-blue-800 dark:text-blue-300">
                    ℹ️ Les membres peuvent créer et modifier des activités, mais seuls les admins peuvent les supprimer
                  </p>
                </div>
              </div>
              <!-- Avertissement pour le rôle membre -->
              <div v-if="form.role === 'member'"
                class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-xs text-yellow-800 dark:text-yellow-300">
                  ⚠️ Les membres ne peuvent pas avoir les permissions de suppression ou d'invitation
                </p>
              </div>
            </div>

            <!-- Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Message personnel (optionnel)
              </label>
              <textarea v-model="form.message" rows="3" placeholder="Ajouter un message pour les invités..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"></textarea>
            </div>


          </form>
        </div>

        <!-- Footer -->
        <div
          class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <button type="button" @click="$emit('close')"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            Annuler
          </button>
          <button @click="handleSubmit" :disabled="submitting || !canSubmit"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
            <span v-if="submitting" class="animate-spin">⏳</span>
            Ajouter {{ totalInvitations }} membre(s)
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { useProjetInvitations } from '@/composables/useProjetInvitations'
import { useToast } from "vue-toastification"
import { XIcon, SearchIcon, UsersIcon, MailIcon } from '@/icons'
import api from '@/api/axios'

const props = defineProps({
  projetId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'invited'])

const { fetchMembers } = useWorkspace()
const { inviteMembers } = useProjetInvitations()
const toast = useToast()

const activeTab = ref('workspace')
const loadingMembers = ref(false)
const submitting = ref(false)
const error = ref(null)
const invitationResult = ref(null)

const workspaceMembers = ref([])
const selectedMembers = ref([])
const searchTerm = ref('')
const emailsInput = ref('')

const form = ref({
  role: '',
  can_edit: false,
  can_delete: false,
  can_invite: false,
  can_delete_member: false,
  can_create_activity: false,
  can_edit_activity: false,
  can_delete_activity: false,
  message: ''
})

// Computed properties améliorées
const filteredWorkspaceMembers = computed(() => {
  if (!searchTerm.value) return workspaceMembers.value

  const term = searchTerm.value.toLowerCase()
  return workspaceMembers.value.filter(member =>
    member.nom.toLowerCase().includes(term) ||
    member.email.toLowerCase().includes(term)
  )
})

const parsedEmails = computed(() => {
  if (!emailsInput.value.trim()) return []

  return emailsInput.value
    .split(/[\n,;]+/)
    .map(email => email.trim())
    .filter(email => email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
})

const totalInvitations = computed(() => {
  if (activeTab.value === 'workspace') {
    return selectedMembers.value.length
  } else {
    return parsedEmails.value.length
  }
})

const canSubmit = computed(() => {
  return form.value.role && totalInvitations.value > 0
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
    invitationResult.value.warning_count > 0 || invitationResult.value.error_count > 0
  )
})
const hasAlreadyMembers = computed(() => {
  if (!invitationResult.value?.errors) return false
  return invitationResult.value.errors.some(err => err.type === 'already_member')
})

// Methods
const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

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

const formatErrorType = (type) => {
  const types = {
    'pending_invitation': 'Invitation en attente',
    'already_member': 'Déjà membre',
    'server_error': 'Erreur serveur'
  }
  return types[type] || type
}

const removeEmail = (index) => {
  const emails = parsedEmails.value
  emails.splice(index, 1)
  emailsInput.value = emails.join(', ')
}

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

const handleClose = () => {
  if (invitationResult.value?.success_count > 0) {
    emit('invited')
  }
  emit('close')
}

const resetAndRetry = () => {
  invitationResult.value = null
  error.value = null

  // Réinitialiser les sélections
  if (activeTab.value === 'workspace') {
    selectedMembers.value = []
  } else {
    emailsInput.value = ''
  }
}

const resendInvitation = async (invitationId, email) => {
  try {
    const response = await api.post(`/projets/${props.projetId}/invitations/${invitationId}/resend`)
    toast.success(`Invitation renvoyée à ${email}`)

    // Mettre à jour l'état de l'erreur
    const errorIndex = invitationResult.value.errors.findIndex(err => err.email === email)
    if (errorIndex !== -1) {
      invitationResult.value.errors.splice(errorIndex, 1)
      invitationResult.value.error_count--
    }
  } catch (err) {
    toast.error(`Erreur lors du renvoi: ${err.response?.data?.message || err.message}`)
  }
}

const cancelInvitation = async (invitationId, email) => {
  try {
    const response = await api.delete(`/projets/${props.projetId}/invitations/${invitationId}/cancel`)
    toast.success(`Invitation annulée pour ${email}`)

    // Mettre à jour l'état de l'erreur
    const errorIndex = invitationResult.value.errors.findIndex(err => err.email === email)
    if (errorIndex !== -1) {
      invitationResult.value.errors.splice(errorIndex, 1)
      invitationResult.value.error_count--
    }
  } catch (err) {
    toast.error(`Erreur lors de l'annulation: ${err.response?.data?.message || err.message}`)
  }
}

const handleSubmit = async () => {
  try {
    submitting.value = true
    error.value = null
    invitationResult.value = null

    if (!form.value.role) {
      error.value = 'Veuillez sélectionner un rôle'
      return
    }

    // Validation des permissions selon le rôle
    if (form.value.role === 'member' && (
      form.value.can_delete ||
      form.value.can_invite ||
      form.value.can_delete_member ||
      form.value.can_delete_activity
    )) {
      error.value = 'Les membres ne peuvent pas avoir les permissions de suppression ou d\'invitation'
      return
    }

    if (form.value.role === 'viewer' && Object.keys(form.value).some(
      key => key.startsWith('can_') && form.value[key]
    )) {
      error.value = 'Les observateurs ne peuvent avoir aucune permission'
      return
    }

    let emails = []

    if (activeTab.value === 'workspace') {
      emails = workspaceMembers.value
        .filter(m => selectedMembers.value.includes(m.id))
        .map(m => m.email)
    } else {
      emails = parsedEmails.value
    }

    if (emails.length === 0) {
      error.value = 'Veuillez sélectionner au moins un membre ou entrer un email'
      return
    }

    const result = await inviteMembers(props.projetId, {
      emails,
      role: form.value.role,
      can_edit: form.value.can_edit,
      can_delete: form.value.can_delete,
      can_invite: form.value.can_invite,
      can_delete_member: form.value.can_delete_member,
      can_create_activity: form.value.can_create_activity,  // NOUVEAU
      can_edit_activity: form.value.can_edit_activity,      // NOUVEAU
      can_delete_activity: form.value.can_delete_activity,  // NOUVEAU
      message: form.value.message,
      send_email: true
    })

    invitationResult.value = result.data

    // Afficher les notifications
    const {
      success_count,
      direct_add_count,
      invitation_count,
      warning_count,
      error_count,
      message
    } = result.data

    if (direct_add_count > 0) {
      toast.success(`${direct_add_count} membre(s) ajouté(s) directement au projet`)
    }

    if (invitation_count > 0) {
      toast.success(`${invitation_count} invitation(s) envoyée(s) par email`)
    }

    if (success_count > 0 && (warning_count > 0 || error_count > 0)) {
      toast.info(message || 'Certaines actions ont échoué')
    } else if (success_count === 0 && warning_count > 0) {
      toast.info(message || 'Ces invitations sont déjà en attente')
    } else if (error_count > 0 && success_count === 0) {
      toast.warning(message || 'Aucune invitation n\'a pu être envoyée')
    }

  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue'
    toast.error(error.value)
    console.error('Error inviting members:', err)
  } finally {
    submitting.value = false
  }
}

// Load workspace members
onMounted(async () => {
  try {
    loadingMembers.value = true

    const projetResponse = await api.get(`/projets/${props.projetId}`)
    const workspaceId = projetResponse.data.data.workspace_id

    if (workspaceId) {
      const allMembers = await fetchMembers(workspaceId)
      const projetMembers = projetResponse.data.data.members || []
      const projetMemberIds = projetMembers.map(m => m.id)

      workspaceMembers.value = allMembers.filter(m => !projetMemberIds.includes(m.id))
    }
  } catch (err) {
    console.error('Error loading members:', err)
  } finally {
    loadingMembers.value = false
  }
})

watch(() => form.value.role, (newRole) => {
  if (newRole === 'member' || newRole === 'viewer') {
    if (form.value.can_delete) form.value.can_delete = false
    if (form.value.can_invite) form.value.can_invite = false
    if (form.value.can_delete_member) form.value.can_delete_member = false
  }
})
</script>