<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import * as adminApi from '@/api/admin'
import { isApiSuccess, apiErrorMessage, type ApiError } from '@/types/api'
import type { Book } from '@/types/book'
import { idToString } from '@/types/book'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const books = ref<Book[]>([])
const loading = ref(true)
const loadError = ref('')
const busyIds = ref<Set<string>>(new Set())

async function loadBooks() {
  loading.value = true
  loadError.value = ''
  try {
    const response = await adminApi.getAllBooks()
    const body = response.data
    books.value = isApiSuccess(body) ? body.data : []
  } catch {
    loadError.value = 'Failed to fetch books.'
  } finally {
    loading.value = false
  }
}

function isBusy(id: string) {
  return busyIds.value.has(id)
}

async function toggleStatus(book: Book) {
  const id = idToString(book._id)
  const nextStatus = book.status === 'public' ? 'draft' : 'public'
  busyIds.value.add(id)
  try {
    const response = await adminApi.updateBook(id, { status: nextStatus })
    if (isApiSuccess(response.data)) {
      book.status = nextStatus
      toast.success(`Status updated to ${nextStatus}`)
    } else {
      toast.error(apiErrorMessage(response.data as ApiError, 'Failed to update status'))
    }
  } catch {
    toast.error('An error occurred while updating status.')
  } finally {
    busyIds.value.delete(id)
  }
}

async function toggleFeatured(book: Book) {
  const id = idToString(book._id)
  const next = !book.featured
  busyIds.value.add(id)
  try {
    const response = await adminApi.updateBook(id, { featured: next })
    if (isApiSuccess(response.data)) {
      book.featured = next
    } else {
      toast.error(apiErrorMessage(response.data as ApiError, 'Failed to update featured status'))
    }
  } catch {
    toast.error('An error occurred while updating featured status.')
  } finally {
    busyIds.value.delete(id)
  }
}

async function handleDelete(book: Book) {
  if (!confirm(`Delete "${book.title}"? This action can't be undone.`)) return
  const id = idToString(book._id)
  busyIds.value.add(id)
  try {
    const response = await adminApi.deleteBook(id)
    if (isApiSuccess(response.data)) {
      books.value = books.value.filter((b) => idToString(b._id) !== id)
      toast.success('Book deleted.')
    } else {
      toast.error(apiErrorMessage(response.data as ApiError, 'Delete failed'))
    }
  } catch {
    toast.error('An error occurred while deleting.')
  } finally {
    busyIds.value.delete(id)
  }
}

// --- Edit modal ---
const editing = ref<Book | null>(null)
const editForm = reactive({
  title: '',
  author: '',
  description: '',
  status: 'draft',
  featured: false,
  isbn: '',
  downloadable: true,
  categoriesText: '',
})
const editSaving = ref(false)
const editError = ref('')

const editCategories = computed(() =>
  editForm.categoriesText
    .split(',')
    .map((c) => c.trim())
    .filter((c) => c.length > 0),
)

function openEdit(book: Book) {
  editing.value = book
  editForm.title = book.title ?? ''
  editForm.author = book.author ?? ''
  editForm.description = book.description ?? ''
  editForm.status = book.status ?? 'draft'
  editForm.featured = !!book.featured
  editForm.isbn = book.isbn ?? ''
  editForm.downloadable = book.downloadable !== false
  editForm.categoriesText = (book.categories ?? []).join(', ')
  editError.value = ''
}

function closeEdit() {
  editing.value = null
}

async function submitEdit() {
  if (!editing.value) return
  editError.value = ''
  editSaving.value = true
  const id = idToString(editing.value._id)
  try {
    const response = await adminApi.updateBook(id, {
      title: editForm.title,
      author: editForm.author,
      description: editForm.description,
      status: editForm.status,
      featured: editForm.featured,
      isbn: editForm.isbn,
      downloadable: editForm.downloadable,
      categories: editCategories.value,
    })
    if (isApiSuccess(response.data)) {
      Object.assign(editing.value, {
        title: editForm.title,
        author: editForm.author,
        description: editForm.description,
        status: editForm.status,
        featured: editForm.featured,
        isbn: editForm.isbn,
        downloadable: editForm.downloadable,
        categories: editCategories.value,
      })
      toast.success('Book updated successfully.')
      closeEdit()
    } else {
      editError.value = apiErrorMessage(response.data as ApiError, 'Update failed')
    }
  } catch (err: any) {
    editError.value = err.response?.data?.message ?? 'An error occurred during update.'
  } finally {
    editSaving.value = false
  }
}

