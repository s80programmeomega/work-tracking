<!-- resources\js\components\layout\MainLayout.vue -->
<template>
    <div v-if="isLoading" class="layout-loading">
        <div class="layout-spinner"></div>
    </div>
    
    <div v-else-if="error" class="layout-error">
        <h3>Une erreur est survenue</h3>
        <p>{{ error.message }}</p>
        <button @click="retry" class="retry-btn">Réessayer</button>
    </div>
    
    <div v-else class="main-layout">
        <slot></slot>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const isLoading = ref(true);
const error = ref(null);

const retry = () => {
    isLoading.value = true;
    error.value = null;
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
};

// Simuler le chargement
setTimeout(() => {
    isLoading.value = false;
}, 1000);
</script>

<style scoped>
.layout-loading {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}

.layout-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.layout-error {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fef2f2;
    color: #dc2626;
    padding: 20px;
    text-align: center;
}

.layout-error h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
}

.layout-error p {
    font-size: 14px;
    margin-bottom: 20px;
    color: #7f1d1d;
}

.retry-btn {
    padding: 8px 16px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
}

.retry-btn:hover {
    background: #2563eb;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>