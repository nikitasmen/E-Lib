<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePdfDocument } from '@/composables/reader/usePdfDocument'
import { useZoomRotate, DEFAULT_SCALE } from '@/composables/reader/useZoomRotate'
import { useReaderSearch } from '@/composables/reader/useReaderSearch'
import { useReaderNotes } from '@/composables/reader/useReaderNotes'
import { useReaderPersistence } from '@/composables/reader/useReaderPersistence'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const toast = useToast()
const bookId = route.params.id as string

const persistence = useReaderPersistence(bookId)
const savedZoom = persistence.getSavedZoom()

const zoomRotate = useZoomRotate(savedZoom ?? DEFAULT_SCALE)
const pdf = usePdfDocument(bookId, { scale: zoomRotate.scale, rotation: zoomRotate.rotation })
const search = useReaderSearch(pdf.pdfDoc)
const notes = useReaderNotes(bookId)

const readerShell = ref<HTMLElement | null>(null)
const pagesContainer = ref<HTMLElement | null>(null)

const themes = ['light', 'sepia', 'dark'] as const
const themeIndex = ref(0)
const themeClass = computed(() => `theme-${themes[themeIndex.value]}`)

const pageInput = ref(1)
const notesOpen = ref(false)
const noteText = ref('')

const zoomPercent = computed(() => Math.round(zoomRotate.scale.value * 100))

watch(pdf.currentPage, (page) => {
  pageInput.value = page
  persistence.setSavedPage(page)
})

async function applyZoom(action: () => void) {
  action()
  persistence.setSavedZoom(zoomRotate.scale.value)
  await pdf.rebuildPages()
}

async function handleFitWidth() {
  if (!pagesContainer.value) return
  const containerWidth = pagesContainer.value.clientWidth - 48
  const baseWidth = await pdf.getPageBaseWidth(1)
  await applyZoom(() => zoomRotate.fitWidth(containerWidth, baseWidth))
}

async function handleRotate() {
  zoomRotate.rotate()
  await pdf.rebuildPages()
}

function handleGoToPage() {
  pdf.goToPage(pageInput.value)
}

async function handleSearch() {
  await search.search((pageNumber) => pdf.goToPage(pageNumber))
  if (search.notFound.value) {
    toast.info('No matches found.')
  }
}

function cycleTheme() {
  themeIndex.value = (themeIndex.value + 1) % themes.length
}

function handleAddNote() {
  if (!noteText.value.trim()) return
  notes.addNote(pdf.currentPage.value, noteText.value)
  noteText.value = ''
}

async function handleExportNotes() {
  await notes.exportNotes()
  toast.success('Notes copied to clipboard as JSON.')
}

function handleClearNotes() {
  if (confirm('Delete all notes for this book in this browser?')) {
    notes.clearNotes()
  }
}

function handlePrint() {
  window.print()
}

function toggleFullscreen() {
  if (!readerShell.value) return
  if (document.fullscreenElement) {
    document.exitFullscreen?.()
  } else {
    readerShell.value.requestFullscreen?.()
  }
}

function handleKeydown(event: KeyboardEvent) {
  const tag = (event.target as HTMLElement)?.tagName
  if (tag === 'INPUT' || tag === 'TEXTAREA') return
  if (event.key === '+' || event.key === '=') {
    event.preventDefault()
    applyZoom(() => zoomRotate.zoomIn())
  } else if (event.key === '-' || event.key === '_') {
    event.preventDefault()
    applyZoom(() => zoomRotate.zoomOut())
  }
}

