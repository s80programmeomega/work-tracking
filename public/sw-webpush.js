// Service worker dédié au Web Push (Task 8b)
//
// Ce fichier doit être servi à la racine du domaine pour avoir le scope
// le plus large possible (« / »). Le navigateur ne tolère pas de scope
// au-delà du chemin du SW lui-même.
//
// Cycle de vie :
//   1. Le frontend appelle navigator.serviceWorker.register('/sw-webpush.js')
//   2. Le navigateur télécharge ce fichier et exécute 'install' puis 'activate'
//   3. Quand un push arrive, l'événement 'push' déclenche showNotification
//   4. Un clic sur la notification déclenche 'notificationclick' qui ouvre l'app
//
// Format attendu du payload envoyé par WebPushChannel (JSON) :
//   {
//     title: string,        // titre de la notification système
//     body:  string,        // ligne de texte sous le titre
//     url:   string,        // URL ouverte au clic (défaut: /)
//     tag:   string,        // identifiant pour grouper/remplacer les notifs
//     icon:  string,        // (optionnel) URL d'icône
//     data:  object         // (optionnel) payload arbitraire transmis au clic
//   }

// Lors de l'installation, on prend immédiatement le contrôle —
// pas de cache à pré-remplir, ce SW ne sert que pour les pushes.
self.addEventListener('install', (event) => {
    self.skipWaiting()
})

// Lors de l'activation, réclamer le contrôle de tous les clients existants
// pour éviter qu'un onglet ouvert ne reste sur l'ancienne version.
self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim())
})

// Gestionnaire principal — un push est arrivé du push service.
// La data est dans event.data — peut être null si le push est « tickle »
// (réveil sans payload), peut être string JSON ou bytes selon le client.
self.addEventListener('push', (event) => {
    let payload = {}

    if (event.data) {
        try {
            payload = event.data.json()
        } catch (e) {
            // Fallback texte brut — rare mais possible si le serveur n'envoie
            // pas du JSON valide.
            payload = { title: 'Notification', body: event.data.text() }
        }
    }

    const title = payload.title || 'Work Tracking'
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/favicon.ico',
        badge: '/favicon.ico',
        // tag permet de regrouper / remplacer les notifs du même type
        // (ex. plusieurs notifs « validation N1 » sur la même tâche se fondent).
        tag: payload.tag || 'default',
        // data est transmis tel quel à 'notificationclick' — on y stocke l'URL.
        data: {
            url: payload.url || '/',
            ...(payload.data || {}),
        },
    }

    event.waitUntil(self.registration.showNotification(title, options))
})

// Clic sur la notification — ouvrir ou activer l'onglet de l'app.
self.addEventListener('notificationclick', (event) => {
    event.notification.close()

    const targetUrl = event.notification.data?.url || '/'

    event.waitUntil(
        // Chercher un onglet existant avec ce host → l'activer plutôt qu'en ouvrir un nouveau
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                // Si un onglet de l'app est ouvert, on le focus et on l'envoie sur la bonne URL
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(targetUrl)
                    return client.focus()
                }
            }
            // Pas d'onglet existant → ouvrir un nouveau
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl)
            }
        }),
    )
})
