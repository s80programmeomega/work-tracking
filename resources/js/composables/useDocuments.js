import { ref, computed } from 'vue';
import api from '@/api/axios';

export function useDocuments() {
    const documents = ref([]);
    const loading = ref(false);
    const uploading = ref(false);
    const error = ref(null);
    const uploadProgress = ref(0);

    /**
     * Fetch documents for an entity
     */
    const fetchDocuments = async (documentableType, documentableId, withVersions = false) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/documents', {
                params: {
                    documentable_type: documentableType,
                    documentable_id: documentableId,
                    with_versions: withVersions,
                },
            });

            documents.value = response.data.data;
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des documents';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Upload single or multiple documents
     */
    const uploadDocuments = async (
        files,
        documentableType,
        documentableId,
        options = {}
    ) => {
        uploading.value = true;
        uploadProgress.value = 0;
        error.value = null;

        try {
            const formData = new FormData();
            formData.append('documentable_type', documentableType);
            formData.append('documentable_id', documentableId);

            // Append files
            if (Array.isArray(files)) {
                files.forEach((file) => {
                    formData.append('files[]', file);
                });
            } else {
                formData.append('files[]', files);
            }

            // Append options
            if (options.description) {
                formData.append('description', options.description);
            }
            if (options.visibility) {
                formData.append('visibility', options.visibility);
            }
            if (options.disk) {
                formData.append('disk', options.disk);
            }
            if (options.allow_duplicates !== undefined) {
                formData.append('allow_duplicates', options.allow_duplicates);
            }

            const response = await api.post('/documents', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    uploadProgress.value = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                },
            });

            // Add new documents to the list
            const newDocs = Array.isArray(response.data.data)
                ? response.data.data
                : [response.data.data];

            documents.value.unshift(...newDocs);

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du téléchargement';
            throw err;
        } finally {
            uploading.value = false;
            uploadProgress.value = 0;
        }
    };

    /**
     * Get document details
     */
    const fetchDocument = async (documentId) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get(`/documents/${documentId}`);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement du document';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update document metadata
     */
    const updateDocument = async (documentId, data) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put(`/documents/${documentId}`, data);

            // Update document in list
            const index = documents.value.findIndex((doc) => doc.id === documentId);
            if (index !== -1) {
                documents.value[index] = response.data.data;
            }

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la mise à jour';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Delete a document
     */
    const deleteDocument = async (documentId) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.delete(`/documents/${documentId}`);

            // Remove from list
            documents.value = documents.value.filter((doc) => doc.id !== documentId);

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la suppression';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Download a document
     */
    const downloadDocument = async (documentId, documentName) => {
        try {
            const response = await api.get(`/documents/${documentId}/download`, {
                responseType: 'blob',
            });

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', documentName);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du téléchargement';
            throw err;
        }
    };

    /**
     * Create new version of a document
     */
    const createVersion = async (documentId, file) => {
        uploading.value = true;
        uploadProgress.value = 0;
        error.value = null;

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await api.post(`/documents/${documentId}/versions`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    uploadProgress.value = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                },
            });

            // Update document in list
            const index = documents.value.findIndex((doc) => doc.id === documentId);
            if (index !== -1) {
                documents.value[index] = response.data.data;
            }

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la création de la version';
            throw err;
        } finally {
            uploading.value = false;
            uploadProgress.value = 0;
        }
    };

    /**
     * Get download statistics
     */
    const fetchStats = async (documentId) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get(`/documents/${documentId}/stats`);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Grant permission to user
     */
    const grantPermission = async (documentId, userId, permissions, expiresAt = null) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post(`/documents/${documentId}/permissions/grant`, {
                user_id: userId,
                ...permissions,
                expires_at: expiresAt,
            });

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de l\'octroi des permissions';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Revoke permission from user
     */
    const revokePermission = async (documentId, userId) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post(`/documents/${documentId}/permissions/revoke`, {
                user_id: userId,
            });

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la révocation des permissions';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Search documents
     */
    const searchDocuments = async (query, filters = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/documents/search', {
                params: {
                    query,
                    ...filters,
                },
            });

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la recherche';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Get file icon based on mime type
     */
    const getFileIcon = (mimeType) => {
        if (mimeType.startsWith('image/')) return 'fa-image';
        if (mimeType === 'application/pdf') return 'fa-file-pdf';
        if (mimeType.includes('word')) return 'fa-file-word';
        if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'fa-file-excel';
        if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'fa-file-powerpoint';
        if (mimeType.startsWith('video/')) return 'fa-file-video';
        if (mimeType.startsWith('audio/')) return 'fa-file-audio';
        if (mimeType.includes('zip') || mimeType.includes('compressed')) return 'fa-file-archive';
        if (mimeType.startsWith('text/')) return 'fa-file-alt';
        return 'fa-file';
    };

    /**
     * Format file size
     */
    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    };

    /**
     * Computed: Total documents count
     */
    const totalDocuments = computed(() => documents.value.length);

    /**
     * Computed: Total size of all documents
     */
    const totalSize = computed(() => {
        return documents.value.reduce((sum, doc) => sum + (doc.taille || 0), 0);
    });

    /**
     * Computed: Documents grouped by type
     */
    const documentsByType = computed(() => {
        const grouped = {};

        documents.value.forEach((doc) => {
            const type = doc.mime_type.split('/')[0];
            if (!grouped[type]) {
                grouped[type] = [];
            }
            grouped[type].push(doc);
        });

        return grouped;
    });

    return {
        // State
        documents,
        loading,
        uploading,
        error,
        uploadProgress,

        // Computed
        totalDocuments,
        totalSize,
        documentsByType,

        // Methods
        fetchDocuments,
        uploadDocuments,
        fetchDocument,
        updateDocument,
        deleteDocument,
        downloadDocument,
        createVersion,
        fetchStats,
        grantPermission,
        revokePermission,
        searchDocuments,
        getFileIcon,
        formatFileSize,
    };
}
