<template>
  <div class="activity-feed">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75z"/></svg>
          Fil d'activité
        </h5>

        <!-- Filter dropdown -->
        <div class="dropdown">
          <button
            class="btn btn-sm btn-outline-secondary dropdown-toggle"
            type="button"
            data-toggle="dropdown"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3z"/></svg>
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
          <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <p class="mt-3 text-muted">Chargement des activités...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="alert alert-danger">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
          {{ error }}
        </div>

        <!-- Empty state -->
        <div v-else-if="activities.length === 0" class="text-center py-5">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-muted mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661z"/></svg>
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
              <svg v-if="loading" class="animate-spin h-4 w-4 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
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
