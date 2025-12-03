<!-- resources\js\App.vue -->
<template>
    <ThemeProvider>
        <SidebarProvider>
             <!-- Notifications -->
      <NotificationContainer
        :notifications="notifications"
        @remove="removeNotification"
      />

            <!-- Overlay de chargement global -->
            <div v-if="isLoading" class="loading-overlay">
                <div class="spinner-container">
                    <div class="spinner"></div>
                    <p class="loading-text">Chargement de l'application...</p>
                </div>
            </div>
            
            <RouterView v-else />
        </SidebarProvider>
    </ThemeProvider>
</template>

<script setup>
import ThemeProvider from "@/components/layout/ThemeProvider.vue";
import SidebarProvider from "@/components/layout/SidebarProvider.vue";
import { ref, onMounted, onErrorCaptured,provide  } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import NotificationContainer from "@/components/ui/NotificationContainer.vue"


const authStore = useAuthStore();
const isLoading = ref(true);
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

onMounted(async () => {
    try {
        console.log('🚀 Initialisation de l\'application...');
        
        // Initialiser l'authentification
        authStore.initialize();
        
        // Attendre un peu pour s'assurer que tout est chargé
        await new Promise(resolve => setTimeout(resolve, 500));
        
        console.log('✅ Application initialisée');
    } catch (err) {
        console.error('❌ Erreur lors de l\'initialisation:', err);
        error.value = err;
    } finally {
        isLoading.value = false;
    }
});
</script>

<style scoped>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.3s ease;
}

.spinner-container {
    text-align: center;
    color: white;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

.loading-text {
    font-size: 16px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9);
    margin-top: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>