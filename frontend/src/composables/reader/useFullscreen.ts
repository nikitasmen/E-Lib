import { onMounted, onUnmounted, ref } from 'vue'

/** Thin Fullscreen API wrapper. */
export function useFullscreen() {
  const isFullscreen = ref(false)

  function sync() {
    isFullscreen.value = document.fullscreenElement !== null
  }

  function toggle(el: HTMLElement | null) {
    if (!el) return
    if (document.fullscreenElement) {
      document.exitFullscreen?.()
    } else {
      el.requestFullscreen?.()
    }
  }

  onMounted(() => document.addEventListener('fullscreenchange', sync))
  onUnmounted(() => document.removeEventListener('fullscreenchange', sync))

  return { isFullscreen, toggle }
}
