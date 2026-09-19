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

<style scoped src="./ReviewList.css"></style>
