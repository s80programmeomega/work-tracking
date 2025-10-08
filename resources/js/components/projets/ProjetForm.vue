<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        {{ projet ? 'Modifier le projet' : 'Nouveau projet' }}
      </h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Nom du projet *
          </label>
          <input
            v-model="formData.nom"
            type="text"
            required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Description
          </label>
          <textarea
            v-model="formData.description"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Date début *
            </label>
            <input
              v-model="formData.date_debut"
              type="date"
              required
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Date fin
            </label>
            <input
              v-model="formData.date_fin"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Responsable *
            </label>
            <select
              v-model="formData.responsable_id"
              required
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            >
              <option value="">Sélectionner un responsable</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.nom }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Progression (%)
            </label>
            <input
              v-model.number="formData.progression"
              type="number"
              min="0"
              max="100"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Statut
            </label>
            <select
              v-model="formData.status"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            >
              <option value="active">Actif</option>
              <option value="completed">Terminé</option>
              <option value="archived">Archivé</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Visibilité
            </label>
            <select
              v-model="formData.visibility"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            >
              <option value="private">Privé</option>
              <option value="team">Équipe</option>
              <option value="public">Public</option>
            </select>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 disabled:opacity-50"
          >
            {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useProjets } from '@/composables/useProjets'
import api from '@/api/axios'

const props = defineProps({
  projet: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const { createProjet, updateProjet } = useProjets()

const loading = ref(false)
const users = ref([])
const formData = ref({
  nom: '',
  description: '',
  date_debut: '',
  date_fin: '',
  responsable_id: authStore.user?.id,
  progression: 0,
  status: 'active',
  visibility: 'team'
})

const loadUsers = async () => {
  try {
    const { data } = await api.get('/users')
    users.value = data.data || []
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (props.projet) {
      await updateProjet(props.projet.id, formData.value)
    } else {
      await createProjet(formData.value)
    }
    emit('saved')
  } catch (error) {
    alert('Erreur lors de l\'enregistrement du projet')
    console.error(error)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadUsers()

  if (props.projet) {
    formData.value = {
      nom: props.projet.nom || '',
      description: props.projet.description || '',
      date_debut: props.projet.date_debut || '',
      date_fin: props.projet.date_fin || '',
      responsable_id: props.projet.responsable_id || authStore.user?.id,
      progression: props.projet.progression || 0,
      status: props.projet.status || 'active',
      visibility: props.projet.visibility || 'team'
    }
  }
})
</script>
