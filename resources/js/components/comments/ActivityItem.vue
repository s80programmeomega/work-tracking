<template>
  <div class="activity-item mb-3">
    <div class="d-flex align-items-start">
      <!-- Icon -->
      <div class="activity-icon mr-3">
        <div class="icon-circle" :class="`bg-${activity.color}`">
          <span v-html="getActivityIcon(activity.icon)" class="flex items-center justify-center"></span>
        </div>
      </div>

      <!-- Content -->
      <div class="activity-content flex-grow-1">
        <div class="activity-header d-flex justify-content-between align-items-start">
          <div>
            <strong>{{ activity.human_readable }}</strong>
          </div>
          <small class="text-muted">{{ formatTime(activity.created_at) }}</small>
        </div>

        <!-- Subject details -->
        <div v-if="activity.subject" class="activity-subject mt-2">
          <component
            :is="getSubjectComponent(activity.subject.type)"
            :subject="activity.subject"
            :activity="activity"
          />
        </div>

        <!-- Properties/changes -->
        <div v-if="hasChanges" class="activity-changes mt-2">
          <div class="card">
            <div class="card-body p-2">
              <small class="text-muted">
                <strong>Modifications:</strong>
                <ul class="mb-0 pl-3">
                  <li v-for="(change, key) in changes" :key="key">
                    <strong>{{ formatKey(key) }}:</strong>
                    <span class="text-danger">{{ change.old }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mx-1 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    <span class="text-success">{{ change.new }}</span>
                  </li>
                </ul>
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, h } from 'vue';

export default {
  name: 'ActivityItem',
  props: {
    activity: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const hasChanges = computed(() => {
      return (
        props.activity.properties &&
        props.activity.properties.attributes &&
        props.activity.properties.old
      );
    });

    const changes = computed(() => {
      if (!hasChanges.value) return {};

      const attributes = props.activity.properties.attributes;
      const old = props.activity.properties.old;
      const result = {};

      Object.keys(attributes).forEach((key) => {
        if (old[key] !== attributes[key]) {
          result[key] = {
            old: old[key] || '—',
            new: attributes[key] || '—',
          };
        }
      });

      return result;
    });

    const formatTime = (dateString) => {
      const date = new Date(dateString);
      const now = new Date();
      const diff = (now - date) / 1000; // seconds

      if (diff < 60) return 'à l\'instant';
      if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`;
      if (diff < 86400) return `il y a ${Math.floor(diff / 3600)} h`;
      if (diff < 604800) return `il y a ${Math.floor(diff / 86400)} j`;

      return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
      });
    };

    const formatKey = (key) => {
      const labels = {
        titre: 'Titre',
        nom: 'Nom',
        description: 'Description',
        statut: 'Statut',
        priorite: 'Priorité',
        echeance: 'Échéance',
        taux_realisation: 'Taux de réalisation',
        date_debut: 'Date de début',
        date_fin: 'Date de fin',
      };
      return labels[key] || key;
    };

    const ACTIVITY_ICONS = {
      tasks: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>',
      check: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>',
      edit: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>',
      trash: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>',
      plus: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>',
      comment: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>',
      file: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>',
      user: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>',
    };

    const getActivityIcon = (iconName) => {
      return ACTIVITY_ICONS[iconName] || ACTIVITY_ICONS.check;
    };

    const getSubjectComponent = (subjectType) => {
      // Return a simple component based on subject type
      return {
        props: ['subject', 'activity'],
        setup(props) {
          return () => {
            switch (props.subject.type) {
              case 'tache':
                return h('div', { class: 'subject-badge badge badge-info' }, [
                  h('svg', { xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.5', stroke: 'currentColor', class: 'w-4 h-4 mr-1 inline-block' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' }),
                  ]),
                  props.subject.titre,
                ]);
              case 'projet':
                return h('div', { class: 'subject-badge badge badge-primary' }, [
                  h('svg', { xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.5', stroke: 'currentColor', class: 'w-4 h-4 mr-1 inline-block' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5' }),
                  ]),
                  props.subject.nom,
                ]);
              case 'activite':
                return h('div', { class: 'subject-badge badge badge-success' }, [
                  h('svg', { xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.5', stroke: 'currentColor', class: 'w-4 h-4 mr-1 inline-block' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z' }),
                  ]),
                  props.subject.nom,
                ]);
              case 'comment':
                return h('div', { class: 'subject-badge badge badge-secondary' }, [
                  h('svg', { xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.5', stroke: 'currentColor', class: 'w-4 h-4 mr-1 inline-block' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z' }),
                  ]),
                  'Commentaire',
                ]);
              default:
                return h('div', { class: 'subject-badge badge badge-light' }, props.subject.type);
            }
          };
        },
      };
    };

    return {
      hasChanges,
      changes,
      formatTime,
      formatKey,
      getActivityIcon,
      getSubjectComponent,
    };
  },
};
</script>

<style scoped>
.activity-item {
  padding: 0.75rem 0;
  border-bottom: 1px solid #e9ecef;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  flex-shrink: 0;
}

.icon-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.icon-circle.bg-green {
  background-color: #28a745;
}

.icon-circle.bg-blue {
  background-color: #007bff;
}

.icon-circle.bg-red {
  background-color: #dc3545;
}

.icon-circle.bg-orange {
  background-color: #fd7e14;
}

.icon-circle.bg-purple {
  background-color: #6f42c1;
}

.icon-circle.bg-gray {
  background-color: #6c757d;
}

.activity-content {
  min-width: 0;
}

.activity-header {
  line-height: 1.4;
}

.subject-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

.activity-changes {
  font-size: 0.875rem;
}

.activity-changes ul {
  margin-top: 0.5rem;
}
</style>
