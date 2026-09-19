<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import * as booksApi from '@/api/books'
import { isApiSuccess } from '@/types/api'
import type { Book } from '@/types/book'
import { useAuthStore } from '@/stores/auth'
import BookCard from '@/components/BookCard.vue'
import LoginForm from '@/components/LoginForm.vue'
import SignupForm from '@/components/SignupForm.vue'

const auth = useAuthStore()

const books = ref<Book[]>([])
const loading = ref(true)
const loadError = ref('')

const showAuthModal = ref(false)
const authModalDismissed = ref(false)
const authTab = ref<'login' | 'signup'>('login')

async function loadFeatured() {
  loading.value = true
  loadError.value = ''
  try {
    const response = await booksApi.getFeaturedBooks()
    const body = response.data
    books.value = isApiSuccess(body) ? body.data : []
    if (!books.value.length) {
      loadError.value = 'No featured books available.'
    }
  } catch {
    loadError.value = 'An error occurred while fetching featured books.'
  } finally {
    loading.value = false
  }
}

function onScroll() {
  if (auth.isAuthenticated || authModalDismissed.value || showAuthModal.value) return
  if (window.scrollY > window.innerHeight * 0.6) {
    showAuthModal.value = true
  }
}

function dismissModal() {
  showAuthModal.value = false
  authModalDismissed.value = true
}

onMounted(() => {
  loadFeatured()
  window.addEventListener('scroll', onScroll, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <div>
    <section class="hero">
      <div class="container hero-inner">
        <h1>Welcome to Epictetus Library</h1>
        <div class="divider"></div>
        <p class="tagline">Discover a world of knowledge with our extensive collection of books and digital resources</p>
        <div class="hero-actions">
          <a href="#featured" class="btn btn-primary">Browse Collection</a>
          <RouterLink to="/search" class="btn btn-outline hero-btn-light">Search Books</RouterLink>
        </div>
      </div>
    </section>

    <section id="featured" class="container featured-section" data-testid="featured-section">
      <h2 class="section-title">Featured Collection</h2>

      <div
        v-if="loading"
        class="spinner"
        role="status"
        aria-label="Loading featured books"
        data-testid="featured-loading"
      ></div>
      <p v-else-if="loadError" class="text-muted centered" data-testid="featured-error">{{ loadError }}</p>
      <div v-else class="grid-books" data-testid="featured-grid">
        <BookCard v-for="book in books" :key="String(book._id)" :book="book" />
      </div>
    </section>

    <div v-if="showAuthModal" class="modal-overlay" @click.self="dismissModal">
      <div class="modal-panel card">
        <button type="button" class="modal-close" aria-label="Close" @click="dismissModal">&times;</button>
        <LoginForm v-if="authTab === 'login'" @success="dismissModal" @switch-to-signup="authTab = 'signup'" />
        <SignupForm v-else @switch-to-login="authTab = 'login'" />
      </div>
    </div>
  </div>
</template>

<style scoped src="@/css/views/Home.css"></style>
