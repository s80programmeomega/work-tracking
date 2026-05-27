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
    background: var(--color-gray-50, #F4F6F9);
}

.layout-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--color-gray-200, #D0DAE8);
    border-top: 3px solid var(--color-brand-500, #2D7DD2);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.layout-error {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--color-error-50, #FDEDEC);
    color: var(--color-error-500, #C0392B);
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
    color: var(--color-error-600, #a33124);
}

.retry-btn {
    padding: 8px 16px;
    background: var(--color-brand-500, #2D7DD2);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
}

.retry-btn:hover {
    background: var(--color-brand-600, #2469b8);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>