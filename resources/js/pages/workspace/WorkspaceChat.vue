<template>
  <AdminLayout>
    <div class="flex flex-col h-[calc(100vh-112px)]">

      <!-- En-tête -->
      <div class="shrink-0 space-y-4 pb-0">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            {{ $t('chat.workspace_chat') }}
          </h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ activeChannel === 'global' ? $t('chat.global_desc') : $t('chat.responsibles_desc') }}
          </p>
        </div>

        <!-- Onglets -->
        <div class="border-b border-gray-200 dark:border-gray-700">
          <nav class="-mb-px flex gap-6">
            <button
              :class="activeChannel === 'global'
                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
              class="inline-flex items-center gap-2 border-b-2 pb-3 text-sm font-medium transition-colors"
              @click="switchChannel('global')"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ $t('chat.global') }}
              <span
                v-if="unreadCounts.global > 0"
                class="ml-1 rounded-full bg-blue-600 text-white text-xs px-1.5 py-0.5 leading-none"
              >
                {{ unreadCounts.global > 99 ? '99+' : unreadCounts.global }}
              </span>
            </button>

            <button
              v-if="canAccessResponsibles"
              :class="activeChannel === 'responsibles'
                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
              class="inline-flex items-center gap-2 border-b-2 pb-3 text-sm font-medium transition-colors"
              @click="switchChannel('responsibles')"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              {{ $t('chat.responsibles') }}
              <span
                v-if="unreadCounts.responsibles > 0"
                class="ml-1 rounded-full bg-amber-500 text-white text-xs px-1.5 py-0.5 leading-none"
              >
                {{ unreadCounts.responsibles > 99 ? '99+' : unreadCounts.responsibles }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Zone de messages -->
      <div
        ref="messagesContainer"
        class="flex-1 overflow-y-auto py-4 flex flex-col gap-1 min-h-0"
      >
        <div v-if="loading" class="flex justify-center py-8">
          <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </div>

        <div
          v-else-if="messages.length === 0"
          class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-600"
        >
          <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <p class="text-sm">{{ $t('chat.no_messages') }}</p>
        </div>

        <div
          v-else
          ref="staggerRef"
          class="flex flex-col gap-3 px-2"
        >
          <div
            v-for="msg in messages"
            :key="msg.uuid"
            class="stagger-item group flex gap-2"
            :class="isOwn(msg) ? 'flex-row-reverse' : 'flex-row'"
          >
            <!-- Avatar -->
            <div class="shrink-0 mt-1">
              <img
                v-if="msg.user?.avatar"
                :src="msg.user.avatar"
                :alt="msg.user.nom"
                class="w-8 h-8 rounded-full object-cover"
              />
              <div
                v-else
                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold"
                :class="isOwn(msg) ? 'bg-blue-600 text-white' : colorFor(msg.user?.id).avatar"
              >
                {{ (msg.user?.nom ?? '?').charAt(0).toUpperCase() }}
              </div>
            </div>

            <!-- Bulle -->
            <div class="flex flex-col max-w-[70%]" :class="isOwn(msg) ? 'items-end' : 'items-start'">
              <!-- Nom + heure -->
              <div
                class="flex items-baseline gap-2 mb-1 px-1"
                :class="isOwn(msg) ? 'flex-row-reverse' : 'flex-row'"
              >
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                  {{ isOwn(msg) ? $t('chat.you') : (msg.user?.nom ?? '—') }}
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(msg.created_at) }}</span>
                <span v-if="msg.is_edited" class="text-xs text-gray-400 dark:text-gray-500 italic">{{ $t('chat.edited') }}</span>
                <span v-if="msg.is_pinned" class="text-xs text-amber-500" :title="$t('chat.pinned')">📌</span>
              </div>

              <!-- Réponse citée -->
              <div
                v-if="msg.reply_to"
                class="mb-1 px-3 py-1.5 rounded-lg text-xs border-l-2 border-gray-300 dark:border-gray-500 bg-gray-100 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 max-w-full"
              >
                <span class="font-medium">{{ msg.reply_to.user_nom }}</span>: {{ msg.reply_to.content_snippet }}
              </div>

              <!-- Texte -->
              <div
                class="px-3 py-2 rounded-2xl text-sm break-words whitespace-pre-wrap leading-relaxed"
                :class="isOwn(msg)
                  ? 'bg-blue-600 text-white rounded-tr-sm'
                  : [colorFor(msg.user?.id).bg, colorFor(msg.user?.id).text, 'rounded-tl-sm']"
                :style="msg._pending ? 'opacity: 0.65' : ''"
              >
                {{ msg.content }}
              </div>

              <!-- Photos jointes -->
              <div
                v-if="msg.attachments?.filter(a => a.type === 'image').length"
                class="mt-1.5 flex flex-wrap gap-1.5"
                :class="isOwn(msg) ? 'justify-end' : 'justify-start'"
              >
                <a
                  v-for="att in msg.attachments.filter(a => a.type === 'image')"
                  :key="att.url"
                  :href="att.url"
                  target="_blank"
                  rel="noopener"
                  class="block"
                >
                  <img
                    :src="att.url"
                    :alt="att.name ?? 'photo'"
                    class="max-h-48 max-w-xs rounded-xl object-cover border border-gray-200 dark:border-gray-600 hover:opacity-90 transition-opacity cursor-zoom-in"
                  />
                </a>
              </div>

              <!-- Réactions -->
              <div v-if="msg.reactions?.length" class="flex flex-wrap gap-1 mt-1.5 px-1">
                <button
                  v-for="reaction in msg.reactions"
                  :key="reaction.emoji"
                  class="inline-flex items-center gap-1 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 hover:border-blue-400 rounded-full px-2 py-0.5 transition-colors shadow-sm"
                  :class="reaction.did_react ? 'border-blue-400 ring-1 ring-blue-300' : ''"
                  @click="toggleReaction(msg, reaction.emoji)"
                >
                  {{ reaction.emoji }} {{ reaction.count }}
                </button>
              </div>

              <!-- Actions (au survol, ou quand le picker est ouvert) -->
              <div
                class="mt-1 px-1 flex items-center gap-2 transition-opacity"
                :class="[isOwn(msg) ? 'flex-row-reverse' : 'flex-row', emojiPickerOpen === msg.uuid ? 'opacity-100' : 'opacity-0 group-hover:opacity-100']"
              >
                <!-- Sélecteur d'emoji -->
                <div class="relative" @click.stop>
                  <button
                    class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    :title="userReactionCount(msg) >= 3 ? $t('chat.reaction_limit') : ''"
                    @click="toggleEmojiPicker(msg.uuid)"
                  >
                    😊
                  </button>
                  <div
                    v-if="emojiPickerOpen === msg.uuid"
                    class="absolute z-10 flex items-center gap-0.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full shadow-lg px-2 py-1"
                    :class="isOwn(msg) ? 'right-0 bottom-6' : 'left-0 bottom-6'"
                  >
                    <button
                      v-for="emoji in quickEmojis"
                      :key="emoji"
                      class="text-base transition-transform rounded-full w-7 h-7 flex items-center justify-center"
                      :class="[
                        isReactedByMe(msg, emoji) ? 'bg-blue-100 dark:bg-blue-900/40 ring-1 ring-blue-400' : '',
                        canPickEmoji(msg, emoji) ? 'hover:scale-125' : 'opacity-30 cursor-not-allowed',
                      ]"
                      :disabled="!canPickEmoji(msg, emoji)"
                      @click="canPickEmoji(msg, emoji) && pickEmoji(msg, emoji)"
                    >{{ emoji }}</button>
                    <span class="text-xs text-gray-400 dark:text-gray-500 pl-1 pr-0.5 whitespace-nowrap">{{ userReactionCount(msg) }}/3</span>
                  </div>
                </div>
                <button
                  v-if="isOwn(msg)"
                  class="text-xs text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                  @click="startEdit(msg)"
                >{{ $t('common.edit') }}</button>
                <button
                  v-if="isOwn(msg) || canPin"
                  class="text-xs text-gray-400 hover:text-red-500 transition-colors"
                  @click="confirmDelete(msg)"
                >{{ $t('common.delete') }}</button>
                <button
                  v-if="canPin"
                  class="text-xs text-gray-400 hover:text-amber-500 transition-colors"
                  @click="togglePin(msg)"
                >{{ msg.is_pinned ? '📌 ' + $t('chat.unpin') : '📌 ' + $t('chat.pin') }}</button>
                <button
                  class="text-xs text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                  @click="replyTo = msg"
                >{{ $t('chat.reply') }}</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Indicateur de frappe -->
        <p
          v-if="typingUsers.length"
          class="text-xs text-gray-500 dark:text-gray-400 italic px-2 mt-1"
        >
          {{ typingUsers.map((u) => u.nom).join(', ') }} {{ $t('chat.typing') }}…
        </p>
      </div>

      <!-- Bande de réponse -->
      <div
        v-if="replyTo"
        class="shrink-0 px-3 py-2 bg-gray-50 dark:bg-gray-800 border-l-4 border-blue-400 rounded text-xs text-gray-600 dark:text-gray-400 flex items-center justify-between"
      >
        <span>
          <strong>{{ replyTo.user?.nom }}</strong>: {{ replyTo.content.slice(0, 80) }}
        </span>
        <button class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="replyTo = null">✕</button>
      </div>

      <!-- Bande d'édition -->
      <div
        v-if="editingMsg"
        class="shrink-0 px-3 py-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded text-xs text-yellow-700 dark:text-yellow-300 flex items-center justify-between"
      >
        <span>{{ $t('chat.editing_message') }}</span>
        <button class="ml-2 text-yellow-500 hover:text-yellow-700" @click="cancelEdit">✕</button>
      </div>

      <!-- Compositeur -->
      <form
        class="shrink-0 pt-2 pb-1"
        @submit.prevent="submitMessage"
      >
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

        <!-- Aperçu des photos en attente d'envoi -->
        <div v-if="pendingPhotos.length" class="flex flex-wrap gap-2 mb-2 px-1">
          <div
            v-for="(photo, idx) in pendingPhotos"
            :key="photo.url"
            class="relative group/photo"
          >
            <img :src="photo.url" class="h-20 w-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
            <button
              type="button"
              class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover/photo:opacity-100 transition-opacity"
              @click="pendingPhotos.splice(idx, 1)"
            >✕</button>
            <div v-if="photo._uploading" class="absolute inset-0 bg-black/40 rounded-lg flex items-center justify-center">
              <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
            </div>
          </div>
        </div>

        <div class="flex gap-2 items-end bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2">
          <!-- Bouton photo -->
          <input
            ref="photoInputRef"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif"
            multiple
            class="hidden"
            @change="onPhotoSelected"
          />
          <button
            type="button"
            class="shrink-0 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors p-1"
            :title="$t('chat.attach_photo')"
            @click="photoInputRef.click()"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </button>
          <textarea
            ref="composerRef"
            v-model="draft"
            rows="1"
            :placeholder="$t('chat.type_message')"
            class="flex-1 resize-none bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none leading-5 max-h-36 overflow-y-auto"
            @keydown.enter.exact.prevent="submitMessage"
            @keydown.enter.shift.exact="draft += '\n'"
            @keydown.escape="mentionSuggestions = []"
            @input="onTyping"
          />
          <button
            type="submit"
            :disabled="(!draft.trim() && !pendingPhotos.length) || sending || pendingPhotos.some(p => p._uploading)"
            class="shrink-0 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white p-2 transition-colors"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
            </svg>
          </button>
        </div>
      </form>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/authStore'
