<!-- resources\js\components\layout\header\NotificationItem.vue -->
<template>
  <div
    dusk="notification-item"
    class="group relative flex gap-3 border-b border-gray-100 dark:border-gray-800 p-4 cursor-pointer transition-colors duration-150"
    :class="notification.read_at
      ? 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50'
      : 'bg-brand-50 dark:bg-brand-500/5 hover:bg-brand-100/60 dark:hover:bg-brand-500/10'"
    @click="handleClick"
  >
    <!-- Icône envelope lecture / non-lu -->
    <div class="shrink-0 relative mt-0.5">
      <div class="w-9 h-9 rounded-3 flex items-center justify-center transition-colors"
        :class="notification.read_at ? 'bg-gray-100 dark:bg-gray-800' : 'bg-brand-100 dark:bg-brand-500/20'">
        <!-- Enveloppe fermée = non lu -->
        <svg v-if="!notification.read_at" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M3.5 8.187V17.25C3.5 17.664 3.836 18 4.25 18h15.5c.414 0 .75-.336.75-.75V8.187l-7.213 5.03a1.75 1.75 0 0 1-2-.001L3.5 8.187ZM20.5 6.23v.013l-8 5.557a.25.25 0 0 1-.286 0L3.601 6.429A.25.25 0 0 1 3.736 6h16.528a.25.25 0 0 1 .236.23ZM2 6.256V17.25A2.25 2.25 0 0 0 4.25 19.5h15.5A2.25 2.25 0 0 0 22 17.25V6.256A2.25 2.25 0 0 0 19.764 4.5H4.236A2.25 2.25 0 0 0 2 6.256Z"/>
        </svg>
        <!-- Enveloppe ouverte = lu -->
        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.04a2.25 2.25 0 0 1 2.134 0l7.5 4.04a2.25 2.25 0 0 1 1.183 1.98V19.5Z"/>
        </svg>
      </div>
      <!-- Point rouge urgent -->
      <div v-if="isUrgent && !notification.read_at"
        class="absolute -top-1 -right-1 w-3 h-3 bg-error-500 rounded-full border-2 border-white dark:border-gray-900">
      </div>
    </div>

    <!-- Contenu -->
    <div class="flex-1 min-w-0">
      <!-- Titre + badge -->
      <div class="flex items-start gap-2 mb-0.5">
        <p class="text-sm font-semibold text-gray-900 dark:text-white flex-1 line-clamp-1 leading-5">
          {{ title }}
        </p>
        <span v-if="badge"
          class="shrink-0 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
          :class="badge.class">
          {{ badge.text }}
        </span>
      </div>

      <!-- Ligne descriptive (qui a fait quoi) -->
      <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-1 leading-relaxed mb-1">
        {{ contextLine }}
      </p>

      <!-- Métadonnées secondaires -->
      <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-gray-400 dark:text-gray-500">
        <span class="flex items-center gap-1">
          <!-- horloge -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/></svg>
          {{ notification.time_ago }}
        </span>
        <span v-if="taskName" class="flex items-center gap-1 text-gray-500 dark:text-gray-400 font-medium max-w-40 truncate">
          <!-- tâche -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg>
          {{ taskName }}
        </span>
        <span v-if="projectName" class="flex items-center gap-1 text-gray-500 dark:text-gray-400 max-w-32 truncate">
          <!-- projet -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/></svg>
          {{ projectName }}
        </span>
        <span v-if="d.taux_realisation != null"
          class="flex items-center gap-1 text-success-600 dark:text-success-400 font-semibold">
          <!-- graphe -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
          {{ d.taux_realisation }}%
        </span>
      </div>
    </div>

    <!-- Actions au survol -->
    <div class="shrink-0 flex items-start gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150 mt-0.5">
      <button v-if="!notification.read_at" type="button"
        class="p-1.5 rounded-3 hover:bg-success-50 dark:hover:bg-success-500/10 text-success-600 dark:text-success-400 transition-colors"
        @click.stop="emit('mark-read', notification.id)" :title="$t('notifications.mark_read')">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
      </button>
      <button type="button"
        class="p-1.5 rounded-3 hover:bg-error-50 dark:hover:bg-error-500/10 text-error-600 dark:text-error-400 transition-colors"
        @click.stop="confirmDelete" :title="$t('notifications.delete')">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNotifications } from '@/composables/useNotifications';

