function keyFor(prefix: string, bookId: string): string {
  return `elib_pdf_${prefix}_${bookId}`
}

/** Reading-progress and zoom persisted per book in localStorage. */
export function useReaderPersistence(bookId: string) {
  function getSavedPage(): number | null {
    try {
      const raw = localStorage.getItem(keyFor('page', bookId))
      if (!raw) return null
      const n = parseInt(raw, 10)
      return Number.isNaN(n) ? null : n
    } catch {
      return null
    }
  }

  function setSavedPage(page: number) {
    try {
      localStorage.setItem(keyFor('page', bookId), String(page))
    } catch {
      // Storage may be unavailable (private browsing); progress just won't persist.
    }
  }

  function getSavedZoom(): number | null {
    try {
      const raw = localStorage.getItem(keyFor('zoom', bookId))
      if (!raw) return null
      const n = parseFloat(raw)
      return Number.isNaN(n) ? null : n
    } catch {
      return null
    }
  }

  function setSavedZoom(zoom: number) {
    try {
      localStorage.setItem(keyFor('zoom', bookId), String(zoom))
    } catch {
      // ignore
    }
  }

  return { getSavedPage, setSavedPage, getSavedZoom, setSavedZoom }
}
