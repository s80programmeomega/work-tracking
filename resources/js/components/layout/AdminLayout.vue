<template>
  <div class="min-h-screen xl:flex">
    <app-sidebar />
    <Backdrop />
    <div
      class="flex-1 transition-all duration-300 ease-in-out"
      :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']"
    >
      <app-header />
      <TrialBanner />
      <div
        ref="contentRef"
        class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6 bg-gray-100 dark:bg-gray-900 min-h-[calc(100vh-64px)]"
      >
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import TrialBanner from '@/components/common/TrialBanner.vue'
import { useSidebar } from '@/composables/useSidebar'
import Backdrop from './Backdrop.vue'

const { isExpanded, isHovered } = useSidebar()
const route = useRoute()
const contentRef = ref(null)

function triggerPageEnter() {
  const el = contentRef.value
  if (!el) { return }
  el.classList.remove('page-enter')
  void el.offsetWidth
  el.classList.add('page-enter')
}

watch(() => route.path, triggerPageEnter, { immediate: true })
</script>
