<!-- resources\js\components\layout\header\NotificationDetailModal.vue -->
<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div v-if="isOpen && notification" dusk="notification-detail-modal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30"
        @click.self="close">
        <div ref="dialogRef" :style="dragStyle" class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-3 max-h-[90vh] overflow-hidden border border-gray-200 dark:border-gray-800"
          @click.stop>

          <!-- En-tête coloré -->
          <div ref="handleRef" class="px-6 py-5 flex items-start gap-4 cursor-move select-none" :class="headerColor">
            <div class="shrink-0 w-12 h-12 rounded-3 flex items-center justify-center bg-white/20 border border-white/30">
              <component :is="notifIconComponent" class="w-6 h-6 text-white" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-start gap-2 mb-1">
                <h3 class="text-lg font-bold text-white flex-1 leading-tight">{{ modalTitle }}</h3>
                <span v-if="headerBadge"
                  class="shrink-0 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-white/20 text-white border border-white/30">
                  {{ headerBadge }}
                </span>
              </div>
              <p class="text-xs text-white/80 flex items-center gap-1.5">
                <!-- horloge -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/></svg>
                {{ notification.time_ago }}
              </p>
            </div>
            <button dusk="modal-close-btn" @click="close"
              class="shrink-0 p-2 rounded-3 hover:bg-white/20 text-white transition-colors border border-white/20">
              <!-- fermer -->
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Contenu -->
          <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)] space-y-4">

            <!-- ══ STATUT AUTO-CHANGÉ ══ -->
            <template v-if="type === 'tache_statut_auto_changed'">
              <p v-if="d.titre" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.titre }}</p>
              <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700">
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :class="statutBadgeClass(d.old_statut)">{{ statutLabel(d.old_statut) }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :class="statutBadgeClass(d.new_statut)">{{ statutLabel(d.new_statut) }}</span>
              </div>
              <ActionButton v-if="d.tache_id" label="Voir la tâche" icon="fa-arrow-right" @click="goToTache" />
            </template>

            <!-- ══ RÉSULTATS ══ -->
            <template v-else-if="isResultatType">
              <p v-if="d.tache_titre" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.tache_titre }}</p>
              <div v-if="d.taux_realisation != null" class="flex items-center gap-3">
                <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div class="h-full bg-brand-500 rounded-full transition-all" :style="{ width: `${d.taux_realisation}%` }"></div>
                </div>
                <span class="text-sm font-bold text-gray-900 dark:text-white w-10 text-right">{{ d.taux_realisation }}%</span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <MetaRow v-if="d.auteur_nom || d.author_nom" icon="fa-user" label="Auteur" :value="d.auteur_nom || d.author_nom" color="brand" />
                <MetaRow v-if="d.validateur_nom" :icon="type === 'resultat_rejete' ? 'fa-user-times' : 'fa-user-check'"
                  :label="type === 'resultat_rejete' ? 'Rejeté par' : 'Validé par'"
                  :value="d.validateur_nom" :color="type === 'resultat_rejete' ? 'error' : 'success'" />
              </div>
              <div v-if="d.commentaire" class="p-3 rounded-3 bg-gray-50 dark:bg-gray-800 border-l-4 border-gray-300 dark:border-gray-600">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">
                  {{ type === 'resultat_rejete' || type === 'resultat_renvoye_n0' ? 'Motif' : 'Commentaire' }}
                </p>
                <p class="text-sm text-gray-700 dark:text-gray-300 italic leading-relaxed">"{{ d.commentaire }}"</p>
              </div>
              <div v-if="type === 'resultat_validation_complete'" class="flex justify-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-3 border border-success-300 dark:border-success-700 bg-success-50 dark:bg-success-500/10">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning-500" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 5.6 6.73 6.73 0 0 0 2.743 1.346A6.707 6.707 0 0 1 9.279 15H8.54c-1.036 0-1.875.84-1.875 1.875V19.5h-.75a2.25 2.25 0 0 0-2.25 2.25c0 .414.336.75.75.75h15a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-2.25-2.25h-.75v-2.625c0-1.036-.84-1.875-1.875-1.875h-.739a6.706 6.706 0 0 1-.426-1.721 6.73 6.73 0 0 0 2.743-1.347 6.753 6.753 0 0 0 6.139-5.6.75.75 0 0 0-.585-.858 47.077 47.077 0 0 0-3.07-.543V2.62a.75.75 0 0 0-.658-.744 49.798 49.798 0 0 0-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 0 0-.657.744Zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 0 1 3.16 5.337a45.6 45.6 0 0 1 2.006-.343v.256Zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 0 1-2.863 3.207 6.72 6.72 0 0 0 .857-3.294Z"/></svg>
                  <span class="text-sm font-bold text-success-700 dark:text-success-300">N1 ✓ + N2 ✓ — Validation totale</span>
                </div>
              </div>
              <ActionButton v-if="d.tache_id" label="Voir la tâche" icon="fa-arrow-right" @click="goToTache" />
            </template>

            <!-- ══ TÂCHE ASSIGNÉE ══ -->
            <template v-else-if="type === 'tache_assignee'">
              <p v-if="d.tache_titre" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.tache_titre }}</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <MetaRow v-if="d.assigned_by" icon="fa-user" label="Assigné par" :value="d.assigned_by" color="brand" />
                <MetaRow v-if="d.tache_echeance" icon="fa-calendar-alt" label="Échéance" :value="formatDate(d.tache_echeance)" color="warning" />
              </div>
              <p v-if="d.resources_count" class="text-xs text-gray-500 dark:text-gray-400">{{ d.resources_count }} ressource(s) disponible(s)</p>
              <ActionButton v-if="d.tache_id" label="Voir la tâche" icon="fa-arrow-right" @click="goToTache" />
            </template>

            <!-- ══ TÂCHE MODIFIÉE ══ -->
            <template v-else-if="type === 'task_updated'">
              <p v-if="d.tache_titre || d.task_title" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.tache_titre || d.task_title }}</p>
              <MetaRow v-if="d.updated_by_nom" icon="fa-user" label="Modifié par" :value="d.updated_by_nom" color="purple" />
              <div v-if="d.changes?.length" class="space-y-2">
                <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide">Modifications</p>
                <div v-for="change in d.changes" :key="change.field" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-purple-400">
                  <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ change.label }}</p>
                  <div class="flex items-center gap-2 text-sm">
                    <span class="text-error-600 dark:text-error-400 line-through">{{ change.old_value || 'Vide' }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    <span class="text-success-600 dark:text-success-400 font-semibold">{{ change.new_value || 'Vide' }}</span>
                  </div>
                </div>
              </div>
            </template>

            <!-- ══ BYPASS ══ -->
            <template v-else-if="type === 'bypass_activated' || type === 'escalades_abusives' || type === 'abusive_escalation_alert'">
              <p v-if="d.tache_titre" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.tache_titre }}</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <MetaRow v-if="d.author_nom || d.abuser_nom" icon="fa-user"
                  :label="type === 'bypass_activated' ? 'Activé par' : 'Utilisateur'"
                  :value="d.author_nom || d.abuser_nom" color="brand" />
                <MetaRow v-if="d.bypass_count || d.consecutive_count" icon="fa-redo" label="Bypass(s)"
                  :value="String(d.bypass_count || d.consecutive_count)" color="error" />
              </div>
              <div v-if="d.motif_bypass" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-4 border-purple-400">
                <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-1 uppercase tracking-wide">Motif</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 italic">"{{ d.motif_bypass }}"</p>
              </div>
            </template>

            <!-- ══ INVITATION PROJET ══ -->
            <template v-else-if="type === 'projet_invitation'">
              <p v-if="d.projet_nom" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.projet_nom }}</p>
              <div class="space-y-2">
                <MetaRow v-if="d.inviter_nom" icon="fa-user" label="Invité par" :value="d.inviter_nom" color="purple" />
                <MetaRow v-if="d.role" icon="fa-user-tag" label="Rôle proposé" :value="roleLabel(d.role)" color="purple" />
              </div>
              <div v-if="d.expires_at" class="flex items-center gap-2 p-3 rounded-3 bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span class="text-sm font-semibold text-warning-700 dark:text-warning-300">Expire le {{ formatDate(d.expires_at) }}</span>
              </div>
              <ActionButton v-if="d.token" label="Voir l'invitation"                @click="router.push(`/invitations/projet/${d.token}`); close()" />
            </template>

            <!-- ══ INVITATION WORKSPACE ══ -->
            <template v-else-if="type === 'workspace_invitation'">
              <div class="flex items-center gap-3">
                <div v-if="d.workspace_logo" class="w-10 h-10 rounded-3 overflow-hidden shrink-0">
                  <img :src="d.workspace_logo" class="w-full h-full object-cover" />
                </div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.workspace_name }}</p>
              </div>
              <div class="space-y-2">
                <MetaRow v-if="d.inviter_name" icon="fa-user" label="Invité par" :value="d.inviter_name" color="brand" />
                <MetaRow v-if="d.role" icon="fa-user-tag" label="Rôle proposé" :value="roleLabel(d.role)" color="brand" />
              </div>
              <div v-if="d.invitation_message" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-4 border-brand-400">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Message</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 italic">"{{ d.invitation_message }}"</p>
              </div>
              <div v-if="d.expires_at" class="flex items-center gap-2 p-3 rounded-3 bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span class="text-sm font-semibold text-warning-700 dark:text-warning-300">Expire le {{ formatDate(d.expires_at) }}</span>
              </div>
              <ActionButton v-if="d.action_url" label="Voir l'invitation" icon="fa-arrow-right" @click="goToUrl(d.action_url)" />
            </template>

            <!-- ══ RESPONSABLE CHANGÉ ══ -->
            <template v-else-if="type === 'responsable_changed'">
              <p v-if="d.activite_nom" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.activite_nom }}</p>
              <div class="space-y-2">
                <MetaRow v-if="d.old_responsable" icon="fa-user-times" label="Ancien responsable" :value="d.old_responsable" color="error" />
                <MetaRow v-if="d.new_responsable" icon="fa-user-check" label="Nouveau responsable" :value="d.new_responsable" color="success" />
              </div>
              <ActionButton v-if="d.activite_id" label="Voir l'activité"                @click="router.push(`/activites/${d.activite_id}`); close()" />
            </template>

            <!-- ══ MEMBRE ACTIVITÉ ══ -->
            <template v-else-if="['activite_member_added','activite_member_removed','activite_member_permissions_updated'].includes(type)">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.activite_nom }}</p>
                <p v-if="d.projet_nom" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ d.projet_nom }}</p>
              </div>
              <div class="space-y-2">
                <MetaRow v-if="d.added_by_nom || d.removed_by_nom || d.updated_by_nom" icon="fa-user"
                  label="Par" :value="d.added_by_nom || d.removed_by_nom || d.updated_by_nom" color="brand" />
                <MetaRow v-if="d.role" icon="fa-user-tag" label="Rôle" :value="d.role" color="brand" />
              </div>
              <ActionButton v-if="d.url" label="Voir l'activité" icon="fa-arrow-right" @click="goToUrl(d.url)" />
            </template>

            <!-- ══ DOCUMENTS ══ -->
            <template v-else-if="['document_uploaded','document_deleted','document_shared','document_permission_granted'].includes(type)">
              <p v-if="d.document_nom" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.document_nom }}</p>
              <MetaRow v-if="d.uploaded_by || d.deleted_by || d.shared_by" icon="fa-user"
                :label="type === 'document_deleted' ? 'Supprimé par' : type === 'document_shared' ? 'Partagé par' : 'Ajouté par'"
                :value="d.uploaded_by || d.deleted_by || d.shared_by" color="brand" />
              <ActionButton v-if="type !== 'document_deleted'"
                :label="type === 'document_shared' ? 'Voir les partagés' : 'Voir les documents'"
                               @click="router.push(type === 'document_shared' ? '/documents?tab=shared' : '/documents'); close()" />
            </template>

            <!-- ══ SCORE / ÉVALUATION ══ -->
            <template v-else-if="type === 'score_updated'">
              <p v-if="d.critere" class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.critere }}</p>
              <div class="grid grid-cols-2 gap-3">
                <div class="p-3 rounded-3 text-center border" :class="d.valeur > 0 ? 'bg-success-50 dark:bg-success-500/10 border-success-200 dark:border-success-700' : 'bg-error-50 dark:bg-error-500/10 border-error-200 dark:border-error-700'">
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Impact</p>
                  <p class="text-xl font-bold" :class="d.valeur > 0 ? 'text-success-600 dark:text-success-400' : 'text-error-600 dark:text-error-400'">
                    {{ d.valeur > 0 ? '+' : '' }}{{ d.valeur }}
                  </p>
                </div>
                <div class="p-3 rounded-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-center">
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Décision</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.decision || '—' }}</p>
                </div>
              </div>
              <p v-if="d.periode_start" class="text-xs text-gray-500 dark:text-gray-400">
                Période : {{ formatDate(d.periode_start) }} → {{ formatDate(d.periode_end) }}
              </p>
            </template>

            <!-- ══ ABONNEMENT / TRIAL ══ -->
            <template v-else-if="['trial_expiring','trial_expired','trial_extended','subscription_limit_reached','workspace_suspended'].includes(type)">
              <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.workspace_nom || 'Workspace' }}</p>
              <p v-if="d.reason || d.limit_type" class="text-sm text-gray-700 dark:text-gray-300">
                {{ d.reason || `Limite "${d.limit_type}" atteinte (${d.current_value}/${d.max_value})` }}
              </p>
            </template>

            <!-- ══ TEAMS ══ -->
            <template v-else-if="type.startsWith('team_')">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                  {{ d.title || d.announcement_title || d.event_title || d.resource_title }}
                </p>
                <p v-if="d.team_name" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ d.team_name }}</p>
              </div>
              <p v-if="d.announcement_content || d.event_description || d.resource_description"
                class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                {{ d.announcement_content || d.event_description || d.resource_description }}
              </p>
              <div v-if="d.event_start_date" class="grid grid-cols-2 gap-2">
                <MetaRow icon="fa-calendar-alt" label="Début" :value="formatDate(d.event_start_date)" color="brand" />
                <MetaRow v-if="d.event_end_date" icon="fa-calendar-alt" label="Fin" :value="formatDate(d.event_end_date)" color="brand" />
              </div>
              <ActionButton v-if="d.url" label="Voir" icon="fa-arrow-right" @click="goToUrl(d.url)" />
            </template>

            <!-- ══ ALERTE INACTION / RETOUR ══ -->
            <template v-else-if="['high_inaction_rate_alert','unjustified_return_alert','abusive_escalation_alert'].includes(type)">
              <p class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ d.responsable_nom || d.agent_nom || d.abuser_nom }}
              </p>
              <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-error-50 dark:bg-error-500/10 border border-error-200 dark:border-error-700 rounded-3 text-center">
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Taux</p>
                  <p class="text-xl font-bold text-error-600 dark:text-error-400">
                    {{ d.inaction_rate || d.rate || d.bypass_count }}{{ d.inaction_rate || d.rate ? '%' : '' }}
                  </p>
                </div>
                <div v-if="d.periode_start" class="p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 text-center">
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Période</p>
                  <p class="text-xs text-gray-700 dark:text-gray-300">{{ formatDate(d.periode_start) }}</p>
                </div>
              </div>
            </template>

            <!-- ══ FALLBACK ══ -->
            <template v-else>
              <div v-if="notification.message">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Message</p>
                <p class="text-sm text-gray-900 dark:text-white leading-relaxed">{{ notification.message }}</p>
              </div>
              <div class="space-y-2">
                <template v-for="(val, key) in readableData" :key="key">
                  <div v-if="val" class="flex items-start gap-2 text-sm">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide min-w-24">{{ friendlyKey(key) }}</span>
                    <span class="text-gray-900 dark:text-white">{{ val }}</span>
                  </div>
                </template>
              </div>
              <ActionButton v-if="d.url" label="Voir" icon="fa-arrow-right" @click="goToUrl(d.url)" />
            </template>

            <!-- Statut de lecture + libellé type -->
            <div class="flex items-center gap-2 pt-2">
              <span v-if="!notification.read_at"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-400 border border-brand-300 dark:border-brand-700">
                <!-- enveloppe fermée -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.5 8.187V17.25C3.5 17.664 3.836 18 4.25 18h15.5c.414 0 .75-.336.75-.75V8.187l-7.213 5.03a1.75 1.75 0 0 1-2-.001L3.5 8.187ZM20.5 6.23v.013l-8 5.557a.25.25 0 0 1-.286 0L3.601 6.429A.25.25 0 0 1 3.736 6h16.528a.25.25 0 0 1 .236.23ZM2 6.256V17.25A2.25 2.25 0 0 0 4.25 19.5h15.5A2.25 2.25 0 0 0 22 17.25V6.256A2.25 2.25 0 0 0 19.764 4.5H4.236A2.25 2.25 0 0 0 2 6.256Z"/></svg>
                {{ $t('notifications.unread') }}
              </span>
              <span v-else
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                <!-- enveloppe ouverte -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.04a2.25 2.25 0 0 1 2.134 0l7.5 4.04a2.25 2.25 0 0 1 1.183 1.98V19.5Z"/></svg>
                {{ $t('notifications.read') }}
              </span>
              <span v-if="typeLabel"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                <component :is="notifIconComponent" class="w-3 h-3" />
                {{ typeLabel }}
              </span>
            </div>
          </div>

          <!-- Pied de page -->
          <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-2">
              <button v-if="!notification.read_at" @click="emit('mark-read', notification.id)"
                class="px-4 py-2 rounded-3 text-sm font-semibold text-white bg-success-500 hover:bg-success-600 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                {{ $t('notifications.mark_read') }}
              </button>
              <button @click="handleDelete"
                class="px-4 py-2 rounded-3 text-sm font-semibold bg-error-50 text-error-700 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400 dark:hover:bg-error-500/20 transition-colors flex items-center gap-2 border border-error-300 dark:border-error-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                {{ $t('notifications.delete') }}
              </button>
            </div>
            <button v-if="actionUrl" @click="handleGoToAction"
              class="px-4 py-2 rounded-3 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 transition-colors flex items-center gap-2">
              {{ actionLabel }}
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed, defineComponent, h, nextTick, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useDraggable } from '@/composables/useDraggable';
import { useNotifications } from '@/composables/useNotifications';
import { getRoleLabel } from '@/permissions/Permission';

