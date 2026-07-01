<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="team?.name || $t('team_show.fallback_name')" />

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
              <div class="flex items-center gap-3 mt-2 flex-wrap">
                <!-- Linked project chip -->
                <router-link
                  v-if="team.project"
                  :to="{ name: 'projets', query: { projet: team.project.id } }"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/40 transition-colors"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                  </svg>
                  {{ team.project.nom }}
                </router-link>

                <span class="text-sm text-gray-600 dark:text-gray-400">
                  <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  {{ $t('team_show.members_count', { count: team.members_count || 0 }) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Actions (visible only to team owner or super admin) -->
          <div v-if="isTeamOwner" class="flex gap-3">
            <button @click="showEditModal = true"
              class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              {{ $t('team_show.modify') }}
            </button>
            <button @click="deleteTeamConfirm"
              class="px-4 py-2 border border-red-300 dark:border-red-700 rounded-3 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              {{ $t('team_show.delete') }}
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
          <div v-if="activeTab === 'chat'" dusk="chat-tab" class="space-y-4">
            <div class="rounded-3 p-4 h-[600px] flex flex-col border border-gray-200 dark:border-gray-700">

              <!-- Messages Container (stagger Guide 23) -->
              <div ref="chatContainer" class="flex-1 overflow-y-auto space-y-3 pr-2 custom-scrollbar">

                <!-- Empty State -->
                <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                  <div class="w-16 h-16 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                  </div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ $t('team_show.no_messages') }}</h3>
                  <p class="text-gray-500 dark:text-gray-400">{{ $t('team_show.be_first_message') }}</p>
                </div>

                <!-- Message rows -->
                <div ref="chatMessagesRef" class="flex flex-col gap-3 px-2">
                  <div
                    v-for="message in messages"
                    :key="message.uuid || message.id"
                    :data-message-uuid="message.uuid"
                    class="stagger-item group flex gap-2"
                    :class="isOwn(message) ? 'flex-row-reverse' : 'flex-row'"
                    dusk="chat-message"
                  >
                    <!-- Avatar -->
                    <div class="shrink-0 mt-1">
                      <img
                        v-if="message.user?.avatar"
                        :src="message.user.avatar"
                        :alt="message.user.nom"
                        class="w-8 h-8 rounded-full object-cover"
                      />
                      <div
                        v-else
                        class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold"
                        :class="isOwn(message) ? 'bg-blue-600 text-white' : colorFor(message.user?.id).avatar"
                      >
                        {{ getUserInitials(message.user) }}
                      </div>
                    </div>

                    <!-- Bulle -->
                    <div class="flex flex-col max-w-[70%]" :class="isOwn(message) ? 'items-end' : 'items-start'">
                      <!-- Nom + heure -->
                      <div
                        class="flex items-baseline gap-2 mb-1 px-1"
                        :class="isOwn(message) ? 'flex-row-reverse' : 'flex-row'"
                      >
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                          {{ isOwn(message) ? $t('chat.you') : (message.user?.nom ?? 'Utilisateur') }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(message.created_at) }}</span>
                        <span v-if="message.is_edited" class="text-xs text-gray-400 italic">{{ $t('team_show.message_edited') }}</span>
                        <span v-if="message._pending" class="text-xs text-gray-400 animate-pulse">…</span>
                      </div>

                      <!-- Reply-to quote -->
                      <div
                        v-if="message.reply_to"
                        class="mb-1 px-3 py-1.5 rounded-lg text-xs border-l-2 border-gray-300 dark:border-gray-500 bg-gray-100 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 max-w-full"
                      >
                        <span class="font-medium">{{ message.reply_to.user_nom }}</span>: {{ message.reply_to.content_snippet }}
                      </div>

                      <!-- Inline edit OR texte -->
                      <div v-if="editingUuid === message.uuid" class="w-full">
                        <textarea
                          v-model="editContent"
                          rows="2"
                          class="w-full px-3 py-2 text-sm border border-blue-400 rounded-2xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none resize-none"
                          @keydown.enter.exact.prevent="confirmEdit(message.uuid)"
                          @keydown.esc="cancelEdit"
                        />
                        <div class="flex gap-2 mt-1" :class="isOwn(message) ? 'justify-end' : 'justify-start'">
                          <button @click="confirmEdit(message.uuid)" class="px-3 py-1 text-xs bg-blue-600 text-white rounded-full hover:bg-blue-700">{{ $t('common.save') }}</button>
                          <button @click="cancelEdit" class="px-3 py-1 text-xs border border-gray-300 rounded-full text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800">{{ $t('common.cancel') }}</button>
                        </div>
                      </div>

                      <!-- Texte (masqué si vide et des pièces jointes sont présentes) -->
                      <div
                        v-else-if="message.content || !message.attachments?.length"
                        class="px-3 py-2 rounded-2xl text-sm break-words whitespace-pre-wrap leading-relaxed"
                        :class="isOwn(message)
                          ? 'bg-blue-600 text-white rounded-tr-sm'
                          : [colorFor(message.user?.id).bg, colorFor(message.user?.id).text, 'rounded-tl-sm']"
                        :style="message._pending ? 'opacity: 0.65' : ''"
                      >
                        {{ message.content }}
                      </div>

                      <!-- Pièces jointes -->
                      <div v-if="message.attachments?.length" class="mt-1.5 flex flex-wrap gap-1.5">
                        <template v-for="att in message.attachments" :key="att.url">
                          <a v-if="att.type === 'image'" :href="att.url" target="_blank" rel="noopener" class="block">
                            <img :src="att.url" :alt="att.name ?? 'photo'" class="max-h-48 max-w-xs rounded-xl object-cover border border-gray-200 dark:border-gray-600 hover:opacity-90 transition-opacity cursor-zoom-in" />
                          </a>
                          <a
                            v-else
                            :href="att.url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded hover:underline text-gray-700 dark:text-gray-300"
                          >
                            📎 {{ att.name }}
                          </a>
                        </template>
                      </div>

                      <!-- Réactions -->
                      <div v-if="(message.reactions || []).length" class="flex flex-wrap gap-1 mt-1.5 px-1">
                        <button
                          v-for="reaction in (message.reactions || [])"
                          :key="reaction.emoji"
                          :disabled="!canPickEmoji(message, reaction.emoji)"
                          class="inline-flex items-center gap-1 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 hover:border-blue-400 rounded-full px-2 py-0.5 transition-colors shadow-sm"
                          :class="reaction.did_react ? 'border-blue-400 ring-1 ring-blue-300' : ''"
                          @click="toggleReaction(message.uuid, reaction.emoji, reaction.did_react)"
                        >
                          {{ reaction.emoji }} {{ reaction.count }}
                        </button>
                      </div>

                      <!-- Actions au survol -->
                      <div
                        v-if="!message._pending"
                        class="mt-1 px-1 flex items-center gap-2 transition-opacity opacity-0 group-hover:opacity-100"
                        :class="isOwn(message) ? 'flex-row-reverse' : 'flex-row'"
                      >
                        <!-- Quick emoji picker -->
                        <div class="flex items-center gap-0.5">
                          <button
                            v-for="emoji in quickEmojis"
                            :key="emoji"
                            :disabled="!canPickEmoji(message, emoji)"
                            class="text-sm transition-transform rounded-full w-6 h-6 flex items-center justify-center"
                            :class="[
                              isReactedByMe(message, emoji) ? 'bg-blue-100 dark:bg-blue-900/40 ring-1 ring-blue-400' : '',
                              canPickEmoji(message, emoji) ? 'hover:scale-125' : 'opacity-30 cursor-not-allowed',
                            ]"
                            @click="canPickEmoji(message, emoji) && pickQuickEmoji(message, emoji)"
                          >{{ emoji }}</button>
                          <span class="text-xs text-gray-400 dark:text-gray-500 pl-0.5 whitespace-nowrap">{{ userReactionCount(message) }}/3</span>
                        </div>
                        <!-- Répondre -->
                        <button
                          @click="startReply(message)"
                          class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                          :title="$t('team_show.reply_to')"
                        >↩</button>
                        <!-- Modifier (propres messages) -->
                        <button
                          v-if="isOwn(message)"
                          dusk="message-edit-btn"
                          @click="startEdit(message)"
                          class="text-xs text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                          :title="$t('team_show.edit_message')"
                        >{{ $t('common.edit') }}</button>
                        <!-- Supprimer (propres messages ou propriétaire) -->
                        <button
                          v-if="isOwn(message) || isTeamOwner"
                          dusk="message-delete-btn"
                          @click="confirmDeleteMessage(message.uuid)"
                          class="text-xs text-gray-400 hover:text-red-500 transition-colors"
                          :title="$t('team_show.delete_message')"
                        >{{ $t('common.delete') }}</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Typing indicator -->
              <div v-if="typingUsers.length" class="px-1 py-1 text-xs text-gray-500 dark:text-gray-400 italic">
                {{ typingUsers.map(u => u.nom).join(', ') }} {{ $t('team_show.typing_indicator') }}
              </div>

              <!-- Reply-to banner -->
              <div v-if="replyingTo" class="flex items-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-3 text-sm">
                <div class="flex-1 min-w-0">
                  <span class="font-medium text-blue-700 dark:text-blue-400">{{ replyingTo.user?.nom }}</span>:
                  <span class="text-gray-600 dark:text-gray-400 truncate">{{ replyingTo.content }}</span>
                </div>
                <button @click="replyingTo = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>

              <!-- Suggestions @mention -->
              <div
                v-if="mentionSuggestions.length"
                class="mb-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg overflow-hidden"
              >
                <button
                  v-for="member in mentionSuggestions"
                  :key="member.id"
                  type="button"
                  class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left"
                  :class="member._isEveryone ? 'text-amber-700 dark:text-amber-300' : 'text-gray-800 dark:text-gray-200'"
                  @mousedown.prevent="insertMention(member)"
                >
                  <div
                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium shrink-0"
                    :class="member._isEveryone ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'"
                  >
                    {{ member._isEveryone ? '📢' : (member.nom ?? '?').charAt(0).toUpperCase() }}
                  </div>
                  <span class="font-medium">@{{ member.nom }}</span>
                  <span v-if="member._isEveryone" class="text-xs text-gray-400">{{ $t('chat.mention_everyone_hint') }}</span>
                  <span v-else class="text-gray-400 text-xs">{{ member.fonction }}</span>
                </button>
              </div>

              <!-- Photo preview strip -->
              <div v-if="pendingPhotos.length" class="flex flex-wrap gap-2 px-1 py-1">
                <div v-for="(photo, idx) in pendingPhotos" :key="idx" class="relative">
                  <img :src="photo.url" class="h-20 w-20 rounded-xl object-cover border border-gray-200 dark:border-gray-700" />
                  <div v-if="photo._uploading" class="absolute inset-0 flex items-center justify-center bg-black/30 rounded-xl">
                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                  </div>
                  <button v-else @click="pendingPhotos.splice(idx, 1)" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-gray-700 text-white text-xs flex items-center justify-center hover:bg-red-600">✕</button>
                </div>
              </div>

              <!-- Attachment preview (non-image) -->
              <div v-if="attachmentFile" class="flex items-center gap-2 px-3 py-1 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-3">
                📎 {{ attachmentFile.name }}
                <button @click="attachmentFile = null; attachmentInputRef && (attachmentInputRef.value = '')" class="text-gray-400 hover:text-red-500 ml-auto">✕</button>
              </div>

              <!-- Message Input -->
              <form @submit.prevent="sendMessage" class="mt-2 flex gap-2 items-end">
                <!-- Photo button -->
                <label class="flex-shrink-0 cursor-pointer p-2 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors" :title="$t('chat.attach_photo')">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                  <input ref="photoInputRef" type="file" class="sr-only" accept="image/jpeg,image/png,image/webp,image/gif" multiple @change="onPhotoSelected" />
                </label>
                <!-- Attachment button -->
                <label class="flex-shrink-0 cursor-pointer p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors" :title="$t('team_show.attachment_btn')">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                  <input ref="attachmentInputRef" type="file" class="sr-only" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt" @change="onAttachmentChange" />
                </label>

                <textarea
                  ref="chatInputRef"
                  v-model="newMessage"
                  rows="1"
                  dusk="chat-input"
                  :placeholder="$t('team_show.message_placeholder')"
                  class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all text-sm resize-none overflow-hidden"
                  @input="onChatInput"
                  @keydown.enter.exact.prevent="sendMessage"
                  @keydown.enter.shift.exact="newMessage += '\n'"
                  @keydown="onChatKeydown"
                />
                <button
                  type="submit"
                  dusk="chat-send-btn"
                  :disabled="!newMessage.trim() && !attachmentFile && !pendingPhotos.some(p => !p._uploading)"
                  class="px-4 py-2.5 bg-brand-600 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:bg-brand-700 flex items-center gap-1.5 text-sm"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                  </svg>
                  {{ $t('team_show.send') }}
                </button>
              </form>
            </div>
          </div>

          <!-- Members Tab -->
          <div v-else-if="activeTab === 'members'" class="space-y-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.members_title') }}</h3>
              <button v-if="isTeamOwner" @click="showAddMemberModal = true"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                {{ $t('team_show.add_member') }}
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
                      :title="isOnline(presences.find(p => p.user_id === member.id)?.last_seen) ? $t('team_show.online') : formatLastSeen(presences.find(p => p.user_id === member.id)?.last_seen)">
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
                        {{ isOnline(presences.find(p => p.user_id === member.id)?.last_seen) ? ('🟢 ' + $t('team_show.online')) :
                          formatLastSeen(presences.find(p => p.user_id === member.id)?.last_seen) }}
                      </span>
                    </div>
                  </div>
                  <button v-if="isTeamOwner && member.pivot?.role !== 'owner'" @click="removeMemberConfirm(member)"
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
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.announcements_title') }}</h3>
              <button @click="showAnnouncementModal = true"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                {{ $t('team_show.new_announcement') }}
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
                        {{ $t('team_show.urgent') }}
                      </span>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">{{ announcement.content }}</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                      <span>{{ $t('team_show.by_prefix') }} {{ announcement.user?.nom }}</span>
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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ $t('team_show.no_announcements') }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ $t('team_show.no_announcements_desc') }}</p>
              </div>
            </div>
          </div>

          <!-- Resources Tab -->
          <div v-else-if="activeTab === 'resources'" class="space-y-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.resources_title') }}</h3>
              <button @click="showResourceModal = true"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('team_show.add_resource') }}
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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ $t('team_show.no_resources') }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ $t('team_show.no_resources_desc') }}</p>
              </div>
            </div>
          </div>

          <!-- Calendar Tab -->
          <div v-else-if="activeTab === 'calendar'" class="space-y-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.calendar_title') }}</h3>
              <button @click="showEventModal = true"
                class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('team_show.new_event') }}
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
                        {{ $t('team_show.today') }}
                      </span>
                      <span v-if="isPast(event.end_date || event.start_date)"
                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ $t('team_show.past') }}
                      </span>
                    </div>
                    <div v-if="event.attendees && event.attendees.length > 0" class="mt-3 flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      <div class="flex -space-x-2">
                        <div v-for="(attendee, index) in event.attendees.filter(a => a).slice(0, 5)" :key="attendee.id"
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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ $t('team_show.no_events') }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ $t('team_show.no_events_desc') }}</p>
              </div>
            </div>
          </div>

          <!-- Activity Tab -->
          <div v-else-if="activeTab === 'activity'" class="space-y-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">{{ $t('team_show.recent_activities') }}</h3>

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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ $t('team_show.no_activities') }}</h3>
                <p class="text-gray-500 dark:text-gray-400">{{ $t('team_show.no_activities_desc') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Team Modal -->
    <div v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 "
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
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.modal_edit_title') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('team_show.modal_edit_subtitle') }}</p>
              </div>
            </div>
            <button type="button" @click="showEditModal = false"
              class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
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
                {{ $t('team_show.team_name_label') }} <span class="text-red-500">*</span>
              </label>
              <input v-model="editForm.name" type="text" required :placeholder="$t('team_show.team_name_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('team_show.description_label') }}
              </label>
              <textarea v-model="editForm.description" rows="3" :placeholder="$t('team_show.description_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>

          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showEditModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            {{ $t('team_show.cancel') }}
          </button>
          <button type="button" @click="updateTeam" :disabled="updating || !editForm.name"
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="updating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ updating ? $t('team_show.updating') : $t('team_show.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div v-if="showAddMemberModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 "
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
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.modal_add_member_title') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('team_show.modal_add_member_subtitle') }}</p>
              </div>
            </div>
            <button type="button" @click="showAddMemberModal = false"
              class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
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
                {{ $t('team_show.user_label') }} <span class="text-red-500">*</span>
              </label>
              <select v-model="newMemberForm.user_id" required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all">
                <option value="">{{ $t('team_show.select_user') }}</option>
                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                  {{ user.nom }} ({{ user.email }})
                </option>
              </select>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                {{ $t('team_show.role_label') }}
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
            {{ $t('team_show.cancel') }}
          </button>
          <button type="button" @click="addMember" :disabled="addingMember || !newMemberForm.user_id"
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="addingMember" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ addingMember ? $t('team_show.adding') : $t('common.add') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Team Modal -->
    <div v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 "
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
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.modal_edit_title') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('team_show.modal_edit_subtitle') }}</p>
              </div>
            </div>
            <button type="button" @click="showEditModal = false"
              class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
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
                {{ $t('team_show.team_name_label') }} <span class="text-red-500">*</span>
              </label>
              <input v-model="editForm.name" type="text" required :placeholder="$t('team_show.team_name_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('team_show.description_label') }}
              </label>
              <textarea v-model="editForm.description" rows="3" :placeholder="$t('team_show.description_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>

          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showEditModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            {{ $t('team_show.cancel') }}
          </button>
          <button type="button" @click="updateTeam" :disabled="updating || !editForm.name"
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="updating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ updating ? $t('team_show.updating') : $t('team_show.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div v-if="showAddMemberModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 "
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
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.modal_add_member_title') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('team_show.modal_add_member_subtitle') }}</p>
              </div>
            </div>
            <button type="button" @click="showAddMemberModal = false"
              class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
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
                {{ $t('team_show.user_label') }} <span class="text-red-500">*</span>
              </label>
              <select v-model="newMemberForm.user_id" required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all">
                <option value="">{{ $t('team_show.select_user') }}</option>
                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                  {{ user.nom }} ({{ user.email }})
                </option>
              </select>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                {{ $t('team_show.role_label') }}
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
            {{ $t('team_show.cancel') }}
          </button>
          <button type="button" @click="addMember" :disabled="addingMember || !newMemberForm.user_id"
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="addingMember" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ addingMember ? $t('team_show.adding') : $t('common.add') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Announcement Modal -->
    <div v-if="showAnnouncementModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 "
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
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.modal_new_announcement') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('team_show.modal_new_announcement_subtitle') }}</p>
              </div>
            </div>
            <button type="button" @click="showAnnouncementModal = false"
              class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
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
                {{ $t('team_show.announcement_title_label') }} <span class="text-red-500">*</span>
              </label>
              <input v-model="announcementForm.title" type="text" required
                :placeholder="$t('team_show.announcement_title_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" />
            </div>

            <!-- Content -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('team_show.announcement_content_label') }} <span class="text-red-500">*</span>
              </label>
              <textarea v-model="announcementForm.content" rows="6" required
                :placeholder="$t('team_show.announcement_content_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>

            <!-- Priority -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                {{ $t('team_show.announcement_priority') }}
              </label>
              <div class="grid grid-cols-2 gap-3">
                <label class="relative flex items-center gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="announcementForm.priority === 'normal'
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'">
                  <input type="radio" v-model="announcementForm.priority" value="normal" class="sr-only" />
                  <span class="text-2xl">ℹ️</span>
                  <div class="flex-1">
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $t('team_show.priority_normal') }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('team_show.priority_normal_desc') }}</span>
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
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $t('team_show.priority_urgent') }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('team_show.priority_urgent_desc') }}</span>
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
            {{ $t('team_show.cancel') }}
          </button>
          <button type="button" @click="createNewAnnouncement"
            :disabled="creatingAnnouncement || !announcementForm.title || !announcementForm.content"
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="creatingAnnouncement" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ creatingAnnouncement ? $t('team_show.creating_announcement') : $t('team_show.create_announcement') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Resource Modal -->
    <div v-if="showResourceModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 "
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
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.modal_add_resource') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('team_show.modal_add_resource_subtitle') }}</p>
              </div>
            </div>
            <button type="button" @click="showResourceModal = false"
              class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
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
                {{ $t('team_show.resource_type_label') }}
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
                {{ $t('team_show.resource_title_label') }} <span class="text-red-500">*</span>
              </label>
              <input v-model="resourceForm.title" type="text" required
                :placeholder="$t('team_show.resource_title_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- URL -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('team_show.resource_url_label') }} <span class="text-red-500">*</span>
              </label>
              <input v-model="resourceForm.url" type="text" required
                :placeholder="$t('team_show.resource_url_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('team_show.resource_desc_label') }}
              </label>
              <textarea v-model="resourceForm.description" rows="3" :placeholder="$t('team_show.resource_desc_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showResourceModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            {{ $t('team_show.cancel') }}
          </button>
          <button type="button" @click="createNewResource"
            :disabled="creatingResource || !resourceForm.title || !resourceForm.url"
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="creatingResource" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ creatingResource ? $t('team_show.adding_resource') : $t('team_show.add_resource_btn') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Event Modal -->
    <div v-if="showEventModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 "
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
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('team_show.modal_new_event') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('team_show.modal_new_event_subtitle') }}</p>
              </div>
            </div>
            <button type="button" @click="showEventModal = false"
              class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
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
                {{ $t('team_show.event_type_label') }}
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
                {{ $t('team_show.event_title_label') }} <span class="text-red-500">*</span>
              </label>
              <input v-model="eventForm.title" type="text" required
                :placeholder="$t('team_show.event_title_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('team_show.event_desc_label') }}
              </label>
              <textarea v-model="eventForm.description" rows="3" :placeholder="$t('team_show.event_desc_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"></textarea>
            </div>

            <!-- Date & Time -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  {{ $t('team_show.event_start_label') }} <span class="text-red-500">*</span>
                </label>
                <input v-model="eventForm.start_date" type="datetime-local" required
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  {{ $t('team_show.event_end_label') }}
                </label>
                <input v-model="eventForm.end_date" type="datetime-local"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
              </div>
            </div>

            <!-- Location -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('team_show.event_location_label') }}
              </label>
              <input v-model="eventForm.location" type="text" :placeholder="$t('team_show.event_location_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <button type="button" @click="showEventModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
            {{ $t('team_show.cancel') }}
          </button>
          <button type="button" @click="createNewEvent"
            :disabled="creatingEvent || !eventForm.title || !eventForm.start_date"
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-3 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <svg v-if="creatingEvent" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            {{ creatingEvent ? $t('team_show.creating_event') : $t('team_show.create_event') }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch, h } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useTeams } from '@/composables/useTeams'
import { useTeamMessages } from '@/composables/useTeamMessages'
import { useTeamAnnouncements } from '@/composables/useTeamAnnouncements'
import { useTeamResources } from '@/composables/useTeamResources'
import { useTeamActivities } from '@/composables/useTeamActivities'
import { useTeamPresence } from '@/composables/useTeamPresence'
import { useTeamCalendar } from '@/composables/useTeamCalendar'
import { useAuthStore } from '@/stores/authStore'
import api from '@/api/axios'
import { getRoleLabel } from '@/permissions/Permission'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { currentTeam: team, loading, fetchTeam, removeMember, updateTeam: updateTeamApi, deleteTeam: deleteTeamApi, addMember: addMemberApi } = useTeams()
const {
  messages, loading: messagesLoading, error: messageError, typingUsers,
  fetchMessages, sendMessage: sendMessageApi,
  updateMessage: updateMessageApi, deleteMessage: deleteMessageApi,
  addReaction, removeReaction, sendTyping, markRead,
  subscribeToTeam, unsubscribeFromTeam,
} = useTeamMessages()
const { announcements, fetchAnnouncements, createAnnouncement, deleteAnnouncement } = useTeamAnnouncements()
const { resources, fetchResources, createResource, deleteResource } = useTeamResources()
const { activities, fetchActivities, getActivityIcon, getActivityLabel } = useTeamActivities()
const { presences, fetchPresences, startPresenceTracking, stopPresenceTracking, getStatusBadge, isOnline, formatLastSeen } = useTeamPresence()
const { events, fetchEvents, createEvent, deleteEvent, getEventTypeStyle, formatEventDate, getEventDuration, isToday, isPast } = useTeamCalendar()
const authStore = useAuthStore()

