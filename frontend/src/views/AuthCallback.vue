<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import * as usersApi from '@/api/users'
import { isApiSuccess } from '@/types/api'

const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

onMounted(async () => {
  const hash = window.location.hash.replace(/^#/, '')
  const params = new URLSearchParams(hash)
  const token = params.get('token')

  if (!token) {
    toast.error('CAS login failed: no token received')
    router.replace('/login')
    return
  }

  // The CAS callback only carries a token; fetch the profile to populate the username.
  auth.setToken(token)
  try {
    const response = await usersApi.getProfile()
    const body = response.data
    if (isApiSuccess(body)) {
      auth.setProfile({
        id: body.data._id,
        email: body.data.email,
        username: body.data.username,
        isAdmin: body.data.isAdmin,
      })
    }
  } catch {
    // Non-fatal: the navbar falls back to the JWT's email claim.
  }

  toast.success('Logged in via CAS')
  router.replace('/')
})
</script>

<template>
  <div class="container callback-page">
    <div class="spinner" role="status" aria-label="Signing you in"></div>
    <p class="text-muted">Signing you in…</p>
  </div>
</template>

<style scoped>
.callback-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem 1.5rem;
}
</style>
