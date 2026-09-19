<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import * as usersApi from '@/api/users'
import { isApiSuccess, apiErrorMessage, type ApiError } from '@/types/api'
import type { Book } from '@/types/book'
import { idToString } from '@/types/book'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import BookCard from '@/components/BookCard.vue'

const auth = useAuthStore()
const toast = useToast()

type Tab = 'saved' | 'downloaded' | 'account'
const activeTab = ref<Tab>('saved')

const profileLoading = ref(true)
const email = ref('')
const memberSince = ref('')

const savedBooks = ref<Book[]>([])
const savedLoading = ref(true)
const savedError = ref('')

const downloadedBooks = ref<Book[]>([])
const downloadedLoading = ref(true)
const downloadedLoaded = ref(false)
const downloadedError = ref('')

const usernameInput = ref('')
const usernameSaving = ref(false)
const usernameError = ref('')

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const passwordSaving = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')

const avatarInitial = computed(() => (auth.username || '?').charAt(0).toUpperCase())

async function loadProfile() {
  profileLoading.value = true
  try {
    const response = await usersApi.getProfile()
    const body = response.data
    if (isApiSuccess(body)) {
      email.value = body.data.email
      usernameInput.value = body.data.username
      auth.setProfile({
        id: body.data._id,
        email: body.data.email,
        username: body.data.username,
        isAdmin: body.data.isAdmin,
      })
      if (body.data.createdAt) {
        memberSince.value = new Date(body.data.createdAt).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
        })
      }
    }
  } finally {
    profileLoading.value = false
  }
}

async function loadSaved() {
  savedLoading.value = true
  savedError.value = ''
  try {
    const response = await usersApi.getSavedBooks()
    const body = response.data
    savedBooks.value = isApiSuccess(body) ? body.data : []
  } catch {
    savedError.value = 'There was a problem loading your saved books.'
  } finally {
    savedLoading.value = false
  }
}

async function loadDownloaded() {
  if (downloadedLoaded.value) return
  downloadedLoading.value = true
  downloadedError.value = ''
  try {
    const response = await usersApi.getDownloadedBooks()
    const body = response.data
    downloadedBooks.value = isApiSuccess(body) ? body.data : []
    downloadedLoaded.value = true
  } catch {
    downloadedError.value = 'There was a problem loading your downloaded books.'
  } finally {
    downloadedLoading.value = false
  }
}

function selectTab(tab: Tab) {
  activeTab.value = tab
  if (tab === 'downloaded') {
    loadDownloaded()
  }
}

async function removeSavedBook(book: Book) {
  const id = idToString(book._id)
  try {
    const response = await usersApi.removeBook(id)
    const body = response.data
    if (isApiSuccess(body)) {
      savedBooks.value = savedBooks.value.filter((b) => idToString(b._id) !== id)
      toast.success('Removed from saved books')
    } else {
      toast.error(apiErrorMessage(body as ApiError, 'Failed to remove book'))
    }
  } catch {
    toast.error('An error occurred while removing the book.')
  }
}

async function saveUsername() {
  usernameError.value = ''
  const trimmed = usernameInput.value.trim()
  if (trimmed.length < 3) {
    usernameError.value = 'Username must be at least 3 characters'
    return
  }
  usernameSaving.value = true
  try {
    const response = await usersApi.updateProfile(trimmed)
    const body = response.data
    if (isApiSuccess(body)) {
      auth.setProfile({
        id: auth.profile?.id ?? '',
        email: email.value,
        username: trimmed,
        isAdmin: auth.isAdmin,
      })
      toast.success('Username updated successfully')
    } else {
      usernameError.value = apiErrorMessage(body as ApiError, 'Failed to update username')
    }
  } catch (err: any) {
    usernameError.value = err.response?.data?.message ?? 'An error occurred. Please try again.'
  } finally {
    usernameSaving.value = false
  }
}

async function submitPasswordChange() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (newPassword.value !== confirmPassword.value) {
    passwordError.value = 'New password and confirmation do not match.'
    return
  }
  if (newPassword.value.length < 8) {
    passwordError.value = 'New password must be at least 8 characters.'
    return
  }

  passwordSaving.value = true
  try {
    const response = await usersApi.changePassword(currentPassword.value, newPassword.value)
    const body = response.data
    if (isApiSuccess(body)) {
      passwordSuccess.value = 'Password updated successfully.'
      toast.success('Password updated successfully')
      currentPassword.value = ''
      newPassword.value = ''
      confirmPassword.value = ''
    } else {
      passwordError.value = apiErrorMessage(body as ApiError, 'Could not update password.')
    }
  } catch (err: any) {
    passwordError.value = err.response?.data?.message ?? 'Could not update password. Please try again.'
  } finally {
    passwordSaving.value = false
  }
}

