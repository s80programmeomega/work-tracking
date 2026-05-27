<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="team?.name || 'Équipe'" />

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500"></div>
    </div>

    <div v-else-if="team" class="space-y-5">
      <!-- Team Header Card -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <button @click="$router.back()"
              class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors">
              <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </button>

            <!-- Team Avatar -->
            <div v-if="team.avatar"
              class="w-16 h-16 rounded-3 overflow-hidden ring-4 ring-gray-100 dark:ring-gray-700">
              <img :src="team.avatar" class="w-full h-full object-cover" alt="Team avatar" />
            </div>
            <div v-else
              class="w-16 h-16 rounded-3 flex items-center justify-center text-white text-2xl font-bold ring-4 ring-gray-100 dark:ring-gray-700">
              {{ getInitials(team.name) }}
            </div>

            <!-- Team Info -->
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white" dusk="team-detail-name">{{ team.name }}</h1>
              <p v-if="team.description" class="text-gray-600 dark:text-gray-400 mt-1">{{ team.description }}</p>
              <div class="flex items-center gap-3 mt-2">
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full" :class="{
                  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': team.visibility === 'public',
                  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': team.visibility === 'private',
                  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': team.visibility === 'secret'
                }">
                  {{ getVisibilityLabel(team.visibility) }}
                </span>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                  <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  {{ team.members_count || 0 }} membre{{ (team.members_count || 0) > 1 ? 's' : '' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-3">
            <button @click="showEditModal = true"
              class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Modifier
            </button>
            <button @click="deleteTeamConfirm"
              class="px-4 py-2 border border-red-300 dark:border-red-700 rounded-3 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Supprimer
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 dark:border-gray-800">
          <nav class="flex gap-1 p-2">
            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
              class="flex items-center gap-2 px-4 py-2.5 rounded-3 font-medium transition-all" :class="activeTab === tab.id
                ? 'bg-brand-500 text-white '
                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
              <component :is="tab.icon" class="w-5 h-5" />
              {{ tab.label }}
              <span v-if="tab.badge" class="px-2 py-0.5 text-xs rounded-full"
                :class="activeTab === tab.id ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-700'">
                {{ tab.badge }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
          <!-- Chat Tab -->
          <div v-if="activeTab === 'chat'" class="space-y-4">
            <div
              class="rounded-3 p-6 h-[600px] flex flex-col border border-gray-200 dark:border-gray-700">
              <!-- Messages Container -->
              <div ref="chatContainer" class="flex-1 overflow-y-auto space-y-4 pr-2 custom-scrollbar">
                <div v-for="message in messages" :key="message.id" class="flex gap-3">
                  <!-- Avatar -->
                  <div class="flex-shrink-0">
                    <div
                      class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm">
                      {{ getUserInitials(message.user) }}
                    </div>
                  </div>

                  <!-- Message Content -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2 mb-1">
                      <span class="font-semibold text-gray-900 dark:text-white">{{ message.user?.nom || 'Utilisateur'
                        }}</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(message.created_at) }}</span>
                    </div>
                    <div
                      class="bg-white dark:bg-gray-800 rounded-3 px-4 py-2.5 border border-gray-200 dark:border-gray-700">
                      <p class="text-gray-700 dark:text-gray-300">{{ message.content }}</p>
                    </div>

                    <!-- Reactions -->
                    <div v-if="message.reactions && message.reactions.length > 0" class="flex gap-2 mt-2">
                      <button v-for="reaction in getGroupedReactions(message.reactions)" :key="reaction.emoji"
                        class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <span>{{ reaction.emoji }}</span>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ reaction.count }}</span>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Empty State -->
                <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                  <div
                    class="w-16 h-16 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                  </div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Aucun message</h3>
                  <p class="text-gray-500 dark:text-gray-400">Soyez le premier à envoyer un message!</p>
                </div>
              </div>

              <!-- Message Input -->
              <form @submit.prevent="sendMessage" class="mt-4 flex gap-2">
                <input v-model="newMessage" type="text" placeholder="Tapez votre message..."
                  class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
                <button type="submit" :disabled="!newMessage.trim()"
                  class="px-6 py-3 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                  </svg>
                  Envoyer
                </button>
              </form>
            </div>
          </div>

          <!-- Members Tab -->
          <div v-else-if="activeTab === 'members'" class="space-y-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">Membres de l'équipe</h3>
              <button @click="showAddMemberModal = true"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Ajouter un membre
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="member in team.members" :key="member.id"
                class="rounded-3 p-5 border border-gray-200 dark:border-gray-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all">
                <div class="flex items-center gap-4">
                  <div class="relative">
                    <div
                      class="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-lg">
                      {{ getUserInitials(member) }}
                    </div>
                    <!-- Presence indicator -->
                    <div v-if="presences.find(p => p.user_id === member.id)"
                      class="absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-white dark:border-gray-800"
                      :class="{
                        'bg-green-500': isOnline(presences.find(p => p.user_id === member.id)?.last_seen),
                        'bg-gray-400': !isOnline(presences.find(p => p.user_id === member.id)?.last_seen)
                      }"
                      :title="isOnline(presences.find(p => p.user_id === member.id)?.last_seen) ? 'En ligne' : formatLastSeen(presences.find(p => p.user_id === member.id)?.last_seen)">
                    </div>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ member.nom }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ member.email }}</p>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full" :class="{
                        'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': member.pivot?.role === 'owner',
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': member.pivot?.role === 'admin',
                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': member.pivot?.role === 'moderator',
                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': member.pivot?.role === 'member'
                      }">
                        {{ getRoleLabel(member.pivot?.role) }}
                      </span>
                      <!-- Last seen -->
                      <span v-if="presences.find(p => p.user_id === member.id)"
                        class="text-xs text-gray-500 dark:text-gray-400">
                        {{isOnline(presences.find(p => p.user_id === member.id)?.last_seen) ? '🟢 En ligne' :
                          formatLastSeen(presences.find(p => p.user_id === member.id)?.last_seen) }}
                      </span>
                    </div>
                  </div>
                  <button v-if="member.pivot?.role !== 'owner'" @click="removeMemberConfirm(member)"
                    class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Announcements Tab -->
          <div v-else-if="activeTab === 'announcements'" class="space-y-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">Annonces de l'équipe</h3>
              <button @click="showAnnouncementModal = true"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                Nouvelle annonce
              </button>
            </div>

            <!-- Announcements List -->
            <div class="space-y-4">
              <div v-for="announcement in announcements" :key="announcement.id"
                class="rounded-3 p-6 border border-gray-200 dark:border-gray-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all">
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0">
                    <div
                      class="w-12 h-12 rounded-full flex items-center justify-center text-white text-xl">
                      📢
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ announcement.title }}</h4>
                      <span v-if="announcement.priority === 'high'"
                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        Urgent
                      </span>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">{{ announcement.content }}</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                      <span>Par {{ announcement.user?.nom }}</span>
                      <span>•</span>
                      <span>{{ formatDate(announcement.published_at || announcement.created_at) }}</span>
                    </div>
                  </div>
                  <button @click="deleteAnnouncementConfirm(announcement)"
                    class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="announcements.length === 0" class="text-center py-12">
                <div
                  class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 mb-4">
                  <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                  </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Aucune annonce</h3>
                <p class="text-gray-500 dark:text-gray-400">Créez la première annonce pour votre équipe</p>
              </div>
            </div>
          </div>

          <!-- Resources Tab -->
          <div v-else-if="activeTab === 'resources'" class="space-y-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">Ressources partagées</h3>
              <button @click="showResourceModal = true"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Ajouter ressource
              </button>
            </div>

            <!-- Resources Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div v-for="resource in resources" :key="resource.id"
                class="rounded-3 p-5 border border-gray-200 dark:border-gray-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all">
                <div class="flex items-start gap-3 mb-3">
                  <div class="flex-shrink-0">
                    <div
                      class="w-10 h-10 rounded-3 flex items-center justify-center text-white text-lg">
                      {{ getResourceIcon(resource.type) }}
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ resource.title }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ resource.type }}</p>
                  </div>
                </div>
                <p v-if="resource.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{
                  resource.description }}</p>
                <div class="flex items-center justify-between">
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(resource.created_at) }}</span>
                  <button @click="deleteResourceConfirm(resource)"
                    class="p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="resources.length === 0" class="col-span-full text-center py-12">
                <div
                  class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 mb-4">
                  <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                  </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Aucune ressource</h3>
                <p class="text-gray-500 dark:text-gray-400">Partagez des documents, liens ou templates avec votre équipe
                </p>
              </div>
            </div>
          </div>

          <!-- Calendar Tab -->
          <div v-else-if="activeTab === 'calendar'" class="space-y-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">Calendrier de l'équipe</h3>
              <button @click="showEventModal = true"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvel événement
              </button>
            </div>

            <!-- Events List -->
            <div class="space-y-4">
              <div v-for="event in events" :key="event.id"
                class="rounded-3 p-5 border-l-4 transition-all"
                :class="getEventTypeStyle(event.type).borderClass">
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-3 flex items-center justify-center text-2xl"
                      :class="getEventTypeStyle(event.type).bgClass">
                      {{ getEventTypeStyle(event.type).icon }}
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between mb-2">
                      <div class="flex-1">
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ event.title }}</h4>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full"
                          :class="getEventTypeStyle(event.type).bgClass + ' ' + getEventTypeStyle(event.type).textClass">
                          {{ getEventTypeStyle(event.type).label }}
                        </span>
                      </div>
                      <button @click="deleteEventConfirm(event)"
                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                    <p v-if="event.description" class="text-gray-700 dark:text-gray-300 mb-3">{{ event.description }}
                    </p>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                      <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ formatEventDate(event.start_date) }}</span>
                      </div>
                      <div v-if="event.end_date" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ getEventDuration(event.start_date, event.end_date) }}</span>
                      </div>
                      <div v-if="event.location" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ event.location }}</span>
                      </div>
                      <span v-if="isToday(event.start_date)"
                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        Aujourd'hui
                      </span>
                      <span v-if="isPast(event.end_date || event.start_date)"
                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        Passé
                      </span>
                    </div>
                    <div v-if="event.attendees && event.attendees.length > 0" class="mt-3 flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      <div class="flex -space-x-2">
                        <div v-for="(attendee, index) in event.attendees.slice(0, 5)" :key="attendee.id"
                          class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs border-2 border-white dark:border-gray-800"
                          :title="attendee.nom">
                          {{ getUserInitials(attendee) }}
                        </div>
                        <div v-if="event.attendees.length > 5"
                          class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-400 font-semibold text-xs border-2 border-white dark:border-gray-800">
                          +{{ event.attendees.length - 5 }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="events.length === 0" class="text-center py-12">
                <div
                  class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 mb-4">
                  <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Aucun événement</h3>
                <p class="text-gray-500 dark:text-gray-400">Créez le premier événement pour votre équipe</p>
              </div>
            </div>
          </div>

          <!-- Activity Tab -->
          <div v-else-if="activeTab === 'activity'" class="space-y-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Activités récentes</h3>

            <!-- Activity Timeline -->
            <div class="space-y-4">
              <div v-for="activity in activities" :key="activity.id"
                class="flex gap-4 pb-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                <div class="flex-shrink-0">
                  <div
                    class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xl">
                    {{ getActivityIcon(activity.action) }}
                  </div>
                </div>
                <div class="flex-1">
                  <p class="text-gray-900 dark:text-white">
                    <span class="font-semibold">{{ activity.user?.nom || 'Utilisateur' }}</span>
                    <span class="text-gray-600 dark:text-gray-400"> {{ getActivityLabel(activity.action) }}</span>
                  </p>
                  <p v-if="activity.details" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ activity.details
                    }}</p>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(activity.created_at) }}</span>
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="activities.length === 0" class="text-center py-12">
                <div
                  class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Aucune activité</h3>
                <p class="text-gray-500 dark:text-gray-400">L'historique des activités apparaîtra ici</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Team Modal -->
    <div v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 "
      @click.self="showEditModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-2xl transform transition-all">
        <!-- Modal Header -->
        <div
          class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-12 h-12 rounded-3 flex items-center justify-center ">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Modifier l'équipe</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Mettez à jour les informations de l'équipe</p>
              </div>
            </div>
            <button type="button" @click="showEditModal = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="updateTeam" class="px-8 py-6">
          <div class="space-y-5">
            <!-- Team Name -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Nom de l'équipe <span class="text-red-500">*</span>
              </label>
              <input v-model="editForm.name" type="text" required placeholder="Ex: Équipe Marketing, Développement..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea v-model="editForm.description" rows="3" placeholder="Décrivez l'objectif de cette équipe..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>

            <!-- Visibility -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Visibilité
              </label>
              <div class="grid grid-cols-3 gap-3">
                <label v-for="option in visibilityOptions" :key="option.value"
                  class="relative flex flex-col items-center p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="editForm.visibility === option.value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="editForm.visibility" :value="option.value" class="sr-only" />
                  <span class="text-2xl mb-2">{{ option.icon }}</span>
                  <span class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ option.label }}</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400 text-center">{{ option.description }}</span>
                  <svg v-if="editForm.visibility === option.value" class="absolute top-2 right-2 w-5 h-5 text-brand-500"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showEditModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            Annuler
          </button>
          <button type="button" @click="updateTeam" :disabled="updating || !editForm.name"
            class="px-5 py-2.5 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="updating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ updating ? 'Mise à jour...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div v-if="showAddMemberModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 "
      @click.self="showAddMemberModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-lg transform transition-all">
        <!-- Modal Header -->
        <div
          class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-12 h-12 rounded-3 flex items-center justify-center ">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Ajouter un membre</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Invitez un utilisateur à rejoindre l'équipe</p>
              </div>
            </div>
            <button type="button" @click="showAddMemberModal = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="addMember" class="px-8 py-6">
          <div class="space-y-5">
            <!-- User Selection -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Utilisateur <span class="text-red-500">*</span>
              </label>
              <select v-model="newMemberForm.user_id" required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all">
                <option value="">Sélectionnez un utilisateur</option>
                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                  {{ user.nom }} ({{ user.email }})
                </option>
              </select>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Rôle
              </label>
              <div class="grid grid-cols-2 gap-3">
                <label v-for="role in memberRoles" :key="role.value"
                  class="relative flex flex-col items-center p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="newMemberForm.role === role.value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="newMemberForm.role" :value="role.value" class="sr-only" />
                  <span class="text-2xl mb-2">{{ role.icon }}</span>
                  <span class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ role.label }}</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400 text-center">{{ role.description }}</span>
                  <svg v-if="newMemberForm.role === role.value" class="absolute top-2 right-2 w-5 h-5 text-brand-500"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showAddMemberModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            Annuler
          </button>
          <button type="button" @click="addMember" :disabled="addingMember || !newMemberForm.user_id"
            class="px-5 py-2.5 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="addingMember" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ addingMember ? 'Ajout...' : 'Ajouter' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Team Modal -->
    <div v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 "
      @click.self="showEditModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-2xl transform transition-all">
        <!-- Modal Header -->
        <div
          class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-12 h-12 rounded-3 flex items-center justify-center ">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Modifier l'équipe</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Mettez à jour les informations de l'équipe</p>
              </div>
            </div>
            <button type="button" @click="showEditModal = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="updateTeam" class="px-8 py-6">
          <div class="space-y-5">
            <!-- Team Name -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Nom de l'équipe <span class="text-red-500">*</span>
              </label>
              <input v-model="editForm.name" type="text" required placeholder="Ex: Équipe Marketing, Développement..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea v-model="editForm.description" rows="3" placeholder="Décrivez l'objectif de cette équipe..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>

            <!-- Visibility -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Visibilité
              </label>
              <div class="grid grid-cols-3 gap-3">
                <label v-for="option in visibilityOptions" :key="option.value"
                  class="relative flex flex-col items-center p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="editForm.visibility === option.value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="editForm.visibility" :value="option.value" class="sr-only" />
                  <span class="text-2xl mb-2">{{ option.icon }}</span>
                  <span class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ option.label }}</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400 text-center">{{ option.description }}</span>
                  <svg v-if="editForm.visibility === option.value" class="absolute top-2 right-2 w-5 h-5 text-brand-500"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showEditModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            Annuler
          </button>
          <button type="button" @click="updateTeam" :disabled="updating || !editForm.name"
            class="px-5 py-2.5 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="updating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ updating ? 'Mise à jour...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div v-if="showAddMemberModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 "
      @click.self="showAddMemberModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-lg transform transition-all">
        <!-- Modal Header -->
        <div
          class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-12 h-12 rounded-3 flex items-center justify-center ">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Ajouter un membre</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Invitez un utilisateur à rejoindre l'équipe</p>
              </div>
            </div>
            <button type="button" @click="showAddMemberModal = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="addMember" class="px-8 py-6">
          <div class="space-y-5">
            <!-- User Selection -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Utilisateur <span class="text-red-500">*</span>
              </label>
              <select v-model="newMemberForm.user_id" required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all">
                <option value="">Sélectionnez un utilisateur</option>
                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                  {{ user.nom }} ({{ user.email }})
                </option>
              </select>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Rôle
              </label>
              <div class="grid grid-cols-2 gap-3">
                <label v-for="role in memberRoles" :key="role.value"
                  class="relative flex flex-col items-center p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="newMemberForm.role === role.value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="newMemberForm.role" :value="role.value" class="sr-only" />
                  <span class="text-2xl mb-2">{{ role.icon }}</span>
                  <span class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ role.label }}</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400 text-center">{{ role.description }}</span>
                  <svg v-if="newMemberForm.role === role.value" class="absolute top-2 right-2 w-5 h-5 text-brand-500"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showAddMemberModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            Annuler
          </button>
          <button type="button" @click="addMember" :disabled="addingMember || !newMemberForm.user_id"
            class="px-5 py-2.5 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="addingMember" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ addingMember ? 'Ajout...' : 'Ajouter' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Announcement Modal -->
    <div v-if="showAnnouncementModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 "
      @click.self="showAnnouncementModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-2xl transform transition-all">
        <!-- Modal Header -->
        <div
          class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-12 h-12 rounded-3 flex items-center justify-center text-white text-2xl">
                📢
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Nouvelle annonce</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Créez une annonce importante pour votre équipe</p>
              </div>
            </div>
            <button type="button" @click="showAnnouncementModal = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="createNewAnnouncement" class="px-8 py-6">
          <div class="space-y-5">
            <!-- Title -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Titre de l'annonce <span class="text-red-500">*</span>
              </label>
              <input v-model="announcementForm.title" type="text" required
                placeholder="Ex: Nouvelle procédure, Mise à jour importante..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" />
            </div>

            <!-- Content -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Contenu <span class="text-red-500">*</span>
              </label>
              <textarea v-model="announcementForm.content" rows="6" required
                placeholder="Décrivez votre annonce en détail..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>

            <!-- Priority -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Priorité
              </label>
              <div class="grid grid-cols-2 gap-3">
                <label class="relative flex items-center gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="announcementForm.priority === 'normal'
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="announcementForm.priority" value="normal" class="sr-only" />
                  <span class="text-2xl">ℹ️</span>
                  <div class="flex-1">
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">Normale</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Information standard</span>
                  </div>
                  <svg v-if="announcementForm.priority === 'normal'" class="w-5 h-5 text-blue-500" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
                  </svg>
                </label>
                <label class="relative flex items-center gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="announcementForm.priority === 'high'
                    ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="announcementForm.priority" value="high" class="sr-only" />
                  <span class="text-2xl">⚠️</span>
                  <div class="flex-1">
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">Urgente</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Nécessite une attention immédiate</span>
                  </div>
                  <svg v-if="announcementForm.priority === 'high'" class="w-5 h-5 text-red-500" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showAnnouncementModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            Annuler
          </button>
          <button type="button" @click="createNewAnnouncement"
            :disabled="creatingAnnouncement || !announcementForm.title || !announcementForm.content"
            class="px-5 py-2.5 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="creatingAnnouncement" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ creatingAnnouncement ? 'Création...' : 'Créer l\'annonce' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Resource Modal -->
    <div v-if="showResourceModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 "
      @click.self="showResourceModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-2xl transform transition-all">
        <!-- Modal Header -->
        <div
          class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-12 h-12 rounded-3 flex items-center justify-center text-white text-2xl">
                📎
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Ajouter une ressource</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Partagez un document, lien, template ou outil</p>
              </div>
            </div>
            <button type="button" @click="showResourceModal = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="createNewResource" class="px-8 py-6">
          <div class="space-y-5">
            <!-- Type Selection -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Type de ressource
              </label>
              <div class="grid grid-cols-4 gap-3">
                <label v-for="type in resourceTypes" :key="type.value"
                  class="relative flex flex-col items-center p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="resourceForm.type === type.value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="resourceForm.type" :value="type.value" class="sr-only" />
                  <span class="text-2xl mb-2">{{ type.icon }}</span>
                  <span class="text-xs font-semibold text-gray-900 dark:text-white text-center">{{ type.label }}</span>
                  <svg v-if="resourceForm.type === type.value" class="absolute top-2 right-2 w-5 h-5 text-brand-500"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>

            <!-- Title -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Titre <span class="text-red-500">*</span>
              </label>
              <input v-model="resourceForm.title" type="text" required
                placeholder="Ex: Guide utilisateur, Lien API documentation..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- URL -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                URL ou chemin <span class="text-red-500">*</span>
              </label>
              <input v-model="resourceForm.url" type="text" required
                placeholder="https://example.com/document.pdf ou /chemin/vers/fichier"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea v-model="resourceForm.description" rows="3" placeholder="Décrivez brièvement cette ressource..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showResourceModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            Annuler
          </button>
          <button type="button" @click="createNewResource"
            :disabled="creatingResource || !resourceForm.title || !resourceForm.url"
            class="px-5 py-2.5 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="creatingResource" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ creatingResource ? 'Ajout...' : 'Ajouter la ressource' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Event Modal -->
    <div v-if="showEventModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 "
      @click.self="showEventModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-2xl transform transition-all">
        <!-- Modal Header -->
        <div
          class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-12 h-12 rounded-3 flex items-center justify-center text-white text-2xl">
                📅
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Nouvel événement</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Créez un événement pour le calendrier d'équipe</p>
              </div>
            </div>
            <button type="button" @click="showEventModal = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="createNewEvent" class="px-8 py-6">
          <div class="space-y-5">
            <!-- Event Type -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Type d'événement
              </label>
              <div class="grid grid-cols-3 gap-3">
                <label v-for="type in eventTypes" :key="type.value"
                  class="relative flex flex-col items-center p-3 border-2 rounded-3 cursor-pointer transition-all"
                  :class="eventForm.type === type.value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="eventForm.type" :value="type.value" class="sr-only" />
                  <span class="text-2xl mb-1">{{ type.icon }}</span>
                  <span class="text-xs font-semibold text-gray-900 dark:text-white text-center">{{ type.label }}</span>
                  <svg v-if="eventForm.type === type.value" class="absolute top-2 right-2 w-4 h-4 text-brand-500"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>

            <!-- Title -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Titre <span class="text-red-500">*</span>
              </label>
              <input v-model="eventForm.title" type="text" required
                placeholder="Ex: Réunion d'équipe, Sprint planning..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea v-model="eventForm.description" rows="3" placeholder="Ajoutez des détails sur l'événement..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>

            <!-- Date & Time -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Date de début <span class="text-red-500">*</span>
                </label>
                <input v-model="eventForm.start_date" type="datetime-local" required
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Date de fin
                </label>
                <input v-model="eventForm.end_date" type="datetime-local"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
              </div>
            </div>

            <!-- Location -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Lieu
              </label>
              <input v-model="eventForm.location" type="text" placeholder="Ex: Salle de réunion A, Zoom, Bureau..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showEventModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            Annuler
          </button>
          <button type="button" @click="createNewEvent"
            :disabled="creatingEvent || !eventForm.title || !eventForm.start_date"
            class="px-5 py-2.5 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="creatingEvent" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ creatingEvent ? 'Création...' : 'Créer l\'événement' }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTeams } from '@/composables/useTeams'
