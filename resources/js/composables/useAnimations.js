import { ref, onMounted, nextTick } from 'vue'

/**
 * useStagger — applique un délai CSS en cascade sur une liste d'éléments.
 *
 * Utilisation :
 *   const { staggerRef, applyStagger } = useStagger()
 *   // Dans le template : <ul ref="staggerRef">
 *   //   <li v-for="..." class="stagger-item">
 *   // Appeler applyStagger() après que la liste est chargée.
 */
export function useStagger(stepMs = 50, maxItems = 20) {
  const staggerRef = ref(null)

  function applyStagger() {
    nextTick(() => {
      if (!staggerRef.value) { return }
      const items = staggerRef.value.querySelectorAll('.stagger-item')
      items.forEach((el, i) => {
        el.style.setProperty('--stagger-delay', `${Math.min(i, maxItems) * stepMs}ms`)
      })
    })
  }

  return { staggerRef, applyStagger }
}

/**
 * useCounter — anime un chiffre de 0 à sa valeur cible.
 *
 * Utilisation :
 *   const { displayValue, animateTo } = useCounter()
 *   onMounted(() => animateTo(props.value))
 *   // Dans le template : <span class="counter-pop">{{ displayValue }}</span>
 */
export function useCounter(durationMs = 800, easing = 'easeOutCubic') {
  const displayValue = ref(0)

  const easings = {
    easeOutCubic: (t) => 1 - Math.pow(1 - t, 3),
    easeOutExpo:  (t) => t === 1 ? 1 : 1 - Math.pow(2, -10 * t),
    linear:       (t) => t,
  }

  function animateTo(target, decimals = 0) {
    const ease = easings[easing] ?? easings.easeOutCubic
    const start = displayValue.value
    const diff  = target - start
    const startTime = performance.now()

    function step(now) {
      const elapsed  = now - startTime
      const progress = Math.min(elapsed / durationMs, 1)
      const value    = start + diff * ease(progress)
      displayValue.value = decimals ? parseFloat(value.toFixed(decimals)) : Math.round(value)

      if (progress < 1) {
        requestAnimationFrame(step)
      }
    }

    requestAnimationFrame(step)
  }

  return { displayValue, animateTo }
}

/**
 * usePageEnter — déclenche la classe .page-enter sur le conteneur passé en ref.
 * À appeler dans onMounted() de chaque page, ou utiliser directement
 * la classe page-enter sur le <div> racine (recommandé via AdminLayout).
 */
export function usePageEnter() {
  const pageRef = ref(null)

  onMounted(() => {
    if (!pageRef.value) { return }
    pageRef.value.classList.add('page-enter')
  })

  return { pageRef }
}