const { dialogRef, handleRef, dragStyle, attachHandle, detachHandle } = useDraggable();

// ── Composants locaux légers ──────────────────────────────────────────────────

const COLOR_CLASSES = {
  blue:    { bg: 'bg-brand-500',   ring: 'bg-brand-100 dark:bg-brand-500/20',   text: 'text-brand-600 dark:text-brand-400' },
  brand:   { bg: 'bg-brand-500',   ring: 'bg-brand-100 dark:bg-brand-500/20',   text: 'text-brand-600 dark:text-brand-400' },
  green:   { bg: 'bg-success-500', ring: 'bg-success-100 dark:bg-success-500/20', text: 'text-success-600 dark:text-success-400' },
  success: { bg: 'bg-success-500', ring: 'bg-success-100 dark:bg-success-500/20', text: 'text-success-600 dark:text-success-400' },
  emerald: { bg: 'bg-success-500', ring: 'bg-success-100 dark:bg-success-500/20', text: 'text-success-600 dark:text-success-400' },
  orange:  { bg: 'bg-warning-500', ring: 'bg-warning-100 dark:bg-warning-500/20', text: 'text-warning-600 dark:text-warning-400' },
  yellow:  { bg: 'bg-warning-400', ring: 'bg-warning-100 dark:bg-warning-500/20', text: 'text-warning-600 dark:text-warning-400' },
  warning: { bg: 'bg-warning-500', ring: 'bg-warning-100 dark:bg-warning-500/20', text: 'text-warning-600 dark:text-warning-400' },
  red:     { bg: 'bg-error-500',   ring: 'bg-error-100 dark:bg-error-500/20',   text: 'text-error-600 dark:text-error-400' },
  error:   { bg: 'bg-error-500',   ring: 'bg-error-100 dark:bg-error-500/20',   text: 'text-error-600 dark:text-error-400' },
  purple:  { bg: 'bg-purple-500',  ring: 'bg-purple-100 dark:bg-purple-500/20', text: 'text-purple-600 dark:text-purple-400' },
  indigo:  { bg: 'bg-purple-600',  ring: 'bg-purple-100 dark:bg-purple-500/20', text: 'text-purple-600 dark:text-purple-400' },
  cyan:    { bg: 'bg-brand-400',   ring: 'bg-brand-100 dark:bg-brand-500/20',   text: 'text-brand-600 dark:text-brand-400' },
  gray:    { bg: 'bg-gray-500',    ring: 'bg-gray-100 dark:bg-gray-700',        text: 'text-gray-600 dark:text-gray-400' },
};