import { useTeamMessages } from '@/composables/useTeamMessages'
import { useTeamAnnouncements } from '@/composables/useTeamAnnouncements'
import { useTeamResources } from '@/composables/useTeamResources'
import { useTeamActivities } from '@/composables/useTeamActivities'
import { useTeamPresence } from '@/composables/useTeamPresence'
import { useTeamCalendar } from '@/composables/useTeamCalendar'
import { useUsers } from '@/composables/useUsers'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'

const route = useRoute()
const router = useRouter()
const { currentTeam: team, loading, fetchTeam, removeMember, updateTeam: updateTeamApi, deleteTeam: deleteTeamApi, addMember: addMemberApi } = useTeams()
const { messages, sendMessage: sendMessageApi, fetchMessages } = useTeamMessages()
const { announcements, fetchAnnouncements, createAnnouncement, deleteAnnouncement } = useTeamAnnouncements()
const { resources, fetchResources, createResource, deleteResource } = useTeamResources()
const { activities, fetchActivities, getActivityIcon, getActivityLabel } = useTeamActivities()
const { presences, fetchPresences, startPresenceTracking, stopPresenceTracking, getStatusBadge, isOnline, formatLastSeen } = useTeamPresence()
const { events, fetchEvents, createEvent, deleteEvent, getEventTypeStyle, formatEventDate, getEventDuration, isToday, isPast } = useTeamCalendar()
const { users, fetchUsers } = useUsers()

