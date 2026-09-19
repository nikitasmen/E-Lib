<script setup lang="ts">
defineProps<{ rating: number; max?: number }>()
</script>

<template>
  <span class="star-rating" :aria-label="`Rated ${rating} out of ${max ?? 5}`">
    <span v-for="i in max ?? 5" :key="i" class="star" :class="{ filled: i <= Math.round(rating) }">★</span>
  </span>
</template>

<style scoped>
.star-rating {
  display: inline-flex;
  gap: 0.1rem;
}

.star {
  color: var(--color-border);
  font-size: 0.95rem;
}

.star.filled {
  color: var(--color-accent);
}
</style>
