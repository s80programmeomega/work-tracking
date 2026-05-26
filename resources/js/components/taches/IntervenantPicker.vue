<template>
  <div class="relative" dusk="intervenant-picker">
    <!-- Search input -->
    <div class="relative">
      <input
        v-model="query"
        type="text"
        :placeholder="placeholder"
        dusk="intervenant-picker-search"
        class="w-full px-4 py-3 pl-10 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
        @focus="open = true"
        @blur="onBlur"
      />
      <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
    </div>

    <!-- Dropdown -->
    <div
      v-if="open && filtered.length > 0"
      class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-56 overflow-y-auto"
      dusk="intervenant-picker-dropdown"
    >
      <button
        v-for="member in filtered"
        :key="member.id"
        type="button"
        class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 text-left transition-colors"
        :class="isSelected(member.id) ? 'bg-brand-50 dark:bg-brand-900/20' : ''"
        @mousedown.prevent="toggle(member)"
      >
        <div class="w-7 h-7 rounded-full bg-brand-100 dark:bg-brand-900 flex items-center justify-center shrink-0">
          <span class="text-brand-600 dark:text-brand-300 text-xs font-medium">
            {{ initials(member) }}
          </span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ member.nom }} {{ member.prenom }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ member.email }}</p>
        </div>
        <i v-if="isSelected(member.id)" class="fas fa-check text-brand-500 text-xs"></i>
      </button>
    </div>

    <!-- No results -->
    <div
      v-if="open && query && filtered.length === 0"
      class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg px-4 py-3 text-sm text-gray-500 dark:text-gray-400"
    >
      {{ noResultsLabel }}
    </div>

    <!-- Selected chips -->
    <div v-if="selected.length > 0" class="flex flex-wrap gap-2 mt-3" dusk="intervenant-picker-chips">
      <span
        v-for="member in selected"
        :key="member.id"
        class="inline-flex items-center gap-1.5 px-3 py-1 bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 rounded-full text-sm"
      >
        <span class="font-medium">{{ member.nom }}</span>
        <button
          v-if="member.id !== lockedId"
          type="button"
          class="hover:text-brand-900 dark:hover:text-brand-100"
          @click="remove(member.id)"
        >
          <i class="fas fa-times text-xs"></i>
        </button>
        <i v-else class="fas fa-crown text-xs text-yellow-500" title="Responsable"></i>
      </span>
    </div>

    <p v-if="selected.length > 0" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
      {{ selected.length }} intervenant(s) sélectionné(s)
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue:    { type: Array,  default: () => [] },   // array of user ids
  members:       { type: Array,  default: () => [] },   // available members [{id, nom, prenom, email}]
  lockedId:      { type: Number, default: null },       // responsable id — cannot be removed
  placeholder:   { type: String, default: 'Rechercher un membre…' },
  noResultsLabel: { type: String, default: 'Aucun membre trouvé.' },
})

const emit = defineEmits(['update:modelValue'])

const query = ref('')
const open  = ref(false)

const filtered = computed(() => {
  const q = query.value.toLowerCase().trim()
  if (!q) {
    return props.members
  }
  return props.members.filter(m =>
    `${m.nom} ${m.prenom} ${m.email}`.toLowerCase().includes(q)
  )
})

const selected = computed(() =>
  props.members.filter(m => props.modelValue.includes(m.id))
)

function isSelected(id) {
  return props.modelValue.includes(id)
}

function toggle(member) {
  if (isSelected(member.id)) {
    if (member.id === props.lockedId) {
      return
    }
    remove(member.id)
  } else {
    emit('update:modelValue', [...props.modelValue, member.id])
  }
}

function remove(id) {
  if (id === props.lockedId) {
    return
  }
  emit('update:modelValue', props.modelValue.filter(i => i !== id))
}

function onBlur() {
  setTimeout(() => { open.value = false }, 150)
}

function initials(member) {
  return `${(member.prenom?.[0] ?? '')}${(member.nom?.[0] ?? '')}`.toUpperCase()
}
</script>
