<!-- resources\js\App.vue -->
<template>
    <ThemeProvider>
        <!-- Notifications téléportées sur body pour éviter tout problème de stacking context -->
        <Teleport to="body">
            <NotificationContainer
                :notifications="notifications"
                @remove="removeNotification"
            />
        </Teleport>
        <SidebarProvider>

            <!-- Erreur d'initialisation -->
            <div v-if="error" class="error-overlay">
                <div class="error-container">
                    <p class="error-title">Une erreur est survenue</p>
                    <p class="error-message">Veuillez recharger la page. Si le problème persiste, contactez le support.</p>
                    <button class="error-reload-btn" @click="() => window.location.reload()">Recharger</button>
                </div>
            </div>

            <RouterView v-else :key="authStore.currentWorkspaceId ?? 'default'" />
        </SidebarProvider>
    </ThemeProvider>
</template>

<script setup>
import ThemeProvider from "@/components/layout/ThemeProvider.vue";
import SidebarProvider from "@/components/layout/SidebarProvider.vue";
import { ref, onMounted, onErrorCaptured, provide, watch } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { useLiveNotifications } from '@/composables/useLiveNotifications';
import { useNotificationSound } from '@/composables/useNotificationSound';
import NotificationContainer from "@/components/ui/NotificationContainer.vue"


const authStore = useAuthStore();
const error = ref(null);
const notifications = ref([])

const showNotification = (message, type = 'info', duration = 5000) => {
  const id = Date.now()
  notifications.value.push({
    id,
    message,
    type,
    duration
  })
  
  setTimeout(() => {
    removeNotification(id)
  }, duration)
  
  return id
}

const removeNotification = (id) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index !== -1) {
    notifications.value.splice(index, 1)
  }
}

// Provide notification functions to all components
provide('notifications', {
  show: showNotification,
  remove: removeNotification
})


// Capturer les erreurs globales
onErrorCaptured((err) => {
    console.error('Erreur capturée dans App.vue:', err);
    error.value = err;
    return false; // Empêche la propagation de l'erreur
});

const { start: startLiveNotifications, onNotification } = useLiveNotifications();
const { playIfEnabled: playNotificationSound } = useNotificationSound();

// Task 8 — when a live broadcast lands, surface a toast and increment the bell.
// The actual unread counter lives in useNotifications; this just shows the user
// that something happened. Consumers (e.g. the bell component) can also subscribe
// via onNotification to refresh their list.
onNotification((data) => {
    const title = data?.tache_titre
        ?? data?.title
        ?? data?.document_nom
        ?? data?.team_name
        ?? data?.projet_nom
        ?? data?.workspace_name
        ?? 'Nouvelle notification';
    showNotification(title, 'info', 4000);
    playNotificationSound();
});

onMounted(() => {
    try {
        authStore.initialize();
        // Si l'utilisateur est déjà en mémoire, on s'abonne immédiatement.
        // Sinon on attend que fetchUser() ait peuplé authStore.user.
        if (authStore.user?.id) {
            startLiveNotifications();
        }
    } catch (err) {
        console.error('❌ Erreur lors de l\'initialisation:', err);
        error.value = err;
    }
});

watch(() => authStore.user?.id, (userId) => {
    if (userId) startLiveNotifications();
}, { immediate: false });
</script>

<style scoped>

.error-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

:global(.dark) .error-overlay {
    background: #111827;
}

:global(.dark) .error-title {
    color: #f87171;
}

:global(.dark) .error-message {
    color: #9ca3af;
}

.error-container {
    text-align: center;
    max-width: 400px;
    padding: 2rem;
}

.error-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #dc3545;
    margin-bottom: 0.5rem;
}

.error-message {
    color: #6c757d;
    margin-bottom: 1.5rem;
}

.error-reload-btn {
    padding: 0.5rem 1.5rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}
</style>