const isTeamOwner = computed(() => {
  if (!team.value || !authStore.user) return false
  return authStore.user.is_super_admin || team.value.owner_id === authStore.user.id
})

const isOwn = (message) => message.user?.id === authStore.currentUser?.id

const users = ref([])
const fetchUsers = async () => {
  const workspaceId = team.value?.workspace_id ?? authStore.currentWorkspaceId
  if (!workspaceId) return
  try {
    const { data } = await api.get(`/workspaces/${workspaceId}/members`)
    users.value = data.data ?? data
  } catch (err) {
    console.error('Erreur lors du chargement des membres:', err)
  }
}

const activeTab = ref('chat')
const newMessage = ref('')
const chatContainer = ref(null)
const chatMessagesRef = ref(null)
const replyingTo = ref(null)
const editingUuid = ref(null)
const editContent = ref('')
const attachmentFile = ref(null)
const attachmentInputRef = ref(null)
const photoInputRef = ref(null)
const pendingPhotos = ref([])
const chatInputRef = ref(null)

// ── @mention ──────────────────────────────────────────────────────────────────
const mentionSuggestions = ref([])
const mentionedUserIds = ref([])
const mentionEveryone = ref(false)
let mentionSearchTimer = null

const EVERYONE_ENTRY = { id: '__everyone__', nom: 'everyone', _isEveryone: true }

