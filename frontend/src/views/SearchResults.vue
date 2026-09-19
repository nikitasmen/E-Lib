<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as booksApi from '@/api/books'
import { isApiSuccess } from '@/types/api'
import type { Book } from '@/types/book'
import BookCard from '@/components/BookCard.vue'

const route = useRoute()
const router = useRouter()

const searchTerm = ref(typeof route.query.q === 'string' ? route.query.q : '')
const books = ref<Book[]>([])
const loading = ref(false)
const error = ref('')
const hasSearched = ref(false)

async function runSearch(term: string) {
  const trimmed = term.trim()
  if (!trimmed) {
    books.value = []
    hasSearched.value = false
    return
  }
  loading.value = true
  error.value = ''
  hasSearched.value = true
  try {
    const response = await booksApi.searchBooks(trimmed)
    const body = response.data
    books.value = isApiSuccess(body) ? body.data : []
    if (!books.value.length) {
      error.value = `No books found for "${trimmed}".`
    }
  } catch {
    error.value = 'An error occurred while searching.'
  } finally {
    loading.value = false
  }
}

function submit() {
  router.push({ path: '/search', query: { q: searchTerm.value } })
}

watch(
  () => route.query.q,
  (q) => {
    searchTerm.value = typeof q === 'string' ? q : ''
    runSearch(searchTerm.value)
  },
  { immediate: true },
)
</script>

<template>
  <div class="container search-page" data-testid="search-page">
    <h1>Search</h1>
    <form class="search-bar" data-testid="search-form" @submit.prevent="submit">
      <input
        v-model="searchTerm"
        type="search"
        placeholder="Search by title…"
        aria-label="Search books"
        data-testid="search-input"
      />
      <button type="submit" class="btn btn-primary" data-testid="search-submit">Search</button>
    </form>

    <div v-if="loading" class="spinner" role="status" aria-label="Searching" data-testid="search-loading"></div>
    <p v-else-if="error" class="text-muted" data-testid="search-message">{{ error }}</p>
    <div v-else-if="hasSearched" class="grid-books" data-testid="search-results">
      <BookCard v-for="book in books" :key="String(book._id)" :book="book" />
    </div>
    <p v-else class="text-muted" data-testid="search-message">Enter a title to search the catalog.</p>
  </div>
</template>

<style scoped src="@/css/views/SearchResults.css"></style>
