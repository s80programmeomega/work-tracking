<!-- resources\js\components\layout\header\NotificationDetailModal.vue -->
<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div v-if="isOpen && notification" dusk="notification-detail-modal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70"
        @click.self="close">
        <div class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-3 max-h-[90vh] overflow-hidden border border-gray-200 dark:border-gray-800"
          @click.stop>

          <!-- En-tête coloré -->
          <div class="px-6 py-5 flex items-start gap-4" :class="headerColor">
            <div class="shrink-0 w-12 h-12 rounded-3 flex items-center justify-center bg-white/20 border border-white/30">
              <i :class="['fas', notifIcon, 'text-white text-xl']"></i>
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
                <i class="fas fa-clock"></i>
                {{ notification.time_ago }}
              </p>
            </div>
            <button dusk="modal-close-btn" @click="close"
              class="shrink-0 p-2 rounded-3 hover:bg-white/20 text-white transition-colors border border-white/20">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Contenu -->
          <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)] space-y-4">

            <!-- ══ STATUT AUTO-CHANGÉ ══ -->
            <template v-if="type === 'tache_statut_auto_changed'">
              <InfoCard color="indigo">
                <template #icon><i class="fas fa-sync-alt text-white"></i></template>
                <template #label>Tâche</template>
                <template #title>{{ d.titre }}</template>
                <div class="flex items-center gap-3 mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                  <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :class="statutBadgeClass(d.old_statut)">
                    {{ statutLabel(d.old_statut) }}
                  </span>
                  <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                  <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :class="statutBadgeClass(d.new_statut)">
                    {{ statutLabel(d.new_statut) }}
                  </span>
                </div>
              </InfoCard>
              <ActionButton v-if="d.tache_id" label="Voir la tâche" icon="fa-arrow-right" @click="goToTache" />
            </template>

            <!-- ══ RÉSULTATS (soumis, validé, rejeté, circuit) ══ -->
            <template v-else-if="isResultatType">
              <InfoCard :color="resultatCardColor">
                <template #icon><i :class="['fas', notifIcon, 'text-white']"></i></template>
                <template #label>{{ resultatCardLabel }}</template>
                <template #title>{{ d.tache_titre }}</template>
                <template v-if="d.taux_realisation != null" #subtitle>
                  <div class="flex items-center gap-2 mt-1">
                    <div class="flex-1 h-1.5 bg-white/30 rounded-full overflow-hidden">
                      <div class="h-full bg-white rounded-full" :style="{ width: `${d.taux_realisation}%` }"></div>
                    </div>
                    <span class="text-xs font-bold text-white/90">{{ d.taux_realisation }}%</span>
                  </div>
                </template>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                  <MetaRow v-if="d.auteur_nom || d.author_nom" icon="fa-user" label="Auteur"
                    :value="d.auteur_nom || d.author_nom" color="brand" />
                  <MetaRow v-if="d.validateur_nom" :icon="type === 'resultat_rejete' ? 'fa-user-times' : 'fa-user-check'"
                    :label="type === 'resultat_rejete' ? 'Rejeté par' : 'Validé par'"
                    :value="d.validateur_nom" :color="type === 'resultat_rejete' ? 'error' : 'success'" />
                </div>
                <div v-if="d.commentaire" class="mt-3 p-3 rounded-3 border-l-2 border-white/40 bg-white/10">
                  <p class="text-xs font-semibold text-white/70 mb-1 uppercase tracking-wide">
                    {{ type === 'resultat_rejete' || type === 'resultat_renvoye_n0' ? 'Motif' : 'Commentaire' }}
                  </p>
                  <p class="text-sm text-white/90 italic leading-relaxed">"{{ d.commentaire }}"</p>
                </div>
              </InfoCard>
              <div v-if="type === 'resultat_validation_complete'" class="flex justify-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-3 border border-success-300 dark:border-success-700 bg-success-50 dark:bg-success-500/10">
                  <i class="fas fa-trophy text-warning-500"></i>
                  <span class="text-sm font-bold text-success-700 dark:text-success-300">N1 ✓ + N2 ✓ — Validation totale</span>
                </div>
              </div>
            </template>

            <!-- ══ TÂCHE ASSIGNÉE ══ -->
            <template v-else-if="type === 'tache_assignee'">
              <InfoCard color="blue">
                <template #icon><i class="fas fa-user-plus text-white"></i></template>
                <template #label>Tâche assignée</template>
                <template #title>{{ d.tache_titre }}</template>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                  <MetaRow v-if="d.assigned_by" icon="fa-user" label="Assigné par" :value="d.assigned_by" color="brand" />
                  <MetaRow v-if="d.tache_echeance" icon="fa-calendar-alt" label="Échéance" :value="formatDate(d.tache_echeance)" color="warning" />
                </div>
                <div v-if="d.resources_count" class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ d.resources_count }} ressource(s) disponible(s)</p>
                </div>
              </InfoCard>
              <ActionButton v-if="d.tache_id" label="Voir la tâche" icon="fa-arrow-right" @click="goToTache" />
            </template>

            <!-- ══ STATUT MODIFIÉ (tache_statut_auto_changed déjà géré, task_updated reste) ══ -->
            <template v-else-if="type === 'task_updated'">
              <InfoCard color="purple">
                <template #icon><i class="fas fa-edit text-white"></i></template>
                <template #label>Tâche modifiée</template>
                <template #title>{{ d.tache_titre || d.task_title }}</template>
                <MetaRow v-if="d.updated_by_nom" icon="fa-user" label="Modifié par" :value="d.updated_by_nom" color="purple" class="mt-3" />
                <div v-if="d.changes?.length" class="mt-3 space-y-2">
                  <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide">Modifications</p>
                  <div v-for="change in d.changes" :key="change.field"
                    class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-purple-400">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ change.label }}</p>
                    <div class="flex items-center gap-2 text-sm">
                      <span class="text-error-600 dark:text-error-400 line-through">{{ change.old_value || 'Vide' }}</span>
                      <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                      <span class="text-success-600 dark:text-success-400 font-semibold">{{ change.new_value || 'Vide' }}</span>
                    </div>
                  </div>
                </div>
              </InfoCard>
            </template>

            <!-- ══ BYPASS ══ -->
            <template v-else-if="type === 'bypass_activated' || type === 'escalades_abusives' || type === 'abusive_escalation_alert'">
              <InfoCard :color="type === 'bypass_activated' ? 'indigo' : 'red'">
                <template #icon><i :class="['fas', type === 'bypass_activated' ? 'fa-shield-alt' : 'fa-exclamation-triangle', 'text-white']"></i></template>
                <template #label>{{ type === 'bypass_activated' ? 'Bypass activé' : 'Alerte escalades abusives' }}</template>
                <template #title>{{ d.tache_titre }}</template>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                  <MetaRow v-if="d.author_nom || d.abuser_nom" icon="fa-user"
                    :label="type === 'bypass_activated' ? 'Activé par' : 'Utilisateur'"
                    :value="d.author_nom || d.abuser_nom" color="brand" />
                  <MetaRow v-if="d.bypass_count || d.consecutive_count" icon="fa-redo"
                    label="Bypass(s)"
                    :value="String(d.bypass_count || d.consecutive_count)" color="error" />
                </div>
                <div v-if="d.motif_bypass" class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border-l-2 border-purple-400">
                  <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-1 uppercase tracking-wide">Motif</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300 italic">"{{ d.motif_bypass }}"</p>
                </div>
              </InfoCard>
            </template>

            <!-- ══ INVITATION PROJET ══ -->
            <template v-else-if="type === 'projet_invitation'">
              <InfoCard color="purple">
                <template #icon><i class="fas fa-project-diagram text-white"></i></template>
                <template #label>Projet</template>
                <template #title>{{ d.projet_nom }}</template>
                <div class="space-y-2 mt-3">
                  <MetaRow v-if="d.inviter_nom" icon="fa-user" label="Invité par" :value="d.inviter_nom" color="purple" />
                  <MetaRow v-if="d.role" icon="fa-user-tag" label="Rôle proposé" :value="roleLabel(d.role)" color="purple" />
                </div>
              </InfoCard>
              <div v-if="d.expires_at" class="flex items-center gap-2 p-3 rounded-3 bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-700">
                <i class="fas fa-clock text-warning-500"></i>
                <span class="text-sm font-semibold text-warning-700 dark:text-warning-300">Expire le {{ formatDate(d.expires_at) }}</span>
              </div>
              <ActionButton v-if="d.token" label="Voir l'invitation" icon="fa-arrow-right"
                @click="router.push(`/invitations/projet/${d.token}`); close()" />
            </template>

            <!-- ══ INVITATION WORKSPACE ══ -->
            <template v-else-if="type === 'workspace_invitation'">
              <InfoCard color="brand">
                <template #icon>
                  <img v-if="d.workspace_logo" :src="d.workspace_logo" class="w-full h-full object-cover" />
                  <span v-else class="text-white font-bold text-sm">{{ initials(d.workspace_name) }}</span>
                </template>
                <template #label>Workspace</template>
                <template #title>{{ d.workspace_name }}</template>
                <div class="space-y-2 mt-3">
                  <MetaRow v-if="d.inviter_name" icon="fa-user" label="Invité par" :value="d.inviter_name" color="brand" />
                  <MetaRow v-if="d.role" icon="fa-user-tag" label="Rôle proposé" :value="roleLabel(d.role)" color="brand" />
                </div>
                <div v-if="d.invitation_message" class="mt-3 p-3 bg-white/10 rounded-3 border-l-2 border-white/40">
                  <p class="text-xs font-semibold text-white/70 mb-1 uppercase tracking-wide">Message</p>
                  <p class="text-sm text-white/90 italic">"{{ d.invitation_message }}"</p>
                </div>
              </InfoCard>
              <div v-if="d.expires_at" class="flex items-center gap-2 p-3 rounded-3 bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-700">
                <i class="fas fa-clock text-warning-500"></i>
                <span class="text-sm font-semibold text-warning-700 dark:text-warning-300">Expire le {{ formatDate(d.expires_at) }}</span>
              </div>
              <ActionButton v-if="d.action_url" label="Voir l'invitation" icon="fa-arrow-right"
                @click="goToUrl(d.action_url)" />
            </template>

            <!-- ══ RESPONSABLE CHANGÉ ══ -->
            <template v-else-if="type === 'responsable_changed'">
              <InfoCard color="purple">
                <template #icon><i class="fas fa-user-shield text-white"></i></template>
                <template #label>Activité</template>
                <template #title>{{ d.activite_nom }}</template>
                <div class="space-y-2 mt-3">
                  <MetaRow v-if="d.old_responsable" icon="fa-user-times" label="Ancien responsable" :value="d.old_responsable" color="error" />
                  <MetaRow v-if="d.new_responsable" icon="fa-user-check" label="Nouveau responsable" :value="d.new_responsable" color="success" />
                </div>
              </InfoCard>
              <ActionButton v-if="d.activite_id" label="Voir l'activité" icon="fa-arrow-right"
                @click="router.push(`/activites/${d.activite_id}`); close()" />
            </template>

            <!-- ══ MEMBRE ACTIVITÉ ══ -->
            <template v-else-if="['activite_member_added','activite_member_removed','activite_member_permissions_updated'].includes(type)">
              <InfoCard :color="type === 'activite_member_removed' ? 'red' : 'blue'">
                <template #icon>
                  <i :class="['fas', type === 'activite_member_removed' ? 'fa-user-times' : type === 'activite_member_permissions_updated' ? 'fa-user-cog' : 'fa-user-plus', 'text-white']"></i>
                </template>
                <template #label>Activité — {{ d.activite_nom }}</template>
                <template #title>{{ d.projet_nom }}</template>
                <div class="space-y-2 mt-3">
                  <MetaRow v-if="d.added_by_nom || d.removed_by_nom || d.updated_by_nom" icon="fa-user"
                    label="Par" :value="d.added_by_nom || d.removed_by_nom || d.updated_by_nom" color="brand" />
                  <MetaRow v-if="d.role" icon="fa-user-tag" label="Rôle" :value="d.role" color="brand" />
                </div>
              </InfoCard>
              <ActionButton v-if="d.url" label="Voir l'activité" icon="fa-arrow-right" @click="goToUrl(d.url)" />
            </template>

            <!-- ══ DOCUMENTS ══ -->
            <template v-else-if="['document_uploaded','document_deleted','document_shared','document_permission_granted'].includes(type)">
              <InfoCard :color="type === 'document_deleted' ? 'red' : type === 'document_shared' ? 'cyan' : 'yellow'">
                <template #icon>
                  <i :class="['fas', type === 'document_deleted' ? 'fa-trash' : type === 'document_shared' ? 'fa-share-alt' : 'fa-file-upload', 'text-white']"></i>
                </template>
                <template #label>{{ type === 'document_deleted' ? 'Document supprimé' : type === 'document_shared' ? 'Document partagé' : 'Nouveau document' }}</template>
                <template #title>{{ d.document_nom }}</template>
                <MetaRow v-if="d.uploaded_by || d.deleted_by || d.shared_by" icon="fa-user"
                  :label="type === 'document_deleted' ? 'Supprimé par' : type === 'document_shared' ? 'Partagé par' : 'Ajouté par'"
                  :value="d.uploaded_by || d.deleted_by || d.shared_by" color="brand"
                  class="mt-3" />
              </InfoCard>
              <ActionButton
                v-if="type !== 'document_deleted'"
                :label="type === 'document_shared' ? 'Voir les partagés' : 'Voir les documents'"
                icon="fa-folder-open"
                @click="router.push(type === 'document_shared' ? '/documents?tab=shared' : '/documents'); close()" />
            </template>

            <!-- ══ SCORE / ÉVALUATION ══ -->
            <template v-else-if="type === 'score_updated'">
              <InfoCard color="purple">
                <template #icon><i class="fas fa-chart-bar text-white"></i></template>
                <template #label>Score mis à jour</template>
                <template #title>{{ d.critere || 'Critère' }}</template>
                <div class="grid grid-cols-2 gap-3 mt-3">
                  <div class="p-3 rounded-3 text-center" :class="d.valeur > 0 ? 'bg-success-50 dark:bg-success-500/10' : 'bg-error-50 dark:bg-error-500/10'">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Impact</p>
                    <p class="text-xl font-bold" :class="d.valeur > 0 ? 'text-success-600 dark:text-success-400' : 'text-error-600 dark:text-error-400'">
                      {{ d.valeur > 0 ? '+' : '' }}{{ d.valeur }}
                    </p>
                  </div>
                  <div class="p-3 rounded-3 bg-gray-50 dark:bg-gray-800 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Décision</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ d.decision || '—' }}</p>
                  </div>
                </div>
                <div v-if="d.periode_start" class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3">
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Période : {{ formatDate(d.periode_start) }} → {{ formatDate(d.periode_end) }}
                  </p>
                </div>
              </InfoCard>
            </template>

            <!-- ══ ABONNEMENT / TRIAL ══ -->
            <template v-else-if="['trial_expiring','trial_expired','trial_extended','subscription_limit_reached','workspace_suspended'].includes(type)">
              <InfoCard :color="['trial_expired','workspace_suspended'].includes(type) ? 'red' : type === 'trial_extended' ? 'green' : 'orange'">
                <template #icon>
                  <i :class="['fas', type === 'trial_extended' ? 'fa-calendar-plus' : type === 'workspace_suspended' ? 'fa-ban' : 'fa-clock', 'text-white']"></i>
                </template>
                <template #label>{{ d.workspace_nom || 'Workspace' }}</template>
                <template #title>
                  {{ type === 'trial_expiring' ? `${d.remaining_days} jour(s) restant(s)`
                   : type === 'trial_expired' ? 'Période d\'essai expirée'
                   : type === 'trial_extended' ? `Prolongé à ${d.new_duration_days} jours`
                   : type === 'workspace_suspended' ? 'Workspace suspendu'
                   : 'Limite atteinte' }}
                </template>
                <div v-if="d.reason || d.limit_type" class="mt-3 p-3 bg-white/10 rounded-3">
                  <p class="text-sm text-white/90">{{ d.reason || `Limite "${d.limit_type}" atteinte (${d.current_value}/${d.max_value})` }}</p>
                </div>
              </InfoCard>
            </template>

            <!-- ══ TEAMS ══ -->
            <template v-else-if="type.startsWith('team_')">
              <InfoCard :color="d.color || 'purple'">
                <template #icon><i :class="['fas', d.icon || notifIcon, 'text-white']"></i></template>
                <template #label>{{ d.team_name || 'Équipe' }}</template>
                <template #title>{{ d.title || d.announcement_title || d.event_title || d.resource_title }}</template>
                <div v-if="d.announcement_content || d.event_description || d.resource_description"
                  class="mt-3 p-3 bg-white/10 rounded-3">
                  <p class="text-sm text-white/90 leading-relaxed">
                    {{ d.announcement_content || d.event_description || d.resource_description }}
                  </p>
                </div>
                <div v-if="d.event_start_date" class="mt-3 grid grid-cols-2 gap-2">
                  <MetaRow icon="fa-calendar-alt" label="Début" :value="formatDate(d.event_start_date)" color="brand" />
                  <MetaRow v-if="d.event_end_date" icon="fa-calendar-alt" label="Fin" :value="formatDate(d.event_end_date)" color="brand" />
                </div>
              </InfoCard>
              <ActionButton v-if="d.url" label="Voir" icon="fa-arrow-right" @click="goToUrl(d.url)" />
            </template>

            <!-- ══ ALERTE INACTION / RETOUR ══ -->
            <template v-else-if="['high_inaction_rate_alert','unjustified_return_alert','abusive_escalation_alert'].includes(type)">
              <InfoCard color="red">
                <template #icon><i class="fas fa-exclamation-triangle text-white"></i></template>
                <template #label>Alerte</template>
                <template #title>{{ d.responsable_nom || d.agent_nom || d.abuser_nom }}</template>
                <div class="grid grid-cols-2 gap-3 mt-3">
                  <div class="p-3 bg-error-50 dark:bg-error-500/10 rounded-3 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Taux</p>
                    <p class="text-xl font-bold text-error-600 dark:text-error-400">
                      {{ d.inaction_rate || d.rate || d.bypass_count }}{{ d.inaction_rate || d.rate ? '%' : '' }}
                    </p>
                  </div>
                  <div v-if="d.periode_start" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Période</p>
                    <p class="text-xs text-gray-700 dark:text-gray-300">{{ formatDate(d.periode_start) }}</p>
                  </div>
                </div>
              </InfoCard>
            </template>

            <!-- ══ FALLBACK ══ -->
            <template v-else>
              <div class="p-4 rounded-3 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div v-if="notification.message" class="mb-3">
                  <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Message</p>
                  <p class="text-sm text-gray-900 dark:text-white leading-relaxed">{{ notification.message }}</p>
                </div>
                <!-- Affiche toutes les données disponibles de façon lisible -->
                <div class="space-y-2">
                  <template v-for="(val, key) in readableData" :key="key">
                    <div v-if="val" class="flex items-start gap-2 text-sm">
                      <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide min-w-24">{{ friendlyKey(key) }}</span>
                      <span class="text-gray-900 dark:text-white">{{ val }}</span>
                    </div>
                  </template>
                </div>
              </div>
              <ActionButton v-if="d.url" label="Voir" icon="fa-arrow-right" @click="goToUrl(d.url)" />
            </template>

            <!-- Statut de lecture + libellé type -->
            <div class="flex items-center gap-2 pt-2">
              <span v-if="!notification.read_at"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400 border border-warning-300 dark:border-warning-700">
                <i class="fas fa-circle text-[6px] animate-pulse"></i>
                Non lu
              </span>
              <span v-else
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400 border border-success-300 dark:border-success-700">
                <i class="fas fa-check-circle"></i>
                Lu
              </span>
              <span v-if="typeLabel"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                <i :class="['fas', notifIcon, 'text-[10px]']"></i>
                {{ typeLabel }}
              </span>
            </div>
          </div>

          <!-- Pied de page -->
          <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-2">
              <button v-if="!notification.read_at" @click="emit('mark-read', notification.id)"
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
            <button v-if="actionUrl" @click="handleGoToAction"
              class="px-4 py-2 rounded-3 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 transition-colors flex items-center gap-2">
              {{ actionLabel }}
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed, defineComponent, h } from 'vue';
import { useRouter } from 'vue-router';
import { useNotifications } from '@/composables/useNotifications';

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