import { useWorkspaceMessages } from '@/composables/useWorkspaceMessages'
import { useStagger } from '@/composables/useAnimations'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import api from '@/api/axios'

const { t } = useI18n()
const authStore = useAuthStore()

const {
  messages,
  loading,
  typingUsers,
  subscribeToChannel,
  unsubscribeFromChannel,
  fetchMessages,
  sendMessage,
  updateMessage,
  deleteMessage,
  pinMessage,
  unpinMessage,
  addReaction,
  removeReaction,
  sendTyping,
  markRead,
  fetchUnreadCounts,
} = useWorkspaceMessages()

const { staggerRef, applyStagger } = useStagger(50)

// ── État ─────────────────────────────────────────────────────────────────────

const activeChannel = ref('global')
const draft = ref('')
const replyTo = ref(null)
const editingMsg = ref(null)
const sending = ref(false)
const messagesContainer = ref(null)
const composerRef = ref(null)
const photoInputRef = ref(null)
const pendingPhotos = ref([])
const emojiPickerOpen = ref(null)

const quickEmojis = ['👍', '❤️', '😂', '😮', '😢', '🎉', '🙏', '🔥', '✅', '👀']

const isOwn = (msg) => msg.user?.id === authStore.currentUser?.id

// Palette de couleurs pour les autres expéditeurs — déterministe par user.id
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

