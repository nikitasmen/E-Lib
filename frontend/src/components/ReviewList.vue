<script setup lang="ts">
import type { Review } from '@/types/review'
import StarRating from '@/components/StarRating.vue'

defineProps<{ reviews: Review[] }>()
</script>

<template>
  <div class="review-list">
    <h3>Reviews</h3>
    <p v-if="!reviews.length" class="text-muted">No reviews yet.</p>
    <ul v-else class="reviews">
      <li v-for="(review, index) in reviews" :key="index" class="review">
        <div class="review-header">
          <strong>{{ review.username }}</strong>
          <StarRating :rating="review.rating" />
        </div>
        <p class="review-comment">{{ review.comment }}</p>
        <span class="review-date text-muted">{{ review.created_at }}</span>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.reviews {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.review {
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 0.85rem;
}

.review-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.3rem;
}

.review-comment {
  margin: 0.2rem 0;
}

.review-date {
  font-size: 0.8rem;
}
</style>
