import { ref } from 'vue';
import Echo from 'laravel-echo';

let echoInstance = null;

const connected = ref(false);

function getEcho() {
    if (!echoInstance) {
        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
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
