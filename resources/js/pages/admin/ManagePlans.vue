<!-- Gestion des plans d'abonnement (Phase 8) — super_admin uniquement. -->
<template>
  <AdminLayout>
    <div class="space-y-5">

      <!-- En-tête -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('admin.plans.title') }}</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('admin.plans.subtitle') }}</p>
        </div>
        <button
          @click="openCreate"
          class="rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          {{ $t('admin.plans.new_plan') }}
        </button>
      </div>

      <p v-if="error" class="rounded-3 border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">{{ error }}</p>

      <!-- Tableau -->
      <div class="overflow-hidden rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3">
        <p v-if="loading" class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ $t('admin.plans.loading') }}</p>
        <p v-else-if="plans.length === 0" class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ $t('admin.plans.empty') }}</p>

        <table v-else class="w-full text-sm">
          <thead class="border-b border-gray-200 text-left text-xs uppercase text-gray-400 dark:border-gray-800">
            <tr>
              <th class="px-4 py-3">{{ $t('admin.plans.col_name') }}</th>
              <th class="px-4 py-3">{{ $t('admin.plans.col_price') }}</th>
              <th class="px-4 py-3">{{ $t('admin.plans.col_limits') }}</th>
              <th class="px-4 py-3">{{ $t('admin.plans.col_status') }}</th>
              <th class="px-4 py-3">{{ $t('admin.plans.col_workspaces') }}</th>
              <th class="px-4 py-3 text-right">{{ $t('admin.plans.col_actions') }}</th>
            </tr>
          </thead>
          <tbody ref="rowsRef" class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr v-for="plan in plans" :key="plan.id" class="stagger-item">
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                {{ plan.nom_fr }}
                <span v-if="plan.is_free" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $t('admin.plans.free') }}</span>
              </td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ plan.is_free ? '—' : `${plan.price} ${plan.currency}` }}</td>
              <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                {{ lim(plan.max_members) }} {{ $t('admin.plans.members') }} ·
                {{ lim(plan.max_storage_mb) }} {{ $t('admin.plans.storage') }} ·
                {{ lim(plan.max_file_size_mb) }} {{ $t('admin.plans.file') }}
              </td>
              <td class="px-4 py-3">
                <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="plan.is_active ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'">
                  {{ plan.is_active ? $t('admin.plans.active') : $t('admin.plans.inactive') }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ plan.workspaces_count ?? 0 }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openEdit(plan)" class="rounded-3 border border-blue-500 px-2 py-1 text-xs text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20">{{ $t('admin.plans.edit') }}</button>
                  <button
                    v-if="!plan.is_free && (plan.workspaces_count ?? 0) === 0"
                    @click="remove(plan)"
                    class="rounded-3 border border-red-400 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                  >{{ $t('admin.plans.delete') }}</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modale création / édition -->
      <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal.open = false">
        <div class="w-full max-w-lg rounded-3 border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900 max-h-[90vh] overflow-y-auto">
          <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            {{ modal.editing ? $t('admin.plans.edit_plan') : $t('admin.plans.new_plan') }}
          </h2>
          <form class="space-y-3" @submit.prevent="save">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('admin.plans.f_slug') }}</label>
                <input v-model="form.slug" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <label class="flex items-center gap-2 self-end pb-2 text-sm text-gray-700 dark:text-gray-300">
                <input v-model="form.is_free" type="checkbox" class="rounded border-gray-300" />
                {{ $t('admin.plans.f_is_free') }}
              </label>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('admin.plans.f_nom_fr') }}</label>
                <input v-model="form.nom_fr" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('admin.plans.f_nom_en') }}</label>
                <input v-model="form.nom_en" required class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('admin.plans.f_price') }}</label>
                <input v-model.number="form.price" type="number" min="0" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('admin.plans.f_billing') }}</label>
                <select v-model="form.billing_period" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                  <option value="monthly">{{ $t('admin.plans.monthly') }}</option>
                  <option value="yearly">{{ $t('admin.plans.yearly') }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('admin.plans.f_max_members') }}</label>
                <input v-model.number="form.max_members" type="number" min="-1" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('admin.plans.f_max_storage') }}</label>
                <input v-model.number="form.max_storage_mb" type="number" min="-1" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('admin.plans.f_max_file') }}</label>
                <input v-model.number="form.max_file_size_mb" type="number" min="-1" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
            </div>
            <p class="text-xs text-gray-400">{{ $t('admin.plans.unlimited_hint') }}</p>

            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
              {{ $t('admin.plans.f_is_active') }}
            </label>

            <p v-if="modal.error" class="text-sm text-red-600">{{ modal.error }}</p>

            <div class="flex items-center justify-end gap-3 pt-2">
              <button type="button" @click="modal.open = false" class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">{{ $t('common.cancel') }}</button>
              <button type="submit" :disabled="modal.loading" class="rounded-3 bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">{{ $t('admin.plans.save') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const { t } = useI18n()
const { staggerRef: rowsRef, applyStagger } = useStagger(40)

const plans = ref([])
const loading = ref(false)
const error = ref('')

const emptyForm = () => ({
  slug: '', nom_fr: '', nom_en: '', description_fr: '', description_en: '',
  is_free: false, price: 0, currency: 'XAF', billing_period: 'monthly',
  max_members: -1, max_storage_mb: -1, max_file_size_mb: -1, features: [], is_active: true, position: 0,
})
const form = ref(emptyForm())
const modal = ref({ open: false, editing: null, loading: false, error: '' })

const lim = (v) => v === -1 ? t('admin.plans.unlimited') : v

const fetchPlans = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/plans')
    plans.value = data.plans ?? []
    await applyStagger()
  } catch (e) {
    error.value = e.response?.data?.message ?? t('admin.plans.error')
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  form.value = emptyForm()
  modal.value = { open: true, editing: null, loading: false, error: '' }
}

const openEdit = (plan) => {
  form.value = { ...emptyForm(), ...plan }
  modal.value = { open: true, editing: plan, loading: false, error: '' }
}

const save = async () => {
  modal.value.loading = true
  modal.value.error = ''
  try {
    if (modal.value.editing) {
      await api.put(`/admin/plans/${modal.value.editing.id}`, form.value)
    } else {
      await api.post('/admin/plans', form.value)
    }
    modal.value.open = false
    fetchPlans()
  } catch (e) {
    modal.value.error = e.response?.data?.message ?? t('admin.plans.error')
  } finally {
    modal.value.loading = false
  }
}

const remove = async (plan) => {
  if (!confirm(t('admin.plans.confirm_delete'))) return
  try {
    await api.delete(`/admin/plans/${plan.id}`)
    fetchPlans()
  } catch (e) {
    error.value = e.response?.data?.message ?? t('admin.plans.error')
  }
}

onMounted(fetchPlans)
</script>
