// Abstraction pour les appels à l'API du package opcodesio/log-viewer.
// Utilise une instance axios dédiée (baseURL = origine, pas /api) avec le token Bearer.
// Remplacer ce fichier suffit pour migrer vers un autre backend de logs.

import axios from 'axios'

const lv = axios.create({
    baseURL: window.location.origin,
    withCredentials: true,
})

lv.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }

    return config
})

export function useLogViewer() {
    /**
     * Liste tous les fichiers de logs disponibles.
     * @returns {Promise<Array>} tableau de LogFileResource
     */
    const fetchFiles = () =>
        lv.get('/log-viewer/api/files').then((r) => r.data)

    /**
     * Récupère les entrées de logs paginées avec filtres.
     * @param {Object} params - file?, query?, direction?, exclude_levels[], per_page?, page?
     * @returns {Promise<Object>} { file, levelCounts, logs, pagination, percentScanned }
     */
    const fetchLogs = (params = {}) =>
        lv.get('/log-viewer/api/logs', { params }).then((r) => r.data)

    /**
     * Demande une URL signée pour télécharger un fichier de log.
     * @param {string} fileIdentifier
     * @returns {Promise<string>} URL signée valide 1 minute
     */
    const requestDownload = (fileIdentifier) =>
        lv
            .get(`/log-viewer/api/files/${fileIdentifier}/download/request`)
            .then((r) => r.data.url)

    /**
     * Supprime un fichier de log.
     * @param {string} fileIdentifier
     * @returns {Promise<void>}
     */
    const deleteFile = (fileIdentifier) =>
        lv.delete(`/log-viewer/api/files/${fileIdentifier}`)

    /**
     * Vide le cache d'un fichier de log.
     * @param {string} fileIdentifier
     * @returns {Promise<void>}
     */
    const clearCache = (fileIdentifier) =>
        lv.post(`/log-viewer/api/files/${fileIdentifier}/clear-cache`)

    return { fetchFiles, fetchLogs, requestDownload, deleteFile, clearCache }
}