// InfoCard : carte colorée avec icon/label/title/subtitle/slot par défaut
const InfoCard = defineComponent({
  props: { color: { type: String, default: 'blue' } },
  setup(props, { slots }) {
    return () => {
      const c = COLOR_CLASSES[props.color] || COLOR_CLASSES.blue;
      return h('div', { class: `rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden` }, [
        // En-tête coloré de la carte
        h('div', { class: `${c.bg} px-5 py-4 flex items-center gap-3` }, [
          h('div', { class: 'w-10 h-10 rounded-3 flex items-center justify-center bg-white/20 border border-white/30 shrink-0 overflow-hidden' },
            slots.icon ? [slots.icon()] : []
          ),
          h('div', { class: 'flex-1 min-w-0' }, [
            slots.label ? h('p', { class: 'text-xs font-semibold text-white/70 uppercase tracking-wide mb-0.5' }, slots.label()) : null,
            slots.title ? h('p', { class: 'text-base font-bold text-white truncate' }, slots.title()) : null,
            slots.subtitle ? slots.subtitle() : null,
          ]),
        ]),
        // Corps de la carte
        h('div', { class: 'bg-white dark:bg-gray-900 px-5 py-4' },
          slots.default ? slots.default() : []
        ),
      ]);
    };
  },
});

