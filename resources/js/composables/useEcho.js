import { ref } from 'vue';
import Echo from 'laravel-echo';

let echoInstance = null;

const connected = ref(false);

function getEcho() {
    if (!echoInstance) {
        // L'app authentifie via un token Sanctum stocké dans localStorage.
        // L'endpoint d'autorisation est exposé sous /api/broadcasting/auth
        // (auth:sanctum) — on doit donc transmettre le Bearer ici sinon
        // /broadcasting/auth renvoie 403.
        //
        // On utilise un "authorizer" personnalisé plutôt que auth.headers
        // statiques: cela permet de relire le token à chaque requête
        // d'autorisation, ce qui couvre le cas où l'utilisateur se
        // connecte après le boot de l'app (le token n'existerait pas
        // encore au moment de la construction d'Echo).
        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
            authorizer: (channel) => ({
                authorize: (socketId, callback) => {
                    const token = localStorage.getItem('auth_token');
                    fetch('/api/broadcasting/auth', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            Accept: 'application/json',
                            Authorization: token ? `Bearer ${token}` : '',
                        },
                        body: new URLSearchParams({
                            socket_id: socketId,
                            channel_name: channel.name,
                        }),
                    })
                        .then((res) => {
                            if (!res.ok) {
                                throw new Error(`Auth failed: ${res.status}`);
                            }
                            return res.json();
                        })
                        .then((data) => callback(null, data))
                        .catch((err) => callback(err, null));
                },
            }),
        });

        echoInstance.connector.pusher.connection.bind('connected', () => {
            connected.value = true;
        });

        echoInstance.connector.pusher.connection.bind('disconnected', () => {
            connected.value = false;
        });
    }

    return echoInstance;
}

function disconnectEcho() {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
        connected.value = false;
    }
}

export function useEcho() {
    return {
        echo: getEcho(),
        connected,
        disconnectEcho,
    };
}
