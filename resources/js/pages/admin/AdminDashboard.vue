<!-- resources/js/pages/admin/AdminDashboard.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 dusk="admin-dashboard-title" class="text-2xl font-semibold text-gray-900 dark:text-white">
            {{ $t('admin.dashboard.title') }}
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $t('admin.dashboard.subtitle') }}
          </p>
        </div>
        <button
          @click="fetchStats"
          :disabled="loading"
          class="px-4 py-2 bg-brand-600 text-white rounded-3 text-sm hover:bg-brand-700 disabled:opacity-50 flex items-center gap-2"
        >
          <i class="fas fa-sync-alt" :class="{ 'animate-spin': loading }"></i>
          {{ $t('admin.dashboard.refresh') }}
        </button>
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-3">
        {{ error }}
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading && !stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 8" :key="i" class="h-24 bg-gray-200 dark:bg-gray-700 animate-pulse rounded-3"></div>
      </div>

      <template v-if="stats">
        <!-- Workspace stats -->
        <section>
          <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ $t('admin.dashboard.workspaces') }}</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <StatCard title="Total" :value="stats.workspaces.total" icon="building" color="blue" />
            <StatCard title="Active" :value="stats.workspaces.active" icon="check-circle" color="green" />
            <StatCard title="Trial" :value="stats.workspaces.trial" icon="clock" color="amber" />
            <StatCard title="Paid" :value="stats.workspaces.paid" icon="credit-card" color="purple" />
            <StatCard title="Expiring soon" :value="stats.workspaces.expiring_soon" icon="alert-triangle" color="amber" :alert="stats.workspaces.expiring_soon > 0" />
            <StatCard title="Expired" :value="stats.workspaces.expired_trials" icon="x-circle" color="red" :alert="stats.workspaces.expired_trials > 0" />
          </div>
        </section>

        <!-- User stats -->
        <section>
          <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ $t('admin.dashboard.users') }}</h2>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <StatCard title="Total users" :value="stats.users.total" icon="users" color="blue" />
            <StatCard title="Active (30 d)" :value="stats.users.active_last_30_days" icon="user-check" color="green" />
            <StatCard title="New (7 d)" :value="stats.users.new_last_7_days" icon="user-plus" color="teal" />
            <StatCard title="Super admins" :value="stats.users.super_admins" icon="shield" color="purple" />
          </div>
        </section>

        <!-- Task stats -->
        <section>
          <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ $t('admin.dashboard.tasks_projects') }}</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <StatCard title="Total tasks" :value="stats.tasks.total" icon="tasks" color="blue" />
            <StatCard title="In progress" :value="stats.tasks.by_status?.en_cours ?? 0" icon="play-circle" color="blue" />
            <StatCard title="Done" :value="stats.tasks.by_status?.termine ?? 0" icon="check-double" color="green" />
            <StatCard title="Overdue" :value="stats.tasks.overdue" icon="exclamation-circle" color="red" :alert="stats.tasks.overdue > 0" />
            <StatCard title="Critical" :value="stats.tasks.critical" icon="fire" color="red" :alert="stats.tasks.critical > 0" />
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-4">
            <StatCard title="Projects" :value="stats.projects.total" icon="folder" color="indigo" />
            <StatCard title="Activities" :value="stats.activities.total" icon="layer-group" color="indigo" />
            <StatCard title="To do" :value="stats.tasks.by_status?.a_faire ?? 0" icon="list-ul" color="gray" />
          </div>
        </section>

        <!-- Task status breakdown -->
        <section>
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
            <i class="fas fa-chart-bar text-gray-400 mr-2"></i>
            Task Status Breakdown
          </h2>
          <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4">
            <div class="space-y-3">
              <div v-for="(statusKey, label) in statusLabels" :key="statusKey" class="flex items-center gap-3">
                <span class="w-28 text-sm text-gray-600 dark:text-gray-400 shrink-0">{{ label }}</span>
                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                  <div
                    class="h-3 rounded-full transition-all duration-500"
                    :class="statusColors[statusKey]"
                    :style="{ width: barWidth(stats.tasks.by_status?.[statusKey] ?? 0) }"
                  ></div>
                </div>
                <span class="w-10 text-right text-sm font-medium text-gray-700 dark:text-gray-300 shrink-0">
                  {{ stats.tasks.by_status?.[statusKey] ?? 0 }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- Growth table (last 7 days) -->
        <section dusk="growth-section">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
            <i class="fas fa-chart-line text-gray-400 mr-2"></i>
            Growth — Last 7 Days
          </h2>
          <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                  <th class="px-4 py-3 text-left">Date</th>
                  <th class="px-4 py-3 text-right">New Workspaces</th>
                  <th class="px-4 py-3 text-right">New Users</th>
                </tr>
              </thead>
              <tbody ref="growthRef" class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr
                  v-for="day in stats.growth"
                  :key="day.date"
                  class="stagger-item hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                >
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ formatDate(day.date) }}</td>
                  <td class="px-4 py-2 text-right">
                    <span v-if="day.new_workspaces > 0" class="font-medium text-blue-600 dark:text-blue-400">+{{ day.new_workspaces }}</span>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                  <td class="px-4 py-2 text-right">
                    <span v-if="day.new_users > 0" class="font-medium text-green-600 dark:text-green-400">+{{ day.new_users }}</span>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
            </div>
          </div>
        </section>

        <!-- Recent workspaces -->
        <section dusk="recent-workspaces-section">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
            <i class="fas fa-clock text-gray-400 mr-2"></i>
            Recent Workspaces
          </h2>
          <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                  <th class="px-4 py-3 text-left">Workspace</th>
                  <th class="px-4 py-3 text-left">Owner</th>
                  <th class="px-4 py-3 text-left">Mode</th>
                  <th class="px-4 py-3 text-left">Status</th>
                  <th class="px-4 py-3 text-left">Trial</th>
                  <th class="px-4 py-3 text-left">Created</th>
                </tr>
              </thead>
              <tbody ref="recentRef" class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr
                  v-for="ws in stats.recent_workspaces"
                  :key="ws.id"
                  class="stagger-item hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                >
                  <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ ws.nom }}</td>
                  <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ ws.owner?.nom ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <SubscriptionBadge :mode="ws.subscription_mode" />
                  </td>
                  <td class="px-4 py-3">
                    <span :class="ws.is_active
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                      class="px-2 py-0.5 rounded-full text-xs font-medium"
                    >
                      {{ ws.is_active ? 'Active' : 'Suspended' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                    <template v-if="ws.subscription_mode === 'trial'">
                      <span v-if="ws.subscription.trial_expired" class="text-red-600 dark:text-red-400 font-medium">Expired</span>
                      <span v-else-if="ws.subscription.expiring_soon" class="text-amber-600 dark:text-amber-400 font-medium">
                        {{ ws.subscription.remaining_trial_days }}d left
                      </span>
                      <span v-else class="text-gray-500 dark:text-gray-400">{{ ws.subscription.remaining_trial_days }}d left</span>
                    </template>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                  <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(ws.created_at) }}</td>
                </tr>
              </tbody>
            </table>
            </div>
          </div>
        </section>

        <!-- Quick links -->
        <div class="flex flex-wrap gap-3">
          <router-link
            to="/admin/workspaces"
            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-3 text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            <i class="fas fa-building mr-2"></i>All Workspaces
          </router-link>
          <router-link
            to="/admin/users"
            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-3 text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            <i class="fas fa-users mr-2"></i>All Users
          </router-link>
          <router-link
            to="/admin/roles"
            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-3 text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            <i class="fas fa-shield-alt mr-2"></i>Roles & Permissions
          </router-link>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import StatCard from '@/components/common/StatCard.vue';