// MetaRow : ligne icone + label + valeur
const MetaRow = defineComponent({
  props: { icon: String, label: String, value: String, color: { type: String, default: 'brand' }, class: String },
  setup(props) {
    return () => {
      const c = COLOR_CLASSES[props.color] || COLOR_CLASSES.brand;
      return h('div', { class: `flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3 ${props.class || ''}` }, [
        h('div', { class: `w-8 h-8 rounded-2 ${c.ring} flex items-center justify-center shrink-0` },
          h('i', { class: `fas ${props.icon} ${c.text} text-sm` })
        ),
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
  props: { label: String, icon: { type: String, default: 'fa-arrow-right' } },
  emits: ['click'],
  setup(props, { emit }) {
    return () => h('button', {
      onClick: () => emit('click'),
      class: 'w-full px-4 py-2.5 rounded-3 text-sm font-semibold text-white bg-brand-500 hover:bg-brand-600 transition-colors flex items-center justify-center gap-2',
    }, [
      props.label,
      h('i', { class: `fas ${props.icon}` }),
    ]);
  },
});

// ── Props / composable ────────────────────────────────────────────────────────

const props = defineProps({
  isOpen:       { type: Boolean, required: true },
  notification: { type: Object,  default: null },
});

const emit = defineEmits(['close', 'mark-read', 'delete']);

const router = useRouter();
const { getNotificationIcon } = useNotifications();

const type    = computed(() => props.notification?.type || '');
const d       = computed(() => props.notification?.data || {});
const notifIcon = computed(() => getNotificationIcon(type.value));

// ── Types qui ont un résultat associé ────────────────────────────────────────

const isResultatType = computed(() => [
  'resultat_soumis', 'resultat_soumis_n0', 'resultat_attente_n2',
  'resultat_valide_n1', 'resultat_valide_n2', 'resultat_rejete',
  'resultat_validation_complete', 'resultat_rejete_n2_info',
  'resultat_approuve_n0', 'resultat_renvoye_n0', 'resultat_transmis_auto',
  'validation_n1_confirmee', 'validation_n2_confirmee', 'rejet_confirme',
].includes(type.value));

const resultatCardColor = computed(() => ({
  resultat_soumis: 'blue', resultat_soumis_n0: 'blue',
  resultat_approuve_n0: 'green', resultat_renvoye_n0: 'orange',
  resultat_transmis_auto: 'indigo',
  resultat_attente_n2: 'orange',
  resultat_valide_n1: 'green', resultat_valide_n2: 'emerald',
  resultat_validation_complete: 'emerald',
  resultat_rejete: 'red', rejet_confirme: 'orange',
  resultat_rejete_n2_info: 'yellow',
  validation_n1_confirmee: 'green', validation_n2_confirmee: 'emerald',
}[type.value] || 'blue'));

const resultatCardLabel = computed(() => ({
  resultat_soumis: 'Résultat soumis — à valider',
  resultat_soumis_n0: 'Résultat soumis au N0',
  resultat_approuve_n0: 'Approuvé par le N0',
  resultat_renvoye_n0: 'Renvoyé par le N0',
  resultat_transmis_auto: 'Transmis automatiquement',
  resultat_attente_n2: 'En attente de validation N2',
  resultat_valide_n1: 'Validé N1',
  resultat_valide_n2: 'Validé N2',
  resultat_validation_complete: 'Validation complète',
  resultat_rejete: 'Résultat rejeté',
  rejet_confirme: 'Rejet confirmé',
  resultat_rejete_n2_info: 'Information de rejet N2',
  validation_n1_confirmee: 'Décision N1 enregistrée',
  validation_n2_confirmee: 'Décision N2 enregistrée',
}[type.value] || 'Résultat'));

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
  return typeLabel.value || 'Notification';
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

const ROLE_LABELS = {
  owner: 'Propriétaire', admin: 'Administrateur', manager: 'Gestionnaire',
  member: 'Membre', viewer: 'Observateur',
  responsable_n1: 'Responsable N1', responsable_n2: 'Responsable N2',
  cadre: 'Cadre', stagiaire: 'Stagiaire',
};
const roleLabel = (r) => ROLE_LABELS[r] || r;

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
  if (confirm('Supprimer cette notification ?')) {
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