const { t } = useI18n();

const props = defineProps({
  notification: { type: Object, required: true },
});

const emit = defineEmits(['click', 'mark-read', 'delete']);

const { getNotificationIcon, getNotificationColor } = useNotifications();

// Le type vient de notification.type (déjà extrait de data.type par formatNotification côté API)
const type = computed(() => props.notification.type || '');
const d = computed(() => props.notification.data || {});

const icon = computed(() => getNotificationIcon(type.value));

const COLOR_BG = {
  blue: 'bg-brand-500', orange: 'bg-warning-500', green: 'bg-success-500',
  purple: 'bg-purple-500', cyan: 'bg-brand-400', indigo: 'bg-purple-600',
  red: 'bg-error-500', yellow: 'bg-warning-400', emerald: 'bg-success-400',
  brand: 'bg-brand-500', gray: 'bg-gray-400',
};

const iconBgClass = computed(() => COLOR_BG[getNotificationColor(type.value)] || 'bg-gray-400');

const isUrgent = computed(() =>
  ['resultat_attente_n2', 'resultat_rejete', 'task_due_soon', 'deadline_approaching',
   'escalades_abusives', 'abusive_escalation_alert', 'trial_expired', 'workspace_suspended'].includes(type.value)
);

const BADGES = {
  resultat_soumis:           { text: 'À valider',  class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
  resultat_soumis_n0:        { text: 'À valider',  class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
  resultat_attente_n2:       { text: 'Urgent',     class: 'bg-error-100 text-error-700 dark:bg-error-900/30 dark:text-error-400' },
  resultat_valide_n1:        { text: 'Validé N1',  class: 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' },
  resultat_valide_n2:        { text: 'Validé N2',  class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
  resultat_rejete:           { text: 'Rejeté',     class: 'bg-error-100 text-error-700 dark:bg-error-900/30 dark:text-error-400' },
  resultat_validation_complete: { text: 'Complet', class: 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' },
  resultat_rejete_n2_info:   { text: 'Info',       class: 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' },
  resultat_approuve_n0:      { text: 'Approuvé',   class: 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' },
  resultat_renvoye_n0:       { text: 'Renvoyé',    class: 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' },
  workspace_invitation:      { text: 'Invitation', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
  projet_invitation:         { text: 'Invitation', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
  tache_assignee:            { text: 'Assigné',    class: 'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400' },
  task_due_soon:             { text: 'Urgent',     class: 'bg-error-100 text-error-700 dark:bg-error-900/30 dark:text-error-400' },
  bypass_activated:          { text: 'Bypass',     class: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
  escalades_abusives:        { text: 'Alerte',     class: 'bg-error-100 text-error-700 dark:bg-error-900/30 dark:text-error-400' },
  document_shared:           { text: 'Partagé',    class: 'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400' },
  trial_expiring:            { text: 'Bientôt',    class: 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' },
  trial_expired:             { text: 'Expiré',     class: 'bg-error-100 text-error-700 dark:bg-error-900/30 dark:text-error-400' },
  tache_statut_auto_changed: { text: 'Auto',       class: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' },
};

const badge = computed(() => BADGES[type.value] ?? null);

// Titre contextuel construit depuis les vrais champs de chaque type
const title = computed(() => {
  const t = type.value;
  const data = d.value;

  // Les types avec title explicite dans les données
  if (data.title) return data.title;

  const TITLES = {
    tache_statut_auto_changed: `Statut auto-modifié — ${data.titre || 'Tâche'}`,
    tache_assignee:            `Tâche assignée — ${data.tache_titre || 'Tâche'}`,
    tache_resources:           `Ressources ajoutées — ${data.tache_titre || 'Tâche'}`,
    tache_partie_terminee:     `Sous-tâche terminée — ${data.tache_titre || 'Tâche'}`,
    sous_tache_assigned:       `Sous-tâche assignée — ${data.titre || 'Sous-tâche'}`,
    sous_tache_overdue:        `Sous-tâche en retard — ${data.titre || 'Sous-tâche'}`,
    resultat_soumis:           `Résultat soumis — ${data.tache_titre || 'Tâche'}`,
    resultat_soumis_n0:        `Résultat soumis au N0 — ${data.tache_titre || 'Tâche'}`,
    resultat_approuve_n0:      `Résultat approuvé N0 — ${data.tache_titre || 'Tâche'}`,
    resultat_renvoye_n0:       `Résultat renvoyé — ${data.tache_titre || 'Tâche'}`,
    resultat_transmis_auto:    `Transmis automatiquement — ${data.tache_titre || 'Tâche'}`,
    resultat_attente_n2:       `En attente de validation N2 — ${data.tache_titre || 'Tâche'}`,
    resultat_valide_n1:        `Validé par le N1 — ${data.tache_titre || 'Tâche'}`,
    resultat_valide_n2:        `Validé N2 — ${data.tache_titre || 'Tâche'}`,
    resultat_rejete:           `Résultat rejeté — ${data.tache_titre || 'Tâche'}`,
    resultat_validation_complete: `Validation complète — ${data.tache_titre || 'Tâche'}`,
    resultat_rejete_n2_info:   `Rejet N2 — ${data.tache_titre || 'Tâche'}`,
    validation_n1_confirmee:   `Décision N1 enregistrée — ${data.tache_titre || 'Tâche'}`,
    validation_n2_confirmee:   `Décision N2 enregistrée — ${data.tache_titre || 'Tâche'}`,
    rejet_confirme:            `Rejet confirmé — ${data.tache_titre || 'Tâche'}`,
    bypass_activated:          `Bypass activé — ${data.tache_titre || 'Tâche'}`,
    escalades_abusives:        `Escalades abusives — ${data.tache_titre || 'Tâche'}`,
    abusive_escalation_alert:  `Alerte escalades — ${data.tache_titre || 'Tâche'}`,
    score_updated:             `Score mis à jour — ${data.tache_titre || 'Évaluation'}`,
    evaluation_sheet_ready:    `Fiche d'évaluation disponible — ${data.agent_nom || 'Agent'}`,
    projet_invitation:         `Invitation — ${data.projet_nom || 'Projet'}`,
    workspace_invitation:      `Invitation workspace — ${data.workspace_name || 'Workspace'}`,
    workspace_member_added:    `Nouveau membre — ${data.workspace_name || 'Workspace'}`,
    workspace_suspended:       `Workspace suspendu — ${data.workspace_nom || 'Workspace'}`,
    trial_expiring:            `Essai bientôt expiré — ${data.workspace_nom || 'Workspace'}`,
    trial_expired:             `Essai expiré — ${data.workspace_nom || 'Workspace'}`,
    trial_extended:            `Essai prolongé — ${data.workspace_nom || 'Workspace'}`,
    subscription_limit_reached:`Limite atteinte — ${data.workspace_nom || 'Workspace'}`,
    responsable_changed:       `Responsable modifié — ${data.activite_nom || 'Activité'}`,
    activite_member_added:     `Ajouté à l'activité — ${data.activite_nom || 'Activité'}`,
    activite_member_removed:   `Retiré de l'activité — ${data.activite_nom || 'Activité'}`,
    activite_member_permissions_updated: `Permissions mises à jour — ${data.activite_nom || 'Activité'}`,
    document_uploaded:         `Document ajouté — ${data.document_nom || 'Fichier'}`,
    document_deleted:          `Document supprimé — ${data.document_nom || 'Fichier'}`,
    document_shared:           `Document partagé — ${data.document_nom || 'Fichier'}`,
    document_permission_granted: `Accès accordé — ${data.document_nom || 'Document'}`,
    team_announcement:         `Annonce — ${data.team_name || 'Équipe'}`,
    team_event:                `Événement — ${data.team_name || 'Équipe'}`,
    team_member_added:         `Nouveau membre — ${data.team_name || 'Équipe'}`,
    team_member_joined:        `Membre rejoint — ${data.team_name || 'Équipe'}`,
    team_resource:             `Ressource ajoutée — ${data.team_name || 'Équipe'}`,
    high_inaction_rate_alert:  `Taux d'inaction élevé — ${data.responsable_nom || 'Responsable'}`,
    unjustified_return_alert:  `Retours injustifiés — ${data.agent_nom || 'Agent'}`,
    deadline_approaching:      `Échéance proche — ${data.tache_titre || 'Tâche'}`,
    support_ticket_new:        `Nouveau ticket #${data.ticket_number || '?'} — ${data.subject || data.submitter_name || ''}`,
    support_ticket_reply:      `Réponse sur votre ticket #${data.ticket_number || '?'} — ${data.subject || ''}`,
    support_ticket_status_changed: `Statut mis à jour — ticket #${data.ticket_number || '?'}`,
  };

  return TITLES[t] || props.notification.title || t;
});

// Ligne descriptive : qui a fait l'action
const contextLine = computed(() => {
  const t = type.value;
  const data = d.value;

  if (data.message) return data.message;

  switch (t) {
    case 'tache_statut_auto_changed': {
      const STATUTS = { a_faire: 'À faire', en_cours: 'En cours', termine: 'Terminé', en_retard: 'En retard', a_refaire: 'À refaire', annule: 'Annulé' };
      return `${STATUTS[data.old_statut] || data.old_statut} → ${STATUTS[data.new_statut] || data.new_statut}`;
    }
    case 'tache_assignee':           return data.assigned_by ? `Assigné par ${data.assigned_by}` : '';
    case 'tache_resources':          return data.attached_by ? `Ressources ajoutées par ${data.attached_by}` : '';
    case 'tache_partie_terminee':    return data.assigne_nom ? `Terminé par ${data.assigne_nom}` : '';
    case 'sous_tache_assigned':      return data.assigned_by ? `Assigné par ${data.assigned_by}` : '';
    case 'resultat_soumis':          return data.auteur_nom ? `Soumis par ${data.auteur_nom}${data.taux_realisation != null ? ` · ${data.taux_realisation}%` : ''}` : '';
    case 'resultat_soumis_n0':       return data.author_nom ? `Soumis par ${data.author_nom}${data.taux_realisation != null ? ` · ${data.taux_realisation}%` : ''}` : '';
    case 'resultat_approuve_n0':     return 'Approuvé par le N0, transmis au N1';
    case 'resultat_renvoye_n0':      return data.commentaire ? `Renvoyé — "${data.commentaire.slice(0, 60)}${data.commentaire.length > 60 ? '…' : ''}"` : 'Renvoyé par le N0';
    case 'resultat_transmis_auto':   return 'Transmis automatiquement après délai N0';
    case 'resultat_attente_n2':      return data.auteur_nom ? `Approuvé N1, en attente N2 — ${data.auteur_nom}` : 'En attente de validation N2';
    case 'resultat_valide_n1':       return data.validateur_nom ? `Validé par ${data.validateur_nom}${data.taux_realisation != null ? ` · ${data.taux_realisation}%` : ''}` : '';
    case 'resultat_valide_n2':       return data.validateur_nom ? `Validé N2 par ${data.validateur_nom}` : '';
    case 'resultat_rejete':          return data.validateur_nom
      ? `Rejeté par ${data.validateur_nom}${data.commentaire ? ` — "${data.commentaire.slice(0, 50)}…"` : ''}`
      : '';
    case 'resultat_validation_complete': return 'N1 ✓ + N2 ✓ — validation totale';
    case 'resultat_rejete_n2_info':  return data.commentaire ? `Motif : ${data.commentaire.slice(0, 60)}` : 'Rejeté par le N2';
    case 'bypass_activated':         return data.author_nom ? `Activé par ${data.author_nom}` : 'Bypass anti-sabotage activé';
    case 'escalades_abusives':
    case 'abusive_escalation_alert': return data.abuser_nom ? `${data.abuser_nom} — ${data.bypass_count || data.consecutive_count || '?'} bypass(s)` : '';
    case 'score_updated':            return data.critere ? `Critère : ${data.critere} (${data.valeur > 0 ? '+' : ''}${data.valeur})` : '';
    case 'evaluation_sheet_ready':   return data.agent_nom ? `Fiche disponible pour ${data.agent_nom}` : '';
    case 'projet_invitation':        return data.inviter_nom ? `Invité par ${data.inviter_nom} — rôle ${data.role || ''}` : '';
    case 'workspace_invitation':     return data.inviter_name ? `Invité par ${data.inviter_name}` : '';
    case 'workspace_member_added':   return data.inviter_name ? `Ajouté par ${data.inviter_name}` : '';
    case 'responsable_changed':      return data.new_responsable ? `Nouveau responsable : ${data.new_responsable}` : '';
    case 'activite_member_added':    return data.added_by_nom ? `Ajouté par ${data.added_by_nom} — rôle ${data.role || ''}` : '';
    case 'activite_member_removed':  return data.removed_by_nom ? `Retiré par ${data.removed_by_nom}` : '';
    case 'activite_member_permissions_updated': return data.updated_by_nom ? `Permissions mises à jour par ${data.updated_by_nom}` : '';
    case 'document_uploaded':        return data.uploaded_by ? `Ajouté par ${data.uploaded_by}` : '';
    case 'document_deleted':         return data.deleted_by ? `Supprimé par ${data.deleted_by}` : '';
    case 'document_shared':          return data.shared_by ? `Partagé par ${data.shared_by}` : '';
    case 'trial_expiring':           return data.remaining_days != null ? `${data.remaining_days} jour(s) restant(s)` : '';
    case 'trial_extended':           return data.new_duration_days != null ? `Prolongé à ${data.new_duration_days} jours` : '';
    case 'workspace_suspended':      return data.reason ? `Motif : ${data.reason}` : '';
    case 'subscription_limit_reached': return data.limit_type ? `Limite "${data.limit_type}" atteinte` : '';
    case 'team_announcement':        return data.announcement_title || '';
    case 'team_event':               return data.event_title || '';
    case 'team_resource':            return data.resource_title || '';
    case 'high_inaction_rate_alert': return data.inaction_rate != null ? `Taux d'inaction : ${data.inaction_rate}%` : '';
    case 'unjustified_return_alert': return data.rate != null ? `${data.rate}% de retours injustifiés` : '';
    default:                         return '';
  }
});

// Nom de tâche pour la ligne méta (absent pour notifications non-tâche)
const NON_TASK_TYPES = new Set([
  'workspace_invitation', 'projet_invitation', 'workspace_member_added',
  'responsable_changed', 'activite_member_added', 'activite_member_removed',
  'activite_member_permissions_updated', 'document_uploaded', 'document_deleted',
  'document_shared', 'document_permission_granted', 'trial_expiring', 'trial_expired',
  'trial_extended', 'subscription_limit_reached', 'workspace_suspended',
  'team_announcement', 'team_event', 'team_member_added', 'team_member_joined',
  'team_resource', 'evaluation_sheet_ready', 'score_updated',
  'high_inaction_rate_alert', 'unjustified_return_alert',
]);

const taskName = computed(() => {
  if (NON_TASK_TYPES.has(type.value)) return null;
  // tache_statut_auto_changed utilise 'titre' au lieu de 'tache_titre'
  return d.value.tache_titre || d.value.titre || null;
});

const projectName = computed(() => {
  if (['workspace_invitation', 'workspace_member_added', 'workspace_suspended',
       'trial_expiring', 'trial_expired', 'trial_extended', 'subscription_limit_reached',
       'responsable_changed'].includes(type.value)) return null;
  return d.value.projet_nom || d.value.project_name || null;
});


const confirmDelete = () => {
  if (confirm(t('notifications.confirm_delete'))) {
    emit('delete', props.notification.id);
  }
};

const handleClick = () => {
  // Ouvrir le modal de détail — la navigation se fait depuis le modal
  emit('click', props.notification);
};
</script>
