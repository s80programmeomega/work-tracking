<!-- resources\js\components\ui\GlobalLoader.vue -->
<template>
    <Transition name="fade">
        <div v-if="isLoading" class="global-loader">
            <div class="loader-content">
                <div class="loader-spinner"></div>
                <div class="loader-text">{{ message }}</div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { isLoading } from '@/router'

const route = useRoute()

const message = computed(() => {
    return `Chargement ${route.meta.title ? `de "${route.meta.title}"` : '...'}`
})
</script>

<script>
export default {
    name: 'GlobalLoader',
    setup() {
        return {
            isLoading
        }
    }
}
</script>

<style scoped>
.global-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    transition: opacity 0.3s ease;
}

:global(.dark) .global-loader {
    background: rgba(17, 24, 39, 0.9);
}

:global(.dark) .loader-content {
    background: #1f2937;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}

:global(.dark) .loader-spinner {
    border-color: #374151;
    border-top-color: #3b82f6;
}

:global(.dark) .loader-text {
    color: #d1d5db;
}

.loader-content {
    text-align: center;
    background: white;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.loader-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

.loader-text {
    font-size: 14px;
    color: #4b5563;
    font-weight: 500;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>