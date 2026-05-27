<!-- resources/js/pages/admin/AdminWorkspaces.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 dusk="admin-workspaces-title" class="text-2xl font-semibold text-gray-900 dark:text-white">
            Workspace Management
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Manage all workspaces, subscriptions and trial periods.
          </p>
        </div>
        <router-link
          to="/admin/dashboard"
          class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
        >
          <i class="fas fa-arrow-left"></i> Back to dashboard
        </router-link>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-3">
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          placeholder="Search workspace..."
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 w-56"
        />
        <select
          v-model="filters.subscription_mode"
          @change="fetchWorkspaces"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
        >
          <option value="">All modes</option>
          <option value="trial">Trial</option>
          <option value="paid">Paid</option>
        </select>
        <select
          v-model="filters.is_active"
          @change="fetchWorkspaces"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
        >
          <option value="">All statuses</option>
          <option value="1">Active</option>
          <option value="0">Suspended</option>
        </select>
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-3">
        {{ error }}
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
        </div>

        <table v-else class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase">
            <tr>
              <th class="px-4 py-3 text-left">Workspace</th>
              <th class="px-4 py-3 text-left">Owner</th>
              <th class="px-4 py-3 text-left">Members</th>
              <th class="px-4 py-3 text-left">Mode</th>
              <th class="px-4 py-3 text-left">Trial</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="!workspaces.length">
              <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No workspaces found.</td>
            </tr>
            <tr
              v-for="ws in workspaces"
              :key="ws.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
            >
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ ws.nom }}</td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ ws.owner?.nom ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ ws.members_count }}</td>
              <td class="px-4 py-3"><SubscriptionBadge :mode="ws.subscription_mode" /></td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                <template v-if="ws.subscription_mode === 'trial'">
                  <span v-if="ws.subscription?.trial_expired" class="text-red-600 font-medium">Expired</span>
                  <span v-else-if="ws.subscription?.expiring_soon" class="text-amber-600 font-medium">
                    {{ ws.subscription.remaining_trial_days }}d left
                  </span>
                  <span v-else>{{ ws.subscription?.remaining_trial_days }}d left</span>
                </template>
                <span v-else>—</span>
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
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <!-- Extend trial -->
                  <button
                    v-if="ws.subscription_mode === 'trial'"
                    @click="openExtendModal(ws)"
                    class="text-xs px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded hover:bg-blue-200 transition-colors"
                    title="Extend trial"
                  >
                    <i class="fas fa-plus-circle mr-1"></i>Extend
                  </button>
                  <!-- Suspend / reactivate -->
                  <button
                    v-if="ws.is_active"
                    @click="openSuspendModal(ws)"
                    class="text-xs px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded hover:bg-red-200 transition-colors"
                    title="Suspend workspace"
                  >
                    <i class="fas fa-ban mr-1"></i>Suspend
                  </button>
                  <button
                    v-else
                    @click="reactivate(ws)"
                    class="text-xs px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded hover:bg-green-200 transition-colors"
                    title="Reactivate workspace"
                  >
                    <i class="fas fa-check-circle mr-1"></i>Reactivate
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
          <p class="text-sm text-gray-500">Page {{ pagination.current_page }} / {{ pagination.last_page }}</p>
          <div class="flex gap-2">
            <button
              @click="page--; fetchWorkspaces()"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded disabled:opacity-40"
            >Prev</button>
            <button
              @click="page++; fetchWorkspaces()"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded disabled:opacity-40"
            >Next</button>
          </div>
        </div>
      </div>

      <!-- Extend trial modal -->
      <div v-if="extendModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 w-full max-w-sm mx-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Extend Trial — {{ extendModal.workspace?.nom }}
          </h3>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New duration (days)</label>
          <input
            v-model.number="extendModal.days"
            type="number"
            min="1"
            max="365"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          />
          <div class="flex justify-end gap-3 mt-4">
            <button @click="extendModal.open = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">Cancel</button>
            <button
              @click="confirmExtend"
              :disabled="extendModal.loading"
              class="px-4 py-2 bg-brand-600 text-white rounded-3 text-sm hover:bg-brand-700 disabled:opacity-50"
            >
              <i class="fas fa-save mr-1"></i>Save
            </button>
          </div>
        </div>
      </div>

      <!-- Suspend modal -->
      <div v-if="suspendModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 w-full max-w-sm mx-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Suspend — {{ suspendModal.workspace?.nom }}
          </h3>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason (optional)</label>
          <textarea
            v-model="suspendModal.reason"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 resize-none"
          ></textarea>
          <div class="flex justify-end gap-3 mt-4">
            <button @click="suspendModal.open = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">Cancel</button>
            <button
              @click="confirmSuspend"
              :disabled="suspendModal.loading"
              class="px-4 py-2 bg-red-600 text-white rounded-3 text-sm hover:bg-red-700 disabled:opacity-50"
            >
              <i class="fas fa-ban mr-1"></i>Suspend
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import SubscriptionBadge from '@/components/admin/SubscriptionBadge.vue';
import api from '@/api/axios';

const workspaces = ref([]);
const pagination = ref(null);
const loading = ref(false);
const error = ref(null);
const search = ref('');
const page = ref(1);
const filters = ref({ subscription_mode: '', is_active: '' });

let debounceTimer = null;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { page.value = 1; fetchWorkspaces(); }, 350);
};

const fetchWorkspaces = async () => {
  loading.value = true;
  error.value = null;
  try {
    const params = { page: page.value, per_page: 20, search: search.value || undefined };
    if (filters.value.subscription_mode) { params.subscription_mode = filters.value.subscription_mode; }
    if (filters.value.is_active !== '') { params.is_active = filters.value.is_active; }
    const { data } = await api.get('/admin/workspaces', { params });
    workspaces.value = data.data;
    pagination.value = { current_page: data.current_page, last_page: data.last_page };
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to load workspaces.';
  } finally {
    loading.value = false;
  }
};

// Extend trial
const extendModal = ref({ open: false, workspace: null, days: 30, loading: false });
const openExtendModal = (ws) => {
  extendModal.value = { open: true, workspace: ws, days: ws.subscription?.remaining_trial_days ?? 30, loading: false };
};
const confirmExtend = async () => {
  extendModal.value.loading = true;
  try {
    await api.post(`/admin/workspaces/${extendModal.value.workspace.id}/extend-trial`, {
      trial_duration_days: extendModal.value.days,
    });
    extendModal.value.open = false;
    fetchWorkspaces();
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to extend trial.';
  } finally {
    extendModal.value.loading = false;
  }
};

// Suspend
const suspendModal = ref({ open: false, workspace: null, reason: '', loading: false });
const openSuspendModal = (ws) => {
  suspendModal.value = { open: true, workspace: ws, reason: '', loading: false };
};
const confirmSuspend = async () => {
  suspendModal.value.loading = true;
  try {
    await api.post(`/admin/workspaces/${suspendModal.value.workspace.id}/suspend`, {
      reason: suspendModal.value.reason || undefined,
    });
    suspendModal.value.open = false;
    fetchWorkspaces();
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to suspend workspace.';
  } finally {
    suspendModal.value.loading = false;
  }
};

// Reactivate
const reactivate = async (ws) => {
  try {
    await api.post(`/admin/workspaces/${ws.id}/reactivate`);
    fetchWorkspaces();
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to reactivate workspace.';
  }
};

onMounted(fetchWorkspaces);
</script>