import SubscriptionBadge from '@/components/admin/SubscriptionBadge.vue';
import { useStagger } from '@/composables/useAnimations';
import api from '@/api/axios';

const { t } = useI18n();
const stats = ref(null);
const loading = ref(false);
const error = ref(null);
const { staggerRef: growthRef, applyStagger: applyGrowthStagger } = useStagger(40);
const { staggerRef: recentRef, applyStagger: applyRecentStagger } = useStagger(40);

const statusLabels = computed(() => ({
  a_faire: t('statuts.a_faire'),
  en_cours: t('statuts.en_cours'),
  en_attente: t('statuts.en_attente'),
  termine: t('statuts.termine'),
  en_retard: t('statuts.en_retard'),
  a_refaire: t('statuts.a_refaire'),
  annule: t('statuts.annule'),
}));

const statusColors = {
  a_faire: 'bg-gray-400',
  en_cours: 'bg-blue-500',
  en_attente: 'bg-amber-400',
  termine: 'bg-green-500',
  en_retard: 'bg-red-500',
  a_refaire: 'bg-orange-500',
  annule: 'bg-gray-300',
};

const barWidth = (count) => {
  if (!stats.value?.tasks?.total || stats.value.tasks.total === 0) return '0%';
  return Math.round((count / stats.value.tasks.total) * 100) + '%';
};

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString() : '—';

const fetchStats = async () => {
  loading.value = true;
  error.value = null;
  try {
    const { data } = await api.get('/admin/stats');
    stats.value = data.data;
    applyGrowthStagger();
    applyRecentStagger();
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to load stats.';
  } finally {
    loading.value = false;
  }
};

onMounted(fetchStats);
</script>
