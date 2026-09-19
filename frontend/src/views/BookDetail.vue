<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import * as booksApi from '@/api/books'
import * as reviewsApi from '@/api/reviews'
import * as usersApi from '@/api/users'
import { isApiSuccess, apiErrorMessage, type ApiError } from '@/types/api'
import type { Book } from '@/types/book'
import type { Review } from '@/types/review'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import StarRating from '@/components/StarRating.vue'
import ReviewList from '@/components/ReviewList.vue'
import ReviewForm from '@/components/ReviewForm.vue'

const route = useRoute()
const auth = useAuthStore()
const toast = useToast()
const bookId = route.params.id as string

const book = ref<Book | null>(null)
const reviews = ref<Review[]>([])
const loading = ref(true)
const notFound = ref(false)

const saving = ref(false)
const saved = ref(false)
const downloading = ref(false)

const cover = computed(() => book.value?.thumbnail || booksApi.thumbnailUrl(bookId))
const isDownloadable = computed(() => book.value?.downloadable !== false)

function onImgError(event: Event) {
  ;(event.target as HTMLImageElement).src = '/assets/uploads/thumbnails/placeholder-book.jpg'
}

async function loadReviews() {
  try {
    const reviewResponse = await reviewsApi.getReviews(bookId)
    const reviewBody = reviewResponse.data
    reviews.value = isApiSuccess(reviewBody) ? reviewBody.data : []
  } catch {
    reviews.value = []
  }
}

async function handleSave() {
  saving.value = true
  try {
    const response = await usersApi.saveBook(bookId)
    const body = response.data
    if (isApiSuccess(body)) {
      saved.value = true
      toast.success('Saved to your reading list')
    } else {
      toast.error(apiErrorMessage(body as ApiError, 'Failed to save book'))
    }
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'An error occurred while saving the book')
  } finally {
    saving.value = false
  }
}

async function handleDownload() {
  downloading.value = true
  try {
    const response = await booksApi.downloadBook(bookId)
    const url = window.URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = `${book.value?.title || 'book'}.pdf`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    toast.error('Failed to download the file. Please try again.')
  } finally {
    downloading.value = false
  }
}

onMounted(async () => {
  try {
    const response = await booksApi.getBook(bookId)
    const body = response.data
    if (isApiSuccess(body)) {
      book.value = body.data
      document.title = book.value.title || 'Book Details'
    } else {
      notFound.value = true
    }
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }

  await loadReviews()
})
</script>

<template>
  <div class="container detail-page">
    <div v-if="loading" class="spinner" role="status" aria-label="Loading book details"></div>

    <div v-else-if="notFound" class="not-found">
      <h2>Book not found</h2>
      <RouterLink to="/browse" class="btn btn-outline">Back to Browse</RouterLink>
    </div>

    <div v-else-if="book" class="detail-grid">
      <div class="cover-column">
        <img :src="cover" class="cover" :alt="`Cover of ${book.title}`" @error="onImgError" />

        <div class="actions">
          <RouterLink :to="`/read/${bookId}`" class="btn btn-outline full-width">Online Preview</RouterLink>
          <template v-if="auth.isAuthenticated">
            <button type="button" class="btn btn-outline full-width" :disabled="saving || saved" @click="handleSave">
              {{ saved ? 'Saved to List' : saving ? 'Saving…' : 'Save to Reading List' }}
            </button>
            <button
              v-if="isDownloadable"
              type="button"
              class="btn btn-primary full-width"
              :disabled="downloading"
              @click="handleDownload"
            >
              {{ downloading ? 'Downloading…' : 'Download PDF' }}
            </button>
            <button v-else type="button" class="btn btn-outline full-width" disabled title="This book is not available for download">
              Download Disabled
            </button>
          </template>
          <p v-else class="text-muted login-hint">
            <RouterLink :to="{ path: '/login', query: { redirect: route.fullPath } }">Log in</RouterLink>
            to read online, save, or download this book.
          </p>
        </div>
      </div>

      <div class="info-column">
        <h1>{{ book.title || 'Untitled' }}</h1>

        <div v-if="book.average_rating" class="rating-row">
          <StarRating :rating="book.average_rating" />
          <span class="text-muted">({{ book.average_rating }})</span>
        </div>

        <div v-if="book.categories?.length" class="categories">
          <span v-for="category in book.categories" :key="category" class="badge">{{ category }}</span>
        </div>
        <p v-else class="badge standalone">Uncategorized</p>

        <p class="description">{{ book.description || 'No description available' }}</p>

        <div class="meta-grid">
          <div>
            <p><strong>Author:</strong> {{ book.author || 'Unknown' }}</p>
            <p v-if="book.published_date"><strong>Published:</strong> {{ book.published_date }}</p>
            <p v-if="book.isbn"><strong>ISBN:</strong> {{ book.isbn }}</p>
          </div>
          <div>
            <p v-if="book.language"><strong>Language:</strong> {{ book.language }}</p>
            <p v-if="book.pages"><strong>Pages:</strong> {{ book.pages }}</p>
            <p v-if="book.publisher"><strong>Publisher:</strong> {{ book.publisher }}</p>
          </div>
        </div>

        <hr />
        <ReviewForm v-if="auth.isAuthenticated" :book-id="bookId" @submitted="loadReviews" />
        <p v-else class="alert alert-info">
          <RouterLink :to="{ path: '/login', query: { redirect: route.fullPath } }">Log in</RouterLink>
          to leave a review.
        </p>
        <ReviewList :reviews="reviews" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.detail-page {
  padding: 2.5rem 1.5rem;
}

.not-found {
  text-align: center;
  padding: 3rem 0;
}

.detail-grid {
  display: grid;
  grid-template-columns: minmax(220px, 320px) 1fr;
  gap: 2.5rem;
}

@media (max-width: 720px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

.cover {
  width: 100%;
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
}

.actions {
  margin-top: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.full-width {
  width: 100%;
}

.login-hint {
  margin-top: 1rem;
  font-size: 0.9rem;
}

.rating-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.categories {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 1rem;
}

.badge {
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: 999px;
  font-size: 0.8rem;
  padding: 0.2rem 0.7rem;
  color: var(--color-text-muted);
}

.badge.standalone {
  display: inline-block;
}

.description {
  font-style: italic;
  color: var(--color-text-muted);
  margin: 1rem 0;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin: 1rem 0;
}

.meta-grid p {
  margin: 0.3rem 0;
}
</style>
