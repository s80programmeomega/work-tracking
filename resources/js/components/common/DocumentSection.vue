<template>
    <div class="document-section">
        <!-- Section Header -->
        <div class="section-header">
            <div class="header-left">
                <div class="icon-wrapper">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div>
                    <h3 class="section-title">Documents & Fichiers</h3>
                    <p class="section-subtitle">
                        {{ totalDocuments }} document(s) · {{ formatTotalSize }}
                    </p>
                </div>
            </div>
            <div class="header-right">
                <button
                    v-if="!showUploader"
                    type="button"
                    class="btn btn-primary"
                    @click="toggleUploader"
                >
                    <i class="fas fa-plus"></i>
                    Ajouter des fichiers
                </button>
                <button
                    v-else
                    type="button"
                    class="btn btn-secondary"
                    @click="toggleUploader"
                >
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
            </div>
        </div>

        <!-- Uploader (collapsed by default) -->
        <transition name="slide-down">
            <div v-if="showUploader" class="uploader-wrapper">
                <DocumentUploader
                    :documentable-type="documentableType"
                    :documentable-id="documentableId"
                    :multiple="true"
                    @uploaded="onUploaded"
                    @error="onUploadError"
                />
            </div>
        </transition>

        <!-- Documents List -->
        <div class="documents-wrapper">
            <DocumentList
                :documents="documents"
                :loading="loading"
                :current-user-id="currentUserId"
                @download="onDownload"
                @edit="onEdit"
                @delete="onDelete"
                @refresh="refreshDocuments"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDocuments } from '@/composables/useDocuments';
import DocumentUploader from './DocumentUploader.vue';
import DocumentList from './DocumentList.vue';

const props = defineProps({
    documentableType: {
        type: String,
        required: true,
    },
    documentableId: {
        type: Number,
        required: true,
    },
    currentUserId: {
        type: Number,
        required: true,
    },
    autoLoad: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['uploaded', 'download', 'edit', 'delete']);

const { documents, loading, fetchDocuments, totalDocuments, totalSize, formatFileSize } = useDocuments();

const showUploader = ref(false);

const formatTotalSize = computed(() => {
    return formatFileSize(totalSize.value);
});

const toggleUploader = () => {
    showUploader.value = !showUploader.value;
};

const loadDocuments = async () => {
    try {
        await fetchDocuments(props.documentableType, props.documentableId);
    } catch (error) {
        console.error('Error loading documents:', error);
    }
};

const refreshDocuments = () => {
    loadDocuments();
};

const onUploaded = (result) => {
    showUploader.value = false;
    refreshDocuments();
    emit('uploaded', result);
};

const onUploadError = (error) => {
    console.error('Upload error:', error);
};

const onDownload = (document) => {
    emit('download', document);
};

const onEdit = (document) => {
    emit('edit', document);
};

const onDelete = (document) => {
    emit('delete', document);
};

onMounted(() => {
    if (props.autoLoad) {
        loadDocuments();
    }
});

// Expose refresh method for parent components
defineExpose({
    refreshDocuments,
});
</script>

<style scoped>
.document-section {
    width: 100%;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.icon-wrapper {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    font-size: 1.5rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
}

.section-subtitle {
    font-size: 0.875rem;
    margin: 0;
    opacity: 0.9;
}

.header-right {
    display: flex;
    gap: 0.75rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: white;
    color: #667eea;
}

.btn-primary:hover {
    background: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
}

.uploader-wrapper {
    padding: 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.documents-wrapper {
    padding: 1.5rem;
}

/* Slide down transition */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}
</style>
