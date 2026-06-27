<template>
    <div class="document-list">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <svg class="animate-spin h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <p>Chargement des documents...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="documents.length === 0" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/></svg>
            <p>Aucun document disponible</p>
            <small>Téléchargez des fichiers pour commencer</small>
        </div>

        <!-- Documents Grid -->
        <div v-else class="documents-grid">
            <div
                v-for="document in documents"
                :key="document.id"
                class="document-card"
            >
                <!-- Thumbnail/Icon -->
                <div class="document-preview">
                    <img
                        v-if="document.thumbnail_url"
                        :src="document.thumbnail_url"
                        :alt="document.nom"
                        class="document-thumbnail"
                    />
                    <div v-else class="document-icon-wrapper">
                        <span class="document-icon" v-html="getFileIconSvg(document.mime_type)"></span>
                    </div>

                    <!-- Actions Overlay -->
                    <div class="document-actions">
                        <button
                            type="button"
                            class="action-btn"
                            @click="downloadDoc(document)"
                            title="Télécharger"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        </button>
                        <button
                            v-if="canEdit(document)"
                            type="button"
                            class="action-btn"
                            @click="editDoc(document)"
                            title="Modifier"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                        </button>
                        <button
                            v-if="canDelete(document)"
                            type="button"
                            class="action-btn action-danger"
                            @click="confirmDelete(document)"
                            title="Supprimer"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Document Info -->
                <div class="document-info">
                    <h4 class="document-name" :title="document.nom">
                        {{ document.nom }}
                    </h4>
                    <div class="document-meta">
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 8.284-7.164 15-16 15m15.9-8.97A5 5 0 0 0 7.207 7.2a5.002 5.002 0 0 0-3.212 9.3"/></svg>
                            {{ document.formatted_size }}
                        </span>
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            {{ document.download_count }}
                        </span>
                    </div>
                    <div class="document-footer">
                        <span class="document-user">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            {{ document.user.nom }}
                        </span>
                        <span class="document-date">
                            {{ formatDate(document.created_at) }}
                        </span>
                    </div>

                    <!-- Version Badge -->
                    <div v-if="document.version > 1" class="version-badge">
                        v{{ document.version }}
                    </div>

                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="modal-overlay" @click.self="closeEditModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Modifier le document</h3>
                    <button type="button" class="btn-close" @click="closeEditModal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit-nom">Nom du fichier</label>
                        <input
                            id="edit-nom"
                            v-model="editForm.nom"
                            type="text"
                            class="form-control"
                        />
                    </div>
                    <div class="form-group">
                        <label for="edit-description">Description</label>
                        <textarea
                            id="edit-description"
                            v-model="editForm.description"
                            class="form-control"
                            rows="3"
                        ></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeEditModal">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveEdit">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useDocuments } from '@/composables/useDocuments';

const props = defineProps({
    documents: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    currentUserId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['download', 'edit', 'delete', 'refresh']);

const { getFileIcon, downloadDocument, updateDocument, deleteDocument } = useDocuments();

const getFileIconSvg = (mimeType) => {
    if (mimeType && mimeType.startsWith('image/')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-slate-300"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0z"/></svg>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-slate-300"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>';
};

const showEditModal = ref(false);
const editingDocument = ref(null);
const editForm = ref({
    nom: '',
    description: '',
});

const canEdit = (document) => {
    return document.user.id === props.currentUserId;
};

const canDelete = (document) => {
    return document.user.id === props.currentUserId;
};

const downloadDoc = async (document) => {
    try {
        await downloadDocument(document.id, document.nom);
        emit('download', document);
    } catch (error) {
        console.error('Download error:', error);
    }
};

const editDoc = (document) => {
    editingDocument.value = document;
    editForm.value = {
        nom: document.nom,
        description: document.description || '',
    };
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingDocument.value = null;
    editForm.value = {
        nom: '',
        description: '',
    };
};

const saveEdit = async () => {
    if (!editingDocument.value) return;

    try {
        await updateDocument(editingDocument.value.id, editForm.value);
        emit('edit', editingDocument.value);
        emit('refresh');
        closeEditModal();
    } catch (error) {
        console.error('Update error:', error);
    }
};

const confirmDelete = async (document) => {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer "${document.nom}" ?`)) {
        return;
    }

    try {
        await deleteDocument(document.id);
        emit('delete', document);
        emit('refresh');
    } catch (error) {
        console.error('Delete error:', error);
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Aujourd\'hui';
    if (diffDays === 1) return 'Hier';
    if (diffDays < 7) return `Il y a ${diffDays} jours`;

    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};



</script>

<style scoped>
.document-list {
    width: 100%;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    color: #64748b;
}

.loading-state i,
.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.loading-state p,
.empty-state p {
    font-size: 1.125rem;
    margin: 0;
}

.empty-state small {
    font-size: 0.875rem;
    color: #94a3b8;
}

.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.document-card {
    position: relative;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.document-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.document-preview {
    position: relative;
    width: 100%;
    height: 180px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.document-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.document-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.document-icon {
    font-size: 4rem;
    color: #cbd5e1;
}

.document-actions {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.document-card:hover .document-actions {
    opacity: 1;
}

.action-btn {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    background: white;
    color: #3b82f6;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.action-btn:hover {
    transform: scale(1.1);
    background: #3b82f6;
    color: white;
}

.action-btn.action-danger {
    color: #ef4444;
}

.action-btn.action-danger:hover {
    background: #ef4444;
    color: white;
}

.document-info {
    padding: 1rem;
    position: relative;
}

.document-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.document-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.meta-item {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.document-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: #94a3b8;
}

.document-user,
.document-date {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.version-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: #eef2ff;
    color: #4f46e5;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e293b;
}

.btn-close {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-close:hover {
    background: #f1f5f9;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
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

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
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

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}
</style>
