<!-- resources\js\components\layout\header\NotificationDetailModal.vue -->
<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 "
        @click.self="close">
        <div
          class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-3 max-h-[90vh] overflow-hidden border border-gray-200 dark:border-gray-800"
          @click.stop>
          <!-- Header -->
          <div class="px-6 py-5 flex items-start gap-4" :class="getHeaderGradientClass()">
            <div class="shrink-0 w-12 h-12 rounded-3 flex items-center justify-center bg-white/20 border border-white/30">
              <i :class="['fas', icon, 'text-white', 'text-xl']"></i>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-start gap-2 mb-1">
                <h3 class="text-lg font-bold text-white flex-1 truncate">
                  {{ notification?.title }}
                </h3>
                <span v-if="getNotificationBadge()"
                  class="shrink-0 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-white/20 text-white border border-white/30">
                  {{ getNotificationBadge() }}
                </span>
              </div>
              <p class="text-xs text-white/80 flex items-center gap-1.5">
                <i class="fas fa-clock"></i>
                {{ notification?.time_ago }}
              </p>
            </div>

            <button @click="close"
              class="shrink-0 p-2 rounded-3 hover:bg-white/20 text-white transition-colors border border-white/20">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Content -->
          <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">

            <!-- ========== RÉSULTATS SOUMIS ========== -->
            <div v-if="isResultatNotification" class="space-y-4">
              <div class="rounded-3 border border-brand-200 dark:border-brand-800/50 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-3 bg-brand-500 flex items-center justify-center shrink-0">
                    <i class="fas fa-tasks text-white"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wide mb-0.5">Tâche</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white truncate">{{ notification.data.tache_titre }}</p>
                    <div v-if="notification.data.taux_realisation" class="flex items-center gap-2 mt-1">
                      <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-success-500 rounded-full transition-all duration-500"
                          :style="{ width: `${notification.data.taux_realisation}%` }"></div>
                      </div>
                      <span class="text-xs font-bold text-success-600 dark:text-success-400">
                        {{ notification.data.taux_realisation }}%
                      </span>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-brand-100 dark:bg-brand-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user text-brand-600 dark:text-brand-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Auteur</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.auteur_nom || notification.data.assignee_nom }}</p>
                    </div>
                  </div>
                  <div v-if="notification.data.validateur_nom" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-success-100 dark:bg-success-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user-check text-success-600 dark:text-success-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Validé par</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.validateur_nom }}</p>
                    </div>
                  </div>
                </div>

                <div v-if="notification.data.commentaire"
                  class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-brand-400">
                  <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 mb-1 uppercase tracking-wide">Commentaire</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300 italic leading-relaxed">"{{ notification.data.commentaire }}"</p>
                </div>
              </div>

              <div v-if="notification.type === 'resultat_valide_n2'" class="flex items-center justify-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-3 border border-success-300 dark:border-success-700 bg-success-50 dark:bg-success-500/10">
                  <i class="fas fa-trophy text-warning-500"></i>
                  <span class="text-sm font-bold text-success-700 dark:text-success-300">Validation complète : N1 ✓ + N2 ✓</span>
                </div>
              </div>
            </div>

            <!-- ========== INVITATIONS PROJET ========== -->
            <div v-else-if="notification?.type === 'projet_invitation'" class="space-y-4">
              <div class="rounded-3 border border-purple-200 dark:border-purple-800/50 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-3 bg-purple-500 flex items-center justify-center shrink-0">
                    <i class="fas fa-project-diagram text-white"></i>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide mb-0.5">Projet</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ notification.data.projet_nom }}</p>
                  </div>
                </div>

                <div class="space-y-2">
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user text-purple-600 dark:text-purple-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Invité par</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.inviter_nom }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user-tag text-purple-600 dark:text-purple-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Rôle proposé</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ getRoleLabel(notification.data.role) }}</p>
                    </div>
                  </div>
                  <div v-if="notification.data.message" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-purple-400">
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-1 uppercase tracking-wide">Message personnel</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 italic leading-relaxed">"{{ notification.data.message }}"</p>
                  </div>
                </div>
              </div>

              <div v-if="notification.data.expires_at"
                class="flex items-center gap-2 p-3 rounded-3 bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-700">
                <i class="fas fa-clock text-warning-500"></i>
                <span class="text-sm font-semibold text-warning-700 dark:text-warning-300">Expire {{ formatExpirationDate(notification.data.expires_at) }}</span>
              </div>

              <div v-if="notification.data.is_pending" class="flex items-center justify-center">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-warning-50 dark:bg-warning-500/10 text-warning-700 dark:text-warning-400 border border-warning-300 dark:border-warning-700">
                  <i class="fas fa-hourglass-half animate-pulse"></i>
                  En attente de réponse
                </span>
              </div>
            </div>

            <!-- ========== INVITATIONS WORKSPACE ========== -->
            <div v-else-if="notification?.type === 'workspace_invitation'" class="space-y-4">
              <div class="rounded-3 border border-brand-200 dark:border-brand-800/50 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3 mb-4">
                  <div v-if="notification.data.workspace_logo"
                    class="w-10 h-10 rounded-3 overflow-hidden border border-brand-300 dark:border-brand-700 shrink-0">
                    <img :src="notification.data.workspace_logo" :alt="notification.data.workspace_name" class="w-full h-full object-cover" />
                  </div>
                  <div v-else
                    class="w-10 h-10 rounded-3 bg-brand-500 flex items-center justify-center border border-brand-600 shrink-0">
                    <span class="text-white font-bold text-sm">{{ getWorkspaceInitials(notification.data.workspace_name) }}</span>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wide mb-0.5">Workspace</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ notification.data.workspace_name }}</p>
                  </div>
                </div>

                <div class="space-y-2">
                  <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                      <strong class="text-gray-900 dark:text-white">{{ notification.data.inviter_name }}</strong> vous invite à rejoindre ce workspace
                    </p>
                  </div>
                  <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      Rôle proposé : <strong class="text-gray-900 dark:text-white">{{ getRoleLabel(notification.data.role) }}</strong>
                    </p>
                  </div>
                  <div v-if="notification.data.invitation_message" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-brand-400">
                    <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 mb-1 uppercase tracking-wide">Message</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 italic leading-relaxed">"{{ notification.data.invitation_message }}"</p>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between text-sm gap-3">
                <span v-if="notification.data.expires_at" class="flex items-center gap-1.5 text-warning-600 dark:text-warning-400 font-semibold">
                  <i class="fas fa-clock"></i>
                  Expire {{ formatExpirationDate(notification.data.expires_at) }}
                </span>
                <span v-if="notification.data.is_pending" class="flex items-center gap-1.5 text-warning-600 dark:text-warning-400 font-semibold">
                  <i class="fas fa-hourglass-half animate-pulse"></i>
                  En attente de réponse
                </span>
              </div>
            </div>

            <!-- ========== CHANGEMENT DE RESPONSABLE ========== -->
            <div v-else-if="notification?.type === 'responsable_changed'" class="space-y-4">
              <div class="rounded-3 border border-purple-200 dark:border-purple-800/50 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-3 bg-purple-600 flex items-center justify-center shrink-0">
                    <i class="fas fa-user-shield text-white"></i>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide mb-0.5">Activité</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ notification.data.activite_nom }}</p>
                  </div>
                </div>

                <div class="space-y-2">
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-error-100 dark:bg-error-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user-times text-error-600 dark:text-error-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Ancien responsable</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.old_responsable }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-success-100 dark:bg-success-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user-check text-success-600 dark:text-success-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Nouveau responsable</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.new_responsable }}</p>
                    </div>
                  </div>
                  <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-purple-400">
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-1 uppercase tracking-wide">Détails</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ notification.data.message }}</p>
                  </div>
                </div>
              </div>

              <div class="flex justify-center">
                <button @click="$router.push(`/activites/${notification.data.activite_id}`)"
                  class="px-5 py-2.5 text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-3 transition-colors">
                  Voir l'activité
                </button>
              </div>
            </div>

            <!-- ========== TÂCHES ASSIGNÉES ========== -->
            <div v-else-if="notification?.type === 'task_assigned'" class="space-y-4">
              <div class="rounded-3 border border-brand-200 dark:border-brand-800/50 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-3 bg-brand-500 flex items-center justify-center shrink-0">
                    <i class="fas fa-tasks text-white"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wide mb-0.5">Tâche assignée</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white truncate">{{ notification.data.tache_titre }}</p>
                    <span v-if="notification.data.tache_priorite"
                      class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                      :class="{
                        'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-400': notification.data.tache_priorite === 'haute',
                        'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400': notification.data.tache_priorite === 'moyenne',
                        'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400': notification.data.tache_priorite === 'basse'
                      }">
                      Priorité {{ notification.data.tache_priorite }}
                    </span>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-brand-100 dark:bg-brand-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user text-brand-600 dark:text-brand-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Assigné par</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.assigned_by_nom }}</p>
                    </div>
                  </div>
                  <div v-if="notification.data.tache_echeance" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-warning-100 dark:bg-warning-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-calendar-alt text-warning-600 dark:text-warning-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Échéance</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(notification.data.tache_echeance) }}</p>
                    </div>
                  </div>
                  <div v-if="notification.data.activite_nom" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-folder text-purple-600 dark:text-purple-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Activité</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.activite_nom }}</p>
                    </div>
                  </div>
                  <div v-if="notification.data.projet_nom" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-project-diagram text-purple-600 dark:text-purple-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Projet</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.projet_nom }}</p>
                    </div>
                  </div>
                </div>

                <div v-if="notification.data.tache_description" class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-brand-400">
                  <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 mb-1 uppercase tracking-wide">Description</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ notification.data.tache_description }}</p>
                </div>
              </div>
            </div>

            <!-- ========== FICHIER AJOUTÉ ========== -->
            <div v-else-if="notification?.type === 'task_file_added'" class="space-y-4">
              <div class="rounded-3 border border-success-200 dark:border-success-800/50 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-3 bg-success-500 flex items-center justify-center shrink-0">
                    <i :class="['fas', notification.data.file_icon, 'text-white']"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-success-600 dark:text-success-400 uppercase tracking-wide mb-0.5">Nouveau fichier</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white truncate">{{ notification.data.file_name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ajouté à : {{ notification.data.tache_titre }}</p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-success-100 dark:bg-success-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user text-success-600 dark:text-success-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Ajouté par</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.uploaded_by_nom }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                      <i class="fas fa-weight-hanging text-gray-500 dark:text-gray-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Taille</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatFileSize(notification.data.file_size) }}</p>
                    </div>
                  </div>
                </div>

                <div class="mt-3 flex gap-2">
                  <button @click="downloadFile" class="flex-1 px-4 py-2.5 rounded-3 text-sm font-semibold text-white bg-success-500 hover:bg-success-600 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-download"></i>
                    Télécharger
                  </button>
                  <button @click="viewTask" class="px-4 py-2.5 rounded-3 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 transition-colors flex items-center gap-2">
                    <i class="fas fa-eye"></i>
                    Voir la tâche
                  </button>
                </div>
              </div>
            </div>

            <!-- ========== LIEN AJOUTÉ ========== -->
            <div v-else-if="notification?.type === 'task_link_added'" class="space-y-4">
              <div class="rounded-3 border border-brand-200 dark:border-brand-800/50 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-3 bg-brand-400 flex items-center justify-center shrink-0">
                    <i :class="['fab', notification.data.link_icon, 'text-white']"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wide mb-0.5">Nouveau lien</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white truncate">{{ notification.data.link_title }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ notification.data.link_domain }}</p>
                  </div>
                </div>

                <div class="space-y-2">
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-brand-100 dark:bg-brand-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-user text-brand-600 dark:text-brand-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Ajouté par</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.added_by_nom }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                    <div class="w-8 h-8 rounded-2 bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center shrink-0">
                      <i class="fas fa-tasks text-purple-600 dark:text-purple-400 text-sm"></i>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Tâche</p>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.tache_titre }}</p>
                    </div>
                  </div>
                </div>

                <div class="mt-3 flex gap-2">
                  <a :href="notification.data.link_url" target="_blank"
                    class="flex-1 px-4 py-2.5 rounded-3 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-external-link-alt"></i>
                    Ouvrir le lien
                  </a>
                  <button @click="viewTask"
                    class="px-4 py-2.5 rounded-3 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 transition-colors flex items-center gap-2">
                    <i class="fas fa-eye"></i>
                    Voir la tâche
                  </button>
                </div>
              </div>
            </div>

            <!-- ========== TÂCHE MISE À JOUR ========== -->
            <div v-else-if="notification?.type === 'task_updated'" class="space-y-4">
              <div class="rounded-3 border border-purple-200 dark:border-purple-800/50 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-3 bg-purple-600 flex items-center justify-center shrink-0">
                    <i class="fas fa-edit text-white"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide mb-0.5">Tâche modifiée</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white truncate">{{ notification.data.tache_titre }}</p>
                    <span class="inline-flex items-center gap-1.5 mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">
                      <i class="fas fa-edit"></i>
                      {{ notification.data.changes_count }} modification(s)
                    </span>
                  </div>
                </div>

                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3 mb-3">
                  <div class="w-8 h-8 rounded-2 bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center shrink-0">
                    <i class="fas fa-user text-purple-600 dark:text-purple-400 text-sm"></i>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Modifié par</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.updated_by_nom }}</p>
                  </div>
                </div>

                <div v-if="notification.data.changes && notification.data.changes.length" class="space-y-2">
                  <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-2 uppercase tracking-wide">Modifications effectuées</p>
                  <div v-for="change in notification.data.changes" :key="change.field"
                    class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-purple-400">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ change.label }}</p>
                    <div class="flex items-center gap-2 text-sm">
                      <span class="text-error-600 dark:text-error-400 line-through">{{ change.old_value || 'Vide' }}</span>
                      <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                      <span class="text-success-600 dark:text-success-400 font-semibold">{{ change.new_value || 'Vide' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ========== AUTRES NOTIFICATIONS ========== -->
            <div v-else class="space-y-4">
              <div class="p-4 rounded-3 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">Message</p>
                <p class="text-sm text-gray-900 dark:text-white leading-relaxed">{{ notification?.message }}</p>
              </div>

              <div v-if="hasAdditionalData" class="space-y-2">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Détails</p>

                <div v-if="notification?.data?.task_title"
                  class="flex items-start gap-3 p-3 rounded-3 border border-brand-200 dark:border-brand-800/50">
                  <i class="fas fa-tasks text-brand-500 dark:text-brand-400 mt-0.5"></i>
                  <div>
                    <p class="text-xs text-brand-600 dark:text-brand-400 font-semibold mb-0.5 uppercase tracking-wide">Tâche</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.task_title }}</p>
                  </div>
                </div>

                <div v-if="notification?.data?.project_title"
                  class="flex items-start gap-3 p-3 rounded-3 border border-purple-200 dark:border-purple-800/50">
                  <i class="fas fa-project-diagram text-purple-500 dark:text-purple-400 mt-0.5"></i>
                  <div>
                    <p class="text-xs text-purple-600 dark:text-purple-400 font-semibold mb-0.5 uppercase tracking-wide">Projet</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.project_title }}</p>
                  </div>
                </div>

                <div v-if="notification?.data?.document_name"
                  class="flex items-start gap-3 p-3 rounded-3 border border-success-200 dark:border-success-800/50">
                  <i class="fas fa-file text-success-500 dark:text-success-400 mt-0.5"></i>
                  <div>
                    <p class="text-xs text-success-600 dark:text-success-400 font-semibold mb-0.5 uppercase tracking-wide">Document</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ notification.data.document_name }}</p>
                  </div>
                </div>

                <div v-if="notification?.data?.hours_until_due"
                  class="flex items-start gap-3 p-3 rounded-3 border border-warning-200 dark:border-warning-800/50">
                  <i class="fas fa-clock text-warning-500 dark:text-warning-400 mt-0.5"></i>
                  <div>
                    <p class="text-xs text-warning-600 dark:text-warning-400 font-semibold mb-0.5 uppercase tracking-wide">Échéance</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Dans {{ notification.data.hours_until_due }} heure(s)</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Read status -->
            <div class="mt-6 flex items-center justify-between">
              <span v-if="!notification?.read_at"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400 border border-warning-300 dark:border-warning-700">
                <i class="fas fa-circle text-[6px] animate-pulse"></i>
                Non lu
              </span>
              <span v-else
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400 border border-success-300 dark:border-success-700">
                <i class="fas fa-check-circle"></i>
                Lu
              </span>

              <span class="text-xs text-gray-400 dark:text-gray-500 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 rounded-full border border-gray-200 dark:border-gray-700">
                {{ notification?.type }}
              </span>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-2">
              <button v-if="!notification?.read_at" @click="handleMarkAsRead"
                class="px-4 py-2 rounded-3 text-sm font-semibold text-white bg-success-500 hover:bg-success-600 transition-colors flex items-center gap-2">
                <i class="fas fa-check"></i>
                Marquer comme lu
              </button>

              <button @click="handleDelete"
                class="px-4 py-2 rounded-3 text-sm font-semibold bg-error-50 text-error-700 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400 dark:hover:bg-error-500/20 transition-colors flex items-center gap-2 border border-error-300 dark:border-error-700">
                <i class="fas fa-trash-alt"></i>
                Supprimer
              </button>
            </div>

            <button v-if="notification?.data?.action_url || notification?.data?.token || notification?.data?.url"
              @click="handleGoToAction"
              class="px-4 py-2 rounded-3 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 transition-colors flex items-center gap-2">
              {{ getActionButtonText() }}
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  notification: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'mark-read', 'delete']);