const onChatInput = () => {
  if (team.value?.id) { sendTyping(team.value.id) }

  const el = chatInputRef.value
  if (!el) { return }
  const pos = el.selectionStart
  const before = newMessage.value.slice(0, pos)
  const match = before.match(/@(\w*)$/)

  if (!match) {
    mentionSuggestions.value = []
    return
  }

  const query = match[1].toLowerCase()
  clearTimeout(mentionSearchTimer)

  if ('everyone'.startsWith(query) && query.length >= 1) {
    mentionSuggestions.value = [EVERYONE_ENTRY]
    return
  }

  if (query.length < 2) {
    mentionSuggestions.value = []
    return
  }

  mentionSearchTimer = setTimeout(() => {
    const members = team.value?.members ?? []
    mentionSuggestions.value = members
      .filter((m) => m.nom?.toLowerCase().includes(query))
      .slice(0, 5)
  }, 100)
}

const insertMention = (member) => {
  const el = chatInputRef.value
  if (!el) { return }
  const pos = el.selectionStart
  const before = newMessage.value.slice(0, pos)
  const after = newMessage.value.slice(pos)

  if (member._isEveryone) {
    const replaced = before.replace(/@(\w*)$/, '@everyone ')
    newMessage.value = replaced + after
    mentionEveryone.value = true
    mentionSuggestions.value = []
    nextTick(() => {
      el.selectionStart = el.selectionEnd = replaced.length
      el.focus()
    })
    return
  }

  const replaced = before.replace(/@(\w*)$/, `@${member.nom} `)
  newMessage.value = replaced + after
  mentionSuggestions.value = []
  if (!mentionedUserIds.value.includes(member.id)) {
    mentionedUserIds.value.push(member.id)
  }
  nextTick(() => {
    el.selectionStart = el.selectionEnd = replaced.length
    el.focus()
  })
}
const quickEmojis = ['👍', '❤️', '😂', '😮', '👏', '🎉']

