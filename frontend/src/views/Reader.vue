<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePdfDocument } from '@/composables/reader/usePdfDocument'
import { useZoomRotate, DEFAULT_SCALE } from '@/composables/reader/useZoomRotate'
import { useReaderSearch } from '@/composables/reader/useReaderSearch'
import { useReaderNotes } from '@/composables/reader/useReaderNotes'
import { useReaderPersistence } from '@/composables/reader/useReaderPersistence'
import { useFullscreen } from '@/composables/reader/useFullscreen'
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
const fullscreen = useFullscreen()

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
        <button type="button" class="btn btn-outline" title="Fullscreen" @click="fullscreen.toggle(readerShell)">⛶</button>
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

<style scoped>
.pdf-reader-shell {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 56px);
}

.pdf-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 1rem;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  flex-shrink: 0;
}

.toolbar-group {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.toolbar-end {
  margin-left: auto;
}

.zoom-label {
  min-width: 3.5rem;
  text-align: center;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.toolbar-group input[type='number'] {
  width: 3.5rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 0.3rem 0.4rem;
}

.search-group input[type='search'] {
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 0.35rem 0.6rem;
  width: 180px;
}

.pdf-reader-body {
  flex: 1;
  display: flex;
  overflow: hidden;
  position: relative;
}

.pdf-container {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem 0;
  text-align: center;
}

.pdf-container.theme-light {
  background: #e9ecef;
}

.pdf-container.theme-sepia {
  background: #f4ecd8;
}

.pdf-container.theme-dark {
  background: #1a1d21;
}

.pdf-container :deep(.pdf-page) {
  position: relative;
  display: inline-block;
  margin: 20px auto;
  background: #fff;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
}

.pdf-container :deep(.pdf-page-placeholder) {
  height: 800px;
  margin: 20px auto;
  max-width: 90%;
  background: rgba(255, 255, 255, 0.4);
  border: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-muted);
}

.pdf-container :deep(.pdf-page-label) {
  position: absolute;
  bottom: 10px;
  right: 10px;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  padding: 3px 9px;
  border-radius: 4px;
  font-size: 0.75rem;
}

.reader-error {
  margin: 2rem auto;
  max-width: 480px;
}

.pdf-notes-panel {
  width: 320px;
  max-width: 100%;
  flex-shrink: 0;
  border-left: 1px solid var(--color-border);
  background: var(--color-surface);
  overflow-y: auto;
  padding: 1rem;
}

.notes-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.notes-hint {
  margin-bottom: 0.75rem;
}

.notes-add textarea {
  width: 100%;
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 0.5rem;
  font-family: inherit;
  margin: 0.4rem 0;
  resize: vertical;
}

.notes-actions {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.notes-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.note-item {
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 0.5rem 0.65rem;
}

.note-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.link-btn {
  background: none;
  border: none;
  color: var(--color-primary);
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  padding: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.1rem;
  line-height: 1;
  color: var(--color-text-muted);
  cursor: pointer;
}

.note-text {
  margin: 0.3rem 0 0;
  font-size: 0.85rem;
  word-break: break-word;
}

@media print {
  .pdf-toolbar,
  .pdf-notes-panel {
    display: none !important;
  }

  .pdf-container {
    overflow: visible !important;
    height: auto !important;
  }
}

@media (max-width: 900px) {
  .pdf-notes-panel {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    z-index: 5;
    width: min(100%, 320px);
  }
}
</style>
