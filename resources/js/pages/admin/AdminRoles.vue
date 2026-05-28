<!-- resources/js/pages/admin/AdminRoles.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 dusk="admin-roles-title" class="text-2xl font-semibold text-gray-900 dark:text-white">
            Roles & Permissions
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Manage what each role can do across the platform.
          </p>
        </div>
        <router-link
          to="/admin/dashboard"
          class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
        >
          <i class="fas fa-arrow-left"></i> Back to dashboard
        </router-link>
      </div>

      <!-- Error / success -->
      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-3">
        {{ error }}
      </div>
      <div v-if="successMsg" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-3">
        {{ successMsg }}
      </div>

      <!-- Loading -->
      <div v-if="loading && !rolesData" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
      </div>

      <div v-if="rolesData" class="flex gap-6 items-start">
        <!-- Left: role list -->
        <div class="w-60 shrink-0 space-y-1">
          <!-- Filter tabs -->
          <div class="flex gap-1 mb-3 p-1 bg-gray-100 dark:bg-gray-800 rounded-3">
            <button
              v-for="tab in filterTabs"
              :key="tab.value"
              @click="activeFilter = tab.value"
              :class="[
                'flex-1 text-xs font-medium py-1 px-2 rounded-2 transition-colors',
                activeFilter === tab.value
                  ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
              ]"
            >
              {{ tab.label }}
              <span class="ml-1 text-gray-400 dark:text-gray-500">{{ tab.count }}</span>
            </button>
          </div>

          <button
            v-for="role in filteredRoles"
            :key="role.name"
            @click="selectRole(role)"
            :class="[
              'w-full text-left px-3 py-2 rounded-3 text-sm transition-colors',
              selectedRole?.name === role.name
                ? 'bg-brand-600 text-white'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
            ]"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="truncate font-medium">{{ formatRoleName(role.name) }}</span>
              <span
                :class="[
                  'text-xs tabular-nums shrink-0 font-semibold',
                  selectedRole?.name === role.name ? 'text-white/60' : 'text-gray-400 dark:text-gray-500'
                ]"
              >
                #{{ role.priority }}
              </span>
            </div>
            <div class="mt-0.5">
              <span
                :class="[
                  'text-xs px-1.5 py-0.5 rounded-full font-medium',
                  role.is_global
                    ? (selectedRole?.name === role.name ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400')
                    : (selectedRole?.name === role.name ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400')
                ]"
              >
                {{ role.is_global ? 'global' : 'contextual' }}
              </span>
            </div>
          </button>
        </div>

        <!-- Right: permission matrix -->
        <div class="flex-1 min-w-0">
          <div v-if="!selectedRole" class="flex items-center justify-center h-48 text-gray-400 dark:text-gray-500">
            <div class="text-center">
              <i class="fas fa-shield-alt text-4xl mb-3 block"></i>
              <p>Select a role to manage its permissions</p>
            </div>
          </div>

          <template v-else>
            <!-- Role header -->
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ formatRoleName(selectedRole.name) }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ draftPermissions.size }} permission(s) assigned
                  <span v-if="isDirty" class="ml-2 text-amber-600 dark:text-amber-400 font-medium">• Unsaved changes</span>
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button
                  v-if="isDirty"
                  @click="resetDraft"
                  class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-3"
                >
                  Reset
                </button>
                <button
                  @click="savePermissions"
                  :disabled="saving || !isDirty"
                  class="px-4 py-1.5 bg-brand-600 text-white text-sm rounded-3 hover:bg-brand-700 disabled:opacity-50 flex items-center gap-2"
                >
                  <i v-if="saving" class="fas fa-spinner animate-spin"></i>
                  <i v-else class="fas fa-save"></i>
                  Save
                </button>
              </div>
            </div>

            <!-- Permission groups -->
            <div class="space-y-4">
              <div
                v-for="group in rolesData.permissions_grouped"
                :key="group.module"
                class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden"
              >
                <!-- Group header with select-all toggle -->
                <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                  <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    {{ group.module }}
                  </span>
                  <button
                    @click="toggleGroup(group)"
                    class="text-xs text-brand-600 dark:text-brand-400 hover:underline"
                  >
                    {{ isGroupAllChecked(group) ? 'Deselect all' : 'Select all' }}
                  </button>
                </div>
                <!-- Permission checkboxes -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y divide-gray-50 dark:divide-gray-700/50">
                  <label
                    v-for="perm in group.permissions"
                    :key="perm.name"
                    class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                  >
                    <input
                      type="checkbox"
                      :checked="draftPermissions.has(perm.name)"
                      @change="togglePermission(perm.name)"
                      class="w-4 h-4 text-brand-600 rounded border-gray-300 dark:border-gray-600 focus:ring-brand-500"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-mono">{{ perm.name.split('.')[1] }}</span>
                  </label>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import api from '@/api/axios';

