<!-- resources\js\App.vue (version finale) -->
<template>
    <ThemeProvider>
        <SidebarProvider>
            <!-- Overlay de chargement initial -->
            <div v-if="isAppLoading" class="app-loading">
                <div class="app-loader">
                    <div class="logo">WT</div>
                    <div class="spinner"></div>
                    <p class="app-loading-text">Work Tracking</p>
                </div>
            </div>
            
            <!-- Application principale -->
            <div v-else class="app-container">
                <GlobalLoader />
                <RouterView />
            </div>
        </SidebarProvider>
    </ThemeProvider>
</template>

<script setup>
import ThemeProvider from "@/components/layout/ThemeProvider.vue";
import SidebarProvider from "@/components/layout/SidebarProvider.vue";
import GlobalLoader from "@/components/ui/GlobalLoader.vue";
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/authStore';

const authStore = useAuthStore();
const isAppLoading = ref(true);

onMounted(async () => {
    try {
        console.log('🚀 Initialisation de l\'application...');
        
        // Initialiser l'authentification
        authStore.initialize();
        
        // Simulation d'un délai de chargement minimum
        await Promise.all([
            new Promise(resolve => setTimeout(resolve, 1000))
        ]);
        
        console.log('✅ Application initialisée');
    } catch (err) {
        console.error('❌ Erreur lors de l\'initialisation:', err);
    } finally {
        isAppLoading.value = false;
    }
});
</script>

<style scoped>
.app-loading {
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
}

.app-loader {
    text-align: center;
    color: white;
}

.logo {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 20px;
    color: white;
}

.app-loading .spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

.app-loading-text {
    font-size: 18px;
    font-weight: 500;
    margin-top: 10px;
    letter-spacing: 1px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.app-container {
    min-height: 100vh;
}
</style>