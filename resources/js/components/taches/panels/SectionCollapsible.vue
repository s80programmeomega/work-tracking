<template>
  <div class="border border-gray-200 dark:border-gray-700 rounded-3 overflow-hidden">
    <!-- Header cliquable -->
    <button
      @click="isOpen = !isOpen"
      class="w-full px-4 py-3 flex items-center justify-between bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
    >
      <h4 class="font-semibold text-gray-900 dark:text-white">{{ title }}</h4>
      
      <svg 
        class="w-5 h-5 text-gray-500 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Contenu -->
    <transition
      name="collapse"
      @enter="enter"
      @after-enter="afterEnter"
      @leave="leave"
    >
      <div v-show="isOpen" class="overflow-hidden">
        <div class="p-4 bg-white dark:bg-gray-800">
          <slot></slot>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  defaultOpen: {
    type: Boolean,
    default: false
  }
})

const isOpen = ref(props.defaultOpen)

// Animations smoothes
const enter = (el) => {
  el.style.height = '0'
  el.style.overflow = 'hidden'
  requestAnimationFrame(() => {
    el.style.transition = 'height 0.3s ease'
    el.style.height = el.scrollHeight + 'px'
  })
}

const afterEnter = (el) => {
  el.style.height = 'auto'
  el.style.overflow = 'visible'
}

const leave = (el) => {
  el.style.height = el.scrollHeight + 'px'
  el.style.overflow = 'hidden'
  requestAnimationFrame(() => {
    el.style.transition = 'height 0.3s ease'
    el.style.height = '0'
  })
}
</script>

<style scoped>
.collapse-enter-active,
.collapse-leave-active {
  transition: height 0.3s ease;
}
</style>