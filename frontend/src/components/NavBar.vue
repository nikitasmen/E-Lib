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
  <nav class="navbar">
    <div class="container navbar-inner">
      <RouterLink to="/" class="brand">
        <span class="brand-mark">📖</span>
        <span>Epictetus Library</span>
      </RouterLink>

      <div class="nav-links">
        <RouterLink to="/" class="nav-link">Home</RouterLink>
        <RouterLink to="/browse" class="nav-link">Browse</RouterLink>
      </div>

      <form class="search-form" @submit.prevent="submitSearch">
        <input v-model="searchTerm" type="search" placeholder="Search titles…" aria-label="Search books" />
        <button type="submit" class="btn btn-primary" aria-label="Search">Search</button>
      </form>

      <div class="auth-area">
        <template v-if="auth.isAuthenticated">
          <button type="button" class="user-chip" @click="menuOpen = !menuOpen">
            <span class="avatar">{{ (auth.username || '?').charAt(0).toUpperCase() }}</span>
            <span class="username">{{ auth.username }}</span>
          </button>
          <div v-if="menuOpen" class="user-menu" @click="menuOpen = false">
            <RouterLink to="/profile" class="user-menu-item">Profile</RouterLink>
            <RouterLink v-if="auth.isAdmin" to="/admin" class="user-menu-item">Dashboard</RouterLink>
            <button type="button" class="user-menu-item" @click="handleLogout">Log out</button>
          </div>
        </template>
        <template v-else>
          <RouterLink to="/login" class="btn btn-outline">Log in</RouterLink>
          <RouterLink to="/signup" class="btn btn-primary">Sign up</RouterLink>
        </template>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.navbar {
  background: var(--color-navy);
  color: #fff;
  position: sticky;
  top: 0;
  z-index: 20;
  box-shadow: var(--shadow-sm);
}

.navbar-inner {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 0.75rem 1.5rem;
  flex-wrap: wrap;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--font-serif);
  font-size: 1.15rem;
  font-weight: 700;
  color: #fff;
}

.brand:hover {
  text-decoration: none;
  opacity: 0.9;
}

.nav-links {
  display: flex;
  gap: 1rem;
}

.nav-link {
  color: #d8dde5;
  font-weight: 500;
  padding: 0.35rem 0;
}

.nav-link:hover,
.nav-link.router-link-active {
  color: #fff;
  text-decoration: none;
  border-bottom: 2px solid var(--color-accent);
}

.search-form {
  display: flex;
  flex: 1;
  max-width: 360px;
  min-width: 160px;
}

.search-form input {
  flex: 1;
  border: none;
  border-radius: var(--radius) 0 0 var(--radius);
  padding: 0.5rem 0.75rem;
}

.search-form .btn {
  border-radius: 0 var(--radius) var(--radius) 0;
}

.auth-area {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: auto;
  position: relative;
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.08);
  border: none;
  border-radius: 999px;
  color: #fff;
  padding: 0.3rem 0.8rem 0.3rem 0.3rem;
  cursor: pointer;
}

.avatar {
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 50%;
  background: var(--color-accent);
  color: #201c16;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  background: var(--color-surface);
  color: var(--color-text);
  border-radius: var(--radius);
  box-shadow: var(--shadow-md);
  overflow: hidden;
  min-width: 140px;
}

.user-menu-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 0.6rem 1rem;
  background: none;
  border: none;
  cursor: pointer;
}

.user-menu-item:hover {
  background: var(--color-bg);
}
</style>
