import { ref, type Ref } from 'vue'
import type { PDFDocumentProxy } from 'pdfjs-dist'

/** Full-text search across pages via PDF.js getTextContent(). */
export function useReaderSearch(pdfDoc: Ref<PDFDocumentProxy | null>) {
  const term = ref('')
  const searching = ref(false)
  const notFound = ref(false)

  async function search(onFound: (pageNumber: number) => void) {
    const query = term.value.trim().toLowerCase()
    const doc = pdfDoc.value
    if (!query || !doc) return

    searching.value = true
    notFound.value = false
    try {
      for (let pageNumber = 1; pageNumber <= doc.numPages; pageNumber++) {
        const page = await doc.getPage(pageNumber)
        const content = await page.getTextContent()
        const text = content.items
          .map((item) => ('str' in item ? item.str : ''))
          .join(' ')
          .toLowerCase()
        if (text.includes(query)) {
          onFound(pageNumber)
          return
        }
      }
      notFound.value = true
    } finally {
      searching.value = false
    }
  }

  return { term, searching, notFound, search }
}
