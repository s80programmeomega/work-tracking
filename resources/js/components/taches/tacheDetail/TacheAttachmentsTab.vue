<!-- resources\js\components\taches\tacheDetail\TacheAttachmentsTab.vue -->
<template>
  <div class="space-y-4">
    <!-- Upload section -->
    <div v-if="permissions.can_add_attachments" class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-3 p-6">
      <input 
        ref="fileInput"
        type="file" 
        multiple 
        @change="handleFileSelect" 
        class="hidden"
      />
      <button 
        @click="$refs.fileInput.click()"
        class="w-full flex flex-col items-center justify-center gap-3 py-8 text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
      >
        <i class="fas fa-cloud-upload-alt text-4xl"></i>
        <span class="font-semibold">Cliquez pour ajouter des fichiers</span>
        <span class="text-sm">ou glissez-déposez vos fichiers ici</span>
      </button>
    </div>

    <!-- Files list -->
    <div v-if="tache.attachments && tache.attachments.length > 0" ref="staggerRef" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="attachment in tache.attachments"
        :key="attachment.id"
        :id="`attachment-${attachment.id}`"
        class="stagger-item bg-white dark:bg-gray-800 rounded-3 p-4 border-2 border-gray-200 dark:border-gray-700 hover:border-brand-500 transition-colors"
        :class="{ 'ring-2 ring-brand-500': highlightedAttachment === attachment.id }"
      >
        <div class="flex items-start gap-4">
          <!-- Icon -->
          <div class="flex-shrink-0 w-12 h-12 rounded-3 flex items-center justify-center">
            <i :class="['fas', getFileIcon(attachment.mime_type), 'text-white text-xl']"></i>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-1 truncate">
              {{ attachment.original_name }}
            </h4>
            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
              <span>{{ formatFileSize(attachment.file_size) }}</span>
              <span>•</span>
              <span>{{ formatDate(attachment.created_at) }}</span>
            </div>
            <div v-if="attachment.uploaded_by" class="flex items-center gap-2 mt-2">
              <img 
                v-if="attachment.uploaded_by.avatar" 
                :src="attachment.uploaded_by.avatar" 
                :alt="attachment.uploaded_by.nom"
                class="w-6 h-6 rounded-full"
              />
              <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ attachment.uploaded_by.nom }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-2">
            <button 
              @click="downloadFile(attachment)"
              class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-3 transition-colors"
              title="Télécharger"
            >
              <i class="fas fa-download"></i>
            </button>
            <button 
              v-if="permissions.can_update"
              @click="deleteFile(attachment)"
              class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors"
              title="Supprimer"
            >
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <i class="fas fa-file text-6xl text-gray-300 dark:text-gray-700 mb-4"></i>
      <p class="text-gray-600 dark:text-gray-400">Aucun fichier attaché</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import { useStagger } from '@/composables/useAnimations';
import { useRoute } from 'vue-router';
import api from '@/api/axios';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  permissions: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['refresh']);

const route = useRoute();
const { showSuccess, showError } = useNotifications();
const { staggerRef, applyStagger } = useStagger(50);
const fileInput = ref(null);
const highlightedAttachment = ref(null);

watch(() => props.tache?.attachments, async () => {
  await nextTick();
  applyStagger();
}, { immediate: true });

const handleFileSelect = async (event) => {
  const files = Array.from(event.target.files);
  if (files.length === 0) return;

  const formData = new FormData();
  files.forEach(file => {
    formData.append('files[]', file);
  });

  try {
    await api.post(`/taches/${props.tache.id}/attachments`, formData, {
      headers: {
        
      }
    });
    
    showSuccess('Fichier(s) ajouté(s) avec succès');
    emit('refresh');
    fileInput.value.value = '';
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de l\'ajout du fichier');
  }
};

const downloadFile = (attachment) => {
  window.open(`/taches/${props.tache.id}/attachments/${attachment.id}/download`, '_blank');
};

const deleteFile = async (attachment) => {
  if (!confirm(`Supprimer le fichier "${attachment.original_name}" ?`)) return;

  try {
    await api.delete(`/taches/${props.tache.id}/attachments/${attachment.id}`);
    showSuccess('Fichier supprimé');
    emit('refresh');
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de la suppression');
  }
};

const getFileIcon = (mimeType) => {
  const icons = {
    'application/pdf': 'fa-file-pdf',
    'application/msword': 'fa-file-word',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'fa-file-word',
    'application/vnd.ms-excel': 'fa-file-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'fa-file-excel',
    'application/zip': 'fa-file-archive',
    'text/plain': 'fa-file-alt',
  };

  if (mimeType?.startsWith('image/')) return 'fa-file-image';
  if (mimeType?.startsWith('video/')) return 'fa-file-video';
  if (mimeType?.startsWith('audio/')) return 'fa-file-audio';

  return icons[mimeType] || 'fa-file';
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

onMounted(() => {
  // Highlight attachment from URL
  if (route.query.highlight) {
    const match = route.query.highlight.match(/attachment-(\d+)/);
    if (match) {
      highlightedAttachment.value = parseInt(match[1]);
      setTimeout(() => {
        const element = document.getElementById(route.query.highlight);
        element?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 300);
    }
  }
});
</script>