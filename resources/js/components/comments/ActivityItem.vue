<template>
  <div class="activity-item mb-3">
    <div class="d-flex align-items-start">
      <!-- Icon -->
      <div class="activity-icon mr-3">
        <div class="icon-circle" :class="`bg-${activity.color}`">
          <i :class="`fas fa-${activity.icon}`"></i>
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
                    <i class="fas fa-arrow-right mx-1"></i>
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

    const getSubjectComponent = (subjectType) => {
      // Return a simple component based on subject type
      return {
        props: ['subject', 'activity'],
        setup(props) {
          return () => {
            switch (props.subject.type) {
              case 'tache':
                return h('div', { class: 'subject-badge badge badge-info' }, [
                  h('i', { class: 'fas fa-tasks mr-1' }),
                  props.subject.titre,
                ]);
              case 'projet':
                return h('div', { class: 'subject-badge badge badge-primary' }, [
                  h('i', { class: 'fas fa-project-diagram mr-1' }),
                  props.subject.nom,
                ]);
              case 'activite':
                return h('div', { class: 'subject-badge badge badge-success' }, [
                  h('i', { class: 'fas fa-clipboard-list mr-1' }),
                  props.subject.nom,
                ]);
              case 'comment':
                return h('div', { class: 'subject-badge badge badge-secondary' }, [
                  h('i', { class: 'fas fa-comment mr-1' }),
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