// SVG paths pour les icônes utilisées dans MetaRow
const META_ICONS = {
  'fa-user':        'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
  'fa-user-check':  'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  'fa-user-times':  'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  'fa-user-tag':    'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3Z',
  'fa-user-cog':    'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
  'fa-user-plus':   'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z',
  'fa-user-shield': 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
  'fa-calendar-alt':'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
  'fa-calendar-plus':'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h4.5m-2.25-2.25v4.5',
  'fa-clock':       'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  'fa-ban':         'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636',
  'fa-redo':        'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
  'fa-chart-bar':   'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
  'fa-shield-alt':  'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
  'fa-exclamation-triangle': 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
  'fa-share-alt':   'M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z',
  'fa-file-upload': 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5',
  'fa-folder-open': 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776',
  'fa-project-diagram': 'M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z',
  'fa-trophy':      'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z',
  'fa-arrow-right': 'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3',
  'fa-sync-alt':    'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
  'fa-edit':        'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125',
  'fa-trash':       'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
  'fa-info-circle': 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
  'fa-unlock':      'M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
  'fa-bullhorn':    'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 10.997a23.743 23.743 0 0 1 0 2.006',
  'fa-clipboard-list': 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z',
};

// MetaRow : ligne icone + label + valeur
const MetaRow = defineComponent({
  props: { icon: String, label: String, value: String, color: { type: String, default: 'brand' }, class: String },
  setup(props) {
    return () => {
      const c = COLOR_CLASSES[props.color] || COLOR_CLASSES.brand;
      const path = META_ICONS[props.icon];
      const iconEl = path
        ? h('svg', { xmlns: 'http://www.w3.org/2000/svg', class: `w-4 h-4 ${c.text}`, fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' },
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: path })
          )
        : null;
      return h('div', { class: `flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3 ${props.class || ''}` }, [
        h('div', { class: `w-8 h-8 rounded-2 ${c.ring} flex items-center justify-center shrink-0` }, iconEl ? [iconEl] : []),
        h('div', [
          h('p', { class: 'text-xs text-gray-500 dark:text-gray-400 mb-0.5' }, props.label),
          h('p', { class: 'text-sm font-semibold text-gray-900 dark:text-white' }, props.value),
        ]),
      ]);
    };
  },
});

