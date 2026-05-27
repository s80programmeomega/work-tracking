<!-- resources/js/pages/admin/AdminDashboard.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 dusk="admin-dashboard-title" class="text-2xl font-semibold text-gray-900 dark:text-white">
            Platform Dashboard
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Global overview of all workspaces, users and subscriptions.
          </p>
        </div>
        <button
          @click="fetchStats"
          :disabled="loading"
          class="px-4 py-2 bg-brand-600 text-white rounded-3 text-sm hover:bg-brand-700 disabled:opacity-50 flex items-center gap-2"
        >
          <i class="fas fa-sync-alt" :class="{ 'animate-spin': loading }"></i>
          Refresh
        </button>
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-3">
        {{ error }}
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading && !stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 6" :key="i" class="h-24 bg-gray-200 dark:bg-gray-700 animate-pulse rounded-3"></div>
      </div>

      <template v-if="stats">
        <!-- Workspace stats cards -->
        <section>
          <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Workspaces</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <StatCard title="Total" :value="stats.workspaces.total" icon="building" color="blue" />
            <StatCard title="Active" :value="stats.workspaces.active" icon="check-circle" color="green" />
            <StatCard title="Trial" :value="stats.workspaces.trial" icon="clock" color="amber" />
            <StatCard title="Paid" :value="stats.workspaces.paid" icon="credit-card" color="purple" />
            <StatCard title="Expiring soon" :value="stats.workspaces.expiring_soon" icon="alert-triangle" color="amber" :alert="stats.workspaces.expiring_soon > 0" />
            <StatCard title="Expired" :value="stats.workspaces.expired_trials" icon="x-circle" color="red" :alert="stats.workspaces.expired_trials > 0" />
          </div>
        </section>

        <!-- User stats cards -->
        <section>
          <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Users</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <StatCard title="Total users" :value="stats.users.total" icon="users" color="blue" />
            <StatCard title="Active (30 d)" :value="stats.users.active_last_30_days" icon="user-check" color="green" />
          </div>
        </section>

        <!-- Recent workspaces -->
        <section dusk="recent-workspaces-section">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
            <i class="fas fa-clock text-gray-400 mr-2"></i>
            Recent Workspaces
          </h2>
          <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden">
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
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr
                  v-for="ws in stats.recent_workspaces"
                  :key="ws.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
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
        </section>

        <!-- Quick links -->
        <div class="flex gap-3">
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
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import StatCard from '@/components/common/StatCard.vue';
import SubscriptionBadge from '@/components/admin/SubscriptionBadge.vue';
import api from '@/api/axios';

const stats = ref(null);
const loading = ref(false);
const error = ref(null);

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString() : '—';

const fetchStats = async () => {
  loading.value = true;
  error.value = null;
  try {
    const { data } = await api.get('/admin/stats');
    stats.value = data.data;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to load stats.';
  } finally {
    loading.value = false;
  }
};

onMounted(fetchStats);
</script>
