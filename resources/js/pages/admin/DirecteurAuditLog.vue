<!-- Journal d'audit scopé : directeur voit uniquement les actions de ses superadmins temporaires. -->
<template>
  <AdminLayout>
    <page-breadcrumb :page-title="$t('admin_audit.directeur_page_title')" />

    <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3">
      <AdminAuditLogTab endpoint="/admin/my-audit-log" />
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminAuditLogTab from '@/components/admin/logs/AdminAuditLogTab.vue'
</script>
