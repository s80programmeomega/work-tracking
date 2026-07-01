<template>
  <div class="p-5 border border-gray-200 dark:border-gray-700 rounded-3">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h5 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-brand-600">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
        </svg>
        {{ $t('profile_sections.certificates.title') }}
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
          <p class="font-medium text-sm text-gray-900 dark:text-white">{{ item.titre }}</p>
          <p v-if="item.organisme_emetteur" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ item.organisme_emetteur }}</p>
          <div class="flex items-center gap-3 mt-0.5 flex-wrap">
            <p v-if="item.date_obtention" class="text-xs text-gray-400 dark:text-gray-500">
              {{ $t('profile_sections.certificates.obtained') }} {{ formatDate(item.date_obtention) }}
            </p>
            <p v-if="item.date_expiration" class="text-xs text-gray-400 dark:text-gray-500">
              {{ $t('profile_sections.certificates.expires') }} {{ formatDate(item.date_expiration) }}
            </p>
          </div>
          <a
            v-if="item.credential_url"
            :href="item.credential_url"
            target="_blank"
            rel="noopener"
            class="mt-1 inline-flex items-center gap-1 text-xs text-brand-600 hover:underline"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
            {{ $t('profile_sections.certificates.verify') }}
          </a>
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
        {{ readonly ? $t('profile_sections.empty') : $t('profile_sections.certificates.empty_editable') }}
      </p>
    </div>

    <!-- Inline form -->
    <div v-if="showForm && !readonly" class="mt-4 p-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-3 space-y-3">
      <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ editingId ? $t('profile_sections.edit') : $t('profile_sections.certificates.add_title') }}
      </h6>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.certificates.titre') }} *</label>
          <input v-model="form.titre" type="text" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
          <p v-if="errors.titre" class="mt-1 text-xs text-red-500">{{ errors.titre[0] }}</p>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.certificates.organisme') }}</label>
          <input v-model="form.organisme_emetteur" type="text" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.certificates.date_obtention') }}</label>
          <input v-model="form.date_obtention" type="date" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.certificates.date_expiration') }}</label>
          <input v-model="form.date_expiration" type="date" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.certificates.credential_id') }}</label>
          <input v-model="form.credential_id" type="text" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('profile_sections.certificates.credential_url') }}</label>
          <input v-model="form.credential_url" type="url" class="w-full px-3 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent" />
          <p v-if="errors.credential_url" class="mt-1 text-xs text-red-500">{{ errors.credential_url[0] }}</p>
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
    titre: '',
    organisme_emetteur: '',
    date_obtention: '',
    date_expiration: '',
    credential_id: '',
    credential_url: '',
    description: '',
})
const form = ref(emptyForm())

const load = async () => {
    if (props.initialData !== null) {
        items.value = props.initialData
        applyStagger()
        return
    }
    const { data } = await api.get('/users/profile/certificates')
    items.value = data.data ?? []
    applyStagger()
}

onMounted(load)

const startEdit = (item) => {
    editingId.value = item.id
    form.value = {
        titre: item.titre ?? '',
        organisme_emetteur: item.organisme_emetteur ?? '',
        date_obtention: item.date_obtention ? item.date_obtention.substring(0, 10) : '',
        date_expiration: item.date_expiration ? item.date_expiration.substring(0, 10) : '',
        credential_id: item.credential_id ?? '',
        credential_url: item.credential_url ?? '',
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
            const { data } = await api.put(`/users/profile/certificates/${editingId.value}`, form.value)
            const idx = items.value.findIndex(i => i.id === editingId.value)
            if (idx !== -1) { items.value[idx] = data.data }
        } else {
            const { data } = await api.post('/users/profile/certificates', form.value)
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
    await api.delete(`/users/profile/certificates/${id}`)
    items.value = items.value.filter(i => i.id !== id)
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' }) : ''
</script>
