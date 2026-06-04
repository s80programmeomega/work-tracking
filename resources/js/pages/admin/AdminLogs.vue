<!-- Journal applicatif unifié : activité Spatie, logs fichier natifs, audit de validation. -->
<template>
  <admin-layout>
    <page-breadcrumb :page-title="$t('admin_logs.page_title')" />

    <div class="space-y-4">
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3">

        <!-- Onglets -->
        <nav class="flex space-x-1 overflow-x-auto border-b border-gray-200 px-4 dark:border-gray-800">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            dusk="log-tab"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-2 px-4 py-3.5 text-sm font-medium transition-all whitespace-nowrap',
              activeTab === tab.id
                ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            ]"
          >
            <component :is="tab.icon" class="h-4 w-4" />
            {{ tab.label }}
          </button>
        </nav>

        <!-- Contenu des onglets -->
        <ActivityLogTab v-if="activeTab === 'activity'" dusk="activity-tab" />
        <AppLogsTab v-else-if="activeTab === 'app_logs'" dusk="app-logs-tab" />
        <ValidationAuditLogTab v-else-if="activeTab === 'audit'" dusk="audit-tab" />
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ClipboardDocumentListIcon, DocumentTextIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ActivityLogTab from '@/components/admin/logs/ActivityLogTab.vue'
import AppLogsTab from '@/components/admin/logs/AppLogsTab.vue'
import ValidationAuditLogTab from '@/components/admin/logs/ValidationAuditLogTab.vue'

const { t } = useI18n()

const activeTab = ref('activity')

const tabs = computed(() => [
  { id: 'activity', label: t('admin_logs.tab_activity'), icon: ClipboardDocumentListIcon },
  { id: 'app_logs', label: t('admin_logs.tab_app_logs'), icon: DocumentTextIcon },
  { id: 'audit', label: t('admin_logs.tab_audit'), icon: ShieldCheckIcon },
])
</script>