// ActionButton : bouton primaire pleine largeur
const ActionButton = defineComponent({
  props: { label: String },
  emits: ['click'],
  setup(props, { emit }) {
    const arrowSvg = h('svg', { xmlns: 'http://www.w3.org/2000/svg', class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' },
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3' })
    );
    return () => h('button', {
      onClick: () => emit('click'),
      class: 'w-full px-4 py-2.5 rounded-3 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 transition-colors flex items-center justify-center gap-2',
    }, [props.label, arrowSvg]);
  },
});

// ── Props / composable ────────────────────────────────────────────────────────

const { t } = useI18n();

const props = defineProps({
  isOpen:       { type: Boolean, required: true },
  notification: { type: Object,  default: null },
});

const emit = defineEmits(['close', 'mark-read', 'delete']);

watch(() => props.isOpen, (val) => {
  if (val) {
    nextTick(attachHandle);
  } else {
    detachHandle();
  }
});

const router = useRouter();

const type = computed(() => props.notification?.type || '');
const d    = computed(() => props.notification?.data || {});

// Icône SVG pour l'en-tête du modal selon le type de notification
const NOTIF_ICON_PATHS = {
  tache_assignee:   'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z',
  tache_statut_auto_changed: 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
  task_updated:     'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z',
  bypass_activated: 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
  escalades_abusives: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
  abusive_escalation_alert: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
  score_updated:    'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
  responsable_changed: 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
  document_uploaded: 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5',
  document_deleted:  'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
  document_shared:   'M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z',
  projet_invitation: 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
  workspace_invitation: 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
  high_inaction_rate_alert: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
  unjustified_return_alert: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
  trial_expired:    'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  trial_expiring:   'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  trial_extended:   'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h4.5m-2.25-2.25v4.5',
  workspace_suspended: 'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636',
  subscription_limit_reached: 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
};
const DEFAULT_NOTIF_ICON_PATH = 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0';

const notifIconPath = computed(() => {
  const t = type.value;
  if (NOTIF_ICON_PATHS[t]) return NOTIF_ICON_PATHS[t];
  if (isResultatType.value) return 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z';
  if (t.startsWith('activite_member')) return 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z';
  if (t.startsWith('team_')) return 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z';
  return DEFAULT_NOTIF_ICON_PATH;
});

const notifIconComponent = computed(() => ({
  render() {
    return h('svg', { xmlns: 'http://www.w3.org/2000/svg', class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' },
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: notifIconPath.value })
    );
  }
}));

