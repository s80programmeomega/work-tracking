// resources/js/composables/useNotificationSound.js
const SOUND_URL = '/sounds/notification.ogg'
const STORAGE_KEY = 'notificationSettings'

let audio = null

const getAudio = () => {
    if (!audio) {
        audio = new Audio(SOUND_URL)
    }

    return audio
}

const isEnabled = () => {
    try {
        const raw = localStorage.getItem(STORAGE_KEY)
        if (!raw) {
            return true
        }

        const settings = JSON.parse(raw)

        return settings.notificationSounds !== false
    } catch (e) {
        return true
    }
}

export function useNotificationSound() {
    const playIfEnabled = () => {
        if (!isEnabled()) {
            return
        }

        try {
            const sound = getAudio()
            sound.currentTime = 0
            // Le navigateur peut bloquer la lecture automatique sans interaction
            // utilisateur préalable : on ignore l'erreur silencieusement.
            sound.play()?.catch(() => {})
        } catch (e) {
            // Lecture audio indisponible (navigateur/contexte) : ignoré.
        }
    }

    return {
        playIfEnabled,
    }
}