onMounted(() => {
  loadProfile()
  loadSaved()
})
</script>

<template>
  <div class="container profile-page">
    <div class="card profile-header">
      <div class="avatar-lg">{{ avatarInitial }}</div>
      <div>
        <h1>{{ auth.username || 'User' }}</h1>
        <p class="text-muted">{{ profileLoading ? 'Loading…' : email }}</p>
        <p v-if="memberSince" class="text-muted small">Member since {{ memberSince }}</p>
      </div>
    </div>

    <div class="tabs">
      <button type="button" :class="{ active: activeTab === 'saved' }" @click="selectTab('saved')">Saved Books</button>
      <button type="button" :class="{ active: activeTab === 'downloaded' }" @click="selectTab('downloaded')">Downloaded</button>
      <button type="button" :class="{ active: activeTab === 'account' }" @click="selectTab('account')">Account</button>
    </div>

    <section v-if="activeTab === 'saved'" class="tab-panel">
      <div v-if="savedLoading" class="spinner" role="status" aria-label="Loading saved books"></div>
      <p v-else-if="savedError" class="text-muted">{{ savedError }}</p>
      <div v-else-if="!savedBooks.length" class="empty-state">
        <p>You haven't saved any books to your list yet.</p>
        <RouterLink to="/browse" class="btn btn-primary">Browse Books</RouterLink>
      </div>
      <div v-else class="grid-books">
        <BookCard v-for="book in savedBooks" :key="String(book._id)" :book="book" removable @remove="removeSavedBook(book)" />
      </div>
    </section>

    <section v-else-if="activeTab === 'downloaded'" class="tab-panel">
      <div v-if="downloadedLoading" class="spinner" role="status" aria-label="Loading downloaded books"></div>
      <p v-else-if="downloadedError" class="text-muted">{{ downloadedError }}</p>
      <div v-else-if="!downloadedBooks.length" class="empty-state">
        <p>Books you download will appear here.</p>
        <RouterLink to="/browse" class="btn btn-primary">Browse Books</RouterLink>
      </div>
      <div v-else class="grid-books">
        <BookCard v-for="book in downloadedBooks" :key="String(book._id)" :book="book" />
      </div>
    </section>

    <section v-else class="tab-panel account-panel">
      <div class="card account-card">
        <h3>Edit username</h3>
        <p v-if="usernameError" class="alert alert-danger">{{ usernameError }}</p>
        <div class="form-field">
          <label for="profile-username">Username</label>
          <input id="profile-username" v-model="usernameInput" type="text" minlength="3" required />
        </div>
        <button type="button" class="btn btn-primary" :disabled="usernameSaving" @click="saveUsername">
          {{ usernameSaving ? 'Saving…' : 'Save' }}
        </button>
      </div>

      <div class="card account-card">
        <h3>Change password</h3>
        <p v-if="passwordError" class="alert alert-danger">{{ passwordError }}</p>
        <p v-if="passwordSuccess" class="alert alert-success">{{ passwordSuccess }}</p>
        <form @submit.prevent="submitPasswordChange">
          <div class="form-field">
            <label for="current-password">Current password</label>
            <input id="current-password" v-model="currentPassword" type="password" autocomplete="current-password" required />
          </div>
          <div class="form-field">
            <label for="new-password">New password</label>
            <input id="new-password" v-model="newPassword" type="password" minlength="8" autocomplete="new-password" required />
          </div>
          <div class="form-field">
            <label for="confirm-new-password">Confirm new password</label>
            <input id="confirm-new-password" v-model="confirmPassword" type="password" minlength="8" autocomplete="new-password" required />
          </div>
          <button type="submit" class="btn btn-primary" :disabled="passwordSaving">
            {{ passwordSaving ? 'Updating…' : 'Update password' }}
          </button>
        </form>
      </div>
    </section>
  </div>
</template>

<style scoped src="./Profile.css"></style>