// ── Types qui ont un résultat associé ────────────────────────────────────────

const isResultatType = computed(() => [
  'resultat_soumis', 'resultat_soumis_n0', 'resultat_attente_n2',
  'resultat_valide_n1', 'resultat_valide_n2', 'resultat_rejete',
  'resultat_validation_complete', 'resultat_rejete_n2_info',
  'resultat_approuve_n0', 'resultat_renvoye_n0', 'resultat_transmis_auto',
  'validation_n1_confirmee', 'validation_n2_confirmee', 'rejet_confirme',
].includes(type.value));

// ── En-tête modal ─────────────────────────────────────────────────────────────

const HEADER_COLORS = {
  resultat_soumis: 'bg-brand-500', resultat_soumis_n0: 'bg-brand-500',
  resultat_approuve_n0: 'bg-success-500', resultat_renvoye_n0: 'bg-warning-500',
  resultat_transmis_auto: 'bg-purple-600',
  resultat_attente_n2: 'bg-warning-500',
  resultat_valide_n1: 'bg-success-500', resultat_valide_n2: 'bg-success-600',
  resultat_validation_complete: 'bg-success-600',
  resultat_rejete: 'bg-error-500', rejet_confirme: 'bg-warning-600',
  resultat_rejete_n2_info: 'bg-warning-400',
  validation_n1_confirmee: 'bg-success-500', validation_n2_confirmee: 'bg-success-600',
  projet_invitation: 'bg-purple-500', workspace_invitation: 'bg-brand-500',
  workspace_member_added: 'bg-brand-500',
  tache_assignee: 'bg-brand-500', tache_statut_auto_changed: 'bg-purple-600',
  tache_resources: 'bg-brand-400', task_updated: 'bg-purple-600',
  bypass_activated: 'bg-purple-600',
  escalades_abusives: 'bg-error-500', abusive_escalation_alert: 'bg-error-500',
  responsable_changed: 'bg-purple-500',
  activite_member_added: 'bg-brand-500', activite_member_removed: 'bg-error-500',
  activite_member_permissions_updated: 'bg-purple-500',
  document_uploaded: 'bg-warning-400', document_deleted: 'bg-error-400',
  document_shared: 'bg-brand-400', document_permission_granted: 'bg-success-400',
  score_updated: 'bg-purple-500', evaluation_sheet_ready: 'bg-purple-600',
  trial_expiring: 'bg-warning-500', trial_expired: 'bg-error-500',
  trial_extended: 'bg-success-500', subscription_limit_reached: 'bg-warning-600',
  workspace_suspended: 'bg-error-600',
  high_inaction_rate_alert: 'bg-error-500', unjustified_return_alert: 'bg-error-500',
  support_ticket_new: 'bg-purple-500',
  support_ticket_reply: 'bg-brand-500',
  support_ticket_status_changed: 'bg-warning-500',
};

