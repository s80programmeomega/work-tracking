<template>
    <div class="document-list">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Chargement des documents...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="documents.length === 0" class="empty-state">
            <i class="fas fa-folder-open"></i>
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
                        <i :class="['fas', getFileIcon(document.mime_type), 'document-icon']"></i>
                    </div>

                    <!-- Actions Overlay -->
                    <div class="document-actions">
                        <button
                            type="button"
                            class="action-btn"
                            @click="downloadDoc(document)"
                            title="Télécharger"
                        >
                            <i class="fas fa-download"></i>
                        </button>
                        <button
                            v-if="canEdit(document)"
                            type="button"
                            class="action-btn"
                            @click="editDoc(document)"
                            title="Modifier"
                        >
                            <i class="fas fa-edit"></i>
                        </button>
                        <button
                            v-if="canDelete(document)"
                            type="button"
                            class="action-btn action-danger"
                            @click="confirmDelete(document)"
                            title="Supprimer"
                        >
                            <i class="fas fa-trash-alt"></i>
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
                            <i class="fas fa-hdd"></i>
                            {{ document.formatted_size }}
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-download"></i>
                            {{ document.download_count }}
                        </span>
                    </div>
                    <div class="document-footer">
                        <span class="document-user">
                            <i class="fas fa-user"></i>
                            {{ document.user.name }}
                        </span>
                        <span class="document-date">
                            {{ formatDate(document.created_at) }}
                        </span>
                    </div>

                    <!-- Version Badge -->
                    <div v-if="document.version > 1" class="version-badge">
                        v{{ document.version }}
                    </div>

                    <!-- Visibility Badge -->
                    <div class="visibility-badge" :class="`visibility-${document.visibility}`">
                        <i :class="getVisibilityIcon(document.visibility)"></i>
                        {{ getVisibilityLabel(document.visibility) }}
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
                        <i class="fas fa-times"></i>
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
                    <div class="form-group">
                        <label for="edit-visibility">Visibilité</label>
                        <select id="edit-visibility" v-model="editForm.visibility" class="form-control">
                            <option value="private">Privé</option>
                            <option value="team">Équipe</option>
                            <option value="public">Public</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeEditModal">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveEdit">
                        <i class="fas fa-save"></i>
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

const showEditModal = ref(false);
const editingDocument = ref(null);
const editForm = ref({
    nom: '',
    description: '',
    visibility: 'team',
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
        visibility: document.visibility,
    };
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingDocument.value = null;
    editForm.value = {
        nom: '',
        description: '',
        visibility: 'team',
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

const getVisibilityIcon = (visibility) => {
    const icons = {
        private: 'fas fa-lock',
        team: 'fas fa-users',
        public: 'fas fa-globe',
    };
    return icons[visibility] || 'fas fa-question';
};

const getVisibilityLabel = (visibility) => {
    const labels = {
        private: 'Privé',
        team: 'Équipe',
        public: 'Public',
    };
    return labels[visibility] || visibility;
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

.version-badge,
.visibility-badge {
    position: absolute;
    top: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.version-badge {
    right: 0.5rem;
    background: #eef2ff;
    color: #4f46e5;
}

.visibility-badge {
    left: 0.5rem;
    background: #f1f5f9;
    color: #64748b;
}

.visibility-private {
    background: #fef2f2;
    color: #991b1b;
}

.visibility-team {
    background: #eff6ff;
    color: #1e40af;
}

.visibility-public {
    background: #f0fdf4;
    color: #166534;
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