const rolesData = ref(null);
const selectedRole = ref(null);
const activeFilter = ref('all');
const draftPermissions = ref(new Set());
const originalPermissions = ref(new Set());
const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const successMsg = ref(null);

const filterTabs = computed(() => {
  const roles = rolesData.value?.roles ?? [];
  return [
    { value: 'all', label: 'All', count: roles.length },
    { value: 'global', label: 'Global', count: roles.filter((r) => r.is_global).length },
    { value: 'contextual', label: 'Contextual', count: roles.filter((r) => !r.is_global).length },
  ];
});

const filteredRoles = computed(() => {
  const roles = rolesData.value?.roles ?? [];
  if (activeFilter.value === 'global') return roles.filter((r) => r.is_global);
  if (activeFilter.value === 'contextual') return roles.filter((r) => !r.is_global);
  return roles;
});

const isDirty = computed(() => {
  if (draftPermissions.value.size !== originalPermissions.value.size) return true;
  for (const p of draftPermissions.value) {
    if (!originalPermissions.value.has(p)) return true;
  }
  return false;
});

const formatRoleName = (name) =>
  name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const selectRole = (role) => {
  selectedRole.value = role;
  draftPermissions.value = new Set(role.permissions);
  originalPermissions.value = new Set(role.permissions);
};

const togglePermission = (name) => {
  const set = new Set(draftPermissions.value);
  if (set.has(name)) {
    set.delete(name);
  } else {
    set.add(name);
  }
  draftPermissions.value = set;
};

const isGroupAllChecked = (group) =>
  group.permissions.every((p) => draftPermissions.value.has(p.name));

const toggleGroup = (group) => {
  const set = new Set(draftPermissions.value);
  if (isGroupAllChecked(group)) {
    group.permissions.forEach((p) => set.delete(p.name));
  } else {
    group.permissions.forEach((p) => set.add(p.name));
  }
  draftPermissions.value = set;
};

const resetDraft = () => {
  draftPermissions.value = new Set(originalPermissions.value);
};

const savePermissions = async () => {
  if (!selectedRole.value) return;
  saving.value = true;
  error.value = null;
  successMsg.value = null;
  try {
    const { data } = await api.patch(`/admin/roles/${selectedRole.value.id}/permissions`, {
      permissions: [...draftPermissions.value],
    });
    // Refresh the original permissions to reflect saved state
    const savedPermissions = data.data.permissions;
    const updatedRole = { ...selectedRole.value, permissions: savedPermissions };
    selectedRole.value = updatedRole;
    originalPermissions.value = new Set(savedPermissions);
    draftPermissions.value = new Set(savedPermissions);

    // Update in the main list too
    const idx = rolesData.value.roles.findIndex((r) => r.name === selectedRole.value.name);
    if (idx !== -1) {
      rolesData.value.roles[idx] = updatedRole;
    }

    successMsg.value = `Permissions for "${formatRoleName(selectedRole.value.name)}" saved.`;
    setTimeout(() => { successMsg.value = null; }, 3000);
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save permissions.';
  } finally {
    saving.value = false;
  }
};

const fetchRoles = async () => {
  loading.value = true;
  error.value = null;
  try {
    const { data } = await api.get('/admin/roles');
    rolesData.value = data.data;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to load roles.';
  } finally {
    loading.value = false;
  }
};

fetchRoles();
</script>