const activeTab = ref('chat')
const newMessage = ref('')
const chatContainer = ref(null)
const showAddMemberModal = ref(false)
const showEditModal = ref(false)
const showAnnouncementModal = ref(false)
const showResourceModal = ref(false)
const showEventModal = ref(false)
const updating = ref(false)
const addingMember = ref(false)
const creatingAnnouncement = ref(false)
const creatingResource = ref(false)
const creatingEvent = ref(false)

// Edit form
const editForm = ref({
  name: '',
  description: '',
  visibility: 'private'
})

// Add member form
const newMemberForm = ref({
  user_id: '',
  role: 'member'
})

// Announcement form
const announcementForm = ref({
  title: '',
  content: '',
  priority: 'normal'
})

// Resource form
const resourceForm = ref({
  title: '',
  description: '',
  type: 'document',
  url: ''
})

// Calendar event form
const eventForm = ref({
  title: '',
  description: '',
  start_date: '',
  end_date: '',
  type: 'meeting',
  location: '',
  attendees: []
})

const resourceTypes = [
  { value: 'document', label: 'Document', icon: '📄' },
  { value: 'link', label: 'Lien', icon: '🔗' },
  { value: 'template', label: 'Template', icon: '📋' },
  { value: 'tool', label: 'Outil', icon: '🔧' }
]

