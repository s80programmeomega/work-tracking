<!-- Journal d'audit des actions admin-plateforme : accessible uniquement au superadmin permanent et au system owner. -->
<template>
  <AdminLayout>
    <page-breadcrumb :page-title="$t('admin_audit.page_title')" />

    <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3">
      <AdminAuditLogTab endpoint="/admin/audit-log" />
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminAuditLogTab from '@/components/admin/logs/AdminAuditLogTab.vue'
</script>