// ── Couleurs par expéditeur (palette 50 entrées) ──────────────────────────────
const BUBBLE_PALETTE = [
  { bg: 'bg-emerald-100 dark:bg-emerald-900/40', text: 'text-emerald-900 dark:text-emerald-100', avatar: 'bg-emerald-500 text-white' },
  { bg: 'bg-violet-100 dark:bg-violet-900/40', text: 'text-violet-900 dark:text-violet-100', avatar: 'bg-violet-500 text-white' },
  { bg: 'bg-amber-100 dark:bg-amber-900/40', text: 'text-amber-900 dark:text-amber-100', avatar: 'bg-amber-500 text-white' },
  { bg: 'bg-rose-100 dark:bg-rose-900/40', text: 'text-rose-900 dark:text-rose-100', avatar: 'bg-rose-500 text-white' },
  { bg: 'bg-cyan-100 dark:bg-cyan-900/40', text: 'text-cyan-900 dark:text-cyan-100', avatar: 'bg-cyan-500 text-white' },
  { bg: 'bg-orange-100 dark:bg-orange-900/40', text: 'text-orange-900 dark:text-orange-100', avatar: 'bg-orange-500 text-white' },
  { bg: 'bg-teal-100 dark:bg-teal-900/40', text: 'text-teal-900 dark:text-teal-100', avatar: 'bg-teal-500 text-white' },
  { bg: 'bg-pink-100 dark:bg-pink-900/40', text: 'text-pink-900 dark:text-pink-100', avatar: 'bg-pink-500 text-white' },
  { bg: 'bg-indigo-100 dark:bg-indigo-900/40', text: 'text-indigo-900 dark:text-indigo-100', avatar: 'bg-indigo-500 text-white' },
  { bg: 'bg-lime-100 dark:bg-lime-900/40', text: 'text-lime-900 dark:text-lime-100', avatar: 'bg-lime-600 text-white' },
  { bg: 'bg-sky-100 dark:bg-sky-900/40', text: 'text-sky-900 dark:text-sky-100', avatar: 'bg-sky-500 text-white' },
  { bg: 'bg-fuchsia-100 dark:bg-fuchsia-900/40', text: 'text-fuchsia-900 dark:text-fuchsia-100', avatar: 'bg-fuchsia-500 text-white' },
  { bg: 'bg-red-100 dark:bg-red-900/40', text: 'text-red-900 dark:text-red-100', avatar: 'bg-red-500 text-white' },
  { bg: 'bg-green-100 dark:bg-green-900/40', text: 'text-green-900 dark:text-green-100', avatar: 'bg-green-600 text-white' },
  { bg: 'bg-yellow-100 dark:bg-yellow-900/40', text: 'text-yellow-900 dark:text-yellow-100', avatar: 'bg-yellow-500 text-white' },
  { bg: 'bg-purple-100 dark:bg-purple-900/40', text: 'text-purple-900 dark:text-purple-100', avatar: 'bg-purple-500 text-white' },
  { bg: 'bg-emerald-200 dark:bg-emerald-800/40', text: 'text-emerald-900 dark:text-emerald-100', avatar: 'bg-emerald-700 text-white' },
  { bg: 'bg-violet-200 dark:bg-violet-800/40', text: 'text-violet-900 dark:text-violet-100', avatar: 'bg-violet-700 text-white' },
  { bg: 'bg-amber-200 dark:bg-amber-800/40', text: 'text-amber-900 dark:text-amber-100', avatar: 'bg-amber-700 text-white' },
  { bg: 'bg-rose-200 dark:bg-rose-800/40', text: 'text-rose-900 dark:text-rose-100', avatar: 'bg-rose-700 text-white' },
  { bg: 'bg-cyan-200 dark:bg-cyan-800/40', text: 'text-cyan-900 dark:text-cyan-100', avatar: 'bg-cyan-700 text-white' },
  { bg: 'bg-orange-200 dark:bg-orange-800/40', text: 'text-orange-900 dark:text-orange-100', avatar: 'bg-orange-700 text-white' },
  { bg: 'bg-teal-200 dark:bg-teal-800/40', text: 'text-teal-900 dark:text-teal-100', avatar: 'bg-teal-700 text-white' },
  { bg: 'bg-pink-200 dark:bg-pink-800/40', text: 'text-pink-900 dark:text-pink-100', avatar: 'bg-pink-700 text-white' },
  { bg: 'bg-indigo-200 dark:bg-indigo-800/40', text: 'text-indigo-900 dark:text-indigo-100', avatar: 'bg-indigo-700 text-white' },
  { bg: 'bg-lime-200 dark:bg-lime-800/40', text: 'text-lime-900 dark:text-lime-100', avatar: 'bg-lime-700 text-white' },
  { bg: 'bg-sky-200 dark:bg-sky-800/40', text: 'text-sky-900 dark:text-sky-100', avatar: 'bg-sky-700 text-white' },
  { bg: 'bg-fuchsia-200 dark:bg-fuchsia-800/40', text: 'text-fuchsia-900 dark:text-fuchsia-100', avatar: 'bg-fuchsia-700 text-white' },
  { bg: 'bg-red-200 dark:bg-red-800/40', text: 'text-red-900 dark:text-red-100', avatar: 'bg-red-700 text-white' },
  { bg: 'bg-green-200 dark:bg-green-800/40', text: 'text-green-900 dark:text-green-100', avatar: 'bg-green-700 text-white' },
  { bg: 'bg-yellow-200 dark:bg-yellow-800/40', text: 'text-yellow-900 dark:text-yellow-100', avatar: 'bg-yellow-600 text-white' },
  { bg: 'bg-purple-200 dark:bg-purple-800/40', text: 'text-purple-900 dark:text-purple-100', avatar: 'bg-purple-700 text-white' },
  { bg: 'bg-emerald-50 dark:bg-emerald-950/60', text: 'text-emerald-900 dark:text-emerald-100', avatar: 'bg-emerald-400 text-white' },
  { bg: 'bg-violet-50 dark:bg-violet-950/60', text: 'text-violet-900 dark:text-violet-100', avatar: 'bg-violet-400 text-white' },
  { bg: 'bg-amber-50 dark:bg-amber-950/60', text: 'text-amber-900 dark:text-amber-100', avatar: 'bg-amber-400 text-white' },
  { bg: 'bg-rose-50 dark:bg-rose-950/60', text: 'text-rose-900 dark:text-rose-100', avatar: 'bg-rose-400 text-white' },
  { bg: 'bg-cyan-50 dark:bg-cyan-950/60', text: 'text-cyan-900 dark:text-cyan-100', avatar: 'bg-cyan-400 text-white' },
  { bg: 'bg-orange-50 dark:bg-orange-950/60', text: 'text-orange-900 dark:text-orange-100', avatar: 'bg-orange-400 text-white' },
  { bg: 'bg-teal-50 dark:bg-teal-950/60', text: 'text-teal-900 dark:text-teal-100', avatar: 'bg-teal-400 text-white' },
  { bg: 'bg-pink-50 dark:bg-pink-950/60', text: 'text-pink-900 dark:text-pink-100', avatar: 'bg-pink-400 text-white' },
  { bg: 'bg-indigo-50 dark:bg-indigo-950/60', text: 'text-indigo-900 dark:text-indigo-100', avatar: 'bg-indigo-400 text-white' },
  { bg: 'bg-lime-50 dark:bg-lime-950/60', text: 'text-lime-900 dark:text-lime-100', avatar: 'bg-lime-500 text-white' },
  { bg: 'bg-sky-50 dark:bg-sky-950/60', text: 'text-sky-900 dark:text-sky-100', avatar: 'bg-sky-400 text-white' },
  { bg: 'bg-fuchsia-50 dark:bg-fuchsia-950/60', text: 'text-fuchsia-900 dark:text-fuchsia-100', avatar: 'bg-fuchsia-400 text-white' },
  { bg: 'bg-red-50 dark:bg-red-950/60', text: 'text-red-900 dark:text-red-100', avatar: 'bg-red-400 text-white' },
  { bg: 'bg-green-50 dark:bg-green-950/60', text: 'text-green-900 dark:text-green-100', avatar: 'bg-green-500 text-white' },
  { bg: 'bg-yellow-50 dark:bg-yellow-950/60', text: 'text-yellow-900 dark:text-yellow-100', avatar: 'bg-yellow-400 text-white' },
  { bg: 'bg-purple-50 dark:bg-purple-950/60', text: 'text-purple-900 dark:text-purple-100', avatar: 'bg-purple-400 text-white' },
  { bg: 'bg-teal-300 dark:bg-teal-700/50', text: 'text-teal-900 dark:text-teal-100', avatar: 'bg-teal-600 text-white' },
  { bg: 'bg-sky-300 dark:bg-sky-700/50', text: 'text-sky-900 dark:text-sky-100', avatar: 'bg-sky-600 text-white' },
]
const senderColorCache = new Map()
const colorFor = (userId) => {
  if (!senderColorCache.has(userId)) {
    senderColorCache.set(userId, BUBBLE_PALETTE[senderColorCache.size % BUBBLE_PALETTE.length])
  }
  return senderColorCache.get(userId)
}

