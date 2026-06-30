<!-- resources/js/pages/workspace/WorkspaceMembers.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- Erreur d'accès -->
      <div
        v-if="accessDenied"
        class="rounded-3 border border-red-200 bg-red-50 px-6 py-4 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
      >
        <p class="font-semibold">{{ $t('workspace_members.access_denied_title') }}</p>
        <p class="mt-1 text-sm">{{ $t('workspace_members.access_denied_desc') }}</p>
      </div>

      <template v-else>
        <!-- En-tête -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
              {{ $t('workspace_members.title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ $t('workspace_members.subtitle') }}
            </p>
          </div>
          <button
            @click="showInviteModal = true"
            class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          >
            <UserPlusIcon class="h-4 w-4" />
            {{ $t('workspace_members.invite_btn') }}
          </button>
        </div>

        <!-- Erreur API -->
        <div
          v-if="error"
          class="rounded-3 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ error }}
        </div>

        <!-- Feedback succès -->
        <transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-300" leave-to-class="opacity-0">
          <div
            v-if="successMessage"
            class="rounded-3 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
          >
            {{ successMessage }}
          </div>
        </transition>

        <!-- Onglets -->
        <div class="border-b border-gray-200 dark:border-gray-700">
          <nav class="-mb-px flex gap-6">
            <button
              @click="activeTab = 'members'"
              :class="activeTab === 'members'
                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
              class="inline-flex items-center gap-2 border-b-2 pb-3 text-sm font-medium transition-colors"
            >
              <UsersIcon class="h-4 w-4" />
              {{ $t('workspace_members.tab_members') }}
              <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                {{ members.length }}
              </span>
            </button>
            <button
              @click="switchToInvitations"
              :class="activeTab === 'invitations'
                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
              class="inline-flex items-center gap-2 border-b-2 pb-3 text-sm font-medium transition-colors"
            >
              <EnvelopeIcon class="h-4 w-4" />
              {{ $t('workspace_members.tab_invitations') }}
              <span
                v-if="invitations.length > 0"
                class="ml-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"
              >
                {{ invitations.length }}
              </span>
            </button>
          </nav>
        </div>

        <!-- ======================== ONGLET MEMBRES ======================== -->
        <template v-if="activeTab === 'members'">
          <!-- Recherche + filtres -->
          <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input
                v-model="search"
                @input="debouncedFetch"
                type="text"
                :placeholder="$t('workspace_members.search_placeholder')"
                class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
              />
            </div>
            <select
              v-model="filterBanned"
              @change="fetchMembers"
              class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
            >
              <option value="">{{ $t('workspace_members.filter_all') }}</option>
              <option value="active">{{ $t('workspace_members.filter_active') }}</option>
              <option value="banned">{{ $t('workspace_members.filter_banned') }}</option>
            </select>
          </div>

          <!-- Tableau membres -->
          <div class="overflow-hidden rounded-3 border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div v-if="loading" class="flex justify-center py-12">
              <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="filteredMembers.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.col_member') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.col_role') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.col_joined') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_users.col_last_login') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.col_status') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.col_actions') }}
                    </th>
                  </tr>
                </thead>
                <tbody ref="staggerRef" class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr
                    v-for="member in filteredMembers"
                    :key="member.id"
                    class="stagger-item transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
                    :class="{ 'opacity-60': member.is_banned }"
                  >
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <img
                          v-if="member.avatar"
                          :src="member.avatar"
                          :alt="member.nom_complet"
                          class="h-9 w-9 rounded-full object-cover"
                        />
                        <div
                          v-else
                          class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                        >
                          {{ initials(member) }}
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-white">{{ member.nom_complet }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">{{ member.email }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ member.workspace_role }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                      {{ formatDate(member.joined_at) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                      {{ member.last_login_at ? formatDate(member.last_login_at) : $t('workspace_users.never_logged_in') }}
                    </td>
                    <td class="px-6 py-4">
                      <span
                        v-if="member.is_banned"
                        class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400"
                      >
                        <NoSymbolIcon class="h-3 w-3" />
                        {{ $t('workspace_members.status_banned') }}
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400"
                      >
                        <CheckCircleIcon class="h-3 w-3" />
                        {{ $t('workspace_members.status_active') }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          v-if="member.workspace_role !== 'owner'"
                          @click="openRoleModal(member)"
                          class="inline-flex items-center gap-1 rounded-3 border border-blue-300 bg-white px-3 py-1.5 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-50 dark:border-blue-700 dark:bg-transparent dark:text-blue-400 dark:hover:bg-blue-900/20"
                        >
                          <PencilIcon class="h-3.5 w-3.5" />
                          {{ $t('workspace_users.btn_change_role') }}
                        </button>
                        <button
                          @click="openActivityModal(member)"
                          class="inline-flex items-center gap-1 rounded-3 border border-purple-300 bg-white px-3 py-1.5 text-xs font-medium text-purple-600 transition-colors hover:bg-purple-50 dark:border-purple-700 dark:bg-transparent dark:text-purple-400 dark:hover:bg-purple-900/20"
                        >
                          <ClockIcon class="h-3.5 w-3.5" />
                          {{ $t('admin.users.btn_activity') }}
                        </button>
                        <button
                          @click="openProfileModal(member)"
                          class="inline-flex items-center gap-1 rounded-3 border border-teal-300 bg-white px-3 py-1.5 text-xs font-medium text-teal-600 transition-colors hover:bg-teal-50 dark:border-teal-700 dark:bg-transparent dark:text-teal-400 dark:hover:bg-teal-900/20"
                        >
                          <UserCircleIcon class="h-3.5 w-3.5" />
                          {{ $t('admin.users.btn_view_profile') }}
                        </button>
                        <button
                          v-if="!member.is_banned && member.workspace_role !== 'owner'"
                          @click="openBanModal(member)"
                          class="inline-flex items-center gap-1 rounded-3 border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 dark:border-red-700 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-900/20"
                        >
                          <NoSymbolIcon class="h-3.5 w-3.5" />
                          {{ $t('workspace_members.action_ban') }}
                        </button>
                        <button
                          v-if="member.is_banned"
                          @click="unban(member)"
                          :disabled="processingId === member.id"
                          class="inline-flex items-center gap-1 rounded-3 border border-green-300 bg-white px-3 py-1.5 text-xs font-medium text-green-600 transition-colors hover:bg-green-50 disabled:opacity-50 dark:border-green-700 dark:bg-transparent dark:text-green-400 dark:hover:bg-green-900/20"
                        >
                          <CheckCircleIcon class="h-3.5 w-3.5" />
                          {{ $t('workspace_members.action_unban') }}
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="py-16 text-center">
              <UsersIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
              <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ $t('workspace_members.empty_title') }}</p>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('workspace_members.empty_desc') }}</p>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="lastPage > 1" class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
            <span>{{ $t('workspace_members.page_info', { current: currentPage, total: lastPage }) }}</span>
            <div class="flex gap-2">
              <button
                :disabled="currentPage <= 1"
                @click="changePage(currentPage - 1)"
                class="rounded-3 border border-gray-300 px-3 py-1.5 text-xs disabled:opacity-40 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
              >
                {{ $t('common.previous') }}
              </button>
              <button
                :disabled="currentPage >= lastPage"
                @click="changePage(currentPage + 1)"
                class="rounded-3 border border-gray-300 px-3 py-1.5 text-xs disabled:opacity-40 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
              >
                {{ $t('common.next') }}
              </button>
            </div>
          </div>
        </template>

        <!-- ======================== ONGLET INVITATIONS ======================== -->
        <template v-else>
          <div class="overflow-hidden rounded-3 border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div v-if="invitationsLoading" class="flex justify-center py-12">
              <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="invitations.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.inv_col_email') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.inv_col_role') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.inv_col_invited_by') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.inv_col_expires') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                      {{ $t('workspace_members.col_actions') }}
                    </th>
                  </tr>
                </thead>
                <tbody ref="invStaggerRef" class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr
                    v-for="inv in invitations"
                    :key="inv.id"
                    class="stagger-item transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
                  >
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-2">
                        <EnvelopeIcon class="h-4 w-4 shrink-0 text-gray-400" />
                        <span class="text-sm text-gray-900 dark:text-white">{{ inv.email }}</span>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ inv.role }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                      {{ inv.invited_by?.nom_complet ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                      {{ formatDate(inv.expires_at) }}
                    </td>
                    <td class="px-6 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          @click="resendInvitation(inv)"
                          :disabled="invProcessingId === inv.id"
                          class="inline-flex items-center gap-1 rounded-3 border border-blue-300 bg-white px-3 py-1.5 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-50 disabled:opacity-50 dark:border-blue-700 dark:bg-transparent dark:text-blue-400 dark:hover:bg-blue-900/20"
                        >
                          <PaperAirplaneIcon class="h-3.5 w-3.5" />
                          {{ $t('workspace_members.inv_action_resend') }}
                        </button>
                        <button
                          @click="cancelInvitation(inv)"
                          :disabled="invProcessingId === inv.id"
                          class="inline-flex items-center gap-1 rounded-3 border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 disabled:opacity-50 dark:border-red-700 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-900/20"
                        >
                          <XMarkIcon class="h-3.5 w-3.5" />
                          {{ $t('workspace_members.inv_action_cancel') }}
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="py-16 text-center">
              <EnvelopeIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
              <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ $t('workspace_members.inv_empty_title') }}</p>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('workspace_members.inv_empty_desc') }}</p>
            </div>
          </div>
        </template>
      </template>
    </div>

    <!-- Modal ban -->
    <div
      v-if="banModal.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="banModal.open = false"
    >
      <div class="w-full max-w-md rounded-3 bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">
            {{ $t('workspace_members.ban_modal_title', { name: banModal.member?.nom_complet }) }}
          </h3>
          <button @click="banModal.open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
        <div class="space-y-4 px-6 py-4">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $t('workspace_members.ban_modal_desc', { name: banModal.member?.nom_complet }) }}
          </p>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('workspace_members.ban_reason_label') }}
            </label>
            <textarea
              v-model="banModal.reason"
              rows="3"
              :placeholder="$t('workspace_members.ban_reason_placeholder')"
              class="w-full rounded-3 border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>
        </div>
        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
          <button
            @click="banModal.open = false"
            class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            @click="confirmBan"
            :disabled="processingId === banModal.member?.id"
            class="inline-flex items-center gap-2 rounded-3 bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
          >
            <span v-if="processingId === banModal.member?.id" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></span>
            {{ $t('workspace_members.ban_confirm_btn') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal changement de rôle -->
    <div
      v-if="roleModal.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="roleModal.open = false"
    >
      <div class="w-full max-w-md rounded-3 bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">
            {{ $t('workspace_users.change_role_title') }}
          </h3>
          <button @click="roleModal.open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
        <div class="space-y-4 px-6 py-4">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $t('workspace_users.change_role_desc', { name: roleModal.member?.nom_complet }) }}
          </p>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('workspace_members.col_role') }}
            </label>
            <select
              v-model="roleModal.role"
              class="w-full rounded-3 border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option v-for="r in workspaceRoles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
          </div>
          <div v-if="roleModal.error" class="text-sm text-red-600 dark:text-red-400">{{ roleModal.error }}</div>
        </div>
        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
          <button
            @click="roleModal.open = false"
            class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            @click="saveRole"
            :disabled="roleModal.saving"
            class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          >
            <span v-if="roleModal.saving" class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></span>
            {{ $t('common.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal activité -->
    <div
      v-if="activityModal.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="activityModal.open = false"
    >
      <div class="flex max-h-[90vh] w-full max-w-5xl flex-col rounded-3 bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $t('admin.users.activity_modal_title', { name: activityModal.member?.nom_complet }) }}
          </h3>
          <button @click="activityModal.open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
        <div class="flex-1 overflow-y-auto">
          <ActivityLogTab
            v-if="activityModal.open"
            :fixed-causer-id="activityModal.member?.id"
            :fixed-causer-label="activityModal.member?.nom_complet"
            :log-endpoint="`/workspaces/${workspaceId}/member-activity-log`"
            causer-search-endpoint=""
          />
        </div>
      </div>
    </div>

    <!-- Modal profil -->
    <UserProfileModal
      v-if="profileModal.open"
      :user-id="profileModal.userId"
      @close="profileModal.open = false"
    />

    <!-- Modal invitation -->
    <InviteMemberModal
      v-if="showInviteModal"
      :workspace-id="workspaceId"
      @close="showInviteModal = false"
      @invited="onInvited"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import InviteMemberModal from '@/components/workspaces/InviteMemberModal.vue'
import ActivityLogTab from '@/components/admin/logs/ActivityLogTab.vue'
import UserProfileModal from '@/components/admin/UserProfileModal.vue'
import {
  UserPlusIcon,
  MagnifyingGlassIcon,
  NoSymbolIcon,
  CheckCircleIcon,
  UsersIcon,
  XMarkIcon,
  EnvelopeIcon,
  PaperAirplaneIcon,
  PencilIcon,
  ClockIcon,
  UserCircleIcon,
} from '@heroicons/vue/24/outline'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/auth'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'
import { useWorkspace } from '@/composables/useWorkspace'
import { useStagger } from '@/composables/useAnimations'

const { t } = useI18n()
const authStore = useAuthStore()
const { currentWorkspace } = useWorkspace()
const { isDirecteur } = useWorkspacePermissions(currentWorkspace)
const { staggerRef, applyStagger } = useStagger(50)
const { staggerRef: invStaggerRef, applyStagger: applyInvStagger } = useStagger(50)

const workspaceId = computed(() => authStore.currentWorkspaceId)
const accessDenied = computed(() => currentWorkspace.value && !isDirecteur.value)

const activeTab = ref('members')

// --- Membres ---
const members = ref([])
const loading = ref(false)
const error = ref('')
const successMessage = ref('')
const search = ref('')
const filterBanned = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const processingId = ref(null)
const showInviteModal = ref(false)

const banModal = ref({ open: false, member: null, reason: '' })
const roleModal = ref({ open: false, member: null, role: '', saving: false, error: null })
const activityModal = ref({ open: false, member: null })
const profileModal = ref({ open: false, userId: null })

const workspaceRoles = [
  { value: 'manager', label: t('workspace_users.role_manager') },
  { value: 'cadre', label: t('workspace_users.role_cadre') },
  { value: 'collaborateur', label: t('workspace_users.role_collaborateur') },
  { value: 'stagiaire', label: t('workspace_users.role_stagiaire') },
  { value: 'observateur', label: t('workspace_users.role_observateur') },
]

const filteredMembers = computed(() => {
  if (!filterBanned.value) return members.value
  if (filterBanned.value === 'banned') return members.value.filter((m) => m.is_banned)
  return members.value.filter((m) => !m.is_banned)
})

const fetchMembers = async () => {
  if (!workspaceId.value) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/workspaces/${workspaceId.value}/users`, {
      params: { search: search.value || undefined, per_page: 20, page: currentPage.value },
    })
    members.value = data.data
    lastPage.value = data.last_page ?? 1
    await applyStagger()
  } catch (e) {
    const msg = e.response?.data?.message
    error.value = msg || t('workspace_members.load_error')
  } finally {
    loading.value = false
  }
}

let debounceTimer = null
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchMembers, 300)
}

const changePage = (page) => {
  currentPage.value = page
  fetchMembers()
}

const openBanModal = (member) => {
  banModal.value = { open: true, member, reason: '' }
}

const confirmBan = async () => {
  const member = banModal.value.member
  if (!member) return
  processingId.value = member.id
  error.value = ''
  try {
    await api.post(`/workspaces/${workspaceId.value}/members/${member.id}/ban`, {
      reason: banModal.value.reason || null,
    })
    banModal.value.open = false
    showSuccess(t('workspace_members.ban_success', { name: member.nom_complet }))
    await fetchMembers()
  } catch (e) {
    const msg = e.response?.data?.message
    error.value = msg || t('workspace_members.ban_error')
    banModal.value.open = false
  } finally {
    processingId.value = null
  }
}

const unban = async (member) => {
  processingId.value = member.id
  error.value = ''
  try {
    await api.delete(`/workspaces/${workspaceId.value}/members/${member.id}/ban`)
    showSuccess(t('workspace_members.unban_success', { name: member.nom_complet }))
    await fetchMembers()
  } catch (e) {
    const msg = e.response?.data?.message
    error.value = msg || t('workspace_members.unban_error')
  } finally {
    processingId.value = null
  }
}

const openRoleModal = (member) => {
  roleModal.value = { open: true, member, role: member.workspace_role ?? 'collaborateur', saving: false, error: null }
}

const saveRole = async () => {
  if (!workspaceId.value || !roleModal.value.member) return
  roleModal.value.saving = true
  roleModal.value.error = null
  try {
    await api.put(`/workspaces/${workspaceId.value}/members/${roleModal.value.member.id}`, { role: roleModal.value.role })
    const idx = members.value.findIndex((m) => m.id === roleModal.value.member.id)
    if (idx !== -1) {
      members.value[idx] = { ...members.value[idx], workspace_role: roleModal.value.role }
    }
    roleModal.value.open = false
    showSuccess(t('workspace_members.role_success', { name: roleModal.value.member.nom_complet }))
  } catch (e) {
    roleModal.value.error = e.response?.data?.message ?? t('workspace_users.role_save_error')
  } finally {
    roleModal.value.saving = false
  }
}

const openActivityModal = (member) => {
  activityModal.value = { open: true, member }
}

const openProfileModal = (member) => {
  profileModal.value = { open: true, userId: member.id }
}

// --- Invitations ---
const invitations = ref([])
const invitationsLoading = ref(false)
const invProcessingId = ref(null)

const fetchInvitations = async () => {
  if (!workspaceId.value) return
  invitationsLoading.value = true
  try {
    const { data } = await api.get(`/workspaces/${workspaceId.value}/members/invitations`)
    invitations.value = data.data ?? []
    await applyInvStagger()
  } catch {
    invitations.value = []
  } finally {
    invitationsLoading.value = false
  }
}

const switchToInvitations = () => {
  activeTab.value = 'invitations'
  fetchInvitations()
}

const resendInvitation = async (inv) => {
  invProcessingId.value = inv.id
  try {
    await api.post(`/workspaces/${workspaceId.value}/members/invitations/${inv.id}/resend`)
    showSuccess(t('workspace_members.inv_resend_success', { email: inv.email }))
  } catch (e) {
    error.value = e.response?.data?.message || t('workspace_members.inv_resend_error')
  } finally {
    invProcessingId.value = null
  }
}

const cancelInvitation = async (inv) => {
  invProcessingId.value = inv.id
  try {
    await api.delete(`/workspaces/${workspaceId.value}/members/invitations/${inv.id}`)
    invitations.value = invitations.value.filter((i) => i.id !== inv.id)
    showSuccess(t('workspace_members.inv_cancel_success', { email: inv.email }))
  } catch (e) {
    error.value = e.response?.data?.message || t('workspace_members.inv_cancel_error')
  } finally {
    invProcessingId.value = null
  }
}

const onInvited = () => {
  showInviteModal.value = false
  fetchMembers()
  fetchInvitations()
}

// --- Utilitaires ---
const showSuccess = (msg) => {
  successMessage.value = msg
  setTimeout(() => { successMessage.value = '' }, 4000)
}

const initials = (member) => {
  const first = member.prenom?.[0] ?? ''
  const last = member.nom?.[0] ?? ''
  return (first + last).toUpperCase() || '?'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(fetchMembers)
</script>
