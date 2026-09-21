import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

export interface ReaderNote {
  id: string
  page: number
  text: string
  ts: number
}

function keyFor(bookId: string, userKey: string): string {
  return `elib_pdf_notes_${userKey}_${bookId}`
}

function load(bookId: string, userKey: string): ReaderNote[] {
  try {
    const raw = localStorage.getItem(keyFor(bookId, userKey))
    const parsed = raw ? JSON.parse(raw) : []
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}

/** Add/export-JSON/clear notes, localStorage-backed per book id and signed-in user. */
export function useReaderNotes(bookId: string) {
  // Namespacing by user (not just book) stops a later user of the same browser — e.g. a
  // shared library terminal — from reading or overwriting a previous user's private notes
  // for the same book (CWE-922); logout never cleared the old book-only key. Read once at
  // setup, same as bookId: the composable's lifetime is one reader session.
  const auth = useAuthStore()
  const userKey = auth.claims?.user_id ?? 'anon'

  const notes = ref<ReaderNote[]>(load(bookId, userKey).sort(byPageThenRecency))

  function byPageThenRecency(a: ReaderNote, b: ReaderNote): number {
    return a.page - b.page || b.ts - a.ts
  }

  function persist() {
    try {
      localStorage.setItem(keyFor(bookId, userKey), JSON.stringify(notes.value))
    } catch {
      // ignore
    }
  }

  function addNote(page: number, text: string) {
    const trimmed = text.trim()
    if (!trimmed) return
    notes.value = [
      ...notes.value,
      { id: `${Date.now()}_${Math.random().toString(36).slice(2)}`, page, text: trimmed, ts: Date.now() },
    ].sort(byPageThenRecency)
    persist()
  }

  function removeNote(id: string) {
    notes.value = notes.value.filter((n) => n.id !== id)
    persist()
  }

  function clearNotes() {
    notes.value = []
    persist()
  }

  /** Copies notes as JSON to the clipboard and returns the JSON string as a fallback. */
  async function exportNotes(): Promise<string> {
    const json = JSON.stringify(notes.value, null, 2)
    try {
      await navigator.clipboard.writeText(json)
    } catch {
      // Clipboard API may be unavailable; caller can still use the returned JSON.
    }
    return json
  }

  return { notes, addNote, removeNote, clearNotes, exportNotes }
}