onMounted(async () => {
  document.addEventListener('keydown', handleKeydown)
  pdf.setContainer(pagesContainer.value)
  await pdf.load()

  if (savedZoom === null) {
    await handleFitWidth()
  }

  const savedPage = persistence.getSavedPage()
  if (savedPage && savedPage >= 1 && savedPage <= pdf.numPages.value) {
    setTimeout(() => pdf.goToPage(savedPage), 400)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
  pdf.destroy()
})
</script>

<template>
  <div ref="readerShell" class="pdf-reader-shell">
    <div class="pdf-toolbar">
      <div class="toolbar-group" role="group" aria-label="Zoom">
        <button type="button" class="btn btn-outline" title="Zoom out" @click="applyZoom(() => zoomRotate.zoomOut())">−</button>
        <span class="zoom-label">{{ zoomPercent }}%</span>
        <button type="button" class="btn btn-outline" title="Zoom in" @click="applyZoom(() => zoomRotate.zoomIn())">+</button>
        <button type="button" class="btn btn-outline" title="Fit width" @click="handleFitWidth">⇔</button>
        <button type="button" class="btn btn-outline" title="Reset zoom" @click="applyZoom(() => zoomRotate.resetZoom())">1.5×</button>
      </div>

      <div class="toolbar-group">
        <label class="visually-hidden" for="reader-page-input">Page</label>
        <input id="reader-page-input" v-model.number="pageInput" type="number" min="1" :max="pdf.numPages.value" @keydown.enter="handleGoToPage" />
        <span class="text-muted">/ {{ pdf.numPages.value || '—' }}</span>
        <button type="button" class="btn btn-outline" title="Go to page" @click="handleGoToPage">Go</button>
      </div>

      <div class="toolbar-group search-group">
        <input v-model="search.term.value" type="search" placeholder="Search in document…" @keydown.enter="handleSearch" />
        <button type="button" class="btn btn-outline" :disabled="search.searching.value" title="Find" @click="handleSearch">Find</button>
      </div>

      <div class="toolbar-group toolbar-end">
        <button type="button" class="btn btn-outline" title="Reading theme" @click="cycleTheme">Theme</button>
        <button type="button" class="btn btn-outline" title="Rotate" @click="handleRotate">⟳</button>
        <button type="button" class="btn btn-outline" title="Fullscreen" @click="toggleFullscreen">⛶</button>
        <button type="button" class="btn btn-outline" title="Print" @click="handlePrint">Print</button>
        <button type="button" class="btn btn-primary" title="Notes" @click="notesOpen = !notesOpen">Notes</button>
      </div>
    </div>

    <div class="pdf-reader-body">
      <div v-if="pdf.loading.value" class="spinner" role="status" aria-label="Loading PDF"></div>
      <p v-else-if="pdf.error.value" class="alert alert-danger reader-error">{{ pdf.error.value }}</p>

      <div ref="pagesContainer" class="pdf-container" :class="themeClass" :hidden="pdf.loading.value || !!pdf.error.value"></div>

      <aside v-if="notesOpen" class="pdf-notes-panel">
        <div class="notes-header">
          <strong>My notes</strong>
          <button type="button" class="btn-close" aria-label="Close" @click="notesOpen = false">&times;</button>
        </div>
        <p class="text-muted small notes-hint">Saved in this browser for this book only.</p>
        <div class="notes-add">
          <label class="small" for="reader-note-text">Add note (page {{ pdf.currentPage.value }})</label>
          <textarea id="reader-note-text" v-model="noteText" rows="3" placeholder="Thoughts, quotes, reminders…"></textarea>
          <div class="notes-actions">
            <button type="button" class="btn btn-primary" @click="handleAddNote">Add</button>
            <button type="button" class="btn btn-outline" @click="handleExportNotes">Export</button>
            <button type="button" class="btn btn-outline" @click="handleClearNotes">Clear all</button>
          </div>
        </div>
        <ul class="notes-list">
          <li v-if="!notes.notes.value.length" class="text-muted small">No notes yet.</li>
          <li v-for="note in notes.notes.value" :key="note.id" class="note-item">
            <div class="note-item-header">
              <button type="button" class="link-btn" @click="pdf.goToPage(note.page)">Page {{ note.page }}</button>
              <button type="button" class="btn-close" aria-label="Delete" @click="notes.removeNote(note.id)">&times;</button>
            </div>
            <p class="note-text">{{ note.text }}</p>
          </li>
        </ul>
      </aside>
    </div>
  </div>
</template>

<style scoped src="@/css/views/Reader.css"></style>
