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

    <section id="featured" class="container featured-section">
      <h2 class="section-title">Featured Collection</h2>

      <div v-if="loading" class="spinner" role="status" aria-label="Loading featured books"></div>
      <p v-else-if="loadError" class="text-muted centered">{{ loadError }}</p>
      <div v-else class="grid-books">
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

<style scoped>
.hero {
  background: linear-gradient(rgba(20, 34, 54, 0.82), rgba(20, 34, 54, 0.82)), url('/assets/img/library-bg.jpg');
  background-size: cover;
  background-position: center;
  color: #fff;
  text-align: center;
  padding: 5rem 0;
}

.hero-inner h1 {
  color: #fff;
  font-size: 2.75rem;
}

.divider {
  height: 4px;
  width: 70px;
  background: var(--color-accent);
  margin: 1.25rem auto;
}

.tagline {
  font-size: 1.2rem;
  max-width: 640px;
  margin: 0 auto 2rem;
  color: #e5e8ee;
}

.hero-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.hero-btn-light {
  color: #fff;
  border-color: rgba(255, 255, 255, 0.6);
}

.hero-btn-light:hover {
  color: #fff;
  border-color: #fff;
}

.featured-section {
  padding: 3.5rem 1.5rem;
}

.section-title {
  text-align: center;
  margin-bottom: 2.5rem;
}

.centered {
  text-align: center;
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
}

.modal-panel {
  position: relative;
  width: 100%;
  max-width: 420px;
  padding: 2rem;
}

.modal-close {
  position: absolute;
  top: 0.75rem;
  right: 0.9rem;
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  color: var(--color-text-muted);
}
</style>
