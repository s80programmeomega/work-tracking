<!-- resources/js/components/taches/TacheDetailModal.vue -->
<template>
  <div dusk="tache-detail-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4"
       @click.self="$emit('close')">

    <div ref="dialogRef" :style="dragStyle" class="bg-white dark:bg-gray-800 rounded-3 w-full overflow-hidden flex flex-col transition-all duration-300"
         :class="modalSizeClass">

      <!-- Header unifié -->
      <div ref="handleRef" class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 cursor-move select-none">
          <div class="flex justify-between items-start">
          <!-- Titre et badges -->
          <div class="flex-1 mr-4">
            <!-- Titre inline-editable -->
            <div class="mb-2">
              <input
                v-if="editing.field === 'titre'"
                v-model="editing.value"
                @blur="saveEdit"
                @keydown.enter.prevent="saveEdit"
                @keydown.escape="cancelEdit"
                autofocus
                class="w-full text-2xl font-bold text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-b-2 border-brand-500 focus:outline-none px-1 py-0.5 rounded"
              />
              <h2
                v-else
                class="text-2xl font-bold text-gray-900 dark:text-white"
                :class="localTache.permissions?.can_edit ? 'cursor-pointer hover:text-brand-600 dark:hover:text-brand-400 group' : ''"
                :title="localTache.permissions?.can_edit ? 'Cliquer pour modifier' : ''"
                @click="startEdit('titre', localTache.titre)"
              >
                {{ localTache.titre }}
                <svg v-if="localTache.permissions?.can_edit" class="inline w-4 h-4 ml-1 opacity-0 group-hover:opacity-50 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </h2>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
              <!-- Badge statut inline-editable -->
              <div v-if="editing.field === 'statut'">
                <select
                  v-model="editing.value"
                  @change="saveEdit"
                  @blur="cancelEdit"
                  @keydown.escape="cancelEdit"
                  autofocus
                  class="text-xs font-semibold rounded-full px-3 py-1 border border-brand-400 focus:outline-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                >
                  <option value="a_faire">À faire</option>
                  <option value="en_cours">En cours</option>
                  <option value="termine">Terminé</option>
                </select>
              </div>
              <span
                v-else
                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full "
                :class="[getStatusClass(localTache.statut), localTache.permissions?.can_edit ? 'cursor-pointer hover:ring-2 hover:ring-brand-400' : '']"
                :title="localTache.permissions?.can_edit ? 'Cliquer pour modifier le statut' : ''"
                @click="startEdit('statut', localTache.statut)"
              >
                <span class="w-2 h-2 rounded-full mr-2" :class="getStatusDotClass(localTache.statut)"></span>
                {{ localTache.statut_label }}
              </span>

              <!-- Badge priorité inline-editable -->
              <div v-if="editing.field === 'priorite'">
                <select
                  v-model="editing.value"
                  @change="saveEdit"
                  @blur="cancelEdit"
                  @keydown.escape="cancelEdit"
                  autofocus
                  class="text-xs font-semibold rounded-full px-3 py-1 border border-brand-400 focus:outline-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                >
                  <option value="faible">🟢 Faible</option>
                  <option value="moyenne">🟡 Moyenne</option>
                  <option value="elevee">🟠 Élevée</option>
                  <option value="critique">🔴 Critique</option>
                </select>
              </div>
              <span
                v-else
                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full "
                :class="[getPriorityClass(localTache.priorite), localTache.permissions?.can_edit ? 'cursor-pointer hover:ring-2 hover:ring-brand-400' : '']"
                :title="localTache.permissions?.can_edit ? 'Cliquer pour modifier la priorité' : ''"
                @click="startEdit('priorite', localTache.priorite)"
              >
                {{ getPriorityIcon(localTache.priorite) }} {{ localTache.priorite_label }}
              </span>

              <!-- Badges validation -->
              <span v-if="localTache.validation?.n2_validated_at"
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                ✓✓ Validé N2
              </span>
              <span v-else-if="localTache.validation?.n1_validated_at"
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                ✓ Validé N1
              </span>
            </div>
          </div>

          <!-- Actions header -->
          <div class="flex items-center gap-2 shrink-0">
            <!-- Lien vers la page complète -->
            <router-link
              :to="`/taches/${localTache.id}`"
              class="p-2 text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
              title="Ouvrir la page complète"
              @click="$emit('close')"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
            </router-link>

            <!-- Bascule mode détaillé -->
            <button
              v-if="!isDetailedView"
              @click="enableDetailedView('details')"
              class="p-2 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors"
              title="Vue détaillée">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
            </button>

            <!-- Bouton refresh -->
            <button
              @click="refreshTask"
              :disabled="isRefreshing"
              class="p-2 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors disabled:opacity-50"
              title="Actualiser">
              <svg class="w-5 h-5" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>

            <button dusk="modal-close-btn" @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors p-2">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Onglets - Seulement en mode détaillé -->
        <div v-if="isDetailedView" class="mt-4 flex gap-4 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="px-4 py-2 font-medium rounded-t-lg transition-all border-b-2 whitespace-nowrap"
            :class="activeTab === tab.id
              ? 'border-brand-500 text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/20'
              : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800'"
          >
            <span class="flex items-center gap-2">
              {{ tab.label }}
              <span v-if="tab.count" class="px-2 py-0.5 text-xs rounded-full"
                    :class="activeTab === tab.id
                      ? 'bg-brand-200 dark:bg-brand-800 text-brand-800 dark:text-brand-200'
                      : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                {{ tab.count }}
              </span>
            </span>
          </button>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="flex-1 overflow-y-auto">

        <!-- MODE RAPIDE -->
        <div v-if="!isDetailedView" class="px-8 py-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Colonne principale -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Description -->
              <SectionCollapsible title="Description" :default-open="!!localTache.description">
                <div v-if="editing.field === 'description'">
                  <textarea
                    v-model="editing.value"
                    @blur="saveEdit"
                    @keydown.escape="cancelEdit"
                    autofocus
                    rows="4"
                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 border border-brand-400 rounded-3 p-3 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-y"
                  />
                  <p class="text-xs text-gray-400 mt-1">Cliquer ailleurs pour enregistrer · Echap pour annuler</p>
                </div>
                <p
                  v-else
                  class="text-gray-700 dark:text-gray-300 whitespace-pre-line"
                  :class="localTache.permissions?.can_edit ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 rounded-3 p-2 -m-2 transition-colors group' : ''"
                  :title="localTache.permissions?.can_edit ? 'Cliquer pour modifier' : ''"
                  @click="startEdit('description', localTache.description || '')"
                >
                  <span v-if="localTache.description">{{ localTache.description }}</span>
                  <span v-else class="text-gray-400 italic">
                    Aucune description
                    <span v-if="localTache.permissions?.can_edit" class="text-brand-400 not-italic"> — cliquer pour ajouter</span>
                  </span>
                </p>
              </SectionCollapsible>

              <!-- Objectif -->
              <SectionCollapsible title="Objectif" :default-open="!!localTache.objectif">
                <div v-if="editing.field === 'objectif'">
                  <textarea
                    v-model="editing.value"
                    @blur="saveEdit"
                    @keydown.escape="cancelEdit"
                    autofocus
                    rows="4"
                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 border border-brand-400 rounded-3 p-3 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-y"
                  />
                  <p class="text-xs text-gray-400 mt-1">Cliquer ailleurs pour enregistrer · Echap pour annuler</p>
                </div>
                <p
                  v-else
                  class="text-gray-700 dark:text-gray-300 whitespace-pre-line"
                  :class="localTache.permissions?.can_edit ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 rounded-3 p-2 -m-2 transition-colors' : ''"
                  :title="localTache.permissions?.can_edit ? 'Cliquer pour modifier' : ''"
                  @click="startEdit('objectif', localTache.objectif || '')"
                >
                  <span v-if="localTache.objectif">{{ localTache.objectif }}</span>
                  <span v-else class="text-gray-400 italic">
                    Aucun objectif défini
                    <span v-if="localTache.permissions?.can_edit" class="text-brand-400 not-italic"> — cliquer pour ajouter</span>
                  </span>
                </p>
              </SectionCollapsible>

              <!-- Indicateurs -->
              <SectionCollapsible title="Indicateurs de résultats" :default-open="!!localTache.indicateurs_resultats">
                <div v-if="editing.field === 'indicateurs_resultats'">
                  <textarea
                    v-model="editing.value"
                    @blur="saveEdit"
                    @keydown.escape="cancelEdit"
                    autofocus
                    rows="4"
                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 border border-brand-400 rounded-3 p-3 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-y"
                  />
                  <p class="text-xs text-gray-400 mt-1">Cliquer ailleurs pour enregistrer · Echap pour annuler</p>
                </div>
                <p
                  v-else
                  class="text-gray-700 dark:text-gray-300 whitespace-pre-line"
                  :class="localTache.permissions?.can_edit ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 rounded-3 p-2 -m-2 transition-colors' : ''"
                  :title="localTache.permissions?.can_edit ? 'Cliquer pour modifier' : ''"
                  @click="startEdit('indicateurs_resultats', localTache.indicateurs_resultats || '')"
                >
                  <span v-if="localTache.indicateurs_resultats">{{ localTache.indicateurs_resultats }}</span>
                  <span v-else class="text-gray-400 italic">
                    Aucun indicateur défini
                    <span v-if="localTache.permissions?.can_edit" class="text-brand-400 not-italic"> — cliquer pour ajouter</span>
                  </span>
                </p>
              </SectionCollapsible>

              <!-- Sous-tâches -->
              <SectionCollapsible title="Sous-tâches" :default-open="true">
                <SousTacheList
                  :tache-id="localTache.id"
                  :parent-echeance="localTache.echeance"
                  :can-create="localTache.permissions?.can_create_subtask ?? false"
                  :can-edit="localTache.permissions?.can_inline_edit ?? false"
                  :can-delete="localTache.permissions?.can_delete ?? false"
                  :can-assign="localTache.permissions?.can_edit ?? false"
                />
              </SectionCollapsible>

              <!-- Commentaire récent -->
              <SectionCollapsible v-if="latestComment" title="Dernier commentaire" :default-open="true">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-3 p-4">
                  <p class="text-gray-700 dark:text-gray-300">{{ latestComment.content }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Par {{ latestComment.user?.name }} • {{ formatRelativeTime(latestComment.created_at) }}
                  </p>
                </div>
                <button
                  @click="enableDetailedView('commentaires')"
                  class="mt-2 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400">
                  Voir tous les commentaires →
                </button>
              </SectionCollapsible>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
              <!-- Actions rapides — masquées en mode lecture seule -->
              <QuickActionsPanel
                v-if="!props.readonly"
                :tache="localTache"
                @validate-n1="$emit('validate-n1', localTache)"
                @validate-n2="$emit('validate-n2', localTache)"
                @complete="handleCompleteTask"
              />

              <!-- Échéance inline-editable -->
              <div class="rounded-3 border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 mb-3">
                  <div class="w-8 h-8 rounded-3 bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Échéance</h4>
                </div>
                <div v-if="editing.field === 'echeance'">
                  <DatePicker
                    v-model="editing.value"
                    :enable-time-picker="false"
                    auto-apply
                    :format="'dd-MM-yyyy'"
                    :locale="'fr'"
                    :dark="isDark"
                    placeholder="Sélectionner une date"
                    @update:model-value="saveEdit"
                    @keydown.escape="cancelEdit"
                    inline
                    class="w-full"
                  />
                </div>
                <p
                  v-else
                  class="text-sm"
                  :class="[
                    localTache.is_overdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-400',
                    localTache.permissions?.can_edit ? 'cursor-pointer hover:text-brand-600 dark:hover:text-brand-400' : ''
                  ]"
                  :title="localTache.permissions?.can_edit ? 'Cliquer pour modifier l\'échéance' : ''"
                  @click="startEdit('echeance', localTache.echeance)"
                >
                  <span v-if="localTache.echeance">
                    {{ localTache.is_overdue ? '⚠ ' : '' }}{{ formatDate(localTache.echeance) }}
                  </span>
                  <span v-else class="text-gray-400 italic">
                    Non définie
                    <span v-if="localTache.permissions?.can_edit" class="text-brand-400 not-italic"> — cliquer pour ajouter</span>
                  </span>
                </p>
              </div>

              <!-- Taux de réalisation inline-editable -->
              <div class="rounded-3 border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-3 bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                    </div>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Avancement</h4>
                  </div>
                  <span class="text-sm font-bold" :class="progressColorClass(localTache.taux_realisation)">
                    {{ localTache.taux_realisation ?? 0 }}%
                  </span>
                </div>

                <!-- Barre de progression cliquable -->
                <div
                  v-if="editing.field !== 'taux_realisation'"
                  class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-3"
                  :class="localTache.permissions?.can_edit ? 'cursor-pointer' : ''"
                  :title="localTache.permissions?.can_edit ? 'Cliquer pour modifier l\'avancement' : ''"
                  @click="startEdit('taux_realisation', localTache.taux_realisation ?? 0)"
                >
                  <div
                    class="h-2.5 rounded-full transition-all"
                    :class="progressBarClass(localTache.taux_realisation)"
                    :style="{ width: (localTache.taux_realisation ?? 0) + '%' }"
                  />
                </div>

                <!-- Contrôles d'édition: slider + number -->
                <div v-if="editing.field === 'taux_realisation'" class="space-y-2">
                  <div class="flex items-center gap-3">
                    <input
                      type="range"
                      min="0"
                      max="100"
                      step="5"
                      v-model.number="editing.value"
                      class="flex-1 accent-brand-500"
                    />
                    <input
                      type="number"
                      min="0"
                      max="100"
                      v-model.number="editing.value"
                      class="w-16 text-sm text-center border border-brand-400 rounded-3 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    />
                    <span class="text-sm text-gray-500 dark:text-gray-400">%</span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div
                      class="h-2.5 rounded-full transition-all"
                      :class="progressBarClass(editing.value)"
                      :style="{ width: editing.value + '%' }"
                    />
                  </div>
                  <div class="flex gap-2 justify-end">
                    <button
                      @click="cancelEdit"
                      class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors"
                    >
                      Annuler
                    </button>
                    <button
                      @click="saveEdit"
                      class="px-3 py-1 text-xs bg-brand-500 hover:bg-brand-600 text-white rounded-3 transition-colors"
                    >
                      Enregistrer
                    </button>
                  </div>
                </div>
              </div>

              <!-- Informations essentielles -->
              <EssentialInfoPanel :tache="localTache" />

              <!-- Assignés -->
              <AssigneesPanel
                :tache="localTache"
                :assignees="localTache.assignees"
                :permissions="localTache.permissions"
                @updated="(fresh) => { localTache = fresh }"
              />

              <!-- Labels -->
              <LabelsPanel v-if="localTache.labels?.length > 0" :labels="localTache.labels" />
            </div>
          </div>
        </div>

        <!-- MODE DÉTAILLÉ -->
        <div v-else class="px-8 py-6">

          <!-- Onglet Détails -->
          <div v-show="activeTab === 'details'">
            <DetailedTaskView :tache="localTache" @refresh="refreshTask" />
          </div>

          <!-- Onglet Sous-tâches -->
          <div v-show="activeTab === 'sous-taches'">
            <SousTacheList
              :tache-id="localTache.id"
              :parent-echeance="localTache.echeance"
              :can-create="localTache.permissions?.can_create_subtask ?? false"
              :can-edit="localTache.permissions?.can_inline_edit ?? false"
              :can-delete="localTache.permissions?.can_delete ?? false"
              :can-assign="localTache.permissions?.can_edit ?? false"
            />
          </div>

          <!-- Onglet Résultats -->
          <div v-show="activeTab === 'resultats'">
            <ResultatsSection
              :tache="localTache"
              @resultat-added="handleResultatAdded"
              @refresh="refreshTask"
            />
          </div>

          <!-- Onglet Documents -->
          <div v-show="activeTab === 'documents'">
            <DocumentSection
              v-if="localTache?.id"
              documentable-type="App\Models\Tache"
              :documentable-id="localTache.id"
              :current-user-id="currentUser?.id"
            />
          </div>

        </div>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          <span>Créée le {{ formatDate(localTache.created_at) }}</span>
          <span v-if="localTache.updated_at !== localTache.created_at" class="ml-3">
            • Modifiée le {{ formatDate(localTache.updated_at) }}
          </span>
        </div>

        <div class="flex gap-3 items-center">
          <!-- Indicateur de sauvegarde en cours -->
          <span v-if="isSaving" class="text-xs text-brand-500 flex items-center gap-1">
            <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            Enregistrement…
          </span>
          <span v-else-if="lastSaved" class="text-xs text-green-500">✓ Enregistré</span>

          <button
            @click="$emit('close')"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all"
          >
            Fermer
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useDraggable } from '@/composables/useDraggable'