const isReactedByMe = (msg, emoji) =>
  (msg.reactions ?? []).some((r) => r.emoji === emoji && r.did_react)

const userReactionCount = (msg) =>
  (msg.reactions ?? []).filter((r) => r.did_react).length

const canPickEmoji = (msg, emoji) => {
  // Toujours autoriser le toggle d'une réaction déjà posée
  if (isReactedByMe(msg, emoji)) return true
  return userReactionCount(msg) < 3
}

const toggleEmojiPicker = (uuid) => {
  emojiPickerOpen.value = emojiPickerOpen.value === uuid ? null : uuid
}

const pickEmoji = (msg, emoji) => {
  emojiPickerOpen.value = null
  toggleReaction(msg, emoji)
}

const closeEmojiPicker = () => {
  emojiPickerOpen.value = null
}

/** @type {{ responsibles: number, global: number, total: number }} */
const unreadCounts = ref({ responsibles: 0, global: 0, total: 0 })

const workspaceId = computed(() => authStore.currentWorkspaceId)

const userRole = computed(() => authStore.getWorkspaceRole(workspaceId.value) ?? '')
const canAccessResponsibles = computed(() =>
  ['owner', 'manager', 'cadre'].includes(userRole.value),
)
const canPin = computed(() =>
  ['owner', 'manager'].includes(userRole.value),
)