const router = useRouter();
const { getNotificationIcon, getNotificationColor } = useNotifications();

const icon = computed(() => getNotificationIcon(props.notification?.type));
const iconColor = computed(() => getNotificationColor(props.notification?.type));

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const downloadFile = () => {
  if (props.notification?.data?.download_url) {
    window.open(props.notification.data.download_url, '_blank');
  }
};

const viewTask = () => {
  if (props.notification?.data?.tache_id) {
    router.push(`/taches/${props.notification.data.tache_id}`);
    emit('close');
  }
};

const isResultatNotification = computed(() => {
  return ['resultat_soumis', 'resultat_attente_n2', 'resultat_valide_n1', 'resultat_valide_n2'].includes(props.notification?.type);
});

const hasAdditionalData = computed(() => {
  if (!props.notification?.data) return false;
  return (
    props.notification.data.task_title ||
    props.notification.data.project_title ||
    props.notification.data.document_name ||
    props.notification.data.hours_until_due
  );
});

const getHeaderGradientClass = () => {
  const colors = {
    'resultat_soumis': 'bg-warning-500',
    'resultat_attente_n2': 'bg-error-500',
    'resultat_valide_n1': 'bg-success-500',
    'resultat_valide_n2': 'bg-success-500',
    'projet_invitation': 'bg-purple-500',
    'workspace_invitation': 'bg-brand-500',
  };
  return colors[props.notification?.type] || 'bg-brand-500';
};

