// resources/js/composables/useToast.js
import { ref } from 'vue'

const toasts = ref([])
let toastId = 0

export function useToast() {
  const addToast = (message, type = 'info', duration = 3000) => {
    const id = toastId++
    const toast = {
      id,
      message,
      type, // 'success', 'error', 'warning', 'info'
      duration
    }
    
    toasts.value.push(toast)
    
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }
    
    return id
  }
  
  const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }
  
  const showSuccess = (message, duration = 3000) => {
    return addToast(message, 'success', duration)
  }
  
  const showError = (message, duration = 5000) => {
    return addToast(message, 'error', duration)
  }
  
  const showWarning = (message, duration = 4000) => {
    return addToast(message, 'warning', duration)
  }
  
  const showInfo = (message, duration = 3000) => {
    return addToast(message, 'info', duration)
  }
  
  const clearAll = () => {
    toasts.value = []
  }
  
  return {
    toasts,
    addToast,
    removeToast,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    clearAll
  }
}