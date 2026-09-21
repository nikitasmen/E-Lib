<script setup lang="ts">
import NavBar from '@/components/NavBar.vue'
import Footer from '@/components/Footer.vue'
import ToastContainer from '@/components/ToastContainer.vue'
</script>

<template>
  <div class="app-shell">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <NavBar />
    <main id="main-content" class="app-main">
      <RouterView />
    </main>
    <Footer />
    <ToastContainer />
  </div>
</template>

<style scoped src="@/css/App.css"></style>