// ── Limite emoji : max 3 réactions différentes par utilisateur ────────────────
const isReactedByMe = (message, emoji) =>
  (message.reactions ?? []).some((r) => r.emoji === emoji && r.did_react)
const userReactionCount = (message) =>
  (message.reactions ?? []).filter((r) => r.did_react).length
const canPickEmoji = (message, emoji) =>
  isReactedByMe(message, emoji) || userReactionCount(message) < 3

// ── Son de notification ───────────────────────────────────────────────────────
const teamNotifAudio = new Audio('/sounds/notification.ogg')
const teamInitialLoadDone = ref(false)
let teamPrevMessageCount = 0
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

const resourceTypes = computed(() => [
  { value: 'document', label: t('team_show.type_document'), icon: '📄' },
  { value: 'link', label: t('team_show.type_link'), icon: '🔗' },
  { value: 'template', label: t('team_show.type_template'), icon: '📋' },
  { value: 'tool', label: t('team_show.type_tool'), icon: '🔧' },
])

const eventTypes = computed(() => [
  { value: 'meeting', label: t('team_show.type_meeting'), icon: '👥' },
  { value: 'deadline', label: t('team_show.type_deadline'), icon: '⏰' },
  { value: 'milestone', label: t('team_show.type_milestone'), icon: '🎯' },
  { value: 'task', label: t('team_show.type_task'), icon: '✓' },
  { value: 'reminder', label: t('team_show.type_reminder'), icon: '🔔' },
  { value: 'event', label: t('team_show.type_event'), icon: '📅' },
])

