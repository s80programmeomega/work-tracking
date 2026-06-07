// resources/js/composables/useDraggable.js
import { ref, onUnmounted } from 'vue'

/**
 * Makes a modal dialog draggable by its header.
 *
 * Usage:
 *   const { dialogRef, handleRef, dragStyle } = useDraggable()
 *   <div ref="dialogRef" :style="dragStyle">
 *     <div ref="handleRef" class="cursor-move">...</div>
 *   </div>
 */
export function useDraggable() {
  const dialogRef = ref(null)
  const handleRef = ref(null)
  const offset = ref({ x: 0, y: 0 })
  const position = ref({ x: 0, y: 0 })
  const dragging = ref(false)

  const dragStyle = ref({})

  // Résout l'élément DOM, que la ref pointe sur un nœud natif ou sur
  // l'instance d'un composant (ex. Headless UI DialogPanel → $el).
  function el(r) {
    const v = r && r.value
    if (!v) { return null }
    return v instanceof HTMLElement ? v : (v.$el ?? null)
  }

  function onMouseDown(e) {
    if (e.button !== 0) { return }
    const dlg = el(dialogRef)
    if (!dlg) { return }
    dragging.value = true

    const rect = dlg.getBoundingClientRect()
    offset.value = {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top,
    }

    document.addEventListener('mousemove', onMouseMove)
    document.addEventListener('mouseup', onMouseUp)
    e.preventDefault()
  }

  function onMouseMove(e) {
    if (!dragging.value) { return }

    const x = e.clientX - offset.value.x
    const y = e.clientY - offset.value.y

    // Clamp so dialog never leaves the viewport
    const dlg = el(dialogRef)
    if (dlg) {
      const maxX = window.innerWidth - dlg.offsetWidth
      const maxY = window.innerHeight - dlg.offsetHeight
      position.value = {
        x: Math.max(0, Math.min(x, maxX)),
        y: Math.max(0, Math.min(y, maxY)),
      }
    } else {
      position.value = { x, y }
    }

    dragStyle.value = {
      position: 'fixed',
      left: position.value.x + 'px',
      top: position.value.y + 'px',
      transform: 'none',
      margin: '0',
    }
  }

  function onMouseUp() {
    dragging.value = false
    document.removeEventListener('mousemove', onMouseMove)
    document.removeEventListener('mouseup', onMouseUp)
  }

  function attachHandle() {
    const h = el(handleRef)
    if (h) {
      h.addEventListener('mousedown', onMouseDown)
    }
  }

  function detachHandle() {
    const h = el(handleRef)
    if (h) {
      h.removeEventListener('mousedown', onMouseDown)
    }
    document.removeEventListener('mousemove', onMouseMove)
    document.removeEventListener('mouseup', onMouseUp)
  }

  onUnmounted(detachHandle)

  return { dialogRef, handleRef, dragStyle, attachHandle, detachHandle }
}
