// resources/js/composables/useWebPush.js
//
// Task 8b — Composable pour la gestion des notifications Web Push côté client.
//
// Responsabilités :
//   - Détecter si le navigateur supporte les notifications push
//   - Enregistrer le service worker /sw-webpush.js
//   - Demander la permission de notification à l'utilisateur
//   - S'abonner au push service du navigateur (FCM, Mozilla, Apple) via VAPID
//   - Transmettre la souscription au backend (POST /api/webpush/subscribe)
//   - Se désabonner (DELETE /api/webpush/unsubscribe)
//
// Utilisation typique dans une page de préférences :
//   import { useWebPush } from '@/composables/useWebPush'
//   const { isSupported, permission, isSubscribed, subscribe, unsubscribe, error } = useWebPush()
//
// Note : les appels nécessitent HTTPS en production. En développement local,
// http://localhost fonctionne aussi (exception navigateur pour les hôtes locaux).

import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

export function useWebPush() {
    // Support natif : Notification + ServiceWorker + PushManager doivent être présents.
    const isSupported = ref(
        typeof window !== 'undefined' &&
        'Notification' in window &&
        'serviceWorker' in navigator &&
        'PushManager' in window,
    )

    // Permission actuelle : 'default' (pas demandée), 'granted', 'denied'.
    const permission = ref(isSupported.value ? Notification.permission : 'unsupported')

    // True si l'utilisateur est actuellement abonné côté navigateur.
    const isSubscribed = ref(false)

    const loading = ref(false)
    const error = ref(null)

    // VAPID public key, récupérée depuis le backend au premier usage.
    // On garde en module-level cache pour éviter de re-fetcher entre instances.
    let cachedVapidKey = null

    /**
     * Récupère la clé publique VAPID nécessaire à l'abonnement navigateur.
     * La clé est mise en cache au premier appel.
     */
    const getVapidKey = async () => {
        if (cachedVapidKey) return cachedVapidKey
        const res = await api.get('/webpush/vapid-key')
        cachedVapidKey = res.data.public_key
        return cachedVapidKey
    }

    /**
     * Convertit une clé VAPID Base64 URL-safe en Uint8Array — format attendu
     * par PushManager.subscribe(). Boilerplate standard de l'API Web Push.
     */
    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
        const rawData = atob(base64)
        const output = new Uint8Array(rawData.length)
        for (let i = 0; i < rawData.length; i++) {
            output[i] = rawData.charCodeAt(i)
        }
        return output
    }

    /**
     * Enregistre le service worker (idempotent — navigateur dédouble en interne).
     * Retourne le ServiceWorkerRegistration prêt à l'emploi.
     */
    const registerServiceWorker = async () => {
        if (!isSupported.value) {
            throw new Error('Notifications push non supportées par ce navigateur.')
        }
        const registration = await navigator.serviceWorker.register('/sw-webpush.js', { scope: '/' })
        // Attendre que le SW soit actif avant de poursuivre — sinon
        // PushManager.subscribe peut échouer si le SW est encore « installing ».
        await navigator.serviceWorker.ready
        return registration
    }

    /**
     * Vérifie si une souscription navigateur existe déjà (l'utilisateur
     * a déjà accordé la permission lors d'une visite précédente).
     */
    const checkSubscription = async () => {
        if (!isSupported.value) return
        try {
            const registration = await navigator.serviceWorker.getRegistration('/sw-webpush.js')
            if (!registration) return
            const subscription = await registration.pushManager.getSubscription()
            isSubscribed.value = !!subscription
        } catch (e) {
            console.warn('Web Push : impossible de vérifier la souscription existante', e)
        }
    }

    /**
     * Flux complet d'abonnement :
     *   1. Enregistrer le service worker
     *   2. Demander la permission utilisateur (prompt navigateur natif)
     *   3. Récupérer la clé VAPID du backend
     *   4. Souscrire auprès du push service
     *   5. Persister la souscription côté backend
     */
    const subscribe = async () => {
        if (!isSupported.value) {
            error.value = 'Notifications push non supportées par ce navigateur.'
            return false
        }

        loading.value = true
        error.value = null

        try {
            const registration = await registerServiceWorker()

            // Permission utilisateur — peut afficher un prompt natif
            const perm = await Notification.requestPermission()
            permission.value = perm
            if (perm !== 'granted') {
                error.value = perm === 'denied'
                    ? 'Permission refusée. Activez les notifications dans les paramètres du navigateur.'
                    : 'Permission non accordée.'
                return false
            }

            const vapidKey = await getVapidKey()

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true, // requis par les navigateurs — chaque push doit produire une notif visible
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            })

            // Persistance côté backend — la souscription est inutile tant que
            // le serveur ne la connaît pas. Le payload suit le format du
            // PushSubscription navigateur (W3C standard).
            const json = subscription.toJSON()
            await api.post('/webpush/subscribe', {
                endpoint: json.endpoint,
                keys: {
                    p256dh: json.keys.p256dh,
                    auth: json.keys.auth,
                },
            })

            isSubscribed.value = true
            return true
        } catch (e) {
            console.error('Échec de l\'abonnement Web Push', e)
            error.value = e?.response?.data?.message || e?.message || 'Échec de l\'abonnement.'
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Désabonnement : on désabonne le navigateur ET on notifie le backend
     * pour qu'il marque la souscription comme inactive.
     */
    const unsubscribe = async () => {
        if (!isSupported.value) return false

        loading.value = true
        error.value = null

        try {
            const registration = await navigator.serviceWorker.getRegistration('/sw-webpush.js')
            const subscription = await registration?.pushManager.getSubscription()

            if (subscription) {
                const endpoint = subscription.endpoint
                await subscription.unsubscribe()
                // Notifier le backend en best-effort — même si l'appel échoue,
                // le navigateur ne recevra plus de pushes.
                try {
                    await api.delete('/webpush/unsubscribe', { data: { endpoint } })
                } catch (e) {
                    console.warn('Échec de la notification de désabonnement au backend', e)
                }
            }

            isSubscribed.value = false
            return true
        } catch (e) {
            console.error('Échec du désabonnement Web Push', e)
            error.value = e?.message || 'Échec du désabonnement.'
            return false
        } finally {
            loading.value = false
        }
    }

    // Statut humain lisible — utile pour affichage UI
    const status = computed(() => {
        if (!isSupported.value) return 'unsupported'
        if (permission.value === 'denied') return 'denied'
        if (isSubscribed.value) return 'subscribed'
        return 'not-subscribed'
    })

    // Vérifier l'état initial au montage
    onMounted(() => {
        checkSubscription()
    })

    return {
        isSupported,
        permission,
        isSubscribed,
        loading,
        error,
        status,
        subscribe,
        unsubscribe,
        checkSubscription,
    }
}
