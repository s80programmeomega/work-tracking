<!-- Panneau latéral glissant pour afficher le détail d'une entrée de log (activité ou audit). -->
<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex" @keydown.esc="close">
        <!-- Fond semi-transparent -->
        <div class="flex-1 bg-black/40" @click="close" />

        <!-- Panneau -->
        <div
          class="flex h-full w-full max-w-lg flex-col overflow-hidden bg-white shadow-2xl dark:bg-gray-900"
          role="dialog"
          :aria-label="$t('admin_logs.drawer_title')"
        >
          <!-- En-tête -->
          <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <span :class="badgeClass" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold">
                {{ badgeLabel }}
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">{{ formattedDate }}</span>
            </div>
            <button
              @click="close"
              class="rounded-full p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <!-- Corps scrollable -->
          <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">

            <!-- Auteur -->
            <div v-if="actor">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                {{ $t('admin_logs.col_causer') }}
              </p>
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ actor.nom ?? actor.name ?? '—' }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ actor.email }}</p>
            </div>

            <!-- Description lisible (activité uniquement) -->
            <div v-if="type === 'activity' && entry.human_readable">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                {{ $t('admin_logs.col_description') }}
              </p>
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ entry.human_readable }}</p>
            </div>

            <!-- Sujet (activité uniquement) -->
            <div v-if="type === 'activity' && entry.subject">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                {{ $t('admin_logs.col_subject') }}
              </p>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ subjectLabel(entry.subject_type) }}
                <span v-if="entry.subject_id" class="text-gray-400">#{{ entry.subject_id }}</span>
              </p>
            </div>

            <!-- Tâche liée (audit uniquement) -->
            <div v-if="type === 'audit' && entry.resultat">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                {{ $t('admin_logs.col_task') }}
              </p>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ entry.resultat.tache_titre ?? '—' }}
                <span class="text-gray-400">(résultat #{{ entry.resultat.id }})</span>
              </p>
            </div>

            <!-- Diff des propriétés (activité) -->
            <div v-if="type === 'activity' && hasDiff">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                {{ $t('admin_logs.prop_old') }} → {{ $t('admin_logs.prop_new') }}
              </p>
              <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-xs">
                  <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                      <th class="px-3 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">Champ</th>
                      <th class="px-3 py-2 text-left font-semibold text-red-500">{{ $t('admin_logs.prop_old') }}</th>
                      <th class="px-3 py-2 text-left font-semibold text-green-500">{{ $t('admin_logs.prop_new') }}</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="(newVal, key) in diffAttributes" :key="key">
                      <td class="px-3 py-2 font-mono text-gray-500 dark:text-gray-400">{{ key }}</td>
                      <td class="px-3 py-2 text-red-600 dark:text-red-400">
                        {{ stringify(diffOld[key]) }}
                      </td>
                      <td class="px-3 py-2 text-green-700 dark:text-green-400">
                        {{ stringify(newVal) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Contexte JSON brut (audit) -->
            <div v-if="type === 'audit' && entry.context && Object.keys(entry.context).length">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                Contexte
              </p>
              <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-xs">
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="(val, key) in entry.context" :key="key">
                      <td class="px-3 py-2 font-mono font-medium text-gray-500 dark:text-gray-400 w-1/3">{{ key }}</td>
                      <td class="px-3 py-2 text-gray-700 dark:text-gray-300 break-all">{{ stringify(val) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Propriétés brutes (activité — cas sans diff structuré) -->
            <div v-if="type === 'activity' && !hasDiff && entry.properties && Object.keys(entry.properties).length">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                {{ $t('admin_logs.prop_new') }}
              </p>
              <pre class="overflow-x-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ JSON.stringify(entry.properties, null, 2) }}</pre>
            </div>

            <!-- Aucune donnée -->
            <p
              v-if="type === 'activity' && !hasDiff && (!entry.properties || !Object.keys(entry.properties).length)"
              class="text-sm text-gray-400 dark:text-gray-500"
            >
              {{ $t('admin_logs.no_changes') }}
            </p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  entry: { type: Object, default: null },
  /** 'activity' | 'audit' | 'app_log' */
  type: { type: String, default: 'activity' },
})

const emit = defineEmits(['update:modelValue'])

const close = () => emit('update:modelValue', false)

// ── Auteur ────────────────────────────────────────────────────────────────────

const actor = computed(() => {
  if (!props.entry) return null
  if (props.type === 'activity') return props.entry.causer ?? null
  if (props.type === 'audit') return props.entry.actor ?? null
  return null
})

// ── Badge ─────────────────────────────────────────────────────────────────────

const badgeLabel = computed(() => {
  if (!props.entry) return ''
  if (props.type === 'activity') return props.entry.event ?? props.entry.description ?? ''
  if (props.type === 'audit') return props.entry.action ?? ''
  return ''
})

const badgeClass = computed(() => {
  const label = badgeLabel.value.toLowerCase()
  if (['created', 'approuve', 'n1_valide', 'n2_valide'].some((k) => label.includes(k))) {
    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  }
  if (['deleted', 'renvoye', 'n1_rejete', 'n2_rejete'].some((k) => label.includes(k))) {
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  }
  if (label.includes('bypass')) {
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
  }
  if (['updated', 'login'].some((k) => label.includes(k))) {
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
})

// ── Date ──────────────────────────────────────────────────────────────────────

const formattedDate = computed(() => {
  if (!props.entry?.created_at) return ''
  return new Date(props.entry.created_at).toLocaleDateString(undefined, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
})

// ── Diff propriétés ───────────────────────────────────────────────────────────

const diffAttributes = computed(() => props.entry?.properties?.attributes ?? {})
const diffOld = computed(() => props.entry?.properties?.old ?? {})
const hasDiff = computed(() => Object.keys(diffAttributes.value).length > 0)

// ── Helpers ───────────────────────────────────────────────────────────────────

const stringify = (val) => {
  if (val === null || val === undefined) return '—'
  if (typeof val === 'object') return JSON.stringify(val)
  return String(val)
}

const subjectLabel = (type) => {
  if (!type) return '—'
  return type.replace(/^App\\Models\\/, '').replace(/\\/g, '')
}
</script>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 0.2s ease;
}
.drawer-enter-active > div:last-child,
.drawer-leave-active > div:last-child {
  transition: transform 0.25s ease;
}
.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}
.drawer-enter-from > div:last-child,
.drawer-leave-to > div:last-child {
  transform: translateX(100%);
}
</style>
