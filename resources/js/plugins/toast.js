// Simple toast plugin for notifications
export function createToastPlugin() {
  return {
    install(app) {
      const toast = {
        success(message) {
          this.show(message, 'success')
        },
        error(message) {
          this.show(message, 'error')
        },
        warning(message) {
          this.show(message, 'warning')
        },
        info(message) {
          this.show(message, 'info')
        },
        show(message, type = 'info') {
          // Create toast element
          const toastContainer = document.getElementById('toast-container') || this.createContainer()
          const toastElement = this.createElement(message, type)

          toastContainer.appendChild(toastElement)

          // Show toast
          setTimeout(() => {
            toastElement.classList.add('show')
          }, 100)

          // Auto remove after 5 seconds
          setTimeout(() => {
            this.removeToast(toastElement)
          }, 5000)
        },
        createContainer() {
          const container = document.createElement('div')
          container.id = 'toast-container'
          container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 300px;
          `
          document.body.appendChild(container)
          return container
        },
        createElement(message, type) {
          const toast = document.createElement('div')
          toast.className = 'toast-item'

          const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
          }

          const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
          }

          toast.style.cssText = `
            background: ${colors[type] || colors.info};
            color: white;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 10px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 8px;
          `

          toast.innerHTML = `
            <span>${icons[type] || icons.info}</span>
            <span>${message}</span>
          `

          toast.addEventListener('click', () => {
            this.removeToast(toast)
          })

          return toast
        },
        removeToast(toastElement) {
          toastElement.style.transform = 'translateX(100%)'
          toastElement.style.opacity = '0'

          setTimeout(() => {
            if (toastElement.parentNode) {
              toastElement.parentNode.removeChild(toastElement)
            }
          }, 300)
        }
      }

      // Add show class styles
      const style = document.createElement('style')
      style.textContent = `
        .toast-item.show {
          opacity: 1 !important;
          transform: translateX(0) !important;
        }
      `
      document.head.appendChild(style)

      app.config.globalProperties.$toast = toast
      app.provide('toast', toast)
    }
  }
}