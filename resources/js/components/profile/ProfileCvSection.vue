<template>
  <div class="p-5 border border-gray-200 dark:border-gray-700 rounded-3">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h5 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-brand-600">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
        {{ $t('profile_sections.cv.title') }}
      </h5>
      <label
        v-if="!readonly"
        class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-brand-600 rounded-3 hover:bg-brand-700 transition-colors cursor-pointer"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        {{ $t('profile_sections.cv.upload') }}
        <input ref="fileInput" type="file" class="hidden" accept=".pdf,.doc,.docx" @change="onFileSelected" />
      </label>
    </div>

    <!-- Upload progress -->
    <div v-if="uploading" class="mb-3 flex items-center gap-2 text-sm text-brand-600 dark:text-brand-400">
      <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
      {{ $t('profile_sections.cv.uploading') }}
    </div>

    <p v-if="uploadError" class="mb-3 text-xs text-red-500 dark:text-red-400">{{ uploadError }}</p>

    <!-- List -->
    <div ref="staggerRef" class="space-y-2">
      <div
        v-for="doc in items"
        :key="doc.id"
        class="stagger-item flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-3"
      >
        <div class="flex items-center gap-2 min-w-0">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 text-red-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
          </svg>
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ doc.nom }}</p>
            <p v-if="doc.description" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ doc.description }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">{{ formatSize(doc.taille) }} · {{ formatDate(doc.created_at) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-1 shrink-0">
          <button
            @click="download(doc)"
            class="p-1.5 text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
            :title="$t('profile_sections.cv.download')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
          </button>
          <button
            v-if="!readonly"
            @click="remove(doc.id)"
            class="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
          </button>
        </div>
      </div>

      <p v-if="items.length === 0 && !uploading" class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">
        {{ readonly ? $t('profile_sections.cv.empty_readonly') : $t('profile_sections.cv.empty_editable') }}
      </p>
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
    targetUserId: { type: [Number, String], default: null },
})

const { t } = useI18n()
const { staggerRef, applyStagger } = useStagger(50)

const items = ref([])
const uploading = ref(false)
const uploadError = ref(null)
const fileInput = ref(null)

const load = async () => {
    if (props.initialData !== null) {
        items.value = props.initialData
        applyStagger()
        return
    }
    const { data } = await api.get('/users/profile/cv')
    items.value = data.data ?? []
    applyStagger()
}

onMounted(load)

const onFileSelected = async (event) => {
    const file = event.target.files[0]
    if (!file) { return }
    uploadError.value = null
    uploading.value = true
    try {
        const form = new FormData()
        form.append('file', file)
        const { data } = await api.post('/users/profile/cv', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        items.value.unshift(data.data)
        applyStagger()
    } catch (err) {
        uploadError.value = err.response?.data?.message
            ?? err.response?.data?.errors?.file?.[0]
            ?? t('profile_sections.cv.upload_error')
    } finally {
        uploading.value = false
        if (fileInput.value) { fileInput.value.value = '' }
    }
}

const download = async (doc) => {
    const response = await api.get(`/documents/${doc.id}/download`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', doc.nom)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
}

const remove = async (id) => {
    if (!confirm(t('profile_sections.confirm_delete'))) { return }
    await api.delete(`/users/profile/cv/${id}`)
    items.value = items.value.filter(d => d.id !== id)
}

const formatSize = (bytes) => {
    if (!bytes) { return '' }
    if (bytes < 1024) { return bytes + ' B' }
    if (bytes < 1024 * 1024) { return (bytes / 1024).toFixed(1) + ' KB' }
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
    : ''
</script>