const getNotificationBadge = () => {
  const badges = {
    'resultat_soumis': 'À valider',
    'resultat_attente_n2': 'Urgent',
    'resultat_valide_n1': 'Validé N1',
    'resultat_valide_n2': 'Complet',
    'workspace_invitation': 'Invitation',
    'projet_invitation': 'Invitation',
  };
  return badges[props.notification?.type];
};

const getWorkspaceInitials = (name) => {
  return name
    ?.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2) || 'W';
};

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    manager: 'Gestionnaire',
    member: 'Membre',
    viewer: 'Observateur'
  };
  return labels[role] || role;
};

const formatExpirationDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getActionButtonText = () => {
  if (props.notification?.type === 'projet_invitation') {
    return 'Voir l\'invitation';
  }
  if (props.notification?.type === 'workspace_invitation') {
    return 'Voir l\'invitation';
  }
  if (isResultatNotification.value) {
    return 'Voir le résultat';
  }
  return 'Voir plus';
};

const close = () => {
  emit('close');
};

const handleMarkAsRead = () => {
  emit('mark-read', props.notification.id);
};

const handleDelete = () => {
  if (confirm('Supprimer cette notification ?')) {
    emit('delete', props.notification.id);
    close();
  }
};

const handleGoToAction = () => {
  if (!props.notification.read_at) {
    emit('mark-read', props.notification.id);
  }

  if (props.notification?.type === 'projet_invitation' && props.notification.data?.token) {
    router.push(`/invitations/projet/${props.notification.data.token}`);
  } else if (props.notification?.data?.action_url) {
    const url = props.notification.data.action_url;
    const path = url.startsWith('http') ? new URL(url).pathname : url;
    router.push(path);
  } else if (props.notification?.data?.url) {
    router.push(props.notification.data.url);
  }

  close();
};

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    close();
  }
};

if (typeof window !== 'undefined') {
  window.addEventListener('keydown', handleKeydown);
}
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active > div,
.modal-fade-leave-active > div {
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.modal-fade-enter-from > div,
.modal-fade-leave-to > div {
  transform: scale(0.95) translateY(10px);
  opacity: 0;
}
</style>