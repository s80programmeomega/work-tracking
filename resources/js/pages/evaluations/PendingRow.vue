<template>
  <div
    :dusk="`pending-row-${row.id}`"
    class="bg-white dark:bg-gray-800 border rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 cursor-pointer hover:shadow"
    :class="row.is_urgent
      ? 'border-red-300 dark:border-red-700'
      : 'border-gray-200 dark:border-gray-700'"
    @click="$emit('open', row)"
  >
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 flex-wrap mb-1">
        <span class="text-xs px-2 py-0.5 rounded uppercase font-semibold"
          :class="level === 'n1' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
          {{ level }}
        </span>
        <span v-if="row.is_urgent" dusk="urgent-badge" class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-800 font-semibold">
          ⚠ Urgent (&lt; 24h)
        </span>
        <span v-if="row.had_bypass" dusk="bypass-badge" class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">
          ⚡ Bypass
        </span>
        <span v-if="row.escalades_abusives" dusk="escalades-badge" class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-800 font-semibold">
          🚨 Escalades abusives
        </span>
      </div>
      <p class="font-medium text-gray-900 dark:text-white truncate">
        {{ row.tache.titre }}
      </p>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
        {{ row.tache.projet }} → {{ row.tache.activite }} · Auteur : {{ row.assignee.nom }}
      </p>
    </div>
    <div class="text-right text-xs whitespace-nowrap">
      <p class="text-gray-500 dark:text-gray-400">Échéance</p>
      <p class="font-semibold"
         :class="row.is_urgent ? 'text-red-600' : 'text-gray-700 dark:text-gray-300'">
        {{ formatRemaining(row.hours_remaining) }}
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  row: { type: Object, required: true },
  level: { type: String, default: 'n1' },
})

defineEmits(['open'])

const formatRemaining = (h) => {
  if (h === null || h === undefined) return '—'
  if (h < 0) return `Dépassé ${Math.abs(Math.round(h))}h`
  if (h < 24) return `${Math.round(h)}h restantes`
  const days = Math.floor(h / 24)
  return `${days}j ${Math.round(h % 24)}h restantes`
}
</script>