onMounted(loadBooks)
</script>

<template>
  <div class="container admin-page">
    <div class="admin-header">
      <h1>Manage Books</h1>
      <RouterLink to="/admin/mass-upload" class="btn btn-primary">Mass Upload PDFs</RouterLink>
    </div>

    <div v-if="loading" class="spinner" role="status" aria-label="Loading books"></div>
    <p v-else-if="loadError" class="text-muted">{{ loadError }}</p>

    <div v-else class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Author</th>
            <th>ISBN</th>
            <th>Status</th>
            <th>Featured</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="book in books" :key="String(book._id)">
            <td>{{ book.title }}</td>
            <td>{{ book.author }}</td>
            <td>{{ book.isbn }}</td>
            <td>
              <button
                type="button"
                class="btn btn-sm"
                :class="book.status === 'public' ? 'btn-primary' : 'btn-outline'"
                :disabled="isBusy(idToString(book._id))"
                @click="toggleStatus(book)"
              >
                {{ book.status === 'public' ? 'Public' : 'Draft' }}
              </button>
            </td>
            <td>
              <button
                type="button"
                class="btn btn-sm"
                :class="book.featured ? 'btn-primary' : 'btn-outline'"
                :disabled="isBusy(idToString(book._id))"
                @click="toggleFeatured(book)"
              >
                {{ book.featured ? 'Featured' : 'Regular' }}
              </button>
            </td>
            <td class="actions-cell">
              <button type="button" class="btn btn-sm btn-outline" @click="openEdit(book)">Edit</button>
              <RouterLink :to="`/read/${idToString(book._id)}`" class="btn btn-sm btn-outline">Preview</RouterLink>
              <button type="button" class="btn btn-sm btn-outline danger" :disabled="isBusy(idToString(book._id))" @click="handleDelete(book)">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="editing" class="modal-overlay" @click.self="closeEdit">
      <div class="modal-panel card">
        <h2>Edit Book</h2>
        <p v-if="editError" class="alert alert-danger">{{ editError }}</p>
        <form @submit.prevent="submitEdit">
          <div class="form-grid">
            <div class="form-field">
              <label for="edit-title">Title</label>
              <input id="edit-title" v-model="editForm.title" type="text" required />
            </div>
            <div class="form-field">
              <label for="edit-author">Author</label>
              <input id="edit-author" v-model="editForm.author" type="text" />
            </div>
            <div class="form-field span-2">
              <label for="edit-description">Description</label>
              <textarea id="edit-description" v-model="editForm.description" rows="3"></textarea>
            </div>
            <div class="form-field">
              <label for="edit-status">Status</label>
              <select id="edit-status" v-model="editForm.status">
                <option value="draft">Draft</option>
                <option value="public">Public</option>
              </select>
            </div>
            <div class="form-field">
              <label for="edit-isbn">ISBN</label>
              <input id="edit-isbn" v-model="editForm.isbn" type="text" />
            </div>
            <div class="form-field checkbox-field">
              <label><input v-model="editForm.featured" type="checkbox" /> Featured</label>
            </div>
            <div class="form-field checkbox-field">
              <label><input v-model="editForm.downloadable" type="checkbox" /> Downloadable</label>
            </div>
            <div class="form-field span-2">
              <label for="edit-categories">Categories</label>
              <input id="edit-categories" v-model="editForm.categoriesText" type="text" placeholder="Fiction, Fantasy, Adventure" />
              <small class="text-muted">Separate categories with commas.</small>
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-outline" @click="closeEdit">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="editSaving">{{ editSaving ? 'Saving…' : 'Save Changes' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-page {
  padding: 2.5rem 1.5rem;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.table-wrap {
  overflow-x: auto;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--color-surface);
}

.admin-table th,
.admin-table td {
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid var(--color-border);
  text-align: left;
  vertical-align: middle;
}

.admin-table th {
  background: var(--color-navy);
  color: #fff;
  font-weight: 600;
}

.actions-cell {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.btn.danger {
  color: var(--color-danger);
  border-color: var(--color-danger);
}

.btn.danger:hover {
  background: var(--color-danger);
  color: #fff;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(20, 34, 54, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 40;
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-panel {
  width: 100%;
  max-width: 640px;
  padding: 1.75rem 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem 1rem;
}

.form-grid .span-2 {
  grid-column: 1 / -1;
}

.checkbox-field label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--color-text-muted);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
  margin-top: 1.25rem;
}
</style>
