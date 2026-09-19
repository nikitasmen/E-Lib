function keyFor(prefix: string, bookId: string): string {
  return `elib_pdf_${prefix}_${bookId}`
}

/** Reading-progress and zoom persisted per book in localStorage. */
export function useReaderPersistence(bookId: string) {
  function getNum(prefix: string, parse: (raw: string) => number): number | null {
    try {
      const raw = localStorage.getItem(keyFor(prefix, bookId))
      if (!raw) return null
      const n = parse(raw)
      return Number.isNaN(n) ? null : n
    } catch {
      return null
    }
  }

  function setNum(prefix: string, value: number) {
    try {
      // Storage may be unavailable (private browsing); progress just won't persist.
      localStorage.setItem(keyFor(prefix, bookId), String(value))
    } catch {
      // ignore
    }
  }

  return {
    getSavedPage: () => getNum('page', (raw) => parseInt(raw, 10)),
    setSavedPage: (page: number) => setNum('page', page),
    getSavedZoom: () => getNum('zoom', parseFloat),
    setSavedZoom: (zoom: number) => setNum('zoom', zoom),
  }
}
