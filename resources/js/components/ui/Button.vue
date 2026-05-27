<template>
  <button
    :class="[
      'inline-flex items-center justify-center font-medium gap-1.5 transition-all border cursor-pointer',
      sizeClasses[size],
      variantClasses[variant],
      className,
      { 'cursor-not-allowed opacity-50': disabled },
    ]"
    :disabled="disabled"
    @click="handleClick"
  >
    <span v-if="startIcon" class="flex items-center">
      <component :is="startIcon" />
    </span>
    <slot></slot>
    <span v-if="endIcon" class="flex items-center">
      <component :is="endIcon" />
    </span>
  </button>
</template>

<script setup lang="ts">
interface ButtonProps {
  size?: 'sm' | 'md'
  variant?: 'primary' | 'accent' | 'outline' | 'ghost' | 'danger' | 'add'
  startIcon?: object
  endIcon?: object
  onClick?: () => void
  className?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<ButtonProps>(), {
  size: 'md',
  variant: 'primary',
  className: '',
  disabled: false,
})

const sizeClasses: Record<string, string> = {
  sm: 'h-[24px] px-[9px] text-[11px] rounded-[3px]',
  md: 'h-[30px] px-3 text-[12px] rounded-[4px]',
}

const variantClasses: Record<string, string> = {
  primary:
    'bg-[#1E3A5F] text-white border-[#1E3A5F] hover:brightness-105 disabled:opacity-50',
  accent:
    'bg-brand-500 text-white border-brand-500 hover:brightness-105 disabled:opacity-50',
  outline:
    'bg-white text-gray-700 border-gray-200 hover:border-brand-500 hover:text-brand-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:border-brand-500 dark:hover:text-brand-400',
  ghost:
    'bg-transparent border-transparent text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5',
  danger:
    'bg-white text-error-500 border-error-500 hover:bg-error-50 dark:bg-transparent dark:text-error-400 dark:border-error-400',
  add:
    'bg-white text-brand-500 border-brand-500 hover:bg-brand-50 dark:bg-transparent dark:text-brand-400 dark:border-brand-400 h-auto! px-[10px] py-[3px]',
}

const handleClick = () => {
  if (!props.disabled && props.onClick) {
    props.onClick()
  }
}
</script>
