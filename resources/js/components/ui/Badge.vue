<template>
  <span :class="['inline-flex items-center justify-center gap-1 border font-medium text-[10px] px-1.5 py-0.5 rounded-1', colorStyles]">
    <span v-if="startIcon" class="mr-0.5">
      <component :is="startIcon" />
    </span>
    <slot></slot>
    <span v-if="endIcon" class="ml-0.5">
      <component :is="endIcon" />
    </span>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type BadgeVariant = 'light' | 'solid'
type BadgeColor = 'primary' | 'success' | 'error' | 'warning' | 'info' | 'light' | 'dark' | 'purple' | 'stone'

interface BadgeProps {
  variant?: BadgeVariant
  size?: 'sm' | 'md'
  color?: BadgeColor
  startIcon?: object
  endIcon?: object
}

const props = withDefaults(defineProps<BadgeProps>(), {
  variant: 'light',
  color: 'primary',
  size: 'md',
})

const colorMap: Record<BadgeColor, string> = {
  primary: 'bg-brand-50 text-brand-500 border-brand-200 dark:bg-brand-500/10 dark:text-brand-400 dark:border-brand-500/30',
  success: 'bg-[#EAFAF1] text-[#1A7A4A] border-[#5DCAA5] dark:bg-success-500/10 dark:text-success-400 dark:border-success-500/30',
  error:   'bg-[#FDEDEC] text-[#C0392B] border-[#E6B0AA] dark:bg-error-500/10 dark:text-error-400 dark:border-error-500/30',
  warning: 'bg-[#FFF3CD] text-[#D68000] border-[#F0C060] dark:bg-warning-500/10 dark:text-warning-400 dark:border-warning-500/30',
  info:    'bg-brand-50 text-brand-500 border-brand-200 dark:bg-brand-500/10 dark:text-brand-400 dark:border-brand-500/30',
  light:   'bg-gray-100 text-gray-700 border-gray-200 dark:bg-white/5 dark:text-gray-300 dark:border-gray-700',
  dark:    'bg-gray-700 text-white border-gray-700 dark:bg-gray-600 dark:border-gray-600',
  purple:  'bg-[#EEEDFE] text-[#534AB7] border-[#AFA9EC] dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/30',
  stone:   'bg-[#F1EFE8] text-[#5F5E5A] border-[#B4B2A9] dark:bg-gray-700/50 dark:text-gray-400 dark:border-gray-600',
}

const colorStyles = computed(() => colorMap[props.color])
</script>
