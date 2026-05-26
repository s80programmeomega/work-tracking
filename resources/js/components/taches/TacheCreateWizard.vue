<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    @click.self="$emit('close')"
  >
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[95vh] overflow-hidden flex flex-col animate-fade-in">

      <!-- Header -->
      <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-brand-600 to-indigo-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold" dusk="wizard-title">Nouvelle tâche</h2>
            <p class="text-sm text-white/80 mt-0.5">
              {{ activiteContext?.nom ? `Activité : ${activiteContext.nom}` : 'Créer une tâche' }}
            </p>
          </div>
          <button @click="$emit('close')" class="text-white/70 hover:text-white p-1 rounded-lg hover:bg-white/10">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>

        <!-- Step progress -->
        <div class="flex items-center gap-2 mt-4" dusk="wizard-steps">
          <template v-for="(step, idx) in steps" :key="step.id">
            <div class="flex items-center gap-2">
              <button
                type="button"
                :dusk="`wizard-step-${step.id}`"
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                :class="idx < currentStep
                  ? 'bg-white text-brand-600'
                  : idx === currentStep
                    ? 'bg-white text-brand-600 ring-2 ring-white/60'
                    : 'bg-white/30 text-white'"
                :disabled="idx > furthestStep"
                @click="idx <= furthestStep && (currentStep = idx)"
              >
                <i v-if="idx < currentStep" class="fas fa-check text-xs"></i>
                <span v-else>{{ idx + 1 }}</span>
              </button>
              <span class="text-xs text-white/80 hidden sm:inline">{{ step.label }}</span>
            </div>
            <div v-if="idx < steps.length - 1" class="flex-1 h-0.5 rounded-full" :class="idx < currentStep ? 'bg-white' : 'bg-white/30'"></div>
          </template>
        </div>
      </div>

      <!-- Error banner -->
      <div
        v-if="stepError"
        class="mx-6 mt-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg text-sm"
        dusk="wizard-error"
      >
        {{ stepError }}
      </div>

      <!-- Step body -->
      <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Step 1: Basic info -->
        <div v-if="currentStep === 0" dusk="wizard-step-informations">
          <!-- Activité selector (when no context) -->
          <div v-if="!activiteContext" class="space-y-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
              <span class="text-red-500 mr-1">*</span>Activité
            </label>
            <select
              v-model="form.activite_id"
              dusk="wizard-activite"
              class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
            >
              <option value="">Sélectionner une activité</option>
              <option v-for="a in activites" :key="a.id" :value="a.id">
                {{ a.nom }} — {{ a.projet?.nom }}
              </option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
              <span class="text-red-500 mr-1">*</span>Titre
            </label>
            <input
              v-model="form.titre"
              type="text"
              placeholder="Ex : Implémenter l'authentification"
              dusk="wizard-titre"
              class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
            />
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Décrivez la tâche…"
              class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 resize-none"
            ></textarea>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Objectif</label>
            <textarea
              v-model="form.objectif"
              rows="2"
              placeholder="Quel est l'objectif ?"
              class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 resize-none"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Priorité</label>
              <select
                v-model="form.priorite"
                dusk="wizard-priorite"
                class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
              >
                <option value="faible">🟢 Faible</option>
                <option value="moyenne">🟡 Moyenne</option>
                <option value="elevee">🟠 Élevée</option>
                <option value="critique">🔴 Critique</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Échéance</label>
              <input
                v-model="form.echeance"
                type="date"
                dusk="wizard-echeance"
                class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
              />
            </div>
          </div>
        </div>

        <!-- Step 2: Assignment -->
        <div v-if="currentStep === 1" dusk="wizard-step-assignation">
          <div class="space-y-1 mb-4">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
              <span class="text-red-500 mr-1">*</span>Responsable
            </label>
            <select
              v-model="form.responsable_id"
              dusk="wizard-responsable"
              class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
              @change="onResponsableChange"
            >
              <option :value="null">-- Sélectionner un responsable --</option>
              <option v-for="m in availableMembers" :key="m.id" :value="m.id">
                {{ m.nom }} {{ m.prenom }} ({{ m.email }})
              </option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
              Autres intervenants
            </label>
            <div v-if="loadingMembers" class="text-sm text-gray-400 py-2">
              <i class="fas fa-circle-notch fa-spin mr-2"></i>Chargement…
            </div>
            <IntervenantPicker
              v-else
              v-model="form.assignee_ids"
              :members="availableMembers"
              :locked-id="form.responsable_id"
              dusk="wizard-intervenant-picker"
            />
          </div>
        </div>

        <!-- Step 3: Resources -->
        <div v-if="currentStep === 2" dusk="wizard-step-ressources">
          <!-- File upload -->
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Fichiers</label>
            <div
              class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-brand-400 transition-colors"
              @dragover.prevent
              @drop.prevent="onDrop"
            >
              <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Glissez-déposez ou</p>
              <input ref="fileInputRef" type="file" multiple class="hidden" @change="onFileChange" />
              <button
                type="button"
                class="text-sm text-brand-600 dark:text-brand-400 underline"
                @click="fileInputRef.click()"
              >
                parcourir
              </button>
              <p class="text-xs text-gray-400 mt-1">PDF, DOC, XLS, JPG, PNG, ZIP — 10 Mo max</p>
            </div>

            <div v-for="(f, i) in uploadedFiles" :key="i" class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
              <span class="text-gray-700 dark:text-gray-300 truncate">{{ f.name }}</span>
              <button type="button" class="text-red-500 ml-2" @click="uploadedFiles.splice(i, 1)">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <!-- External links -->
          <div class="space-y-2 mt-5">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Liens externes</label>
            <div class="flex gap-2">
              <input
                v-model="newLink.url"
                type="url"
                placeholder="https://…"
                class="flex-1 px-3 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm"
              />
              <input
                v-model="newLink.title"
                type="text"
                placeholder="Titre"
                class="w-32 px-3 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm"
              />
              <button
                type="button"
                class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm hover:bg-green-700"
                @click="addLink"
              >
                <i class="fas fa-plus"></i>
              </button>
            </div>

            <div v-for="(link, i) in externalLinks" :key="i" class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm">
              <a :href="link.url" target="_blank" class="text-brand-600 dark:text-brand-400 truncate">
                {{ link.title || link.url }}
              </a>
              <button type="button" class="text-red-500 ml-2" @click="externalLinks.splice(i, 1)">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Step 4: Validation options -->
        <div v-if="currentStep === 3" dusk="wizard-step-validation">
          <div class="grid grid-cols-1 gap-4">
            <label
              class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
              :class="form.validation_n1_required ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600'"
            >
              <input v-model="form.validation_n1_required" type="checkbox" class="mt-1" dusk="wizard-n1" />
              <div>
                <span class="block text-sm font-bold text-gray-900 dark:text-white">Validation N1 requise</span>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Par le responsable d'activité (cadre)</p>
              </div>
            </label>

            <label
              class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
              :class="form.validation_n2_required ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-300 dark:border-gray-600'"
            >
              <input v-model="form.validation_n2_required" type="checkbox" class="mt-1" dusk="wizard-n2" />
              <div>
                <span class="block text-sm font-bold text-gray-900 dark:text-white">Validation N2 requise</span>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Par le responsable de projet (manager)</p>
              </div>
            </label>
          </div>

          <!-- Summary -->
          <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl space-y-2 text-sm" dusk="wizard-summary">
            <p class="font-semibold text-gray-900 dark:text-white mb-2">Récapitulatif</p>
            <p class="text-gray-600 dark:text-gray-300"><span class="font-medium">Titre :</span> {{ form.titre }}</p>
            <p class="text-gray-600 dark:text-gray-300"><span class="font-medium">Priorité :</span> {{ form.priorite }}</p>
            <p v-if="form.echeance" class="text-gray-600 dark:text-gray-300"><span class="font-medium">Échéance :</span> {{ form.echeance }}</p>
            <p class="text-gray-600 dark:text-gray-300">
              <span class="font-medium">Intervenants :</span> {{ form.assignee_ids.length }}
            </p>
            <p class="text-gray-600 dark:text-gray-300">
              <span class="font-medium">Ressources :</span>
              {{ uploadedFiles.length + externalLinks.length }}
            </p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between gap-3">
        <button
          v-if="currentStep > 0"
          type="button"
          dusk="wizard-prev"
          class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all"
          @click="currentStep--"
        >
          <i class="fas fa-arrow-left mr-2"></i>Précédent
        </button>
        <div v-else></div>

        <div class="flex gap-3">
          <button
            type="button"
            dusk="wizard-cancel"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all"
            @click="$emit('close')"
          >
            Annuler
          </button>

          <button
            v-if="currentStep < steps.length - 1"
            type="button"
            dusk="wizard-next"
            class="px-5 py-2.5 bg-brand-600 text-white rounded-xl text-sm font-semibold hover:bg-brand-700 transition-all"
            @click="nextStep"
          >
            Suivant <i class="fas fa-arrow-right ml-2"></i>
          </button>

          <button
            v-else
            type="button"
            dusk="wizard-submit"
            :disabled="loading"
            class="px-5 py-2.5 bg-brand-600 text-white rounded-xl text-sm font-semibold hover:bg-brand-700 disabled:opacity-50 transition-all flex items-center gap-2"
            @click="handleSubmit"
          >
            <i v-if="loading" class="fas fa-circle-notch fa-spin"></i>
            <i v-else class="fas fa-check"></i>
            {{ loading ? 'Création…' : 'Créer la tâche' }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'
import { useActivityMembers } from '@/composables/useActivityMembers'
import IntervenantPicker from './IntervenantPicker.vue'

const props = defineProps({
  activiteContext: { type: Object, default: null },
  activiteId:      { type: Number, default: null },
  initialStatut:   { type: String, default: 'a_faire' },
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()

const steps = [
  { id: 'informations', label: 'Informations' },
  { id: 'assignation',  label: 'Assignation' },
  { id: 'ressources',   label: 'Ressources' },
  { id: 'validation',   label: 'Validation' },
]

const currentStep  = ref(0)
const furthestStep = ref(0)
const stepError    = ref('')
const loading      = ref(false)

const form = ref({
  activite_id:           props.activiteContext?.id ?? props.activiteId ?? '',
  titre:                 '',
  description:           '',
  objectif:              '',
  priorite:              'moyenne',
  echeance:              '',
  statut:                props.initialStatut ?? 'a_faire',
  responsable_id:        null,
  assignee_ids:          [],
  validation_n1_required: true,
  validation_n2_required: true,
})

const activites     = ref([])
const uploadedFiles = ref([])
const externalLinks = ref([])
const newLink       = ref({ url: '', title: '' })
const fileInputRef  = ref(null)

const { loadingMembers, availableMembers, loadActivityMembers } = useActivityMembers()

// ── Validation per step ──────────────────────────────────────────────────────

function validateCurrentStep() {
  stepError.value = ''

  if (currentStep.value === 0) {
    if (!form.value.activite_id) {
      stepError.value = 'Veuillez sélectionner une activité.'
      return false
    }
    if (!form.value.titre.trim()) {
      stepError.value = 'Le titre de la tâche est obligatoire.'
      return false
    }
  }

  if (currentStep.value === 1) {
    if (!form.value.responsable_id) {
      stepError.value = 'Veuillez sélectionner un responsable.'
      return false
    }
  }

  return true
}

function nextStep() {
  if (!validateCurrentStep()) {
    return
  }
  currentStep.value++
  furthestStep.value = Math.max(furthestStep.value, currentStep.value)
  stepError.value = ''
}

// ── Responsable auto-include ─────────────────────────────────────────────────

function onResponsableChange() {
  const rid = form.value.responsable_id
  if (rid && !form.value.assignee_ids.includes(rid)) {
    form.value.assignee_ids = [rid, ...form.value.assignee_ids]
  }
}

// ── File helpers ─────────────────────────────────────────────────────────────

function onFileChange(event) {
  for (const file of event.target.files) {
    if (file.size > 10 * 1024 * 1024) {
      toast.error(`"${file.name}" dépasse 10 Mo.`)
      continue
    }
    uploadedFiles.value.push(file)
  }
  event.target.value = ''
}

function onDrop(event) {
  for (const file of event.dataTransfer.files) {
    uploadedFiles.value.push(file)
  }
}

function addLink() {
  if (!newLink.value.url.trim()) {
    toast.error('Veuillez saisir une URL.')
    return
  }
  try {
    new URL(newLink.value.url)
  } catch {
    toast.error('URL invalide.')
    return
  }
  externalLinks.value.push({ url: newLink.value.url.trim(), title: newLink.value.title.trim() })
  newLink.value = { url: '', title: '' }
}

// ── Submit ───────────────────────────────────────────────────────────────────

async function handleSubmit() {
  if (!validateCurrentStep()) {
    return
  }

  loading.value = true

  try {
    // Ensure responsable is always in assignees
    if (form.value.responsable_id && !form.value.assignee_ids.includes(form.value.responsable_id)) {
      form.value.assignee_ids.unshift(form.value.responsable_id)
    }

    const fd = new FormData()
    fd.append('activite_id',            form.value.activite_id)
    fd.append('titre',                  form.value.titre)
    fd.append('description',            form.value.description || '')
    fd.append('objectif',               form.value.objectif || '')
    fd.append('statut',                 form.value.statut)
    fd.append('priorite',               form.value.priorite)
    if (form.value.echeance) {
      fd.append('echeance', form.value.echeance)
    }
    fd.append('responsable_id',         form.value.responsable_id)
    fd.append('validation_n1_required', form.value.validation_n1_required ? '1' : '0')
    fd.append('validation_n2_required', form.value.validation_n2_required ? '1' : '0')

    form.value.assignee_ids.forEach(id => fd.append('assignee_ids[]', id))

    uploadedFiles.value.forEach((file, i) => fd.append(`uploaded_files[${i}]`, file, file.name))

    if (externalLinks.value.length) {
      fd.append('external_links', JSON.stringify(externalLinks.value))
    }

    await api.post('/taches', fd)

    toast.success('Tâche créée avec succès !')
    emit('saved')
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      Object.values(errors).flat().forEach(e => toast.error(e))
    } else {
      toast.error(err.response?.data?.message ?? 'Erreur lors de la création.')
    }
  } finally {
    loading.value = false
  }
}

// ── Init ─────────────────────────────────────────────────────────────────────

onMounted(async () => {
  const activiteId = props.activiteContext?.id ?? props.activiteId

  if (!props.activiteContext) {
    try {
      const res = await api.get('/activites/mes-activites')
      activites.value = res.data.data ?? []
    } catch {
      // non-blocking
    }
  }

  if (activiteId) {
    await loadActivityMembers(activiteId)
  }
})
</script>

<style scoped>
@reference "tailwindcss";

.animate-fade-in {
  animation: fadeIn 0.25s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.97) translateY(-8px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
