<!-- resources/js/components/taches/IndicatorCard.vue -->
<template>
  <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium uppercase tracking-wider" :class="textColorClass">
          {{ title }}
        </p>
        <div class="flex items-baseline gap-1 mt-1">
          <p class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ value }}
          </p>
          <span v-if="suffix" class="text-sm text-gray-500 dark:text-gray-400">{{ suffix }}</span>
        </div>
      </div>
      <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="iconBgColorClass">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="icon === 'clipboard-list'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          <path v-if="icon === 'check-circle'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          <path v-if="icon === 'trending-up'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          <path v-if="icon === 'badge-check'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          <path v-if="icon === 'clock'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          <path v-if="icon === 'exclamation'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  value: [String, Number],
  suffix: String,
  icon: String,
  color: {
    type: String,
    default: 'blue'
  }
})

const colorClasses = {
  blue: { text: 'text-blue-600', bg: 'bg-blue-500' },
  green: { text: 'text-green-600', bg: 'bg-green-500' },
  purple: { text: 'text-purple-600', bg: 'bg-purple-500' },
  emerald: { text: 'text-emerald-600', bg: 'bg-emerald-500' },
  orange: { text: 'text-orange-600', bg: 'bg-orange-500' },
  red: { text: 'text-red-600', bg: 'bg-red-500' }
}

const textColorClass = computed(() => colorClasses[props.color]?.text || 'text-blue-600')
const iconBgColorClass = computed(() => colorClasses[props.color]?.bg || 'bg-blue-500')
</script>