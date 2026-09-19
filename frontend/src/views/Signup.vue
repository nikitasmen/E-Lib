<script setup lang="ts">
import { useRouter } from 'vue-router'
import SignupForm from '@/components/SignupForm.vue'

const router = useRouter()
</script>

<template>
  <div class="container auth-page">
    <div class="card auth-card">
      <SignupForm @switch-to-login="router.push('/login')" />
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  display: flex;
  justify-content: center;
  padding: 3rem 1.5rem;
}

.auth-card {
  width: 100%;
  max-width: 420px;
  padding: 2rem;
}
</style>