const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()
onMounted(attachHandle)
import { useTaches } from '@/composables/useTaches'
import api from '@/api/axios'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

import QuickActionsPanel from './panels/QuickActionsPanel.vue'
import EssentialInfoPanel from './panels/EssentialInfoPanel.vue'
import AssigneesPanel from './panels/AssigneesPanel.vue'
import LabelsPanel from './panels/LabelsPanel.vue'
import SectionCollapsible from './panels/SectionCollapsible.vue'
import DetailedTaskView from './DetailedTaskView.vue'
import ResultatsSection from './ResultatsSection.vue'
import CommentSection from '@/components/comments/CommentSection.vue'
import DocumentSection from '@/components/common/DocumentSection.vue'
import SousTacheList from '@/components/taches/SousTacheList.vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true,
  },
  // En mode lecture seule, toutes les actions d'édition/validation sont masquées.
  // Utilisé par la page de recherche globale pour prévisualiser une tâche sans modifier.
  readonly: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'edit', 'validate-n1', 'validate-n2', 'resultat-added', 'refresh'])

const { completeTache } = useTaches()

// État réactif
const isDetailedView = ref(false)
const activeTab = ref('details')
const latestComment = ref(null)
const isRefreshing = ref(false)
const localTache = ref({ ...props.tache })
const isSaving = ref(false)
const lastSaved = ref(false)