const headerColor = computed(() => HEADER_COLORS[type.value] || 'bg-gray-500');

const HEADER_BADGES = {
  resultat_soumis: 'À valider', resultat_soumis_n0: 'À valider',
  resultat_attente_n2: 'Urgent', resultat_rejete: 'Rejeté',
  resultat_valide_n1: 'Validé N1', resultat_valide_n2: 'Validé N2',
  resultat_validation_complete: 'Complet',
  workspace_invitation: 'Invitation', projet_invitation: 'Invitation',
  tache_assignee: 'Assigné', bypass_activated: 'Bypass',
  escalades_abusives: 'Alerte', trial_expired: 'Expiré',
  workspace_suspended: 'Suspendu',
};
const headerBadge = computed(() => HEADER_BADGES[type.value] ?? null);

const TYPE_LABEL_DISPLAY = {
  tache_statut_auto_changed: 'Statut auto-modifié',
  tache_assignee: 'Tâche assignée', tache_resources: 'Ressources',
  resultat_soumis: 'Résultat soumis', resultat_soumis_n0: 'Résultat soumis N0',
  resultat_approuve_n0: 'Approuvé N0', resultat_renvoye_n0: 'Renvoyé N0',
  resultat_transmis_auto: 'Transmis auto', resultat_attente_n2: 'Attente N2',
  resultat_valide_n1: 'Validé N1', resultat_valide_n2: 'Validé N2',
  resultat_rejete: 'Rejeté', resultat_validation_complete: 'Validation complète',
  resultat_rejete_n2_info: 'Info rejet N2',
  validation_n1_confirmee: 'Décision N1', validation_n2_confirmee: 'Décision N2',
  rejet_confirme: 'Rejet confirmé', bypass_activated: 'Bypass',
  escalades_abusives: 'Escalades abusives',
  workspace_invitation: 'Invitation workspace', projet_invitation: 'Invitation projet',
  responsable_changed: 'Responsable modifié',
  activite_member_added: 'Membre ajouté', activite_member_removed: 'Membre retiré',
  document_uploaded: 'Document ajouté', document_deleted: 'Document supprimé',
  document_shared: 'Document partagé', score_updated: 'Score mis à jour',
  trial_expiring: 'Essai bientôt expiré', trial_expired: 'Essai expiré',
  trial_extended: 'Essai prolongé', workspace_suspended: 'Workspace suspendu',
  subscription_limit_reached: 'Limite atteinte',
  support_ticket_new: 'Nouveau ticket', support_ticket_reply: 'Réponse ticket',
  support_ticket_status_changed: 'Statut ticket',
};
const typeLabel = computed(() => TYPE_LABEL_DISPLAY[type.value] ?? null);