// ── Chargement ────────────────────────────────────────────────────────────────

const initialLoadDone = ref(false)

const loadChannel = async (channelType) => {
  if (!workspaceId.value) return
  initialLoadDone.value = false
  prevMessageCount = 0
  await fetchMessages(workspaceId.value, channelType)
  initialLoadDone.value = true
  await nextTick()
  applyStagger()
  scrollToBottom()
  markRead(workspaceId.value, channelType)
}

const loadUnread = async () => {
  if (!workspaceId.value) return
  unreadCounts.value = await fetchUnreadCounts(workspaceId.value)
}

const switchChannel = async (channelType) => {
  if (channelType === activeChannel.value) return
  unsubscribeFromChannel(workspaceId.value, activeChannel.value)
  activeChannel.value = channelType
  messages.value = []
  await loadChannel(channelType)
  subscribeToChannel(workspaceId.value, channelType)
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

// ── Envoi ─────────────────────────────────────────────────────────────────────

const submitMessage = async () => {
  const content = draft.value.trim()
  const hasPhotos = pendingPhotos.value.some((p) => !p._uploading)
  if ((!content && !hasPhotos) || sending.value) return

  if (editingMsg.value) {
    await updateMessage(workspaceId.value, editingMsg.value.uuid, content)
    cancelEdit()
    return
  }

  sending.value = true
  const capturedMentions = [...mentionedUserIds.value]
  const capturedEveryone = mentionEveryone.value
  const capturedPhotos = pendingPhotos.value.filter((p) => !p._uploading).map(({ url, name, type }) => ({ url, name, type }))
  draft.value = ''
  mentionSuggestions.value = []
  mentionedUserIds.value = []
  mentionEveryone.value = false
  pendingPhotos.value = []
  try {
    const payload = {
      content,
      reply_to_id: replyTo.value?.id ?? null,
      mentions: capturedMentions,
      mention_everyone: capturedEveryone,
      attachments: capturedPhotos,
    }
    await sendMessage(workspaceId.value, activeChannel.value, payload)
    replyTo.value = null
  } finally {
    sending.value = false
    nextTick(() => composerRef.value?.focus())
  }
}

const startEdit = (msg) => {
  editingMsg.value = msg
  draft.value = msg.content
  nextTick(() => composerRef.value?.focus())
}

const cancelEdit = () => {
  editingMsg.value = null
  draft.value = ''
  mentionedUserIds.value = []
  mentionEveryone.value = false
  mentionSuggestions.value = []
  pendingPhotos.value = []
}

const confirmDelete = async (msg) => {
  if (!confirm(t('chat.confirm_delete'))) return
  await deleteMessage(workspaceId.value, msg.uuid)
}

const togglePin = async (msg) => {
  if (msg.is_pinned) {
    await unpinMessage(workspaceId.value, msg.uuid)
  } else {
    await pinMessage(workspaceId.value, msg.uuid)
  }
}

const toggleReaction = async (msg, emoji) => {
  const already = msg.reactions?.find((r) => r.emoji === emoji)?.did_react
  if (already) {
    await removeReaction(workspaceId.value, msg.uuid, emoji)
  } else {
    await addReaction(workspaceId.value, msg.uuid, emoji)
  }
}

// ── Photos ────────────────────────────────────────────────────────────────────

const onPhotoSelected = async (event) => {
  const files = Array.from(event.target.files ?? [])
  if (!files.length) return
  // Réinitialiser l'input pour permettre la resélection du même fichier
  event.target.value = ''

  for (const file of files) {
    const localUrl = URL.createObjectURL(file)
    const entry = { url: localUrl, name: file.name, type: 'image', _uploading: true, _local: true }
    pendingPhotos.value.push(entry)

    try {
      const form = new FormData()
      form.append('photo', file)
      const { data } = await api.post(`/workspaces/${workspaceId.value}/chat/upload`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      // Remplacer l'URL locale par l'URL serveur
      const idx = pendingPhotos.value.indexOf(entry)
      if (idx !== -1) {
        pendingPhotos.value[idx] = { url: data.url, name: data.name, type: 'image', _uploading: false }
      }
    } catch {
      // Retirer l'entrée si l'upload échoue
      pendingPhotos.value = pendingPhotos.value.filter((p) => p !== entry)
    }
    URL.revokeObjectURL(localUrl)
  }
}

// ── @mention ──────────────────────────────────────────────────────────────────

const mentionSuggestions = ref([])
const mentionedUserIds = ref([])
const mentionEveryone = ref(false)
let mentionSearchTimer = null

// Entrée spéciale @everyone affichée dans les suggestions
const EVERYONE_ENTRY = { id: '__everyone__', nom: 'everyone', _isEveryone: true }

const onTyping = () => {
  sendTyping(workspaceId.value, activeChannel.value, authStore.user)

  // Détecter @token en cours de saisie
  const textarea = composerRef.value
  if (!textarea) return
  const pos = textarea.selectionStart
  const before = draft.value.slice(0, pos)
  const match = before.match(/@(\w*)$/)

  if (!match) {
    mentionSuggestions.value = []
    return
  }

  const query = match[1].toLowerCase()
  clearTimeout(mentionSearchTimer)

  // @everyone — afficher l'entrée spéciale immédiatement
  if ('everyone'.startsWith(query) && query.length >= 1) {
    mentionSuggestions.value = [EVERYONE_ENTRY]
    return
  }

  if (query.length < 2) {
    mentionSuggestions.value = []
    return
  }

  mentionSearchTimer = setTimeout(async () => {
    try {
      const { data } = await api.get(`/workspaces/${workspaceId.value}/members/search`, {
        params: { q: query },
      })
      mentionSuggestions.value = data.data ?? data ?? []
    } catch {
      mentionSuggestions.value = []
    }
  }, 200)
}

const insertMention = (member) => {
  const textarea = composerRef.value
  if (!textarea) return
  const pos = textarea.selectionStart
  const before = draft.value.slice(0, pos)
  const after = draft.value.slice(pos)

  if (member._isEveryone) {
    const replaced = before.replace(/@(\w*)$/, '@everyone ')
    draft.value = replaced + after
    mentionEveryone.value = true
    mentionSuggestions.value = []
    nextTick(() => {
      textarea.selectionStart = textarea.selectionEnd = replaced.length
      textarea.focus()
    })
    return
  }

  // Remplacer le @token en cours par @Nom suivi d'un espace
  const replaced = before.replace(/@(\w*)$/, `@${member.nom} `)
  draft.value = replaced + after
  mentionSuggestions.value = []
  if (!mentionedUserIds.value.includes(member.id)) {
    mentionedUserIds.value.push(member.id)
  }
  nextTick(() => {
    textarea.selectionStart = textarea.selectionEnd = replaced.length
    textarea.focus()
  })
}


const formatDate = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  const now = new Date()
  const sameDay = d.toDateString() === now.toDateString()
  return sameDay
    ? d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    : d.toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// ── Cycle de vie ──────────────────────────────────────────────────────────────

let initialized = false

const init = async (wsId) => {
  if (!wsId || initialized) return
  initialized = true
  await loadChannel(activeChannel.value)
  subscribeToChannel(wsId, activeChannel.value)
  loadUnread()
}

onMounted(() => {
  if (workspaceId.value) {
    init(workspaceId.value)
  }
  document.addEventListener('click', closeEmojiPicker)
})

watch(workspaceId, (wsId) => {
  if (wsId) init(wsId)
})

onUnmounted(() => {
  initialized = false
  unsubscribeFromChannel(workspaceId.value, activeChannel.value)
  document.removeEventListener('click', closeEmojiPicker)
})

const notifAudio = new Audio('/sounds/notification.ogg')

let prevMessageCount = 0
watch(messages, (next) => {
  nextTick(scrollToBottom)
  if (initialLoadDone.value && next.length > prevMessageCount) {
    const newest = next[next.length - 1]
    if (newest && !newest._pending && newest.user?.id !== authStore.currentUser?.id) {
      notifAudio.currentTime = 0
      notifAudio.play().catch(() => {})
    }
  }
  prevMessageCount = next.length
})
</script>
