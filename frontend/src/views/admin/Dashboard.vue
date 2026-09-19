<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import * as adminApi from '@/api/admin'
import { isApiSuccess, apiErrorMessage, requestErrorMessage, type ApiError } from '@/types/api'
import type { Book } from '@/types/book'
import { idToString } from '@/types/book'
import { useToast } from '@/composables/useToast'
import { useCategoryOptions } from '@/composables/useCategoryOptions'
import CategoryPicker from '@/components/CategoryPicker.vue'

const toast = useToast()

const books = ref<Book[]>([])
const loading = ref(true)
const loadError = ref('')
const busyIds = ref<Set<string>>(new Set())
const availableCategories = useCategoryOptions()

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
  categories: [] as string[],
})
const editSaving = ref(false)
const editError = ref('')

function openEdit(book: Book) {
  editing.value = book
  editForm.title = book.title ?? ''
  editForm.author = book.author ?? ''
  editForm.description = book.description ?? ''
  editForm.status = book.status ?? 'draft'
  editForm.featured = !!book.featured
  editForm.isbn = book.isbn ?? ''
  editForm.downloadable = book.downloadable !== false
  editForm.categories = [...(book.categories ?? [])]
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
      categories: editForm.categories,
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
        categories: editForm.categories,
      })
      toast.success('Book updated successfully.')
      closeEdit()
    } else {
      editError.value = apiErrorMessage(response.data as ApiError, 'Update failed')
    }
  } catch (err) {
    editError.value = requestErrorMessage(err, 'An error occurred during update.')
  } finally {
    editSaving.value = false
  }
}

onMounted(loadBooks)
</script>

<template>
  <div class="container admin-page" data-testid="dashboard-page">
    <div class="admin-header">
      <h1 data-testid="dashboard-heading">Manage Books</h1>
      <RouterLink to="/admin/upload" class="btn btn-primary" data-testid="upload-pdf-link">
        Upload PDF
      </RouterLink>
    </div>

    <div v-if="loading" class="spinner" role="status" aria-label="Loading books" data-testid="dashboard-loading"></div>
    <p v-else-if="loadError" class="text-muted" data-testid="dashboard-error">{{ loadError }}</p>

    <div v-else class="table-wrap">
      <table class="admin-table" data-testid="books-table">
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
          <tr v-for="book in books" :key="String(book._id)" :data-testid="`book-row-${idToString(book._id)}`">
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
              <CategoryPicker
                id="edit-categories"
                v-model="editForm.categories"
                :options="availableCategories"
                placeholder="Fiction, Fantasy, Adventure…"
              />
              <small class="text-muted">Pick an existing category or type a new one and press Enter.</small>
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

<style scoped src="@/css/views/admin/Dashboard.css"></style>