// Titre du modal (utilise d.title quand disponible, sinon construit)
const modalTitle = computed(() => {
  if (props.notification?.title) return props.notification.title;
  const t = type.value;
  if (isResultatType.value) return d.value.tache_titre || 'Tâche';
  if (t === 'tache_statut_auto_changed') return d.value.titre || 'Tâche';
  if (t === 'tache_assignee') return d.value.tache_titre || 'Tâche';
  if (t === 'projet_invitation') return d.value.projet_nom || 'Projet';
  if (t === 'workspace_invitation') return d.value.workspace_name || 'Workspace';
  if (t === 'responsable_changed') return d.value.activite_nom || 'Activité';
  if (t.startsWith('document_')) return d.value.document_nom || 'Document';
  if (t.startsWith('team_')) return d.value.team_name || 'Équipe';
  if (t.startsWith('trial_') || t === 'workspace_suspended' || t === 'subscription_limit_reached')
    return d.value.workspace_nom || 'Workspace';
  if (t === 'support_ticket_new') return `#${d.value.ticket_number || '?'} — ${d.value.subject || 'Nouveau ticket'}`;
  if (t === 'support_ticket_reply') return `#${d.value.ticket_number || '?'} — ${d.value.subject || 'Réponse'}`;
  if (t === 'support_ticket_status_changed') return `#${d.value.ticket_number || '?'} — ${d.value.subject || 'Ticket'}`;
  return typeLabel.value || t;
});

// ── Bouton d'action pied de page ─────────────────────────────────────────────

const actionUrl = computed(() => {
  const data = d.value;
  if (type.value === 'projet_invitation' && data.token) return true;
  return data.action_url || data.url;
});

