<template>
  <div class="p-5 border border-gray-200 dark:border-gray-700 rounded-3">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h5 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-brand-600">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
        </svg>
        {{ $t('profile_sections.school.title') }}
      </h5>
      <button
        v-if="!readonly"
        @click="showForm = true"
        class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-brand-600 rounded-3 hover:bg-brand-700 transition-colors"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        {{ $t('profile_sections.add') }}
      </button>
    </div>

    <!-- List -->
    <div ref="staggerRef" class="space-y-3">
      <div
        v-for="item in items"
        :key="item.id"
        class="stagger-item flex items-start justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3"
      >
        <div class="flex-1 min-w-0">
          <p class="font-medium text-sm text-gray-900 dark:text-white">{{ item.etablissement }}</p>
          <p v-if="item.diplome || item.domaine" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {{ [item.diplome, item.domaine].filter(Boolean).join(' · ') }}
          </p>
          <p v-if="item.date_debut || item.date_fin" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
            {{ formatDateRange(item.date_debut, item.date_fin) }}
          </p>
          <p v-if="item.description" class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ item.description }}</p>
        </div>
        <div v-if="!readonly" class="flex gap-1 shrink-0">
          <button @click="startEdit(item)" class="p-1.5 text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>
          </button>
          <button @click="remove(item.id)" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
          </button>
        </div>
      </div>

      <p v-if="items.length === 0 && !showForm" class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">
        {{ readonly ? $t('profile_sections.empty') : $t('profile_sections.school.empty_editable') }}
      </p>
    </div>

    <!-- Inline form -->
    <div v-if="showForm && !readonly" class="mt-4 p-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-3 space-y-3">
      <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ editingId ? $t('profile_sections.edit') : $t('profile_sections.school.add_title') }}
      </h6>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.school.etablissement') }} *</label>
          <input v-model="form.etablissement" type="text" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
          <p v-if="errors.etablissement" class="mt-1 text-xs text-red-500">{{ errors.etablissement[0] }}</p>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.school.diplome') }}</label>
          <input v-model="form.diplome" type="text" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.school.domaine') }}</label>
          <input v-model="form.domaine" type="text" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.date_debut') }}</label>
          <input v-model="form.date_debut" type="date" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.date_fin') }}</label>
          <input v-model="form.date_fin" type="date" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.description') }}</label>
          <textarea v-model="form.description" rows="2" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button @click="cancelForm" type="button" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
          {{ $t('profile_sections.cancel') }}
        </button>
        <button @click="save" :disabled="saving" type="button" class="px-4 py-1.5 text-sm font-medium text-white bg-brand-600 rounded-3 hover:bg-brand-700 disabled:opacity-50 transition-colors">
          {{ saving ? $t('profile_sections.saving') : $t('profile_sections.save') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/api/axios'
import { useStagger } from '@/composables/useAnimations'

const props = defineProps({
    readonly: { type: Boolean, default: false },
    initialData: { type: Array, default: null },
})

const { t } = useI18n()
const { staggerRef, applyStagger } = useStagger(50)

const items = ref([])
const showForm = ref(false)
const saving = ref(false)
const editingId = ref(null)
const errors = ref({})

const emptyForm = () => ({
    etablissement: '',
    diplome: '',
    domaine: '',
    date_debut: '',
    date_fin: '',
    description: '',
})
const form = ref(emptyForm())

const load = async () => {
    if (props.initialData !== null) {
        items.value = props.initialData
        applyStagger()
        return
    }
    const { data } = await api.get('/users/profile/school-backgrounds')
    items.value = data.data ?? []
    applyStagger()
}

onMounted(load)

const startEdit = (item) => {
    editingId.value = item.id
    form.value = {
        etablissement: item.etablissement ?? '',
        diplome: item.diplome ?? '',
        domaine: item.domaine ?? '',
        date_debut: item.date_debut ? item.date_debut.substring(0, 10) : '',
        date_fin: item.date_fin ? item.date_fin.substring(0, 10) : '',
        description: item.description ?? '',
    }
    showForm.value = true
}

const cancelForm = () => {
    showForm.value = false
    editingId.value = null
    form.value = emptyForm()
    errors.value = {}
}

const save = async () => {
    errors.value = {}
    saving.value = true
    try {
        if (editingId.value) {
            const { data } = await api.put(`/users/profile/school-backgrounds/${editingId.value}`, form.value)
            const idx = items.value.findIndex(i => i.id === editingId.value)
            if (idx !== -1) { items.value[idx] = data.data }
        } else {
            const { data } = await api.post('/users/profile/school-backgrounds', form.value)
            items.value.push(data.data)
        }
        cancelForm()
        applyStagger()
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors
        }
    } finally {
        saving.value = false
    }
}

const remove = async (id) => {
    if (!confirm(t('profile_sections.confirm_delete'))) { return }
    await api.delete(`/users/profile/school-backgrounds/${id}`)
    items.value = items.value.filter(i => i.id !== id)
}

const formatDateRange = (start, end) => {
    const fmt = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' }) : null
    const s = fmt(start)
    const e = fmt(end)
    if (s && e) { return `${s} — ${e}` }
    if (s) { return `${s} — ${t('profile_sections.present')}` }
    return ''
}
</script>
