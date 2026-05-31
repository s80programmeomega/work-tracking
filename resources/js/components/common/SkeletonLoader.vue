<template>
  <div :class="wrapperClass" aria-busy="true" aria-label="Chargement…">
    <!-- Table skeleton -->
    <template v-if="type === 'table'">
      <div class="space-y-3">
        <div
          v-for="row in rows"
          :key="row"
          class="flex gap-4"
        >
          <div
            v-for="col in cols"
            :key="col"
            class="skeleton h-5"
            :style="{ flex: colWidths[col - 1] ?? 1 }"
          />
        </div>
      </div>
    </template>

    <!-- Card grid skeleton -->
    <template v-else-if="type === 'cards'">
      <div :class="`grid gap-4 ${gridClass}`">
        <div
          v-for="n in rows"
          :key="n"
          class="skeleton rounded-3"
          :style="{ height: `${cardHeight}px` }"
        />
      </div>
    </template>

    <!-- Stat cards skeleton -->
    <template v-else-if="type === 'stats'">
      <div :class="`grid gap-4 ${gridClass}`">
        <div
          v-for="n in cols"
          :key="n"
          class="skeleton rounded-3 h-24"
        />
      </div>
    </template>

    <!-- Single block skeleton (default) -->
    <template v-else>
      <div
        v-for="n in rows"
        :key="n"
        class="skeleton mb-3"
        :style="{ height: `${lineHeight}px`, width: lineWidths[n - 1] ?? '100%' }"
      />
    </template>
  </div>
</template>

<script setup>
defineProps({
  /** 'table' | 'cards' | 'stats' | 'lines' */
  type: {
    type: String,
    default: 'lines',
  },
  /** Nombre de lignes / cartes */
  rows: {
    type: Number,
    default: 4,
  },
  /** Nombre de colonnes (table ou stats) */
  cols: {
    type: Number,
    default: 4,
  },
  /** Flex widths des colonnes table, ex: [2, 1, 1, 1] */
  colWidths: {
    type: Array,
    default: () => [],
  },
  /** Classe de grille Tailwind pour cards/stats */
  gridClass: {
    type: String,
    default: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
  },
  /** Hauteur des cards en px */
  cardHeight: {
    type: Number,
    default: 160,
  },
  /** Hauteur des lignes en px (type lines) */
  lineHeight: {
    type: Number,
    default: 16,
  },
  /** Largeurs variables pour les lignes, ex: ['100%','75%','90%'] */
  lineWidths: {
    type: Array,
    default: () => ['100%', '80%', '60%', '90%'],
  },
  wrapperClass: {
    type: String,
    default: '',
  },
})
</script>