// Member roles
const memberRoles = computed(() => [
  { value: 'admin', label: t('team_show.role_admin'), icon: '👑', description: t('team_show.role_admin_desc') },
  { value: 'moderator', label: t('team_show.role_moderator'), icon: '🛡️', description: t('team_show.role_moderator_desc') },
  { value: 'member', label: t('team_show.role_member'), icon: '👤', description: t('team_show.role_member_desc') },
])

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
  { id: 'chat', label: t('team_show.tab_chat'), icon: ChatIcon, badge: messages.value.length || null },
  { id: 'announcements', label: t('team_show.tab_announcements'), icon: AnnouncementIcon, badge: announcements.value.length || null },
  { id: 'members', label: t('team_show.tab_members'), icon: UsersIcon, badge: team.value?.members_count || null },
  { id: 'resources', label: t('team_show.tab_resources'), icon: FolderIcon, badge: resources.value.length || null },
  { id: 'calendar', label: t('team_show.tab_calendar'), icon: CalendarIcon, badge: events.value.length || null },
  { id: 'activity', label: t('team_show.tab_activity'), icon: ClockIcon },
])

const getInitials = (name) => {
  return name?.split(' ').map(w => w[0]).join('').toUpperCase().substring(0, 2) || '??'
}

const getUserInitials = (user) => {
  return user?.nom ? getInitials(user.nom) : '??'
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

// ── Envoi de message ──────────────────────────────────────────────────────────

const sendMessage = async () => {
  const content = newMessage.value.trim()
  const readyPhotos = pendingPhotos.value.filter((p) => !p._uploading)
  if (!content && !attachmentFile.value && !readyPhotos.length) { return }

  const attachments = readyPhotos.map((p) => ({ url: p.url, name: p.name, type: 'image' }))

  const capturedMentions = [...mentionedUserIds.value]
  const capturedEveryone = mentionEveryone.value

  const data = new FormData()
  data.append('content', content)
  if (replyingTo.value) { data.append('reply_to_id', replyingTo.value.id ?? '') }
  if (attachmentFile.value) { data.append('attachment', attachmentFile.value) }
  if (attachments.length) { data.append('attachments_json', JSON.stringify(attachments)) }
  if (capturedMentions.length) { data.append('mentions', JSON.stringify(capturedMentions)) }
  if (capturedEveryone) { data.append('mention_everyone', '1') }

  newMessage.value = ''
  replyingTo.value = null
  attachmentFile.value = null
  pendingPhotos.value = []
  mentionSuggestions.value = []
  mentionedUserIds.value = []
  mentionEveryone.value = false
  if (attachmentInputRef.value) { attachmentInputRef.value.value = '' }
  if (photoInputRef.value) { photoInputRef.value.value = '' }

  try {
    await sendMessageApi(route.params.uuid, data, attachments)
    await nextTick()
    scrollToBottom()
    markRead(route.params.uuid)
  } catch {
    /* erreur gérée dans le composable */
  }
}

// ── Upload de photos ──────────────────────────────────────────────────────────

const onPhotoSelected = async (e) => {
  const files = Array.from(e.target.files ?? [])
  if (!files.length) { return }
  const workspaceId = team.value?.workspace_id ?? authStore.currentWorkspaceId
  for (const file of files) {
    const localUrl = URL.createObjectURL(file)
    const entry = { url: localUrl, name: file.name, _uploading: true }
    pendingPhotos.value.push(entry)
    const form = new FormData()
    form.append('photo', file)
    try {
      const { data } = await api.post(`/workspaces/${workspaceId}/chat/upload`, form)
      const idx = pendingPhotos.value.indexOf(entry)
      if (idx !== -1) {
        pendingPhotos.value.splice(idx, 1, { url: data.url, name: data.name, type: 'image', _uploading: false })
      }
    } catch {
      const idx = pendingPhotos.value.indexOf(entry)
      if (idx !== -1) { pendingPhotos.value.splice(idx, 1) }
    }
  }
  if (photoInputRef.value) { photoInputRef.value.value = '' }
}

// ── Édition inline ────────────────────────────────────────────────────────────

const startEdit = (message) => {
  editingUuid.value = message.uuid
  editContent.value = message.content
}

const cancelEdit = () => {
  editingUuid.value = null
  editContent.value = ''
}

const confirmEdit = async (uuid) => {
  if (!editContent.value.trim()) return
  try {
    await updateMessageApi(uuid, editContent.value.trim())
  } catch { /* ignore */ }
  cancelEdit()
}

// ── Suppression ───────────────────────────────────────────────────────────────

const confirmDeleteMessage = async (uuid) => {
  if (!confirm($t('team_show.confirm_delete_message'))) return
  try {
    await deleteMessageApi(uuid)
  } catch { /* ignore */ }
}

// ── Réponse ───────────────────────────────────────────────────────────────────

const startReply = (message) => {
  replyingTo.value = message
  newMessage.value = ''
}

// ── Réactions ─────────────────────────────────────────────────────────────────

const toggleReaction = (uuid, emoji, didReact) => {
  const message = messages.value.find((m) => m.uuid === uuid)
  if (!message) { return }
  if (didReact) {
    removeReaction(uuid, emoji)
  } else if (canPickEmoji(message, emoji)) {
    addReaction(uuid, emoji)
  }
}

const pickQuickEmoji = (message, emoji) => {
  if (canPickEmoji(message, emoji)) {
    addReaction(message.uuid, emoji)
  }
}

// ── Pièce jointe ──────────────────────────────────────────────────────────────

const onAttachmentChange = (e) => {
  attachmentFile.value = e.target.files?.[0] ?? null
}

// ── Indicateur de frappe ──────────────────────────────────────────────────────

const onChatKeydown = (e) => {
  if (e.key === 'Escape') { mentionSuggestions.value = [] }
}

const scrollToBottom = () => {
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

// Faire défiler jusqu'à un message spécifique (depuis ?message= dans l'URL)
// et le mettre brièvement en surbrillance pour le repérer visuellement.
const scrollToMessage = (uuid) => {
  const el = document.querySelector(`[data-message-uuid="${uuid}"]`)
  if (!el) return
  el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  el.classList.add('ring-2', 'ring-blue-400', 'ring-offset-1')
  setTimeout(() => el.classList.remove('ring-2', 'ring-blue-400', 'ring-offset-1'), 3000)
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
  try {
    await fetchTeam(route.params.uuid)
    await Promise.all([
      fetchMessages(route.params.uuid),
      fetchAnnouncements(route.params.uuid),
      fetchResources(route.params.uuid),
      fetchActivities(route.params.uuid),
      fetchPresences(route.params.uuid),
      fetchEvents(route.params.uuid),
      fetchUsers(),
    ])

    startPresenceTracking(route.params.uuid)

    // S'abonner au canal Reverb de l'équipe pour le temps réel
    if (team.value?.id) {
      subscribeToTeam(team.value.id)
      markRead(route.params.uuid)
    }

    nextTick(() => {
      teamInitialLoadDone.value = true
      teamPrevMessageCount = messages.value.length
      scrollToBottom()
      if (route.query.message) {
        scrollToMessage(String(route.query.message))
      }
    })
  } catch (error) {
    console.error('Erreur lors du chargement de l\'équipe:', error.response?.data || error.message)
  }
})

// Son sur nouveau message entrant (après chargement initial)
watch(messages, (next) => {
  if (teamInitialLoadDone.value && next.length > teamPrevMessageCount) {
    const newest = next[next.length - 1]
    if (newest && !newest._pending && newest.user?.id !== authStore.currentUser?.id) {
      teamNotifAudio.currentTime = 0
      teamNotifAudio.play().catch(() => {})
    }
  }
  teamPrevMessageCount = next.length
})

onUnmounted(() => {
  stopPresenceTracking(route.params.uuid)
  if (team.value?.id) unsubscribeFromTeam(team.value.id)
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
