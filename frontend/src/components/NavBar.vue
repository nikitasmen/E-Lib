<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import * as usersApi from '@/api/users'

const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

const searchTerm = ref('')
const menuOpen = ref(false)

function submitSearch() {
  const term = searchTerm.value.trim()
  if (!term) return
  router.push({ path: '/search', query: { q: term } })
}

async function handleLogout() {
  try {
    await usersApi.logout()
  } catch {
    // Session cookie may already be gone; token-based logout still proceeds client-side.
  }
  auth.logout()
  menuOpen.value = false
  toast.info('Logged out')
  router.push('/')
}
</script>

<template>
  <nav class="navbar" data-testid="navbar">
    <div class="container navbar-inner">
      <RouterLink to="/" class="brand" data-testid="navbar-brand">
        <span class="brand-mark">📖</span>
        <span>Epictetus Library</span>
      </RouterLink>

      <div class="nav-links">
        <RouterLink to="/" class="nav-link" data-testid="nav-link-home">Home</RouterLink>
        <RouterLink to="/browse" class="nav-link" data-testid="nav-link-browse">Browse</RouterLink>
      </div>

      <form class="search-form" data-testid="nav-search-form" @submit.prevent="submitSearch">
        <input
          v-model="searchTerm"
          type="search"
          placeholder="Search titles…"
          aria-label="Search books"
          data-testid="nav-search-input"
        />
        <button type="submit" class="btn btn-primary" aria-label="Search" data-testid="nav-search-submit">
          Search
        </button>
      </form>

      <div class="auth-area">
        <template v-if="auth.isAuthenticated">
          <button type="button" class="user-chip" data-testid="nav-user-chip" @click="menuOpen = !menuOpen">
            <span class="avatar">{{ (auth.username || '?').charAt(0).toUpperCase() }}</span>
            <span class="username" data-testid="nav-username">{{ auth.username }}</span>
          </button>
          <div v-if="menuOpen" class="user-menu" data-testid="nav-user-menu" @click="menuOpen = false">
            <RouterLink to="/profile" class="user-menu-item" data-testid="nav-menu-profile">Profile</RouterLink>
            <RouterLink
              v-if="auth.isAdmin"
              to="/admin"
              class="user-menu-item"
              data-testid="nav-menu-dashboard"
            >
              Dashboard
            </RouterLink>
            <button type="button" class="user-menu-item" data-testid="nav-menu-logout" @click="handleLogout">
              Log out
            </button>
          </div>
        </template>
        <template v-else>
          <RouterLink to="/login" class="btn btn-outline" data-testid="nav-login-link">Log in</RouterLink>
          <RouterLink to="/signup" class="btn btn-primary" data-testid="nav-signup-link">Sign up</RouterLink>
        </template>
      </div>
    </div>
  </nav>
</template>

<style scoped src="@/css/components/NavBar.css"></style>
