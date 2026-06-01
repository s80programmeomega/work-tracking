<template>
  <div class="overflow-x-auto rounded-3 border border-gray-200 dark:border-gray-700">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/3">
            Titre
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Statut
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Priorité
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Échéance
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Assigné à
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Visibilité
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Avancement
          </th>
          <th class="px-4 py-3 w-10"></th>
        </tr>
      </thead>
      <tbody ref="tbodyRef" class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
        <tr
          v-for="tache in taches"
          :key="tache.id"
          :dusk="`table-row-${tache.id}`"
          class="stagger-item hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group"
        >
          <!-- Titre -->
          <td class="px-4 py-3">
            <button
              :dusk="`tache-view-btn-${tache.id}`"
              class="text-left font-medium text-gray-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
              @click="$emit('view', tache)"
            >
              {{ tache.titre }}
            </button>
            <div v-if="tache.sous_taches_count > 0" class="mt-0.5">
              <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs text-gray-500 dark:text-gray-400">
                {{ tache.sous_taches_count }} ST
              </span>
            </div>
          </td>

          <!-- Statut — inline edit -->
          <td class="px-4 py-3">
            <template v-if="editing.id === tache.id && editing.field === 'statut'">
              <select
                v-model="editing.value"
                class="text-xs border border-brand-400 rounded px-2 py-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none"
                @change="saveInlineEdit(tache)"
                @blur="cancelEdit"
                @keydown.escape="cancelEdit"
                autofocus
              >
                <option value="a_faire">À faire</option>
                <option value="en_cours">En cours</option>
                <option value="termine">Terminé</option>
                <option value="en_retard">En retard</option>
                <option value="a_refaire">À refaire</option>
              </select>
            </template>
            <template v-else>
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium cursor-pointer"
                :class="statutClasses(tache.statut)"
                :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier' : ''"
                @click="tache.permissions?.can_inline_edit && startEdit(tache, 'statut', tache.statut)"
              >
                {{ statutLabel(tache.statut) }}
              </span>
            </template>
          </td>

          <!-- Priorité — inline edit -->
          <td class="px-4 py-3">
            <template v-if="editing.id === tache.id && editing.field === 'priorite'">
              <select
                v-model="editing.value"
                class="text-xs border border-brand-400 rounded px-2 py-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none"
                @change="saveInlineEdit(tache)"
                @blur="cancelEdit"
                @keydown.escape="cancelEdit"
                autofocus
              >
                <option value="faible">Faible</option>
                <option value="moyenne">Moyenne</option>
                <option value="elevee">Élevée</option>
                <option value="critique">Critique</option>
              </select>
            </template>
            <template v-else>
              <span
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium cursor-pointer"
                :class="prioriteClasses(tache.priorite)"
                :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier' : ''"
                @click="tache.permissions?.can_inline_edit && startEdit(tache, 'priorite', tache.priorite)"
              >
                {{ prioriteLabel(tache.priorite) }}
              </span>
            </template>
          </td>

          <!-- Échéance — inline edit -->
          <td class="px-4 py-3">
            <template v-if="editing.id === tache.id && editing.field === 'echeance'">
              <input
                v-model="editing.value"
                type="date"
                class="text-xs border border-brand-400 rounded px-2 py-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none"
                @change="saveInlineEdit(tache)"
                @blur="cancelEdit"
                @keydown.escape="cancelEdit"
                autofocus
              />
            </template>
            <template v-else>
              <span
                class="text-sm cursor-pointer"
                :class="tache.is_overdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-400'"
                :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier' : ''"
                @click="tache.permissions?.can_inline_edit && startEdit(tache, 'echeance', tache.echeance ? tache.echeance.substring(0, 10) : '')"
              >
                {{ tache.echeance ? formatDate(tache.echeance) : '—' }}
              </span>
            </template>
          </td>

          <!-- Assignés -->
          <td class="px-4 py-3">
            <span class="text-sm text-gray-600 dark:text-gray-400">
              {{ tache.assignees?.map(a => a.nom).join(', ') || '—' }}
            </span>
          </td>

          <!-- Visibilité -->
          <td class="px-4 py-3">
            <span
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
              :class="visibilityClasses(tache.visibility)"
            >{{ visibilityLabel(tache.visibility) }}</span>
          </td>

          <!-- Avancement -->
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div
                  class="h-1.5 rounded-full transition-all"
                  :class="progressColor(tache.taux_realisation)"
                  :style="{ width: `${tache.taux_realisation ?? 0}%` }"
                ></div>
              </div>
              <span class="text-xs text-gray-500 dark:text-gray-400 w-8">{{ tache.taux_realisation ?? 0 }}%</span>
            </div>
          </td>

          <!-- Actions -->
          <td class="px-4 py-3">
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                v-if="tache.permissions?.can_edit"
                class="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                title="Modifier"
                @click="$emit('edit', tache)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
            </div>
          </td>
        </tr>

        <tr v-if="taches.length === 0">
          <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
            Aucune tâche pour cette activité.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import api from '@/api/axios'
import { useStagger } from '@/composables/useAnimations'

const props = defineProps({
  taches: { type: Array, required: true },
})

const emit = defineEmits(['view', 'edit', 'updated'])

const { staggerRef: tbodyRef, applyStagger } = useStagger(40)
watch(() => props.taches, applyStagger, { immediate: true })

const editing = reactive({ id: null, field: null, value: null })

const startEdit = (tache, field, value) => {
  editing.id = tache.id
  editing.field = field
  editing.value = value
}

const cancelEdit = () => {
  editing.id = null
  editing.field = null
  editing.value = null
}

const saveInlineEdit = async (tache) => {
  const { id, field, value } = editing
  if (!id || !field) return

  const oldValue = tache[field]
  if (value === oldValue) {
    cancelEdit()
    return
  }

  try {
    await api.patch(`/taches/${id}`, { [field]: value })
    tache[field] = value
    emit('updated', { tache, field, oldValue, newValue: value })
  } catch (err) {
    console.error('Erreur mise à jour inline:', err)
  } finally {
    cancelEdit()
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const statutLabel = (s) => ({
  a_faire: 'À faire', en_cours: 'En cours', termine: 'Terminé',
  en_retard: 'En retard', a_refaire: 'À refaire',
  en_attente: 'En attente', annule: 'Annulé',
}[s] ?? s)

const statutClasses = (s) => ({
  a_faire: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  en_cours: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  termine: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  en_retard: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  a_refaire: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
  en_attente: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  annule: 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400 line-through',
}[s] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200')

const prioriteLabel = (p) => ({
  faible: 'Faible', moyenne: 'Moyenne', elevee: 'Élevée', critique: 'Critique',
}[p] ?? p)

const prioriteClasses = (p) => ({
  faible: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
  moyenne: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  elevee: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
  critique: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[p] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300')

const visibilityLabel = (v) => ({ public: 'Public', private: 'Privé', members_only: 'Membres' }[v] ?? v)

const visibilityClasses = (v) => ({
  public: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  members_only: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  private: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
}[v] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400')

const progressColor = (pct) => {
  if (!pct || pct < 33) return 'bg-red-400'
  if (pct < 66) return 'bg-yellow-400'
  return 'bg-green-500'
}
</script>
