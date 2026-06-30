<template>
    <div class="document-uploader">
        <!-- Drag & Drop Zone -->
        <div
            class="upload-zone"
            :class="{ 'drag-over': isDragging, 'uploading': uploading }"
            @dragenter.prevent="onDragEnter"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
            @click="triggerFileInput"
        >
            <div v-if="!uploading" class="upload-content">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="upload-icon" style="width:3rem;height:3rem;color:#64748b"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                <p class="upload-text">
                    Glissez-déposez vos fichiers ici ou
                    <span class="upload-link">cliquez pour parcourir</span>
                </p>
                <p class="upload-hint">
                    Taille max: {{ maxSizeMB }}MB | Formats: {{ allowedFormats }}
                </p>
            </div>

            <div v-else class="upload-progress">
                <div class="progress-circle">
                    <svg viewBox="0 0 100 100">
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            fill="none"
                            stroke="#e5e7eb"
                            stroke-width="8"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="8"
                            :stroke-dasharray="`${uploadProgress * 2.83} 283`"
                            transform="rotate(-90 50 50)"
                        />
                    </svg>
                    <span class="progress-text">{{ uploadProgress }}%</span>
                </div>
                <p class="upload-status">Téléchargement en cours...</p>
            </div>

            <input
                ref="fileInput"
                type="file"
                :multiple="multiple"
                :accept="accept"
                class="file-input"
                @change="onFileSelect"
            />
        </div>

        <!-- Selected Files Preview -->
        <div v-if="selectedFiles.length > 0 && !uploading" class="selected-files">
            <div class="selected-files-header">
                <h4 class="selected-files-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                    {{ selectedFiles.length }} fichier(s) sélectionné(s)
                </h4>
                <button
                    type="button"
                    class="btn-clear"
                    @click="clearFiles"
                    title="Effacer tout"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="files-list">
                <div
                    v-for="(file, index) in selectedFiles"
                    :key="index"
                    class="file-item"
                >
                    <div class="file-icon">
                        <span v-html="getFileIconSvg(file.type)"></span>
                    </div>
                    <div class="file-info">
                        <p class="file-name">{{ file.name }}</p>
                        <p class="file-size">{{ formatFileSize(file.size) }}</p>
                    </div>
                    <button
                        type="button"
                        class="btn-remove"
                        @click="removeFile(index)"
                        title="Supprimer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </button>
                </div>
            </div>

            <!-- Upload Options -->
            <div class="upload-options">
                <div class="form-group">
                    <label for="description">Description (optionnel)</label>
                    <textarea
                        id="description"
                        v-model="description"
                        class="form-control"
                        rows="2"
                        placeholder="Ajoutez une description pour ces fichiers..."
                    ></textarea>
                </div>

            </div>

            <!-- Upload Button -->
            <div class="upload-actions">
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="uploading"
                    @click="uploadFiles"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                    Télécharger {{ selectedFiles.length }} fichier(s)
                </button>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="alert alert-danger">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useDocuments } from '@/composables/useDocuments';

const props = defineProps({
    documentableType: {
        type: String,
        required: true,
    },
    documentableId: {
        type: Number,
        required: true,
    },
    multiple: {
        type: Boolean,
        default: true,
    },
    accept: {
        type: String,
        default: '*/*',
    },
    maxSize: {
        type: Number,
        default: 10240, // 10MB in KB
    },
});

const emit = defineEmits(['uploaded', 'error']);

const { uploadDocuments, uploading, uploadProgress, getFileIcon, formatFileSize } = useDocuments();

const getFileIconSvg = (mimeType) => {
    if (mimeType && mimeType.startsWith('image/')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0z"/></svg>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>';
};

const fileInput = ref(null);
const selectedFiles = ref([]);
const isDragging = ref(false);
const error = ref(null);
const description = ref('');


const maxSizeMB = computed(() => Math.round(props.maxSize / 1024));
const allowedFormats = computed(() => {
    if (props.accept === '*/*') return 'Tous les formats';
    return props.accept;
});

const triggerFileInput = () => {
    fileInput.value?.click();
};

const onFileSelect = (event) => {
    const files = Array.from(event.target.files);
    addFiles(files);
};

const onDragEnter = () => {
    isDragging.value = true;
};

const onDragOver = () => {
    isDragging.value = true;
};

const onDragLeave = () => {
    isDragging.value = false;
};

const onDrop = (event) => {
    isDragging.value = false;
    const files = Array.from(event.dataTransfer.files);
    addFiles(files);
};

const addFiles = (files) => {
    error.value = null;

    // Validate files
    for (const file of files) {
        // Check file size
        if (file.size > props.maxSize * 1024) {
            error.value = `Le fichier "${file.name}" dépasse la taille maximale de ${maxSizeMB.value}MB`;
            return;
        }
    }

    if (props.multiple) {
        selectedFiles.value.push(...files);
    } else {
        selectedFiles.value = [files[0]];
    }
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
};

const clearFiles = () => {
    selectedFiles.value = [];
    description.value = '';
    error.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const uploadFiles = async () => {
    if (selectedFiles.value.length === 0) return;

    error.value = null;

    try {
        const result = await uploadDocuments(
            selectedFiles.value,
            props.documentableType,
            props.documentableId,
            {
                description: description.value,
            }
        );

        emit('uploaded', result);
        clearFiles();
    } catch (err) {
        error.value = err.response?.data?.message || 'Erreur lors du téléchargement';
        emit('error', err);
    }
};
</script>

<style scoped>
.document-uploader {
    width: 100%;
}

.upload-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-zone:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.upload-zone.drag-over {
    border-color: #3b82f6;
    background: #dbeafe;
    transform: scale(1.02);
}

.upload-zone.uploading {
    cursor: not-allowed;
    opacity: 0.7;
}

.upload-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.upload-icon {
    font-size: 3rem;
    color: #64748b;
}

.upload-text {
    font-size: 1.125rem;
    color: #475569;
    margin: 0;
}

.upload-link {
    color: #3b82f6;
    font-weight: 600;
}

.upload-hint {
    font-size: 0.875rem;
    color: #94a3b8;
    margin: 0;
}

.file-input {
    display: none;
}

.upload-progress {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.progress-circle {
    position: relative;
    width: 100px;
    height: 100px;
}

.progress-circle svg {
    transform: rotate(-90deg);
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.5rem;
    font-weight: 600;
    color: #3b82f6;
}

.upload-status {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
}

.selected-files {
    margin-top: 1.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    background: white;
}

.selected-files-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.selected-files-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-clear {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-clear:hover {
    background: #fee2e2;
}

.files-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.file-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
}

.file-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e0e7ff;
    border-radius: 8px;
    color: #4f46e5;
    font-size: 1.25rem;
}

.file-info {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-size {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
}

.btn-remove {
    flex-shrink: 0;
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-remove:hover {
    background: #fee2e2;
}

.upload-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
}

.form-control {
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.upload-actions {
    display: flex;
    justify-content: flex-end;
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
    background: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.alert {
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
</style>