const actionLabel = computed(() => {
  if (isResultatType.value) return 'Voir le résultat';
  if (['projet_invitation', 'workspace_invitation'].includes(type.value)) return "Voir l'invitation";
  if (type.value === 'support_ticket_new') return 'Gérer les tickets';
  if (['support_ticket_reply', 'support_ticket_status_changed'].includes(type.value)) return 'Voir mon ticket';
  return 'Voir plus';
});

// ── Helpers ───────────────────────────────────────────────────────────────────

const formatDate = (val) => {
  if (!val) return '';
  return new Date(val).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });
};

const STATUT_LABELS = {
  a_faire: 'À faire', en_cours: 'En cours', termine: 'Terminé',
  en_retard: 'En retard', a_refaire: 'À refaire', annule: 'Annulé',
};
const STATUT_CLASSES = {
  a_faire: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  en_cours: 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-400',
  termine: 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400',
  en_retard: 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-400',
  a_refaire: 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400',
  annule: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
};
const statutLabel = (s) => STATUT_LABELS[s] || s;
const statutBadgeClass = (s) => STATUT_CLASSES[s] || 'bg-gray-100 text-gray-700';

const roleLabel = (r) => getRoleLabel(r);

const initials = (name) => name?.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || 'W';

// Fallback : données lisibles (exclut les champs internes)
const EXCLUDED_KEYS = new Set(['type', 'dedup_key', 'url', 'action_url', 'is_pending',
  'icon', 'color', 'titre', 'tache_titre', 'tache_id', 'resultat_id', 'tache_resultat_id',
  'workspace_id', 'projet_id', 'activite_id', 'agent_id', 'abuser_id', 'responsable_id',
  'document_id', 'team_id', 'team_uuid', 'announcement_id', 'event_id', 'resource_id',
  'invitation_id', 'score_id', 'added_by_id', 'removed_by_id', 'updated_by_id',
  'auteur_id', 'validateur_id', 'author_id']);

const FRIENDLY_KEYS = {
  tache_titre: 'Tâche', titre: 'Tâche', projet_nom: 'Projet', activite_nom: 'Activité',
  workspace_name: 'Workspace', workspace_nom: 'Workspace', team_name: 'Équipe',
  auteur_nom: 'Auteur', author_nom: 'Auteur', validateur_nom: 'Validateur',
  assigned_by: 'Assigné par', added_by_nom: 'Ajouté par', removed_by_nom: 'Retiré par',
  inviter_nom: 'Invité par', inviter_name: 'Invité par', shared_by: 'Partagé par',
  uploaded_by: 'Ajouté par', deleted_by: 'Supprimé par',
  old_statut: 'Ancien statut', new_statut: 'Nouveau statut',
  taux_realisation: 'Avancement', commentaire: 'Commentaire', motif_bypass: 'Motif',
  periode_start: 'Début période', periode_end: 'Fin période',
  remaining_days: 'Jours restants', new_duration_days: 'Nouvelle durée',
  bypass_count: 'Bypass(s)', consecutive_count: 'Bypass(s) consécutifs',
  inaction_rate: 'Taux d\'inaction', rate: 'Taux',
  critere: 'Critère', valeur: 'Valeur', decision: 'Décision',
  role: 'Rôle', reason: 'Motif', limit_type: 'Limite', level: 'Niveau',
};
const friendlyKey = (k) => FRIENDLY_KEYS[k] || k.replace(/_/g, ' ');

const readableData = computed(() => {
  const data = d.value;
  const result = {};
  for (const [key, val] of Object.entries(data)) {
    if (EXCLUDED_KEYS.has(key)) continue;
    if (typeof val === 'object' && val !== null) continue;
    if (val === null || val === undefined || val === '') continue;
    result[key] = String(val);
  }
  return result;
});

// ── Actions ───────────────────────────────────────────────────────────────────

const goToTache = () => {
  if (d.value.tache_id) router.push(`/taches/${d.value.tache_id}`);
  close();
};

const goToUrl = (url) => {
  if (!url) return;
  const path = url.startsWith('http') ? new URL(url).pathname : url;
  router.push(path);
  close();
};

const close = () => emit('close');

const handleDelete = () => {
  if (confirm(t('notifications.confirm_delete'))) {
    emit('delete', props.notification.id);
    close();
  }
};

const handleGoToAction = () => {
  if (!props.notification.read_at) emit('mark-read', props.notification.id);
  const data = d.value;
  if (type.value === 'projet_invitation' && data.token) {
    router.push(`/invitations/projet/${data.token}`);
  } else if (data.action_url) {
    goToUrl(data.action_url);
  } else if (data.url) {
    goToUrl(data.url);
  }
  close();
};

if (typeof window !== 'undefined') {
  window.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
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