const eventTypes = [
  { value: 'meeting', label: 'Réunion', icon: '👥' },
  { value: 'deadline', label: 'Échéance', icon: '⏰' },
  { value: 'milestone', label: 'Jalon', icon: '🎯' },
  { value: 'task', label: 'Tâche', icon: '✓' },
  { value: 'reminder', label: 'Rappel', icon: '🔔' },
  { value: 'event', label: 'Événement', icon: '📅' }
]

// Visibility options
const visibilityOptions = [
  { value: 'public', label: 'Public', icon: '🌍', description: 'Visible par tous' },
  { value: 'private', label: 'Privé', icon: '🔒', description: 'Sur invitation' },
  { value: 'secret', label: 'Secret', icon: '🕵️', description: 'Totalement privé' }
]

// Member roles
const memberRoles = [
  { value: 'admin', label: 'Administrateur', icon: '👑', description: 'Tous les droits' },
  { value: 'moderator', label: 'Modérateur', icon: '🛡️', description: 'Modération' },
  { value: 'member', label: 'Membre', icon: '👤', description: 'Accès standard' }
]

// Available users (not already in team)
const availableUsers = computed(() => {
  if (!users.value || !team.value) return []
  const teamMemberIds = team.value.members?.map(m => m.id) || []
  return users.value.filter(u => !teamMemberIds.includes(u.id))
})

