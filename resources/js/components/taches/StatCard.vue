<!-- resources/js/components/common/StatCard.vue -->
<template>
  <div 
    class="relative overflow-hidden rounded-xl p-4 border transition-all duration-300 hover:shadow-lg group"
    :class="[
      alert ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'
    ]"
  >
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ title }}</p>
        <p 
          class="text-2xl font-bold mt-1"
          :class="getValueColor"
        >
          {{ value }}
        </p>
      </div>
      
      <div 
        class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110"
        :class="getIconBgClass"
      >
        <!-- Clipboard List -->
        <svg v-if="icon === 'clipboard-list'" class="w-6 h-6" :class="getIconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        
        <!-- Clock -->
        <svg v-else-if="icon === 'clock'" class="w-6 h-6" :class="getIconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        
        <!-- Play -->
        <svg v-else-if="icon === 'play'" class="w-6 h-6" :class="getIconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        
        <!-- Check Circle -->
        <svg v-else-if="icon === 'check-circle'" class="w-6 h-6" :class="getIconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        
        <!-- Exclamation -->
        <svg v-else-if="icon === 'exclamation'" class="w-6 h-6" :class="[getIconColor, alert ? 'animate-pulse' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        
        <!-- Chart Pie -->
        <svg v-else-if="icon === 'chart-pie'" class="w-6 h-6" :class="getIconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
        </svg>
        
        <!-- Default -->
        <svg v-else class="w-6 h-6" :class="getIconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
      </div>
    </div>
    
    <!-- Progress bar optionnelle -->
    <div v-if="progress !== undefined" class="mt-3">
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
        <div 
          class="h-2 rounded-full transition-all duration-500"
          :class="getProgressBarColor"
          :style="{ width: `${Math.min(progress, 100)}%` }"
        ></div>
      </div>
    </div>
    
    <!-- Barre de couleur en bas -->
    <div 
      class="absolute bottom-0 left-0 right-0 h-1"
      :class="getBottomBarClass"
    ></div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  icon: {
    type: String,
    default: 'clipboard-list'
  },
  color: {
    type: String,
    default: 'gray',
    validator: (v) => ['gray', 'slate', 'blue', 'green', 'red', 'brand', 'amber', 'purple'].includes(v)
  },
  alert: {
    type: Boolean,
    default: false
  },
  progress: {
    type: Number,
    default: undefined
  }
})

const colorClasses = {
  gray: {
    icon: 'text-gray-500',
    iconBg: 'bg-gray-100 dark:bg-gray-700',
    value: 'text-gray-900 dark:text-white',
    bar: 'bg-gradient-to-r from-gray-400 to-gray-500',
    progress: 'bg-gray-500'
  },
  slate: {
    icon: 'text-slate-500',
    iconBg: 'bg-slate-100 dark:bg-slate-900/30',
    value: 'text-slate-600 dark:text-slate-400',
    bar: 'bg-gradient-to-r from-slate-400 to-slate-500',
    progress: 'bg-slate-500'
  },
  blue: {
    icon: 'text-blue-500',
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    value: 'text-blue-600 dark:text-blue-400',
    bar: 'bg-gradient-to-r from-blue-400 to-blue-500',
    progress: 'bg-blue-500'
  },
  green: {
    icon: 'text-green-500',
    iconBg: 'bg-green-100 dark:bg-green-900/30',
    value: 'text-green-600 dark:text-green-400',
    bar: 'bg-gradient-to-r from-green-400 to-green-500',
    progress: 'bg-green-500'
  },
  red: {
    icon: 'text-red-500',
    iconBg: 'bg-red-100 dark:bg-red-900/30',
    value: 'text-red-600 dark:text-red-400',
    bar: 'bg-gradient-to-r from-red-400 to-red-500',
    progress: 'bg-red-500'
  },
  brand: {
    icon: 'text-brand-500',
    iconBg: 'bg-brand-100 dark:bg-brand-900/30',
    value: 'text-brand-600 dark:text-brand-400',
    bar: 'bg-gradient-to-r from-brand-400 to-brand-500',
    progress: 'bg-brand-500'
  },
  amber: {
    icon: 'text-amber-500',
    iconBg: 'bg-amber-100 dark:bg-amber-900/30',
    value: 'text-amber-600 dark:text-amber-400',
    bar: 'bg-gradient-to-r from-amber-400 to-amber-500',
    progress: 'bg-amber-500'
  },
  purple: {
    icon: 'text-purple-500',
    iconBg: 'bg-purple-100 dark:bg-purple-900/30',
    value: 'text-purple-600 dark:text-purple-400',
    bar: 'bg-gradient-to-r from-purple-400 to-purple-500',
    progress: 'bg-purple-500'
  }
}

const getIconColor = computed(() => colorClasses[props.color]?.icon || colorClasses.gray.icon)
const getIconBgClass = computed(() => colorClasses[props.color]?.iconBg || colorClasses.gray.iconBg)
const getValueColor = computed(() => colorClasses[props.color]?.value || colorClasses.gray.value)
const getBottomBarClass = computed(() => colorClasses[props.color]?.bar || colorClasses.gray.bar)
const getProgressBarColor = computed(() => colorClasses[props.color]?.progress || colorClasses.gray.progress)
</script>