// Inline edit state
const editing = reactive({ field: null, value: null })
const isDark = computed(() => document.documentElement.classList.contains('dark'))

// Computed
const modalSizeClass = computed(() =>
  isDetailedView.value ? 'max-w-7xl max-h-[95vh]' : 'max-w-4xl max-h-[90vh]'
)

const tabs = computed(() => [
  { id: 'details', label: 'Détails' },
  { id: 'sous-taches', label: 'Sous-tâches' },
  { id: 'documents', label: 'Documents de resultat', count: localTache.value.documents_count || 0 },
])

const currentUser = computed(() => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
})

const shouldUseDetailedView = computed(() => {
  const t = localTache.value
  return (
    (t.comments_count > 3) ||
    (t.documents_count > 2) ||
    (t.resultats_count > 5) ||
    (t.description?.length > 500) ||
    (t.assignees?.length > 5)
  )
})

const formatDateForApi = (date) => {
  if (!date) return null
  const d = date instanceof Date ? date : new Date(date)
  if (isNaN(d.getTime())) return null
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Inline edit methods
const startEdit = (field, value) => {
  if (!localTache.value.permissions?.can_edit) return
  editing.field = field
  editing.value = field === 'echeance' ? (value ? new Date(value) : null) : value
}

const cancelEdit = () => {
  editing.field = null
  editing.value = null
}

const saveEdit = async () => {
  const { field, value } = editing
  if (!field) return

  const apiValue = field === 'echeance' ? formatDateForApi(value) : value

  // Pour les champs texte, on considère null == ''
  const oldValue = localTache.value[field]
  const normalizedOld = oldValue ?? ''
  const normalizedNew = apiValue ?? ''
  if (normalizedNew === normalizedOld) {
    cancelEdit()
    return
  }

  isSaving.value = true
  cancelEdit()

  try {
    await api.patch(`/taches/${localTache.value.id}`, { [field]: apiValue })
    localTache.value[field] = apiValue

    // Rafraîchir les champs dérivés (statut_label, priorite_label, etc.) si besoin
    if (field === 'statut' || field === 'priorite') {
      await refreshTask()
    }

    lastSaved.value = true
    setTimeout(() => { lastSaved.value = false }, 2000)
    emit('refresh', localTache.value)
  } catch (err) {
    console.error('Erreur mise à jour:', err)
    // Revenir à l'ancienne valeur en cas d'erreur
    localTache.value[field] = oldValue
  } finally {
    isSaving.value = false
  }
}

// Méthodes
const enableDetailedView = (tab = 'details') => {
  isDetailedView.value = true
  activeTab.value = tab
}

const handleCompleteTask = async () => {
  if (!localTache.value.permissions?.can_complete) {
    alert('Vous n\'avez pas la permission de marquer cette tâche comme terminée')
    return
  }

  try {
    await completeTache(localTache.value.id)
    emit('close')
  } catch (error) {
    console.error('Error completing task:', error)
    alert(error.response?.data?.message || 'Erreur lors de la complétion de la tâche')
  }
}

const handleResultatAdded = () => {
  emit('resultat-added')
  refreshTask()
}

const refreshTask = async () => {
  if (isRefreshing.value) return

  isRefreshing.value = true
  try {
    const { data } = await api.get(`/taches/${localTache.value.id}`)
    localTache.value = data.data
    emit('refresh', data.data)
  } catch (error) {
    console.error('Error refreshing task:', error)
  } finally {
    isRefreshing.value = false
  }
}

// Méthodes utilitaires
const getStatusClass = (statut) => {
  const classes = {
    'a_faire': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
  }
  return classes[statut] || classes.a_faire
}

const getStatusDotClass = (statut) => {
  const classes = {
    'a_faire': 'bg-gray-500',
    'en_cours': 'bg-blue-500',
    'termine': 'bg-green-500'
  }
  return classes[statut] || classes.a_faire
}

const getPriorityClass = (priorite) => {
  const classes = {
    'faible': 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    'moyenne': 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
    'elevee': 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    'critique': 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
  }
  return classes[priorite] || classes.moyenne
}

const getPriorityIcon = (priorite) => {
  const icons = {
    faible: '🟢',
    moyenne: '🟡',
    elevee: '🟠',
    critique: '🔴'
  }
  return icons[priorite] || '🟡'
}

const progressColorClass = (val) => {
  const v = val ?? 0
  if (v >= 75) return 'text-green-600 dark:text-green-400'
  if (v >= 50) return 'text-blue-600 dark:text-blue-400'
  if (v >= 25) return 'text-amber-600 dark:text-amber-400'
  return 'text-red-600 dark:text-red-400'
}

const progressBarClass = (val) => {
  const v = val ?? 0
  if (v >= 75) return 'bg-green-500'
  if (v >= 50) return 'bg-blue-500'
  if (v >= 25) return 'bg-amber-500'
  return 'bg-red-500'
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatRelativeTime = (date) => {
  const now = new Date()
  const diffMs = now - new Date(date)
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 60) return `il y a ${diffMins} min`
  if (diffHours < 24) return `il y a ${diffHours} h`
  if (diffDays < 7) return `il y a ${diffDays} j`
  return formatDate(date)
}

// Lifecycle
const handleKeydown = (e) => {
  if (e.key === 'Escape' && editing.field) cancelEdit()
}

onMounted(() => {
  if (shouldUseDetailedView.value) {
    isDetailedView.value = true
  }
  window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>