// Watch team changes to update edit form
watch(team, (newTeam) => {
  if (newTeam) {
    editForm.value = {
      name: newTeam.name,
      description: newTeam.description || '',
      visibility: newTeam.visibility || 'private'
    }
  }
}, { immediate: true })

// Icônes des onglets — fonctions de rendu (h()) car le build runtime de
// Vue (utilisé par Vite) ne compile pas les chaînes template: à la volée.
const strokeIcon = (d) => ({
  render: () => h(
    'svg',
    { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' },
    [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': 2, d })]
  ),
})

const ChatIcon = strokeIcon('M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z')
const UsersIcon = strokeIcon('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z')
const FolderIcon = strokeIcon('M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z')
const ClockIcon = strokeIcon('M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z')
const AnnouncementIcon = strokeIcon('M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z')
const CalendarIcon = strokeIcon('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z')

const tabs = computed(() => [
  { id: 'chat', label: 'Discussion', icon: ChatIcon, badge: messages.value.length || null },
  { id: 'announcements', label: 'Annonces', icon: AnnouncementIcon, badge: announcements.value.length || null },
  { id: 'members', label: 'Membres', icon: UsersIcon, badge: team.value?.members_count || null },
  { id: 'resources', label: 'Ressources', icon: FolderIcon, badge: resources.value.length || null },
  { id: 'calendar', label: 'Calendrier', icon: CalendarIcon, badge: events.value.length || null },
  { id: 'activity', label: 'Activité', icon: ClockIcon }
])

const getInitials = (name) => {
  return name?.split(' ').map(w => w[0]).join('').toUpperCase().substring(0, 2) || '??'
}

const getUserInitials = (user) => {
  return user?.nom ? getInitials(user.nom) : '??'
}

const getVisibilityLabel = (visibility) => {
  const labels = { public: 'Public', private: 'Privé', secret: 'Secret' }
  return labels[visibility] || visibility
}

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    moderator: 'Modérateur',
    member: 'Membre'
  }
  return labels[role] || role
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getGroupedReactions = (reactions) => {
  const grouped = {}
  reactions.forEach(r => {
    if (!grouped[r.emoji]) grouped[r.emoji] = { emoji: r.emoji, count: 0 }
    grouped[r.emoji].count++
  })
  return Object.values(grouped)
}

const sendMessage = async () => {
  if (!newMessage.value.trim()) return

  try {
    await sendMessageApi(route.params.uuid, { content: newMessage.value })
    newMessage.value = ''
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Error sending message:', error)
  }
}

const scrollToBottom = () => {
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

const removeMemberConfirm = async (member) => {
  if (confirm(`Retirer ${member.nom} de l'équipe ?`)) {
    try {
      await removeMember(route.params.uuid, member.id)
      await fetchTeam(route.params.uuid)
    } catch (error) {
      console.error('Error removing member:', error)
      alert('Erreur lors du retrait du membre')
    }
  }
}

const updateTeam = async () => {
  if (!editForm.value.name) return

  updating.value = true
  try {
    await updateTeamApi(route.params.uuid, editForm.value)
    showEditModal.value = false
    await fetchTeam(route.params.uuid)
    alert('Équipe mise à jour avec succès')
  } catch (error) {
    console.error('Error updating team:', error)
    alert(error.response?.data?.message || 'Erreur lors de la mise à jour de l\'équipe')
  } finally {
    updating.value = false
  }
}

const deleteTeamConfirm = async () => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer l'équipe "${team.value?.name}" ? Cette action est irréversible.`)) {
    try {
      await deleteTeamApi(route.params.uuid)
      alert('Équipe supprimée avec succès')
      router.push({ name: 'teams.index' })
    } catch (error) {
      console.error('Error deleting team:', error)
      alert(error.response?.data?.message || 'Erreur lors de la suppression de l\'équipe')
    }
  }
}

