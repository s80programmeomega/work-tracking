<!-- resources/js/pages/admin/AdminUsers.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 dusk="admin-users-title" class="text-2xl font-semibold text-gray-900 dark:text-white">
            User Management
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            All platform users, their workspaces and last login.
          </p>
        </div>
        <router-link
          to="/admin/dashboard"
          class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
        >
          <i class="fas fa-arrow-left"></i> Back to dashboard
        </router-link>
      </div>

      <!-- Search -->
      <div>
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          placeholder="Search by name or email..."
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 w-72"
        />
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
              <th class="px-4 py-3 text-left">Name</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Role</th>
              <th class="px-4 py-3 text-left">Current Workspace</th>
              <th class="px-4 py-3 text-left">Last Login</th>
              <th class="px-4 py-3 text-left">Registered</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="!users.length">
              <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No users found.</td>
            </tr>
            <tr
              v-for="user in users"
              :key="user.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
            >
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ user.nom }}</td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ user.email }}</td>
              <td class="px-4 py-3">
                <span v-if="user.is_super_admin"
                  class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
                >
                  Super Admin
                </span>
                <span v-else class="text-gray-500 text-xs">User</span>
              </td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                {{ user.current_workspace?.nom ?? '—' }}
              </td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                {{ user.last_login_at ? formatDate(user.last_login_at) : 'Never' }}
              </td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(user.created_at) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
          <p class="text-sm text-gray-500">Page {{ pagination.current_page }} / {{ pagination.last_page }}</p>
          <div class="flex gap-2">
            <button
              @click="page--; fetchUsers()"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded disabled:opacity-40"
            >Prev</button>
            <button
              @click="page++; fetchUsers()"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded disabled:opacity-40"
            >Next</button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import api from '@/api/axios';

const users = ref([]);
const pagination = ref(null);
const loading = ref(false);
const error = ref(null);
const search = ref('');
const page = ref(1);

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString() : '—';

let debounceTimer = null;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { page.value = 1; fetchUsers(); }, 350);
};

const fetchUsers = async () => {
  loading.value = true;
  error.value = null;
  try {
    const params = { page: page.value, per_page: 20, search: search.value || undefined };
    const { data } = await api.get('/admin/users', { params });
    users.value = data.data;
    pagination.value = { current_page: data.current_page, last_page: data.last_page };
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to load users.';
  } finally {
    loading.value = false;
  }
};

onMounted(fetchUsers);
</script>
