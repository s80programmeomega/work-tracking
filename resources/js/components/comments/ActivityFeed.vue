<template>
  <div class="activity-feed">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="fas fa-stream mr-2"></i>
          Fil d'activité
        </h5>

        <!-- Filter dropdown -->
        <div class="dropdown">
          <button
            class="btn btn-sm btn-outline-secondary dropdown-toggle"
            type="button"
            data-toggle="dropdown"
          >
            <i class="fas fa-filter mr-1"></i>
            {{ filterLabel }}
          </button>
          <div class="dropdown-menu dropdown-menu-right">
            <button class="dropdown-item" @click="changeFilter('all')">
              Toutes les activités
            </button>
            <button class="dropdown-item" @click="changeFilter('recent')">
              Activités récentes
            </button>
            <button class="dropdown-item" @click="changeFilter('created')">
              Créations
            </button>
            <button class="dropdown-item" @click="changeFilter('updated')">
              Modifications
            </button>
            <button class="dropdown-item" @click="changeFilter('completed')">
              Complétées
            </button>
          </div>
        </div>
      </div>

      <div class="card-body">
        <!-- Loading state -->
        <div v-if="loading && activities.length === 0" class="text-center py-5">
          <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
          <p class="mt-3 text-muted">Chargement des activités...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="alert alert-danger">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          {{ error }}
        </div>

        <!-- Empty state -->
        <div v-else-if="activities.length === 0" class="text-center py-5">
          <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
          <p class="text-muted">Aucune activité pour le moment.</p>
        </div>

        <!-- Activities list grouped by date -->
        <div v-else class="activity-list">
          <div
            v-for="(dateActivities, date) in activitiesByDate"
            :key="date"
            class="activity-group mb-4"
          >
            <div class="activity-date-header mb-3">
              <strong class="text-muted">{{ date }}</strong>
            </div>

            <ActivityItem
              v-for="activity in dateActivities"
              :key="activity.id"
              :activity="activity"
            />
          </div>

          <!-- Load more button -->
          <div v-if="hasMorePages" class="text-center mt-4">
            <button
              class="btn btn-outline-primary"
              :disabled="loading"
              @click="handleLoadMore"
            >
              <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
              Charger plus d'activités
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useActivityFeed } from '../../composables/useActivityFeed';
import ActivityItem from './ActivityItem.vue';

export default {
  name: 'ActivityFeed',
  components: {
    ActivityItem,
  },
  props: {
    subjectType: {
      type: String,
      default: null,
    },
    subjectId: {
      type: [String, Number],
      default: null,
    },
    userId: {
      type: [String, Number],
      default: null,
    },
    autoLoad: {
      type: Boolean,
      default: true,
    },
  },
  setup(props) {
    const {
      activities,
      loading,
      error,
      hasMorePages,
      activitiesByDate,
      fetchFeed,
      fetchRecent,
      fetchForSubject,
      fetchByUser,
      fetchByLogName,
      loadMore,
    } = useActivityFeed();

    const currentFilter = ref('all');

    const filterLabel = computed(() => {
      const labels = {
        all: 'Tous',
        recent: 'Récents',
        created: 'Créations',
        updated: 'Modifications',
        completed: 'Complétés',
      };
      return labels[currentFilter.value] || 'Tous';
    });

    onMounted(() => {
      if (props.autoLoad) {
        loadActivities();
      }
    });

    const loadActivities = async () => {
      try {
        if (props.subjectType && props.subjectId) {
          // Load activities for a specific subject
          await fetchForSubject(props.subjectType, props.subjectId);
        } else if (props.userId) {
          // Load activities for a specific user
          await fetchByUser(props.userId);
        } else {
          // Load general feed
          await fetchFeed();
        }
      } catch (err) {
        console.error('Error loading activities:', err);
      }
    };

    const changeFilter = async (filter) => {
      currentFilter.value = filter;

      try {
        switch (filter) {
          case 'recent':
            await fetchRecent();
            break;
          case 'created':
          case 'updated':
          case 'completed':
            await fetchByLogName(filter);
            break;
          default:
            await loadActivities();
        }
      } catch (err) {
        console.error('Error changing filter:', err);
      }
    };

    const handleLoadMore = async () => {
      try {
        await loadMore();
      } catch (err) {
        console.error('Error loading more activities:', err);
      }
    };

    return {
      activities,
      loading,
      error,
      hasMorePages,
      activitiesByDate,
      currentFilter,
      filterLabel,
      loadActivities,
      changeFilter,
      handleLoadMore,
    };
  },
};
</script>

<style scoped>
.activity-feed {
  margin-bottom: 2rem;
}

.activity-date-header {
  position: sticky;
  top: 0;
  background: #f8f9fa;
  padding: 0.5rem 1rem;
  margin: 0 -1rem;
  border-bottom: 1px solid #dee2e6;
  z-index: 1;
}

.activity-list {
  max-height: 600px;
  overflow-y: auto;
}

.activity-group:last-child {
  margin-bottom: 0 !important;
}
</style>