const addMember = async () => {
  if (!newMemberForm.value.user_id) return

  addingMember.value = true
  try {
    await addMemberApi(route.params.uuid, newMemberForm.value)
    showAddMemberModal.value = false
    newMemberForm.value = { user_id: '', role: 'member' }
    await fetchTeam(route.params.uuid)
    alert('Membre ajouté avec succès')
  } catch (error) {
    console.error('Error adding member:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'ajout du membre')
  } finally {
    addingMember.value = false
  }
}

// Announcements
const createNewAnnouncement = async () => {
  if (!announcementForm.value.title || !announcementForm.value.content) return

  creatingAnnouncement.value = true
  try {
    await createAnnouncement(route.params.uuid, announcementForm.value)
    showAnnouncementModal.value = false
    announcementForm.value = { title: '', content: '', priority: 'normal' }
    alert('Annonce créée avec succès')
  } catch (error) {
    console.error('Error creating announcement:', error)
    alert(error.response?.data?.message || 'Erreur lors de la création de l\'annonce')
  } finally {
    creatingAnnouncement.value = false
  }
}

const deleteAnnouncementConfirm = async (announcement) => {
  if (confirm(`Supprimer l'annonce "${announcement.title}" ?`)) {
    try {
      await deleteAnnouncement(route.params.uuid, announcement.id)
      alert('Annonce supprimée avec succès')
    } catch (error) {
      console.error('Error deleting announcement:', error)
      alert(error.response?.data?.message || 'Erreur lors de la suppression de l\'annonce')
    }
  }
}

// Resources
const createNewResource = async () => {
  if (!resourceForm.value.title || !resourceForm.value.url) return

  creatingResource.value = true
  try {
    await createResource(route.params.uuid, resourceForm.value)
    showResourceModal.value = false
    resourceForm.value = { title: '', description: '', type: 'document', url: '' }
    alert('Ressource ajoutée avec succès')
  } catch (error) {
    console.error('Error creating resource:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'ajout de la ressource')
  } finally {
    creatingResource.value = false
  }
}

const deleteResourceConfirm = async (resource) => {
  if (confirm(`Supprimer la ressource "${resource.title}" ?`)) {
    try {
      await deleteResource(route.params.uuid, resource.id)
      alert('Ressource supprimée avec succès')
    } catch (error) {
      console.error('Error deleting resource:', error)
      alert(error.response?.data?.message || 'Erreur lors de la suppression de la ressource')
    }
  }
}

const getResourceIcon = (type) => {
  const icons = {
    'document': '📄',
    'link': '🔗',
    'template': '📋',
    'tool': '🔧'
  }
  return icons[type] || '📎'
}

// Calendar Events
const createNewEvent = async () => {
  if (!eventForm.value.title || !eventForm.value.start_date) return

  creatingEvent.value = true
  try {
    await createEvent(route.params.uuid, eventForm.value)
    showEventModal.value = false
    eventForm.value = { title: '', description: '', start_date: '', end_date: '', type: 'meeting', location: '', attendees: [] }
    alert('Événement créé avec succès')
  } catch (error) {
    console.error('Error creating event:', error)
    alert(error.response?.data?.message || 'Erreur lors de la création de l\'événement')
  } finally {
    creatingEvent.value = false
  }
}

const deleteEventConfirm = async (event) => {
  if (confirm(`Supprimer l'événement "${event.title}" ?`)) {
    try {
      await deleteEvent(route.params.uuid, event.id)
      alert('Événement supprimé avec succès')
    } catch (error) {
      console.error('Error deleting event:', error)
      alert(error.response?.data?.message || 'Erreur lors de la suppression de l\'événement')
    }
  }
}

onMounted(async () => {
  console.log('Team Show page mounted')
  console.log('Team UUID from route:', route.params.uuid)
  console.log('Token:', localStorage.getItem('auth_token') ? 'Present' : 'Missing')

  try {
    console.log('Fetching team details...')
    const teamData = await fetchTeam(route.params.uuid)
    console.log('Team fetched successfully:', teamData)

    // Load all tab data
    console.log('Fetching team messages...')
    await fetchMessages(route.params.uuid)
    console.log('Messages fetched successfully:', messages.value)

    console.log('Fetching announcements...')
    await fetchAnnouncements(route.params.uuid)
    console.log('Announcements fetched successfully:', announcements.value)

    console.log('Fetching resources...')
    await fetchResources(route.params.uuid)
    console.log('Resources fetched successfully:', resources.value)

    console.log('Fetching activities...')
    await fetchActivities(route.params.uuid)
    console.log('Activities fetched successfully:', activities.value)

    console.log('Fetching presences...')
    await fetchPresences(route.params.uuid)
    console.log('Presences fetched successfully:', presences.value)

    console.log('Fetching calendar events...')
    await fetchEvents(route.params.uuid)
    console.log('Events fetched successfully:', events.value)

    console.log('Fetching users for member selection...')
    await fetchUsers()
    console.log('Users fetched successfully')

    // Start presence tracking (heartbeat every 30 seconds)
    console.log('Starting presence tracking...')
    startPresenceTracking(route.params.uuid)

    nextTick(scrollToBottom)
  } catch (error) {
    console.error('Error loading team details:', error)
    console.error('Error details:', error.response?.data || error.message)
  }
})

// Stop presence tracking when leaving page
onUnmounted(() => {
  stopPresenceTracking(route.params.uuid)
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #4a5568;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #718096;
}
</style>
