<script setup lang="ts">
import { computed } from 'vue'
import type { Book } from '@/types/book'
import { idToString } from '@/types/book'
import { thumbnailUrl } from '@/api/books'

const props = defineProps<{ book: Book; removable?: boolean }>()
const emit = defineEmits<{ remove: [] }>()

const id = computed(() => idToString(props.book._id))
const cover = computed(() => props.book.thumbnail || thumbnailUrl(id.value))
const visibleCategories = computed(() => (props.book.categories ?? []).slice(0, 2))
const extraCategoryCount = computed(() => Math.max((props.book.categories?.length ?? 0) - 2, 0))

function onImgError(event: Event) {
  ;(event.target as HTMLImageElement).src = '/assets/uploads/thumbnails/placeholder-book.jpg'
}
</script>

<template>
  <RouterLink :to="`/books/${id}`" class="book-card">
    <button
      v-if="removable"
      type="button"
      class="remove-btn"
      aria-label="Remove from saved books"
      @click.stop.prevent="emit('remove')"
    >
      &times;
    </button>
    <img :src="cover" class="cover" :alt="`Cover of ${book.title}`" @error="onImgError" />
    <div class="body">
      <h3 class="title" :title="book.title">{{ book.title || 'Unknown Title' }}</h3>
      <p class="author">By {{ book.author || 'Unknown Author' }}</p>
      <p v-if="book.year" class="year">{{ book.year }}</p>
      <div v-if="visibleCategories.length" class="categories">
        <span v-for="category in visibleCategories" :key="category" class="badge">{{ category }}</span>
        <span v-if="extraCategoryCount > 0" class="badge">+{{ extraCategoryCount }} more</span>
      </div>
      <span class="view-link">View details</span>
    </div>
  </RouterLink>
</template>

<style scoped>
.book-card {
  position: relative;
  display: flex;
  flex-direction: column;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  color: var(--color-text);
  transition: box-shadow 0.15s ease, transform 0.15s ease;
}

.remove-btn {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 50%;
  border: none;
  background: rgba(20, 34, 54, 0.75);
  color: #fff;
  font-size: 1.1rem;
  line-height: 1;
  cursor: pointer;
  z-index: 1;
}

.remove-btn:hover {
  background: var(--color-danger);
}

.book-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
  text-decoration: none;
}

.cover {
  width: 100%;
  height: 220px;
  object-fit: cover;
  background: var(--color-bg);
}

.body {
  padding: 0.9rem 1rem 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.title {
  font-size: 1rem;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.author,
.year {
  margin: 0;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.categories {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  margin: 0.3rem 0;
}

.badge {
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: 999px;
  font-size: 0.7rem;
  padding: 0.15rem 0.55rem;
  color: var(--color-text-muted);
}

.view-link {
  margin-top: auto;
  padding-top: 0.5rem;
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--color-primary);
}
</style>
